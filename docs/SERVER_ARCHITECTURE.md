# SERVER_ARCHITECTURE.md — Servidor Linux multiproyecto

## Objetivo

Usar una computadora Linux propia como un pequeño servidor de aplicaciones para aprender y practicar una arquitectura similar a la de un servidor cloud.

El servidor alojará:

- El portfolio.
- Proyectos Laravel.
- Frontends React/Next.js.
- APIs.
- Bases de datos de demo.
- Futuros proyectos Dockerizados.

Cloudflare Tunnel será la capa de acceso público.

## Propiedad operativa

Este documento registra la topología compartida y el contrato que el portfolio debe respetar; no convierte al roadmap del portfolio en propietario de las operaciones físicas del servidor.

- El repositorio del portfolio define su stack, su gateway, su entrypoint futuro `127.0.0.1:8000`, persistencia, variables, migraciones, bootstrap y health checks.
- El workflow externo `home_server_ops_claude` inspecciona el servidor real y ejecuta el preflight, la preparación del host, el Compose final de producción, el deployment, `cloudflared`, firewall, backups, restores, reinicios y coordinación multiproyecto.
- Las características reales de Linux, hardware, almacenamiento y layout se descubren durante ese workflow externo; nunca se inventan desde este repositorio.
- La documentación del portfolio entrega el contrato necesario y referencia el workflow externo sin copiar su checklist completo.

---

## Modelo general

```text
PC de desarrollo
      |
      | git push
      v
    GitHub
      |
      | CI: lint / tests / build validation
      v

Internet
      |
      v
Cloudflare DNS / Edge
      |
      | Cloudflare Tunnel
      v
+-------------------------------------------------------+
|              PC Linux de producción                  |
|                                                       |
|  cloudflared (servicio compartido del host)           |
|       |                                               |
|       +------> 127.0.0.1:8000 -> Portfolio           |
|       |                                               |
|       +------> 127.0.0.1:8080 -> Proyecto A          |
|       |                                               |
|       +------> 127.0.0.1:8081 -> Proyecto B          |
|       |                                               |
|       +------> 127.0.0.1:8082 -> Proyecto C          |
|                                                       |
+-------------------------------------------------------+
```

Un mismo Cloudflare Tunnel puede publicar múltiples aplicaciones, cada una asociando un hostname público con un servicio local distinto.

---

## Estructura lógica del servidor

Los proyectos no deben vivir todos dentro del repositorio del portfolio.

Cada aplicación es independiente.

Estructura definida:

```text
/srv/apps/
├── portfolio/
│   ├── .git/
│   ├── compose.yaml
│   ├── web/
│   ├── api/
│   ├── infra/
│   └── ...
│
├── reserva-hub/
│   ├── .git/
│   ├── compose.yaml
│   ├── frontend/
│   ├── backend/
│   └── ...
│
├── proyecto-tickets/
│   ├── .git/
│   ├── compose.yaml
│   └── ...
│
└── futuro-proyecto/
    └── ...
```

Infraestructura compartida del host:

```text
/etc/cloudflared/            # configuración/credenciales si corresponde
/srv/backups/
├── portfolio/
├── reserva-hub/
└── ...
/var/log/                    # logs del sistema/servicios
```

`/srv/apps` es la raíz definitiva de aplicaciones desplegadas y `/srv/backups` es la raíz definitiva de backups. El portfolio usa `/srv/apps/portfolio` y `/srv/backups/portfolio`.

La distribución y versión de Linux, CPU, RAM, discos, capacidades, layout físico y ubicación final de los datos persistentes se relevan al iniciar el preflight de deployment. No se infieren durante la fase de diseño local.

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

Compartir únicamente infraestructura deliberadamente global, por ejemplo:

- Docker Engine.
- `cloudflared`.
- Sistema operativo.
- Sistema de backups.
- Monitorización del host.

Esto reduce el impacto de errores entre demos.

---

