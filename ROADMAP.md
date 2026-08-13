# ROADMAP.md — Portfolio Backend PHP & Laravel

## 1. Visión

Construir un portfolio bilingüe, administrable y visualmente cinematográfico que posicione a Luciano González para puestos de:

**Backend PHP/Laravel Jr.**

El portfolio debe demostrar experiencia real en:

- APIs REST.
- Lógica de negocio.
- Sistemas de gestión educativa.
- Integraciones con APIs bancarias y medios de pago.
- MySQL.
- Automatizaciones.
- Mantenimiento de sistemas productivos.
- Docker y prácticas de entrega profesional.

La estética será experimental, pero el mensaje central siempre será backend.

---

## 2. Principios de ejecución

- Construir por fases.
- No comenzar por las animaciones.
- Validar primero el mensaje y la arquitectura.
- No inventar contenido para completar secciones.
- Priorizar una primera versión sólida antes de agregar efectos.
- Medir rendimiento desde el primer prototipo visual.
- Mantener el sitio funcional sin animaciones avanzadas.
- No publicar proyectos nuevos hasta que tengan calidad suficiente.
- Cada fase debe tener criterios de aceptación.
- No avanzar con errores críticos acumulados.

---

# Fase 0 — Descubrimiento y definición

## Objetivo

Cerrar las decisiones necesarias antes de inicializar el workspace de aplicaciones y los frameworks. El repositorio Git y su baseline documental ya existen.

## Tareas

- [x] Definir nombre del proyecto y repositorio.
- [x] Definir si será un monorepositorio desde el inicio.
- [x] Elegir herramienta de paquetes para frontend.
- [x] Confirmar Laravel 13 y versión de PHP.
- [x] Confirmar MySQL y versión de imagen Docker.
- [x] Elegir librería de internacionalización para Next.js App Router.
- [x] Confirmar Filament 5.
- [x] Definir estructura inicial de rutas `/es` y `/en`.
- [x] Definir URL local del frontend, API y administración.
- [x] Definir política inicial de ramas.
- [x] Reservar `127.0.0.1:8000` para el portfolio en el registro de puertos del servidor.
- [x] Confirmar dominio principal definitivo.
- [x] Definir convención de subdominios para futuros proyectos.
- [x] Confirmar que `cloudflared` será un servicio global del host y no un contenedor por proyecto.
- [x] Diferir distribución Linux, CPU, RAM, almacenamiento y layout físico al preflight de deployment.
- [x] Confirmar `/srv/apps` como raíz de aplicaciones y `/srv/backups` como raíz de backups.
- [x] Definir formato del CV público.
- [x] Preparar política de publicación y confidencialidad.

## Entregables

- Documento de arquitectura inicial.
- Diagrama de alto nivel.
- Convenciones del repositorio.
- Decisiones registradas.
- Backlog inicial priorizado.

## Criterios de aceptación

- Frontend, backend y administración tienen responsabilidades claras.
- La arquitectura de producción está definida para ejecutarse completamente en el servidor propio mediante Docker y Cloudflare Tunnel.
- Las decisiones diferidas están identificadas.
- No queda ninguna duda que impida crear el repositorio.

---

# Fase 1 — Identidad, contenido y narrativa

## Objetivo

Definir qué verá y entenderá el reclutador antes de diseñar la interfaz final.

## Tareas

### Posicionamiento

- [x] Redactar titular principal en español.
- [x] Redactar titular principal en inglés.
- [x] Redactar resumen profesional corto.
- [x] Redactar presentación extendida.
- [x] Definir llamado a la acción principal.
- [x] Definir mensaje de disponibilidad laboral.

### Experiencia

- [x] Crear una versión pública y anonimizada de la experiencia en gestión educativa.
- [x] Crear una versión pública y anonimizada de las integraciones con APIs bancarias.
- [x] Describir trabajo con APIs REST.
- [x] Describir automatizaciones y tareas programadas.
- [ ] Describir optimización de consultas y soporte multiinstitución.
- [x] Revisar que no se expongan clientes, credenciales, rutas, datos ni reglas privadas.
- [x] Separar responsabilidades propias de logros del equipo.

> Pendiente: el contenido aprobado describe optimización de consultas, pero no incluye una descripción pública y aprobada de soporte multiinstitución; por eso el ítem combinado permanece abierto.

### Contacto

- [x] Confirmar LinkedIn.
- [x] Confirmar GitHub.
- [x] Confirmar correo público.
- [x] Aprobar versión del CV descargable.

> Los dos PDF están aprobados como activos para futura publicación, pero su extracción y comparación textual siguen pendientes; no se usaron para respaldar nuevas afirmaciones. Además, el PDF español declara actualmente `/Lang(en-US)` y debe reexportarse o retagearse como español, con nueva aprobación, antes de ser servido.

