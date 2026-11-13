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
- **Resultado esperado:** Deberia negar el registro con esas fechas
- **Resultado obtenido:** El registro fue echo correctamente
- **Estado:** Fallida


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
- **Resultado obtenido:** El sistema despues de registrar dos actividades por proyecto ya no le refleja mas al estudiante para validacion aunque las horas si existan
- **Estado:** FALLIDA

### Prueba 012 :Probar valores válidos, cero, negativos, texto y duplicados.

- **Fecha:** 2026-11-06
- **Objetivo:**  probar la validacion del formulario agregar horas
- **Procedimiento:**Agregar todo tipo de formato de revision 
- **Resultado esperado:** deberia rechazar cualquier formato no valido
- **Resultado obtenido:** Se rechazo exitosamente
- **Estado:** APROBADA
**OBSERVACION IMPORTANTE**: **El minimo de horas permitido deberia ser definido por un administrador y no permitir que puedan hacer registros de 1 minuto de trabajo**

### Prueba 013 :Probar actividades solapadas y no solapadas.

- **Fecha:** 2026-11-06
- **Objetivo:** verificar que las actividades esten bien Validadas
- **Procedimiento:**Ingresar actividades para el mismo periodo de fecha
- **Resultado esperado:** Las actividades para el mismo periodo deberian ser rechazadas
- **Resultado obtenido:** Las actividades fueron rechazadas correctamente
- **Estado:** APROBADA
### Prueba 014 :Probar asignación, duplicado y retiro de participación.

- **Fecha:** 2026-11-06
- **Objetivo:** Verificar todas las funciones de asignacion de alumnos
- **Procedimiento:**Ingresar alumnos en los proyectos y retirarlos
- **Resultado esperado:** los estudiantes puedenn asignarse y retirarse de los proyectos y no puede haber repetidos
- **Resultado obtenido:** se asignan y no se pueden repetir pero falta el retiro de participacion 
- **Estado:** BLOQUEADA POR FALTA DE LA FUNCION


## 5. Resumen de resultados

- Pruebas ejecutadas: 12
- Pruebas aprobadas: 8
- Pruebas fallidas: 3
- Pruebas bloqueadas: 1