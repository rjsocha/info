# syntax=docker/dockerfile:1
ARG REDIST=https://aka.ms/vc14/vc_redist.x64.exe

FROM --platform=$BUILDPLATFORM debian:trixie-slim AS extract
RUN apt-get update \
 && apt-get install -y --no-install-recommends 7zip binutils ca-certificates curl \
 && rm -rf /var/lib/apt/lists/*
ARG REDIST
RUN set -eu; \
    curl -fsSLo /tmp/vc_redist.exe "$REDIST"; \
    mkdir -p /tmp/burn /tmp/cab /vcruntime; \
    7z x -y -t# -o/tmp/burn /tmp/vc_redist.exe >/dev/null; \
    for c in /tmp/burn/*.cab; do 7z x -y -o/tmp/cab "$c" >/dev/null 2>&1 || true; done; \
    for c in /tmp/cab/a*; do 7z e -y -o/vcruntime "$c" '*_amd64' >/dev/null 2>&1 || true; done; \
    for f in /vcruntime/*_amd64; do mv "$f" "${f%_amd64}"; done; \
    strings -el /vcruntime/vcruntime140.dll \
      | grep -m1 -E '^14\.[0-9]+\.[0-9]+\.[0-9]+$' | sed 's/\.0$//' > /version

FROM scratch AS version
COPY --from=extract /version /

FROM scratch AS image
COPY --from=extract /vcruntime /vcruntime/
