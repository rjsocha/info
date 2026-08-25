# python

Runtime-info page over HTTP on the standard library alone - `http.server` on
`ThreadingHTTPServer`, no dependencies.

```sh
make image        # wyga/info-python:linux, :2019, :2022, :2025
make index        # wyga/info-python:latest
make run
```

Linux sits on the official `python:$(PYTHON_VERSION)-slim`. Windows has no such image - the
`python` repository publishes only `windowsservercore` variants, which are
measured in gigabytes. Instead the Windows stage takes the official embeddable
package from python.org, unpacked on the build host, and copies it onto Nano
Server. The package carries `vcruntime140.dll`, so Nano Server needs nothing
beyond the UCRT it already has.

The interpreter lands in `C:\Python` and the app in `C:\App`. The `._pth` file
that ships with the package puts CPython in isolated mode, so `PYTHONPATH` is
ignored and the directory of the running script never reaches `sys.path`. One
file run by path does not care; a second module in `C:\App` would mean adding
that directory to `._pth`.

No `RUN` runs in the Windows stage, so the whole image builds from Linux
through buildx.
