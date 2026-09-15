# Fase 6 — Estructura, CMS y diseño visual del portfolio

- **Fecha:** 2026-09-14
- **Rama / worktree:** `feat/phase-6-cinematic-scroll` / `.worktrees/phase-6-cinematic-scroll`
- **Autoridad:** decisiones tomadas por Luciano durante el brainstorming del 2026-09-14.
- **Reemplaza:** las secciones 1, 2 y 3 de `2026-09-14-phase-6-cinematic-scroll-design.md` (copy y visual de la escena). Siguen vigentes su lógica de scroll (sección 3, `useVideoScrub`) y las decisiones de la revisión 1.1 (front base en `web/`, datos desde la API, campos `statement`/`closing`).
- **Estado de partida:** commits `677e70f`…`5766a88` (front Vite en `web/`, campos de escena en Profile, headline "Backend Engineer", foto nueva, fix de alt text en Filament).

## 1. Objetivo

Convertir el front de la escena de scroll en el portfolio completo:

1. Definir las secciones de la página y su contenido.
2. Hacer que todo el contenido de trabajo y proyectos sea escalable y editable desde Filament.
3. Definir el sistema visual completo (paleta, tipografía, escena, secciones, modo oscuro, navegación).

La lógica de scroll existente (pista de 500vh, curvas de opacidad secuenciales, lerp, banco de frames) se conserva. La capa visual se rediseña.

## 2. Resumen de decisiones

| # | Tema | Decisión |
|---|---|---|
| D1 | Agrupación del trabajo | Tres grupos: Experiencia laboral, Proyectos para clientes, Proyectos personales |
| D2 | Trucks and Drinks | Se nombra al cliente (el cliente lo aceptó), rol backend, sin mencionar equipo |
| D3 | Secciones de apoyo | Stack y Sobre mí. Especialización y Forma de trabajo dejan de mostrarse |
| D4 | Campos de proyecto | Nombre, resumen, tipo · rol · estado, problema → solución → resultado, stack, enlaces y varias capturas |
| D5 | Orden y navegación | Hero → Experiencia → Proyectos (clientes, luego personales) → Stack → Sobre mí → Contacto. Nav: Experiencia · Proyectos · Stack · Sobre mí · Contacto |
| D6 | Experiencia | Empresa, roles con fechas y casos vinculados |
| D7 | Modelo de proyectos | Extender `Project` con tipo; capturas en tabla ordenada |
| D8 | Sobre mí | Estructurado: formación e idiomas como listas; ubicación y modalidades en Profile; frase desde el principio de trabajo |
| D9 | Escalabilidad | Toda lista (experiencias, casos, proyectos, capturas, formación, idiomas, tecnologías) admite N ítems desde Filament |
| D10 | Vínculo caso–experiencia | Opcional; un caso sin experiencia pública va a un bloque general |
| D11 | Capturas | Máximo 12 por proyecto |
| D12 | Paleta | Terracota (claro y oscuro) |
| D13 | Tipografía | Fraunces (títulos), Instrument Sans (texto), IBM Plex Mono (etiquetas) |
| D14 | Video | Ilustración IA "línea de tinta que se dibuja": árbol que se vuelve red de nodos, de día a noche |
| D15 | Fondo de la escena | Paisaje esbozado en la mitad clara → constelación al final, con el color y destello de los nodos, más liviana |
| D16 | Transición del video | Corrección de contraste y saturación en el tramo de mezcla |
| D17 | Textos de la escena | Columna izquierda en los tres momentos |
| D18 | Empalme escena → secciones | "Amanecer" según tema (oscuro continuo; claro con degradé espresso → crema) |
| D19 | Proyectos | Dossier completo con galería; "Ver más proyectos" a partir del cuarto por grupo |
| D20 | Experiencia | Línea de tiempo con nodos y casos desplegables |
| D21 | Stack / Sobre mí / Contacto | Tres secciones separadas con aire; Contacto muestra correo, LinkedIn, GitHub y CV (sin título editable) |
| D22 | Modo oscuro | Todas las secciones, paleta Terracota oscura (aprobada) |
| D23 | Navegación | Transparente sobre la escena, sólida en las secciones; idioma, tema y CV a la derecha; menú a pantalla completa en móvil |
| D24 | Fuentes de contenido | Coned: LinkedIn y CV. ReservaHub: README y CV. Sobre mí: CV |
| D25 | Referencias externas | Ningún archivo del repositorio nombra el sitio usado como referencia ni a su autor |
| D26 | Foto profesional | En el hero (beat 1) y, al salir de la escena, como avatar junto al nombre en la navegación |
| D27 | Datos de Coned | Organización "Coned"; roles "Backend Engineer" (sept 2025 – actualidad) y "Pasante" (may 2025 – sept 2025), con nombres y fechas de LinkedIn |

