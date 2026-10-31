<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Gestión de Estudiantes - ITCA-FEPADE</title>
  <link rel="stylesheet" href="css/estudiantes.css">
</head>
<body>
  <aside class="sidebar">
    <div class="sidebar-brand"><h2>ITCA FEPADE</h2><p>Sistema de Proyección Social</p></div>
    <ul class="sidebar-nav">
      <li><a href="#">🏠 Inicio</a></li>
      <li><a href="estudiantes_listado.php" class="active">🎓 Estudiantes</a></li>
      <li><a href="#">📂 Proyectos</a></li>
      <li><a href="#">📋 Registros</a></li>
      <li><a href="#">📊 Reportes</a></li>
      <li><a href="#">⚙️ Configuración</a></li>
    </ul>
  </aside>

  <div class="main-wrapper">
    <header class="topbar"><span>👤 Brayan Vásquez (Administrador)</span></header>

    <main class="content-area">
      <div class="breadcrumb">🏠 Inicio > Estudiantes</div>
      <div class="page-header">
        <div>
          <h2>Gestión de Estudiantes</h2>
          <p style="color: var(--text-muted); font-size: 0.85rem;">Administra la información de los estudiantes participantes en proyectos.</p>
        </div>
        <a href="estudiantes_nuevo.php" class="btn-red">+ Nuevo Estudiante</a>
      </div>

      <!-- Buscador y Filtros -->
      <div class="form-card" style="margin-bottom: 1.5rem; padding: 1rem;">
        <form style="display: flex; gap: 1rem;">
          <input type="text" placeholder="Buscar por nombre, carnet o carrera..." style="flex: 1; padding: 0.6rem; border: 1px solid var(--border-color); border-radius: 6px;">
          <button type="submit" class="btn-red">🔍 Buscar</button>
        </form>
      </div>

      <!-- Tabla de Listado -->
      <div class="form-card" style="padding: 0; overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.85rem;">
          <thead style="background: #f1f5f9; border-bottom: 1px solid var(--border-color);">
            <tr>
              <th style="padding: 0.8rem;">#</th>
              <th style="padding: 0.8rem;">Foto</th>
              <th style="padding: 0.8rem;">Nombre completo</th>
              <th style="padding: 0.8rem;">Carnet</th>
              <th style="padding: 0.8rem;">Carrera</th>
              <th style="padding: 0.8rem;">Estado</th>
              <th style="padding: 0.8rem; text-align: center;">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr style="border-bottom: 1px solid var(--border-color);">
              <td style="padding: 0.8rem;">1</td>
              <td style="padding: 0.8rem;">👤</td>
              <td style="padding: 0.8rem; font-weight: 600;">Juan Carlos Pérez López</td>
              <td style="padding: 0.8rem;">202400123</td>
              <td style="padding: 0.8rem;">Ing. en Desarrollo de Software</td>
              <td style="padding: 0.8rem;"><span class="badge badge-activo">Activo</span></td>
              <td style="padding: 0.8rem; text-align: center;">
                <a href="estudiantes_detalle.php" title="Ver detalle">👁️</a>
                <a href="estudiantes_editar.php" title="Editar">✏️</a>
                <a href="#" title="Inactivar" style="color: var(--itca-red);">🗑️</a>
              </td>
            </tr>
            <tr>
              <td style="padding: 0.8rem;">2</td>
              <td style="padding: 0.8rem;">👤</td>
              <td style="padding: 0.8rem; font-weight: 600;">Carlos Daniel Herrera</td>
              <td style="padding: 0.8rem;">202401345</td>
              <td style="padding: 0.8rem;">Ing. en Desarrollo de Software</td>
              <td style="padding: 0.8rem;"><span class="badge badge-inactivo">Inactivo</span></td>
              <td style="padding: 0.8rem; text-align: center;">
                <a href="estudiantes_detalle.php">👁️</a>
                <a href="estudiantes_editar.php">✏️</a>
                <a href="#" style="color: var(--itca-red);">🗑️</a>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </main>
  </div>
</body>
</html>