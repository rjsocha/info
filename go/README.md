# go

Runtime-info page over HTTP, one image per Windows LTSC base. The binary is
static and needs no runtime, so the Linux entries sit on `scratch` and only the
Windows entry carries a base layer.

```sh
make image        # wyga/info-go:linux, :2019, :2022, :2025
make index        # wyga/info-go:latest
make run
```

`:linux` carries both Linux architectures and each year tag carries only its
own Windows build; `:latest` is the index over all five. The binary is
cross-compiled on the build host, so no emulation is involved.