## 3. Mapa de la página

Rutas: `/` y `/es` en español, `/en` en inglés. Página única por idioma.

| Orden | Ancla | Sección (ES / EN) | Contenido | Fuente |
|---|---|---|---|---|
| — | `#top` | Hero (escena) | Foto, headline, nombre, frase en 3 partes, cierre y CTA de correo | Profile, Site, Technologies |
| 01 | `#experience` | Experiencia laboral / Work experience | Organizaciones → roles → casos vinculados; casos sin vínculo al final | Experience, WorkCase |
| 02 | `#projects` | Proyectos / Projects | `#client-projects` (Para clientes) y luego `#personal-projects` (Personales) | Project, ProjectImage |
| 03 | `#stack` | Stack | Tecnologías por categoría | Technology, Site |
| 04 | `#about` | Sobre mí / About me | Frase, formación, idiomas, ubicación y modalidades | Profile, WorkPrinciple, EducationEntry, Language |
| 05 | `#contact` | Contacto / Contact | Introducción, correo (dirección visible), LinkedIn, GitHub y CV | Site, ProfessionalLink, CvDocument |

Reglas:

- Un grupo o sección sin ítems publicados no se muestra y su enlace no aparece en la navegación. Si ambos grupos de proyectos están vacíos, `#projects` muestra `site.projects_empty_message`.
- Expertise Areas y Work Principles no se borran: siguen en el CMS y en la API. El front solo usa el primer Work Principle publicado como frase de Sobre mí.
- El copy profesional nunca vive en el código del front; solo textos de interfaz.

## 4. Modelo de contenido y Filament

Principio (D9): cada colección se crea, edita, ordena (`position`), publica y oculta desde Filament, con los mismos estados `draft`/`published` y `is_visible` que el resto del CMS.

### 4.1 Project (extendido)

| Campo | Tipo | Borrador | Publicación |
|---|---|---|---|
| `kind` | enum `client` \| `personal` | obligatorio (default `personal`) | obligatorio |
| `client_name` | string(255) nullable | libre | obligatorio si `kind = client` |
| `role_es` / `role_en` | string(255) nullable | libre | par obligatorio |
| `status` | enum `in_production` \| `in_use` \| `public_demo` \| `in_development`, nullable | libre | obligatorio |
| `result_es` / `result_en` | text nullable | libre | par obligatorio |
| existentes | `title`, `summary`, `problem`, `solution`, `demo_url`, `repository_url`, `featured`, tecnologías | sin cambios | sin cambios |

- Restricción de base de datos: si `kind = personal`, `client_name` debe ser `NULL`.
- Se eliminan `image_private_path`, `image_public_path`, `image_mime`, `image_size`, `image_alt_es`, `image_alt_en`; su contenido migra a `project_images` como posición 0 (migración de datos incluida y testeada). No hay datos productivos: el cambio es seguro.

### 4.2 ProjectImage (nuevo)

`project_images`: `id`, `project_id` (FK, cascade), `position`, `private_path`, `public_path` nullable, `mime`, `size`, `alt_es`, `alt_en` (500), timestamps.

- Máximo **12** por proyecto (validación de dominio y `maxItems` en Filament).
- JPEG, PNG o WebP, hasta 8 MiB. Alt ES/EN obligatorio para publicar el proyecto.
- Ciclo de vida igual al de los assets actuales: original privado siempre; copia pública solo mientras el proyecto está publicado y visible; reemplazo y borrado con compensación.
- En Filament: repeater ordenable dentro del formulario de Project (archivo + alt ES + alt EN). Al guardar, el alt se persiste antes de reemplazar el archivo (mismo criterio que el fix `5766a88`).

### 4.3 WorkCase (vínculo)

