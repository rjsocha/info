# syntax=docker/dockerfile:1
ARG TARGETOS
ARG PHP_VERSION=8.5.9
ARG BASE_WINDOWS=mcr.microsoft.com/windows/nanoserver:ltsc2022
ARG VCRUNTIME=wyga/vcruntime:14

FROM --platform=$BUILDPLATFORM debian:trixie-slim AS windist
RUN apt-get update \
 && apt-get install -y --no-install-recommends ca-certificates curl unzip \
 && rm -rf /var/lib/apt/lists/*
ARG PHP_VERSION
RUN curl -fsSLo /tmp/php.zip \
      "https://downloads.php.net/~windows/releases/archives/php-$PHP_VERSION-Win32-vs17-x64.zip" \
 && mkdir -p /Php \
 && unzip -q /tmp/php.zip -d /Php

FROM ${VCRUNTIME} AS vcruntime

FROM php:${PHP_VERSION}-cli AS final-linux
WORKDIR /app
COPY app.php .
ENTRYPOINT ["sh", "-c", "exec php -S 0.0.0.0:${PORT:-8080} app.php"]

FROM ${BASE_WINDOWS} AS final-windows
COPY --from=windist /Php /Php/
COPY --from=vcruntime \
     /vcruntime/vcruntime140.dll \
     /vcruntime/vcruntime140_1.dll \
     /vcruntime/msvcp140.dll \
     /Php/
COPY app.php /App/
ENTRYPOINT C:\Php\php.exe -S 0.0.0.0:%PORT% C:\App\app.php

FROM final-${TARGETOS}
ENV PORT=8080
EXPOSE 8080
