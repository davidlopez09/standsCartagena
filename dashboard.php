<?php
session_start();

if (!isset($_SESSION['admin_logged']) || $_SESSION['admin_logged'] !== true) {
    header('Location: login.php');
    exit;
}

require_once './app/conexion.php';

$admin = htmlspecialchars($_SESSION['admin_usuario']);

// ─── Manejo de acciones POST (AJAX) ───────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];

    // LOGOUT
    if ($action === 'logout') {
        session_destroy();
        echo json_encode(['ok' => true]);
        exit;
    }

    // GUARDAR STAND (crear o actualizar)
    if ($action === 'save_stand') {
        $id       = intval($_POST['id'] ?? 0);
        $ciudad   = trim($_POST['ciudad'] ?? '');
        $tipo     = trim($_POST['tipo'] ?? '');
        $nombre   = trim($_POST['nombre'] ?? '');
        $precio   = trim($_POST['precio'] ?? '');
        $desc     = trim($_POST['descripcion'] ?? '');
        $orden    = intval($_POST['orden'] ?? 0);

        // Manejo de imagen
        $imagen_path = trim($_POST['imagen_actual'] ?? '');
        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
            $ext_allowed = ['jpg', 'jpeg', 'png', 'webp'];
            $ext = strtolower(pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $ext_allowed)) {
                $upload_dir = 'public/images/stands/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
                $filename = uniqid('stand_') . '.' . $ext;
                if (move_uploaded_file($_FILES['imagen']['tmp_name'], $upload_dir . $filename)) {
                    $imagen_path = $upload_dir . $filename;
                }
            }
        }

        if ($id > 0) {
            // UPDATE
            $stmt = $pdo->prepare("UPDATE stands SET id_ciudad=?, tipo=?, nombre=?, precio=?, descripcion=?, imagen=?, orden=?, updated_at=NOW() WHERE id=?");
            $stmt->execute([$ciudad, $tipo, $nombre, $precio, $desc, $imagen_path, $orden, $id]);
        } else {
            // INSERT
            $stmt = $pdo->prepare("INSERT INTO stands (id_ciudad, tipo, nombre, precio, descripcion, imagen, orden, activo) VALUES (?,?,?,?,?,?,?,1)");
            $stmt->execute([$ciudad, $tipo, $nombre, $precio, $desc, $imagen_path, $orden]);
            $id = $pdo->lastInsertId();
        }
        echo json_encode(['ok' => true, 'id' => $id, 'imagen' => $imagen_path]);
        exit;
    }

    // ELIMINAR STAND
    if ($action === 'delete_stand') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            // Obtener imagen para borrarla del disco
            $stmt = $pdo->prepare("SELECT imagen FROM stands WHERE id=?");
            $stmt->execute([$id]);
            $row = $stmt->fetch();
            if ($row && $row['imagen'] && file_exists($row['imagen'])) {
                @unlink($row['imagen']);
            }
            $pdo->prepare("DELETE FROM stands WHERE id=?")->execute([$id]);
        }
        echo json_encode(['ok' => true]);
        exit;
    }

    echo json_encode(['ok' => false, 'msg' => 'Acción no reconocida']);
    exit;
}

// ─── Leer stands ─────────────────────────────────────────────────────────────
$stands = $pdo->query("SELECT * FROM stands ORDER BY id_ciudad ASC, orden ASC, id ASC")->fetchAll();

// Agrupar por ciudad
$byCity = ['cartagena' => [], 'barranquilla' => [], 'santamarta' => []];
foreach ($stands as $s) {
    $c = $s['ciudad'] ?? 'cartagena';
    if (isset($byCity[$c])) $byCity[$c][] = $s;
}

$totalStands = count($stands);
$cities = [
    'cartagena'    => ['label' => 'Cartagena',    'color' => '#f05a1a', 'abbr' => 'CTG'],
    'barranquilla' => ['label' => 'Barranquilla',  'color' => '#1a3aff', 'abbr' => 'BQA'],
    'santamarta'   => ['label' => 'Santa Marta',   'color' => '#00b894', 'abbr' => 'SMR'],
];

