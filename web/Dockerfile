FROM node:24.18.0-bookworm-slim

ENV PATH="/root/.local/share/pnpm/bin:${PATH}"

RUN corepack enable \
    && corepack prepare pnpm@11.20.0 --activate \
    && pnpm config set store-dir /pnpm/store --global

WORKDIR /app

COPY . .

EXPOSE 3000

CMD ["pnpm", "dev", "--hostname", "0.0.0.0"]