### Fotografía

- [x] Elegir o producir fotografía profesional.
- [x] Preparar recorte horizontal y vertical.
- [x] Preparar versión optimizada.
- [x] Definir texto alternativo.
- [x] Verificar integración en modo claro y oscuro.

> Los derivados optimizados y la validación visual en ambos temas se completaron en el prototipo de Fase 2. La integración de medios mediante el futuro flujo CMS sigue pendiente.

### Traducción

- [x] Crear glosario técnico español/inglés.
- [x] Traducir el contenido profesional.
- [x] Revisar el inglés de forma humana.
- [x] Mantener consistencia en términos como backend, API, payment integration y education management platform/system según el alcance aprobado.

## Entregables

- Documento de contenido aprobado.
- Textos en español e inglés.
- Material visual inicial.
- CV definitivo.
- Matriz de confidencialidad.

## Criterios de aceptación

- El mensaje “resuelve sistemas backend reales” se entiende en menos de diez segundos.
- Las integraciones bancarias y la gestión educativa tienen prioridad.
- El contenido es creíble sin exageraciones.
- Todo texto tiene versión equivalente en ambos idiomas.
- No hay información confidencial.

---

# Fase 2 — Diseño de experiencia

## Objetivo

Diseñar la experiencia completa antes de implementar animaciones complejas.

## Tareas

### Arquitectura de información

- [x] Definir orden definitivo de secciones.
- [x] Definir navegación de escritorio.
- [x] Definir navegación móvil.
- [x] Definir comportamiento de enlaces internos.
- [x] Definir ubicación de idioma y tema.
- [x] Definir estados con y sin proyectos publicados.

### Wireframes

- [x] Wireframe del hero.
- [x] Wireframe de experiencia.
- [x] Wireframe de casos de trabajo.
- [x] Wireframe de especializaciones.
- [x] Wireframe de proyectos.
- [x] Wireframe de tecnologías.
- [x] Wireframe de forma de trabajo.
- [x] Wireframe de contacto.
- [x] Wireframe de menú móvil.
- [x] Wireframe de estados de carga y error.

### Sistema visual

- [x] Elegir paleta clara.
- [x] Elegir paleta oscura.
- [x] Elegir tipografías.
- [x] Definir escala tipográfica.
- [x] Definir espaciado.
- [x] Definir radios, bordes y sombras.
- [x] Definir tokens de color y movimiento.
- [x] Definir tratamiento de fotografía.
- [x] Definir iconografía.
- [x] Definir apariencia de tarjetas de proyecto.
- [x] Definir estados hover, focus, active y disabled.

### Prototipo

- [x] Crear prototipo estático de alta fidelidad.
- [x] Revisar desktop.
- [x] Revisar tablet.
- [x] Revisar mobile.
- [x] Revisar modo claro.
- [x] Revisar modo oscuro.
- [x] Validar contraste.
- [x] Validar legibilidad sin animaciones.

## Entregables

- Mapa del sitio.
- Wireframes.
- Prototipo de alta fidelidad.
- Tokens de diseño.
- Especificación responsive.

## Criterios de aceptación

- El diseño se reconoce como profesional y técnico.
- No parece una web de agencia, videojuego o criptomonedas.
- La fotografía y el mensaje backend dominan el hero.
- La página funciona visualmente sin animación.
- Los dos temas tienen calidad equivalente.
- El diseño móvil no es una versión recortada sin criterio.

---

# Fase 3 — Arquitectura técnica y entorno Docker

## Objetivo

Crear una base reproducible para frontend, backend, base de datos y administración, con un entorno completo de desarrollo/pruebas y límites de servicio compatibles con el futuro handoff de deployment.

## Tareas

### Repositorio

- [ ] Inicializar el workspace de aplicaciones del monorepositorio existente.
- [ ] Crear estructura para `web`, `api`, `infra` y `docs`.
- [ ] Configurar `.editorconfig`.
- [ ] Configurar política de variables de entorno.
- [ ] Crear archivos de ejemplo sin secretos.
- [ ] Documentar comandos principales.

### Frontend

- [ ] Inicializar Next.js con App Router.
- [ ] Activar TypeScript estricto.
- [ ] Configurar Tailwind CSS.
- [ ] Configurar lint y formato.
- [ ] Preparar rutas localizadas.
- [ ] Preparar sistema de tema.
- [ ] Preparar cliente de API tipado.
- [ ] Definir estrategia de Server y Client Components.

### Backend

