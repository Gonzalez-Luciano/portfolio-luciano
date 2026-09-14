# SERVER_ARCHITECTURE.md — VPS Linux multiproyecto

## Objetivo

Usar un VPS Linux de OVHcloud, administrado por Luciano, como servidor de aplicaciones compartido entre proyectos Docker independientes, practicando una arquitectura equivalente a la de un servidor cloud real.

El servidor aloja o alojará:

- El portfolio (todavía **no desplegado**).
- Proyectos Laravel.
- Frontends React/Next.js.
- APIs.
- Bases de datos de demo.
- Futuros proyectos Dockerizados.

La entrada pública es **Cloudflare DNS proxied + Caddy global del host**. No se utiliza Cloudflare Tunnel y `cloudflared` no forma parte de la arquitectura vigente.

> Nota histórica: los specs, planes y evidencias de Fases 0–5 describen una computadora Linux doméstica publicada mediante Cloudflare Tunnel y un `cloudflared` compartido. Esa topología fue reemplazada por el VPS descrito en este documento. Los registros de fases cerradas se conservan tal como fueron escritos; este documento es la única fuente de verdad vigente de la topología compartida.

## Propiedad operativa

Este documento registra la topología compartida y el contrato que el portfolio debe respetar. No convierte al repositorio del portfolio en operador del servidor.

- **Repositorio del portfolio:** define la aplicación, su gateway interno, el entrypoint `127.0.0.1:8000`, persistencia, variables, migraciones, bootstrap y health checks, y — en su fase final (Fase 11 de `ROADMAP.md`) — produce el runtime productivo (Dockerfiles productivos y `compose.production.yaml`), CI, workflow de release, tag, imágenes en GHCR con digests registrados y el handoff de deployment. **Termina ahí y nunca entra al VPS.**
- **Flujo operativo `vps_ops_claude`:** opera el VPS real desde un contexto separado: host, SSH, UFW, Caddy global (`/srv/ingress`), Cloudflare y DNS, `/srv/apps`, `/srv/ops`, `/srv/backups`, secretos productivos reales, overrides por proyecto, deployment, migraciones de producción, backups, restores, reboot recovery y registro multiproyecto.
- Las características de hardware, distribución y layout interno del host pertenecen a operaciones; nunca se infieren ni se inventan desde este repositorio.

---

## Modelo general

```text
PC de desarrollo
      |
      | git push
      v
    GitHub
      |  CI: lint / format / typecheck / tests / builds / validación Docker
      |  tag vX.Y.Z -> workflow de release
      v
    GHCR  (imágenes identificables por release y commit; digests registrados)
      :
      :  pull manual/asistido de tags inmutables o digests exactos,
      :  ejecutado por vps_ops_claude — fuera de cualquier repositorio de proyecto
      v

Internet
      |
      v
Cloudflare  (DNS proxied, SSL/TLS "Full (strict)")
      |
      |  HTTPS :443 — UFW solo admite rangos IP oficiales de Cloudflare
      v
+----------------------------------------------------------------+
|                    VPS Linux OVHcloud                          |
|                                                                |
|  Caddy GLOBAL del host  :80 / :443      (config: /srv/ingress) |
|       |  TLS de origen + routing por hostname                   |
|       |                                                        |
|       +------> 127.0.0.1:8000     -> stack Docker Portfolio    |
|       |                                                        |
|       +------> 127.0.0.1:<puerto> -> stack Docker Proyecto N   |
|                                                                |
+----------------------------------------------------------------+
```

GitHub Actions nunca se conecta al VPS. La línea punteada entre GHCR y el VPS representa una acción operativa posterior, no un paso de CI.

---

## Estructura del servidor

```text
/srv/apps/       aplicaciones desplegadas (un directorio por proyecto)
/srv/ops/        configuración/overrides operacionales por proyecto + vps_ops_claude
/srv/ingress/    configuración del Caddy global del host
/srv/backups/    backups gestionados por operaciones
```

Convención esperada para el portfolio, **creada exclusivamente por `vps_ops_claude` durante el deployment posterior** (hoy no existe y ningún agente del repositorio la crea):

```text
/srv/apps/portfolio/                   checkout exacto del tag de release
/srv/ops/portfolio/compose.vps.yaml    override específico del VPS
/srv/backups/portfolio/                backups del portfolio
```

Los proyectos no viven dentro del repositorio del portfolio: cada aplicación tiene su propio repositorio, directorio, Compose y ciclo de release.

El layout interno de `/srv/ops`, `/srv/ingress` y `/srv/backups`, la estructura de `vps_ops_claude` y la ubicación física de los datos persistentes pertenecen a operaciones. Este documento solo nombra los puntos de contacto que un proyecto necesita conocer.

