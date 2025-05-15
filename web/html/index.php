<?php
session_start();
require 'config.php';

// Logout
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header('Location: index.php');
    exit;
}

$errors = [];
// Handle registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'register') {
    $u = trim($_POST['username'] ?? '');
    $e = trim($_POST['email'] ?? '');
    $p = $_POST['password'] ?? '';
    if (!$u || !$e || !$p) {
        $errors[] = 'Todos los campos son obligatorios.';
    } elseif (!filter_var($e, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email inválido.';
    } else {
        $st = $pdo->prepare('SELECT id FROM users WHERE username=? OR email=?');
        $st->execute([$u, $e]);
        if ($st->fetch()) {
            $errors[] = 'Usuario o email ya registrado.';
        } else {
            $hash = password_hash($p, PASSWORD_DEFAULT);
            $st = $pdo->prepare('INSERT INTO users (username,email,password) VALUES (?,?,?)');
            $st->execute([$u, $e, $hash]);
            header('Location: index.php');
            exit;
        }
    }
}
// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'login') {
    $u = trim($_POST['username'] ?? '');
    $p = $_POST['password'] ?? '';
    if (!$u || !$p) {
        $errors[] = 'Usuario y contraseña son obligatorios.';
    } else {
        $st = $pdo->prepare('SELECT password FROM users WHERE username=?');
        $st->execute([$u]);
        $row = $st->fetch();
        if ($row && password_verify($p, $row['password'])) {
            $_SESSION['user'] = $u;
            header('Location: index.php');
            exit;
        } else {
            $errors[] = 'Credenciales incorrectas.';
        }
    }
}