- Nuevo `experience_id` nullable (FK, `nullOnDelete`).
- Filament: selector "Experiencia" con etiqueta `organización · rol · fechas`.
- Borrar una experiencia deja sus casos sin vínculo; despublicarla hace que la API los exponga con `experience_key = null`.

### 4.4 Experience

Sin cambios de esquema. El orden lo define `position`. El front agrupa por `organization` en orden de aparición.

### 4.5 EducationEntry (nuevo)

Campos con clave estable y posición: `institution` (string), `program_es`/`program_en` (par obligatorio), `detail_es`/`detail_en` (par opcional), `start_year` nullable, `end_year` nullable (`end_year >= start_year` si ambos existen). Recurso Filament propio.

### 4.6 Language (nuevo)

Campos con clave estable y posición: `name_es`/`name_en` (par obligatorio), `level` enum `native` \| `a1` \| `a2` \| `b1` \| `b2` \| `c1` \| `c2` (obligatorio). Recurso Filament propio.

### 4.7 Profile

- `location` string(255) nullable (no se traduce); `work_modes` JSON con valores `on_site`, `hybrid`, `remote` (opcional). Ambos editables en "Profile".
- La foto sigue siendo la de Profile (ya existente); no hay campos nuevos para ella.
- SiteConfiguration no cambia.
- El importador inicial agrega `location` y `work_modes` a su lista de columnas pristine de Profile.

### 4.8 Caché pública

Registrar en el mapa de dependencias de `PublicContentCache`:

| Cambio en | Invalida |
|---|---|
| Project, ProjectImage | `projects` |
| WorkCase | `work-cases` |
| Experience | `experiences`, `work-cases` (por `experience_key`) |
| EducationEntry, Language, SiteConfiguration | `site` |
| Profile | `profile` |

## 5. Contrato de la API pública v1

Se actualiza `docs/api/PUBLIC_API_V1.md` y el test de documentación.

| Endpoint | Cambio |
|---|---|
| `GET /projects` | Suma `kind`, `client_name` (`null` si personal), `role`, `status`, `result`, `images: [{url, alt}]` (solo copias públicas verificadas, en orden). Quita `image` |
| `GET /work-cases` | Suma `experience_key: string \| null` (solo si la experiencia vinculada es pública) |
| `GET /profile` | Suma `location: string \| null`, `work_modes: string[]` (orden `on_site`, `hybrid`, `remote`) |
| `GET /site` | Suma `education: [{key, institution, program, detail, start_year, end_year}]` y `languages: [{key, name, level}]` |
| `GET /experiences`, `GET /technologies` | Sin cambios |

Colecciones vacías devuelven `[]`. Campos opcionales siempre presentes con `null`.

## 6. Arquitectura del front

### 6.1 Datos

- `loadPublicContent(locale)` pide en paralelo `profile`, `site`, `technologies`, `experiences`, `work-cases` y `projects`. Cada cuerpo se valida como `unknown`.
- Criticidad: `profile` y `site` son estructurales (sin ellos: error general con Reintentar). Los otros cuatro son regionales: su sección muestra un error propio con Reintentar y el resto de la página sigue.
- Funciones puras y testeadas:
  - `groupExperience(experiences, workCases)` → organizaciones → roles → casos; casos sin experiencia pública en `unlinkedCases`.
  - `groupProjects(projects)` → `client` y `personal`, conservando el orden de la API.
  - `buildScene(...)` → contenido de la escena (existente, extendido).

### 6.2 Componentes

`ScrollScene` (video + canvas + fondo + textos), `SceneBackdrop` (SVG de paisaje y constelación), `SiteNav` (barra y menú móvil), `ThemeToggle`, `LanguageSwitch`, secciones `ExperienceSection`, `ProjectsSection`, `ProjectDossier`, `ProjectGallery`, `StackSection`, `AboutSection`, `ContactSection`, y `Accordion`. Cada componente con una responsabilidad.

### 6.3 Idioma, tema y textos de interfaz

- Idioma por ruta (`/en` → inglés). El cambio de idioma enlaza a la otra ruta conservando el ancla actual.
- Tema `light` / `dark`: preferencia guardada en `localStorage`; si no existe, `prefers-color-scheme`. Un script en `index.html` fija `data-theme` antes del primer pintado para evitar parpadeo.
- `content.ts` contiene solo textos de interfaz ES/EN: títulos de sección, etiquetas de tipo ("Para cliente" / "Personal"), estados ("En producción", "En uso", "Demo pública", "En desarrollo"), modalidades ("Presencial", "Híbrido", "Remoto"), niveles ("Nativo", "A1"…"C2"), controles y accesibilidad.