---

## Regla fundamental de aislamiento

Cada proyecto es un stack independiente.

No compartir por defecto entre proyectos:

- Contenedor MySQL.
- Base de datos.
- Red Docker.
- `.env`.
- Volúmenes.
- Credenciales.
- Dependencias.
- Cron/scheduler.
- Cola.
- Redis.

Compartir únicamente infraestructura deliberadamente global:

- Docker Engine.
- Caddy global del host.
- UFW.
- Sistema operativo.
- Sistema de backups.
- Monitorización del host.

Esto reduce el impacto de errores entre proyectos.

---

## Ingress: dos capas de Caddy

Existen dos capas de Caddy con responsabilidades distintas. No deben confundirse ni fusionarse.

| | Caddy GLOBAL del VPS | Caddy INTERNO del portfolio (gateway) |
|---|---|---|
| Pertenece a | Operaciones (`vps_ops_claude`) | Repositorio del portfolio |
| Configuración | `/srv/ingress` | `infra/caddy/` del repositorio |
| Escucha | `:80` / `:443` públicos del host | `80` dentro de Docker; publicado solo en `127.0.0.1:8000` |
| Responsabilidad | TLS de origen, routing por hostname, multiproyecto | Entrypoint único del stack; enruta a Next.js, Laravel, media y rutas de backend |
| Conoce | Hostnames y puertos loopback de cada proyecto | Servicios y redes Docker internas del portfolio |

El Caddy global no conoce los contenedores internos del portfolio. El gateway interno no conoce Cloudflare, otros proyectos ni la configuración del host. **El gateway interno del portfolio se conserva:** no es un duplicado del Caddy global, sino la frontera HTTP propia del stack.

---

## Cloudflare

- Los hostnames públicos usan registros DNS **proxied**.
- SSL/TLS en modo **Full (strict)**: Cloudflare se conecta al origen por HTTPS y valida el certificado presentado por el Caddy global.
- Cloudflare es obligatoriamente la **única** entrada HTTP/HTTPS pública.
- DNS, SSL/TLS, reglas y cualquier control de acceso de Cloudflare pertenecen a operaciones.
- Ningún repositorio de proyecto contiene credenciales, API tokens ni configuración de Cloudflare.
- Cloudflare Access puede agregarse sobre `/admin` como defensa en profundidad por decisión de operaciones; nunca reemplaza la autenticación de Laravel.

---

## Firewall y puertos públicos del host

UFW está activo con política `default deny incoming`.

| Puerto | Escucha | Admitido desde |
|---|---|---|
| `80` / `443` | Caddy global | Únicamente rangos IP oficiales de Cloudflare |
| `22` | SSH | Público; autenticación exclusivamente por clave |
| Cualquier otro | — | Denegado |

- El bypass directo al origen (conectarse a la IP del VPS sin pasar por Cloudflare) fue probado y está bloqueado.
- Mantener sincronizados los rangos de Cloudflare en UFW es responsabilidad de operaciones.
- Nunca publicar MySQL, Redis ni puertos de contenedores internos.
- Los puertos de proyecto se publican exclusivamente sobre `127.0.0.1`. Docker publica puertos mediante reglas `iptables` propias que no pasan por UFW; por eso el binding a loopback es obligatorio y no un detalle opcional.

---

## Registro de puertos del host

Cada proyecto recibe un único puerto loopback de entrada.

| Puerto host | Proyecto | Hostname | Estado |
|---:|---|---|---|
| `127.0.0.1:8000` | Portfolio | `lucianogonzalez.dev` | Reservado; **no desplegado** |

El registro autoritativo del resto de proyectos lo mantiene `vps_ops_claude`. Este documento no replica puertos de otros proyectos ni reserva puertos para proyectos inexistentes.

Antes de cada deployment, el registro operativo debe guardar: identificador del proyecto, hostname público, puerto loopback, ruta en `/srv/apps`, override en `/srv/ops` y site correspondiente del Caddy global.

El puerto `127.0.0.1:8000` es un **contrato entre aplicación y operaciones**. Cambiarlo exige actualizar este registro y la documentación del portfolio en el mismo cambio, y coordinarlo con operaciones.

### Regla

Los servicios de entrada de cada proyecto deben enlazarse a:

```text
127.0.0.1:PUERTO
```

y no, salvo necesidad documentada, a:

```text
0.0.0.0:PUERTO
```

Así el Caddy global alcanza el proyecto desde el host sin que el servicio escuche en interfaces públicas.

---

## Arquitectura interna de cada proyecto

Cloudflare y el Caddy global no necesitan conocer los contenedores internos.

