# Errores encontrados

## BUG-001: error al crear cuenta

- **Fecha:** 2026-10-23
- **Función afectada:** Crear cuenta
- **Severidad:** Crítica
- **Pasos para reproducir:**
  1. entrar a crear cuenta
  2. rellenar los datos del formulario
  3. al hacer click en crear cuenta se produce dicho error

- **Resultado esperado:** Se crea la cuenta de manera correcta
- **Resultado obtenido:** la cuenta no se creo y manda error critico
- **Estado:** CERRADO
- **Evidencia:** evidencias/error login.png

**Revisión:** la creación de cuentas usa validaciones de entrada y persistencia transaccional. La suite automatizada cubre creación, autenticación y aprobación de solicitudes; el error crítico original no se volvió a reproducir manualmente en navegador.


---
## BUG-001.1: error al crear cuenta

- **Fecha:** 2026-10-23
- **Función afectada:** Crear cuenta
- **Severidad:** Crítica
- **Pasos para reproducir:**
  1. entrar a crear cuenta
  2. rellenar los datos del formulario con un solo nombre y sin apellido
  3. al hacer click en crear cuenta se crea correctamente
  4. entrar como administrador y tratar de aceptar salta el error

- **Resultado esperado vigente:** el registro nuevo debe incluir nombre y apellido; solicitudes antiguas con un solo nombre deben poder corregirse antes de aprobarse.
- **Resultado obtenido originalmente:** la solicitud con nombre incompleto producía un error crítico al aprobarse.
- **Estado:** CERRADO
- **Evidencia:**evidencias/errorrapellido.png

**Corrección verificada:** el registro nuevo exige nombre y apellido. Para solicitudes antiguas, administración puede corregir el nombre antes de aprobar; si la validación falla, la transacción se revierte y la solicitud permanece pendiente. La suite comprueba rechazo y aprobación tras corregirlo.


---

## BUG-002: error en los accesos de  los usuarios

- **Fecha:** 2026-11-06
- **Función afectada:** Cambio de rol
- **Severidad:** Leve
- **Pasos para reproducir:**
  1. Crear cuenta de estudiannte
  2. Entrar como Administrador activar su cuenta 
  3. Cambiar su rol a administrador

- **Resultado esperado:** La cuenta deja su rol anterior para tener un rol completamente nuevo
- **Resultado obtenido:** El sistema marca la existenncia de un alumno aun cuando ya no hay al ser Administrador provocando el conteo de estudiantes fallido un estudiante no debe ser administrador
- **Estado:** CERRADO

**Corrección verificada:** el contador considera las cuentas activas cuyo rol vigente es estudiante. El expediente se conserva al cambiar el rol.

---

## BUG-003: Validacion fallida en las fechas

- **Fecha:** 2026-11-06
- **Función afectada:** Fechas de proyectos y actividades
- **Severidad:** ALTA
- **Pasos para reproducir:**
  1. Como administrador crear un proyecto con una fecha anterior a la actual
  2. Ingresar una fecha anterior a la  actual para iniciar el proyecto y una anterior a la actual para finalizarlo
  3. Ir a cronograma para crear una nueva  actividad
  4. En la nueva actividad seleccionar una fecha antes de la iniciacion de proyecto y despues de su finalizacion

- **Resultado esperado vigente:** se permiten proyectos históricos; cada actividad debe quedar dentro del periodo del proyecto y la fecha reportada debe pertenecer al periodo de la actividad.
- **Resultado obtenido originalmente:** se podían registrar actividades o reportar horas fuera de sus periodos relacionados.
- **Estado:** CERRADO

**Corrección verificada:** las fechas históricas de proyectos están permitidas por decisión funcional; los límites entre proyecto, actividad y registro de horas sí se validan y tienen regresiones automatizadas.

---
## BUG-003.1: Validacion fallida en las fechas

- **Fecha:** 2026-11-06
- **Función afectada:** Fechas de proyectos y actividades
- **Severidad:** ALTA
- **Pasos para reproducir:**
  1. Como administrador crear un proyecto con una fecha anterior a la actual
  2. Ingresar una fecha anterior a la  actual para iniciar el proyecto y una anterior a la actual para finalizarlo
  3. Ir a cronograma para crear una nueva  actividad
  4. En la nueva actividad seleccionar una fecha antes de la iniciacion de proyecto y antes de su finalizacion
  5. si hay otro proyecto programado para esa fecha marca error lo que deberia ser solo un aviso haciendo imposible tener mas de 1 proyecto funcionando a la vez

- **Resultado esperado vigente:** advertir sobre cruces del mismo responsable o recurso y pedir confirmación para continuar. Actividades con responsables y recursos distintos pueden coexistir.
- **Resultado obtenido originalmente:** los cruces relevantes se rechazaban sin permitir confirmación.
- **Estado:** CERRADO

**Corrección verificada:** los cruces de responsable/recurso se advierten y requieren confirmación explícita para continuar; si no se indica un recurso separado, se usa el lugar para detectar la coincidencia. La suite cubre cruces por responsable y por lugar, rechazo sin confirmación y guardado confirmado. No se aplica un bloqueo general a cualquier actividad coincidente en fecha.

---
## BUG-004: error en las horas reflejadas

- **Fecha:** 2026-11-06
- **Función afectada:** Resumen de horas
- **Severidad:** MEDIA
- **Pasos para reproducir:**
  1. agregar mas de 2 registros a una actividad de unn proyecto
  2. Como administrador rechazar y aceptar
  3. regresar como estudiante y solo se muestran dos y si se rechazan horas despues de haber aprobado 2 estas no se reflejan

- **Resultado esperado vigente:** mostrar todos los registros y sumar las horas por estado; el límite diario es de 8 horas por estudiante, sin limitar por las horas estimadas de la actividad.
- **Resultado obtenido originalmente:** se informaba que solo se reflejaban dos registros y que las horas aprobadas por encima de lo estimado no se reflejaban.
- **Estado:** CERRADO
- **Evidencia:**evidencias/errorHoras.png
**Verificación automatizada:** el expediente muestra todos los registros (la regresión incluye más de dos); las horas aprobadas, pendientes y rechazadas se resumen por estado. Se verificó que se pueden aprobar horas por encima de lo estimado y que el límite acumulado diario es de 8 horas por estudiante; las horas rechazadas no consumen ese límite.

---

