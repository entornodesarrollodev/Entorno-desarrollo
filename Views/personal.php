<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gestión de Personal - ITCA-FEPADE</title>
  <style>
    :root {
      --itca-red: #a8201a;
      --itca-red-dark: #841813;
      --bg-main: #f5f6f8;
      --text-main: #333333;
      --border-color: #d1d5db;
      --error-color: #dc2626;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
    body { background-color: var(--bg-main); display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 1.5rem; }
    .card-form { background-color: #ffffff; width: 100%; max-width: 500px; border-radius: 10px; box-shadow: 0 10px 25px rgba(0,0,0,0.08); overflow: hidden; border-top: 6px solid var(--itca-red); }
    .header-form { background-color: var(--itca-red); color: #ffffff; padding: 1.8rem 1.5rem; text-align: center; }
    .header-form h2 { font-size: 1.4rem; font-weight: 700; margin-bottom: 0.2rem; }
    .header-form p { font-size: 0.85rem; color: #f3dede; text-transform: uppercase; letter-spacing: 0.5px; }
    .body-form { padding: 2rem; }
    .form-group { margin-bottom: 1.2rem; display: flex; flex-direction: column; }
    .form-group label { font-size: 0.85rem; font-weight: 600; color: var(--text-main); margin-bottom: 0.4rem; }
    .form-group input, .form-group select { padding: 0.75rem 0.9rem; border: 1px solid var(--border-color); border-radius: 6px; font-size: 0.95rem; outline: none; transition: all 0.2s; }
    .form-group input:focus, .form-group select:focus { border-color: var(--itca-red); box-shadow: 0 0 0 3px rgba(168, 32, 26, 0.15); }
    .checkbox-group { display: flex; align-items: center; gap: 0.5rem; margin-top: 0.5rem; }
    .checkbox-group input { width: 1.1rem; height: 1.1rem; accent-color: var(--itca-red); cursor: pointer; }
    .checkbox-group label { margin-bottom: 0; cursor: pointer; font-weight: normal; }
    .btn-itca { width: 100%; padding: 0.85rem; background-color: var(--itca-red); color: #ffffff; border: none; border-radius: 6px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: background-color 0.2s; margin-top: 0.5rem; }
    .btn-itca:hover { background-color: var(--itca-red-dark); }
  </style>
</head>
<body>

  <div class="card-form">
    <div class="header-form">
      <h2>Gestión de Personal</h2>
      <p>Registro y Edición de Cuentas Internas</p>
    </div>

    <div class="body-form">
      <form id="personalForm" action="guardar_personal.php" method="POST">
        
        <div class="form-group">
          <label for="nombre">Nombre Completo</label>
          <input type="text" id="nombre" name="nombre" placeholder="Nombre del empleado" required>
        </div>

        <div class="form-group">
          <label for="correo">Correo Institucional</label>
          <input type="email" id="correo" name="correo" placeholder="empleado@itca.edu.sv" required>
        </div>

        <div class="form-group">
          <label for="rol">Rol Asignado</label>
          <select id="rol" name="rol">
            <option value="admin">Administrador</option>
            <option value="docente">Docente / Profesor</option>
            <option value="asistente">Asistente Administrativo</option>
          </select>
        </div>

        <div class="form-group">
          <label for="password">Contraseña Temporal</label>
          <input type="password" id="password" name="password" placeholder="••••••••" required>
        </div>

        <!-- Tarea #16 y #17: Activar/Desactivar cuenta con confirmación visual -->
        <div class="form-group">
          <div class="checkbox-group">
            <input type="checkbox" id="estado" name="estado" checked>
            <label for="estado">Cuenta Activa en el Sistema</label>
          </div>
        </div>

        <button type="submit" class="btn-itca">Guardar Cuenta</button>
      </form>
    </div>
  </div>

</body>
</html>