Cada proyecto presenta un solo endpoint HTTP al host.

Ejemplo Laravel + React:

```text
Caddy global :443
      |
      v
127.0.0.1:<puerto>
      |
      v
gateway del proyecto
   |             |
   v             v
React          Laravel
                  |
                  v
                MySQL
```

El gateway del portfolio es Caddy (capa interna). Hace reverse proxy HTTP hacia Next.js y hacia el contenedor interno Apache + Laravel, sin montar ni interpretar el source tree de Laravel. Solo el gateway publica `127.0.0.1:8000`.

Ejemplo de rutas internas:

```text
/          -> frontend
/api/*     -> Laravel
/admin/*   -> Laravel/Filament, si existe
```

La base de datos queda únicamente en la red Docker privada.

---

## Portfolio

El portfolio tiene reservado:

```text
127.0.0.1:8000
```

y se publicará como:

```text
https://lucianogonzalez.dev
```

Topología objetivo:

```text
Internet
        |
        v
Cloudflare (DNS proxied, Full (strict))
        |
        v
Caddy GLOBAL del VPS :443
        |
        v
127.0.0.1:8000
        |
        v
Caddy/gateway INTERNO del portfolio
    |          |
    v          v
 Next.js     Laravel
                |
                v
              MySQL
```

MySQL y cualquier otro servicio interno permanecen en redes Docker privadas, sin puertos publicados al host ni acceso desde Internet.

---

## Proyectos adicionales

Cada nuevo proyecto obtiene:

1. Repositorio independiente.
2. Directorio independiente en `/srv/apps`.
3. Compose productivo propio y override operativo en `/srv/ops`.
4. Puerto loopback reservado en el registro.
5. Subdominio.
6. Registro DNS proxied en Cloudflare.
7. Site propio en el Caddy global (`/srv/ingress`).
8. Backups independientes si contiene datos persistentes.

Ejemplo:

```text
<slug>.lucianogonzalez.dev
        |
        v
Cloudflare (proxied)
        |
        v
Caddy global :443
        |
        v
127.0.0.1:<puerto>
        |
        v
stack Docker del proyecto
```

Agregar o quitar un proyecto no modifica la arquitectura interna de los demás.

---

## DNS

El dominio principal se administra desde Cloudflare.

Convención definitiva:

```text
lucianogonzalez.dev             portfolio
<slug>.lucianogonzalez.dev      proyecto independiente desplegado
```

El slug debe ser minúsculo, conciso, descriptivo y DNS-safe, con guiones solo cuando sean necesarios. No se reservan slugs concretos para proyectos que todavía no existen.

Frontend, API y administración comparten un solo hostname mediante el gateway del proyecto. Un hostname separado para API o administración es una excepción y requiere documentar por qué el mismo origen no resulta adecuado.

Esto simplifica:

- CORS.
- Cookies.
- Autenticación.
- Configuración del Caddy global.
- Demostración al usuario.

---

## Docker por proyecto

Cada proyecto mantiene separados su entorno de desarrollo y su runtime productivo. Para el portfolio:

- `compose.yaml` — desarrollo y pruebas (existente).
- `compose.production.yaml` — runtime productivo portable, entregado por el repositorio en su fase de release (todavía no existe).
- `/srv/ops/portfolio/compose.vps.yaml` — override del VPS, propiedad de operaciones; nunca vive en el repositorio.

Ejemplo conceptual de un stack:

```text
project-gateway
project-web
project-api
project-mysql
```

Solo `project-gateway` publica un puerto al host, sobre loopback.

Los demás usan redes Docker privadas. Los nombres reales de los servicios se definen en cada proyecto.

El Compose productivo del proyecto debe ser portable: no contiene rutas `/srv`, secretos reales, hostnames del host ni configuración del Caddy global. Lo específico del VPS se aplica mediante el override de `/srv/ops`.

### Volúmenes

Los volúmenes persistentes deben tener nombres claramente asociados al proyecto.

Ejemplo conceptual:

```text
portfolio_mysql_data
portfolio_media

otro-proyecto_mysql_data
otro-proyecto_storage
```

Nunca usar el mismo volumen de base de datos para dos proyectos independientes.

---

## Scheduler / cron

Los procesos programados pertenecen al proyecto que los necesita.

Para Laravel, el scheduler puede ejecutarse:

- mediante un contenedor dedicado;
- mediante cron del host apuntando a un contenedor;
- mediante otra estrategia documentada por proyecto.

No crear un cron global que haga `migrate:fresh` sobre todas las aplicaciones.

### Reset de demos

Un proyecto de demostración puede resetear datos periódicamente únicamente si:

