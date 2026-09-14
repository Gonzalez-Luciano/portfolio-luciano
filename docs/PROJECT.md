# PROJECT.md — Portfolio profesional de Luciano González

## Producto

Portfolio profesional de Luciano González.

Posicionamiento principal:

**Backend Developer | PHP & Laravel**

Objetivo laboral principal:

**Backend PHP/Laravel Jr.**

Mensaje que debe transmitir rápidamente:

> Este desarrollador sabe resolver sistemas backend reales.

---

## Narrativa profesional

El portfolio debe priorizar experiencia práctica sobre listas genéricas de tecnologías.

Temas principales:

- Desarrollo y mantenimiento de APIs REST con PHP y Laravel.
- Lógica de negocio en sistemas reales.
- Desarrollo y mantenimiento de sistemas de gestión educativa.
- Integraciones con APIs bancarias y medios de pago.
- Sincronización de pagos, comprobantes y estados.
- MySQL y optimización de consultas.
- Comandos Artisan, tareas programadas y automatizaciones.
- Mantenimiento y evolución de sistemas existentes y código legacy.
- Colaboración con equipos frontend.

La experiencia profesional pública debe anonimizarse cuando corresponda.

Nunca publicar datos confidenciales del empleador, clientes, bancos, credenciales, identificadores reales o reglas internas sensibles.

---

## Público objetivo

Principalmente:

- Reclutadores técnicos.
- Engineering leads.
- CTOs y responsables técnicos.
- Empresas de software.
- Consultoras que busquen perfiles PHP/Laravel.

El contenido debe ser comprensible para RR. HH. y técnicamente creíble para desarrolladores.

---

## Idiomas

El portfolio será completamente bilingüe:

- Español.
- Inglés.

El contenido visible, metadata, SEO y contenido administrado debe soportar ambos idiomas.

---

## Dirección visual

Estilo:

**Experimental + cinematográfico + profesional.**

Puede utilizar:

- Composición editorial.
- Tipografía grande.
- Profundidad visual.
- Storytelling mediante scroll.
- Transiciones animadas.
- Nodos/puntos interactivos.
- Fotografía profesional.

Debe evitar parecer:

- Una landing de criptomonedas.
- Una web de videojuegos.
- Un portfolio centrado exclusivamente en frontend.
- Una agencia de diseño.
- Una simulación permanente de terminal.

---

## Hero

Debe incluir:

- Fotografía profesional de Luciano.
- Nombre.
- Título: `Backend Developer | PHP & Laravel`.
- Propuesta de valor breve.
- Acción principal.
- Fondo/elemento de nodos o puntos interactivos que reaccionen al mouse.

El mensaje debe entenderse inmediatamente sin depender de la animación.

---

## Secciones principales

Versión inicial:

1. Hero.
2. Presentación profesional.
3. Experiencia y trabajo real.
4. Áreas de especialización.
5. Proyectos.
6. Tecnologías.
7. Forma de trabajo.
8. Contacto y CV.

Los proyectos se muestran como tarjetas dentro de la página principal.

No se requieren páginas individuales de proyecto en la primera versión.

---

## Experiencia profesional a destacar

Orden de prioridad:

1. Integraciones con APIs bancarias y medios de pago.
2. Desarrollo y mantenimiento de sistemas de gestión educativa.
3. APIs REST y reglas de negocio.
4. MySQL y rendimiento.
5. Automatizaciones y procesos programados.
6. Mantenimiento de sistemas existentes.

Posibles casos anonimizados:

- Integración con API de pagos.
- Sincronización de pagos y estados.
- Plataforma educativa multiinstitución.
- Módulos académicos y administrativos.
- Optimización de consultas.
- Procesos backend programados.

No inventar métricas públicas.

---

## Proyectos

Luciano va a crear nuevos proyectos para el portfolio.

Por lo tanto:

- Los proyectos viejos no condicionan el diseño.
- El CMS debe soportar cero proyectos publicados.
- Los proyectos nuevos podrán tener capturas, video, demo, repositorio y diagramas.
- La calidad tiene prioridad sobre la cantidad.
- Los proyectos incompletos deben poder quedar en borrador.

