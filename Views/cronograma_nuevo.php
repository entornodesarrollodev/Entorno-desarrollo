<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Registrar Actividad - ITCA-FEPADE</title>
  <link rel="stylesheet" href="css/estudiantes.css">
</head>
<body>
  <aside class="sidebar">
    <div class="sidebar-brand"><h2>ITCA FEPADE</h2><p>Sistema de Proyección Social</p></div>
    <ul class="sidebar-nav">
      <li><a href="/dashboard.php">🏠 Inicio</a></li>
      <li><a href="estudiantes_listado.php">🎓 Estudiantes</a></li>
      <li><a href="/proyectos.php" class="active">📅 Proyectos</a></li>
    </ul>
  </aside>

  <div class="main-wrapper">
    <header class="topbar"><span>👤 Brayan Vásquez (Administrador)</span><a href="logout.php" class="btn-logout">Cerrar sesión</a></header>
    
    <main class="content-area">
      <div class="breadcrumb">🏠 Inicio > Cronograma > Nueva Actividad</div>
      <h2 style="margin-top: 0.5rem;">Registrar Actividad del Cronograma</h2>
      <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 1.5rem;">Ingrese los detalles de la actividad validando disponibilidad en el calendario.</p>

      <form action="/cronograma_nuevo.php" method="POST" class="form-card">
        <h4 style="margin-bottom: 1rem; color: var(--itca-red);">📅 Detalle del Evento</h4>
        
        <div class="form-grid">
          <div class="form-group">
            <label>Título de la Actividad *</label>
            <input type="text" name="titulo" maxlength="180" required placeholder="Ej: Taller de Capacitación Básica">
          </div>
          <div class="form-group full">
            <label>Descripción *</label>
            <textarea name="descripcion" rows="2" required placeholder="Describa el objetivo y trabajo de la actividad"></textarea>
          </div>
          <div class="form-group">
            <label>Modalidad *</label>
            <select name="modalidad" required>
              <option value="">Seleccione...</option>
              <option>Presencial</option>
              <option>Virtual</option>
              <option>Mixta</option>
            </select>
          </div>
          <div class="form-group">
            <label>Proyecto Asociado *</label>
            <select name="proyecto_id" required>
              <option value="">Seleccione...</option>
              <option value="1">Alfabetización Comunitaria</option>
            </select>
          </div>
          <div class="form-group">
            <label>Fecha y Hora de Inicio *</label>
            <input type="datetime-local" name="fecha_inicio" required>
          </div>
          <div class="form-group">
            <label>Fecha y Hora de Finalización *</label>
            <input type="datetime-local" name="fecha_fin" required>
          </div>
          <div class="form-group">
            <label>Fecha límite de respuesta de asistencia (opcional)</label>
            <input type="datetime-local" name="fecha_limite_confirmacion">
            <small>Debe ser anterior o igual al inicio. Si se deja vacío, el plazo termina al comenzar la actividad.</small>
          </div>
          <div class="form-group" style="grid-column: span 2;">
            <label>Lugar o Enlace *</label>
            <input type="text" name="lugar" maxlength="255" required placeholder="Ej: Centro Escolar Cantón El Guayabo / Enlace Meet">
          </div>
          <div class="form-group">
            <label>Horas estimadas *</label>
            <input type="number" name="horas_estimadas" min="0.5" max="24" step="0.5" required placeholder="Ej: 4">
          </div>
          <div class="form-group">
            <label>Cupo (opcional)</label>
            <input type="number" name="cupo" min="1" step="1" placeholder="Ej: 25">
          </div>
          <div class="form-group">
            <label>Recurso compartido (opcional)</label>
            <input type="text" name="recurso" maxlength="150" placeholder="Aula, laboratorio, enlace o equipo">
          </div>
          <div class="form-group">
            <label>Responsable *</label>
            <select name="responsable_id" required>
              <option value="">Seleccione...</option>
            </select>
          </div>
        </div>

        <div style="margin-top: 1.5rem; display: flex; gap: 1rem; justify-content: flex-end;">
          <a href="proyectos.php" style="padding: 0.6rem 1.2rem; text-decoration: none; color: var(--text-muted); border: 1px solid var(--border-color); border-radius: 6px;">Cancelar</a>
          <button type="submit" style="background: var(--itca-red); color: white; border: none; padding: 0.6rem 1.2rem; border-radius: 6px; cursor: pointer;">💾 Guardar Actividad</button>
        </div>
      </form>
    </main>
  </div>
</body>
</html>