### 6.4 Fuentes

Autoalojadas con paquetes `@fontsource` (Fraunces variable, Instrument Sans, IBM Plex Mono), subconjunto latino y `font-display: swap`. Se evita depender de un servicio externo y el salto de layout.

## 7. Escena de scroll

### 7.1 Video

| Aspecto | Valor |
|---|---|
| Archivo | `web/public/media/scroll/ink-tree-network-v1.mp4` (se reemplaza la URL externa) |
| Origen | Wan 2.2 First-Last Frame, 81 cuadros a 16 fps, 848×480, 5,06 s, sin audio |
| Póster | `ink-tree-network-v1-poster.webp` (cuadro 0), usado mientras carga y sin JavaScript |
| Versionado | Un video nuevo se publica con sufijo `-v2`, nunca pisando el anterior |
| Recodificación | H.264 todo intra (cada cuadro clave) para que el fallback con `currentTime` salte sin trabas; herramienta ffmpeg ejecutada en contenedor Docker de uso puntual |
| Fuentes | Cuadros y prompts en `docs/design/phase-6/scroll-video/` (fuera de `public/`) |

### 7.2 Render

- Se conserva `useVideoScrub` (lerp, `LERP_TAU`, `SNAP`, banco de frames, LRU, watchdog, fallback).
- El canvas usa las dimensiones nativas del video; `object-fit: cover` lo escala.
- **Mezcla entre cuadros:** con el banco de frames activo se dibujan los dos cuadros vecinos con opacidad proporcional a la posición fraccional, para que 81 cuadros se sientan continuos.
- **Corrección de la transición (D16):** entre progreso 0,55 y 0,75 (cuadros ≈44–60) se aplica un filtro `contrast`/`saturate`/`brightness` sobre la capa del video, con envolvente triangular: 1 → pico en 0,65 (`contrast(1.35) saturate(1.25) brightness(.94)`) → 1.

### 7.3 Tiempos

Progreso `p` de 0 a 1 sobre la pista de 500vh.

| Tramo | Video | Fondo (D15) | Texto (D17) | Barra |
|---|---|---|---|---|
| 0 – 0,28 | Papel claro, el árbol crece | Paisaje: colinas, nubes y sol | Beat 1 | Texto oscuro |
| 0,32 – 0,63 | Papel todavía claro; empieza a oscurecer desde 0,50 | 0,50–0,75: paisaje se desvanece, sol baja y se apaga, aparecen estrellas | Beat 2 | Texto oscuro |
| 0,67 – 1 | Oscurece hasta la noche con nodos iluminados | 0,75–1: constelación completa; líneas punteadas 0,75–0,90 | Beat 3 | Cambia a claro en 0,70 |

Las curvas de opacidad de los beats y el stagger se conservan (mismas formas y tramos). Lo que se ajusta al video nuevo son los colores y el umbral de la barra, a partir del contraste medido cuadro por cuadro sobre la zona de texto y la franja de la barra:

- El texto oscuro `#2D251B` pasa AA hasta el cuadro 56 (p 0,70).
- El texto claro `#F3E9DD` pasa AA desde el cuadro 62 (p 0,775).
- Entre p 0,70 y 0,78 ninguno de los dos pasa por sí solo.

Por eso se agregan dos **velos** oscuros suaves (`#1A1411`), medidos con la corrección de 7.2 aplicada:

| Velo | Opacidad | Resultado medido |
|---|---|---|
| Detrás de la columna de texto | 0 → 45 % entre p 0,66 y 0,70; 45 % hasta 0,78; 45 % → 0 entre 0,78 y 0,84 | El texto claro del beat 3, completamente visible desde p 0,75, nunca baja de 7,2:1. No afecta al beat 2, que ya desapareció en 0,63 |
| Franja superior de la barra | No existe antes del cambio de color. Aparece en p 0,70 junto con el cambio (fundido de 150 ms), 45 % hasta 0,78 y 45 % → 0 entre 0,78 y 0,84 | Barra oscura sin velo hasta p 0,70: mínimo 5,0:1. Barra clara con velo desde p 0,70: mínimo 5,6:1 |

