<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Mis Solicitudes - ITCA-FEPADE</title>
  <link rel="stylesheet" href="css/estudiantes.css">
</head>
<body>
  <aside class="sidebar">
    <div class="sidebar-brand"><h2>ITCA FEPADE</h2><p>Sistema de Proyección Social</p></div>
    <ul class="sidebar-nav">
      <li><a href="#">🏠 Inicio</a></li>
      <li><a href="#">👤 Mi perfil</a></li>
      <li><a href="#">📂 Mis proyectos</a></li>
      <li><a href="mis_solicitudes.php" class="active">📄 Mis solicitudes</a></li>
      <li><a href="#">📋 Mis asignaciones</a></li>
      <li><a href="#">📅 Mis actividades</a></li>
      <li><a href="#">🔔 Notificaciones</a></li>
    </ul>
  </aside>

  <div class="main-wrapper">
    <header class="topbar"><span>👤 Estudiante</span></header>
    
    <main class="content-area">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <div>
          <h2>Mis solicitudes</h2>
          <p style="color: var(--text-muted); font-size: 0.85rem;">Consulta el estado de tus solicitudes y realiza nuevas solicitudes relacionadas con tu Proyección Social.</p>
        </div>
        <a href="#" style="background: var(--itca-red); color: white; padding: 0.6rem 1.2rem; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 0.9rem;">+ Nueva solicitud</a>
      </div>

      <!-- Tarjetas de Estadísticas -->
      <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 1.5rem;">
        <div class="form-card" style="padding: 1rem; margin: 0; display: flex; align-items: center; gap: 1rem;">
          <div style="font-size: 1.8rem; background: #fee2e2; color: var(--itca-red); padding: 0.5rem; border-radius: 8px;">📄</div>
          <div>
            <span style="color: var(--text-muted); font-size: 0.8rem;">Total de solicitudes</span>
            <h3 style="font-size: 1.5rem; margin: 0;">5</h3>
          </div>
        </div>
        <div class="form-card" style="padding: 1rem; margin: 0; display: flex; align-items: center; gap: 1rem;">
          <div style="font-size: 1.8rem; background: #dcfce7; color: #16a34a; padding: 0.5rem; border-radius: 8px;">✔️</div>
          <div>
            <span style="color: var(--text-muted); font-size: 0.8rem;">Aprobadas</span>
            <h3 style="font-size: 1.5rem; margin: 0; color: #16a34a;">3</h3>
          </div>
        </div>
        <div class="form-card" style="padding: 1rem; margin: 0; display: flex; align-items: center; gap: 1rem;">
          <div style="font-size: 1.8rem; background: #fef3c7; color: #d97706; padding: 0.5rem; border-radius: 8px;">⏳</div>
          <div>
            <span style="color: var(--text-muted); font-size: 0.8rem;">En revisión</span>
            <h3 style="font-size: 1.5rem; margin: 0; color: #d97706;">1</h3>
          </div>
        </div>
        <div class="form-card" style="padding: 1rem; margin: 0; display: flex; align-items: center; gap: 1rem;">
          <div style="font-size: 1.8rem; background: #fee2e2; color: #dc2626; padding: 0.5rem; border-radius: 8px;">❌</div>
          <div>
            <span style="color: var(--text-muted); font-size: 0.8rem;">Rechazadas</span>
            <h3 style="font-size: 1.5rem; margin: 0; color: #dc2626;">1</h3>
          </div>
        </div>
      </div>

      <!-- Tabla de Solicitudes -->
      <div class="form-card" style="padding: 0; overflow: hidden;">
        <div style="padding: 1rem; background: var(--bg-main); border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
          <strong>Mis solicitudes registradas</strong>
          <input type="text" placeholder="Buscar por asunto o proyecto..." style="padding: 0.4rem 0.8rem; border: 1px solid var(--border-color); border-radius: 4px; font-size: 0.85rem; width: 250px;">
        </div>
        <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9rem;">
          <thead style="background: #f8fafc; border-bottom: 1px solid var(--border-color);">
            <tr>
              <th style="padding: 0.8rem 1rem;">N.°</th>
              <th style="padding: 0.8rem 1rem;">Asunto</th>
              <th style="padding: 0.8rem 1rem;">Proyecto</th>
              <th style="padding: 0.8rem 1rem;">Fecha de solicitud</th>
              <th style="padding: 0.8rem 1rem;">Estado</th>
              <th style="padding: 0.8rem 1rem; text-align: center;">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr style="border-bottom: 1px solid var(--border-color);">
              <td style="padding: 0.8rem 1rem;">#005</td>
              <td style="padding: 0.8rem 1rem; font-weight: 500;">Constancia de horas</td>
              <td style="padding: 0.8rem 1rem;">Apoyo Comunitario en la Zona Norte</td>
              <td style="padding: 0.8rem 1rem;">10/11/2026</td>
              <td style="padding: 0.8rem 1rem;"><span style="background: #dcfce7; color: #16a34a; padding: 0.2rem 0.6rem; border-radius: 4px; font-size: 0.8rem;">✔️ Aprobada</span></td>
              <td style="padding: 1rem; text-align: center;">
                <a href="#" title="Ver" style="text-decoration: none; margin-right: 0.5rem;">👁️</a>
                <a href="#" title="Descargar" style="text-decoration: none;">📥</a>
              </td>
            </tr>
            <tr style="border-bottom: 1px solid var(--border-color);">
              <td style="padding: 0.8rem 1rem;">#004</td>
              <td style="padding: 0.8rem 1rem; font-weight: 500;">Cambio de asignación</td>
              <td style="padding: 0.8rem 1rem;">Apoyo Comunitario en la Zona Norte</td>
              <td style="padding: 0.8rem 1rem;">06/11/2026</td>
              <td style="padding: 0.8rem 1rem;"><span style="background: #fef3c7; color: #d97706; padding: 0.2rem 0.6rem; border-radius: 4px; font-size: 0.8rem;">⏳ En revisión</span></td>
              <td style="padding: 1rem; text-align: center;">
                <a href="#" title="Ver" style="text-decoration: none;">👁️</a>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </main>
  </div>
</body>
</html>