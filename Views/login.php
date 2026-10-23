<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Acceso y Registro - ITCA-FEPADE</title>
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
    .card-login { background-color: #ffffff; width: 100%; max-width: 440px; border-radius: 10px; box-shadow: 0 10px 25px rgba(0,0,0,0.08); overflow: hidden; border-top: 6px solid var(--itca-red); }
    .header-login { background-color: var(--itca-red); color: #ffffff; padding: 2rem 1.5rem; text-align: center; }
    .header-login h1 { font-size: 1.5rem; font-weight: 700; margin-bottom: 0.3rem; }
    .header-login p { font-size: 0.85rem; color: #f3dede; text-transform: uppercase; letter-spacing: 1px; }
    .nav-tabs { display: flex; background-color: #e5e7eb; border-bottom: 1px solid var(--border-color); }
    .nav-tab { flex: 1; padding: 0.85rem; text-align: center; font-weight: 600; font-size: 0.9rem; color: #4b5563; cursor: pointer; background: none; border: none; }
    .nav-tab.active { background-color: #ffffff; color: var(--itca-red); border-bottom: 3px solid var(--itca-red); }
    .body-login { padding: 1.8rem; }
    .form-section { display: none; }
    .form-section.active { display: block; }
    .form-group { margin-bottom: 1.1rem; display: flex; flex-direction: column; }
    .form-group label { font-size: 0.85rem; font-weight: 600; color: var(--text-main); margin-bottom: 0.4rem; }
    .form-group input { padding: 0.7rem 0.9rem; border: 1px solid var(--border-color); border-radius: 6px; font-size: 0.95rem; outline: none; }
    .form-group input:focus { border-color: var(--itca-red); box-shadow: 0 0 0 3px rgba(168, 32, 26, 0.15); }
    .form-group.has-error input { border-color: var(--error-color); background-color: #fffafaca; }
    .error-msg { color: var(--error-color); font-size: 0.78rem; margin-top: 0.3rem; display: none; font-weight: 500; }
    .form-group.has-error .error-msg { display: block; }
    .btn-itca { width: 100%; padding: 0.8rem; background-color: var(--itca-red); color: #ffffff; border: none; border-radius: 6px; font-size: 1rem; font-weight: 600; cursor: pointer; margin-top: 0.5rem; }
    .btn-itca:hover { background-color: var(--itca-red-dark); }
    .footer-login { text-align: center; padding: 1rem; background-color: #fafafa; border-top: 1px solid #f0f0f0; font-size: 0.75rem; color: #666; }
  </style>
</head>
<body>
  <div class="card-login">
    <div class="header-login">
      <h1>ITCA-FEPADE</h1>
      <p>Plataforma de Gestión</p>
    </div>
    <div class="nav-tabs">
      <button class="nav-tab active" id="tabLoginBtn" onclick="switchTab('login')">Iniciar Sesión</button>
      <button class="nav-tab" id="tabRegisterBtn" onclick="switchTab('register')">Registrarse</button>
    </div>
    <div class="body-login">
      <!-- Sección Login -->
      <div class="form-section active" id="sectionLogin">
        <form id="loginForm" action="auth.php" method="POST" novalidate>
          <div class="form-group" id="group-login-user">
            <label for="loginUser">Usuario o Correo Institucional</label>
            <input type="text" id="loginUser" name="identifier" placeholder="ej. kevin.diaz25@itca.edu.sv">
            <span class="error-msg" id="msg-login-user">Este campo es obligatorio.</span>
          </div>
          <div class="form-group" id="group-login-pass">
            <label for="loginPass">Contraseña</label>
            <input type="password" id="loginPass" name="password" placeholder="••••••••">
            <span class="error-msg" id="msg-login-pass">La contraseña es obligatoria.</span>
          </div>
          <button type="submit" class="btn-itca">Acceder</button>
        </form>
      </div>
      <!-- Sección Registro -->
      <div class="form-section" id="sectionRegister">
        <form id="registerForm" action="register.php" method="POST" novalidate>
          <div class="form-group" id="group-reg-name">
            <label for="regName">Nombre Completo</label>
            <input type="text" id="regName" name="fullname" placeholder="Tu nombre completo">
            <span class="error-msg" id="msg-reg-name">El nombre es obligatorio.</span>
          </div>
          <div class="form-group" id="group-reg-email">
            <label for="regEmail">Correo Institucional</label>
            <input type="email" id="regEmail" name="email" placeholder="usuario@itca.edu.sv">
            <span class="error-msg" id="msg-reg-email">Ingrese un correo válido.</span>
          </div>
          <div class="form-group" id="group-reg-pass">
            <label for="regPass">Contraseña</label>
            <input type="password" id="regPass" name="password" placeholder="Mínimo 6 caracteres">
            <span class="error-msg" id="msg-reg-pass">La contraseña debe tener al menos 6 caracteres.</span>
          </div>
          <button type="submit" class="btn-itca">Crear Cuenta</button>
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