- [ ] Inicializar Laravel.
- [ ] Servir Laravel mediante Apache + PHP 8.5 interno con `public/` como `DocumentRoot`, rewrite/front controller y permisos runtime explícitos.
- [ ] Configurar MySQL.
- [ ] Configurar API versionada.
- [ ] Configurar recursos JSON.
- [ ] Configurar autenticación administrativa.
- [ ] Instalar y configurar Filament.
- [ ] Preparar almacenamiento de medios.
- [ ] Configurar CORS.
- [ ] Configurar rate limiting.
- [ ] Configurar logs.

### Docker

- [ ] Crear entorno de desarrollo.
- [ ] Crear servicio frontend.
- [ ] Crear servicio backend.
- [ ] Crear servicio MySQL 8.4 persistente para desarrollo.
- [ ] Crear servicio/perfil MySQL 8.4 descartable y bajo demanda para pruebas automatizadas.
- [ ] Crear `api-test` one-shot en el perfil de pruebas, reutilizando la imagen backend y esperando a `mysql-test` saludable.
- [ ] Crear Caddy como gateway/reverse proxy del portfolio y enlazar únicamente `127.0.0.1:8000`.
- [ ] Inventariar rutas y tráfico público reales de Laravel/Filament/Livewire/media antes de fijar los matchers backend de Caddy.
- [ ] Verificar compatibilidad del stack con el `cloudflared` global del servidor; no crear `cloudflared` dentro del proyecto.
- [ ] Definir redes Docker internas del portfolio (`front` y `data` o equivalente).
- [ ] Confirmar que MySQL no publica puertos al host ni a Internet.
- [ ] Agregar health checks.
- [ ] Agregar volúmenes.
- [ ] Configurar red interna.
- [ ] Definir estrategia de migraciones.
- [ ] Definir seed inicial.
- [ ] Crear un comando interactivo y explícito de bootstrap del administrador con entrada secreta oculta; los seeds normales no deben crear credenciales.
- [ ] Documentar arranque, parada y reinicio.
- [ ] Documentar PowerShell + Docker Desktop con backend WSL2 como flujo canónico de Windows.
- [ ] Comprobar operación equivalente desde Ubuntu WSL2 mediante la integración de Docker Desktop, sin instalar un segundo Docker Engine.
- [ ] Documentar el contrato de handoff sin crear Compose final de producción ni infraestructura específica del host.

## Entregables

- Repositorio inicial.
- Entorno local reproducible.
- Frontend base.
- API base.
- Panel administrativo accesible.
- Documentación de instalación.

## Criterios de aceptación

- El entorno completo se levanta mediante un procedimiento único documentado.
- Frontend y backend se comunican.
- La base de datos persiste.
- Las pruebas usan una base MySQL 8.4 descartable y no pueden reutilizar datos de desarrollo.
- No hay secretos en Git.
- Los builds básicos terminan correctamente.
- La administración requiere autenticación.
- Frontend, backend, gateway y MySQL pueden ejecutarse de forma reproducible mediante Docker; `cloudflared` permanece como servicio compartido del host.
- El repositorio describe límites, persistencia, variables, migraciones, bootstrap y health checks suficientes para el futuro agente externo de operaciones.

---

# Fase 4 — CMS y modelo de datos

## Objetivo

Permitir administrar todo el contenido relevante desde Laravel/Filament.

## Tareas

### Modelado

- [ ] Crear perfil.
- [ ] Crear experiencias.
- [ ] Crear casos de trabajo.
- [ ] Crear proyectos.
- [ ] Crear tecnologías.
- [ ] Crear enlaces.
- [ ] Crear configuración del sitio.
- [ ] Crear medios.
- [ ] Crear estados borrador y publicado.
- [ ] Crear orden manual.
- [ ] Crear visibilidad por elemento.
- [ ] Crear campos bilingües.

### Administración

- [ ] CRUD de perfil.
- [ ] CRUD de experiencias.
- [ ] CRUD de casos.
- [ ] CRUD de proyectos.
- [ ] CRUD de tecnologías.
- [ ] Gestión de enlaces.
- [ ] Gestión del CV.
- [ ] Gestión de imágenes.
- [ ] Filtros por estado.
- [ ] Ordenamiento.
- [ ] Validaciones.
- [ ] Confirmaciones de eliminación.
- [ ] Vista previa o mecanismo de revisión.

### API

- [ ] Endpoint público de configuración.
- [ ] Endpoint público de perfil.
- [ ] Endpoint público de experiencia.
- [ ] Endpoint público de casos.
- [ ] Endpoint público de proyectos.
- [ ] Endpoint público de tecnologías.
- [ ] Respuestas por idioma.
- [ ] Solo contenido publicado.
- [ ] Recursos JSON consistentes.
- [ ] Caché inicial.
- [ ] Errores controlados.
- [ ] Documentación del contrato.

## Entregables

- Migraciones.
- Modelos.
- Panel administrable.
- API pública.
- Seed de demostración.
- Documento del contrato de API.

## Criterios de aceptación

