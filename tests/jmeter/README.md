# JMeter - prueba de inicio de sesión

Este plan contiene solo el flujo de login: obtiene el token CSRF, envía las credenciales y
verifica que la respuesta no vuelva a mostrar el formulario de acceso. No crea ni modifica
datos de los módulos.

## Preparación

1. Instala Java compatible con Apache JMeter y PHP.
2. Desde la raíz del proyecto inicia la aplicación: `php -S 127.0.0.1:8000 router.php`.
3. Copia `user.properties.example` como `user.properties` en esta carpeta y completa
   `login_user` y `login_password` con las credenciales de una cuenta activa.
4. Desde la raíz del proyecto inicia JMeter con esas propiedades:
   `jmeter -q tests/jmeter/user.properties`.
5. Abre `tests/jmeter/Sistema-Academico-ITCA.jmx` y pulsa **Start**.

El plan ejecuta por defecto **1 hilo y 1 iteración**. Puedes modificar `threads`, `ramp_seconds`
y `loops` en tu archivo `user.properties` si quieres hacer una prueba de concurrencia de login.
Para una comprobación funcional manual, déjalos en `1`.

## Resultados

En la interfaz, revisa **View Results Tree** para el detalle de las peticiones y aserciones,
**Summary Report** y **Aggregate Report** para métricas, y **Graph Results** para la gráfica.
El **Simple Data Writer** de la interfaz guarda muestras en
`tests/jmeter/resultados/gui-login.jtl`. El comando por consola usa `login.jtl` para generar
el dashboard; son archivos separados para evitar que dos escritores usen el mismo archivo.

Para generar el dashboard HTML desde PowerShell, en la raíz del proyecto:

```powershell
jmeter -q tests/jmeter/user.properties -n `
  -t tests/jmeter/Sistema-Academico-ITCA.jmx `
  -l tests/jmeter/resultados/login.jtl `
  -e -o tests/jmeter/resultados/reporte-html
```

Abre `tests/jmeter/resultados/reporte-html/index.html`. El dashboard incluye resumen,
tasas de error, rendimiento, percentiles y gráficas. JMeter no reutiliza un directorio HTML
existente; elige una nueva ruta de salida por ejecución o limpia manualmente el reporte anterior.
Los archivos de credenciales, JTL y reportes están excluidos de Git.

## Ejecutar por separado

Postman y JMeter son herramientas independientes: ejecuta solo la colección o el plan que
necesites; ninguno inicia al otro ni ejecuta `tests/run.php`.

- **Postman CRUD manual**: colección `Pruebas manuales - Sistema Académico ITCA`.
- **Postman login**: colección `Prueba de Carga - Login`.
- **JMeter login**: este archivo `.jmx`.

En Postman usa **Run** sobre una sola colección, no sobre todo el workspace. En JMeter ejecuta
este único plan. No selecciones ambas herramientas en una acción externa de automatización si
quieres evitar ejecuciones simultáneas.