`NAV_LIGHT_THRESHOLD` pasa de 0,55 a 0,70.

### 7.4 Fondo (SVG sobre el video, bajo el texto)

- **Paisaje:** trazos `#665244` al 24 % (colinas, nubes), sol en contorno `#9A4E2A` al 22 %. Desplazamiento vertical leve con el scroll.
- **Constelación:** estrellas con núcleo `#E38B50` al 75 % y halo difuminado `#E38B50` al 32 % (mismo tono medido en los nodos del video); líneas punteadas `#E38B50` al 20 %. Como máximo el 20 % de las estrellas en el tercio izquierdo.
- La escena es igual en ambos temas (cuenta el día y la noche).

### 7.5 Textos de la escena (columna izquierda)

- Desktop (≥1024px): columna en el tercio izquierdo, ancho máximo ≈40 %, centrada verticalmente.
- Móvil: ancho completo con márgenes laterales, el video encuadrado hacia el árbol (`object-position` ≈70 %).

| Beat | Contenido | Color |
|---|---|---|
| 1 | Foto de Profile (circular, 128px en desktop y 96px en móvil, borde de 1px `#9A4E2A`) + etiqueta con el nombre (Plex Mono) + H1 headline (Fraunces). Si no hay foto publicada, se omite | Texto `#2D251B`, acento `#9A4E2A` |
| 2 | Frase en 3 partes (`statement` o `short_summary`) | Inicio `#2D251B`; énfasis `#9A4E2A` en cursiva de Fraunces; cierre `#665244`. Es texto grande (≥24px), por lo que el mínimo AA es 3:1 y se cumple hasta p 0,63 |
| 3 | Etiqueta con tecnologías backend + H2 cierre (`closing` o `availability`) + CTA de correo | Texto `#F3E9DD`, acento `#E08E5E`, sobre el velo de 7.3 mientras dura |

Las etiquetas pequeñas (Plex Mono) de los beats 1 y 3 usan el color de texto principal del beat, no el acento, cuando el acento no llegue a 4,5:1 sobre el cuadro.

Todo el copy de la escena también existe en un bloque solo para lectores de pantalla, porque los beats ocultos quedan `inert`.

### 7.6 Movimiento reducido

Sin banco de frames ni lerp (comportamiento actual del hook), sin desplazamiento del fondo y sin transiciones de stagger. Los cambios de opacidad por progreso se mantienen porque dependen del scroll, no del tiempo.

## 8. Sistema visual

### 8.1 Paleta Terracota

| Token | Claro | Oscuro |
|---|---|---|
| `--color-canvas` | `#F5EFE6` | `#1A1411` |
| `--color-surface` | `#EADFD1` | `#281F1A` |
| `--color-text` | `#2D251B` | `#F3E9DD` |
| `--color-text-muted` | `#665244` | `#C7B3A1` |
| `--color-accent` | `#9A4E2A` | `#E08E5E` |
| `--color-border` | `#86705E` | `#8C7462` |
| `--color-node` | `#9A4E2A` | `#E38B50` (con halo) |

Contraste verificado (claro / oscuro):

| Par | Claro | Oscuro |
|---|---|---|
| Texto sobre fondo | 13,2:1 | 15,2:1 |
| Secundario sobre fondo | 6,4:1 | 9,0:1 |
| Secundario sobre superficie | 5,6:1 | 8,0:1 |
| Acento sobre fondo | 5,3:1 | 7,1:1 |
| Acento sobre superficie | 4,6:1 | 6,3:1 |
| Borde sobre fondo | 4,1:1 | 4,2:1 |
| Borde sobre superficie | 3,6:1 | 3,7:1 |

Los bordes se oscurecieron/aclararon respecto de las maquetas aprobadas (`#9E8672` / `#7D6553`) porque sobre las superficies no llegaban a 3:1. Tailwind consume estos tokens como variables CSS con `[data-theme="dark"]`.

### 8.2 Tipografía

| Rol | Fuente | Uso |
|---|---|---|
| Display | Fraunces 300 (cursiva para énfasis) | H1, H2, nombres de organización y proyecto, frase de Sobre mí, título de contacto |
| Texto | Instrument Sans 400/600 | Párrafos, navegación, botones |
| Etiquetas | IBM Plex Mono 400/500, mayúsculas con tracking | Índices "0N · Sección", meta de proyecto, fechas, categorías |