- Se puede modificar el contenido sin tocar React.
- Los borradores no aparecen públicamente.
- El contenido español e inglés se administra sin confusión.
- Los proyectos pueden quedar ocultos hasta estar listos.
- El sitio puede funcionar con cero proyectos.
- La API no expone datos administrativos.

---

# Fase 5 — Implementación del sitio público

## Objetivo

Construir una versión funcional, accesible y responsive antes de añadir la capa cinematográfica.

## Tareas

### Base

- [ ] Layout global.
- [ ] Header.
- [ ] Navegación.
- [ ] Selector de idioma.
- [ ] Selector de tema.
- [ ] Footer.
- [ ] Estados de carga.
- [ ] Estados de error.
- [ ] Página 404.

### Secciones

- [ ] Hero.
- [ ] Presentación.
- [ ] Experiencia.
- [ ] Casos de trabajo.
- [ ] Especializaciones.
- [ ] Proyectos.
- [ ] Tecnologías.
- [ ] Forma de trabajo.
- [ ] Contacto.
- [ ] Descarga de CV.

### Integración

- [ ] Consumo de API.
- [ ] Tipado de respuestas.
- [ ] Manejo de errores.
- [ ] Estrategia de caché.
- [ ] Revalidación.
- [ ] Fallback si la API no está disponible.
- [ ] Optimización de imágenes.
- [ ] Integración de fotografía.
- [ ] Metadata inicial.

### Responsive y accesibilidad

- [ ] Navegación por teclado.
- [ ] Focus visible.
- [ ] Contraste.
- [ ] Jerarquía de encabezados.
- [ ] Textos alternativos.
- [ ] Lectura con zoom.
- [ ] Menú móvil accesible.
- [ ] Prueba con lector de pantalla.
- [ ] Prueba sin animaciones.

## Entregables

- Portfolio funcional.
- Dos idiomas.
- Dos temas.
- Contenido conectado al CMS.
- Versión responsive.
- Versión accesible base.

## Criterios de aceptación

- Todo el contenido principal es utilizable.
- Los enlaces de contacto funcionan.
- El CV se descarga correctamente.
- El sitio es navegable con teclado.
- La experiencia es clara sin animaciones.
- No hay errores de hidratación.
- El backend sigue siendo el centro del mensaje.

---

# Fase 6 — Sistema de movimiento

## Objetivo

Agregar movimiento cinematográfico de forma progresiva y medible.

## Orden obligatorio

1. Microinteracciones.
2. Entradas de secciones.
3. Transiciones de layout.
4. Secuencias de scroll.
5. Campo de nodos.
6. Ajustes de rendimiento.
7. Degradación móvil y movimiento reducido.

## Tareas

### Motion

- [ ] Animación inicial del hero.
- [ ] Estados de botones.
- [ ] Estados de enlaces.
- [ ] Selector de tema.
- [ ] Selector de idioma.
- [ ] Tarjetas de experiencia.
- [ ] Tarjetas de proyecto.
- [ ] Transiciones de secciones.
- [ ] Presencia y salida de menú móvil.

### GSAP y ScrollTrigger

- [ ] Definir storyboard de scroll.
- [ ] Elegir un máximo de dos secuencias complejas.
- [ ] Implementar entrada tipográfica.
- [ ] Implementar transición narrativa de experiencia.
- [ ] Evaluar una sección fijada.
- [ ] Probar scroll rápido.
- [ ] Probar cambio de tamaño.
- [ ] Limpiar timelines al desmontar.
- [ ] Eliminar efectos que no aporten.

### Campo de nodos interactivos

- [ ] Crear prototipo Canvas 2D.
- [ ] Medir rendimiento.
- [ ] Crear prototipo WebGL solo si es necesario.
- [ ] Comparar consumo y resultado.
- [ ] Reacción por proximidad al cursor.
- [ ] Conexiones entre nodos.
- [ ] Límites de densidad.
- [ ] Pausa fuera de viewport.
- [ ] Pausa con pestaña oculta.
- [ ] Reducción automática en móvil.
- [ ] Versión estática para movimiento reducido.
- [ ] Integración con tema claro y oscuro.
- [ ] Verificar que no tape la fotografía ni el texto.

### Rendimiento

- [ ] Importación dinámica.
- [ ] Medición de FPS.
- [ ] Medición de CPU.
- [ ] Medición de memoria.
- [ ] Medición de JavaScript transferido.
- [ ] Prueba en notebook media.
- [ ] Prueba en teléfono Android medio.
- [ ] Prueba con ahorro de batería.
- [ ] Desactivar efectos fuera del viewport.
- [ ] Revisar Core Web Vitals.

## Entregables

- Sistema de movimiento documentado.
- Campo de nodos interactivo.
- Versión reducida.
- Storyboard.
- Informe de rendimiento antes y después.

