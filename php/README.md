# php

Runtime-info page over HTTP on the built-in server - `php -S`, no framework and
no `php.ini`, so no extension is loaded beyond what the binary carries.

```sh
make image        # wyga/info-php:linux, :2019, :2022, :2025
make index        # wyga/info-php:latest
make run
```

Linux sits on the official `php:$(PHP_VERSION)-cli`. Windows takes the official
build from `downloads.php.net`, unpacked on the build host and copied onto Nano
Server whole - `ext/`, `dev/` and `lib/` included.

`php.exe` and `php8ts.dll` import `VCRUNTIME140.dll`, which Nano Server does not
have: it ships `msvcp110_win.dll`, `msvcp120.dll` and `msvcp_win.dll`, nothing
from the 14.x line. Three files come from `wyga/vcruntime` and land next to
`php.exe`, where the loader looks first: `vcruntime140.dll` for the binary
itself, plus `vcruntime140_1.dll` and `msvcp140.dll` for the extensions written
in C++.

The interpreter lands in `C:\Php` and the app in `C:\App`. Nothing runs in the
Windows stage, so the whole image builds from Linux through buildx.
