<?php
session_start();

// Si ya está logueado, redirigir al dashboard
if (isset($_SESSION['admin_logged']) && $_SESSION['admin_logged'] === true) {
    header('Location: dashboard.php');
    exit;
}

require_once './app/conexion.php';

$error = '';
$shake = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario  = trim($_POST['usuario'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($usuario === '' || $password === '') {
        $error = 'Por favor completa todos los campos.';
        $shake = true;
    } else {
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE usuario = ? AND password = ? LIMIT 1");
        $stmt->execute([$usuario, $password]);
        $user = $stmt->fetch();

        if ($user) {
            $_SESSION['admin_logged']  = true;
            $_SESSION['admin_usuario'] = $user['usuario'];
            $_SESSION['admin_id']      = $user['id_usuario'];
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Usuario o contraseña incorrectos.';
            $shake = true;
        }
    }
}
?>
<!doctype html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Acceso Administrativo — Marca & Medios</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    <style>
        :root {
            --orange: #f05a1a;
            --orange-dark: #d4380d;
            --bg: #080c10;
            --surface: #111720;
            --surface2: #161e28;
            --border: rgba(255, 255, 255, 0.07);
            --border-focus: rgba(240, 90, 26, 0.5);
            --text: #e8edf4;
            --muted: #7a8694;
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html,
        body {
            height: 100%;
        }

        body {
            background: var(--bg);
            color: var(--text);
            font-family: 'DM Sans', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            overflow: hidden;
        }

        /* BG EFFECTS */
        .bg-grid {
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(rgba(255, 255, 255, 0.025) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.025) 1px, transparent 1px);
            background-size: 60px 60px;
            mask-image: radial-gradient(ellipse 80% 80% at 50% 50%, black 30%, transparent 100%);
            pointer-events: none;
        }

        .bg-glow {
            position: fixed;
            inset: 0;
            background:
                radial-gradient(ellipse 60% 50% at 50% 50%, rgba(240, 90, 26, 0.08) 0%, transparent 70%);
            pointer-events: none;
        }

        .blob {
            position: fixed;
            border-radius: 50%;
            filter: blur(90px);
            pointer-events: none;
            animation: blobFloat 10s ease-in-out infinite;
        }

        .blob-1 {
            width: 400px;
            height: 400px;
            background: rgba(240, 90, 26, 0.1);
            top: -150px;
            right: -100px;
        }

        .blob-2 {
            width: 300px;
            height: 300px;
            background: rgba(212, 56, 13, 0.08);
            bottom: -100px;
            left: -80px;
            animation-delay: -5s;
        }

        @keyframes blobFloat {

            0%,
            100% {
                transform: translateY(0) scale(1)
            }

            50% {
                transform: translateY(-20px) scale(1.04)
            }
        }

        /* CARD */
        .login-wrap {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 420px;
            padding: 20px;
            animation: cardIn 0.6s cubic-bezier(0.16, 1, 0.3, 1) both;
        }

        @keyframes cardIn {
            from {
                opacity: 0;
                transform: translateY(24px) scale(0.97);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .login-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 48px 40px 40px;
            position: relative;
            overflow: hidden;
        }

        .login-card::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(ellipse 80% 50% at 50% 0%, rgba(240, 90, 26, 0.06) 0%, transparent 60%);
            pointer-events: none;
        }

        /* TOP DECO */
        .card-top-line {
            position: absolute;
            top: 0;
            left: 10%;
            right: 10%;
            height: 1px;
            background: linear-gradient(to right, transparent, var(--orange), transparent);
            opacity: 0.6;
        }

        /* BRAND */
        .login-brand {
            text-align: center;
            margin-bottom: 36px;
        }

        .brand-icon {
            width: 56px;
            height: 56px;
            background: rgba(240, 90, 26, 0.12);
            border: 1px solid rgba(240, 90, 26, 0.25);
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
        }

        .brand-icon svg {
            width: 26px;
            height: 26px;
            stroke: var(--orange);
            fill: none;
            stroke-width: 1.8;
        }

        .brand-name {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 26px;
            letter-spacing: 3px;
            color: #fff;
            line-height: 1;
        }

        .brand-name span {
            color: var(--orange);
        }

        .brand-sub {
            font-size: 11px;
            color: var(--muted);
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-top: 4px;
        }

        .login-title {
            font-size: 13px;
            color: var(--muted);
            text-align: center;
            margin-top: 10px;
            font-weight: 500;
        }

        /* FORM */
        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: var(--muted);
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .input-wrap {
            position: relative;
        }

        .input-wrap svg {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            width: 16px;
            height: 16px;
            stroke: var(--muted);
            fill: none;
            stroke-width: 2;
            pointer-events: none;
            transition: stroke 0.2s;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            background: var(--surface2);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 13px 14px 13px 42px;
            color: var(--text);
            font-family: 'DM Sans', sans-serif;
            font-size: 15px;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        input:focus {
            border-color: var(--border-focus);
            box-shadow: 0 0 0 3px rgba(240, 90, 26, 0.1);
        }

        input:focus+svg,
        .input-wrap:focus-within svg {
            stroke: var(--orange);
        }

        /* TOGGLE PASSWORD */
        .toggle-pw {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            padding: 4px;
            color: var(--muted);
            display: flex;
            align-items: center;
            transition: color 0.2s;
        }

        .toggle-pw:hover {
            color: var(--text);
        }

        .toggle-pw svg {
            width: 16px;
            height: 16px;
            stroke: currentColor;
            fill: none;
            stroke-width: 2;
        }

        /* ERROR */
        .error-msg {
            display: flex;
            align-items: center;
            gap: 8px;
            background: rgba(240, 90, 26, 0.1);
            border: 1px solid rgba(240, 90, 26, 0.25);
            border-radius: 10px;
            padding: 12px 16px;
            font-size: 13px;
            color: #ff8a5c;
            margin-bottom: 20px;
            animation: errorIn 0.3s ease;
        }

        .error-msg svg {
            width: 16px;
            height: 16px;
            stroke: currentColor;
            fill: none;
            stroke-width: 2;
            flex-shrink: 0;
        }

        @keyframes errorIn {
            from {
                opacity: 0;
                transform: translateY(-6px)
            }

            to {
                opacity: 1;
                transform: translateY(0)
            }
        }

        <?php if ($shake): ?>.login-card {
            animation: shake 0.4s cubic-bezier(0.36, 0.07, 0.19, 0.97);
        }

        @keyframes shake {

            10%,
            90% {
                transform: translateX(-2px);
            }

            20%,
            80% {
                transform: translateX(4px);
            }

            30%,
            50%,
            70% {
                transform: translateX(-6px);
            }

            40%,
            60% {
                transform: translateX(6px);
            }
        }

        <?php endif; ?>

        /* SUBMIT BTN */
        .btn-login {
            width: 100%;
            background: var(--orange);
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: 14px;
            font-family: 'DM Sans', sans-serif;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: background 0.2s, transform 0.15s, box-shadow 0.2s;
            letter-spacing: 0.5px;
            margin-top: 8px;
        }

        .btn-login:hover {
            background: var(--orange-dark);
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(240, 90, 26, 0.35);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .btn-login svg {
            width: 16px;
            height: 16px;
            stroke: currentColor;
            fill: none;
            stroke-width: 2.5;
        }

        /* BACK LINK */
        .back-to-site {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            margin-top: 24px;
            color: var(--muted);
            text-decoration: none;
            font-size: 13px;
            transition: color 0.2s;
        }

        .back-to-site:hover {
            color: var(--text);
        }

        .back-to-site svg {
            width: 14px;
            height: 14px;
            stroke: currentColor;
            fill: none;
            stroke-width: 2.5;
        }
    </style>
</head>

<body>

    <div class="bg-grid"></div>
    <div class="bg-glow"></div>
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>

    <div class="login-wrap">
        <div class="login-card">
            <div class="card-top-line"></div>

            <div class="login-brand">
                <div class="brand-icon">
                    <svg viewBox="0 0 24 24">
                        <rect x="3" y="11" width="18" height="11" rx="2" />
                        <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                    </svg>
                </div>
                <div class="brand-name">MAR<span>CA</span> &amp; MEDIOS</div>
                <div class="brand-sub">Panel Administrativo</div>
                <div class="login-title">Ingresa tus credenciales para continuar</div>
            </div>

            <?php if ($error): ?>
                <div class="error-msg">
                    <svg viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="10" />
                        <line x1="12" y1="8" x2="12" y2="12" />
                        <line x1="12" y1="16" x2="12.01" y2="16" />
                    </svg>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php" autocomplete="off">
                <div class="form-group">
                    <label for="usuario">Usuario</label>
                    <div class="input-wrap">
                        <input
                            type="text"
                            id="usuario"
                            name="usuario"
                            placeholder="Ingresa tu usuario"
                            value="<?= htmlspecialchars($_POST['usuario'] ?? '') ?>"
                            autocomplete="username"
                            required />
                        <svg viewBox="0 0 24 24">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                            <circle cx="12" cy="7" r="4" />
                        </svg>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <div class="input-wrap">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Ingresa tu contraseña"
                            autocomplete="current-password"
                            required />
                        <svg viewBox="0 0 24 24">
                            <rect x="3" y="11" width="18" height="11" rx="2" />
                            <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                        </svg>
                        <button type="button" class="toggle-pw" onclick="togglePassword()" aria-label="Mostrar contraseña">
                            <svg id="eye-icon" viewBox="0 0 24 24">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                <circle cx="12" cy="12" r="3" />
                            </svg>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-login">
                    <svg viewBox="0 0 24 24">
                        <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4" />
                        <polyline points="10 17 15 12 10 7" />
                        <line x1="15" y1="12" x2="3" y2="12" />
                    </svg>
                    Ingresar al panel
                </button>
            </form>

            <a href="index.php" class="back-to-site">
                <svg viewBox="0 0 24 24">
                    <path d="m15 18-6-6 6-6" />
                </svg>
                Volver al sitio
            </a>
        </div>
    </div>

    <script>
        function togglePassword() {
            const input = document.getElementById('password');
            const icon = document.getElementById('eye-icon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>';
            } else {
                input.type = 'password';
                icon.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
            }
        }
    </script>
</body>

</html>