Se reutilizan de Fase 2 la escala de espaciado (base 4px), el anillo de foco (3px acento, offset 2px), el objetivo táctil mínimo de 44×44px y el ancho máximo de contenido (90rem).

### 8.3 Empalme escena → secciones (D18)

- Oscuro: degradé de 32px de `#1D140F` a `#1A1411`, sin corte visible.
- Claro: degradé de ≈24vh `#1D140F` → `#5A3F2E` → `#C9AE93` → `#F5EFE6`.

### 8.4 Secciones

Todas con encabezado "0N · Sección" (Plex Mono, acento) y H2 en Fraunces.

- **01 Experiencia (D20):** línea vertical de 1px color nodo con un nodo por rol (en oscuro con el halo del árbol). Organización en Fraunces; por rol: fechas (Mono), título, resumen y logros. Debajo, casos como acordeón: botón con título y `+`/`−`; al abrir, grilla de 2 columnas (1 en móvil) con Problema, Aporte, Enfoque técnico, Resultado y chips de tecnologías. Se pueden abrir varios. Casos sin vínculo al final, bajo "Otros casos".
- **02 Proyectos (D19):** subsección "Para clientes" y luego "Personales". Cada proyecto es un dossier de dos columnas:
  - Texto a la izquierda: meta (`Para cliente · Cliente · Rol · Estado`), título, resumen, Problema / Solución / Resultado, chips de stack, enlaces a demo y repositorio.
  - Galería a la derecha: captura principal 16:10 y miniaturas (hasta 4 visibles y `+N`). Las miniaturas son botones; "Ver en tamaño completo" abre un `<dialog>` con anterior/siguiente y cierre con Escape.
  - En móvil: texto arriba, galería abajo con miniaturas en carrusel horizontal.
  - Los primeros 3 proyectos por grupo se muestran; el resto aparece con "Ver más proyectos" en el mismo lugar.
- **03 Stack (D21):** 4 columnas en el orden `backend`, `data`, `integration`, `collaboration` con las etiquetas de `site.technology_groups`; 2 columnas en móvil. Solo nombres.
- **04 Sobre mí (D21):** columna izquierda con la frase en Fraunces; columna derecha con Formación, Idiomas y Ubicación · Modalidades. Sin foto (D26).
- **05 Contacto (D21):** introducción (`contact_intro`) y lista de enlaces: correo con la dirección visible (tomada del enlace `mailto:`) →, LinkedIn ↗ y GitHub ↗; debajo, botón de CV en contorno si hay CV publicado.

### 8.5 Navegación (D23)

- Contenido: nombre (enlace a `#top`), Experiencia, Proyectos, Stack, Sobre mí, Contacto; a la derecha ES/EN, cambio de tema y CV (si está publicado).
- Sobre la escena: transparente; color del texto según el tramo (7.3).
- En las secciones: barra sólida `--color-canvas` con línea inferior `--color-border`. La sección activa (detectada con `IntersectionObserver`) muestra su índice "0N" y subrayado de 2px en acento.
- Avatar (D26): al salir de la escena aparece, con un fundido corto, la foto de Profile como avatar circular de 28px a la izquierda del nombre, también en la barra móvil. Es decorativo (`alt=""`) porque el nombre está al lado. Sobre la escena no se muestra, porque la foto ya está en el beat 1. Si no hay foto publicada, se omite.
- Menos de 1024px: nombre + "Menú". Al abrir, índice a pantalla completa con los destinos numerados y, abajo, idioma, tema y CV. Foco atrapado, cierre con Escape, foco devuelto al botón y scroll bloqueado.
- Enlace "Saltar al contenido" como primer elemento enfocable.

## 9. Contenido para cargar (borrador en Filament, revisión humana antes de publicar)

