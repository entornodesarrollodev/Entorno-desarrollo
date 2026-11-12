<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Generar Diplomas - ITCA-FEPADE</title>
  <link rel="stylesheet" href="css/estudiantes.css">
  <style>
    .split-grid { display: grid; grid-template-columns: 1fr 1.2fr; gap: 1.5rem; margin-bottom: 1.5rem; }
    .cert-preview { background: #fff; border: 2px solid #e5e7eb; border-radius: 6px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.06); text-align: center; }
    .cert-banner { background: var(--itca-red); color: #fff; padding: 0.9rem; font-weight: 700; letter-spacing: 1px; border-bottom: 4px solid #d97706; }
    .cert-body { padding: 1.8rem 1.5rem; }
    .cert-title { color: var(--itca-red); font-size: 1.5rem; font-weight: 800; letter-spacing: 1px; margin-bottom: 0.4rem; }
    .cert-student { font-family: 'Georgia', serif; font-style: italic; font-size: 1.45rem; font-weight: 700; color: #111827; margin: 0.6rem 0; border-bottom: 1px solid #d1d5db; display: inline-block; padding: 0 1.5rem 0.2rem; }
    .cert-text { font-size: 0.85rem; color: #374151; line-height: 1.5; max-width: 460px; margin: 0.5rem auto 1.5rem; }
    .cert-signatures { display: flex; justify-content: space-around; align-items: flex-end; margin-top: 1.5rem; font-size: 0.75rem; color: #4b5563; }
    .sig-line { border-top: 1px solid #374151; width: 140px; padding-top: 0.3rem; }
    .seal-badge { width: 46px; height: 46px; border-radius: 50%; background: #fef3c7; border: 2px solid #d97706; color: #d97706; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; }
  </style>
</head>
<body>
  <aside class="sidebar">
    <div class="sidebar-brand"><h2>ITCA FEPADE</h2><p>Sistema de Proyección Social</p></div>
    <ul class="sidebar-nav">
      <li><a href="dashboard_licenciada.php">🏠 Panel principal</a></li>
      <li><a href="estudiantes_listado.php">🎓 Estudiantes</a></li>
      <li><a href="proyectos_asignar.php">📂 Proyectos</a></li>
      <li><a href="calendario_actividades.php">📅 Calendario</a></li>
      <li><a href="diplomas_generar.php" class="active">🏅 Diplomas</a></li>
      <li><a href="cartas_participacion.php">📄 Cartas de participación</a></li>
    </ul>
  </aside>

  <div class="main-wrapper">
    <header class="topbar"><span>👤 Licenciada</span></header>

    <main class="content-area">
      <div class="breadcrumb">Diplomas > Generar diplomas</div>
      <h2 style="margin-top: 0.4rem;">🏅 Previsualizar y generar diplomas</h2>
      <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 1.5rem;">Configura la información, previsualiza el diploma y genera los diplomas de los estudiantes.</p>

      <div class="split-grid">
        <!-- Configuración -->
        <div class="form-card" style="margin: 0;">
          <h4 style="color: var(--itca-red); margin-bottom: 1rem;">⚙️ Configuración del diploma</h4>
          <div class="form-group" style="margin-bottom: 1rem;">
            <label>Proyecto *</label>
            <select style="width: 100%; padding: 0.6rem; border: 1px solid var(--border-color); border-radius: 6px;">
              <option>Apoyo Comunitario en la Zona Norte</option>
            </select>
          </div>
          <div class="form-group" style="margin-bottom: 1rem;">
            <label>Actividad *</label>
            <select style="width: 100%; padding: 0.6rem; border: 1px solid var(--border-color); border-radius: 6px;">
              <option>Jornada de limpieza</option>
            </select>
          </div>
          <div class="form-group" style="margin-bottom: 1rem;">
            <label>Plantilla *</label>
            <select style="width: 100%; padding: 0.6rem; border: 1px solid var(--border-color); border-radius: 6px;">
              <option>Diploma institucional (ITCA-FEPADE)</option>
            </select>
          </div>
          <div class="form-group">
            <label>Fecha de emisión *</label>
            <input type="date" value="2026-11-15" style="width: 100%; padding: 0.6rem; border: 1px solid var(--border-color); border-radius: 6px;">
          </div>
        </div>

        <!-- Vista Previa -->
        <div class="form-card" style="margin: 0; background: #f8fafc;">
          <h4 style="color: var(--itca-red); margin-bottom: 1rem;">👁️ Vista previa del diploma</h4>
          <div class="cert-preview">
            <div class="cert-banner">ITCA-FEPADE</div>
            <div class="cert-body">
              <div class="cert-title">DIPLOMA</div>
              <div style="font-size: 0.8rem; color: #6b7280;">Se otorga el presente diploma a:</div>
              <div class="cert-student">Carlos Alberto Mejía Rivera</div>
              <p class="cert-text">
                Por su participación y cumplimiento en la actividad <strong>"Jornada de limpieza"</strong> desarrollada en el marco del proyecto <strong>"Apoyo Comunitario en la Zona Norte"</strong>.
              </p>
              <div class="cert-signatures">
                <div class="sig-line">Lic. Coordinador(a)<br>Proyección Social</div>
                <div class="seal-badge">★</div>
                <div class="sig-line">Licenciada<br>Responsable</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Tabla de Estudiantes -->
      <div class="form-card" style="padding: 0; overflow: hidden;">
        <div style="padding: 1rem; background: var(--bg-main); border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
          <strong>👥 Estudiantes a generar diploma</strong>
          <div style="display: flex; gap: 0.8rem; align-items: center;">
            <input type="text" placeholder="🔍 Buscar estudiante..." style="padding: 0.45rem 0.8rem; border: 1px solid var(--border-color); border-radius: 6px; font-size: 0.85rem;">
            <label style="font-size: 0.85rem; font-weight: 600; cursor: pointer;"><input type="checkbox" checked> Seleccionar todos</label>
          </div>
        </div>
        <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.88rem;">
          <thead style="background: #f8fafc; border-bottom: 1px solid var(--border-color);">
            <tr>
              <th style="padding: 0.8rem 1rem; width: 40px;"><input type="checkbox" checked></th>
              <th style="padding: 0.8rem 1rem;">Carnet</th>
              <th style="padding: 0.8rem 1rem;">Nombre del estudiante</th>
              <th style="padding: 0.8rem 1rem;">Proyecto</th>
              <th style="padding: 0.8rem 1rem;">Actividad</th>
              <th style="padding: 0.8rem 1rem;">Estado</th>
            </tr>
          </thead>
          <tbody>
            <tr style="border-bottom: 1px solid var(--border-color);">
              <td style="padding: 0.8rem 1rem;"><input type="checkbox" checked></td>
              <td style="padding: 0.8rem 1rem;">20260001</td>
              <td style="padding: 0.8rem 1rem; font-weight: 500;">Carlos Alberto Mejía Rivera</td>
              <td style="padding: 0.8rem 1rem;">Apoyo Comunitario en la Zona Norte</td>
              <td style="padding: 0.8rem 1rem;">Jornada de limpieza</td>
              <td style="padding: 0.8rem 1rem;"><span style="background: #dcfce7; color: #16a34a; padding: 0.2rem 0.6rem; border-radius: 4px; font-size: 0.8rem;">✔️ Cumplido</span></td>
            </tr>
            <tr style="border-bottom: 1px solid var(--border-color);">
              <td style="padding: 0.8rem 1rem;"><input type="checkbox" checked></td>
              <td style="padding: 0.8rem 1rem;">20260015</td>
              <td style="padding: 0.8rem 1rem; font-weight: 500;">Brayan Estiven Vásquez Linares</td>
              <td style="padding: 0.8rem 1rem;">Apoyo Comunitario en la Zona Norte</td>
              <td style="padding: 0.8rem 1rem;">Jornada de limpieza</td>
              <td style="padding: 0.8rem 1rem;"><span style="background: #dcfce7; color: #16a34a; padding: 0.2rem 0.6rem; border-radius: 4px; font-size: 0.8rem;">✔️ Cumplido</span></td>
            </tr>
            <tr>
              <td style="padding: 0.8rem 1rem;"><input type="checkbox" checked></td>
              <td style="padding: 0.8rem 1rem;">20260028</td>
              <td style="padding: 0.8rem 1rem; font-weight: 500;">Andrea Sofía Gómez Torres</td>
              <td style="padding: 0.8rem 1rem;">Apoyo Comunitario en la Zona Norte</td>
              <td style="padding: 0.8rem 1rem;">Jornada de limpieza</td>
              <td style="padding: 0.8rem 1rem;"><span style="background: #dcfce7; color: #16a34a; padding: 0.2rem 0.6rem; border-radius: 4px; font-size: 0.8rem;">✔️ Cumplido</span></td>
            </tr>
          </tbody>
        </table>
        <div style="padding: 1rem; background: #f8fafc; border-top: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
          <span style="font-size: 0.85rem; color: var(--text-muted);">3 estudiantes seleccionados</span>
          <div style="display: flex; gap: 0.8rem;">
            <button type="button" style="background: #fff; border: 1px solid var(--border-color); padding: 0.55rem 1rem; border-radius: 6px; cursor: pointer; font-weight: 600;">👁️ Previsualizar</button>
            <button type="button" style="background: var(--itca-red); color: #fff; border: none; padding: 0.55rem 1.2rem; border-radius: 6px; cursor: pointer; font-weight: 600;">📥 Generar diplomas (3)</button>
          </div>
        </div>
      </div>
    </main>
  </div>
</body>
</html>