<?php
session_start();

define('DEV_SESSION_KEY', 'dev_master_logged');

// ── Verificar sesión del desarrollador ───────────────────────────────────────
if (!isset($_SESSION[DEV_SESSION_KEY]) || $_SESSION[DEV_SESSION_KEY] !== true) {
    header('Location: dev_login.php');
    exit;
}

$dev = htmlspecialchars($_SESSION['dev_usuario'] ?? 'DEV');

// ── Ruta raíz del proyecto ────────────────────────────────────────────────────
// cpanel.php está en /admin/cpanel.php
// index.php está en la raíz /index.php
// Por eso subimos un nivel con __DIR__ . '/..'
define('PROJECT_ROOT', realpath(__DIR__ . '/..') . DIRECTORY_SEPARATOR);
define('PUBLIC_HTML',  PROJECT_ROOT);

// ── Archivo de estado de la página ───────────────────────────────────────────
define('STATUS_FILE', PROJECT_ROOT . '.site_status');

// ═══════════════════════════════════════════════════════════════════════════════
// ACCIONES AJAX
// ═══════════════════════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];

    // LOGOUT
    if ($action === 'logout') {
        session_destroy();
        echo json_encode(['ok' => true]);
        exit;
    }

    // ── DESHABILITAR / HABILITAR PÁGINA ──────────────────────────────────────
    if ($action === 'toggle_site') {
        $estado = $_POST['estado'] ?? 'off'; // 'on' o 'off'

        if ($estado === 'off') {
            // Crear archivo de bloqueo
            file_put_contents(STATUS_FILE, json_encode([
                'disabled'    => true,
                'disabled_at' => date('Y-m-d H:i:s'),
                'reason'      => trim($_POST['reason'] ?? 'Mantenimiento'),
                'by'          => $dev,
            ]));
            // Crear/sobreescribir index.php con página de bloqueo
            $blocked_html = generarPaginaBloqueo(trim($_POST['reason'] ?? 'Sitio temporalmente no disponible'));
            file_put_contents(PROJECT_ROOT . 'index.php', $blocked_html);
            echo json_encode(['ok' => true, 'estado' => 'disabled']);
        } else {
            // Eliminar archivo de bloqueo → el sitio vuelve a funcionar
            // OJO: el cliente debe hacer deploy nuevo del index.php real
            // O puedes restaurarlo desde un backup que guardaste
            if (file_exists(STATUS_FILE)) unlink(STATUS_FILE);
            // Restaurar index desde backup si existe
            $backup = PROJECT_ROOT . 'index.php.bak';
            if (file_exists($backup)) {
                copy($backup, PROJECT_ROOT . 'index.php');
                echo json_encode(['ok' => true, 'estado' => 'enabled', 'restored' => true]);
            } else {
                echo json_encode(['ok' => true, 'estado' => 'enabled', 'restored' => false, 'msg' => 'Sin backup disponible. Sube el index.php manualmente.']);
            }
        }
        exit;
    }

    // ── CREAR BACKUP DEL INDEX ────────────────────────────────────────────────
    if ($action === 'backup_index') {
        $src = PROJECT_ROOT . 'index.php';
        $dst = PROJECT_ROOT . 'index.php.bak';
        if (file_exists($src)) {
            copy($src, $dst);
            echo json_encode(['ok' => true, 'msg' => 'Backup creado: index.php.bak']);
        } else {
            echo json_encode(['ok' => false, 'msg' => 'index.php no encontrado']);
        }
        exit;
    }

    // ── LISTAR ARCHIVOS ───────────────────────────────────────────────────────
    if ($action === 'list_files') {
        $dir   = realpath($_POST['path'] ?? PROJECT_ROOT);
        // Seguridad: no salir de la raíz del proyecto
        if (!$dir || strpos($dir, realpath(PROJECT_ROOT)) !== 0) {
            echo json_encode(['ok' => false, 'msg' => 'Ruta no permitida']);
            exit;
        }
        $files = [];
        foreach (scandir($dir) as $f) {
            if ($f === '.') continue;
            $full = $dir . DIRECTORY_SEPARATOR . $f;
            $files[] = [
                'name'     => $f,
                'path'     => $full,
                'rel'      => str_replace(realpath(PROJECT_ROOT), '', $full),
                'is_dir'   => is_dir($full),
                'size'     => is_file($full) ? filesize($full) : null,
                'modified' => date('Y-m-d H:i', filemtime($full)),
            ];
        }
        echo json_encode(['ok' => true, 'files' => $files, 'current' => $dir, 'root' => realpath(PROJECT_ROOT)]);
        exit;
    }

    // ── ELIMINAR ARCHIVO O CARPETA ────────────────────────────────────────────
    if ($action === 'delete_file') {
        $path = realpath($_POST['path'] ?? '');
        if (!$path || strpos($path, realpath(PROJECT_ROOT)) !== 0) {
            echo json_encode(['ok' => false, 'msg' => 'Ruta no permitida']);
            exit;
        }
        // Proteger archivos críticos del sistema de control
        $protected = ['dev_login.php', 'cpanel.php', '.site_status', 'index.php.bak'];
        foreach ($protected as $p) {
            if (basename($path) === $p) {
                echo json_encode(['ok' => false, 'msg' => 'Este archivo está protegido del sistema.']);
                exit;
            }
        }
        if (is_dir($path)) {
            deleteDir($path);
            echo json_encode(['ok' => true, 'msg' => 'Carpeta eliminada']);
        } elseif (is_file($path)) {
            unlink($path);
            echo json_encode(['ok' => true, 'msg' => 'Archivo eliminado']);
        } else {
            echo json_encode(['ok' => false, 'msg' => 'No encontrado']);
        }
        exit;
    }

    // ── ELIMINAR TODO public_html (OPCIÓN NUCLEAR) ────────────────────────────
    if ($action === 'nuke') {
        $confirm = $_POST['confirm'] ?? '';
        if ($confirm !== 'ELIMINAR TODO') {
            echo json_encode(['ok' => false, 'msg' => 'Confirmación incorrecta']);
            exit;
        }
        // Eliminar todo EXCEPTO los archivos del sistema de control
        $keep = ['dev_login.php', 'cpanel.php'];
        foreach (scandir(PROJECT_ROOT) as $f) {
            if ($f === '.' || $f === '..' || in_array($f, $keep)) continue;
            $full = PROJECT_ROOT . $f;
            if (is_dir($full)) deleteDir($full);
            else @unlink($full);
        }
        echo json_encode(['ok' => true, 'msg' => 'Todo eliminado. Solo quedan los archivos del sistema.']);
        exit;
    }

    echo json_encode(['ok' => false, 'msg' => 'Acción desconocida']);
    exit;
}