$stmt = $pdo->query("SELECT id, nombre FROM ciudades ORDER BY nombre ASC");
$ciudades = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Dashboard — Marca & Medios</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    <style>
        :root {
            --orange: #f05a1a;
            --orange-dark: #d4380d;
            --bg: #080c10;
            --bg2: #0d1117;
            --surface: #111720;
            --surface2: #161e28;
            --surface3: #1c2535;
            --border: rgba(255, 255, 255, 0.07);
            --border2: rgba(255, 255, 255, 0.12);
            --text: #e8edf4;
            --muted: #7a8694;
            --sidebar-w: 260px;
            --red: #ff4757;
            --green: #00b894;
            --blue: #1a3aff;
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            background: var(--bg);
            color: var(--text);
            font-family: 'DM Sans', sans-serif;
            display: flex;
            min-height: 100vh;
        }

        /* ── SIDEBAR ── */
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
            z-index: 50;
            transition: transform 0.3s;
        }

        .sidebar-brand {
            padding: 28px 24px 20px;
            border-bottom: 1px solid var(--border);
        }

        .brand-logo {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 20px;
            letter-spacing: 2px;
            color: #fff;
            line-height: 1;
        }

        .brand-logo span {
            color: var(--orange);
        }

        .brand-sub-text {
            font-size: 10px;
            color: var(--muted);
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-top: 2px;
        }

        .admin-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-top: 14px;
            background: rgba(240, 90, 26, 0.1);
            border: 1px solid rgba(240, 90, 26, 0.2);
            border-radius: 100px;
            padding: 4px 12px;
            font-size: 11px;
            color: var(--orange);
            font-weight: 600;
        }

        .admin-badge svg {
            width: 10px;
            height: 10px;
            fill: currentColor;
        }

        .sidebar-nav {
            flex: 1;
            padding: 20px 0;
            overflow-y: auto;
        }

        .nav-section-label {
            font-size: 10px;
            color: var(--muted);
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
            padding: 0 24px 8px;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 11px 24px;
            color: var(--muted);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            border: none;
            background: none;
            width: 100%;
            text-align: left;
            transition: all 0.15s;
            border-left: 2px solid transparent;
        }

        .nav-item:hover {
            color: var(--text);
            background: rgba(255, 255, 255, 0.03);
        }

        .nav-item.active {
            color: #fff;
            background: rgba(240, 90, 26, 0.08);
            border-left-color: var(--orange);
        }

        .nav-item svg {
            width: 16px;
            height: 16px;
            stroke: currentColor;
            fill: none;
            stroke-width: 2;
            flex-shrink: 0;
        }

        .nav-item .count {
            margin-left: auto;
            background: var(--surface3);
            border-radius: 100px;
            padding: 2px 8px;
            font-size: 11px;
            color: var(--muted);
            font-weight: 600;
        }

        .nav-divider {
            height: 1px;
            background: var(--border);
            margin: 12px 24px;
        }

        .sidebar-footer {
            padding: 20px 24px;
            border-top: 1px solid var(--border);
        }

        .btn-logout {
            display: flex;
            align-items: center;
            gap: 10px;
            width: 100%;
            padding: 10px 14px;
            background: rgba(255, 71, 87, 0.08);
            border: 1px solid rgba(255, 71, 87, 0.15);
            border-radius: 10px;
            color: #ff6b7a;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            font-family: 'DM Sans', sans-serif;
            transition: all 0.2s;
        }

        .btn-logout:hover {
            background: rgba(255, 71, 87, 0.15);
            border-color: rgba(255, 71, 87, 0.3);
        }

        .btn-logout svg {
            width: 15px;
            height: 15px;
            stroke: currentColor;
            fill: none;
            stroke-width: 2;
        }

        /* ── MAIN ── */
        .main {
            margin-left: var(--sidebar-w);
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* ── TOPBAR ── */
        .topbar {
            position: sticky;
            top: 0;
            z-index: 40;
            background: rgba(8, 12, 16, 0.9);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border);
            padding: 16px 40px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        .topbar-title {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 24px;
            color: #fff;
            letter-spacing: 1px;
        }

        .topbar-title span {
            color: var(--orange);
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .topbar-user {
            display: flex;
            align-items: center;
            gap: 10px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 8px 14px;
        }

        .topbar-user svg {
            width: 16px;
            height: 16px;
            stroke: var(--orange);
            fill: none;
            stroke-width: 2;
        }

        .topbar-user span {
            font-size: 13px;
            font-weight: 600;
            color: var(--text);
        }

        .btn-new-stand {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--orange);
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: 10px 20px;
            font-family: 'DM Sans', sans-serif;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-new-stand:hover {
            background: var(--orange-dark);
            transform: translateY(-1px);
            box-shadow: 0 8px 24px rgba(240, 90, 26, 0.3);
        }

        .btn-new-stand svg {
            width: 14px;
            height: 14px;
            stroke: currentColor;
            fill: none;
            stroke-width: 2.5;
        }

        /* ── CONTENT ── */
        .content {
            padding: 36px 40px;
            flex: 1;
        }

        /* ── STATS ── */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 36px;
        }

        .stat-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 24px;
            position: relative;
            overflow: hidden;
            transition: border-color 0.2s;
        }

        .stat-card:hover {
            border-color: var(--border2);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
        }

        .stat-card.orange::before {
            background: linear-gradient(to right, var(--orange), var(--orange-dark));
        }

        .stat-card.blue::before {
            background: linear-gradient(to right, var(--blue), #0033aa);
        }

        .stat-card.green::before {
            background: linear-gradient(to right, var(--green), #007a63);
        }

        .stat-card.white::before {
            background: linear-gradient(to right, #fff, rgba(255, 255, 255, 0.3));
        }

        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
        }

        .stat-icon svg {
            width: 18px;
            height: 18px;
            stroke: currentColor;
            fill: none;
            stroke-width: 2;
        }

        .stat-icon.orange {
            background: rgba(240, 90, 26, 0.12);
            color: var(--orange);
        }

        .stat-icon.blue {
            background: rgba(26, 58, 255, 0.12);
            color: #4d6fff;
        }

        .stat-icon.green {
            background: rgba(0, 184, 148, 0.12);
            color: var(--green);
        }

        .stat-icon.white {
            background: rgba(255, 255, 255, 0.08);
            color: #fff;
        }

        .stat-num {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 40px;
            color: #fff;
            line-height: 1;
            margin-bottom: 4px;
        }

        .stat-label {
            font-size: 12px;
            color: var(--muted);
            font-weight: 500;
        }

        /* ── CITY TABS ── */
        .city-tabs {
            display: flex;
            gap: 0;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 4px;
            margin-bottom: 28px;
            width: fit-content;
        }

        .city-tab {
            padding: 9px 20px;
            border-radius: 9px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            color: var(--muted);
            background: none;
            border: none;
            font-family: 'DM Sans', sans-serif;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .city-tab .dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: currentColor;
            opacity: 0.5;
        }

        .city-tab:hover {
            color: var(--text);
        }

        .city-tab.active {
            background: var(--surface2);
            color: #fff;
        }

        .city-tab.active .dot {
            opacity: 1;
        }

        /* ── STANDS GRID ── */
        .stands-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .stand-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.25s;
            animation: cardSlideIn 0.4s ease both;
        }

        @keyframes cardSlideIn {
            from {
                opacity: 0;
                transform: translateY(16px)
            }

            to {
                opacity: 1;
                transform: translateY(0)
            }
        }

        .stand-card:hover {
            border-color: var(--border2);
            transform: translateY(-4px);
            box-shadow: 0 16px 48px rgba(0, 0, 0, 0.4);
        }

        .stand-img {
            height: 180px;
            position: relative;
            overflow: hidden;
            background: var(--surface2);
        }

        .stand-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.4s;
        }

        .stand-card:hover .stand-img img {
            transform: scale(1.05);
        }

        .stand-img-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            gap: 8px;
            color: var(--muted);
        }

        .stand-img-placeholder svg {
            width: 32px;
            height: 32px;
            stroke: currentColor;
            fill: none;
            stroke-width: 1.5;
        }

        .stand-img-placeholder span {
            font-size: 12px;
        }

        .stand-city-badge {
            position: absolute;
            top: 12px;
            left: 12px;
            padding: 4px 10px;
            border-radius: 100px;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            backdrop-filter: blur(10px);
        }

        .stand-body {
            padding: 18px;
        }

        .stand-type {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-bottom: 6px;
        }

        .stand-name {
            font-size: 15px;
            font-weight: 700;
            color: #fff;
            margin-bottom: 6px;
            line-height: 1.3;
        }

        .stand-price {
            font-size: 13px;
            color: var(--muted);
            margin-bottom: 8px;
        }

        .stand-price strong {
            font-weight: 700;
        }

        .stand-desc {
            font-size: 12px;
            color: var(--muted);
            line-height: 1.5;
            margin-bottom: 16px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .stand-actions {
            display: flex;
            gap: 8px;
        }

        .btn-edit,
        .btn-delete {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 9px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            font-family: 'DM Sans', sans-serif;
            transition: all 0.15s;
        }

        .btn-edit {
            background: rgba(255, 255, 255, 0.06);
            color: var(--text);
        }

        .btn-edit:hover {
            background: rgba(240, 90, 26, 0.12);
            color: var(--orange);
        }

        .btn-delete {
            background: rgba(255, 71, 87, 0.08);
            color: #ff6b7a;
        }

        .btn-delete:hover {
            background: rgba(255, 71, 87, 0.18);
        }

        .btn-edit svg,
        .btn-delete svg {
            width: 13px;
            height: 13px;
            stroke: currentColor;
            fill: none;
            stroke-width: 2.5;
        }

        /* ── EMPTY STATE ── */
        .empty-state {
            grid-column: 1 / -1;
            text-align: center;
            padding: 60px 20px;
            color: var(--muted);
        }

        .empty-state svg {
            width: 48px;
            height: 48px;
            stroke: currentColor;
            fill: none;
            stroke-width: 1.5;
            margin-bottom: 16px;
            opacity: 0.4;
        }

        .empty-state p {
            font-size: 14px;
            margin-bottom: 20px;
        }

        /* ── MODAL ── */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 200;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(6px);
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .modal-overlay.open {
            display: flex;
            animation: fadeIn 0.2s ease;
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
            background: var(--surface);
            border: 1px solid var(--border2);
            border-radius: 20px;
            width: 100%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            animation: modalIn 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes modalIn {
            from {
                opacity: 0;
                transform: scale(0.95) translateY(20px)
            }

            to {
                opacity: 1;
                transform: scale(1) translateY(0)
            }
        }

        .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 24px 28px 20px;
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            background: var(--surface);
            z-index: 1;
            border-radius: 20px 20px 0 0;
        }

        .modal-title {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 22px;
            letter-spacing: 1px;
            color: #fff;
        }

        .modal-title span {
            color: var(--orange);
        }

        .modal-close {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.06);
            border: none;
            color: var(--muted);
            cursor: pointer;
            font-size: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.15s;
        }

        .modal-close:hover {
            background: rgba(255, 71, 87, 0.12);
            color: var(--red);
        }

        .modal-body {
            padding: 24px 28px;
        }

        .modal-footer {
            padding: 16px 28px 24px;
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            border-top: 1px solid var(--border);
        }

        /* FORM FIELDS */
        .field-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 16px;
        }

        .field-row.full {
            grid-template-columns: 1fr;
        }

        .field {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .field label {
            font-size: 11px;
            font-weight: 700;
            color: var(--muted);
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .field input,
        .field select,
        .field textarea {
            background: var(--surface2);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 11px 14px;
            color: var(--text);
            font-family: 'DM Sans', sans-serif;
            font-size: 14px;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .field input:focus,
        .field select:focus,
        .field textarea:focus {
            border-color: rgba(240, 90, 26, 0.5);
            box-shadow: 0 0 0 3px rgba(240, 90, 26, 0.1);
        }

        .field select option {
            background: var(--surface2);
        }

        .field textarea {
            resize: vertical;
            min-height: 80px;
        }

        /* IMAGE UPLOAD */
        .img-upload-area {
            border: 2px dashed var(--border);
            border-radius: 12px;
            padding: 24px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            position: relative;
            overflow: hidden;
        }

        .img-upload-area:hover,
        .img-upload-area.drag {
            border-color: rgba(240, 90, 26, 0.4);
            background: rgba(240, 90, 26, 0.04);
        }

        .img-upload-area input[type="file"] {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
        }

        .upload-icon {
            width: 36px;
            height: 36px;
            margin: 0 auto 10px;
            background: rgba(240, 90, 26, 0.1);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .upload-icon svg {
            width: 18px;
            height: 18px;
            stroke: var(--orange);
            fill: none;
            stroke-width: 2;
        }

        .upload-text {
            font-size: 13px;
            color: var(--muted);
        }

        .upload-text strong {
            color: var(--orange);
        }

        .img-preview {
            width: 100%;
            max-height: 160px;
            object-fit: cover;
            border-radius: 8px;
            margin-top: 12px;
            display: none;
        }

        /* MODAL BUTTONS */
        .btn-cancel {
            padding: 10px 22px;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid var(--border);
            color: var(--muted);
            cursor: pointer;
            font-family: 'DM Sans', sans-serif;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.2s;
        }

        .btn-cancel:hover {
            color: var(--text);
            border-color: var(--border2);
        }

        .btn-save {
            padding: 10px 28px;
            border-radius: 10px;
            background: var(--orange);
            border: none;
            color: #fff;
            cursor: pointer;
            font-family: 'DM Sans', sans-serif;
            font-size: 14px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }

        .btn-save:hover {
            background: var(--orange-dark);
            transform: translateY(-1px);
            box-shadow: 0 8px 24px rgba(240, 90, 26, 0.3);
        }

        .btn-save svg {
            width: 14px;
            height: 14px;
            stroke: currentColor;
            fill: none;
            stroke-width: 2.5;
        }

        /* CONFIRM DELETE MODAL */
        .confirm-modal {
            max-width: 420px;
        }

        .confirm-icon {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: rgba(255, 71, 87, 0.1);
            border: 1px solid rgba(255, 71, 87, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
        }

        .confirm-icon svg {
            width: 24px;
            height: 24px;
            stroke: var(--red);
            fill: none;
            stroke-width: 2;
        }

        .confirm-body {
            text-align: center;
            padding: 8px 0 8px;
        }

        .confirm-title {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 24px;
            color: #fff;
            margin-bottom: 8px;
            letter-spacing: 1px;
        }

        .confirm-sub {
            font-size: 14px;
            color: var(--muted);
            line-height: 1.6;
        }

        .btn-delete-confirm {
            padding: 10px 28px;
            border-radius: 10px;
            background: var(--red);
            border: none;
            color: #fff;
            cursor: pointer;
            font-family: 'DM Sans', sans-serif;
            font-size: 14px;
            font-weight: 700;
            transition: all 0.2s;
        }

        .btn-delete-confirm:hover {
            background: #e63946;
            transform: translateY(-1px);
        }

        /* TOAST */
        .toast {
            position: fixed;
            bottom: 28px;
            right: 28px;
            z-index: 999;
            background: var(--surface2);
            border: 1px solid var(--border2);
            border-radius: 12px;
            padding: 14px 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            font-weight: 500;
            color: var(--text);
            transform: translateY(80px);
            opacity: 0;
            transition: all 0.35s cubic-bezier(0.16, 1, 0.3, 1);
            pointer-events: none;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
        }

        .toast.show {
            transform: translateY(0);
            opacity: 1;
        }

        .toast svg {
            width: 18px;
            height: 18px;
            stroke: currentColor;
            fill: none;
            stroke-width: 2.5;
            flex-shrink: 0;
        }

        .toast.success {
            border-color: rgba(0, 184, 148, 0.3);
        }

        .toast.success svg {
            stroke: var(--green);
        }

        .toast.error {
            border-color: rgba(255, 71, 87, 0.3);
        }

        .toast.error svg {
            stroke: var(--red);
        }

        /* LOADING SPINNER */
        .spinner {
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255, 255, 255, 0.2);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* RESPONSIVE */
        @media (max-width: 900px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.open {
                transform: translateX(0);
            }

            .main {
                margin-left: 0;
            }

            .stats-row {
                grid-template-columns: repeat(2, 1fr);
            }

            .topbar {
                padding: 14px 20px;
            }

            .content {
                padding: 24px 20px;
            }
        }

        @media (max-width: 540px) {
            .stats-row {
                grid-template-columns: 1fr 1fr;
            }

            .field-row {
                grid-template-columns: 1fr;
            }

            .stands-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

    <!-- SIDEBAR -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <div class="brand-logo">MAR<span>CA</span> &amp; MEDIOS</div>
            <div class="brand-sub-text">Panel Administrativo</div>
            <div class="admin-badge">
                <svg viewBox="0 0 8 8">
                    <circle cx="4" cy="4" r="4" />
                </svg>
                <?= $admin ?>
            </div>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-section-label">Gestión</div>
            <button class="nav-item active" onclick="showCity('cartagena', this)">
                <svg viewBox="0 0 24 24">
                    <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z" />
                    <circle cx="12" cy="9" r="2.5" />
                </svg>
                Cartagena
                <span class="count"><?= count($byCity['cartagena']) ?></span>
            </button>
            <button class="nav-item" onclick="showCity('barranquilla', this)">
                <svg viewBox="0 0 24 24">
                    <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z" />
                    <circle cx="12" cy="9" r="2.5" />
                </svg>
                Barranquilla
                <span class="count"><?= count($byCity['barranquilla']) ?></span>
            </button>
            <button class="nav-item" onclick="showCity('santamarta', this)">
                <svg viewBox="0 0 24 24">
                    <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z" />
                    <circle cx="12" cy="9" r="2.5" />
                </svg>
                Santa Marta
                <span class="count"><?= count($byCity['santamarta']) ?></span>
            </button>

            <div class="nav-divider"></div>
            <div class="nav-section-label">Sistema</div>
            <a class="nav-item" href="index.php" target="_blank">
                <svg viewBox="0 0 24 24">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                    <polyline points="9,22 9,12 15,12 15,22" />
                </svg>
                Ver sitio web
            </a>
        </nav>

        <div class="sidebar-footer">
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
        <!-- TOPBAR -->
        <header class="topbar">
            <div class="topbar-title">PANEL <span>ADMIN</span></div>
            <div class="topbar-right">
                <div class="topbar-user">
                    <svg viewBox="0 0 24 24">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                        <circle cx="12" cy="7" r="4" />
                    </svg>
                    <span><?= $admin ?></span>
                </div>
                <button class="btn-new-stand" onclick="openModal()">
                    <svg viewBox="0 0 24 24">
                        <line x1="12" y1="5" x2="12" y2="19" />
                        <line x1="5" y1="12" x2="19" y2="12" />
                    </svg>
                    Nuevo Stand
                </button>
            </div>
        </header>

        <!-- CONTENT -->
        <div class="content">

            <!-- STATS -->
            <div class="stats-row">
                <div class="stat-card orange">
                    <div class="stat-icon orange">
                        <svg viewBox="0 0 24 24">
                            <rect x="2" y="3" width="20" height="14" rx="2" />
                            <line x1="8" y1="21" x2="16" y2="21" />
                            <line x1="12" y1="17" x2="12" y2="21" />
                        </svg>
                    </div>
                    <div class="stat-num"><?= $totalStands ?></div>
                    <div class="stat-label">Stands totales</div>
                </div>
                <div class="stat-card orange">
                    <div class="stat-icon orange">
                        <svg viewBox="0 0 24 24">
                            <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z" />
                        </svg>
                    </div>
                    <div class="stat-num"><?= count($byCity['cartagena']) ?></div>
                    <div class="stat-label">Cartagena</div>
                </div>
                <div class="stat-card blue">
                    <div class="stat-icon blue">
                        <svg viewBox="0 0 24 24">
                            <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z" />
                        </svg>
                    </div>
                    <div class="stat-num"><?= count($byCity['barranquilla']) ?></div>
                    <div class="stat-label">Barranquilla</div>
                </div>
                <div class="stat-card green">
                    <div class="stat-icon green">
                        <svg viewBox="0 0 24 24">
                            <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z" />
                        </svg>
                    </div>
                    <div class="stat-num"><?= count($byCity['santamarta']) ?></div>
                    <div class="stat-label">Santa Marta</div>
                </div>
            </div>

            <!-- CITY TABS -->
            <div class="city-tabs" id="cityTabs">
                <button class="city-tab active" id="tab-cartagena" style="color:#f05a1a" onclick="showCity('cartagena', null, this)">
                    <span class="dot"></span> Cartagena
                </button>
                <button class="city-tab" id="tab-barranquilla" style="--c:#1a3aff" onclick="showCity('barranquilla', null, this)">
                    <span class="dot" style="background:#1a3aff"></span> Barranquilla
                </button>
                <button class="city-tab" id="tab-santamarta" onclick="showCity('santamarta', null, this)">
                    <span class="dot" style="background:#00b894"></span> Santa Marta
                </button>
            </div>

            <!-- STANDS PANELS -->
            <?php foreach ($cities as $cityKey => $cityData): ?>
                <div class="city-panel" id="panel-<?= $cityKey ?>" style="display:<?= $cityKey === 'cartagena' ? 'block' : 'none' ?>">
                    <div class="stands-grid" id="grid-<?= $cityKey ?>">
                        <?php if (empty($byCity[$cityKey])): ?>
                            <div class="empty-state">
                                <svg viewBox="0 0 24 24">
                                    <rect x="2" y="3" width="20" height="14" rx="2" />
                                    <line x1="8" y1="21" x2="16" y2="21" />
                                    <line x1="12" y1="17" x2="12" y2="21" />
                                </svg>
                                <p>No hay stands en <?= $cityData['label'] ?> todavía.</p>
                                <button class="btn-new-stand" onclick="openModal('<?= $cityKey ?>')">
                                    <svg viewBox="0 0 24 24">
                                        <line x1="12" y1="5" x2="12" y2="19" />
                                        <line x1="5" y1="12" x2="19" y2="12" />
                                    </svg>
                                    Agregar primer stand
                                </button>
                            </div>
                        <?php else: ?>
                            <?php foreach ($byCity[$cityKey] as $stand): ?>
                                <div class="stand-card" id="card-<?= $stand['id'] ?>">
                                    <div class="stand-img">
                                        <?php if ($stand['imagen'] && file_exists($stand['imagen'])): ?>
                                            <img src="<?= htmlspecialchars($stand['imagen']) ?>?v=<?= time() ?>" alt="<?= htmlspecialchars($stand['nombre']) ?>" loading="lazy" />
                                        <?php else: ?>
                                            <div class="stand-img-placeholder">
                                                <svg viewBox="0 0 24 24">
                                                    <rect x="3" y="3" width="18" height="18" rx="2" />
                                                    <circle cx="8.5" cy="8.5" r="1.5" />
                                                    <polyline points="21 15 16 10 5 21" />
                                                </svg>
                                                <span>Sin imagen</span>
                                            </div>
                                        <?php endif; ?>
                                        <div class="stand-city-badge" style="background:<?= $cityData['color'] ?>22;color:<?= $cityData['color'] ?>;border:1px solid <?= $cityData['color'] ?>44">
                                            <?= $cityData['abbr'] ?>
                                        </div>
                                    </div>
                                    <div class="stand-body">
                                        <div class="stand-type" style="color:<?= $cityData['color'] ?>"><?= htmlspecialchars($stand['tipo']) ?></div>
                                        <div class="stand-name"><?= htmlspecialchars($stand['nombre']) ?></div>
                                        <div class="stand-price">Precio: <strong style="color:<?= $cityData['color'] ?>"><?= htmlspecialchars($stand['precio']) ?></strong></div>
                                        <?php if ($stand['descripcion']): ?>
                                            <div class="stand-desc"><?= htmlspecialchars($stand['descripcion']) ?></div>
                                        <?php endif; ?>
                                        <div class="stand-actions">
                                            <button class="btn-edit" onclick='editStand(<?= json_encode($stand) ?>)'>
                                                <svg viewBox="0 0 24 24">
                                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                                                </svg>
                                                Editar
                                            </button>
                                            <button class="btn-delete" onclick="confirmDelete(<?= $stand['id'] ?>, '<?= htmlspecialchars(addslashes($stand['nombre'])) ?>')">
                                                <svg viewBox="0 0 24 24">
                                                    <polyline points="3 6 5 6 21 6" />
                                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2" />
                                                </svg>
                                                Eliminar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>

        </div><!-- /content -->
    </div><!-- /main -->

    <!-- ── MODAL CREAR/EDITAR STAND ── -->
    <div class="modal-overlay" id="modalOverlay" onclick="closeModal(event)">
        <div class="modal" onclick="event.stopPropagation()">
            <div class="modal-header">
                <div class="modal-title" id="modalTitle">NUEVO <span>STAND</span></div>
                <button class="modal-close" onclick="closeModal()">✕</button>
            </div>
            <div class="modal-body">
                <form id="standForm" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="save_stand" />
                    <input type="hidden" name="id" id="f_id" value="0" />
                    <input type="hidden" name="imagen_actual" id="f_imagen_actual" value="" />

                    <div class="field-row">
                        <div class="field">
                            <label>Ciudad</label>
                            <select name="ciudad" id="f_ciudad" required>
                                <option value="">Seleccione ciudad</option>

                                <?php foreach ($ciudades as $c): ?>
                                    <option value="<?= $c['id'] ?>">
                                        <?= htmlspecialchars($c['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>

                            </select>
                        </div>
                        <div class="field">
                            <label>Tipo de stand</label>
                            <input type="text" name="tipo" id="f_tipo" placeholder="Ej: Stand Isla" required />
                        </div>
                    </div>

                    <div class="field-row full">
                        <div class="field">
                            <label>Nombre del stand</label>
                            <input type="text" name="nombre" id="f_nombre" placeholder="Ej: Stand Tipo Isla con Esquinero" required />
                        </div>
                    </div>

                    <div class="field-row">
                        <div class="field">
                            <label>Precio</label>
                            <input type="text" name="precio" id="f_precio" placeholder="Ej: Desde $6.000.000" required />
                        </div>
                        <div class="field">
                            <label>Orden (en catálogo)</label>
                            <input type="number" name="orden" id="f_orden" value="0" min="0" />
                        </div>
                    </div>

                    <div class="field-row full" style="margin-bottom:16px">
                        <div class="field">
                            <label>Descripción</label>
                            <textarea name="descripcion" id="f_desc" placeholder="Descripción breve del stand..."></textarea>
                        </div>
                    </div>

                    <div class="field">
                        <label>Imagen del stand</label>
                        <div class="img-upload-area" id="uploadArea">
                            <input type="file" name="imagen" id="f_imagen" accept="image/jpg,image/jpeg,image/png,image/webp" onchange="previewImage(this)" />
                            <div class="upload-icon"><svg viewBox="0 0 24 24">
                                    <rect x="3" y="3" width="18" height="18" rx="2" />
                                    <circle cx="8.5" cy="8.5" r="1.5" />
                                    <polyline points="21 15 16 10 5 21" />
                                </svg></div>
                            <div class="upload-text"><strong>Clic para subir</strong> o arrastra aquí<br><small style="color:var(--muted)">JPG, PNG o WEBP — máx 5MB</small></div>
                            <img id="imgPreview" class="img-preview" src="" alt="Vista previa" />
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn-cancel" onclick="closeModal()">Cancelar</button>
                <button class="btn-save" id="btnSave" onclick="saveStand()">
                    <svg viewBox="0 0 24 24">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z" />
                        <polyline points="17 21 17 13 7 13 7 21" />
                        <polyline points="7 3 7 8 15 8" />
                    </svg>
                    Guardar Stand
                </button>
            </div>
        </div>
    </div>

    <!-- ── MODAL CONFIRMAR ELIMINAR ── -->
    <div class="modal-overlay" id="deleteOverlay" onclick="closeDelete(event)">
        <div class="modal confirm-modal" onclick="event.stopPropagation()">
            <div class="modal-header">
                <div class="modal-title">ELIMINAR <span>STAND</span></div>
                <button class="modal-close" onclick="closeDelete()">✕</button>
            </div>
            <div class="modal-body">
                <div class="confirm-body">
                    <div class="confirm-icon">
                        <svg viewBox="0 0 24 24">
                            <polyline points="3 6 5 6 21 6" />
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2" />
                        </svg>
                    </div>
                    <div class="confirm-title">¿Estás seguro?</div>
                    <div class="confirm-sub">Vas a eliminar <strong id="deleteName" style="color:#fff"></strong>.<br>Esta acción no se puede deshacer.</div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-cancel" onclick="closeDelete()">Cancelar</button>
                <button class="btn-delete-confirm" id="btnConfirmDelete" onclick="doDelete()">Sí, eliminar</button>
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
        // ── DATA ──────────────────────────────────────────────────────────────────────
        const cityColors = {
            cartagena: '#f05a1a',
            barranquilla: '#1a3aff',
            santamarta: '#00b894'
        };
        let deleteId = 0;
        let currentCity = 'cartagena';

        // ── CITY TABS ─────────────────────────────────────────────────────────────────
        function showCity(city, sidebarBtn = null, tabBtn = null) {
            currentCity = city;

            // Panels
            document.querySelectorAll('.city-panel').forEach(p => p.style.display = 'none');
            document.getElementById('panel-' + city).style.display = 'block';

            // Sidebar nav
            if (sidebarBtn) {
                document.querySelectorAll('.nav-item').forEach(b => b.classList.remove('active'));
                sidebarBtn.classList.add('active');
            }

            // Tab buttons
            document.querySelectorAll('.city-tab').forEach(t => t.classList.remove('active'));
            const activeTab = tabBtn || document.getElementById('tab-' + city);
            if (activeTab) activeTab.classList.add('active');
        }

        // ── MODAL CREAR/EDITAR ────────────────────────────────────────────────────────
        function openModal(city = null) {
            clearForm();
            if (city) document.getElementById('f_ciudad').value = city;
            else document.getElementById('f_ciudad').value = currentCity;
            document.getElementById('modalTitle').innerHTML = 'NUEVO <span>STAND</span>';
            document.getElementById('modalOverlay').classList.add('open');
        }

        function editStand(stand) {
            document.getElementById('modalTitle').innerHTML = 'EDITAR <span>STAND</span>';
            document.getElementById('f_id').value = stand.id;
            document.getElementById('f_ciudad').value = stand.ciudad;
            document.getElementById('f_tipo').value = stand.tipo;
            document.getElementById('f_nombre').value = stand.nombre;
            document.getElementById('f_precio').value = stand.precio;
            document.getElementById('f_orden').value = stand.orden;
            document.getElementById('f_desc').value = stand.descripcion || '';
            document.getElementById('f_imagen_actual').value = stand.imagen || '';

            // Preview imagen existente
            const preview = document.getElementById('imgPreview');
            if (stand.imagen) {
                preview.src = '../' + stand.imagen;
                preview.style.display = 'block';
            } else {
                preview.style.display = 'none';
            }
            document.getElementById('modalOverlay').classList.add('open');
        }

        function closeModal(e) {
            if (e && e.target !== document.getElementById('modalOverlay')) return;
            document.getElementById('modalOverlay').classList.remove('open');
            clearForm();
        }

        function clearForm() {
            document.getElementById('standForm').reset();
            document.getElementById('f_id').value = '0';
            document.getElementById('f_imagen_actual').value = '';
            document.getElementById('imgPreview').style.display = 'none';
        }

        function previewImage(input) {
            const file = input.files[0];
            if (!file) return;
            if (file.size > 5 * 1024 * 1024) {
                showToast('La imagen no debe superar 5MB', 'error');
                input.value = '';
                return;
            }
            const reader = new FileReader();
            reader.onload = e => {
                const prev = document.getElementById('imgPreview');
                prev.src = e.target.result;
                prev.style.display = 'block';
            };
            reader.readAsDataURL(file);
        }

        // ── GUARDAR STAND ─────────────────────────────────────────────────────────────
        async function saveStand() {
            const btn = document.getElementById('btnSave');
            const origHtml = btn.innerHTML;
            btn.innerHTML = '<div class="spinner"></div> Guardando...';
            btn.disabled = true;

            const formData = new FormData(document.getElementById('standForm'));

            try {
                const res = await fetch('dashboard.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                if (data.ok) {
                    showToast('Stand guardado correctamente', 'success');
                    closeModal();
                    setTimeout(() => location.reload(), 800);
                } else {
                    showToast('Error al guardar', 'error');
                }
            } catch (err) {
                showToast('Error de conexión', 'error');
            }
            btn.innerHTML = origHtml;
            btn.disabled = false;
        }

        // ── ELIMINAR STAND ────────────────────────────────────────────────────────────
        function confirmDelete(id, name) {
            deleteId = id;
            document.getElementById('deleteName').textContent = name;
            document.getElementById('deleteOverlay').classList.add('open');
        }

        function closeDelete(e) {
            if (e && e.target !== document.getElementById('deleteOverlay')) return;
            document.getElementById('deleteOverlay').classList.remove('open');
        }
        async function doDelete() {
            const btn = document.getElementById('btnConfirmDelete');
            btn.textContent = 'Eliminando...';
            btn.disabled = true;

            const fd = new FormData();
            fd.append('action', 'delete_stand');
            fd.append('id', deleteId);

            try {
                const res = await fetch('dashboard.php', {
                    method: 'POST',
                    body: fd
                });
                const data = await res.json();
                if (data.ok) {
                    showToast('Stand eliminado', 'success');
                    closeDelete();
                    const card = document.getElementById('card-' + deleteId);
                    if (card) {
                        card.style.opacity = '0';
                        card.style.transform = 'scale(0.9)';
                        card.style.transition = '0.3s';
                        setTimeout(() => {
                            card.remove();
                        }, 300);
                    }
                } else {
                    showToast('Error al eliminar', 'error');
                }
            } catch {
                showToast('Error de conexión', 'error');
            }
            btn.textContent = 'Sí, eliminar';
            btn.disabled = false;
        }

        // ── LOGOUT ────────────────────────────────────────────────────────────────────
        async function doLogout() {
            const fd = new FormData();
            fd.append('action', 'logout');
            await fetch('dashboard.php', {
                method: 'POST',
                body: fd
            });
            window.location.href = 'login.php';
        }

        // ── TOAST ─────────────────────────────────────────────────────────────────────
        let toastTimer;

        function showToast(msg, type = 'success') {
            const toast = document.getElementById('toast');
            const icon = document.getElementById('toastIcon');
            document.getElementById('toastMsg').textContent = msg;
            toast.className = 'toast ' + type + ' show';
            icon.innerHTML = type === 'success' ?
                '<polyline points="20 6 9 17 4 12"/>' :
                '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>';
            clearTimeout(toastTimer);
            toastTimer = setTimeout(() => toast.classList.remove('show'), 3000);
        }

        // ── DRAG & DROP IMAGEN ────────────────────────────────────────────────────────
        const uploadArea = document.getElementById('uploadArea');
        uploadArea.addEventListener('dragover', e => {
            e.preventDefault();
            uploadArea.classList.add('drag');
        });
        uploadArea.addEventListener('dragleave', () => uploadArea.classList.remove('drag'));
        uploadArea.addEventListener('drop', e => {
            e.preventDefault();
            uploadArea.classList.remove('drag');
            const file = e.dataTransfer.files[0];
            if (file) {
                document.getElementById('f_imagen').files = e.dataTransfer.files;
                previewImage(document.getElementById('f_imagen'));
            }
        });

        // ── ESC PARA CERRAR MODALES ───────────────────────────────────────────────────
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') {
                closeModal();
                closeDelete();
            }
        });
    </script>
</body>

</html>