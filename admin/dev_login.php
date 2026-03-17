<?php
session_start();

// ── Credenciales del DESARROLLADOR (solo tú las sabes) ────────────────────────
// Cambia estos valores a algo que solo tú conozcas
define('DEV_USER', 'edutech');
define('DEV_PASS', 'Shirvid0911');
define('DEV_SESSION_KEY', 'dev_master_logged');

// Si ya está logueado, ir al cpanel
if (isset($_SESSION[DEV_SESSION_KEY]) && $_SESSION[DEV_SESSION_KEY] === true) {
    header('Location: cpanel.php');
    exit;
}

$error = '';
$shake = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = trim($_POST['usuario']  ?? '');
    $p = trim($_POST['password'] ?? '');

    if ($u === DEV_USER && $p === DEV_PASS) {
        $_SESSION[DEV_SESSION_KEY]    = true;
        $_SESSION['dev_usuario']      = $u;
        $_SESSION['dev_login_time']   = time();
        header('Location: cpanel.php');
        exit;
    } else {
        $error = 'Credenciales incorrectas.';
        $shake = true;
        // Log intento fallido (opcional)
        error_log('[DEV LOGIN FAIL] IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . ' | Time: ' . date('Y-m-d H:i:s'));
    }
}
?>
<!doctype html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1.0" />
    <title>Acceso — Sistema de Control</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    <style>
        :root {
            --red: #e63946;
            --red-dk: #b71c2a;
            --bg: #06080b;
            --surf: #0f1318;
            --surf2: #161c24;
            --border: rgba(255, 255, 255, 0.06);
            --text: #e2e8f0;
            --muted: #64748b;
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

        /* BG */
        .bg-noise {
            position: fixed;
            inset: 0;
            pointer-events: none;
            z-index: 0;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.04'/%3E%3C/svg%3E");
            background-repeat: repeat;
            background-size: 200px;
        }

        .bg-glow {
            position: fixed;
            inset: 0;
            pointer-events: none;
            z-index: 0;
            background: radial-gradient(ellipse 55% 45% at 50% 50%, rgba(230, 57, 70, 0.06) 0%, transparent 70%);
        }

        .scan-line {
            position: fixed;
            inset: 0;
            pointer-events: none;
            z-index: 0;
            background: repeating-linear-gradient(0deg, transparent, transparent 2px, rgba(0, 0, 0, 0.03) 2px, rgba(0, 0, 0, 0.03) 4px);
        }

        /* CARD */
        .card {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 400px;
            margin: 20px;
            background: var(--surf);
            border: 1px solid var(--border);
            border-radius: 16px;
            overflow: hidden;
            animation: cardIn 0.5s cubic-bezier(0.16, 1, 0.3, 1) both;
        }

        @keyframes cardIn {
            from {
                opacity: 0;
                transform: translateY(20px) scale(0.97);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        <?php if ($shake): ?>.card {
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
                transform: translateX(-5px);
            }

            40%,
            60% {
                transform: translateX(5px);
            }
        }

        <?php endif; ?>

        /* TOP ACCENT */
        .card-top {
            height: 3px;
            background: linear-gradient(90deg, var(--red-dk), var(--red), #ff6b6b, var(--red));
            background-size: 200% 100%;
            animation: gradShift 3s linear infinite;
        }

        @keyframes gradShift {
            to {
                background-position: -200% 0;
            }
        }

        .card-body {
            padding: 36px 32px 32px;
        }

        /* ICON */
        .lock-icon {
            width: 52px;
            height: 52px;
            background: rgba(230, 57, 70, 0.1);
            border: 1px solid rgba(230, 57, 70, 0.2);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }

        .lock-icon svg {
            width: 24px;
            height: 24px;
            stroke: var(--red);
            fill: none;
            stroke-width: 2;
        }

        h1 {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 26px;
            letter-spacing: 3px;
            color: #fff;
            text-align: center;
            line-height: 1;
            margin-bottom: 4px;
        }

        .sub {
            text-align: center;
            font-size: 11px;
            color: var(--muted);
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-bottom: 28px;
        }

        /* ERROR */
        .err {
            display: flex;
            align-items: center;
            gap: 8px;
            background: rgba(230, 57, 70, 0.1);
            border: 1px solid rgba(230, 57, 70, 0.25);
            border-radius: 9px;
            padding: 11px 14px;
            font-size: 13px;
            color: #ff8a8a;
            margin-bottom: 20px;
            animation: errIn 0.3s ease;
        }

        @keyframes errIn {
            from {
                opacity: 0;
                transform: translateY(-6px)
            }

            to {
                opacity: 1;
                transform: translateY(0)
            }
        }

        .err svg {
            width: 15px;
            height: 15px;
            stroke: currentColor;
            fill: none;
            stroke-width: 2;
            flex-shrink: 0;
        }

        /* FIELDS */
        .field {
            margin-bottom: 16px;
        }

        .field label {
            display: block;
            font-size: 10px;
            font-weight: 700;
            color: var(--muted);
            letter-spacing: 1.5px;
            text-transform: uppercase;
            margin-bottom: 7px;
        }

        .input-wrap {
            position: relative;
        }

        .input-wrap svg.icon {
            position: absolute;
            left: 13px;
            top: 50%;
            transform: translateY(-50%);
            width: 15px;
            height: 15px;
            stroke: var(--muted);
            fill: none;
            stroke-width: 2;
            pointer-events: none;
            transition: stroke 0.2s;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            background: var(--surf2);
            border: 1px solid var(--border);
            border-radius: 9px;
            padding: 12px 12px 12px 40px;
            color: var(--text);
            font-family: 'DM Sans', sans-serif;
            font-size: 14px;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        input:focus {
            border-color: rgba(230, 57, 70, 0.5);
            box-shadow: 0 0 0 3px rgba(230, 57, 70, 0.1);
        }

        input:focus~svg.icon {
            stroke: var(--red);
        }

        .input-wrap:focus-within svg.icon {
            stroke: var(--red);
        }

        .toggle-pw {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: var(--muted);
            display: flex;
            align-items: center;
            padding: 4px;
            transition: color 0.2s;
        }

        .toggle-pw:hover {
            color: var(--text);
        }

        .toggle-pw svg {
            width: 15px;
            height: 15px;
            stroke: currentColor;
            fill: none;
            stroke-width: 2;
        }

        /* BTN */
        .btn-login {
            width: 100%;
            background: var(--red);
            color: #fff;
            border: none;
            border-radius: 9px;
            padding: 13px;
            margin-top: 8px;
            font-family: 'DM Sans', sans-serif;
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 0.5px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: background 0.2s, transform 0.15s, box-shadow 0.2s;
        }

        .btn-login:hover {
            background: var(--red-dk);
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(230, 57, 70, 0.35);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .btn-login svg {
            width: 15px;
            height: 15px;
            stroke: currentColor;
            fill: none;
            stroke-width: 2.5;
        }

        .card-footer {
            padding: 14px 32px;
            border-top: 1px solid var(--border);
            text-align: center;
            font-size: 11px;
            color: var(--muted);
        }

        .card-footer span {
            color: var(--red);
            font-weight: 600;
        }
    </style>
</head>

<body>
    <div class="bg-noise"></div>
    <div class="bg-glow"></div>
    <div class="scan-line"></div>

    <div class="card">
        <div class="card-top"></div>
        <div class="card-body">
            <div class="lock-icon">
                <svg viewBox="0 0 24 24">
                    <rect x="3" y="11" width="18" height="11" rx="2" />
                    <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                </svg>
            </div>
            <h1>SISTEMA DE CONTROL</h1>
            <p class="sub">Acceso restringido — solo desarrollador</p>

            <?php if ($error): ?>
                <div class="err">
                    <svg viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="10" />
                        <line x1="12" y1="8" x2="12" y2="12" />
                        <line x1="12" y1="16" x2="12.01" y2="16" />
                    </svg>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" autocomplete="off">
                <div class="field">
                    <label>Usuario</label>
                    <div class="input-wrap">
                        <input type="text" name="usuario" placeholder="Usuario del sistema" required autocomplete="off" />
                        <svg class="icon" viewBox="0 0 24 24">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                            <circle cx="12" cy="7" r="4" />
                        </svg>
                    </div>
                </div>
                <div class="field">
                    <label>Contraseña</label>
                    <div class="input-wrap">
                        <input type="password" id="pw" name="password" placeholder="Contraseña del sistema" required autocomplete="new-password" />
                        <svg class="icon" viewBox="0 0 24 24">
                            <rect x="3" y="11" width="18" height="11" rx="2" />
                            <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                        </svg>
                        <button type="button" class="toggle-pw" onclick="togglePw()" tabindex="-1">
                            <svg id="eye" viewBox="0 0 24 24">
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
                    Acceder al sistema
                </button>
            </form>
        </div>
        <div class="card-footer">
            Acceso <span>RESTRINGIDO</span> — Solo personal autorizado
        </div>
    </div>

    <script>
        function togglePw() {
            const i = document.getElementById('pw');
            const e = document.getElementById('eye');
            if (i.type === 'password') {
                i.type = 'text';
                e.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>';
            } else {
                i.type = 'password';
                e.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
            }
        }
    </script>
</body>

</html>