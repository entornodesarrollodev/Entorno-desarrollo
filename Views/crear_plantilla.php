<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Crear Plantilla - ITCA-FEPADE</title>
  <link rel="stylesheet" href="css/estudiantes.css">
</head>
<body>
  <aside class="sidebar">
    <div class="sidebar-brand"><h2>ITCA FEPADE</h2><p>Sistema de Proyección Social</p></div>
    <ul class="sidebar-nav">
      <li><a href="#">🏠 Inicio</a></li>
      <li><a href="#">👥 Estudiantes</a></li>
      <li><a href="#">📅 Actividades</a></li>
      <li><a href="crear_plantilla.php" class="active">🔔 Notificaciones</a></li>
      <li><a href="#">⚙️ Configuración</a></li>
    </ul>
  </aside>

  <div class="main-wrapper">
    <header class="topbar"><span>👤 Administrador</span></header>
    
    <main class="content-area">
      <div class="breadcrumb">Notificaciones > Plantillas > Nueva plantilla</div>
      <h2 style="margin-top: 0.5rem;">Crear plantilla de notificación</h2>
      <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 1.5rem;">Diseña la plantilla que se usará para informar sobre una nueva actividad.</p>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
        <!-- Formulario Izquierda -->
        <div class="form-card" style="margin: 0;">
          <h4 style="color: var(--itca-red); margin-bottom: 1rem;">Información de la plantilla</h4>
          <div class="form-group" style="margin-bottom: 1rem;">
            <label>Nombre de la plantilla *</label>
            <input type="text" value="Nueva actividad" style="width: 100%; padding: 0.6rem; border: 1px solid var(--border-color); border-radius: 6px;">
          </div>
          <div class="form-group" style="margin-bottom: 1rem;">
            <label>Tipo de notificación *</label>
            <select style="width: 100%; padding: 0.6rem; border: 1px solid var(--border-color); border-radius: 6px;">
              <option>Actividad</option>
            </select>
          </div>
          <div class="form-group" style="margin-bottom: 1rem;">
            <label>Asunto *</label>
            <input type="text" value="Nueva actividad programada" style="width: 100%; padding: 0.6rem; border: 1px solid var(--border-color); border-radius: 6px;">
          </div>
          <div class="form-group" style="margin-bottom: 1rem;">
            <label>Mensaje *</label>
            <div style="border: 1px solid var(--border-color); border-radius: 6px; padding: 0.5rem; background: #fff;">
              <div style="font-size: 0.8rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.4rem; margin-bottom: 0.4rem; color: #6b7280;">
                <b>B</b> <i>I</i> <u>U</u> &nbsp;|&nbsp; ≡ ≡ &nbsp;|&nbsp; 🔗
              </div>
              <textarea rows="6" style="width: 100%; border: none; outline: none; font-size: 0.85rem;">Se ha registrado una nueva actividad en el proyecto {{nombre_proyecto}}.

Actividad: {{nombre_actividad}}
Fecha: {{fecha_actividad}}
Hora: {{hora_actividad}}
Lugar: {{lugar_actividad}}

Te invitamos a participar y cumplir con tu asignación.</textarea>
            </div>
          </div>
          <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 1rem;">
            <strong>Variables disponibles:</strong><br>
            <span style="background: #f1f5f9; padding: 0.2rem 0.4rem; border-radius: 4px; display: inline-block; margin-top: 0.3rem;">{{nombre_proyecto}}</span>
            <span style="background: #f1f5f9; padding: 0.2rem 0.4rem; border-radius: 4px; display: inline-block; margin-top: 0.3rem;">{{nombre_actividad}}</span>
            <span style="background: #f1f5f9; padding: 0.2rem 0.4rem; border-radius: 4px; display: inline-block; margin-top: 0.3rem;">{{fecha_actividad}}</span>
          </div>
        </div>

        <!-- Vista Previa Derecha -->
        <div class="form-card" style="margin: 0; background: #f8fafc;">
          <h4 style="color: var(--text-main); margin-bottom: 1rem;">Vista previa de la notificación</h4>
          <div style="background: white; border: 1px solid var(--border-color); border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
            <div style="background: var(--itca-red); color: white; padding: 1rem; text-align: center;">
              <strong>ITCA-FEPADE</strong>
            </div>
            <div style="padding: 1.5rem;">
              <h4 style="color: var(--text-main); margin-bottom: 0.5rem;">🔔 Nueva actividad programada</h4>
              <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1rem;">Se ha registrado una nueva actividad en el proyecto "Apoyo Comunitario en la Zona Norte".</p>
              <div style="background: #f8fafc; padding: 1rem; border-radius: 6px; font-size: 0.85rem;">
                <p><strong>📅 Actividad:</strong> Jornada de limpieza</p>
                <p><strong>📆 Fecha:</strong> 15 de marzo de 2025</p>
                <p><strong>🕒 Hora:</strong> 08:00 a. m.</p>
                <p><strong>📍 Lugar:</strong> Parque Central, Santa Tecla</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
</body>
</html>