- Su base de datos es descartable.
- No contiene usuarios/datos que deban conservarse.
- El comportamiento está documentado.
- El reset afecta exclusivamente a esa demo.

El portfolio y su CMS **nunca** deben usar `migrate:fresh --seed` programado en producción porque destruiría contenido administrado.

---

## CI y release

GitHub Actions se utiliza para:

- Lint y formato.
- Type checking.
- Tests.
- Builds de aplicación.
- Validación de Dockerfiles productivos y Compose productivo.
- Auditorías de dependencias y detección de secretos accidentales.
- Workflow de release: a partir de un tag versionado sobre `main`, publicar imágenes en GHCR identificables por versión y commit.

Reglas:

- CI y release **nunca** acceden al VPS.
- CI no contiene secretos del host, claves SSH ni credenciales de Cloudflare.
- No existen runners con acceso al VPS.
- Un build o una release exitosa en GitHub no significa que algo haya sido desplegado.
- Producción nunca depende exclusivamente de `latest`: el deployment usa tags inmutables o, preferentemente, digests exactos.

---

## Despliegue

El deployment es propiedad de `vps_ops_claude` y se ejecuta desde un contexto operativo separado, manual/asistido. Un proyecto llega a ese flujo como **release ya creada** (tag, imágenes GHCR con digests y handoff); ningún agente de repositorio de proyecto entra al VPS.

Flujo conceptual:

```text
release ya creada (tag + digests + handoff)
  -> operador entra al VPS
  -> preflight
  -> backup si corresponde
  -> checkout exacto del tag de release en /srv/apps/<proyecto>
  -> compose productivo del proyecto + override /srv/ops/<proyecto>/compose.vps.yaml
  -> pull de imágenes/digests exactos
  -> migraciones
  -> arranque
  -> health
  -> smoke
  -> verificación pública
  -> cierre del deployment
```

No ejecutar `docker compose down` por costumbre si `docker compose up -d` permite actualizar sin una interrupción innecesaria.

### Automatización

No existe deployment automático. GitHub Actions no despliega, no hace SSH al VPS y no hay runners self-hosted con acceso al servidor. Cualquier automatización futura del deployment sería una decisión explícita de operaciones, fuera de los repositorios de proyecto.

---

## Backups

`/srv/backups` pertenece a operaciones. Cada aplicación con datos persistentes tiene backup independiente.

Estructura sugerida:

```text
/srv/backups/
├── portfolio/
│   ├── mysql/
│   └── media/
└── <proyecto>/
    └── ...
```

Requisitos:

- Retención definida.
- Copia fuera del disco del VPS.
- Restore probado.
- No considerar una copia en el mismo disco como única estrategia de backup.

Los repositorios de proyecto identifican qué datos requieren backup; no ejecutan ni programan backups reales.

---

## Reinicios del servidor

Después de reiniciar el VPS deben recuperarse automáticamente:

1. Docker.
2. Reglas de UFW.
3. Caddy global.
4. Stacks Docker con restart policy adecuada.
5. Bases de datos.
6. APIs.
7. Frontends.
8. Gateways de proyecto.

Operaciones prueba este escenario antes de considerar terminada la infraestructura de cada proyecto.

---

## Seguridad mínima del host

- Usuario administrativo no utilizado como usuario de aplicación.
- SSH en `:22` con autenticación exclusiva por clave: `PermitRootLogin no`, `PasswordAuthentication no`, `PubkeyAuthentication yes`.
- UFW con `default deny incoming`; `80`/`443` solo desde rangos de Cloudflare.
- Contraseñas/keys fuera de Git.
- Actualizaciones del sistema operativo.
- MySQL solo en redes privadas.
- Backups.
- Logs.
- Principio de mínimo privilegio.
- No ejecutar contenedores privilegiados sin necesidad.
- No montar el socket de Docker dentro de contenedores de aplicación sin una justificación fuerte.

---

## Objetivo educativo

Esta infraestructura forma parte del portfolio técnico.

Debe permitir demostrar experiencia práctica con:

- Linux en VPS.
- Docker.
- Networking.
- DNS.
- Cloudflare como proxy con SSL/TLS Full (strict).
- Reverse proxy en dos capas (Caddy global + gateway por proyecto).
- Firewall (UFW) restringido a Cloudflare.
- CI, releases versionadas y GHCR.
- HTTPS.
- Gestión de secretos.
- Persistencia.
- Backups.
- Despliegues controlados.
- Observabilidad básica.

La infraestructura no debe ocultarse como un detalle accidental: puede documentarse como un proyecto técnico real siempre que la seguridad no se vea comprometida (sin IPs de origen, usuarios, rutas sensibles ni configuración que facilite eludir Cloudflare).
