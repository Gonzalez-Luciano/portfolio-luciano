FROM node:24.18.0-bookworm-slim

ENV PATH="/root/.local/share/pnpm/bin:${PATH}"

RUN corepack enable \
    && corepack prepare pnpm@11.20.0 --activate \
    && pnpm config set store-dir /pnpm/store --global

WORKDIR /app

COPY . .

EXPOSE 5173

CMD ["pnpm", "dev"]
