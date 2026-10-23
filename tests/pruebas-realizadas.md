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

- Pruebas ejecutadas: 3
- Pruebas aprobadas: 2
- Pruebas fallidas: 1
- Pruebas bloqueadas: 2

## 3. Observaciones

la prueba 004 y 003fue bloqueada por la incopatibilidad de la prueba al no poder crear cuentas nuevas para asignar roles