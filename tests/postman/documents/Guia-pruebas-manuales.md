# Pruebas manuales con Postman

## Preparación

1. Inicia la aplicación desde la raíz del proyecto: `php -S 127.0.0.1:8000 router.php`.
2. En Postman Desktop, pulsa **Import** y selecciona los archivos JSON de `tests/postman/exports`:
   - `Pruebas-manuales-Sistema-Academico-ITCA.postman_collection.json` para CRUD.
   - `Prueba-de-Carga-Login.postman_collection.json` para login solamente.
3. Abre la colección **Pruebas manuales - Sistema Académico ITCA**.
4. En las variables de la colección, completa `usuario_login` y `password_login` con una cuenta que tenga permisos de administración. Conserva `base_url` si el servidor usa `127.0.0.1:8000`.
5. Ejecuta **00 - Sesión → GET Preparar sesión y token CSRF** y luego **POST Iniciar sesión**. Postman conserva automáticamente la cookie de sesión.
6. Ejecuta individualmente las solicitudes de los módulos desde la colección. Para repetir una prueba, actualiza sus variables antes de enviar la solicitud.
7. Si cambias de servidor o reinicias la sesión, vuelve a ejecutar ambos pasos de sesión.

## Probar módulos

- **Proyectos**: usa `GET Buscar y filtrar proyectos` con `buscar_proyecto`, `estado_proyecto` y `filtro_modalidad_proyecto`. Crea con `POST Crear proyecto`; luego localiza el proyecto y copia su ID a `proyecto_id` para consultar, editar o cerrar/reactivar.
- **Estudiantes**: usa `GET Buscar y filtrar estudiantes` con `buscar_estudiante`, `filtro_carrera` y `filtro_estado_estudiante`. Crea o edita con las variables de estudiante; copia el ID a `estudiante_id`. Cambia `estado_estudiante` a Inactivo o Activo en `POST Cambiar estado del expediente`; conserva el historial.
- **Actividades**: lista por `proyecto_id`, crea o edita con `actividad_id` y las fechas `fecha_hora_inicio_actividad` / `fecha_hora_fin_actividad`. Debe usarse un proyecto activo y las fechas deben quedar dentro de su periodo. El sistema no tiene una acción para eliminar actividades.
- **Asignaciones**: busca estudiantes activos para el proyecto, asigna usando `estudiante_id` y retira usando `asignacion_id`. Retirar conserva el historial.

Si una actividad coincide con otra en horario, ajusta sus fechas o cambia `confirmar_conflicto` a `1` únicamente cuando quieras confirmar deliberadamente el cruce.

## Datos de prueba y comportamiento

Edita las variables de la colección antes de ejecutar las peticiones; los cuerpos de las solicitudes las referencian directamente. Cambia `codigo_proyecto`, `carnet` y `correo_estudiante` a valores únicos cada vez que repitas altas. Los IDs iniciales `1` son ejemplos: reemplázalos por IDs existentes en tu base.

Las rutas de esta aplicación son formularios web, no una API JSON: Postman recibirá páginas HTML y redirecciones. Las operaciones de actualización y baja son POST y requieren sesión, permisos y `token_csrf`. No existe borrado físico para proyectos, expedientes ni asignaciones.

## Ejecutar suites por separado

- Para el CRUD, selecciona **Pruebas manuales - Sistema Académico ITCA** y ejecuta solo las peticiones o carpeta que necesites.
- Para probar únicamente login en Postman, selecciona la colección independiente **Prueba de Carga - Login**; completa `login_identifier` y `login_password`, ejecuta primero `GET Preparar sesion y extraer CSRF` y luego `POST Iniciar sesion`.
- Para login en JMeter, abre el plan de [JMeter](../../tests/jmeter/Sistema-Academico-ITCA.jmx) por separado. Postman y JMeter no se invocan entre sí. En Collection Runner ejecuta una sola colección, no todo el workspace.

Los JSON en `tests/postman/exports` son exportaciones Postman Collection v2.1 para Postman Desktop. Las definiciones YAML originales siguen en `tests/postman/collections`; los cambios en una colección importada en Desktop no se sincronizan automáticamente con esos archivos.