## Registro de puertos del host

Cada proyecto recibe un único puerto de entrada local.

Ejemplo inicial:

| Puerto host | Proyecto | Dominio |
|---:|---|---|
| `127.0.0.1:8000` | Portfolio | `lucianogonzalez.dev` |
| Sin reservar | Proyecto futuro | Sin reservar |

No reservar nombres ni puertos para proyectos inexistentes.

El registro central debe guardar, antes de cada deployment, el identificador del proyecto, hostname público, puerto loopback, ruta de deployment y mapeo de Cloudflare Tunnel.

### Regla

Los servicios públicos de cada proyecto deben enlazarse a:

```text
127.0.0.1:PUERTO
```

y no, salvo necesidad documentada, a:

```text
0.0.0.0:PUERTO
```

El objetivo es que el servicio sea alcanzable desde el host para `cloudflared`, pero no quede escuchando directamente en todas las interfaces de red.

---

## Arquitectura interna de cada proyecto

Cloudflare no necesita conocer todos los contenedores internos.

Cada proyecto presenta un solo endpoint HTTP al host.

Ejemplo Laravel + React:

```text
Cloudflare Tunnel
      |
      v
127.0.0.1:8080
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

El gateway del portfolio es Caddy. Hace reverse proxy HTTP hacia Next.js y hacia el contenedor interno Apache + Laravel, sin montar ni interpretar el source tree de Laravel. Solo Caddy publica `127.0.0.1:8000`; el servidor externo debe preservar este contrato al preparar el Compose final.

Ejemplo de rutas internas:

```text
/          -> frontend
/api/*     -> Laravel
/admin/*   -> Laravel/Filament, si existe
```

La base de datos queda únicamente en la red Docker privada.

---

## Portfolio

El portfolio ocupa inicialmente:

```text
127.0.0.1:8000
```

y se publica como:

```text
https://lucianogonzalez.dev
```

Topología conceptual:

```text
lucianogonzalez.dev
        |
        v
Cloudflare
        |
        v
cloudflared del host
        |
        v
127.0.0.1:8000
        |
        v
portfolio-gateway
    |          |
    v          v
 Next.js     Laravel
                |
                v
              MySQL
```

---

## Proyectos adicionales

Cada nuevo proyecto obtiene:

1. Directorio independiente.
2. Repositorio independiente.
3. Compose independiente.
4. Puerto local reservado.
5. Subdominio.
6. Entrada propia en Cloudflare Tunnel.
7. Backups independientes si contiene datos persistentes.

Ejemplo:

```text
reservas.lucianogonzalez.dev
        |
        v
Cloudflare Tunnel
        |
        v
127.0.0.1:8080
        |
        v
stack Docker de Reserva Hub
```

Esto permite agregar y quitar proyectos sin modificar la arquitectura interna de los demás.

---

## Cloudflare Tunnel

### Estrategia

Usar un tunnel compartido a nivel del host para las aplicaciones públicas de este servidor.

`cloudflared` se ejecuta como servicio Linux administrado por `systemd`.

No debe formar parte del `compose.yaml` de cada proyecto.

Ventajas:

- Un solo conector para el servidor.
- Configuración de hostnames centralizada.
- Cada aplicación sigue aislada.
- Reiniciar un proyecto no reinicia el tunnel.
- Agregar una demo consiste en asignar puerto + hostname.

### Ejemplo conceptual de rutas publicadas

```text
lucianogonzalez.dev
    -> http://localhost:8000

reservas.lucianogonzalez.dev
    -> http://localhost:8080

proyecto2.lucianogonzalez.dev
    -> http://localhost:8081
```

Las rutas pueden administrarse mediante el dashboard/API de Cloudflare para un tunnel administrado remotamente.

El token del tunnel es un secreto del servidor.

Nunca debe estar dentro de un repositorio.

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
- Configuración del tunnel.
- Demostración al usuario.

---

## Puertos públicos del router

Para servir las aplicaciones mediante Cloudflare Tunnel:

- No abrir `80`.
- No abrir `443`.
- No publicar MySQL.
- No publicar Redis.
- No publicar puertos Docker innecesarios.

La conexión del tunnel parte de forma saliente desde el servidor.

La administración remota del servidor debe definirse por separado y no debe lograrse simplemente publicando SSH al Internet.

---

## Docker por proyecto

Cada proyecto tiene su propio `compose.yaml`.

Ejemplo conceptual de un stack:

```text
project-gateway
project-web
project-api
project-mysql
```

Solo `project-gateway` publica un puerto al host.

Los demás usan redes Docker privadas.

Los nombres reales de los servicios se definen en cada proyecto.

### Volúmenes

Los volúmenes persistentes deben tener nombres claramente asociados al proyecto.

Ejemplo conceptual:

```text
portfolio_mysql_data
portfolio_media

reserva_mysql_data
reserva_storage
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

## CI

GitHub Actions se utiliza inicialmente para:

- Lint.
- Tests.
- Type checking.
- Build validation.
- Docker build validation.

CI y despliegue son responsabilidades separadas.

Un build exitoso en GitHub no significa que los archivos compilados hayan sido desplegados al servidor.

---

## Despliegue

La ejecución de esta sección pertenece al workflow externo `home_server_ops_claude`. El repositorio del portfolio debe llegar a ese workflow como candidato aprobado, verificable y acompañado por su contrato de deployment; no instala ni opera el host desde su roadmap de desarrollo.

### Primera etapa

Mantener el despliegue deliberadamente simple:

1. CI valida el commit.
2. Conectarse de forma segura al servidor.
3. Entrar al directorio del proyecto.
4. Actualizar el repositorio a una versión aprobada.
5. Ejecutar el procedimiento Docker del proyecto.
6. Ejecutar migraciones si corresponde.
7. Ejecutar smoke tests.
8. Confirmar que el hostname público responde.

No ejecutar `docker compose down` por costumbre si `docker compose up -d` permite actualizar sin una interrupción innecesaria.

### Automatización futura

La automatización de producción se decidirá en una fase posterior.

Opciones aceptables:

- Runner self-hosted asociado únicamente a infraestructura/repositorios privados de despliegue.
- Administración remota protegida mediante Cloudflare Access/Tunnel.
- Otra estrategia que no requiera exponer SSH públicamente.

No configurar un runner self-hosted sin restricciones para workflows de repositorios públicos.

---

## Backups

Cada aplicación con datos persistentes debe tener backup independiente.

Estructura sugerida:

```text
/srv/backups/
├── portfolio/
│   ├── mysql/
│   └── media/
├── reserva-hub/
│   └── mysql/
└── ...
```

Requisitos:

- Retención definida.
- Copia en un disco o equipo diferente del disco principal.
- Restore probado.
- No considerar una copia en el mismo disco como única estrategia de backup.

---

## Reinicios del servidor

Después de reiniciar Linux deben recuperarse automáticamente:

1. Docker.
2. `cloudflared`.
3. Stacks Docker marcados para inicio automático.
4. Bases de datos.
5. APIs.
6. Frontends.
7. Gateways.

Probar este escenario antes de considerar la infraestructura terminada.

---

## Seguridad mínima del host

- Usuario administrativo no utilizado como usuario de aplicación.
- SSH con claves si se utiliza SSH.
- Contraseñas/keys fuera de Git.
- Firewall del host.
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

- Linux.
- Docker.
- Networking.
- DNS.
- Reverse proxy.
- Cloudflare Tunnel.
- CI/CD.
- HTTPS.
- Gestión de secretos.
- Persistencia.
- Backups.
- Despliegues.
- Observabilidad básica.

La infraestructura no debe ocultarse como un detalle accidental: puede documentarse como un proyecto técnico real siempre que la seguridad no se vea comprometida.
