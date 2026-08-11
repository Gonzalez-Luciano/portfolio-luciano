# ARCHITECTURE.md — Portfolio

## Estado

Baseline de arquitectura del proyecto Portfolio.

Este documento describe la arquitectura interna del portfolio.

La arquitectura compartida de la computadora/servidor se encuentra en:

`docs/SERVER_ARCHITECTURE.md`

Las reglas de despliegue específicas del portfolio se encuentran en:

`docs/DEPLOYMENT.md`

---

## Contexto

El portfolio no es el único proyecto del servidor.

La computadora Linux de producción aloja múltiples aplicaciones Docker independientes.

A nivel del host:

```text
Cloudflare Tunnel
  ├── lucianogonzalez.dev -> 127.0.0.1:8000 -> Portfolio
  ├── subdominio A        -> 127.0.0.1:8080 -> Proyecto A
  └── subdominio B        -> 127.0.0.1:8081 -> Proyecto B
```

Por lo tanto, el repositorio del portfolio no debe intentar administrar los demás proyectos ni ser dueño de `cloudflared`.

---

## Estructura del repositorio del portfolio

```text
portfolio/
├── AGENTS.md
├── ROADMAP.md
├── README.md
├── docs/
│   ├── PROJECT.md
│   ├── ARCHITECTURE.md
│   ├── SERVER_ARCHITECTURE.md
│   └── DEPLOYMENT.md
├── web/
├── api/
└── infra/
```

### `web/`

Aplicación pública.

Responsabilidades:

- Renderizado.
- UI responsive.
- Español/inglés.
- Tema claro/oscuro.
- SEO.
- Animaciones.
- Consumo de API.
- Estados de error/carga.

### `api/`

Laravel.

Responsabilidades:

- Autenticación administrativa.
- Filament.
- Persistencia MySQL.
- Validación.
- Borrador/publicado.
- Medios.
- API pública.
- Autorización.
- Integridad de datos.

### `infra/`

Infraestructura propia del portfolio.

Responsabilidades:

- Dockerfiles.
- Compose del proyecto.
- Gateway/reverse proxy del proyecto.
- Configuración de servicios internos.
- Scripts de deploy/backup específicos del portfolio.

No debe incluir la configuración global de otros proyectos del servidor.

### `docs/`

Contexto permanente para humanos y agentes.

---

## Frontera con el servidor

El portfolio debe exponer exactamente un punto de entrada HTTP al host:

```text
127.0.0.1:8000
```

El puerto podrá parametrizarse, pero `8000` es la reserva inicial.

`cloudflared` del host publica:

```text
https://lucianogonzalez.dev
    -> http://localhost:8000
```

El stack del portfolio no necesita un contenedor `cloudflared`.

---

## Topología interna del portfolio

```text
127.0.0.1:8000
       |
       v
portfolio-gateway
    |             |
    v             v
portfolio-web   portfolio-api
 Next.js         Laravel
                    |
                    v
              portfolio-mysql
```

Servicios adicionales se agregan solo con una necesidad real.

---

## Gateway

El gateway es el único contenedor del proyecto que publica un puerto al host.

Rutas conceptuales:

```text
/          -> web
/api/*     -> api
/admin/*   -> api / Filament
```

Beneficios:

- Un solo hostname.
- Un solo puerto del host.
- Menos CORS.
- Cookies más simples.
- `cloudflared` no necesita conocer la red Docker interna.
- Los contenedores web/api/mysql no se exponen directamente.

La implementación exacta del gateway se decidirá al crear la infraestructura.

---

## Frontend

Dirección:

- React.
- Next.js App Router.
- TypeScript estricto.
- Tailwind CSS.

### Renderizado

Preferir Server Components y renderizado apto para SEO cuando sea posible.

Usar Client Components únicamente donde la interacción lo requiera:

- Tema.
- Menú.
- Selector de idioma si corresponde.
- Animaciones.
- Campo de nodos.

Evitar hidratar contenido estático innecesariamente.

---

## Backend

Dirección:

- PHP.
- Laravel.
- MySQL.
- Filament.

Laravel es la fuente de verdad del contenido administrable.

El frontend no duplica el CMS.

---

## Base de datos

`portfolio-mysql` pertenece exclusivamente al portfolio.

Reglas:

- Red Docker privada.
- Sin puerto público.
- Volumen persistente independiente.
- Usuario/contraseña propios.
- Backup independiente.
- No compartir instancia lógica con demos por comodidad.
- No ejecutar resets automáticos destructivos.

---

## API

Principios:

- Namespace versionado.
- Solo lectura para contenido público.
- Solo contenido publicado.
- JSON consistente.
- Serialización explícita.
- Rate limiting.
- Errores controlados.
- Sin campos sensibles.
- Caché cuando tenga sentido.

El contenido en borrador nunca debe filtrarse a endpoints públicos.

---

## Modelo de contenido

### Profile

