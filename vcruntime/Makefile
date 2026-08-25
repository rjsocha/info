IMAGE    ?= wyga/vcruntime
REDIST   ?= https://aka.ms/vc14/vc_redist.x64.exe
PLATFORM ?= windows/amd64
MAJOR    ?= 14
BUILDER  ?= web-runtime-builder
DIST     ?= dist

VERSION = $(shell cat $(DIST)/version 2>/dev/null)

OUT = --provenance=false --sbom=false --output type=image,oci-mediatypes=false,push=true

.PHONY: all image version builder clean

all: image

image: builder $(DIST)/version
	docker buildx build --pull --builder $(BUILDER) --platform $(PLATFORM) -f Dockerfile \
	  --build-arg REDIST=$(REDIST) \
	  -t $(IMAGE):$(MAJOR) -t $(IMAGE):$(VERSION) $(OUT) .

version: $(DIST)/version
	@cat $(DIST)/version

$(DIST)/version: builder
	docker buildx build --pull --builder $(BUILDER) -f Dockerfile --target version \
	  --build-arg REDIST=$(REDIST) \
	  --output type=local,dest=$(DIST) .

builder:
	docker buildx inspect $(BUILDER) >/dev/null 2>&1 || docker buildx create --name $(BUILDER) --driver docker-container --bootstrap >/dev/null

clean:
	rm -rf $(DIST)