## Criterios de aceptación

- Las animaciones refuerzan la narrativa.
- El contenido nunca queda bloqueado.
- El hero es usable inmediatamente.
- No hay scroll-jacking.
- El campo de nodos responde con suavidad.
- La versión móvil mantiene fluidez.
- `prefers-reduced-motion` funciona.
- El sitio sigue siendo profesional.

---

# Fase 7 — Proyectos nuevos

## Objetivo

Crear y publicar proyectos que respalden el perfil backend.

Esta fase puede desarrollarse en paralelo después de que el CMS acepte proyectos.

## Criterios para elegir proyectos

Cada proyecto debe demostrar al menos una capacidad relevante:

- API REST con Laravel.
- Autenticación y autorización.
- Integración externa.
- Pagos o webhooks simulados de forma segura.
- Procesos en cola.
- Tareas programadas.
- MySQL y modelado.
- Pruebas.
- Docker.
- CI/CD.
- Documentación.
- Observabilidad.
- Manejo de errores.
- Seguridad.

## Material obligatorio por proyecto

- [ ] Repositorio público limpio.
- [ ] README completo.
- [ ] Capturas profesionales.
- [ ] Video breve o demo.
- [ ] Diagrama.
- [ ] Tecnologías.
- [ ] Problema.
- [ ] Solución.
- [ ] Instrucciones de instalación.
- [ ] Docker.
- [ ] Pruebas.
- [ ] Licencia.
- [ ] Demo estable.
- [ ] Datos de demostración seguros.
- [ ] Texto español e inglés para la tarjeta.

## Publicación

- [ ] Cargar proyecto en CMS.
- [ ] Revisar imagen.
- [ ] Verificar enlaces.
- [ ] Verificar responsive.
- [ ] Verificar traducción.
- [ ] Verificar accesibilidad.
- [ ] Publicar solamente después de completar el checklist.

## Criterios de aceptación

- Ningún proyecto parece un tutorial sin evolución.
- Cada proyecto explica decisiones técnicas.
- El material visual tiene calidad consistente.
- Las demos funcionan.
- Los repositorios no contienen secretos.
- Los proyectos apoyan la búsqueda de empleo Laravel.

---

# Fase 8 — SEO, metadata y analítica

## Objetivo

Hacer que el portfolio sea encontrable, compartible y medible.

## Tareas

- [ ] Metadata en español.
- [ ] Metadata en inglés.
- [ ] Canonical.
- [ ] `hreflang`.
- [ ] Sitemap.
- [ ] Robots.
- [ ] Open Graph.
- [ ] Twitter card.
- [ ] Imagen social.
- [ ] Datos estructurados Person.
- [ ] Datos estructurados WebSite.
- [ ] Favicon.
- [ ] Manifest opcional.
- [ ] Página 404.
- [ ] Exclusión de administración.
- [ ] Analítica respetuosa de privacidad.
- [ ] Eventos de clic en CV, GitHub, LinkedIn y correo.
- [ ] No registrar información sensible.
- [ ] Revisar indexación por idioma.

## Entregables

- Configuración SEO.
- Imagen social.
- Analítica.
- Checklist de indexación.

## Criterios de aceptación

- Los enlaces compartidos muestran imagen, título y descripción correctos.
- Cada idioma tiene metadata propia.
- Administración y previews no se indexan.
- Los eventos de contacto pueden medirse sin invadir privacidad.

---

# Fase 9 — Seguridad y endurecimiento

## Objetivo

Preparar el sistema para exposición pública.

## Tareas

- [ ] Revisar autenticación del panel.
- [ ] Desactivar registro público.
- [ ] Rate limiting.
- [ ] CORS por dominio.
- [ ] Validación de archivos.
- [ ] Límites de tamaño.
- [ ] Sanitización.
- [ ] Cabeceras de seguridad.
- [ ] Gestión de secretos.
- [ ] Rotación de credenciales iniciales.
- [ ] Backups.
- [ ] Restauración de prueba.
- [ ] Actualización de dependencias.
- [ ] Auditoría de paquetes.
- [ ] Revisión de logs.
- [ ] Ocultar información interna en errores.
- [ ] Política de contraseñas.
- [ ] Protección de rutas administrativas.
- [ ] Revisión del CV público.
- [ ] Revisión de información confidencial.

## Entregables

- Checklist de seguridad.
- Política de secretos.
- Procedimiento de backup.
- Procedimiento de recuperación.

## Criterios de aceptación

- No hay secretos en cliente ni repositorio.
- Los archivos están restringidos.
- Los errores no filtran información interna.
- El panel no permite registro.
- Existe un backup recuperable.

---

# Fase 10 — Pruebas y control de calidad

## Objetivo