Las demos públicas se alojarán en el mismo VPS que el portfolio, usando Docker y subdominios independientes.

Ejemplo conceptual:

```text
lucianogonzalez.dev           -> portfolio
reservas.lucianogonzalez.dev  -> proyecto Laravel + React
tickets.lucianogonzalez.dev   -> otro proyecto
```

Los nombres definitivos se elegirán más adelante.

---

## Contacto

Versión inicial:

- LinkedIn.
- GitHub.
- Email.
- Descarga de CV.

El CV público se ofrece únicamente en PDF, con archivos separados para español e inglés administrados desde Filament. Las rutas estables son `/cv/luciano-gonzalez-es.pdf` y `/cv/luciano-gonzalez-en.pdf`. Cada idioma usa su archivo correspondiente; si falta, se oculta esa descarga. Los formatos editables no son públicos.

Sin formulario de contacto.
Sin WhatsApp.
Sin calendario de reuniones.

---

## Temas

Obligatorios:

- Modo oscuro.
- Modo claro.

El modo inicial respeta la preferencia del sistema y la selección manual debe persistir.

---

## Administración

El contenido del portfolio debe administrarse desde un panel privado.

Debe permitir gestionar:

- Perfil.
- Hero.
- Textos ES/EN.
- Experiencias.
- Casos anonimizados.
- Proyectos.
- Tecnologías.
- Enlaces.
- CV.
- Imágenes.
- SEO.
- Visibilidad y orden.
- Estado borrador/publicado.

El panel será una herramienta de mantenimiento, no un segundo proyecto visual.

## Política de confidencialidad

Queda prohibido publicar secretos, endpoints internos, datos personales o financieros reales, reglas privadas, código propietario, documentos internos, identidades confidenciales o material sujeto a obligaciones de confidencialidad.

Empleadores/clientes no públicos, instituciones, proveedores financieros, workflows, incidentes, infraestructura, capturas, logs, ejemplos de base de datos, payloads y afirmaciones cuantitativas requieren anonimización y revisión manual. Los artefactos visuales y técnicos deben revisarse también por datos ocultos y metadata.

Se puede publicar, tras revisar exactitud, la responsabilidad profesional propia, identidades ya intencionalmente públicas si no existe restricción, tecnologías públicas, patrones genéricos, aprendizajes, resultados cualitativos seguros y proyectos propios/open source.

Nunca inventar métricas, identidades, instituciones, logros ni escala. Toda afirmación cuantitativa debe ser verificable. Ante duda, no publicar. En Fase 1 se completa una matriz específica antes de ingresar contenido profesional real al CMS.

---

## Portfolio como demostración técnica

El propio portfolio debe demostrar buenas prácticas mediante:

- Laravel.
- REST API.
- MySQL.
- React / Next.js.
- Docker.
- Pruebas.
- CI.
- Documentación.
- Seguridad.
- Accesibilidad.
- Rendimiento.
- Releases versionadas con imágenes en GHCR.
- Despliegue en VPS propio administrado.
- Cloudflare como proxy y Caddy como reverse proxy.

La arquitectura debe existir por una razón real y no para inflar el stack.

---

## Infraestructura de producción

El portfolio es una aplicación dentro de un VPS Linux de OVHcloud administrado por Luciano.

Ese servidor también aloja otros proyectos independientes.

Principios:

- Cada proyecto vive en su propio directorio/repositorio.
- Cada proyecto usa su propio Docker Compose.
- Cada proyecto tiene redes y volúmenes aislados.
- Cada proyecto expone únicamente un puerto HTTP de entrada sobre loopback del host.
- Un Caddy global del host publica los proyectos por hostname detrás de Cloudflare DNS proxied.
- Cloudflare es la única entrada HTTP/HTTPS pública; el firewall del VPS solo admite `80`/`443` desde rangos de Cloudflare.
- Las bases de datos no se publican a Internet.
- El dominio se administra mediante Cloudflare DNS.
- El portfolio no depende de una plataforma externa de hosting de aplicaciones.

La arquitectura compartida del servidor se documenta en `docs/SERVER_ARCHITECTURE.md`.
