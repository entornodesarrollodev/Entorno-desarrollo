<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Administración de Roles - ITCA-FEPADE</title>
  <style>
    :root {
      --itca-red: #a8201a;
      --itca-red-dark: #841813;
      --bg-main: #f5f6f8;
      --text-main: #333333;
      --border-color: #e5e7eb;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
    body { background-color: var(--bg-main); color: var(--text-main); padding: 2rem; }
    
    .container { max-width: 1000px; margin: 0 auto; background: #ffffff; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); overflow: hidden; border-top: 6px solid var(--itca-red); }
    
    .header-section { padding: 1.5rem 2rem; background-color: #fafafa; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; }
    .header-section h2 { color: var(--itca-red); font-size: 1.4rem; }
    
    .table-container { width: 100%; overflow-x: auto; }
    table { width: 100%; border-collapse: collapse; text-align: left; font-size: 0.95rem; }
    th, td { padding: 1rem 1.5rem; border-bottom: 1px solid var(--border-color); }
    th { background-color: #f9fafb; font-weight: 600; color: #4b5563; }
    
    .badge { padding: 0.35rem 0.75rem; border-radius: 50px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; }
    .badge.admin { background-color: #fee2e2; color: #991b1b; }
    .badge.docente { background-color: #dbeafe; color: #1e40af; }
    .badge.estudiante { background-color: #dcfce7; color: #166534; }
    
    .btn-action { padding: 0.4rem 0.8rem; border-radius: 4px; font-size: 0.85rem; font-weight: 600; cursor: pointer; border: none; transition: background 0.2s; }
    .btn-edit { background-color: #f3f4f6; color: #374151; margin-right: 0.5rem; }
    .btn-edit:hover { background-color: #e5e7eb; }
    .btn-delete { background-color: #fee2e2; color: #991b1b; }
    .btn-delete:hover { background-color: #fecaca; }
  </style>
</head>
<body>

  <div class="container">
    <div class="header-section">
      <h2>Gestión de Usuarios y Roles</h2>
    </div>

    <div class="table-container">
      <table>
        <thead>
          <tr>
            <th>Nombre</th>
            <th>Correo Electrónico</th>
            <th>Rol Actual</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>Kevin Díaz</td>
            <td>kevin.diaz@itca.edu.sv</td>
            <td><span class="badge admin">Administrador</span></td>
            <td>
              <button class="btn-action btn-edit">Editar</button>
              <button class="btn-action btn-delete">Desactivar</button>
            </td>
          </tr>
          <tr>
            <td>María Pérez</td>
            <td>maria.perez@itca.edu.sv</td>
            <td><span class="badge docente">Docente</span></td>
            <td>
              <button class="btn-action btn-edit">Editar</button>
              <button class="btn-action btn-delete">Desactivar</button>
            </td>
          </tr>
          <tr>
            <td>Juan López</td>
            <td>juan.lopez@itca.edu.sv</td>
            <td><span class="badge estudiante">Estudiante</span></td>
            <td>
              <button class="btn-action btn-edit">Editar</button>
              <button class="btn-action btn-delete">Desactivar</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

</body>
</html>