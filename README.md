# Portfolio — Luciano González

Portfolio profesional orientado a:

**Backend Developer | PHP & Laravel**

## Antes de trabajar en el proyecto

Leer:

1. `AGENTS.md`
2. `docs/PROJECT.md`
3. `docs/ARCHITECTURE.md`
4. `docs/SERVER_ARCHITECTURE.md`
5. `docs/DEPLOYMENT.md`
6. `ROADMAP.md`

## Estructura del repositorio

- `web/` — portfolio público React/Next.js.
- `api/` — Laravel API + administración.
- `infra/` — Docker e infraestructura específica del portfolio.
- `docs/` — documentación del producto, arquitectura y servidor.

## Contexto de producción

El portfolio se ejecutará en una computadora Linux propia junto con otros proyectos Dockerizados.

```text
Internet
   ↓
Cloudflare
   ↓
Cloudflare Tunnel compartido del servidor
   ↓
127.0.0.1:8000
   ↓
Portfolio Docker
   ├── Next.js
   ├── Laravel / Filament
   └── MySQL
```

Otros proyectos se publicarán usando subdominios y puertos locales diferentes.

La arquitectura multiproyecto se documenta en:

`docs/SERVER_ARCHITECTURE.md`

El proyecto todavía debe seguir las fases activas de `ROADMAP.md`.
