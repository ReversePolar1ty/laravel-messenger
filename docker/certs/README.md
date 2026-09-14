# TLS certificates (local)

Self-signed cert for `localhost` used by nginx (HTTPS/WSS) and optionally Vite HMR.

Regenerate:

```bash
# Linux / macOS / Git Bash
bash docker/certs/generate.sh

# or via Docker
docker run --rm -v "$PWD/docker/certs:/certs" alpine/openssl req -x509 -nodes \
  -newkey rsa:2048 -keyout /certs/localhost.key -out /certs/localhost.crt -days 825 \
  -subj "/C=RU/ST=Local/L=Local/O=LaravelMessenger/CN=localhost" \
  -addext "subjectAltName=DNS:localhost,IP:127.0.0.1"
```

Browser will warn about the untrusted certificate — accept it once for `https://localhost:8443` (and `https://localhost:5173` if using Vite).