// ─── Helpers ─────────────────────────────────────────────────────────────────
function deleteDir(string $dir): void
{
    if (!is_dir($dir)) return;
    foreach (scandir($dir) as $f) {
        if ($f === '.' || $f === '..') continue;
        $p = $dir . DIRECTORY_SEPARATOR . $f;
        is_dir($p) ? deleteDir($p) : @unlink($p);
    }
    @rmdir($dir);
}

function generarPaginaBloqueo(string $reason): string
{
    return '<?php http_response_code(503); ?>
<!doctype html><html lang="es"><head><meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>Sitio no disponible</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{background:#06080b;color:#e2e8f0;font-family:system-ui,sans-serif;
  display:flex;align-items:center;justify-content:center;min-height:100vh;text-align:center;padding:24px}
.box{max-width:480px}
.icon{font-size:64px;margin-bottom:24px}
h1{font-size:28px;margin-bottom:12px;color:#fff}
p{color:#64748b;font-size:15px;line-height:1.6}
.reason{margin-top:16px;background:rgba(230,57,70,.08);border:1px solid rgba(230,57,70,.2);
  border-radius:10px;padding:14px 20px;color:#ff8a8a;font-size:14px}
</style></head><body>
<div class="box">
  <div class="icon">🔒</div>
  <h1>Sitio temporalmente no disponible</h1>
  <p>Este sitio web se encuentra en mantenimiento o suspendido.</p>
  <div class="reason">' . htmlspecialchars($reason) . '</div>
</div>
</body></html>';
}

// ── Leer estado actual ────────────────────────────────────────────────────────
$siteStatus = file_exists(STATUS_FILE) ? json_decode(file_get_contents(STATUS_FILE), true) : null;
$isDisabled = $siteStatus && ($siteStatus['disabled'] ?? false);

// ── Info del proyecto ─────────────────────────────────────────────────────────
$hasBackup   = file_exists(PROJECT_ROOT . 'index.php.bak');
$indexExists = file_exists(PROJECT_ROOT . 'index.php');
?>
<!doctype html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1.0" />
    <title>Control Panel — Sistema Dev</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet" />
    <style>
        :root {
            --red: #e63946;
            --red-dk: #b71c2a;
            --red-lt: #ff6b6b;
            --green: #00b894;
            --yellow: #f0b429;
            --blue: #3b82f6;
            --bg: #06080b;
            --bg2: #0a0d12;
            --surf: #0f1318;
            --surf2: #161c24;
            --surf3: #1e2530;
            --border: rgba(255, 255, 255, 0.06);
            --border2: rgba(255, 255, 255, 0.1);
            --text: #e2e8f0;
            --muted: #64748b;
            --sidebar-w: 240px;
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0
        }

        html {
            scroll-behavior: smooth
        }

        body {
            background: var(--bg);
            color: var(--text);
            font-family: 'DM Sans', sans-serif;
            display: flex;
            min-height: 100vh
        }

        /* SIDEBAR */
        .sidebar {
            width: var(--sidebar-w);
            background: var(--bg2);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            z-index: 50
        }

        .sb-brand {
            padding: 24px 20px 18px;
            border-bottom: 1px solid var(--border)
        }

        .sb-logo {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 18px;
            letter-spacing: 3px;
            color: #fff;
            line-height: 1
        }

        .sb-logo span {
            color: var(--red)
        }

        .sb-sub {
            font-size: 9px;
            color: var(--muted);
            letter-spacing: 2.5px;
            text-transform: uppercase;
            margin-top: 3px
        }

        .sb-dev {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-top: 12px;
            background: rgba(230, 57, 70, .1);
            border: 1px solid rgba(230, 57, 70, .2);
            border-radius: 100px;
            padding: 3px 10px;
            font-size: 10px;
            color: var(--red);
            font-weight: 600
        }

        .sb-dev svg {
            width: 8px;
            height: 8px;
            fill: currentColor
        }

        .sb-nav {
            flex: 1;
            padding: 14px 0;
            overflow-y: auto
        }

        .sb-label {
            font-size: 9px;
            color: var(--muted);
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
            padding: 0 20px 6px
        }

        .sb-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 20px;
            color: var(--muted);
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            border: none;
            background: none;
            width: 100%;
            text-align: left;
            transition: all .15s;
            border-left: 2px solid transparent
        }

        .sb-item:hover {
            color: var(--text);
            background: rgba(255, 255, 255, .02)
        }

        .sb-item.active {
            color: #fff;
            background: rgba(230, 57, 70, .07);
            border-left-color: var(--red)
        }

        .sb-item svg {
            width: 14px;
            height: 14px;
            stroke: currentColor;
            fill: none;
            stroke-width: 2;
            flex-shrink: 0
        }

        .sb-divider {
            height: 1px;
            background: var(--border);
            margin: 10px 20px
        }

        .sb-footer {
            padding: 16px 20px;
            border-top: 1px solid var(--border)
        }

        .btn-logout {
            display: flex;
            align-items: center;
            gap: 8px;
            width: 100%;
            padding: 9px 12px;
            background: rgba(255, 71, 87, .07);
            border: 1px solid rgba(255, 71, 87, .12);
            border-radius: 9px;
            color: #ff6b7a;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            font-family: 'DM Sans', sans-serif;
            transition: all .2s
        }

        .btn-logout:hover {
            background: rgba(255, 71, 87, .14)
        }

        .btn-logout svg {
            width: 13px;
            height: 13px;
            stroke: currentColor;
            fill: none;
            stroke-width: 2
        }

        /* MAIN */
        .main {
            margin-left: var(--sidebar-w);
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 100vh
        }

        .topbar {
            position: sticky;
            top: 0;
            z-index: 40;
            background: rgba(6, 8, 11, .92);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border);
            padding: 13px 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px
        }

        .topbar-title {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 20px;
            color: #fff;
            letter-spacing: 1px
        }

        .topbar-title span {
            color: var(--red)
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 5px 14px;
            border-radius: 100px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .5px
        }

        .status-pill.online {
            background: rgba(0, 184, 148, .1);
            border: 1px solid rgba(0, 184, 148, .25);
            color: var(--green)
        }

        .status-pill.offline {
            background: rgba(230, 57, 70, .1);
            border: 1px solid rgba(230, 57, 70, .25);
            color: var(--red)
        }

        .status-pill .dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: currentColor;
            animation: pulse 2s infinite
        }

        @keyframes pulse {

            0%,
            100% {
                opacity: 1
            }

            50% {
                opacity: .4
            }
        }

        /* CONTENT */
        .content {
            padding: 28px 32px;
            flex: 1
        }

        .section {
            display: none
        }

        .section.active {
            display: block
        }

        /* CARDS GRID */
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 16px;
            margin-bottom: 28px
        }

        .info-card {
            background: var(--surf);
            border: 1px solid var(--border);
            border-radius: 13px;
            padding: 20px;
            transition: border-color .2s
        }

        .info-card:hover {
            border-color: var(--border2)
        }

        .info-card-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 12px
        }

        .ic-icon {
            width: 36px;
            height: 36px;
            border-radius: 9px;
            display: flex;
            align-items: center;
            justify-content: center
        }

        .ic-icon svg {
            width: 16px;
            height: 16px;
            stroke: currentColor;
            fill: none;
            stroke-width: 2
        }

        .ic-icon.red {
            background: rgba(230, 57, 70, .12);
            color: var(--red)
        }

        .ic-icon.green {
            background: rgba(0, 184, 148, .12);
            color: var(--green)
        }

        .ic-icon.yellow {
            background: rgba(240, 180, 41, .12);
            color: var(--yellow)
        }

        .ic-icon.blue {
            background: rgba(59, 130, 246, .12);
            color: var(--blue)
        }

        .ic-val {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 32px;
            color: #fff;
            line-height: 1;
            margin-bottom: 3px
        }

        .ic-label {
            font-size: 11px;
            color: var(--muted);
            font-weight: 500
        }

        /* CONTROL PANEL */
        .control-box {
            background: var(--surf);
            border: 1px solid var(--border);
            border-radius: 13px;
            padding: 24px;
            margin-bottom: 20px
        }

        .control-box h3 {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 18px;
            letter-spacing: 1px;
            color: #fff;
            margin-bottom: 6px
        }

        .control-box p {
            font-size: 13px;
            color: var(--muted);
            line-height: 1.6;
            margin-bottom: 20px
        }

        .control-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap
        }

        /* BOTONES */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 10px 20px;
            border-radius: 9px;
            font-family: 'DM Sans', sans-serif;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            border: none;
            transition: all .2s;
            white-space: nowrap
        }

        .btn svg {
            width: 13px;
            height: 13px;
            stroke: currentColor;
            fill: none;
            stroke-width: 2.5
        }

        .btn-red {
            background: var(--red);
            color: #fff
        }

        .btn-red:hover {
            background: var(--red-dk);
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(230, 57, 70, .35)
        }

        .btn-green {
            background: var(--green);
            color: #fff
        }

        .btn-green:hover {
            background: #007a63;
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(0, 184, 148, .3)
        }

        .btn-yellow {
            background: var(--yellow);
            color: #000
        }

        .btn-yellow:hover {
            background: #c8910c;
            transform: translateY(-1px)
        }

        .btn-ghost {
            background: var(--surf2);
            color: var(--text);
            border: 1px solid var(--border)
        }

        .btn-ghost:hover {
            border-color: var(--border2);
            background: var(--surf3)
        }

        .btn-blue {
            background: var(--blue);
            color: #fff
        }

        .btn-blue:hover {
            background: #1d4ed8;
            transform: translateY(-1px)
        }

        .btn-danger-outline {
            background: transparent;
            color: var(--red);
            border: 1px solid rgba(230, 57, 70, .3)
        }

        .btn-danger-outline:hover {
            background: rgba(230, 57, 70, .08);
            border-color: var(--red)
        }

        /* STATUS BOX */
        .status-box {
            border-radius: 12px;
            padding: 16px 20px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 20px
        }

        .status-box.online {
            background: rgba(0, 184, 148, .07);
            border: 1px solid rgba(0, 184, 148, .2)
        }

        .status-box.offline {
            background: rgba(230, 57, 70, .07);
            border: 1px solid rgba(230, 57, 70, .2)
        }

        .status-box svg {
            width: 18px;
            height: 18px;
            stroke: currentColor;
            fill: none;
            stroke-width: 2;
            flex-shrink: 0;
            margin-top: 1px
        }

        .status-box.online svg,
        .status-box.online .sb-title {
            color: var(--green)
        }

        .status-box.offline svg,
        .status-box.offline .sb-title {
            color: var(--red)
        }

        .sb-title {
            font-weight: 700;
            font-size: 14px;
            margin-bottom: 3px
        }

        .sb-desc {
            font-size: 12px;
            color: var(--muted);
            line-height: 1.5
        }

        /* FIELD */
        .field {
            margin-bottom: 14px
        }

        .field label {
            display: block;
            font-size: 10px;
            font-weight: 700;
            color: var(--muted);
            letter-spacing: 1.5px;
            text-transform: uppercase;
            margin-bottom: 6px
        }

        .field input,
        .field textarea,
        .field select {
            width: 100%;
            background: var(--surf2);
            border: 1px solid var(--border);
            border-radius: 9px;
            padding: 10px 13px;
            color: var(--text);
            font-family: 'DM Sans', sans-serif;
            font-size: 13px;
            outline: none;
            transition: border-color .2s
        }

        .field input:focus,
        .field textarea:focus {
            border-color: rgba(230, 57, 70, .4);
            box-shadow: 0 0 0 3px rgba(230, 57, 70, .08)
        }

        .field textarea {
            resize: vertical;
            min-height: 70px
        }

        /* FILE MANAGER */
        .fm-bar {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
            flex-wrap: wrap
        }

        .fm-path {
            font-family: 'JetBrains Mono', monospace;
            font-size: 12px;
            color: var(--muted);
            background: var(--surf2);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 8px 12px;
            flex: 1;
            min-width: 200px;
            overflow: auto;
            white-space: nowrap
        }

        .fm-table {
            width: 100%;
            border-collapse: collapse
        }

        .fm-table th {
            padding: 9px 14px;
            text-align: left;
            font-size: 10px;
            font-weight: 700;
            color: var(--muted);
            letter-spacing: 1.5px;
            text-transform: uppercase;
            border-bottom: 1px solid var(--border);
            background: var(--surf)
        }

        .fm-table td {
            padding: 11px 14px;
            font-size: 13px;
            border-bottom: 1px solid rgba(255, 255, 255, .03)
        }

        .fm-table tr:hover td {
            background: rgba(255, 255, 255, .02)
        }

        .fm-name {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            color: var(--text)
        }

        .fm-name:hover {
            color: #fff
        }

        .fm-name svg {
            width: 14px;
            height: 14px;
            stroke: currentColor;
            fill: none;
            stroke-width: 2;
            flex-shrink: 0
        }

        .fm-dir {
            color: var(--yellow)
        }

        .fm-size {
            font-family: 'JetBrains Mono', monospace;
            font-size: 11px;
            color: var(--muted)
        }

        .fm-date {
            font-size: 11px;
            color: var(--muted)
        }

        .fm-del {
            background: rgba(230, 57, 70, .08);
            border: 1px solid rgba(230, 57, 70, .15);
            color: #ff6b7a;
            padding: 5px 11px;
            border-radius: 7px;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            font-family: 'DM Sans', sans-serif;
            transition: all .15s
        }

        .fm-del:hover {
            background: rgba(230, 57, 70, .18)
        }

        .fm-wrap {
            background: var(--surf);
            border: 1px solid var(--border);
            border-radius: 13px;
            overflow: hidden
        }

        /* NUKE */
        .nuke-box {
            background: rgba(230, 57, 70, .04);
            border: 2px solid rgba(230, 57, 70, .2);
            border-radius: 13px;
            padding: 24px
        }

        .nuke-title {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 22px;
            letter-spacing: 1px;
            color: var(--red);
            margin-bottom: 8px
        }

        .nuke-desc {
            font-size: 13px;
            color: var(--muted);
            line-height: 1.6;
            margin-bottom: 20px
        }

        /* MODAL */
        .modal-ov {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 200;
            background: rgba(0, 0, 0, .8);
            backdrop-filter: blur(8px);
            align-items: center;
            justify-content: center;
            padding: 20px
        }

        .modal-ov.open {
            display: flex;
            animation: fadeIn .2s ease
        }

        @keyframes fadeIn {
            from {
                opacity: 0
            }

            to {
                opacity: 1
            }
        }

        .modal {
            background: var(--surf);
            border: 1px solid var(--border2);
            border-radius: 16px;
            width: 100%;
            max-width: 480px;
            animation: mIn .25s cubic-bezier(.16, 1, .3, 1)
        }

        @keyframes mIn {
            from {
                opacity: 0;
                transform: scale(.95) translateY(16px)
            }

            to {
                opacity: 1;
                transform: scale(1) translateY(0)
            }
        }

        .mh {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 18px 22px 14px;
            border-bottom: 1px solid var(--border)
        }

        .mh-title {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 18px;
            letter-spacing: 1px;
            color: #fff
        }

        .mh-title span {
            color: var(--red)
        }

        .mc {
            width: 28px;
            height: 28px;
            border-radius: 6px;
            background: rgba(255, 255, 255, .05);
            border: none;
            color: var(--muted);
            cursor: pointer;
            font-size: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all .15s
        }

        .mc:hover {
            background: rgba(255, 71, 87, .1);
            color: var(--red)
        }

        .mb {
            padding: 18px 22px
        }

        .mf {
            padding: 12px 22px 18px;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            border-top: 1px solid var(--border)
        }

        /* TOAST */
        .toast {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 999;
            background: var(--surf2);
            border: 1px solid var(--border2);
            border-radius: 11px;
            padding: 12px 16px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            font-weight: 500;
            color: var(--text);
            transform: translateY(60px);
            opacity: 0;
            transition: all .3s cubic-bezier(.16, 1, .3, 1);
            pointer-events: none;
            box-shadow: 0 6px 24px rgba(0, 0, 0, .5)
        }

        .toast.show {
            transform: translateY(0);
            opacity: 1
        }

        .toast svg {
            width: 15px;
            height: 15px;
            stroke: currentColor;
            fill: none;
            stroke-width: 2.5;
            flex-shrink: 0
        }

        .toast.ok {
            border-color: rgba(0, 184, 148, .3)
        }

        .toast.ok svg {
            stroke: var(--green)
        }

        .toast.err {
            border-color: rgba(230, 57, 70, .3)
        }

        .toast.err svg {
            stroke: var(--red)
        }

        .spinner {
            width: 14px;
            height: 14px;
            border: 2px solid rgba(255, 255, 255, .15);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin .6s linear infinite
        }

        @keyframes spin {
            to {
                transform: rotate(360deg)
            }
        }

        @media(max-width:768px) {
            .sidebar {
                transform: translateX(-100%)
            }

            .main {
                margin-left: 0
            }

            .content {
                padding: 20px 16px
            }

            .cards-grid {
                grid-template-columns: 1fr
            }
        }
    </style>
