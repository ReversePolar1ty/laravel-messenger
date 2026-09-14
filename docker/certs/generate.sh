#!/usr/bin/env bash
# Генерация self-signed сертификата для локального HTTPS/WSS.
set -euo pipefail

DIR="$(cd "$(dirname "$0")" && pwd)"
KEY="$DIR/localhost.key"
CRT="$DIR/localhost.crt"

openssl req -x509 -nodes -newkey rsa:2048 \
  -keyout "$KEY" \
  -out "$CRT" \
  -days 825 \
  -subj "/C=RU/ST=Local/L=Local/O=LaravelMessenger/CN=localhost" \
  -addext "subjectAltName=DNS:localhost,IP:127.0.0.1"

echo "Created:"
echo "  $CRT"
echo "  $KEY"
