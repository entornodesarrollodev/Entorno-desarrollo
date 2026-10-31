<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Detalle del Estudiante - ITCA-FEPADE</title>
  <link rel="stylesheet" href="css/estudiantes.css">
</head>
<body>
  <aside class="sidebar">
    <div class="sidebar-brand"><h2>ITCA FEPADE</h2><p>Sistema de Proyección Social</p></div>
    <ul class="sidebar-nav">
      <li><a href="#">🏠 Inicio</a></li>
      <li><a href="estudiantes_listado.php" class="active">🎓 Estudiantes</a></li>
    </ul>
  </aside>

  <div class="main-wrapper">
    <header class="topbar"><span>👤 Brayan Vásquez (Administrador)</span></header>

    <main class="content-area">
      <div class="breadcrumb">🏠 Inicio > Estudiantes > Detalle</div>

      <!-- Tarjeta Resumen -->
      <div class="form-card" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <div style="display: flex; gap: 1.5rem; align-items: center;">
          <div style="font-size: 3rem;">👤</div>
          <div>
            <h2>Juan Carlos Pérez López <span class="badge badge-activo">Activo</span></h2>
            <p style="color: var(--text-muted);">Ing. en Desarrollo de Software | Carnet: 202400123</p>
          </div>
        </div>
        <div style="display: flex; gap: 0.5rem;">
          <a href="estudiantes_editar.php" class="btn-gray" style="background-color: #f59e0b; color: #fff;">✏️ Editar</a>
          <a href="#" class="btn-red">🗑️ Eliminar</a>
        </div>
      </div>

      <!-- Expediente Dividido -->
      <div class="form-grid">
        <div class="form-card">
          <h4 style="margin-bottom: 1rem; color: var(--itca-red);">👤 Datos personales</h4>
          <p><strong>Nombres:</strong> Juan Carlos</p>
          <p><strong>Apellidos:</strong> Pérez López</p>
          <p><strong>Carnet:</strong> 202400123</p>
          <p><strong>DUI:</strong> 12345678-9</p>
          <p><strong>Correo:</strong> juan.perez@itca.edu.sv</p>
          <p><strong>Teléfono:</strong> 7654-3210</p>
          <p><strong>Dirección:</strong> Santa Ana, El Salvador</p>
        </div>

        <div class="form-card">
          <h4 style="margin-bottom: 1rem; color: var(--itca-red);">➕ Información académica</h4>
          <p><strong>Carrera:</strong> Ing. en Desarrollo de Software</p>
          <p><strong>Estado:</strong> Activo</p>
          <p><strong>Fecha de registro:</strong> 12/03/2024</p>
        </div>
      </div>
    </main>
  </div>
</body>
</html>