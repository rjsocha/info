# vcruntime

The Visual C++ runtime as a payload image. Nano Server carries the UCRT but
nothing from the 14.x redistributable, so anything built with the MSVC
toolchain - PHP among them - needs these DLLs copied in.

```sh
make version      # 14.51.36247
make image        # wyga/vcruntime:14 and wyga/vcruntime:<version>
```

Downloading and unpacking happen inside the build. `aka.ms/vc14/vc_redist.x64.exe`
always points at the newest 14.x release and the file is a Burn container: the
first `7zz` pass yields an inner cabinet, the second unpacks it into MSI files
and cabinets, and the third pulls every `*_amd64` member out of whichever
cabinet holds them. That cabinet is not at a fixed index between releases,
hence the loop. Only the `_amd64` suffix is matched, so the other
architectures stay behind.

The version is read from `vcruntime140.dll` in the same stage, and Docker
cannot tag an image with something computed inside the build. The `version`
stage exports that one line to `dist/version` so the Makefile can name the tag;
the build cache means the unpacking still runs once.

Nothing here is Windows-version specific. A `scratch` image inherits no
`os.version`, so one `windows/amd64` entry serves every Nano Server base, and
the DLLs themselves are identical across them.

Consumers reach the files at `/vcruntime`:

```dockerfile
COPY --from=wyga/vcruntime:14 /vcruntime/vcruntime140.dll /Php/
```
