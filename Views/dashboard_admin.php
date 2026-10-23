<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Panel de Administración - ITCA-FEPADE</title>
  <style>
    :root {
      --itca-red: #a8201a;
      --itca-red-dark: #841813;
      --bg-main: #f5f6f8;
      --text-main: #333333;
      --border-color: #e5e7eb;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
    body { background-color: var(--bg-main); color: var(--text-main); display: flex; min-height: 100vh; }

    /* Barra Lateral (Sidebar) */
    .sidebar { width: 260px; background-color: #ffffff; border-right: 1px solid var(--border-color); display: flex; flex-direction: column; }
    .sidebar-header { padding: 1.5rem; background-color: var(--itca-red); color: white; text-align: center; }
    .sidebar-header h2 { font-size: 1.2rem; font-weight: 700; }
    .sidebar-header p { font-size: 0.75rem; color: #f3dede; text-transform: uppercase; margin-top: 0.2rem; }
    
    .sidebar-menu { list-style: none; padding: 1.5rem 1rem; flex: 1; }
    .sidebar-menu li { margin-bottom: 0.5rem; }
    .sidebar-menu a { display: block; padding: 0.75rem 1rem; color: #4b5563; text-decoration: none; font-weight: 600; font-size: 0.9rem; border-radius: 6px; transition: all 0.2s; }
    .sidebar-menu a:hover, .sidebar-menu a.active { background-color: #fee2e2; color: var(--itca-red); }

    /* Contenido Principal */
    .main-content { flex: 1; display: flex; flex-direction: column; }
    .topbar { background-color: #ffffff; padding: 1rem 2rem; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; }
    .topbar h1 { font-size: 1.25rem; color: var(--text-main); }
    .btn-logout { background-color: #fee2e2; color: var(--itca-red); border: none; padding: 0.5rem 1rem; border-radius: 6px; font-weight: 600; cursor: pointer; text-decoration: none; font-size: 0.85rem; }
    .btn-logout:hover { background-color: #fecaca; }

    .content-body { padding: 2rem; }
    
    /* Tarjetas de Estadísticas */
    .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
    .stat-card { background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); border-left: 4px solid var(--itca-red); }
    .stat-card h3 { font-size: 0.85rem; color: #6b7280; text-transform: uppercase; margin-bottom: 0.5rem; }
    .stat-card .number { font-size: 1.8rem; font-weight: 700; color: var(--text-main); }

    /* Sección de Accesos Rápidos */
    .card-section { background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); }
    .card-section h3 { font-size: 1.1rem; color: var(--itca-red); margin-bottom: 1rem; }
    .quick-links { display: flex; gap: 1rem; flex-wrap: wrap; }
    .btn-quick { padding: 0.75rem 1.25rem; background-color: #f9fafb; border: 1px solid var(--border-color); border-radius: 6px; color: var(--text-main); text-decoration: none; font-weight: 600; font-size: 0.9rem; transition: 0.2s; }
    .btn-quick:hover { background-color: var(--itca-red); color: white; border-color: var(--itca-red); }
  </style>
</head>
<body>

  <!-- Barra Lateral -->
  <aside class="sidebar">
    <div class="sidebar-header">
      <h2>ITCA-FEPADE</h2>
      <p>Panel Admin</p>
    </div>
    <ul class="sidebar-menu">
      <li><a href="dashboard_admin.php" class="active">📊 Resumen</a></li>
      <li><a href="roles.php">👥 Gestión de Roles</a></li>
      <li><a href="personal.php">🏢 Personal Interno</a></li>
      <li><a href="estudiante.php">🎓 Estudiantes</a></li>
    </ul>
  </aside>

  <!-- Área Principal -->
  <div class="main-content">
    <header class="topbar">
      <h1>Bienvenido, Administrador</h1>
      <a href="logout.php" class="btn-logout">Cerrar Sesión</a>
    </header>

    <div class="content-body">
      <!-- Tarjetas de Estadísticas simuladas -->
      <div class="stats-grid">
        <div class="stat-card">
          <h3>Estudiantes Matriculados</h3>
          <div class="number">1,245</div>
        </div>
        <div class="stat-card">
          <h3>Personal Activo</h3>
          <div class="number">84</div>
        </div>
        <div class="stat-card">
          <h3>Roles del Sistema</h3>
          <div class="number">3</div>
        </div>
      </div>

      <!-- Accesos Rápidos a tus otras vistas -->
      <div class="card-section">
        <h3>Accesos Directos a Módulos</h3>
        <p style="font-size: 0.9rem; color: #6b7280; margin-bottom: 1rem;">Selecciona un módulo para gestionar las vistas que has desarrollado:</p>
        <div class="quick-links">
          <a href="roles.php" class="btn-quick">Administrar Roles</a>
          <a href="personal.php" class="btn-quick">Registrar Personal</a>
          <a href="estudiante.php" class="btn-quick">Ficha de Estudiante</a>
        </div>
      </div>
    </div>
  </div>

</body>
</html>