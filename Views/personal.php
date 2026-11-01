<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gestión de personal - ITCA-FEPADE</title>
  <link rel="stylesheet" href="css/estudiantes.css">
</head>
<body>
  <aside class="sidebar">
    <div class="sidebar-brand"><h2>ITCA-FEPADE</h2><p>Administración del sistema</p></div>
    <ul class="sidebar-nav">
      <li><a href="dashboard_admin.php">🏠 <span>Inicio</span></a></li>
      <li><a href="estudiantes_listado.php">🎓 <span>Estudiantes</span></a></li>
      <li><a href="proyectos.php">📁 <span>Proyectos</span></a></li>
      <li><a href="roles.php">👥 <span>Usuarios y roles</span></a></li>
      <li><a href="personal.php" class="active">🧑‍💼 <span>Personal</span></a></li>
    </ul>
  </aside>

  <div class="main-wrapper">
    <header class="topbar"><span>Gestión de personal</span><a href="logout.php" class="btn-logout">Cerrar sesión</a></header>
    <main class="content-area">
      <div class="breadcrumb">Inicio / Personal</div>
      <div class="page-header"><div><h1>Registrar personal</h1><p class="page-description">Crea una cuenta interna con el rol y estado correspondientes.</p></div></div>
      <section class="form-card">
        <form id="personalForm" action="guardar_personal.php" method="POST">
          <div class="form-grid">
            <div class="form-group"><label for="nombre">Nombre completo</label><input type="text" id="nombre" name="nombre" placeholder="Nombre del empleado" required></div>
            <div class="form-group"><label for="correo">Correo institucional</label><input type="email" id="correo" name="correo" placeholder="empleado@itca.edu.sv" required></div>
            <div class="form-group"><label for="rol">Rol asignado</label><select id="rol" name="rol"><option value="admin">Administrador</option><option value="docente">Docente / Profesor</option><option value="asistente">Asistente administrativo</option></select></div>
            <div class="form-group"><label for="password">Contraseña temporal</label><input type="password" id="password" name="password" placeholder="Contraseña temporal" required></div>
            <div class="form-group full"><label><input type="checkbox" id="estado" name="estado" checked> Cuenta activa en el sistema</label></div>
          </div>
          <div class="form-actions"><button type="submit" class="btn-red">Guardar cuenta</button><a href="roles.php" class="btn-gray">Cancelar</a></div>
        </form>
      </section>
    </main>
  </div>
</body>
</html>
