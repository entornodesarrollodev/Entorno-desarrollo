<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Administración de roles - ITCA-FEPADE</title>
  <link rel="stylesheet" href="css/estudiantes.css">
</head>
<body>
  <aside class="sidebar">
    <div class="sidebar-brand"><h2>ITCA-FEPADE</h2><p>Administración del sistema</p></div>
    <ul class="sidebar-nav">
      <li><a href="dashboard_admin.php">🏠 <span>Inicio</span></a></li>
      <li><a href="estudiantes_listado.php">🎓 <span>Estudiantes</span></a></li>
      <li><a href="proyectos.php">📁 <span>Proyectos</span></a></li>
      <li><a href="roles.php" class="active">👥 <span>Usuarios y roles</span></a></li>
      <li><a href="personal.php">🧑‍💼 <span>Personal</span></a></li>
    </ul>
  </aside>

  <div class="main-wrapper">
    <header class="topbar"><span>Administración de usuarios y roles</span><a href="logout.php" class="btn-logout">Cerrar sesión</a></header>
    <main class="content-area">
      <div class="breadcrumb">Inicio / Usuarios y roles</div>
      <div class="page-header">
        <div><h1>Gestión de usuarios y roles</h1><p class="page-description">Consulta y administra las cuentas internas del sistema.</p></div>
        <a href="personal.php" class="btn-red">+ Registrar personal</a>
      </div>
      <section class="table-container">
        <table>
          <thead><tr><th>Nombre</th><th>Correo electrónico</th><th>Rol actual</th><th>Acciones</th></tr></thead>
          <tbody>
            <tr><td>Kevin Díaz</td><td>kevin.diaz@itca.edu.sv</td><td><span class="badge admin">Administrador</span></td><td><button class="btn-action btn-edit">Editar</button><button class="btn-action btn-delete">Desactivar</button></td></tr>
            <tr><td>María Pérez</td><td>maria.perez@itca.edu.sv</td><td><span class="badge docente">Docente</span></td><td><button class="btn-action btn-edit">Editar</button><button class="btn-action btn-delete">Desactivar</button></td></tr>
            <tr><td>Juan López</td><td>juan.lopez@itca.edu.sv</td><td><span class="badge estudiante">Estudiante</span></td><td><button class="btn-action btn-edit">Editar</button><button class="btn-action btn-delete">Desactivar</button></td></tr>
          </tbody>
        </table>
      </section>
    </main>
  </div>
</body>
</html>