- Nombre.
- Título profesional.
- Resumen corto.
- Resumen extendido.
- Ubicación general.
- Fotografía.
- Disponibilidad.
- Estado de publicación.

### Experience

- Empresa o etiqueta anonimizada.
- Rol.
- Fechas.
- Resumen.
- Responsabilidades.
- Tecnologías.
- Orden.
- Visibilidad.

### Work Case

- Título.
- Contexto.
- Problema.
- Participación.
- Solución.
- Tecnologías.
- Aprendizajes/resultados descriptivos.
- Confidencialidad.
- Traducciones.
- Visibilidad.

### Project

- Nombre.
- Descripción.
- Descripción técnica.
- Imagen.
- Video opcional.
- Tecnologías.
- Demo URL.
- Repository URL.
- Destacado.
- Publicación.
- Orden.

La demo URL puede apuntar a subdominios alojados en el mismo servidor.

### Technology

- Nombre.
- Categoría.
- Icono.
- Nivel descriptivo opcional.
- Orden.
- Visibilidad.

### Site Settings

- Enlaces.
- Email.
- CV.
- SEO.
- Open Graph.
- Secciones.
- Disponibilidad.

---

## Internacionalización

Idiomas:

- Español.
- Inglés.

Reglas:

- No duplicar manualmente toda la aplicación.
- Contenido administrado traducible.
- Metadata localizada.
- Rutas previsibles como `/es` y `/en`.
- Persistir selección del usuario.
- No traducir nombres de marcas/tecnologías innecesariamente.

---

## Tema

- Dark.
- Light.
- Preferencia del sistema.

Reglas:

- Persistencia.
- Sin flash inicial notable.
- Tokens de diseño.
- Campo de nodos adaptado al tema.
- Contraste accesible.

---

## Animaciones

### Motion

Para:

- UI.
- Enter/exit.
- Hover/focus.
- Layout.
- Menú/tema/idioma.

### GSAP / ScrollTrigger

Solo para:

- Secuencias complejas.
- Storytelling de scroll.
- Timelines coordinados.
- Secciones fijadas justificadas.

No usar GSAP para efectos simples.

### Campo de nodos

Orden de evaluación:

1. Canvas 2D.
2. WebGL si es necesario.
3. React Three Fiber solo si justifica el costo.

Fallbacks:

- Móvil.
- Reduced motion.
- Background tab.
- Offscreen.

---

## Docker

Compose del portfolio esperado:

```text
gateway
web
api
mysql
```

Posibles servicios posteriores:

```text
scheduler
queue
redis
```

solo si aparecen requisitos reales.

### Redes

Conceptualmente:

```text
portfolio_front:
  gateway
  web
  api

portfolio_data:
  api
  mysql
```

MySQL no necesita pertenecer a la red frontal.

### Puertos

Solo:

```text
gateway -> 127.0.0.1:8000
```

Los demás servicios se comunican mediante DNS interno de Docker.

---

## CI

CI valida:

### Web

- Install.
- Lint.
- Type check.
- Tests.
- Build.

### API

- Install.
- Format/lint/static checks configurados.
- Tests.

### Docker

- Build validation.
- Config validation.

CI no debe asumir que es responsable del acceso público del servidor.

---

## Producción

El portfolio se ejecuta en la computadora Linux de Luciano.

No se despliega a una plataforma de hosting de aplicaciones.

Cloudflare proporciona DNS/edge/tunnel, pero los procesos y datos de la aplicación permanecen en el servidor propio.

---

## Seguridad

- Secretos solo del lado servidor.
- Panel administrativo autenticado.
- Sin registro público.
- CORS mínimo; idealmente mismo origen.
- Uploads validados.
- API pública limitada.
- Errores de producción sin traces internos.
- Información profesional sensible anonimizada.
- `.env` fuera de Git.
- Puerto MySQL no publicado.
- Gateway enlazado a loopback del host.
- `cloudflared` administrado fuera de este repo.

---

## Accesibilidad

Baseline:

- WCAG AA.
- Navegación por teclado.
- Focus visible.
- HTML semántico.
- Reduced motion.
- Controles accesibles.
- Canvas decorativo.

---

## Rendimiento

Prioridades:

1. Hero legible rápido.
2. Poca hidratación innecesaria.
3. Imágenes/fuentes optimizadas.
4. Lazy loading de animación pesada.
5. Pausa de animaciones fuera de viewport.
6. Simplificación móvil.
7. Core Web Vitals medidos.

---

## Política de decisiones

Actualizar este documento si cambia:

- Frontera frontend/backend.
- Base de datos.
- CMS.
- Autenticación.
- API.
- Renderizado.
- Gateway.
- Puerto asignado al portfolio.
- Servicios Docker internos.
- Tecnología principal de animación.

Actualizar `SERVER_ARCHITECTURE.md` si cambia:

- Estructura multiproyecto del host.
- Tunnel global.
- Convención de puertos.
- Política de backups compartida.
- Estrategia general de deploy.
