# For demo purposes only ...

Web runtime-info apps as multi-platform container images.

| project | language |
|---|---|
| [cpp](cpp/) | C++20, Boost.Beast, static binary |
| [dotnet](dotnet/) | C# / ASP.NET Core on .NET 10 |
| [dotnet-aot](dotnet-aot/) | C# / ASP.NET Core published with NativeAOT |
| [go](go/) | Go, net/http, static binary |
| [java](java/) | Java 25 LTS, com.sun.net.httpserver, virtual threads |
| [python](python/) | Python 3.14, http.server, stdlib only |
| [php](php/) | PHP 8.5, built-in server, no framework |
| [rust](rust/) | Rust, hyper on tokio, static binary |

## Run

Every app listens on 8080 inside the container.

```sh
docker run --rm -p 8080:8080 wyga/info-go:latest
curl localhost:8080
curl localhost:8080/verbose
curl localhost:8080/env
```

## Build

Each project builds with `make` and pushes to the registry. `IMAGE` names the
target, so another registry needs no edit:

```sh
make -C go image IMAGE=registry.example.com/team/info-go
make -C dotnet all IMAGE=registry.example.com/team/info-dotnet
```

In dotnet the payload follows `IMAGE` as `$(IMAGE)-payload:latest` and reaches
the runtime build as a build argument; `PAYLOAD` overrides it on its own.

## Manifest

What a merged tag carries:

```sh
regctl manifest get wyga/info-go:latest
```

```
Name:        wyga/info-go:latest
MediaType:   application/vnd.docker.distribution.manifest.list.v2+json
Digest:      sha256:0ec1dfa2d83ea80cbd1d941b02ad21f969860894c90275a1bdff12b185ca9cbb

Manifests:

  Name:      docker.io/wyga/info-go:latest@sha256:f5325114ba69...
  MediaType: application/vnd.docker.distribution.manifest.v2+json
  Platform:  linux/amd64

  Name:      docker.io/wyga/info-go:latest@sha256:5406339fc535...
  MediaType: application/vnd.docker.distribution.manifest.v2+json
  Platform:  linux/arm64

  Name:      docker.io/wyga/info-go:latest@sha256:af3a769bffdb...
  MediaType: application/vnd.docker.distribution.manifest.v2+json
  Platform:  windows/amd64
  OSVersion: 10.0.17763.9121

  Name:      docker.io/wyga/info-go:latest@sha256:1e3617b60a3b...
  MediaType: application/vnd.docker.distribution.manifest.v2+json
  Platform:  windows/amd64
  OSVersion: 10.0.20348.5499

  Name:      docker.io/wyga/info-go:latest@sha256:54ee25de39b0...
  MediaType: application/vnd.docker.distribution.manifest.v2+json
  Platform:  windows/amd64
  OSVersion: 10.0.26100.33296
```

Five entries: two Linux architectures and one Windows entry per LTSC base.
`OSVersion` is what a Windows host matches against its own build number, so
one tag serves 2019, 2022 and 2025.

Just the platforms:

```sh
regctl manifest get wyga/info-go:latest \
  --format '{{range .Manifests}}{{.Platform.OS}}/{{.Platform.Architecture}} {{.Platform.OSVersion}}{{println}}{{end}}'
```

```
linux/amd64
linux/arm64
windows/amd64 10.0.17763.9121
windows/amd64 10.0.20348.5499
windows/amd64 10.0.26100.33296
```

## Images

## Deployable

| image | platforms |
|---|---|
| `wyga/info-c:latest` | linux/amd64, linux/arm64, windows/amd64 |
| `wyga/info-dotnet:latest` | linux/amd64, linux/arm64, windows/amd64 |
| `wyga/info-dotnet-aot:latest` | linux/amd64, linux/arm64, windows/amd64 |
| `wyga/info-go:latest` | linux/amd64, linux/arm64, windows/amd64 |
| `wyga/info-java:latest` | linux/amd64, linux/arm64, windows/amd64 |
| `wyga/info-php:latest` | linux/amd64, linux/arm64, windows/amd64 |
| `wyga/info-python:latest` | linux/amd64, linux/arm64, windows/amd64 |
| `wyga/info-rust:latest` | linux/amd64, linux/arm64, windows/amd64 |
| `wyga/info-c:2019` | windows/amd64 (ltsc2019) |
| `wyga/info-c:2022` | windows/amd64 (ltsc2022) |
| `wyga/info-c:2025` | windows/amd64 (ltsc2025) |
| `wyga/info-c:linux` | linux/amd64, linux/arm64 |
| `wyga/info-dotnet:2022` | windows/amd64 (ltsc2022) |
| `wyga/info-dotnet:2025` | windows/amd64 (ltsc2025) |
| `wyga/info-dotnet:linux` | linux/amd64, linux/arm64 |
| `wyga/info-dotnet-aot:2019` | windows/amd64 (ltsc2019) |
| `wyga/info-dotnet-aot:2022` | windows/amd64 (ltsc2022) |
| `wyga/info-dotnet-aot:2025` | windows/amd64 (ltsc2025) |
| `wyga/info-dotnet-aot:linux` | linux/amd64, linux/arm64 |
| `wyga/info-go:2019` | windows/amd64 (ltsc2019) |
| `wyga/info-go:2022` | windows/amd64 (ltsc2022) |
| `wyga/info-go:2025` | windows/amd64 (ltsc2025) |
| `wyga/info-go:linux` | linux/amd64, linux/arm64 |
| `wyga/info-java:2022` | windows/amd64 (ltsc2022) |
| `wyga/info-java:2025` | windows/amd64 (ltsc2025) |
| `wyga/info-java:linux` | linux/amd64, linux/arm64 |
| `wyga/info-php:2019` | windows/amd64 (ltsc2019) |
| `wyga/info-php:2022` | windows/amd64 (ltsc2022) |
| `wyga/info-php:2025` | windows/amd64 (ltsc2025) |
| `wyga/info-php:linux` | linux/amd64, linux/arm64 |
| `wyga/info-python:2019` | windows/amd64 (ltsc2019) |
| `wyga/info-python:2022` | windows/amd64 (ltsc2022) |
| `wyga/info-python:2025` | windows/amd64 (ltsc2025) |
| `wyga/info-python:linux` | linux/amd64, linux/arm64 |
| `wyga/info-rust:2019` | windows/amd64 (ltsc2019) |
| `wyga/info-rust:2022` | windows/amd64 (ltsc2022) |
| `wyga/info-rust:2025` | windows/amd64 (ltsc2025) |
| `wyga/info-rust:linux` | linux/amd64, linux/arm64 |

## Nondeployable

| image | description |
|---|---|
| `wyga/info-dotnet-payload:latest` |  published IL, not runnable |
| `wyga/info-java-payload:latest` |  app.jar, not runnable |
| `wyga/vcruntime:14` |  Visual C++ 14.x redistributable, not runnable |

