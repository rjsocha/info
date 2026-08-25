import gc
import os
import platform
import socket
import sys
import threading
import time
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer

STARTED = time.monotonic()

ARCHITECTURES = {
    "x86_64": "amd64",
    "amd64": "amd64",
    "aarch64": "arm64",
    "arm64": "arm64",
}


def section(title, rows):
    width = 0
    for key, _ in rows:
        if width < len(key) <= 30:
            width = len(key)

    out = ["[%s]" % title]
    for key, value in rows:
        value = str(value).replace("\r\n", " ").replace("\n", " ").replace("\r", " ")
        out.append("%-*s  %s" % (width, key, value))

    out.append("")
    return "\n".join(out) + "\n"


def uptime():
    seconds = int(time.monotonic() - STARTED)
    hours, rest = divmod(seconds, 3600)
    minutes, seconds = divmod(rest, 60)

    if hours:
        return "%dh%dm%ds" % (hours, minutes, seconds)
    if minutes:
        return "%dm%ds" % (minutes, seconds)
    return "%ds" % seconds


def hostname():
    try:
        return socket.gethostname()
    except OSError:
        return "-"


def system():
    return "windows" if os.name == "nt" else sys.platform


def architecture():
    machine = platform.machine().lower()
    return ARCHITECTURES.get(machine, machine or "-")


def os_description():
    if os.name == "nt":
        version = sys.getwindowsversion()
        return "Windows %d.%d.%d" % (version.major, version.minor, version.build)

    try:
        with open("/etc/os-release", encoding="utf-8") as release:
            for line in release:
                if line.startswith("PRETTY_NAME="):
                    return line[len("PRETTY_NAME="):].strip().strip('"')
    except OSError:
        pass

    return system()


def mb(value):
    return "%.1f MB" % (value / 1024 / 1024)


def memory_limit():
    for path in ("/sys/fs/cgroup/memory.max", "/sys/fs/cgroup/memory/limit_in_bytes"):
        try:
            with open(path, encoding="utf-8") as limits:
                value = limits.read().strip()
        except OSError:
            continue

        if value == "max":
            break

        try:
            limit = int(value)
        except ValueError:
            continue

        if limit < 1 << 62:
            return mb(limit)

    total = mem_total()
    if total:
        return mb(total)

    return "-"


def mem_total():
    try:
        with open("/proc/meminfo", encoding="utf-8") as meminfo:
            for line in meminfo:
                if line.startswith("MemTotal:"):
                    return int(line.split()[1]) * 1024
    except (OSError, IndexError, ValueError):
        pass

    return 0


def addresses():
    try:
        found = socket.getaddrinfo(hostname(), None)
    except OSError:
        return []

    unique = []
    for entry in found:
        address = entry[4][0]
        if address not in unique:
            unique.append(address)

    if not unique:
        return []

    return [("addresses", ", ".join(unique))]


def endpoint(address):
    if not isinstance(address, tuple) or len(address) < 2:
        return str(address)

    host, port = address[0], address[1]
    if ":" in host:
        return "[%s]:%s" % (host, port)

    return "%s:%s" % (host, port)


def report(handler, verbose, with_environment):
    out = []

    runtime = [
        ("hostname", hostname()),
        ("uptime", uptime()),
        ("framework", "%s %s" % (platform.python_implementation(), platform.python_version())),
        ("runtime identifier", "%s-%s" % (system(), architecture())),
        ("os", os_description()),
        ("architecture", architecture()),
        ("processors", os.cpu_count() or "-"),
    ]

    if verbose:
        gil = getattr(sys, "_is_gil_enabled", None)
        runtime.append(("gil", "enabled" if gil is None or gil() else "disabled"))
        runtime.append(("compiler", platform.python_compiler()))
    else:
        runtime.append(("ram", memory_limit()))

    node = os.environ.get("RUNTIME_NODE")
    if node:
        details = []
        for name in ("RUNTIME_NODE_ID", "RUNTIME_TASK", "RUNTIME_SLOT"):
            value = os.environ.get(name)
            if value:
                details.append(value)

        if details:
            node += " (%s)" % " / ".join(details)

        runtime.insert(0, ("node", node))

    out.append(section("runtime", runtime))

    if verbose:
        out.append(section("process", [
            ("pid", os.getpid()),
            ("executable", sys.executable),
            ("working directory", os.getcwd()),
            ("command line", " ".join(sys.argv)),
            ("threads", threading.active_count()),
        ]))

        counts = gc.get_count()
        collections = sum(stats["collections"] for stats in gc.get_stats())

        out.append(section("memory", [
            ("tracked objects", len(gc.get_objects())),
            ("gc counts", "%d / %d / %d" % counts),
            ("gc thresholds", "%d / %d / %d" % gc.get_threshold()),
            ("collections", collections),
            ("available", memory_limit()),
        ]))

    out.append(section("network", addresses()))
    out.append(section("request", request(handler)))

    if verbose:
        headers = []
        for name in sorted({name for name, _ in handler.headers.items()}):
            headers.append((name, ", ".join(handler.headers.get_all(name, []))))

        out.append(section("headers", headers))

    if with_environment:
        environment = [(name, os.environ[name]) for name in sorted(os.environ)]
        out.append(section("environment", environment))

    return "".join(out)


def request(handler):
    try:
        local = endpoint(handler.connection.getsockname())
    except OSError:
        local = ""

    return [
        ("method", handler.command),
        ("path", handler.path),
        ("protocol", handler.request_version),
        ("scheme", "http"),
        ("host header", handler.headers.get("Host", "")),
        ("remote", endpoint(handler.client_address)),
        ("local", local),
    ]


class Handler(BaseHTTPRequestHandler):
    protocol_version = "HTTP/1.1"
    server_version = "info-python"
    sys_version = ""

    def do_GET(self):
        path = self.path.split("?", 1)[0]
        environment = path.startswith("/env")
        verbose = environment or path.startswith("/verbose")

        body = report(self, verbose, environment).encode("utf-8")

        self.send_response(200)
        self.send_header("Content-Type", "text/plain; charset=utf-8")
        self.send_header("Content-Length", str(len(body)))
        self.send_header("Connection", "close")
        self.end_headers()
        self.wfile.write(body)

        self.close_connection = True

    def log_message(self, fmt, *args):
        pass


def main():
    port = int(os.environ.get("PORT") or "8080")

    try:
        server = ThreadingHTTPServer(("", port), Handler)
    except OSError as error:
        print("cannot listen on :%d: %s" % (port, error), file=sys.stderr)
        raise SystemExit(1)

    server.daemon_threads = True
    server.serve_forever()


if __name__ == "__main__":
    main()