| Contenido | Fuente | Estado |
|---|---|---|
| Experiencias Coned: "Backend Engineer" (sept 2025 – actualidad) y "Pasante" (may 2025 – sept 2025), con resúmenes y logros | Organización, roles y fechas: LinkedIn (D27). Resúmenes y logros: CV | Listo |
| Vínculo de los 4 casos con el rol actual | Casos aprobados existentes | Listo |
| ReservaHub: tipo personal, rol full stack, estado demo pública, P/S/R, stack, demo y repositorio | README del repositorio y CV | Borrador para revisión |
| Trucks and Drinks: tipo cliente, rol backend, estado en uso | Decisión D2 | Faltan textos de P/S/R y capturas (los aporta Luciano) |
| Formación: Bachiller en Economía y Administración (Instituto Argentino Modelo, 2021); Curso de Programación (CFP N°401, 2022, introducción a C, fundamentos de Python y MySQL) | CV | Listo |
| Idiomas: Español nativo; Inglés B2 | CV | Listo |
| Ubicación: Mar del Plata; modalidades presencial, híbrido, remoto | CV y LinkedIn | Listo |
| Tecnologías adicionales del "Stack técnico" del CV | CV | Borrador para revisión |

La carga se hace con las acciones de dominio (el mismo camino que Filament), no con SQL directo.

## 10. Confirmaciones editoriales (resueltas el 2026-09-14)

| Tema | Conflicto entre fuentes | Decisión |
|---|---|---|
| Fin de la pasantía | CV: julio 2025; LinkedIn: septiembre 2025 | Septiembre 2025 (LinkedIn) |
| Rol actual | CV: "Desarrollador Backend"; LinkedIn: "Backend Engineer" | "Backend Engineer" |
| Organización | CV: "CONED"; LinkedIn: "Coned Virtual" | "Coned" |
| Foto | No estaba definida | Hero y avatar en la navegación (D26) |
| Contacto | Se había propuesto un título editable | Sin título: correo, LinkedIn, GitHub y CV (D21) |

Sigue pendiente solo el contenido que aporta Luciano: textos de problema, solución y resultado de Trucks and Drinks y sus capturas (sección 9).

## 11. Accesibilidad y rendimiento

- Jerarquía: H1 en la escena; H2 por sección; H3 organización/proyecto; H4 rol/caso.
- Acordeones y miniaturas son botones con `aria-expanded` / `aria-current`; el visor es un `<dialog>` nativo.
- La foto del hero usa el alt de Profile; el avatar de la barra es decorativo.
- Contraste AA en ambos temas (8.1) y sobre los cuadros de la escena.
- Nada depende solo del hover; todo funciona con teclado y a 200 % de zoom.
- Imágenes con `loading="lazy"`, `decoding="async"` y dimensiones explícitas. Video de 246 KB.
- El fondo SVG y la corrección de filtro solo trabajan mientras la escena está en pantalla.

## 12. Verificación

- **API:** campos y validaciones nuevas; restricción `kind`/`client_name`; ciclo de vida de `project_images` (copia pública, orden, tope 12, alt obligatorio); migración de la imagen existente; `experience_key` con experiencia pública, oculta y borrada; formación e idiomas; invalidación de caché; test de documentación.
- **Filament:** crear varios proyectos de ambos tipos; agregar, reordenar y quitar capturas; rechazo de la número 13; cliente obligatorio solo en tipo cliente; selector de experiencia en casos; recursos de Formación e Idiomas; campos nuevos de Profile.
- **Front (unitarios):** validadores de los 6 recursos; `groupExperience` con varias organizaciones, casos sin vínculo y experiencias ocultas; `groupProjects` con grupos vacíos; resolución de tema e idioma; mezcla de cuadros y envolvente de corrección como funciones puras.
- **Navegador (QA manual con ventana visible):** foto en el hero y avatar en la barra al salir de la escena, scrub y mezcla del video, corrección en la transición, paso paisaje → constelación, textos a la izquierda, cambio de color de la barra, empalme en ambos temas, acordeones, galería y visor, "Ver más proyectos", menú móvil, teclado, movimiento reducido, 200 % de zoom, anchos de 375px y 1440px.
- **Repositorio:** typecheck, tests y build del front; suite de la API; Pint; validador del repositorio.

## 13. Documentación a actualizar

`docs/ARCHITECTURE.md` (front y escena), `docs/api/PUBLIC_API_V1.md`, `ROADMAP.md` (Fase 6), `docs/content/ASSET_INVENTORY.md` (video, póster y fuentes), `README.md` (carpetas de medios). Un nuevo `docs/design/phase-6/DESIGN_TOKENS.md` con los tokens de la sección 8.

## 14. Fuera de alcance

SEO, metadata y Open Graph (Fase 8); despliegue y runtime productivo (Fase 11); regenerar el video; nuevos proyectos más allá de los listados.