Comprobar el funcionamiento completo antes del lanzamiento.

## Frontend

- [ ] Lint.
- [ ] Type check.
- [ ] Build.
- [ ] Pruebas unitarias críticas.
- [ ] Pruebas de integración.
- [ ] Pruebas E2E.
- [ ] Idioma.
- [ ] Tema.
- [ ] Navegación.
- [ ] Contacto.
- [ ] Descarga del CV.
- [ ] Proyectos con y sin contenido.
- [ ] API caída.
- [ ] 404.
- [ ] Accesibilidad automática.
- [ ] Accesibilidad manual.
- [ ] Responsive.
- [ ] Navegadores principales.

## Backend

- [ ] Pruebas unitarias.
- [ ] Pruebas de API.
- [ ] Autenticación.
- [ ] Autorización.
- [ ] Publicación.
- [ ] Borradores.
- [ ] Traducciones.
- [ ] Archivos.
- [ ] Rate limiting.
- [ ] CORS.
- [ ] Validaciones.
- [ ] Recursos JSON.
- [ ] Migraciones desde cero.
- [ ] Seed.
- [ ] Análisis estático.
- [ ] Formato.

## Animaciones

- [ ] Mouse.
- [ ] Touchpad.
- [ ] Touch.
- [ ] Teclado.
- [ ] Movimiento reducido.
- [ ] Pestaña en segundo plano.
- [ ] Scroll rápido.
- [ ] Resize.
- [ ] Rotación móvil.
- [ ] Bajo rendimiento.
- [ ] Modo claro.
- [ ] Modo oscuro.

## Entregables

- Matriz de pruebas.
- Reporte de errores.
- Reporte de accesibilidad.
- Reporte de rendimiento.
- Evidencia de builds correctos.

## Criterios de aceptación

- No hay errores críticos.
- No hay enlaces rotos.
- No hay contenido faltante en un idioma.
- No hay fallos graves de accesibilidad.
- No hay degradación severa por animaciones.
- Frontend y backend pasan CI.

---

# Fase 11 — CI y preparación de entrega

## Objetivo

Convertir el repositorio aprobado en un artefacto verificable y listo para entregar al flujo externo de operaciones del servidor, sin acceder ni desplegar directamente en el servidor doméstico.

## Tareas

### GitHub Actions y controles del repositorio

- [ ] Crear workflow frontend.
- [ ] Crear workflow backend.
- [ ] Crear workflow Docker.
- [ ] Configurar caché de dependencias donde sea segura y útil.
- [ ] Ejecutar lint y formato verificable.
- [ ] Ejecutar TypeScript checks.
- [ ] Ejecutar pruebas frontend y backend.
- [ ] Ejecutar builds de aplicación.
- [ ] Validar configuración y builds Docker definidos por el repositorio.
- [ ] Ejecutar auditorías de dependencias y seguridad acordadas.
- [ ] Documentar requisitos de protección de `main` y revisión previa a release.
- [ ] Confirmar que CI no necesita acceso al servidor doméstico, sus secretos ni su red privada.

### Preparación de release

- [ ] Definir el checklist de release readiness.
- [ ] Confirmar que la versión candidata proviene de una rama y commit aprobados.
- [ ] Registrar comandos reproducibles de build, pruebas y smoke checks de aplicación.
- [ ] Documentar versionado, release notes y creación de tags anotados.
- [ ] Confirmar que los artefactos y logs de CI no exponen secretos ni contenido confidencial.

### Handoff de deployment

- [ ] Documentar servicios, gateway y puertos internos del proyecto.
- [ ] Documentar el futuro entrypoint `127.0.0.1:8000` y las rutas `/`, `/es`, `/en`, `/api/*` y `/admin/*`.
- [ ] Documentar datos persistentes, variables requeridas y secretos necesarios sin incluir valores.
- [ ] Documentar procedimientos de build, migración, bootstrap administrativo y health checks de aplicación.
- [ ] Identificar los datos/rutas lógicas relevantes para backup y la información de aplicación necesaria para rollback.
- [ ] Documentar qué servicios y puertos nunca deben exponerse públicamente.
- [ ] Preservar la frontera de Cloudflare: `cloudflared` y sus credenciales permanecen fuera del repositorio.
- [ ] Enumerar las condiciones del servidor que el flujo externo debe descubrir en el preflight, sin inferirlas.
- [ ] Referenciar el flujo externo `home_server_ops_claude` como propietario del deployment y las operaciones físicas.

## Entregables

- Pipeline de CI del repositorio.
- Candidato de release validado.
- Checklist de release readiness.
- Contrato de runtime y deployment actualizado.
- Handoff autocontenido para el agente externo de operaciones del servidor.
- Procedimientos de build, migración, bootstrap y smoke checks de aplicación.

## Criterios de aceptación

