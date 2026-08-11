# DEPLOYMENT.md — Portfolio en servidor Linux propio

## Objetivo

Desplegar el portfolio como una aplicación Docker independiente dentro del servidor Linux multiproyecto de Luciano.

Arquitectura global:

`docs/SERVER_ARCHITECTURE.md`

---

## Asignación del portfolio

Directorio recomendado:

```text
/srv/apps/portfolio
```

Puerto reservado:

```text
127.0.0.1:8000
```

Hostname:

```text
lucianogonzalez.dev
```

Mapeo de Cloudflare Tunnel:

```text
lucianogonzalez.dev
    -> http://localhost:8000
```

---

## Servicios de producción

Stack conceptual:

```text
portfolio-gateway
portfolio-web
portfolio-api
portfolio-mysql
```

`cloudflared` no pertenece al Compose del portfolio.

Se ejecuta una única vez como servicio del servidor Linux.

---

## Red

Único puerto publicado por el proyecto:

```text
127.0.0.1:8000
```

No publicar:

- MySQL.
- Puerto interno de Laravel.
- Puerto interno de Next.js.
- Redis si se agrega.
- Scheduler/queue.

El gateway recibe el tráfico del host y lo distribuye internamente.

---

## Rutas

Conceptualmente:

```text
/           -> Next.js
/api/*      -> Laravel
/admin/*    -> Laravel / Filament
```

Esto permite usar un mismo origen público para frontend y API.

---

## Cloudflare

La configuración de Cloudflare es responsabilidad de la infraestructura del servidor, no del repositorio del portfolio.

El portfolio solo necesita cumplir su contrato:

> estar saludable y disponible en `http://127.0.0.1:8000`.

No guardar token de Cloudflare dentro del proyecto.

---

## Variables de producción

Secretos mínimos esperados:

- Laravel `APP_KEY`.
- Credenciales MySQL.
- Credenciales administrativas iniciales.
- Otros secretos de aplicación.

No incluir:

- Token del tunnel.
- Credenciales de otros proyectos.
- Variables globales del host.

Usar un archivo/environment de producción no versionado.

---

## Datos persistentes

Persistir:

- MySQL.
- Media subida desde Filament.
- CV administrado si se almacena localmente.
- Otros archivos no regenerables.

Reemplazar contenedores no debe borrar contenido.

---

## Primera instalación

Flujo de alto nivel:

1. Preparar directorio `/srv/apps/portfolio`.
2. Clonar el repositorio.
3. Crear variables de producción.
4. Construir imágenes.
5. Iniciar base de datos.
6. Ejecutar migraciones.
7. Crear usuario administrador de forma segura.
8. Iniciar el stack.
9. Verificar `http://127.0.0.1:8000`.
10. Agregar/activar la ruta pública en Cloudflare Tunnel.
11. Verificar HTTPS desde Internet.
12. Probar `/api`.
13. Probar `/admin`.
14. Ejecutar backup inicial.

No automatizar pasos sensibles antes de comprobar manualmente el flujo completo.

---

## Actualización manual inicial

Antes de automatizar CI/CD, usar un procedimiento explícito y reproducible.

Conceptualmente:

1. Confirmar que CI está verde.
2. Conectarse al servidor de forma segura.
3. Entrar a `/srv/apps/portfolio`.
4. Obtener la versión aprobada.
5. Construir/actualizar los servicios.
6. Ejecutar migraciones.
7. Ejecutar smoke tests.
8. Verificar el dominio.
9. Conservar una ruta de rollback.

No ejecutar `docker compose down` sin necesidad.

Preferir actualización de servicios con la menor interrupción posible.

---

## CI

GitHub Actions valida el proyecto:

- Frontend lint.
- Type checking.
- Frontend tests.
- Frontend build.
- Laravel tests.
- Docker build/config validation.
- Security/dependency checks acordados.

CI no debe afirmar que desplegó la aplicación simplemente porque compiló React.

Para que un artefacto compilado en GitHub llegue a producción debe existir un mecanismo de transferencia/deploy definido explícitamente.

---

## Automatización de deploy futura

No exponer SSH directamente a Internet solo para permitir deployments.

Antes de automatizar, elegir deliberadamente una estrategia.

Opciones compatibles con la arquitectura:

### Opción A — Deploy controlado desde servidor

Un proceso seguro del servidor actualiza una versión aprobada.

### Opción B — Runner self-hosted restringido

Usar un runner únicamente desde un repositorio privado/controlado de infraestructura.

No adjuntar un runner con acceso al host a workflows arbitrarios de repositorios públicos.

### Opción C — Administración mediante Cloudflare Access

Usar una ruta administrativa protegida/tunnel para acceso remoto sin abrir SSH públicamente.

La decisión definitiva se registra cuando se implemente CI/CD.

---

## Backups

Backups del portfolio:

```text
/srv/backups/portfolio/
├── mysql/
└── media/
```

Requisitos:

- Backup periódico de MySQL.
- Backup de media.
- Retención.
- Copia en otro disco/equipo.
- Restore probado.
- Scripts versionados cuando no contengan secretos.

---

## Reinicio del host

Prueba obligatoria:

1. Reiniciar Linux.
2. Docker inicia.
3. `cloudflared` inicia.
4. Stack portfolio inicia.
5. MySQL queda healthy.
6. API queda healthy.
7. Web queda healthy.
8. Gateway queda healthy.
9. `http://127.0.0.1:8000` responde.
10. `https://lucianogonzalez.dev` responde.

No considerar producción estable hasta probar este escenario.

---

## Scheduler

Si Laravel necesita scheduler, agregar un mecanismo específico del portfolio.

Nunca programar:

```text
migrate:fresh
```

en producción del portfolio.

El CMS contiene información persistente.

---

## Logs

Definir:

- Logs de Laravel.
- Logs de gateway.
- Logs/errores de Next.js.
- Rotación.
- Límites de tamaño.

Los logs no deben contener secretos ni datos bancarios reales.

---

## Health checks

Definir health checks para:

- MySQL.
- Laravel.
- Next.js.
- Gateway.

El health check público no debe revelar información sensible.

---

## Rollback

Antes de automatizar producción documentar:

- Cómo volver al commit/tag anterior.
- Qué ocurre con migraciones incompatibles.
- Cómo restaurar backup si una migración no puede revertirse.
- Cómo comprobar el sistema luego del rollback.

---

## Definition of done para deployment

- [ ] El stack se levanta desde cero siguiendo documentación.
- [ ] Solo el gateway publica `127.0.0.1:8000`.
- [ ] MySQL no es accesible públicamente.
- [ ] Cloudflare Tunnel publica el dominio correctamente.
- [ ] No se abren 80/443 en el router para la app.
- [ ] Admin requiere autenticación.
- [ ] CI pasa.
- [ ] Backup funciona.
- [ ] Restore fue probado.
- [ ] Reinicio completo del servidor fue probado.
- [ ] Rollback está documentado.
