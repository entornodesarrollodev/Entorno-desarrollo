# Informe de pruebas

**Proyecto:** Estudiantes en proyeccion sociasl 
**Responsable:** Mauricio
**Tecnología:** PHP  
**Rama:** main

## 1. Pruebas realizadas

### Prueba 001: Crear cuenta
- **Fecha:** 2026-10-23
- **Objetivo:** crear una cuenta nueva
- **Procedimiento:** meter toda la informacion que pide el formulario de manera correcta
- **Resultado esperado:** deberia crear la cuenta 
- **Resultado obtenido:** salto un error 
- **Estado:** FALLIDA
- **Evidencia:** evidencias/error login 



### Prueba 002 : paginas privadas

- **Fecha:** 2026-10-23
- **Objetivo:** Ingresar de manera manual un link hacia una pagina de usuario mientras su sesion esta cerradda
- **Procedimiento:**se intento ingresar en http://127.0.0.1:8000/roles.php mientras la sesion estaba cerrada 
- **Resultado esperado:** deberia redirigir al login
- **Resultado obtenido:** redirigio al login
- **Estado:** APROBADA

### Prueba 003 : Probar el inicio de sesión con datos válidos, campos vacíos, formato incorrecto y credenciales inválidas.


- **Fecha:** 2026-10-23
- **Objetivo:** probar el funcionamiento correcto de las validaciones del login
- **Procedimiento:**se probo con datos no validos, con campos vacios, con credenciales invalidas y con formato incorrecto
- **Resultado esperado:** No deberia ingresar al sistema bajo ninguna de las circustancias
- **Resultado obtenido:** no ingreso al sistema por la validacion correcta
- **Estado:** APROBADA

### Prueba 004 :Probar accesos permitidos y denegados para cada rol.

- **Fecha:** 2026-10-23
- **Objetivo:** Verificar permisos de usuarios por rol 
- **Procedimiento:**Cambiar los roles de los usuarios actuales para verificar como cambian los accesos que tiene cada uno
- **Resultado esperado:** que cada usuario tenga accesos especificos segun su rol
- **Resultado obtenido:** no data
- **Estado:** BLOQUEADA



### Prueba 005 : Probar que una cuenta desactivada no pueda iniciar sesión.

- **Fecha:** 2026-10-23
- **Objetivo:** Desactivar una cuenta para negarle el inicio de session
- **Procedimiento:**Desactivar una cuenta desde la cuenta de administrador y que esta misma no pueda  iniciar session
- **Resultado esperado:** No deberia poder iniciar session aunque los datos esten correctos por que su cuenta estaria  desactivada
- **Resultado obtenido:** no data
- **Estado:** BLOQUEADA



## 2. Resumen de resultados

- Pruebas ejecutadas: 5
- Pruebas aprobadas: 3
- Pruebas fallidas: 0
- Pruebas bloqueadas: 2

## 3. Observaciones

la prueba 004 y 003fue bloqueada por la incopatibilidad de la prueba al no poder crear cuentas nuevas para asignar roles

## 4. Segunda ronda
### Prueba 001.1: Crear cuenta
- **Fecha:** 2026-11-06
- **Objetivo:** crear una cuenta nueva
- **Procedimiento:** meter toda la informacion que pide el formulario de manera correcta
- **Resultado esperado:** deberia crear la cuenta 
- **Resultado obtenido:** La cuenta se creo correctamente
- **Estado Actual:** APROBADA

### Prueba 004.1 :Probar accesos permitidos y denegados para cada rol.

- **Fecha:** 2026-11-06
- **Objetivo:** Verificar permisos de usuarios por rol 
- **Procedimiento:**Cambiar los roles de los usuarios actuales para verificar como cambian los accesos que tiene cada uno
- **Resultado esperado:** que cada usuario tenga accesos especificos segun su rol
- **Resultado obtenido:** El acceso cambio correctamente pero seguia marcando un estudiante
- **Estado Actual:** FALLIDA

### Prueba 005.1 : Probar que una cuenta desactivada no pueda iniciar sesión.

- **Fecha:** 2026-10-23
- **Objetivo:** Desactivar una cuenta para negarle el inicio de session
- **Procedimiento:**Desactivar una cuenta desde la cuenta de administrador y que esta misma no pueda  iniciar session
- **Resultado esperado:** No deberia poder iniciar session aunque los datos esten correctos por que su cuenta estaria  desactivada
- **Resultado obtenido:** no inicia sesion si la cuenta esta desactivada
- **Estado Actual:** APROBADA

### Prueba 006 :Probar registro válido y fechas incorrectas.

- **Fecha:** 2026-11-06
- **Objetivo:** El sistema solo debe aceptar fechas validas
- **Procedimiento:**Ingresar fechas anteriores a la actual y pasandose del limite  del proyectio
- **Resultado esperado actualizado:** se permiten proyectos históricos; las actividades deben respetar las fechas del proyecto y los registros de horas, las fechas de la actividad.
- **Resultado obtenido originalmente:** se interpretó erróneamente que toda fecha pasada debía rechazarse.
- **Estado histórico:** FALLIDA bajo el criterio original; reevaluada y cubierta por la regresión automatizada de fechas.


### Prueba 007 :Probar filtros individuales, combinados y búsquedas sin resultados.

- **Fecha:** 2026-11-06
- **Objetivo:** Los filtros se realizan sin complicaciones
- **Procedimiento:**Filtrar de diferentes maneras y comprobar que las tareas aparezcan solo en su filtro
- **Resultado esperado:** Deberia filtrar correctamente
- **Resultado obtenido:** Se filtro correctamente
- **Estado:** APROBADA

