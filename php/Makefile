IMAGE    ?= wyga/info-php
PHP_VERSION ?= 8.5.9
VCRUNTIME ?= wyga/vcruntime:14
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

.PHONY: all image linux windows index builder run clean

.NOTPARALLEL:

all: image index

image: linux windows

linux: builder
	docker buildx build --pull --builder $(BUILDER) --platform $(LINUX_PLATFORMS) -f Dockerfile \
	  --build-arg PHP_VERSION=$(PHP_VERSION) \
	  -t $(IMAGE):$(LINUX_TAG) $(OUT) app

windows: builder
	for v in $(WINDOWS); do \
	  tag=$${v%%:*}; \
	  docker buildx build --pull --builder $(BUILDER) --platform $(WINDOWS_PLATFORM) -f Dockerfile \
	    --build-arg BASE_WINDOWS=mcr.microsoft.com/windows/nanoserver:$${v##*:} \
	    --build-arg PHP_VERSION=$(PHP_VERSION) \
	    --build-arg VCRUNTIME=$(VCRUNTIME) \
	    -t $(IMAGE):$$tag $(OUT) app ; \
	done

index:
	regctl index create $(IMAGE):$(LATEST) \
	  -m application/vnd.docker.distribution.manifest.list.v2+json \
	  --ref $(IMAGE):$(LINUX_TAG) \
	  $(foreach p,$(subst $(comma), ,$(LINUX_PLATFORMS)),--platform $(p))
	regctl index add $(IMAGE):$(LATEST) \
	  $(foreach v,$(WINDOWS),--ref $(IMAGE):$(firstword $(subst :, ,$(v))))

builder:
	docker buildx inspect $(BUILDER) >/dev/null 2>&1 || docker buildx create --name $(BUILDER) --driver docker-container --bootstrap >/dev/null

run:
	docker run --rm -p $(PORT):8080 $(IMAGE):$(RUN_TAG)

clean:
	docker buildx rm $(BUILDER) 2>/dev/null || true
	docker rmi -f $(IMAGE):$(RUN_TAG) 2>/dev/null || true