- Cada cambio relevante pasa las validaciones automáticas acordadas.
- La versión candidata se puede reconstruir y verificar desde un commit aprobado.
- CI no accede al servidor doméstico ni requiere secretos de host o Cloudflare.
- El handoff describe con precisión servicios, entrypoint, persistencia, secretos, migraciones, bootstrap, health checks y límites de exposición.
- El repositorio queda listo para ser clonado y operado por el flujo externo sin duplicar su checklist de host.
- Ninguna tarea de esta fase afirma que el portfolio ya fue desplegado.

---

# Fase 12 — Lanzamiento

## Objetivo

Publicar el portfolio y comenzar a utilizarlo en postulaciones.

## Tareas

### Prerrequisitos externos de lanzamiento

- [ ] Confirmar que el flujo externo de operaciones desplegó la versión aprobada.
- [ ] Confirmar que el dominio público y las rutas `/api` y `/admin` responden según el contrato.
- [ ] Confirmar que los smoke checks de producción pasaron.
- [ ] Confirmar que existe un backup inicial gestionado y verificado por operaciones.

### Revisión y comunicación

- [ ] Revisión final de contenido.
- [ ] Revisión final en inglés.
- [ ] Revisión de fotografía.
- [ ] Revisión de CV.
- [ ] Revisión de enlaces.
- [ ] Revisión de confidencialidad.
- [ ] Revisión móvil.
- [ ] Lighthouse final.
- [ ] Core Web Vitals.
- [ ] Prueba desde otra red.
- [ ] Prueba sin sesión de administrador.
- [ ] Etiqueta de versión.
- [ ] Aprobar publicación después de recibir el handoff operativo exitoso.
- [ ] Actualizar LinkedIn.
- [ ] Actualizar GitHub.
- [ ] Agregar enlace al CV.
- [ ] Preparar mensaje de presentación para postulaciones.

## Entregables

- Versión 1.0 pública.
- Release notes.
- Confirmación del handoff operativo, smoke checks y backup inicial externo.
- Enlaces profesionales actualizados.

## Criterios de aceptación

- El sitio puede enviarse a un reclutador sin explicación adicional.
- La propuesta profesional se entiende rápido.
- Los cuatro contactos funcionan.
- El contenido real tiene prioridad sobre los efectos.
- La experiencia es estable en móvil y escritorio.
- El deployment y el backup fueron confirmados por el flujo externo de operaciones, no ejecutados por el roadmap de desarrollo.

---

# Fase 13 — Postlanzamiento

## Objetivo

Mejorar el portfolio mediante evidencia y no por impulso.

## Primera semana

- [ ] Revisar errores.
- [ ] Revisar los logs de aplicación disponibles mediante el handoff operativo, sin asumir administración del host.
- [ ] Revisar analítica.
- [ ] Verificar indexación.
- [ ] Verificar descargas del CV.
- [ ] Corregir enlaces.
- [ ] Consultar a dos personas técnicas.
- [ ] Consultar a una persona no técnica.

## Primer mes

- [ ] Publicar el primer proyecto nuevo fuerte.
- [ ] Revisar qué secciones reciben interacción.
- [ ] Mejorar textos con feedback.
- [ ] Ajustar rendimiento.
- [ ] Revisar dependencias.
- [ ] Confirmar con operaciones el estado de backups y restores del portfolio.

## Mejoras futuras posibles

- [ ] Página individual de proyecto, solo si las tarjetas resultan insuficientes.
- [ ] Blog técnico.
- [ ] RSS.
- [ ] Página de notas técnicas.
- [ ] Open source de componentes.
- [ ] Automatización de proyectos desde GitHub.
- [ ] Observabilidad avanzada.
- [ ] Panel de métricas.
- [ ] Migración de medios a almacenamiento dedicado.
- [ ] Nuevos idiomas, solo si existe una necesidad real.

---

# Hitos

## Hito A — Base definida

Incluye fases 0 a 2.

Resultado:

- Mensaje profesional.
- Contenido.
- Arquitectura.
- Diseño aprobado.

## Hito B — Producto funcional

Incluye fases 3 a 5.

Resultado:

- Docker.
- Laravel.
- Filament.
- API.
- Next.js.
- Español e inglés.
- Tema claro y oscuro.
- Sitio sin animación avanzada.

## Hito C — Experiencia cinematográfica

Incluye fase 6.

Resultado:

- Motion.
- GSAP.
- ScrollTrigger.
- Campo de nodos.
- Movimiento reducido.
- Rendimiento validado.

## Hito D — Portfolio demostrable

Incluye fases 7 y 8.

Resultado:

- Proyectos nuevos.
- Material visual.
- SEO.
- Analítica.

## Hito E — Entrega, deployment externo y lanzamiento

Incluye fases 9 a 12.