### Prueba 008 :Probar los permisos de edición.

- **Fecha:** 2026-11-06
- **Objetivo:** Permisos de edicio correctos
- **Procedimiento:**Intercalar entre administrador y  estudiante probbanndo permisos
- **Resultado esperado:** Los permisos deberian funcionar correctamente
- **Resultado obtenido:** los permisos funcionan
- **Estado:** APROBADA

### Prueba 009 :Probar formatos de correo válidos e inválidos

- **Fecha:** 2026-11-06
- **Objetivo:** Formatos de correo validados
- **Procedimiento:**Insertar varios correos invalidos 
- **Resultado esperado:** Los correos no validos deberiann tener mensaje de alerta
- **Resultado obtenido:** Los correos invalidos no ingresan y mandan mensaje de alerta
- **Estado:** APROBADA


### Prueba 010 :Probar que solo horas aprobadas se sumen al total oficial

- **Fecha:** 2026-11-06
- **Objetivo:** Horas unicamente aprobadas
- **Procedimiento:**Ingresar horas y luego aprobarlass desde administracion
- **Resultado esperado:** Las horas aceptadas son las unicas que se deben reflejar
- **Resultado obtenido:** Solo las horas validas se reflejaron
- **Estado:** APROBADA

### Prueba 011 :Probar la funcion agregar horas

- **Fecha:** 2026-11-06
- **Objetivo:** La validacion de horas debe ser correcta
- **Procedimiento:**Ingresar horas y sobrepasar las horas del proyecto para ver como reacciona el sistema
- **Resultado esperado:** Las horas se suman o rechazan correctamente
- **Resultado obtenido originalmente:** se reportó que después de dos registros no se mostraban más horas.
- **Estado histórico:** FALLIDA en la ejecución manual original; la regresión automatizada actual verifica que se muestran más de dos registros y que los resúmenes por estado son correctos.

### Prueba 012 :Probar valores válidos, cero, negativos, texto y duplicados.

- **Fecha:** 2026-11-06
- **Objetivo:**  probar la validacion del formulario agregar horas
- **Procedimiento:**Agregar todo tipo de formato de revision 
- **Resultado esperado:** deberia rechazar cualquier formato no valido
- **Resultado obtenido:** Se rechazo exitosamente
- **Estado:** APROBADA
**OBSERVACION IMPORTANTE**: **El minimo de horas permitido deberia ser definido por un administrador y no permitir que puedan hacer registros de 1 minuto de trabajo**

**Nota vigente:** no se acordó una duración mínima configurable; actualmente se admite cualquier cantidad positiva hasta dos decimales, respetando el máximo diario.

### Prueba 013 :Probar actividades solapadas y no solapadas.

- **Fecha:** 2026-11-06
- **Objetivo:** verificar que las actividades esten bien Validadas
- **Procedimiento:**Ingresar actividades para el mismo periodo de fecha
- **Resultado esperado actualizado:** un cruce de horario del mismo responsable o recurso debe advertirse, bloquearse sin confirmación y permitirse tras confirmación explícita.
- **Resultado obtenido originalmente:** los cruces se rechazaban sin opción de confirmarlos.
- **Estado histórico:** APROBADA bajo el criterio original; el flujo actualizado está cubierto por la suite automatizada.
### Prueba 014 :Probar asignación, duplicado y retiro de participación.

- **Fecha:** 2026-11-06
- **Objetivo:** Verificar todas las funciones de asignacion de alumnos
- **Procedimiento:**Ingresar alumnos en los proyectos y retirarlos
- **Resultado esperado:** los estudiantes puedenn asignarse y retirarse de los proyectos y no puede haber repetidos
- **Resultado obtenido originalmente:** se asignaban participantes y se impedían duplicados; todavía no estaba disponible el retiro.
- **Estado histórico:** BLOQUEADA en la ejecución manual original; una regresión automatizada posterior comprueba el retiro y la conservación del historial.


## 5. Resumen de resultados

**Resultados de la ronda manual original (2026-11-06; no representan el estado actual):**
- Pruebas ejecutadas: 12
- Pruebas aprobadas: 8
- Pruebas fallidas: 3
- Pruebas bloqueadas: 1

## 6. Regresión automatizada de correcciones

- **Procedimiento:** ejecutar `php tests\run.php` con la base de datos configurada.
- **Fecha:** 2026-11-12
- **Resultado:** suite de integración completada correctamente. Los datos temporales de la prueba se revirtieron.
- **Cobertura añadida:** validación y corrección administrativa de nombres; contador según rol vigente conservando expedientes; límites de fechas entre proyectos, actividades y horas; confirmación explícita de cruces por responsable y por lugar cuando no se especifica otro recurso; máximo de 8 horas diarias; resumen correcto de registros aprobados, pendientes y rechazados; visualización de más de dos registros.

## 7. Validaciones de formularios

- El alta administrativa de estudiantes ofrece una lista cerrada de carreras, con validación equivalente en el servidor; DUI, teléfono, carné, correo, nombres, dirección y fecha tienen restricciones acordes a sus reglas.
- La edición de expedientes y datos de contacto conserva esas restricciones sin quitar compatibilidad con carreras históricas.
- El formulario de registro de horas refleja el máximo diario de 8 horas y su precisión de centésimas; el servidor rechaza valores con más de dos decimales.
- Los formularios de proyectos y actividades limitan los campos de texto conforme a la base de datos. Las horas estimadas aceptan únicamente incrementos de media hora, tanto en el navegador como en el servidor.