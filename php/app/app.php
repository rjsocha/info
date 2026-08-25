<?php

const ARCHITECTURES = [
    'x86_64' => 'amd64',
    'amd64' => 'amd64',
    'aarch64' => 'arm64',
    'arm64' => 'arm64',
];

function section(string $title, array $rows): string
{
    $width = 0;
    foreach ($rows as [$key, $value]) {
        $length = strlen($key);
        if ($length > $width && $length <= 30) {
            $width = $length;
        }
    }

    $out = sprintf("[%s]\n", $title);
    foreach ($rows as [$key, $value]) {
        $value = str_replace(["\r\n", "\n", "\r"], ' ', (string) $value);
        $out .= sprintf("%-{$width}s  %s\n", $key, $value);
    }

    return $out . "\n";
}

function started(): int
{
    if (PHP_OS_FAMILY !== 'Windows') {
        $start = @filemtime('/proc/self');
        if ($start !== false) {
            return $start;
        }
    }

    $marker = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'info-php.started';
    if (!file_exists($marker)) {
        @touch($marker);
    }

    return @filemtime($marker) ?: time();
}

function uptime(): string
{
    $seconds = max(0, time() - started());

    $hours = intdiv($seconds, 3600);
    $minutes = intdiv($seconds % 3600, 60);
    $seconds = $seconds % 60;

    if ($hours) {
        return sprintf('%dh%dm%ds', $hours, $minutes, $seconds);
    }
    if ($minutes) {
        return sprintf('%dm%ds', $minutes, $seconds);
    }

    return sprintf('%ds', $seconds);
}

function command_line(): string
{
    $cmdline = @file_get_contents('/proc/self/cmdline');
    if ($cmdline !== false && $cmdline !== '') {
        return trim(str_replace("\0", ' ', $cmdline));
    }

    return PHP_BINARY;
}

function architecture(): string
{
    $machine = strtolower(php_uname('m'));

    return ARCHITECTURES[$machine] ?? ($machine ?: '-');
}

function os_description(): string
{
    if (PHP_OS_FAMILY === 'Windows') {
        return sprintf('%s %s.%s', php_uname('s'), php_uname('r'), php_uname('v'));
    }

    $release = @file('/etc/os-release', FILE_IGNORE_NEW_LINES);
    if ($release !== false) {
        foreach ($release as $line) {
            if (str_starts_with($line, 'PRETTY_NAME=')) {
                return trim(substr($line, strlen('PRETTY_NAME=')), '"');
            }
        }
    }

    return PHP_OS_FAMILY;
}

function processors(): string
{
    if (PHP_OS_FAMILY === 'Windows') {
        return getenv('NUMBER_OF_PROCESSORS') ?: '-';
    }

    $cpuinfo = @file_get_contents('/proc/cpuinfo');
    if ($cpuinfo !== false) {
        $count = preg_match_all('/^processor\s*:/m', $cpuinfo);
        if ($count) {
            return (string) $count;
        }
    }

    return '-';
}

function mb(float $bytes): string
{
    return sprintf('%.1f MB', $bytes / 1024 / 1024);
}

function memory_limit(): string
{
    foreach (['/sys/fs/cgroup/memory.max', '/sys/fs/cgroup/memory/limit_in_bytes'] as $path) {
        $value = @file_get_contents($path);
        if ($value === false) {
            continue;
        }

        $value = trim($value);
        if ($value === 'max') {
            break;
        }

        if (ctype_digit($value) && (float) $value < 4.6e18) {
            return mb((float) $value);
        }
    }

    $meminfo = @file_get_contents('/proc/meminfo');
    if ($meminfo !== false && preg_match('/^MemTotal:\s+(\d+) kB/m', $meminfo, $found)) {
        return mb((float) $found[1] * 1024);
    }

    return '-';
}

function addresses(): array
{
    $found = @gethostbynamel(gethostname());
    if (!$found) {
        return [];
    }

    return [['addresses', implode(', ', $found)]];
}

function request_rows(): array
{
    $remote = ($_SERVER['REMOTE_ADDR'] ?? '') . ':' . ($_SERVER['REMOTE_PORT'] ?? '');
    $local = ($_SERVER['SERVER_ADDR'] ?? $_SERVER['SERVER_NAME'] ?? '') . ':' . ($_SERVER['SERVER_PORT'] ?? '');

    return [
        ['method', $_SERVER['REQUEST_METHOD'] ?? ''],
        ['path', $_SERVER['REQUEST_URI'] ?? ''],
        ['protocol', $_SERVER['SERVER_PROTOCOL'] ?? ''],
        ['scheme', 'http'],
        ['host header', $_SERVER['HTTP_HOST'] ?? ''],
        ['remote', trim($remote, ':')],
        ['local', trim($local, ':')],
    ];
}

function report(bool $verbose, bool $with_environment): string
{
    $runtime = [
        ['hostname', gethostname() ?: '-'],
        ['uptime', uptime()],
        ['framework', 'PHP ' . PHP_VERSION],
        ['runtime identifier', strtolower(PHP_OS_FAMILY) . '-' . architecture()],
        ['os', os_description()],
        ['architecture', architecture()],
        ['processors', processors()],
    ];

    if ($verbose) {
        $runtime[] = ['sapi', PHP_SAPI];
        $runtime[] = ['zend engine', zend_version()];
        $runtime[] = ['thread safety', PHP_ZTS ? 'zts' : 'nts'];
    } else {
        $runtime[] = ['ram', memory_limit()];
    }

    $node = getenv('RUNTIME_NODE');
    if ($node) {
        $details = [];
        foreach (['RUNTIME_NODE_ID', 'RUNTIME_TASK', 'RUNTIME_SLOT'] as $name) {
            $value = getenv($name);
            if ($value) {
                $details[] = $value;
            }
        }

        if ($details) {
            $node .= ' (' . implode(' / ', $details) . ')';
        }

        array_unshift($runtime, ['node', $node]);
    }

    $out = section('runtime', $runtime);

    if ($verbose) {
        $out .= section('process', [
            ['pid', getmypid()],
            ['executable', PHP_BINARY],
            ['working directory', getcwd()],
            ['command line', command_line()],
            ['extensions', count(get_loaded_extensions())],
        ]);

        $gc = gc_status();

        $out .= section('memory', [
            ['in use', mb((float) memory_get_usage(true))],
            ['peak', mb((float) memory_get_peak_usage(true))],
            ['limit', ini_get('memory_limit')],
            ['available', memory_limit()],
            ['gc runs', $gc['runs'] ?? 0],
            ['gc collected', $gc['collected'] ?? 0],
        ]);
    }

    $out .= section('network', addresses());
    $out .= section('request', request_rows());

    if ($verbose) {
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        ksort($headers);

        $rows = [];
        foreach ($headers as $name => $value) {
            $rows[] = [$name, $value];
        }

        $out .= section('headers', $rows);
    }

    if ($with_environment) {
        $environment = getenv();
        ksort($environment);

        $rows = [];
        foreach ($environment as $name => $value) {
            $rows[] = [$name, $value];
        }

        $out .= section('environment', $rows);
    }

    return $out;
}

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$environment = str_starts_with($path, '/env');
$verbose = $environment || str_starts_with($path, '/verbose');

header('Content-Type: text/plain; charset=utf-8');
header('Connection: close');

echo report($verbose, $environment);
