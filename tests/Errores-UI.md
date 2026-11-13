## errores en interfaz de usuario



## Modulo: inicio
Descripción: Escalabilidad cuestionable 

Detalles:
1. Solicitudes de registro pendientes no es una notificacion
recomendacion: dejar las solicitudes como modulo aparte donde se acumulen las solicitudes y hacerlo mas escalable
2. Bajar hasta "modulos del sistema" es completamente inutil si es un acceso directo sin retorno
recomendacion: si se quiere conservar esa funcion, evitar que lleve al modulo y mejor lo muestre en la misma pagina sin recargar para no hacerlo tedioso teniendo que volver a bajar cada vez que se quiera presionar uno diferente
3. "Modulos disponibles" muestra informacion totalmente irrelevante
recomendacion: eliminar este apartado


Impacto: Puede provocar confusión y dificultar el uso del sitema

---
## Modulo: panel de proyeccion
Notificaciones mal colocadas y lenguaje tecnico detectado

Descripción: Notificaciones no ocupadas correctamente

Detalles:
1. Notificaciones tiene un acceso para "ver plantillas" ¿por que?
recomendacion: Las notificaciones no son plantillas y deberian estar en el panel principal
2. Notificaciones ocupa lenguaje tecnico como "Estado: Aprobada. /mis_solicitudes.php?solicitud_id=33"
recomendacion: Evitar el lenguaje tecnico en las notificaciones y cambiarlo por un mensaje amigable con el usuario

3. Solicitudes de estudiantes pendientes sin escalabilidad
recomendacion: deberia ser un apartado diferente o ser un desplegable para evitar al ser llenado de solicitudes bajar infinitamente hacia abajo dejando el panel principal olvidado hasta arriba


Impacto: Puede perder al usuario al no dejarle las notificaciones a la vista

---
## Modulo: Estudiantes
Optimizacion e intuitividad

Descripción: Distribucion de la interfaz mal organizada

Detalles:
1. Entrar en el modulo "Estudiantes" Puede causar confucion
recomendacion:  El modulo deberia llamarse "Expedientes" para facilitar la busqueda al usuario de este apartado 
2. mala optimizacion del espacio
recomendacion: proyectos relacionados, horas y actividades, Evaluaciones y documentos deberian estar en un menu como en modulo inicio "modulos disponibles" (aplicando las mejoras antes mencionadas)

3. Solicitudes de estudiantes pendientes sin escalabilidad
recomendacion: deberia ser un apartado diferente o ser un desplegable para evitar al ser llenado de solicitudes bajar infinitamente hacia abajo dejando el panel principal olvidado hasta arriba


Impacto: Puede no ser intuitivo

---
## Modulo: Proyectos 
Optimizacion e intuitividad

Descripción: Funciones mal organizadas en la interfaz 

Detalles:
1.  crear proyecto ocupa toda la pantalla como si siempre se va a crear uno
recomendacion:  crear proyecto como boton el mismo modulo que desplega el formulario para crear un nuevo proyecto
2. editar proyecto siempre esta en detalles pero no siempre se ocupara
recomendacion: al ver detalles en los detalles de proyecto agregar el boton "editar para una  interfaz mas limpia
3. problemas de escalabilidad en ver detalles de proyecto
recomendacion: al ver los detalles de un proyecto deberia abrirse en grande con un boton para regresar a ver los proyectos
4. Botones no pensados a grandes datos
recomendacion: mover los botones de agregar y asignar a la parte superior derecha para evitar que ehaya que dezplazarse al querer ocuparlos

Impacto: Perdida de tiempo mmasiva buscando las funciones correctas

---
## Modulo: Proyectos  disponibles
Apartado poco util
Descripción: Apartados poco utiles y solo informativos
Detalles:
1.  Los proyectos solo se muestran y solo se pueden ver sus actividades
recomendacion: hacer de  este apartado mas util permitiendo agregar a los estudiantes directamente al proyecto y a sus actividades designadas


Impacto: la Funcion podria ser facilmente olvidada e inutil

---

## Modulo: Calendario
mal optimizado
Descripción: fallas en la distribucion de la interfaz
Detalles:
1.  Los filtros estan debajo del calendario
recomendacion: poner los filtros del lado arriba y al ocuparlos desaparece el calendario y aparecen las actividades filtradas

Impacto: Ahorro de tiempo de busqueda

---
## Modulo: Evaluaciones
mala escalabilidad
Descripción: al haber muchas evaluaciones sera dificil encontrarlas naturalmente
Detalles:
1.  interfaz conn los apartados  hacia abajo
recomendacion: Crear un menu como el mecionado antes (primer modulo) y agregar filtros, y asi evitar la superpocision y hacerlo mas escalable y

Impacto: Ahorro de tiempo de busqueda

---
## Modulo: Respuestas de asistencia
filtros
Descripción: No hay filtros
Detalles:
1.  dificultad si hay muchas actividades
recomendacion: Agregar filtros de busqueda

Impacto: Ahorro de tiempo de busqueda

---
## Modulo: plantilla de notificacion
poco intuitiva
Descripción: No es facil de ocupar
Detalles:
1.  Usuario promedio no entenderia la plantilla
recomendacion: dejarla  de manera predeterminada y eliminar el modulo "plantillas"

Impacto: confucion en el usuario

---
## Modulo: Diplomas y cartas
dezplazamiento
Descripción: problema en interfaz
Detalles:
1.  dezplazamiento de derecha a izquierda incomodo
recomendacion: El apartado de estudiantes elegibles para el diplona justo debajo del diploma para evitar el dezplazamiento no deseado
Impacto: interfaz mas limpia

---
## errores en interfaz de usuario solucionado
**estado** : cerrado


## errores de interfaz de usuario alumno

ubicacion : Mi espacio
modulo : Mis solicitudes

detalles:
1. formulario a la vista Nueva solicitud de participacion
recomendacion: hacer que un boton lo despliegue llamado "nueva solicitud", asi  "mis solicitudes" queda mas limpio

Impacto: molesto para el usuario