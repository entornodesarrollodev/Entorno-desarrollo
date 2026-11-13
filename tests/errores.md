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

- **Resultado esperado:** Se crea la cuenta de manera correcta
- **Resultado obtenido:** la cuenta se creo pero al aceptarla manda error critico
- **Estado:** REABIERTO
- **Evidencia:**evidencias/errorrapellido.png


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
- **Estado:** Aun sin solucion

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
- **Estado:** Aun sin solucion
**OBSERVACION IMPORTANTE**: La fechas tambien estan mal validadas al registrar las horas como estudiante

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

- **Resultado esperado:** Las fechas deberian ser bloqueadas para evitar incongruencias o fraudes y deberia mostrar una advertencia avisando que ya  hay actividades para ese periodo de tiempo pero dejarlo en decicion del usuario si desea hacerlo o no
- **Resultado obtenido:** El sistema no valida las fechas correctamente y muestra un error que inpide que dos actividades coexistan
- **Estado:** Aun sin solucion
**OBSERVACION IMPORTANTE**: La fechas estan mal validadas al registrar las horas como estudiante

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
- **Resultado obtenido:** El sistema tiene un limite por proyecto y actividad para aprobar las horas al llegar al limite establecido al principio, aun asi permite agregar mas horas y haceptarlas pero sin reflejarlas
- **Estado:** Pendiente  de revision
- **Evidencia:**evidencias/errorHoras.png
**OBSERVACION IMPORTANTE**: las horas diarias no deben superar las 8 y el sistema permite un maximo de 12 y cuando se exede el total de horas de la actividad no lo refleja y de todas formas lo manda a revision

---

