IMAGE   ?= wyga/info-dotnet-aot
WINHOST ?= wx
WINDIR  ?= C:/build/web-runtime-dotnet-aot
ARMHOST ?= dotnet-arm64-docker
ARMBUILDER ?= web-runtime-arm
WINDOWS  ?= 2019:ltsc2019 2022:ltsc2022 2025:ltsc2025
LINUX_TAG ?= linux
LATEST   ?= latest
LINUX_PLATFORMS  ?= linux/amd64,linux/arm64
WINDOWS_PLATFORM ?= windows/amd64
BUILDER  ?= web-runtime-builder
RUN_TAG  ?= latest
PORT     ?= 8080

comma := ,

OUT = --provenance=false --sbom=false --output type=image,oci-mediatypes=false,push=true

.PHONY: all image linux windows index publish builder run clean

.NOTPARALLEL:

all: image index

image: linux windows

linux: builder dist/linux-arm64/app
	docker buildx build --pull --builder $(BUILDER) --platform $(LINUX_PLATFORMS) -f Dockerfile \
	  -t $(IMAGE):$(LINUX_TAG) $(OUT) .

windows: builder dist/win-x64/app.exe
	for v in $(WINDOWS); do \
	  tag=$${v%%:*}; \
	  docker buildx build --pull --builder $(BUILDER) --platform $(WINDOWS_PLATFORM) -f Dockerfile \
	    --build-arg BASE_WINDOWS=mcr.microsoft.com/windows/nanoserver:$${v##*:} \
	    -t $(IMAGE):$$tag $(OUT) . ; \
	done

index:
	regctl index create $(IMAGE):$(LATEST) \
	  -m application/vnd.docker.distribution.manifest.list.v2+json \
	  --ref $(IMAGE):$(LINUX_TAG) \
	  $(foreach p,$(subst $(comma), ,$(LINUX_PLATFORMS)),--platform $(p))
	regctl index add $(IMAGE):$(LATEST) \
	  $(foreach v,$(WINDOWS),--ref $(IMAGE):$(firstword $(subst :, ,$(v))))

publish: dist/win-x64/app.exe dist/linux-arm64/app

dist/linux-arm64/app: app/app.csproj app/Program.cs
	DOCKER_HOST=ssh://$(ARMHOST) docker buildx inspect $(ARMBUILDER) >/dev/null 2>&1 || DOCKER_HOST=ssh://$(ARMHOST) docker buildx create --name $(ARMBUILDER) --driver docker-container --bootstrap >/dev/null
	DOCKER_HOST=ssh://$(ARMHOST) docker buildx build --pull --builder $(ARMBUILDER) --platform linux/arm64 -f Dockerfile.publish --output type=local,dest=dist/linux-arm64 app

dist/win-x64/app.exe: app/app.csproj app/Program.cs
	ssh $(WINHOST) powershell -NoProfile -Command "New-Item -ItemType Directory -Force -Path $(WINDIR) | Out-Null"
	scp -q -r app/. $(WINHOST):$(WINDIR)/
	ssh $(WINHOST) dotnet publish $(WINDIR)/app.csproj -c Release -r win-x64 -o $(WINDIR)/out
	mkdir -p dist/win-x64
	scp -q $(WINHOST):"$(WINDIR)/out/app.exe" dist/win-x64/app.exe

builder:
	docker buildx inspect $(BUILDER) >/dev/null 2>&1 || docker buildx create --name $(BUILDER) --driver docker-container --bootstrap >/dev/null

run:
	docker run --rm -p $(PORT):8080 $(IMAGE):$(RUN_TAG)

clean:
	docker buildx rm $(BUILDER) 2>/dev/null || true
	docker rmi -f $(IMAGE):$(RUN_TAG) 2>/dev/null || true
	rm -rf dist
