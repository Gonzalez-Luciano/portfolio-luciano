# Production image for the public Vite + React single-page app.
#
# Deliberately separate from web/Dockerfile, which is the development image
# running the Vite dev server against a bind-mounted source tree. Production is
# a build stage followed by a static runtime: no Node process, no HMR, no dev
# dependencies and no application source in the shipped layer.
#
# Build context is the repository root.
# Requires BuildKit/buildx: the sibling Dockerfile.dockerignore replaces the
# root .dockerignore, which excludes web/ and api/ for the development image.

FROM node:24.18.0-bookworm-slim AS build

ENV PNPM_HOME="/pnpm"
ENV PATH="${PNPM_HOME}:${PATH}"

RUN corepack enable && corepack prepare pnpm@11.20.0 --activate

WORKDIR /app

COPY web/package.json web/pnpm-lock.yaml web/pnpm-workspace.yaml ./
RUN pnpm install --frozen-lockfile

COPY web/ ./

# `pnpm build` is `tsc --noEmit && vite build`, so the image cannot be produced
# from source that fails type checking. verify:build then asserts the emitted
# SEO, sitemap, robots and favicon contract against the real output.
RUN pnpm build && pnpm verify:build

FROM caddy:2.11.4-alpine AS runtime

COPY infra/docker/web/Caddyfile /etc/caddy/Caddyfile
COPY infra/docker/web/entrypoint.sh /usr/local/bin/portfolio-web-entrypoint
RUN chmod +x /usr/local/bin/portfolio-web-entrypoint

COPY --from=build /app/dist /srv/www

EXPOSE 8080
ENTRYPOINT ["portfolio-web-entrypoint"]
CMD ["caddy", "run", "--config", "/etc/caddy/Caddyfile", "--adapter", "caddyfile"]
