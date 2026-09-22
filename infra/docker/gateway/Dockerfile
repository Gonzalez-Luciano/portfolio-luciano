# Production image for the portfolio's internal Caddy gateway.
#
# The development gateway mounts infra/caddy/Caddyfile read-only from the host.
# Production bakes the configuration into the image so the routing contract is
# immutable and travels with the release digest.
#
# This is NOT the host-level global Caddy on the VPS: that one terminates TLS
# for lucianogonzalez.dev and belongs to operations. This gateway is the single
# entrypoint of the portfolio stack and listens on a loopback-bound host port.
#
# Build context is the repository root.
# Requires BuildKit/buildx: the sibling Dockerfile.dockerignore replaces the
# root .dockerignore, which excludes web/ and api/ for the development image.

FROM caddy:2.11.4-alpine

COPY infra/caddy/Caddyfile.production /etc/caddy/Caddyfile

RUN caddy validate --config /etc/caddy/Caddyfile --adapter caddyfile

EXPOSE 80
CMD ["caddy", "run", "--config", "/etc/caddy/Caddyfile", "--adapter", "caddyfile"]
