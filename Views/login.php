<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Acceso y Registro Completo - ITCA-FEPADE</title>
  <style>
    :root {
      --itca-red: #a8201a;
      --itca-red-dark: #841813;
      --itca-bg: #f5f6f8;
      --text-main: #333333;
      --border-color: #d1d5db;
      --error-color: #dc2626;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
    body { background-color: var(--itca-bg); display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 1rem; }
    .card-login { background-color: #ffffff; width: 100%; max-width: 480px; border-radius: 10px; box-shadow: 0 10px 25px rgba(0,0,0,0.08); overflow: hidden; border-top: 6px solid var(--itca-red); }
    .header-login { background-color: var(--itca-red); color: #ffffff; padding: 1.8rem 1.5rem; text-align: center; }
    .header-login h1 { font-size: 1.4rem; font-weight: 700; margin-bottom: 0.3rem; }
    .header-login p { font-size: 0.85rem; color: #f3dede; text-transform: uppercase; letter-spacing: 1px; }
    .nav-tabs { display: flex; background-color: #e5e7eb; border-bottom: 1px solid var(--border-color); }
    .nav-tab { flex: 1; padding: 0.85rem; text-align: center; font-weight: 600; font-size: 0.9rem; color: #4b5563; cursor: pointer; background: none; border: none; }
    .nav-tab.active { background-color: #ffffff; color: var(--itca-red); border-bottom: 3px solid var(--itca-red); }
    .body-login { padding: 1.8rem; max-height: 75vh; overflow-y: auto; }
    .form-section { display: none; }
    .form-section.active { display: block; }
    .form-group { margin-bottom: 1rem; display: flex; flex-direction: column; }
    .form-group label { font-size: 0.85rem; font-weight: 600; color: var(--text-main); margin-bottom: 0.3rem; }
    .form-group input, .form-group select { padding: 0.65rem 0.9rem; border: 1px solid var(--border-color); border-radius: 6px; font-size: 0.95rem; outline: none; }
    .form-group input:focus, .form-group select:focus { border-color: var(--itca-red); box-shadow: 0 0 0 3px rgba(168, 32, 26, 0.15); }
    .btn-itca { width: 100%; padding: 0.8rem; background-color: var(--itca-red); color: #ffffff; border: none; border-radius: 6px; font-size: 1rem; font-weight: 600; cursor: pointer; margin-top: 0.5rem; }
    .btn-itca:hover { background-color: var(--itca-red-dark); }
    .footer-login { text-align: center; padding: 1rem; background-color: #fafafa; border-top: 1px solid #f0f0f0; font-size: 0.75rem; color: #666; }
  </style>
</head>
<body>
  <div class="card-login">
    <div class="header-login">
      <h1>ITCA-FEPADE</h1>
      <p>Plataforma de Gestión Académica</p>
    </div>
    
    <div class="nav-tabs">
      <button class="nav-tab active" id="tabLoginBtn" onclick="switchTab('login')">Iniciar Sesión</button>
      <button class="nav-tab" id="tabRegisterBtn" onclick="switchTab('register')">Registro Completo</button>
    </div>

    <div class="body-login">
      <!-- Sección Login -->
      <div class="form-section active" id="sectionLogin">
        <form id="loginForm" action="auth.php" method="POST">
          <div class="form-group">
            <label for="loginUser">Correo Institucional o Carnet</label>
            <input type="text" id="loginUser" name="identifier" placeholder="ej. kevin.diaz@itca.edu.sv" required>
          </div>
          <div class="form-group">
            <label for="loginPass">Contraseña</label>
            <input type="password" id="loginPass" name="password" placeholder="••••••••" required>
          </div>
          <button type="submit" class="btn-itca">Acceder al Sistema</button>
        </form>
      </div>

      <!-- Sección Registro Completo -->
      <div class="form-section" id="sectionRegister">
        <form id="registerForm" action="register.php" method="POST">
          <div class="form-group">
            <label for="regName">Nombre Completo</label>
            <input type="text" id="regName" name="fullname" placeholder="Tus nombres y apellidos" required>
          </div>

          <div class="form-group">
            <label for="regCarnet">Número de Carnet</label>
            <input type="text" id="regCarnet" name="carnet" placeholder="ej. 00012324" required>
          </div>

          <div class="form-group">
            <label for="regEmail">Correo Institucional</label>
            <input type="email" id="regEmail" name="email" placeholder="usuario@itca.edu.sv" required>
          </div>

          <div class="form-group">
            <label for="regCareer">Carrera</label>
            <select id="regCareer" name="career" required>
              <option value="">Seleccione su carrera...</option>
              <option value="sistemas">Ingeniería Electrica</option>
              <option value="software">Técnico en Desarrollo de Software</option>
              <option value="mantenimiento">Hardware Computacional</option>
              <option value="otro">Otra Carrera</option>
            </select>
          </div>
          <div class="form-group">
            <label for="regCareer">Carrera</label>
            <select id="regCareer" name="career" required>
              <option value="">Año</option>
              <option value="sistemas">Primer año</option>
              <option value="sistemas">Segundo año</option>
            </select>
          </div>

          <div class="form-group">
            <label for="regPass">Contraseña</label>
            <input type="password" id="regPass" name="password" placeholder="Mínimo 6 caracteres" required>
          </div>

          <button type="submit" class="btn-itca">Registrarse y Guardar Datos</button>
        </form>
      </div>
    </div>

    <div class="footer-login">&copy; <?php echo date('Y'); ?> ITCA-FEPADE</div>
  </div>

  <script>
    function switchTab(tabName) {
      document.getElementById('sectionLogin').classList.toggle('active', tabName === 'login');
      document.getElementById('sectionRegister').classList.toggle('active', tabName === 'register');
      document.getElementById('tabLoginBtn').classList.toggle('active', tabName === 'login');
      document.getElementById('tabRegisterBtn').classList.toggle('active', tabName === 'register');
    }
  </script>
</body>
</html>