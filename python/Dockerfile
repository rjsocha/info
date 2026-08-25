# syntax=docker/dockerfile:1
ARG TARGETOS
ARG BASE_WINDOWS=mcr.microsoft.com/windows/nanoserver:ltsc2022
ARG PYTHON_VERSION=3.14.7

FROM --platform=$BUILDPLATFORM debian:trixie-slim AS embed
RUN apt-get update \
 && apt-get install -y --no-install-recommends ca-certificates curl unzip \
 && rm -rf /var/lib/apt/lists/*
ARG PYTHON_VERSION
RUN curl -fsSLo /tmp/python.zip \
      "https://www.python.org/ftp/python/$PYTHON_VERSION/python-$PYTHON_VERSION-embed-amd64.zip" \
 && mkdir -p /Python \
 && unzip -q /tmp/python.zip -d /Python

FROM python:${PYTHON_VERSION}-slim AS final-linux
WORKDIR /app
COPY app.py .
ENTRYPOINT ["python", "app.py"]

FROM ${BASE_WINDOWS} AS final-windows
COPY --from=embed /Python /Python/
COPY app.py /App/
ENTRYPOINT ["C:\\Python\\python.exe", "C:\\App\\app.py"]

FROM final-${TARGETOS}
ENV PORT=8080
EXPOSE 8080
