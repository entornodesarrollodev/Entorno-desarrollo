<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Registro de Estudiante - ITCA-FEPADE</title>
  <style>
    :root { --itca-red: #a8201a; --itca-red-dark: #841813; --bg-main: #f5f6f8; }
    * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', sans-serif; }
    body { background-color: var(--bg-main); display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 1.5rem; }
    .form-card { background: white; width: 100%; max-width: 550px; padding: 2.5rem; border-radius: 10px; box-shadow: 0 10px 25px rgba(0,0,0,0.08); border-top: 6px solid var(--itca-red); }
    .form-card h2 { color: var(--itca-red); margin-bottom: 1.5rem; text-align: center; }
    .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
    .form-group { margin-bottom: 1rem; display: flex; flex-direction: column; grid-column: span 2; }
    .form-group.half { grid-column: span 1; }
    .form-group label { font-size: 0.85rem; font-weight: 600; margin-bottom: 0.3rem; color: #333; }
    .form-group input, .form-group select { padding: 0.7rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.95rem; outline: none; }
    .form-group input:focus, .form-group select:focus { border-color: var(--itca-red); }
    .btn-submit { width: 100%; padding: 0.8rem; background-color: var(--itca-red); color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; margin-top: 1rem; }
    .btn-submit:hover { background-color: var(--itca-red-dark); }
  </style>
</head>
<body>
  <div class="form-card">
    <h2>Registro de Estudiante</h2>
    <form action="guardar_estudiante.php" method="POST">
      <div class="form-group">
        <label for="fullname">Nombre Completo</label>
        <input type="text" id="fullname" name="fullname" required placeholder="Nombre y Apellidos">
      </div>
      <div class="form-group half">
        <label for="email">Correo Institucional</label>
        <input type="email" id="email" name="email" required placeholder="usuario@itca.edu.sv">
      </div>
      <div class="form-group half">
        <label for="phone">Teléfono de Contacto</label>
        <input type="tel" id="phone" name="phone" placeholder="0000-0000">
      </div>
      <div class="form-group half">
        <label for="career">Carrera</label>
        <select id="career" name="career">
          <option value="software">Ingenieria en desarrollo Software</option>
          <option value="software">Ingenieria electrica</option>
          <option value="software">Hardware computacional</option>
        </select>
      </div>
      <div class="form-group half">
        <label for="year">Año Académico</label>
        <select id="year" name="year">
          <option value="1">Primer Año</option>
          <option value="2">Segundo Año</option>
        </select>
      </div>
      <button type="submit" class="btn-submit">Guardar Estudiante</button>
    </form>
  </div>
</body>
</html>