$user = $_SESSION['user'] ?? null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Historia del Fútbol</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet" />
  <style>
    :root {
      --verde-futbol: #145a32;
      --gris: #f4f4f4;
      --texto: #222;
      --blanco: #fff;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Inter', sans-serif; background: var(--gris); color: var(--texto); min-height:100vh; display:flex; flex-direction:column; }
    header { background: var(--verde-futbol); color: var(--blanco); text-align: center; padding: 20px; font-size: 28px; font-weight: bold; position: relative; }
    header .user { position: absolute; top: 20px; right: 20px; font-size: 14px; }
    header .user a { color: var(--blanco); text-decoration: none; margin-left:10px; }
    nav { background: var(--blanco); box-shadow: 0 2px 5px rgba(0,0,0,0.05); position: sticky; top: 0; z-index: 10; }
    nav ul { display: flex; justify-content: center; list-style: none; gap: 20px; flex-wrap: wrap; padding: 10px 0; }
    nav a { text-decoration: none; color: var(--texto); font-weight: 600; padding: 5px 10px; border-radius: 5px; transition: background 0.2s; }
    nav a:hover, nav a.active { background: #e9f5ec; }
    .container { flex:1; max-width: 1000px; margin: 30px auto; padding: 0 20px; }
    .section { display: none; animation: fadeIn 0.4s ease; }
    .section.active { display: block; }
    .section h2 { color: var(--verde-futbol); margin-bottom: 15px; font-size: 24px; border-left: 5px solid var(--verde-futbol); padding-left: 10px; }
    .card { background: var(--blanco); border: 1px solid #ddd; border-radius: 6px; padding: 15px 20px; margin-bottom: 20px; box-shadow: 0 1px 4px rgba(0,0,0,0.06); }
    .card h3 { color: #d35400; font-size: 18px; margin-bottom: 10px; text-transform: uppercase; }
    .card p { margin-bottom: 10px; text-align: justify; }
    #loader { display: none; text-align: center; font-size: 18px; padding: 30px; animation: fadeIn 0.3s ease-in-out; }
    footer { text-align: center; padding: 20px; font-size: 13px; color: #777; background: var(--blanco); border-top: 1px solid #eaeaea; }
    @keyframes fadeIn { from { opacity: 0; transform: scale(0.98); } to { opacity: 1; transform: scale(1); } }
    @media (max-width: 768px) { nav ul { flex-direction: column; align-items: center; } }
    /* Auth styles */
    .auth-container { max-width:400px; margin:50px auto; padding:20px; background:#fff; box-shadow:0 2px 5px rgba(0,0,0,0.1); border-radius:4px; }
    .auth-container h2 { text-align:center; color:var(--verde-futbol); margin-bottom:20px; }
    .auth-container label { display:block; font-weight:600; margin-bottom:5px; }
    .auth-container input { width:100%; padding:8px; margin-bottom:15px; border:1px solid #ccc; border-radius:3px; }
    .auth-container button { width:100%; padding:10px; background:var(--verde-futbol); color:#fff; border:none; border-radius:3px; cursor:pointer; font-size:16px; }
    .auth-container .errors { color:red; margin-bottom:15px; }
    .auth-container .toggle { text-align:center; margin-bottom:20px; }
    .auth-container .toggle a { margin:0 10px; color:#333; text-decoration:none; }
    .auth-container .toggle a.active { color:var(--verde-futbol); font-weight:700; }
  </style>
</head>
<body>

<?php if (!$user): ?>
  <?php $mode = $_GET['mode'] ?? 'login'; ?>
  <div class="auth-container">
    <div class="toggle">
      <a href="?mode=login" class="<?= $mode==='login'?'active':'' ?>">Login</a>
      <a href="?mode=register" class="<?= $mode==='register'?'active':'' ?>">Registro</a>
    </div>
    <?php if ($errors): ?>
      <div class="errors">
        <?php foreach ($errors as $err): ?>
          <div><?= htmlspecialchars($err) ?></div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
    <?php if ($mode==='register'): ?>
      <h2>Registro</h2>
      <form method="post">
        <input type="hidden" name="action" value="register">
        <label>Usuario</label><input name="username" required>
        <label>Email</label><input name="email" type="email" required>
        <label>Contraseña</label><input name="password" type="password" required>
        <button type="submit">Registrar</button>
      </form>
    <?php else: ?>
      <h2>Login</h2>
      <form method="post">
        <input type="hidden" name="action" value="login">
        <label>Usuario</label><input name="username" required>
        <label>Contraseña</label><input name="password" type="password" required>
        <button type="submit">Entrar</button>
      </form>
    <?php endif; ?>
  </div>
<?php else: ?>
  <header>
    Historia del Fútbol
    <div class="user">
      <?= htmlspecialchars($user) ?> |
      <a href="?logout=1">Cerrar sesión</a>
    </div>
  </header>
  <nav>
    <ul>
      <li><a href="#" data-section="introduccion" class="active">Introducción</a></li>
      <li><a href="#" data-section="origenes">Orígenes</a></li>
      <li><a href="#" data-section="evolucion">Evolución</a></li>
      <li><a href="#" data-section="momentos">Grandes Momentos</a></li>
      <li><a href="#" data-section="reglas">Reglas y Curiosidades</a></li>
    </ul>
  </nav>

  <div class="container">
    <div id="loader">Cargando sección...</div>

    <section id="introduccion" class="section active">
      <h2>Introducción</h2>
      <div class="card"><h3>Pasión Mundial</h3><p>El fútbol es el deporte más popular del mundo, practicado en casi todos los países y con una audiencia que supera los miles de millones de personas.</p></div>
      <div class="card"><h3>Evolución</h3><p>Ha evolucionado desde juegos con pelotas rudimentarias hasta convertirse en una industria con ligas profesionales y millones de seguidores.</p></div>
      <div class="card"><h3>Impacto Social</h3><p>El fútbol ha impactado política, economía y cultura en muchas naciones.</p></div>
    </section>

    <section id="origenes" class="section">
      <h2>Orígenes del Fútbol</h2>
      <div class="card"><h3>Antigüedad</h3><p>Civilizaciones como China, Grecia y culturas mesoamericanas practicaban juegos con pelota.</p></div>
      <div class="card"><h3>Inglaterra</h3><p>Durante la Edad Media se jugaban variantes desorganizadas. En 1863 nace la Football Association y el reglamento moderno.</p></div>
      <div class="card"><h3>Expansión</h3><p>El fútbol se expandió gracias al Imperio Británico hacia Europa, América, África y Asia.</p></div>
    </section>

    <section id="evolucion" class="section">
      <h2>Evolución del Deporte</h2>
      <div class="card"><h3>Organización</h3><p>La FIFA fue fundada en 1904. La Copa Mundial comenzó en 1930. El fútbol profesional creció rápidamente.</p></div>
      <div class="card"><h3>Modernización</h3><p>Con la TV y tecnologías modernas, el deporte se volvió un fenómeno global.</p></div>
      <div class="card"><h3>Fútbol Femenino</h3><p>Crecimiento notable en las últimas décadas con torneos internacionales y apoyo institucional.</p></div>
    </section>

    <section id="momentos" class="section">
      <h2>Grandes Momentos Históricos</h2>
      <div class="card"><h3>Maracanazo</h3><p>Uruguay venció a Brasil en 1950. Un antes y un después en la historia del deporte.</p></div>
      <div class="card"><h3>Mano de Dios</h3><p>Maradona dejó su huella en 1986 con dos goles legendarios ante Inglaterra.</p></div>
      <div class="card"><h3>España 2010</h3><p>España conquistó su primer Mundial con un estilo de juego único: el tiki-taka.</p></div>
    </section>

    <section id="reglas" class="section">
      <h2>Reglas y Curiosidades</h2>
      <div class="card"><h3>Reglas Básicas</h3><p>Dos equipos de 11, 90 minutos de partido, árbitro y reglas definidas.</p></div>
      <div class="card"><h3>VAR</h3><p>Introducido en 2018 en el Mundial. Revolucionó el arbitraje moderno.</p></div>
      <div class="card"><h3>Curiosidades</h3><p>Primer balón de vejiga animal, más de 200 federaciones afiliadas a la FIFA.</p></div>
    </section>
  </div>

  <footer>
    &copy; <?php echo date('Y'); ?> Historia del Fútbol | Página educativa interactiva
  </footer>

  <script>
    const navLinks = document.querySelectorAll('nav a');
    const sections = document.querySelectorAll('.section');
    const loader = document.getElementById('loader');

    navLinks.forEach(link => {
      link.addEventListener('click', e => {
        e.preventDefault();
        const target = link.dataset.section;

        navLinks.forEach(l => l.classList.remove('active'));
        link.classList.add('active');

        sections.forEach(s => s.classList.remove('active'));
        loader.style.display = 'block';

        setTimeout(() => {
          loader.style.display = 'none';
          document.getElementById(target).classList.add('active');
        }, 500);
      });
    });
  </script>
<?php endif; ?>

</body>
</html>

