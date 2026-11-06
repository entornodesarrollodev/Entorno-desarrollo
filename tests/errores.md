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
- **Resultado obtenido:** la cuennta no se creo y manda error critico
- **Estado:** CORREGIDO
- **Evidencia:** evidencias/error login.pnng

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
- **Resultado obtenido:** El sistema marca la existenncia de un alumno aun cuando ya no hay al ser Administrador provocando el conteo de estudiantes fallido
- **Estado:** Pendiente  de revision

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

- **Resultado esperado:** Las fechas deberian ser bloqueadas para evitar incongruencias o fraudes
- **Resultado obtenido:** El sistema no valida las fechas correctamente
- **Estado:** Pendiente  de revision
**OBSERVACION IMPORTANTE**: La fechas tambien estan mal validadas al registrar las horas como estudiante

---

## BUG-004: error en las horas reflejadas

- **Fecha:** 2026-11-06
- **Función afectada:** Resumen de horas
- **Severidad:** MEDIA
- **Pasos para reproducir:**
  1. agregar mas de 2 registros a una actividad de unn proyecto
  2. Como administrador rechazar y aceptar
  3. regresar como estudiante y solo se muestran dos y si se rechazan horas despues de haber aprobado 2 estas no se reflejan

- **Resultado esperado:** Las horas se reflejan correctamente segun son aceptadas o rechazadas
- **Resultado obtenido:** El sistema tiene un limite por proyecto y actividad para aprobar las horas
- **Estado:** Pendiente  de revision

---