Resultado:

- Seguridad.
- Pruebas.
- CI y candidato de release reproducible.
- Repositorio y contrato de deployment entregados al flujo externo de operaciones.
- Deployment en el servidor propio y Cloudflare Tunnel confirmados externamente.
- Frontend, backend, gateway y MySQL verificados contra el contrato de runtime.
- Lanzamiento.

---

# Prioridades

## Prioridad 1

- Mensaje profesional.
- Contenido real.
- Arquitectura.
- Accesibilidad.
- Rendimiento.
- CMS.
- Backend Laravel.
- Docker.
- Bilingüe.
- Responsive.

## Prioridad 2

- Animaciones.
- Campo de nodos.
- Transiciones cinematográficas.
- SEO avanzado.
- Analítica.

## Prioridad 3

- Blog.
- Páginas individuales.
- Automatizaciones externas.
- Funciones adicionales.

No avanzar con prioridades 3 mientras existan problemas en prioridades 1.

---

# Riesgos principales

## Exceso de animación

Riesgo:

- El portfolio puede parecer frontend-first o perder legibilidad.

Mitigación:

- Aprobar diseño estático primero.
- Limitar secuencias complejas.
- Medir rendimiento.
- Eliminar cualquier efecto que compita con el contenido.

## Complejidad de dos aplicaciones

Riesgo:

- Next.js y Laravel aumentan despliegue y mantenimiento.

Mitigación:

- Separación clara.
- Docker.
- Contrato de API.
- CI.
- Documentación.
- Evitar duplicación.

## Administración demasiado grande

Riesgo:

- Invertir más tiempo en el CMS que en el portfolio.

Mitigación:

- Usar Filament.
- Implementar solo CRUD necesario.
- Un único administrador.
- No personalizar en exceso.

## Falta de proyectos listos

Riesgo:

- Lanzamiento sin evidencia pública suficiente.

Mitigación:

- Diseñar estado sin proyectos.
- Mostrar experiencia profesional anonimizada.
- Publicar proyectos solo cuando sean fuertes.
- Desarrollar proyectos en paralelo.

## Información confidencial

Riesgo:

- Exponer detalles del trabajo real.

Mitigación:

- Matriz de confidencialidad.
- Anonimización.
- Revisión manual antes de publicar.
- No inventar ni revelar nombres o datos internos.

## Servidor físico como punto único de producción

Riesgo:

- Energía, conexión, disco, Linux o Docker pueden dejar fuera de línea todas las demos.

Mitigación:

- Entregar health checks y requisitos de persistencia claros desde el repositorio.
- Delegar reinicio automático, `cloudflared`, backups, restores, monitoreo, recuperación y seguridad del host al flujo externo `home_server_ops_claude`.
- Confirmar esas capacidades como prerrequisitos externos de lanzamiento.
- Evaluar UPS y redundancia desde operaciones si el proyecto lo justifica.

## Colisión entre proyectos

Riesgo:

- Dos proyectos pueden intentar usar el mismo puerto, volumen, red o recurso.

Mitigación:

- Publicar con claridad el entrypoint reservado `127.0.0.1:8000`, las redes y los volúmenes propios del portfolio.
- Delegar el registro central de puertos y la coordinación multiproyecto al flujo externo de operaciones.
- Nombres/prefijos por proyecto.
- Redes independientes.
- Volúmenes independientes.
- Un directorio/repositorio por proyecto.

## Automatización insegura del deploy

Riesgo:

- Un runner o SSH expuesto de forma incorrecta puede dar acceso al servidor.

Mitigación:

- CI separado de deploy.
- CI del repositorio sin acceso al servidor doméstico ni secretos del host.
- Entregar el candidato aprobado y su contrato al flujo externo de operaciones.
- Delegar SSH, runners, automatización y controles de acceso del host a `home_server_ops_claude`.

---

# Backlog inicial sugerido

1. Aprobar arquitectura.
2. Aprobar contenido.
3. Crear wireframes.
4. Crear prototipo visual estático.
5. Inicializar el workspace de aplicaciones del monorepositorio existente.
6. Crear Docker.
7. Crear Laravel y Filament.
8. Modelar contenido.
9. Crear API.
10. Crear Next.js.
11. Integrar idiomas.
12. Integrar temas.
13. Construir secciones.
14. Conectar CMS.
15. Agregar Motion.
16. Agregar GSAP.
17. Crear campo de nodos.
18. Medir y optimizar.
19. Crear proyectos nuevos.
20. Implementar SEO.
21. Implementar pruebas.
22. Configurar CI y preparar el handoff de deployment.
23. Entregar la versión aprobada al flujo externo `home_server_ops_claude`.
24. Confirmar externamente deployment, smoke checks y backup inicial.
25. Lanzar.