</head>

<body>

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="sb-brand">
            <div class="sb-logo">SYS<span>CTRL</span></div>
            <div class="sb-sub">Panel del Desarrollador</div>
            <div class="sb-dev"><svg viewBox="0 0 8 8">
                    <circle cx="4" cy="4" r="4" />
                </svg><?= $dev ?></div>
        </div>
        <nav class="sb-nav">
            <div class="sb-label">Control</div>
            <button class="sb-item active" onclick="showSection('overview',this)">
                <svg viewBox="0 0 24 24">
                    <rect x="3" y="3" width="7" height="7" />
                    <rect x="14" y="3" width="7" height="7" />
                    <rect x="14" y="14" width="7" height="7" />
                    <rect x="3" y="14" width="7" height="7" />
                </svg>
                Resumen
            </button>
            <button class="sb-item" onclick="showSection('sitio',this)">
                <svg viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="10" />
                    <line x1="2" y1="12" x2="22" y2="12" />
                    <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z" />
                </svg>
                Estado del Sitio
            </button>
            <button class="sb-item" onclick="showSection('archivos',this)">
                <svg viewBox="0 0 24 24">
                    <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z" />
                </svg>
                Archivos
            </button>
            <button class="sb-item" onclick="showSection('nuclear',this)">
                <svg viewBox="0 0 24 24">
                    <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2" />
                </svg>
                Acción Nuclear
            </button>
            <div class="sb-divider"></div>
            <div class="sb-label">Sistema</div>
            <a class="sb-item" href="../index.php" target="_blank">
                <svg viewBox="0 0 24 24">
                    <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 0 2-2h6" />
                    <polyline points="15 3 21 3 21 9" />
                    <line x1="10" y1="14" x2="21" y2="3" />
                </svg>
                Ver sitio web
            </a>
        </nav>
        <div class="sb-footer">
            <button class="btn-logout" onclick="doLogout()">
                <svg viewBox="0 0 24 24">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                    <polyline points="16 17 21 12 16 7" />
                    <line x1="21" y1="12" x2="9" y2="12" />
                </svg>
                Cerrar sesión
            </button>
        </div>
    </aside>

    <!-- MAIN -->
    <div class="main">
        <header class="topbar">
            <div class="topbar-title">CONTROL <span>PANEL</span></div>
            <div class="status-pill <?= $isDisabled ? 'offline' : 'online' ?>">
                <span class="dot"></span>
                <?= $isDisabled ? 'SITIO DESHABILITADO' : 'SITIO EN LÍNEA' ?>
            </div>
        </header>

        <div class="content">

            <!-- ═══ RESUMEN ═══ -->
            <div id="section-overview" class="section active">
                <div class="cards-grid">
                    <div class="info-card">
                        <div class="info-card-top">
                            <div class="ic-icon <?= $isDisabled ? 'red' : 'green' ?>">
                                <svg viewBox="0 0 24 24"><?= $isDisabled ? '<path d="M18.36 6.64a9 9 0 1 1-12.73 0"/><line x1="12" y1="2" x2="12" y2="12"/>' : '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>' ?></svg>
                            </div>
                        </div>
                        <div class="ic-val"><?= $isDisabled ? 'OFF' : 'ON' ?></div>
                        <div class="ic-label">Estado del sitio</div>
                    </div>
                    <div class="info-card">
                        <div class="info-card-top">
                            <div class="ic-icon yellow"><svg viewBox="0 0 24 24">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                                    <polyline points="14 2 14 8 20 8" />
                                </svg></div>
                        </div>
                        <div class="ic-val"><?= $hasBackup ? 'SÍ' : 'NO' ?></div>
                        <div class="ic-label">Backup index disponible</div>
                    </div>
                    <div class="info-card">
                        <div class="info-card-top">
                            <div class="ic-icon blue"><svg viewBox="0 0 24 24">
                                    <rect x="2" y="3" width="20" height="14" rx="2" />
                                    <line x1="8" y1="21" x2="16" y2="21" />
                                    <line x1="12" y1="17" x2="12" y2="21" />
                                </svg></div>
                        </div>
                        <div class="ic-val"><?= $indexExists ? 'SÍ' : 'NO' ?></div>
                        <div class="ic-label">index.php existe</div>
                    </div>
                    <div class="info-card">
                        <div class="info-card-top">
                            <div class="ic-icon red"><svg viewBox="0 0 24 24">
                                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                                </svg></div>
                        </div>
                        <div class="ic-val">DEV</div>
                        <div class="ic-label">Modo desarrollador</div>
                    </div>
                </div>

                <?php if ($isDisabled): ?>
                    <div class="status-box offline">
                        <svg viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10" />
                            <line x1="4.93" y1="4.93" x2="19.07" y2="19.07" />
                        </svg>
                        <div>
                            <div class="sb-title">Sitio deshabilitado</div>
                            <div class="sb-desc">
                                Motivo: <strong><?= htmlspecialchars($siteStatus['reason'] ?? '') ?></strong><br>
                                Deshabilitado el: <?= htmlspecialchars($siteStatus['disabled_at'] ?? '') ?> por <?= htmlspecialchars($siteStatus['by'] ?? '') ?>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="status-box online">
                        <svg viewBox="0 0 24 24">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
                            <polyline points="22 4 12 14.01 9 11.01" />
                        </svg>
                        <div>
                            <div class="sb-title">Sitio en línea y funcionando</div>
                            <div class="sb-desc">El sitio web está activo y accesible para todos los usuarios.</div>
                        </div>
                    </div>
                <?php endif; ?>

                <div style="background:var(--surf);border:1px solid var(--border);border-radius:13px;padding:20px">
                    <h3 style="font-family:'Bebas Neue',sans-serif;font-size:16px;letter-spacing:1px;color:#fff;margin-bottom:12px">ACCIONES RÁPIDAS</h3>
                    <div style="display:flex;gap:10px;flex-wrap:wrap">
                        <?php if (!$isDisabled): ?>
                            <button class="btn btn-yellow" onclick="showSection('sitio',document.querySelector('.sb-item:nth-child(2)'))">
                                <svg viewBox="0 0 24 24">
                                    <path d="M18.36 6.64a9 9 0 1 1-12.73 0" />
                                    <line x1="12" y1="2" x2="12" y2="12" />
                                </svg>
                                Deshabilitar sitio
                            </button>
                        <?php else: ?>
                            <button class="btn btn-green" onclick="showSection('sitio',document.querySelector('.sb-item:nth-child(2)'))">
                                <svg viewBox="0 0 24 24">
                                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
                                    <polyline points="22 4 12 14.01 9 11.01" />
                                </svg>
                                Habilitar sitio
                            </button>
                        <?php endif; ?>
                        <button class="btn btn-ghost" onclick="backupIndex()">
                            <svg viewBox="0 0 24 24">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                                <polyline points="14 2 14 8 20 8" />
                                <line x1="12" y1="18" x2="12" y2="12" />
                                <line x1="9" y1="15" x2="12" y2="12" />
                                <line x1="15" y1="15" x2="12" y2="12" />
                            </svg>
                            Crear backup index
                        </button>
                        <button class="btn btn-ghost" onclick="showSection('archivos',null); setTimeout(loadFiles,100)">
                            <svg viewBox="0 0 24 24">
                                <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z" />
                            </svg>
                            Explorar archivos
                        </button>
                    </div>
                </div>
            </div>

            <!-- ═══ ESTADO DEL SITIO ═══ -->
            <div id="section-sitio" class="section">
                <div class="control-box">
                    <h3>🔒 DESHABILITAR SITIO</h3>
                    <p>El index.php del cliente será reemplazado por una página de "Sitio no disponible". El cliente no podrá ver su web hasta que la habilites de nuevo.</p>
                    <div class="field">
                        <label>Motivo (visible para visitantes)</label>
                        <input type="text" id="disable_reason" placeholder="Ej: Pago pendiente — Sitio suspendido temporalmente" value="Este sitio web se encuentra suspendido por falta de pago." />
                    </div>
                    <div class="control-actions">
                        <button class="btn btn-yellow" onclick="toggleSite('off')">
                            <svg viewBox="0 0 24 24">
                                <path d="M18.36 6.64a9 9 0 1 1-12.73 0" />
                                <line x1="12" y1="2" x2="12" y2="12" />
                            </svg>
                            Deshabilitar sitio ahora
                        </button>
                    </div>
                </div>

                <div class="control-box">
                    <h3>✅ HABILITAR SITIO</h3>
                    <p>Restaura el index.php original desde el backup guardado. Si no hay backup, deberás subir el archivo manualmente por FTP/cPanel.</p>
                    <div class="control-actions">
                        <button class="btn btn-green" onclick="toggleSite('on')">
                            <svg viewBox="0 0 24 24">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
                                <polyline points="22 4 12 14.01 9 11.01" />
                            </svg>
                            Habilitar sitio
                        </button>
                        <button class="btn btn-ghost" onclick="backupIndex()">
                            <svg viewBox="0 0 24 24">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                                <polyline points="14 2 14 8 20 8" />
                            </svg>
                            Crear backup del index actual
                        </button>
                    </div>
                    <?php if (!$hasBackup): ?>
                        <p style="color:var(--yellow);font-size:12px;margin-top:14px">⚠ No hay backup guardado. Crea uno antes de deshabilitar el sitio.</p>
                    <?php endif; ?>
                </div>

                <?php if ($isDisabled): ?>
                    <div class="status-box offline">
                        <svg viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10" />
                            <line x1="4.93" y1="4.93" x2="19.07" y2="19.07" />
                        </svg>
                        <div>
                            <div class="sb-title">Sitio actualmente DESHABILITADO</div>
                            <div class="sb-desc">
                                Motivo: <?= htmlspecialchars($siteStatus['reason'] ?? '') ?><br>
                                Desde: <?= htmlspecialchars($siteStatus['disabled_at'] ?? '') ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- ═══ ARCHIVOS ═══ -->
            <div id="section-archivos" class="section">
                <div style="margin-bottom:20px">
                    <h2 style="font-family:'Bebas Neue',sans-serif;font-size:22px;letter-spacing:1px;color:#fff;margin-bottom:6px">EXPLORADOR DE ARCHIVOS</h2>
                    <p style="font-size:13px;color:var(--muted)">Navega y elimina archivos del proyecto.</p>
                </div>
                <div class="fm-bar">
                    <div class="fm-path" id="currentPath"><?= htmlspecialchars(PROJECT_ROOT) ?></div>
                    <button class="btn btn-ghost" onclick="loadFiles(document.getElementById('currentPath').textContent)" style="padding:8px 14px;font-size:12px">
                        <svg viewBox="0 0 24 24" style="width:12px;height:12px">
                            <polyline points="1 4 1 10 7 10" />
                            <path d="M3.51 15a9 9 0 1 0 .49-4.5" />
                        </svg>
                        Recargar
                    </button>
                </div>
                <div class="fm-wrap">
                    <table class="fm-table">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Tamaño</th>
                                <th>Modificado</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody id="fm-tbody">
                            <tr>
                                <td colspan="4" style="text-align:center;padding:30px;color:var(--muted)">
                                    <button class="btn btn-blue" onclick="loadFiles()" style="padding:9px 20px;font-size:13px">
                                        <svg viewBox="0 0 24 24" style="width:13px;height:13px">
                                            <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z" />
                                        </svg>
                                        Cargar archivos
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ═══ ACCIÓN NUCLEAR ═══ -->
            <div id="section-nuclear" class="section">
                <div style="margin-bottom:20px">
                    <h2 style="font-family:'Bebas Neue',sans-serif;font-size:22px;letter-spacing:1px;color:var(--red)">⚡ ACCIÓN NUCLEAR</h2>
                    <p style="font-size:13px;color:var(--muted)">Eliminar todo el contenido del proyecto. Irreversible.</p>
                </div>
                <div class="nuke-box">
                    <div class="nuke-title">⚠ ELIMINAR TODO EL PROYECTO</div>
                    <p class="nuke-desc">
                        Esta acción eliminará <strong style="color:#fff">TODOS los archivos</strong> del proyecto (imágenes, CSS, JS, PHP, BD incluida).<br>
                        Solo sobrevivirán <code style="color:var(--red)">dev_login.php</code> y <code style="color:var(--red)">cpanel.php</code> (tus archivos de control).<br><br>
                        <strong style="color:var(--red)">Esta acción es PERMANENTE e IRREVERSIBLE.</strong> Úsala solo cuando el cliente haya perdido todos los derechos sobre el sitio.
                    </p>
                    <div class="field">
                        <label>Escribe exactamente: <strong style="color:var(--red)">ELIMINAR TODO</strong></label>
                        <input type="text" id="nuke_confirm" placeholder="ELIMINAR TODO" />
                    </div>
                    <button class="btn btn-red" onclick="openNukeModal()">
                        <svg viewBox="0 0 24 24">
                            <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2" />
                        </svg>
                        Ejecutar acción nuclear
                    </button>
                </div>
            </div>

        </div><!-- /content -->
    </div><!-- /main -->

    <!-- MODAL CONFIRM NUKE -->
    <div class="modal-ov" id="nukeModal" onclick="closeNuke(event)">
        <div class="modal" onclick="event.stopPropagation()">
            <div class="mh">
                <div class="mh-title">⚡ ACCIÓN <span>NUCLEAR</span></div>
                <button class="mc" onclick="closeNuke()">✕</button>
            </div>
            <div class="mb">
                <div style="text-align:center;padding:8px 0">
                    <div style="font-size:48px;margin-bottom:12px">💥</div>
                    <div style="font-family:'Bebas Neue',sans-serif;font-size:22px;color:var(--red);margin-bottom:8px;letter-spacing:1px">¿ESTÁS COMPLETAMENTE SEGURO?</div>
                    <p style="font-size:13px;color:var(--muted);line-height:1.6">Esto eliminará <strong style="color:#fff">absolutamente todo</strong> el proyecto del cliente. No hay vuelta atrás.</p>
                </div>
            </div>
            <div class="mf">
                <button class="btn btn-ghost" onclick="closeNuke()" style="font-size:13px">Cancelar</button>
                <button class="btn btn-red" id="nukeBtn" onclick="doNuke()" style="font-size:13px">
                    <svg viewBox="0 0 24 24">
                        <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2" />
                    </svg>
                    Confirmar — Eliminar Todo
                </button>
            </div>
        </div>
    </div>

    <!-- TOAST -->
    <div class="toast" id="toast">
        <svg id="toastIcon" viewBox="0 0 24 24">
            <polyline points="20 6 9 17 4 12" />
        </svg>
        <span id="toastMsg"></span>
    </div>

    <script>
        // ── SECCIONES ─────────────────────────────────────────────────────
        function showSection(id, btn) {
            document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
            document.getElementById('section-' + id).classList.add('active');
            document.querySelectorAll('.sb-item').forEach(b => b.classList.remove('active'));
            const target = btn || document.querySelector(`.sb-item[onclick*="${id}"]`);
            if (target) target.classList.add('active');
            if (id === 'archivos') loadFiles();
        }

        // ── TOGGLE SITIO ──────────────────────────────────────────────────
        async function toggleSite(estado) {
            const reason = document.getElementById('disable_reason')?.value || 'Mantenimiento';
            if (estado === 'off' && !confirm('¿Deshabilitar el sitio ahora? El cliente no podrá verlo.')) return;
            if (estado === 'on' && !confirm('¿Habilitar el sitio? Se restaurará desde el backup.')) return;

            const fd = new FormData();
            fd.append('action', 'toggle_site');
            fd.append('estado', estado);
            fd.append('reason', reason);

            try {
                const res = await fetch('cpanel.php', {
                    method: 'POST',
                    body: fd
                });
                const data = await res.json();
                if (data.ok) {
                    if (data.msg) showToast(data.msg, 'ok');
                    else showToast(estado === 'off' ? '🔒 Sitio deshabilitado' : '✅ Sitio habilitado', 'ok');
                    setTimeout(() => location.reload(), 1200);
                } else showToast(data.msg || 'Error', 'err');
            } catch {
                showToast('Error de conexión', 'err');
            }
        }

        // ── BACKUP INDEX ──────────────────────────────────────────────────
        async function backupIndex() {
            const fd = new FormData();
            fd.append('action', 'backup_index');
            try {
                const res = await fetch('cpanel.php', {
                    method: 'POST',
                    body: fd
                });
                const data = await res.json();
                showToast(data.ok ? data.msg : (data.msg || 'Error'), data.ok ? 'ok' : 'err');
                if (data.ok) setTimeout(() => location.reload(), 1200);
            } catch {
                showToast('Error', 'err');
            }
        }

        // ── FILE MANAGER ──────────────────────────────────────────────────
        let currentDir = '';

        async function loadFiles(path) {
            const fd = new FormData();
            fd.append('action', 'list_files');
            if (path) fd.append('path', path);

            try {
                const res = await fetch('cpanel.php', {
                    method: 'POST',
                    body: fd
                });
                const data = await res.json();
                if (!data.ok) {
                    showToast(data.msg, 'err');
                    return;
                }

                currentDir = data.current;
                document.getElementById('currentPath').textContent = data.current;

                const tbody = document.getElementById('fm-tbody');
                tbody.innerHTML = '';

                // Botón "Subir nivel"
                if (data.current !== data.root) {
                    const parentPath = data.current.substring(0, data.current.includes('/') ? data.current.lastIndexOf('/') : data.current.lastIndexOf('\\'));
                    const tr = document.createElement('tr');
                    tr.innerHTML = `<td colspan="4">
                <div class="fm-name fm-dir" onclick="loadFiles('${escHtml(parentPath)}')">
                    <svg viewBox="0 0 24 24"><path d="M15 18l-6-6 6-6"/></svg>
                    .. (subir nivel)
                </div></td>`;
                    tbody.appendChild(tr);
                }

                data.files.forEach(f => {
                    const tr = document.createElement('tr');
                    const sizeStr = f.is_dir ? '—' : formatSize(f.size);
                    tr.innerHTML = `
                <td>
                    <div class="fm-name ${f.is_dir?'fm-dir':''}" onclick="${f.is_dir?`loadFiles('${escHtml(f.path)}')`:''}">
                        <svg viewBox="0 0 24 24">${f.is_dir
                            ? '<path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>'
                            : '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>'}
                        </svg>
                        ${escHtml(f.name)}
                    </div>
                </td>
                <td class="fm-size">${sizeStr}</td>
                <td class="fm-date">${f.modified}</td>
                <td>${f.name!=='..'?`<button class="fm-del" onclick="deleteFile('${escHtml(f.path)}','${escHtml(f.name)}',${f.is_dir})">Eliminar</button>`:''}</td>`;
                    tbody.appendChild(tr);
                });
            } catch (e) {
                showToast('Error cargando archivos', 'err');
            }
        }

        function deleteFile(path, name, isDir) {
            const tipo = isDir ? 'la carpeta' : 'el archivo';
            if (!confirm(`¿Eliminar ${tipo} "${name}"?${isDir?' Se eliminará TODO su contenido.':''}`)) return;
            const fd = new FormData();
            fd.append('action', 'delete_file');
            fd.append('path', path);
            fetch('cpanel.php', {
                    method: 'POST',
                    body: fd
                })
                .then(r => r.json())
                .then(d => {
                    showToast(d.ok ? `"${name}" eliminado` : d.msg, d.ok ? 'ok' : 'err');
                    if (d.ok) loadFiles(currentDir);
                })
                .catch(() => showToast('Error', 'err'));
        }

        // ── NUCLEAR ───────────────────────────────────────────────────────
        function openNukeModal() {
            const confirm_txt = document.getElementById('nuke_confirm').value;
            if (confirm_txt !== 'ELIMINAR TODO') {
                showToast('Escribe exactamente: ELIMINAR TODO', 'err');
                return;
            }
            document.getElementById('nukeModal').classList.add('open');
        }

        function closeNuke(e) {
            if (e && e.target !== document.getElementById('nukeModal')) return;
            document.getElementById('nukeModal').classList.remove('open');
        }
        async function doNuke() {
            const btn = document.getElementById('nukeBtn');
            btn.innerHTML = '<div class="spinner"></div> Eliminando...';
            btn.disabled = true;
            const fd = new FormData();
            fd.append('action', 'nuke');
            fd.append('confirm', 'ELIMINAR TODO');
            try {
                const res = await fetch('cpanel.php', {
                    method: 'POST',
                    body: fd
                });
                const data = await res.json();
                showToast(data.ok ? '💥 Todo eliminado' : data.msg, data.ok ? 'ok' : 'err');
                if (data.ok) {
                    closeNuke();
                    setTimeout(() => location.reload(), 2000);
                }
            } catch {
                showToast('Error', 'err');
            }
            btn.innerHTML = 'Confirmar — Eliminar Todo';
            btn.disabled = false;
        }

        // ── LOGOUT ────────────────────────────────────────────────────────
        async function doLogout() {
            const fd = new FormData();
            fd.append('action', 'logout');
            await fetch('cpanel.php', {
                method: 'POST',
                body: fd
            });
            window.location.href = 'dev_login.php';
        }

        // ── TOAST / HELPERS ───────────────────────────────────────────────
        let tt;

        function showToast(msg, type = 'ok') {
            const t = document.getElementById('toast'),
                i = document.getElementById('toastIcon');
            document.getElementById('toastMsg').textContent = msg;
            t.className = 'toast ' + type + ' show';
            i.innerHTML = type === 'ok' ?
                '<polyline points="20 6 9 17 4 12"/>' :
                '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>';
            clearTimeout(tt);
            tt = setTimeout(() => t.classList.remove('show'), 3500);
        }

        function formatSize(bytes) {
            if (!bytes) return '0 B';
            const k = 1024,
                sizes = ['B', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return (bytes / Math.pow(k, i)).toFixed(1) + ' ' + sizes[i];
        }

        function escHtml(s) {
            return String(s).replace(/&/g, '&amp;').replace(/'/g, '&#39;').replace(/"/g, '&quot;');
        }

        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') closeNuke();
        });
    </script>
</body>

</html>