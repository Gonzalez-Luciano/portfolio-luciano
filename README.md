# Portfolio — Luciano González

Portfolio profesional orientado a:

**Backend Developer | PHP & Laravel**

Identidad del repositorio:

- Workspace local: `portfolio-luciano`.
- Repositorio GitHub canónico: `portfolio`.
- Producción: `lucianogonzalez.dev`.

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

La inicialización de `web/`, `api/` e `infra/` pertenece a la Fase 3. El repositorio Git y la documentación base ya existen.

## Flujo Git

`main` es la única rama estable de larga duración. El trabajo usa ramas cortas con prefijos como `feat/`, `fix/`, `docs/`, `refactor/`, `test/`, `chore/` e `infra/`, y llega a `main` mediante pull request después de las validaciones relevantes. No existe una rama permanente `develop`, no se reescribe historial compartido y ningún trabajo se integra en `main` sin aprobación explícita.

Los tags anotados se crean desde commits aprobados de `main` solo para hitos o releases significativos.

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

El roadmap de este repositorio termina su responsabilidad operativa al entregar una versión aprobada, validada y acompañada por su contrato de deployment. El workflow externo `home_server_ops_claude` inspecciona la computadora Linux real y ejecuta el Compose final de producción, Cloudflare Tunnel, backups, seguridad del host y deployment. El portfolio no duplica ese checklist ni presupone características físicas del servidor.

`docs/DEPLOYMENT.md` describe el handoff de aplicación que consume ese workflow externo.

La arquitectura multiproyecto se documenta en:

`docs/SERVER_ARCHITECTURE.md`

El proyecto todavía debe seguir las fases activas de `ROADMAP.md`.
