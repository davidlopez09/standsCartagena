<?php
session_start();

if (!isset($_SESSION['admin_logged']) || $_SESSION['admin_logged'] !== true) {
    header('Location: login.php');
    exit;
}

require_once './app/conexion.php';
$admin = htmlspecialchars($_SESSION['admin_usuario']);

// ─── Mapeo colores por id_ciudad ──────────────────────────────────────────────
$cityMeta = [
    1 => ['label' => 'Cartagena',   'color' => '#f05a1a', 'abbr' => 'CTG'],
    2 => ['label' => 'Santa Marta', 'color' => '#00b894', 'abbr' => 'SMR'],
    3 => ['label' => 'Barranquilla', 'color' => '#1a3aff', 'abbr' => 'BQA'],
    4 => ['label' => 'Index',       'color' => '#9b59b6', 'abbr' => 'IDX'],
];

// ═══════════════════════════════════════════════════════════════════════════════
// AJAX ACTIONS
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

    // ── GUARDAR STAND ────────────────────────────────────────────────────────
    if ($action === 'save_stand') {
        $id        = intval($_POST['id']          ?? 0);
        $id_ciudad = intval($_POST['id_ciudad']   ?? 0);
        $tipo      = trim($_POST['tipo']          ?? '');
        $nombre    = trim($_POST['nombre']        ?? '');
        $precio    = trim($_POST['precio']        ?? '');
        $desc      = trim($_POST['descripcion']   ?? '');
        $orden     = intval($_POST['orden']       ?? 0);
        $activo    = intval($_POST['activo']      ?? 1);
        $destacado = intval($_POST['destacado']   ?? 0);

        $imagen_path = trim($_POST['imagen_actual'] ?? '');
        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
            $ext_allowed = ['jpg', 'jpeg', 'png', 'webp'];
            $ext = strtolower(pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $ext_allowed)) {
                $upload_dir = 'public/images/stands/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
                $filename = uniqid('stand_') . '.' . $ext;
                if (move_uploaded_file($_FILES['imagen']['tmp_name'], $upload_dir . $filename)) {
                    // Borrar imagen anterior si existe
                    if ($imagen_path && file_exists($imagen_path)) @unlink($imagen_path);
                    $imagen_path = $upload_dir . $filename;
                }
            }
        }

        if ($id > 0) {
            $stmt = $pdo->prepare("UPDATE stands SET id_ciudad=?,tipo=?,nombre=?,precio=?,descripcion=?,imagen=?,orden=?,activo=?,destacado=?,updated_at=NOW() WHERE id=?");
            $stmt->execute([$id_ciudad, $tipo, $nombre, $precio, $desc, $imagen_path, $orden, $activo, $destacado, $id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO stands (id_ciudad,tipo,nombre,precio,descripcion,imagen,orden,activo,destacado) VALUES (?,?,?,?,?,?,?,?,?)");
            $stmt->execute([$id_ciudad, $tipo, $nombre, $precio, $desc, $imagen_path, $orden, $activo, $destacado]);
            $id = $pdo->lastInsertId();
        }
        echo json_encode(['ok' => true, 'id' => $id, 'imagen' => $imagen_path]);
        exit;
    }

    // ── ELIMINAR STAND ───────────────────────────────────────────────────────
    if ($action === 'delete_stand') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare("SELECT imagen FROM stands WHERE id=?");
            $stmt->execute([$id]);
            $row = $stmt->fetch();
            if ($row && $row['imagen'] && file_exists($row['imagen'])) @unlink($row['imagen']);
            $pdo->prepare("DELETE FROM stands WHERE id=?")->execute([$id]);
        }
        echo json_encode(['ok' => true]);
        exit;
    }

    // ── TOGGLE DESTACADO ─────────────────────────────────────────────────────
    if ($action === 'toggle_destacado') {
        $id  = intval($_POST['id']        ?? 0);
        $val = intval($_POST['destacado'] ?? 0);
        $pdo->prepare("UPDATE stands SET destacado=? WHERE id=?")->execute([$val, $id]);
        echo json_encode(['ok' => true]);
        exit;
    }

    // ── GUARDAR CIUDAD ───────────────────────────────────────────────────────
    if ($action === 'save_ciudad') {
        $id_c   = intval($_POST['ciudad_id']     ?? 0);
        $nombre = trim($_POST['ciudad_nombre']   ?? '');
        if ($nombre === '') {
            echo json_encode(['ok' => false, 'msg' => 'Nombre requerido']);
            exit;
        }
        if ($id_c > 0) {
            $pdo->prepare("UPDATE ciudades SET nombre=? WHERE id=?")->execute([$nombre, $id_c]);
        } else {
            $pdo->prepare("INSERT INTO ciudades (nombre) VALUES (?)")->execute([$nombre]);
            $id_c = $pdo->lastInsertId();
        }
        echo json_encode(['ok' => true, 'id' => $id_c]);
        exit;
    }

    // ── ELIMINAR CIUDAD ──────────────────────────────────────────────────────
    if ($action === 'delete_ciudad') {
        $id_c = intval($_POST['ciudad_id'] ?? 0);
        if ($id_c > 0) {
            // Obtener imágenes de stands de esa ciudad y borrarlas
            $rows = $pdo->prepare("SELECT imagen FROM stands WHERE id_ciudad=?");
            $rows->execute([$id_c]);
            foreach ($rows->fetchAll() as $r) {
                if ($r['imagen'] && file_exists($r['imagen'])) @unlink($r['imagen']);
            }
            // Obtener imagen principal y borrarla
            $imgRow = $pdo->prepare("SELECT ruta FROM imagesprincipales WHERE id_ciudad=?");
            $imgRow->execute([$id_c]);
            $imgR = $imgRow->fetch();
            if ($imgR && file_exists($imgR['ruta'])) @unlink($imgR['ruta']);
            // La FK CASCADE borrará stands e imagesprincipales automáticamente
            $pdo->prepare("DELETE FROM ciudades WHERE id=?")->execute([$id_c]);
        }
        echo json_encode(['ok' => true]);
        exit;
    }

    // ── GUARDAR IMAGEN PRINCIPAL ─────────────────────────────────────────────
    if ($action === 'save_imagen_principal') {
        $id_ciudad = intval($_POST['img_ciudad'] ?? 0);
        if ($id_ciudad <= 0) {
            echo json_encode(['ok' => false, 'msg' => 'Ciudad inválida']);
            exit;
        }

        // Verificar si ya existe imagen
        $existing = $pdo->prepare("SELECT ruta FROM imagesprincipales WHERE id_ciudad=?");
        $existing->execute([$id_ciudad]);
        $existRow = $existing->fetch();

        if (!isset($_FILES['img_principal']) || $_FILES['img_principal']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['ok' => false, 'msg' => 'No se recibió imagen']);
            exit;
        }
        $ext_allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $ext = strtolower(pathinfo($_FILES['img_principal']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $ext_allowed)) {
            echo json_encode(['ok' => false, 'msg' => 'Formato no permitido. Use JPG, PNG o WEBP']);
            exit;
        }

        $upload_dir = 'public/images/hero/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
        $filename = 'hero_ciudad_' . $id_ciudad . '_' . time() . '.' . $ext;
        if (!move_uploaded_file($_FILES['img_principal']['tmp_name'], $upload_dir . $filename)) {
            echo json_encode(['ok' => false, 'msg' => 'Error al subir imagen']);
            exit;
        }
        $nueva_ruta = $upload_dir . $filename;

        if ($existRow) {
            // Borrar imagen anterior del disco
            if ($existRow['ruta'] && file_exists($existRow['ruta'])) @unlink($existRow['ruta']);
            $pdo->prepare("UPDATE imagesprincipales SET ruta=? WHERE id_ciudad=?")->execute([$nueva_ruta, $id_ciudad]);
        } else {
            $pdo->prepare("INSERT INTO imagesprincipales (id_ciudad,ruta) VALUES (?,?)")->execute([$id_ciudad, $nueva_ruta]);
        }
        echo json_encode(['ok' => true, 'ruta' => $nueva_ruta, 'had_previous' => (bool)$existRow]);
        exit;
    }

    // ── ELIMINAR IMAGEN PRINCIPAL ────────────────────────────────────────────
    if ($action === 'delete_imagen_principal') {
        $id_ciudad = intval($_POST['img_ciudad'] ?? 0);
        $imgRow = $pdo->prepare("SELECT ruta FROM imagesprincipales WHERE id_ciudad=?");
        $imgRow->execute([$id_ciudad]);
        $r = $imgRow->fetch();
        if ($r && file_exists($r['ruta'])) @unlink($r['ruta']);
        $pdo->prepare("DELETE FROM imagesprincipales WHERE id_ciudad=?")->execute([$id_ciudad]);
        echo json_encode(['ok' => true]);
        exit;
    }

    echo json_encode(['ok' => false, 'msg' => 'Acción no reconocida']);
    exit;
}

// ═══════════════════════════════════════════════════════════════════════════════
// LECTURA DE DATOS
// ═══════════════════════════════════════════════════════════════════════════════
$ciudadesRaw = $pdo->query("SELECT * FROM ciudades ORDER BY id ASC")->fetchAll();

$stands = $pdo->query("
    SELECT s.*, c.nombre AS ciudad_nombre
    FROM stands s
    JOIN ciudades c ON c.id = s.id_ciudad
    ORDER BY s.id_ciudad ASC, s.orden ASC, s.id ASC
")->fetchAll();

$byCity = [];
foreach ($ciudadesRaw as $c) $byCity[$c['id']] = [];
foreach ($stands as $s)      $byCity[$s['id_ciudad']][] = $s;

$imagenesHero = $pdo->query("SELECT * FROM imagesprincipales")->fetchAll(PDO::FETCH_ASSOC);
$heroByCity = [];
foreach ($imagenesHero as $h) $heroByCity[$h['id_ciudad']] = $h;

$totalStands = count($stands);

// Merge cityMeta con ciudades de BD
foreach ($ciudadesRaw as $c) {
    if (!isset($cityMeta[$c['id']])) {
        $cityMeta[$c['id']] = ['label' => $c['nombre'], 'color' => '#888', 'abbr' => strtoupper(substr($c['nombre'], 0, 3))];
    }
}
?>
<!doctype html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1.0" />
    <title>Dashboard — Marca & Medios</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="./public/css/dashboard/dashboard.css" />
</head>

<body>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- SIDEBAR -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <div class="brand-logo">MAR<span>CA</span> &amp; MEDIOS</div>
            <div class="brand-sub-text">Panel Administrativo</div>
            <div class="admin-badge"><svg viewBox="0 0 8 8">
                    <circle cx="4" cy="4" r="4" />
                </svg><?= $admin ?></div>
        </div>
        <nav class="sidebar-nav">
            <div class="nav-section-label">Gestión</div>
            <button class="nav-item active" onclick="switchSection('stands',this)">
                <svg viewBox="0 0 24 24">
                    <rect x="2" y="3" width="20" height="14" rx="2" />
                    <line x1="8" y1="21" x2="16" y2="21" />
                    <line x1="12" y1="17" x2="12" y2="21" />
                </svg>
                Stands
                <span class="count"><?= $totalStands ?></span>
            </button>
            <button class="nav-item" onclick="switchSection('ciudades',this)">
                <svg viewBox="0 0 24 24">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                    <polyline points="9,22 9,12 15,12 15,22" />
                </svg>
                Ciudades
                <span class="count"><?= count($ciudadesRaw) ?></span>
            </button>
            <button class="nav-item" onclick="switchSection('imagenes',this)">
                <svg viewBox="0 0 24 24">
                    <rect x="3" y="3" width="18" height="18" rx="2" />
                    <circle cx="8.5" cy="8.5" r="1.5" />
                    <polyline points="21 15 16 10 5 21" />
                </svg>
                Imágenes Hero
                <span class="count"><?= count($heroByCity) ?></span>
            </button>
            <div class="nav-divider"></div>
            <div class="nav-section-label">Sistema</div>
            <a class="nav-item" href="index.php" target="_blank">
                <svg viewBox="0 0 24 24">
                    <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 0 2-2h6" />
                    <polyline points="15 3 21 3 21 9" />
                    <line x1="10" y1="14" x2="21" y2="3" />
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
        <header class="topbar">

            <!-- AÑADIR ESTO AL INICIO -->
            <button class="topbar-hamburger" id="dashHamburger" aria-label="Abrir menú">
                <span></span>
                <span></span>
                <span></span>
            </button>

            <div class="topbar-title">PANEL <span>ADMIN</span></div>
            <div class="topbar-right">
                <div class="topbar-user">
                    <svg viewBox="0 0 24 24">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                        <circle cx="12" cy="7" r="4" />
                    </svg>
                    <span><?= $admin ?></span>
                </div>
                <button class="btn-primary-sm" id="topbarAddBtn" onclick="openModal()">
                    <svg viewBox="0 0 24 24">
                        <line x1="12" y1="5" x2="12" y2="19" />
                        <line x1="5" y1="12" x2="19" y2="12" />
                    </svg>
                    Nuevo Stand
                </button>
            </div>
        </header>

        <div class="content">
            <!-- STATS -->
            <div class="stats-row">
                <div class="stat-card c-orange">
                    <div class="stat-icon c-orange"><svg viewBox="0 0 24 24">
                            <rect x="2" y="3" width="20" height="14" rx="2" />
                            <line x1="8" y1="21" x2="16" y2="21" />
                            <line x1="12" y1="17" x2="12" y2="21" />
                        </svg></div>
                    <div class="stat-num"><?= $totalStands ?></div>
                    <div class="stat-label">Stands totales</div>
                </div>
                <?php foreach (array_slice($ciudadesRaw, 0, 3) as $c):
                    $cls = $c['id'] == 3 ? 'c-blue' : ($c['id'] == 2 ? 'c-green' : 'c-orange');
                    $cnt = count($byCity[$c['id']] ?? []);
                ?>
                    <div class="stat-card <?= $cls ?>">
                        <div class="stat-icon <?= $cls ?>"><svg viewBox="0 0 24 24">
                                <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z" />
                            </svg></div>
                        <div class="stat-num"><?= $cnt ?></div>
                        <div class="stat-label"><?= htmlspecialchars($c['nombre']) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- ═══ SECCIÓN STANDS ═══ -->
            <div id="section-stands">
                <div class="city-tabs" id="cityTabs">
                    <?php foreach ($ciudadesRaw as $i => $c):
                        $meta = $cityMeta[$c['id']] ?? ['color' => '#fff'];
                        if ($c['nombre'] === 'index') continue; // Ocultar "index" de tabs de stands
                    ?>
                        <button class="city-tab <?= $i === 0 ? 'active' : '' ?>"
                            id="tab-city-<?= $c['id'] ?>"
                            style="color:<?= $meta['color'] ?>"
                            onclick="showCity(<?= $c['id'] ?>,this)">
                            <span class="dot" style="background:<?= $meta['color'] ?>"></span>
                            <?= htmlspecialchars($c['nombre']) ?>
                        </button>
                    <?php endforeach; ?>
                </div>

                <?php foreach ($ciudadesRaw as $i => $c):
                    if ($c['nombre'] === 'index') continue;
                    $meta = $cityMeta[$c['id']] ?? ['color' => '#f05a1a', 'abbr' => '???'];
                    $cityStands = $byCity[$c['id']] ?? [];
                ?>
                    <div class="city-panel" id="panel-city-<?= $c['id'] ?>" style="display:<?= $i === 0 ? 'block' : 'none' ?>">
                        <div class="stands-grid" id="grid-city-<?= $c['id'] ?>">
                            <?php if (empty($cityStands)): ?>
                                <div class="empty-state">
                                    <svg viewBox="0 0 24 24">
                                        <rect x="2" y="3" width="20" height="14" rx="2" />
                                        <line x1="8" y1="21" x2="16" y2="21" />
                                        <line x1="12" y1="17" x2="12" y2="21" />
                                    </svg>
                                    <p>No hay stands en <?= htmlspecialchars($c['nombre']) ?> todavía.</p>
                                    <button class="btn-primary-sm" onclick="openModal(<?= $c['id'] ?>)">
                                        <svg viewBox="0 0 24 24">
                                            <line x1="12" y1="5" x2="12" y2="19" />
                                            <line x1="5" y1="12" x2="19" y2="12" />
                                        </svg>
                                        Agregar primer stand
                                    </button>
                                </div>
                                <?php else: foreach ($cityStands as $stand): ?>
                                    <div class="stand-card" id="card-<?= $stand['id'] ?>">
                                        <div class="stand-img">
                                            <?php if ($stand['imagen'] && file_exists($stand['imagen'])): ?>
                                                <img src="<?= htmlspecialchars($stand['imagen']) ?>?v=<?= time() ?>" alt="<?= htmlspecialchars($stand['nombre']) ?>" loading="lazy" />
                                            <?php else: ?>
                                                <div class="stand-img-placeholder"><svg viewBox="0 0 24 24">
                                                        <rect x="3" y="3" width="18" height="18" rx="2" />
                                                        <circle cx="8.5" cy="8.5" r="1.5" />
                                                        <polyline points="21 15 16 10 5 21" />
                                                    </svg><span>Sin imagen</span></div>
                                            <?php endif; ?>
                                            <div class="stand-badge" style="background:<?= $meta['color'] ?>22;color:<?= $meta['color'] ?>;border:1px solid <?= $meta['color'] ?>44"><?= $meta['abbr'] ?></div>
                                            <?php if ($stand['destacado']): ?>
                                                <div class="destacado-badge">★ Destacado</div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="stand-body">
                                            <div class="stand-type" style="color:<?= $meta['color'] ?>"><?= htmlspecialchars($stand['tipo']) ?></div>
                                            <div class="stand-name"><?= htmlspecialchars($stand['nombre']) ?></div>
                                            <div class="stand-price">Precio: <strong style="color:<?= $meta['color'] ?>"><?= htmlspecialchars($stand['precio']) ?></strong></div>
                                            <?php if ($stand['descripcion']): ?><div class="stand-desc"><?= htmlspecialchars($stand['descripcion']) ?></div><?php endif; ?>
                                            <div class="stand-actions">
                                                <button class="btn-act edit" onclick='editStand(<?= json_encode($stand) ?>)'>
                                                    <svg viewBox="0 0 24 24">
                                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                                                    </svg>
                                                    Editar
                                                </button>
                                                <button class="btn-act star <?= $stand['destacado'] ? 'on' : '' ?>" onclick="toggleDestacado(<?= $stand['id'] ?>,<?= $stand['destacado'] ? 0 : 1 ?>,this)" title="<?= $stand['destacado'] ? 'Quitar destacado' : 'Marcar destacado' ?>">
                                                    <svg viewBox="0 0 24 24">
                                                        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" />
                                                    </svg>
                                                </button>
                                                <button class="btn-act del" onclick="confirmDelete(<?= $stand['id'] ?>,'<?= htmlspecialchars(addslashes($stand['nombre'])) ?>')">
                                                    <svg viewBox="0 0 24 24">
                                                        <polyline points="3 6 5 6 21 6" />
                                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2" />
                                                    </svg>
                                                    Eliminar
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                            <?php endforeach;
                            endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- ═══ SECCIÓN CIUDADES ═══ -->
            <div id="section-ciudades" style="display:none">
                <div class="table-wrap">
                    <div class="table-header">
                        <div class="table-title">CIUDADES REGISTRADAS</div>
                        <button class="btn-primary-sm" onclick="openCiudadModal()">
                            <svg viewBox="0 0 24 24">
                                <line x1="12" y1="5" x2="12" y2="19" />
                                <line x1="5" y1="12" x2="19" y2="12" />
                            </svg>
                            Nueva Ciudad
                        </button>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Stands</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ciudadesRaw as $c):
                                $meta  = $cityMeta[$c['id']] ?? ['color' => '#888', 'abbr' => '???'];
                                $count = count($byCity[$c['id']] ?? []);
                            ?>
                                <tr id="ciudad-row-<?= $c['id'] ?>">
                                    <td style="color:var(--muted);font-weight:600">#<?= $c['id'] ?></td>
                                    <td>
                                        <span class="badge-ciudad" style="background:<?= $meta['color'] ?>18;color:<?= $meta['color'] ?>;border:1px solid <?= $meta['color'] ?>35">
                                            <?= $meta['abbr'] ?> — <?= htmlspecialchars($c['nombre']) ?>
                                        </span>
                                    </td>
                                    <td><span style="color:var(--text);font-weight:600"><?= $count ?></span> <span style="color:var(--muted)">stands</span></td>
                                    <td>
                                        <div style="display:flex;gap:8px">
                                            <button class="btn-secondary-sm" onclick='openCiudadModal(<?= json_encode($c) ?>)' style="padding:6px 14px;font-size:12px">
                                                <svg viewBox="0 0 24 24" style="width:12px;height:12px">
                                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                                                </svg>
                                                Editar
                                            </button>
                                            <?php if ($c['nombre'] !== 'index'): ?>
                                                <button class="btn-act del" style="flex:none;padding:6px 14px;font-size:12px;border-radius:9px" onclick="confirmDeleteCiudad(<?= $c['id'] ?>,'<?= htmlspecialchars(addslashes($c['nombre'])) ?>')">
                                                    <svg viewBox="0 0 24 24">
                                                        <polyline points="3 6 5 6 21 6" />
                                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2" />
                                                    </svg>
                                                    Eliminar
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ═══ SECCIÓN IMÁGENES HERO ═══ -->
            <div id="section-imagenes" style="display:none">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
                    <div>
                        <h2 style="font-family:'Bebas Neue',sans-serif;font-size:22px;letter-spacing:1px;color:#fff">IMÁGENES <span style="color:var(--orange)">HERO</span></h2>
                        <p style="font-size:13px;color:var(--muted);margin-top:4px">Una imagen principal por ciudad — se reemplaza automáticamente al subir una nueva.</p>
                    </div>
                </div>
                <div class="img-grid">
                    <?php foreach ($ciudadesRaw as $c):
                        $meta   = $cityMeta[$c['id']] ?? ['color' => '#888', 'abbr' => '???'];
                        $heroR  = $heroByCity[$c['id']] ?? null;
                    ?>
                        <div class="img-card" id="img-card-<?= $c['id'] ?>">
                            <div class="img-preview-wrap">
                                <?php if ($heroR && file_exists($heroR['ruta'])): ?>
                                    <img src="<?= htmlspecialchars($heroR['ruta']) ?>?v=<?= time() ?>" alt="Hero <?= htmlspecialchars($c['nombre']) ?>" />
                                <?php else: ?>
                                    <div class="img-no-img"><svg viewBox="0 0 24 24">
                                            <rect x="3" y="3" width="18" height="18" rx="2" />
                                            <circle cx="8.5" cy="8.5" r="1.5" />
                                            <polyline points="21 15 16 10 5 21" />
                                        </svg><span style="font-size:11px;color:var(--muted)">Sin imagen</span></div>
                                <?php endif; ?>
                                <div style="position:absolute;top:10px;left:10px;background:<?= $meta['color'] ?>22;color:<?= $meta['color'] ?>;border:1px solid <?= $meta['color'] ?>44;padding:3px 9px;border-radius:100px;font-size:9px;font-weight:700;letter-spacing:1px"><?= $meta['abbr'] ?></div>
                            </div>
                            <div class="img-card-body">
                                <div class="img-city-name"><?= htmlspecialchars($c['nombre']) ?></div>
                                <div class="img-ruta"><?= $heroR ? htmlspecialchars($heroR['ruta']) : '<span style="color:var(--muted);font-style:italic">Sin imagen asignada</span>' ?></div>
                                <div class="img-actions">
                                    <button class="btn-primary-sm" style="flex:1" onclick="openImgModal(<?= $c['id'] ?>, '<?= htmlspecialchars(addslashes($c['nombre'])) ?>', <?= $heroR ? 'true' : 'false' ?>)">
                                        <svg viewBox="0 0 24 24">
                                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                                            <polyline points="17 8 12 3 7 8" />
                                            <line x1="12" y1="3" x2="12" y2="15" />
                                        </svg>
                                        <?= $heroR ? 'Cambiar' : 'Subir' ?>
                                    </button>
                                    <?php if ($heroR): ?>
                                        <button class="btn-act del" style="flex:none;padding:8px 14px;font-size:12px;border-radius:9px" onclick="deleteImagen(<?= $c['id'] ?>, '<?= htmlspecialchars(addslashes($c['nombre'])) ?>')">
                                            <svg viewBox="0 0 24 24">
                                                <polyline points="3 6 5 6 21 6" />
                                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2" />
                                            </svg>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

        </div><!-- /content -->
    </div><!-- /main -->

    <!-- ═══════════════════ MODAL STAND ═══════════════════ -->
    <div class="modal-overlay" id="standModal" onclick="closeStandModal(event)">
        <div class="modal" onclick="event.stopPropagation()">
            <div class="modal-header">
                <div class="modal-title" id="standModalTitle">NUEVO <span>STAND</span></div>
                <button class="modal-close" onclick="closeStandModal()">✕</button>
            </div>
            <div class="modal-body">
                <form id="standForm" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="save_stand" />
                    <input type="hidden" name="id" id="f_id" value="0" />
                    <input type="hidden" name="imagen_actual" id="f_imagen_actual" value="" />
                    <div class="field-row">
                        <div class="field">
                            <label>Ciudad</label>
                            <select name="id_ciudad" id="f_ciudad" required>
                                <option value="">Seleccione ciudad</option>
                                <?php foreach ($ciudadesRaw as $c): if ($c['nombre'] === 'index') continue; ?>
                                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
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
                            <label>Orden (catálogo)</label>
                            <input type="number" name="orden" id="f_orden" value="0" min="0" />
                        </div>
                    </div>
                    <div class="field-row full" style="margin-bottom:14px">
                        <div class="field">
                            <label>Descripción</label>
                            <textarea name="descripcion" id="f_desc" placeholder="Descripción breve..."></textarea>
                        </div>
                    </div>
                    <!-- TOGGLES -->
                    <div style="display:flex;gap:24px;margin-bottom:16px;padding:12px 14px;background:var(--surface2);border-radius:10px;border:1px solid var(--border)">
                        <div class="toggle-wrap">
                            <label class="toggle">
                                <input type="checkbox" name="activo" id="f_activo" value="1" checked>
                                <span class="toggle-slider"></span>
                            </label>
                            <span class="toggle-label">Activo (visible)</span>
                        </div>
                        <div class="toggle-wrap">
                            <label class="toggle">
                                <input type="checkbox" name="destacado" id="f_destacado" value="1">
                                <span class="toggle-slider"></span>
                            </label>
                            <span class="toggle-label">★ Destacado en inicio</span>
                        </div>
                    </div>
                    <div class="field">
                        <label>Imagen del stand</label>
                        <div class="img-upload-area" id="standUploadArea">
                            <input type="file" name="imagen" id="f_imagen" accept="image/jpg,image/jpeg,image/png,image/webp" onchange="previewStandImg(this)" />
                            <div class="upload-icon"><svg viewBox="0 0 24 24">
                                    <rect x="3" y="3" width="18" height="18" rx="2" />
                                    <circle cx="8.5" cy="8.5" r="1.5" />
                                    <polyline points="21 15 16 10 5 21" />
                                </svg></div>
                            <div class="upload-text"><strong>Clic para subir</strong> o arrastra aquí<br><small style="color:var(--muted)">JPG, PNG o WEBP — máx 5MB</small></div>
                            <img id="standImgPreview" class="img-prv" src="" alt="Vista previa" />
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn-cancel" onclick="closeStandModal()">Cancelar</button>
                <button class="btn-save" id="btnSaveStand" onclick="saveStand()">
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

    <!-- ═══════════════════ MODAL CIUDAD ═══════════════════ -->
    <div class="modal-overlay" id="ciudadModal" onclick="closeCiudadModal(event)">
        <div class="modal sm" onclick="event.stopPropagation()">
            <div class="modal-header">
                <div class="modal-title" id="ciudadModalTitle">NUEVA <span>CIUDAD</span></div>
                <button class="modal-close" onclick="closeCiudadModal()">✕</button>
            </div>
            <div class="modal-body">
                <form id="ciudadForm">
                    <input type="hidden" name="action" value="save_ciudad" />
                    <input type="hidden" name="ciudad_id" id="ciudad_id" value="0" />
                    <div class="field-row full">
                        <div class="field">
                            <label>Nombre de la ciudad</label>
                            <input type="text" name="ciudad_nombre" id="ciudad_nombre" placeholder="Ej: Medellín" required />
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn-cancel" onclick="closeCiudadModal()">Cancelar</button>
                <button class="btn-save" onclick="saveCiudad()">
                    <svg viewBox="0 0 24 24">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z" />
                        <polyline points="17 21 17 13 7 13 7 21" />
                        <polyline points="7 3 7 8 15 8" />
                    </svg>
                    Guardar Ciudad
                </button>
            </div>
        </div>
    </div>

    <!-- ═══════════════════ MODAL IMAGEN HERO ═══════════════════ -->
    <div class="modal-overlay" id="imgModal" onclick="closeImgModal(event)">
        <div class="modal sm" onclick="event.stopPropagation()">
            <div class="modal-header">
                <div class="modal-title">IMAGEN <span>HERO</span></div>
                <button class="modal-close" onclick="closeImgModal()">✕</button>
            </div>
            <div class="modal-body">
                <form id="imgForm" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="save_imagen_principal" />
                    <input type="hidden" name="img_ciudad" id="img_ciudad_id" value="" />
                    <div class="warning-box" id="imgWarning">
                        <svg viewBox="0 0 24 24">
                            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" />
                            <line x1="12" y1="9" x2="12" y2="13" />
                            <line x1="12" y1="17" x2="12.01" y2="17" />
                        </svg>
                        <p>Ya existe una imagen para esta ciudad. Al subir una nueva, la imagen anterior será <strong>eliminada permanentemente</strong>.</p>
                    </div>
                    <div class="field" style="margin-bottom:0">
                        <label id="imgCiudadLabel">Ciudad</label>
                        <div class="img-upload-area" id="heroUploadArea">
                            <input type="file" name="img_principal" id="f_img_principal" accept="image/jpg,image/jpeg,image/png,image/webp" onchange="previewHeroImg(this)" required />
                            <div class="upload-icon"><svg viewBox="0 0 24 24">
                                    <rect x="3" y="3" width="18" height="18" rx="2" />
                                    <circle cx="8.5" cy="8.5" r="1.5" />
                                    <polyline points="21 15 16 10 5 21" />
                                </svg></div>
                            <div class="upload-text"><strong>Clic para subir</strong> imagen hero<br><small style="color:var(--muted)">JPG, PNG o WEBP — recomendado 1200×600px</small></div>
                            <img id="heroImgPreview" class="img-prv" src="" alt="Vista previa" />
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn-cancel" onclick="closeImgModal()">Cancelar</button>
                <button class="btn-save" id="btnSaveImg" onclick="saveImagen()">
                    <svg viewBox="0 0 24 24">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                        <polyline points="17 8 12 3 7 8" />
                        <line x1="12" y1="3" x2="12" y2="15" />
                    </svg>
                    Subir Imagen
                </button>
            </div>
        </div>
    </div>

    <!-- ═══════════════════ MODAL CONFIRMAR ELIMINAR ═══════════════════ -->
    <div class="modal-overlay" id="deleteModal" onclick="closeDeleteModal(event)">
        <div class="modal sm" onclick="event.stopPropagation()">
            <div class="modal-header">
                <div class="modal-title">ELIMINAR <span id="deleteModalType">STAND</span></div>
                <button class="modal-close" onclick="closeDeleteModal()">✕</button>
            </div>
            <div class="modal-body">
                <div class="confirm-body">
                    <div class="confirm-icon" style="background:rgba(255,71,87,.1);border:1px solid rgba(255,71,87,.2);color:var(--red)">
                        <svg viewBox="0 0 24 24">
                            <polyline points="3 6 5 6 21 6" />
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2" />
                        </svg>
                    </div>
                    <div class="confirm-title">¿Estás seguro?</div>
                    <div class="confirm-sub">Vas a eliminar <strong id="deleteItemName" style="color:#fff"></strong>.<br>Esta acción <strong>no se puede deshacer</strong>.</div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-cancel" onclick="closeDeleteModal()">Cancelar</button>
                <button class="btn-danger" id="btnConfirmDelete">Sí, eliminar</button>
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
        // ═══════════════════════════════════════════════════════════════════
        // ESTADO GLOBAL
        // ═══════════════════════════════════════════════════════════════════
        let currentCityId = <?= $ciudadesRaw[0]['id'] ?? 1 ?>;
        let deleteCallback = null;
        let toastTimer;

        // ─── SECCIONES ────────────────────────────────────────────────────
        function switchSection(section, btn) {
            localStorage.setItem('dash_section', section);
            ['stands', 'ciudades', 'imagenes'].forEach(s => {
                document.getElementById('section-' + s).style.display = s === section ? 'block' : 'none';
            });
            document.querySelectorAll('.nav-item').forEach(b => b.classList.remove('active'));
            if (btn) btn.classList.add('active');

            // Cambiar botón del topbar
            const topBtn = document.getElementById('topbarAddBtn');
            if (section === 'stands') {
                topBtn.style.display = '';
                topBtn.onclick = () => openModal();
                topBtn.querySelector('svg+span') ? null : null;
                topBtn.lastChild.textContent = ' Nuevo Stand';
            }
            if (section === 'ciudades') {
                topBtn.style.display = '';
                topBtn.onclick = () => openCiudadModal();
                topBtn.lastChild.textContent = ' Nueva Ciudad';
            }
            if (section === 'imagenes') {
                topBtn.style.display = 'none';
            }
        }

        // ─── CITY TABS ────────────────────────────────────────────────────
        function showCity(cityId, btn) {
            localStorage.setItem('dash_city', cityId);
            currentCityId = cityId;
            document.querySelectorAll('.city-panel').forEach(p => p.style.display = 'none');
            const panel = document.getElementById('panel-city-' + cityId);
            if (panel) panel.style.display = 'block';
            document.querySelectorAll('.city-tab').forEach(t => t.classList.remove('active'));
            if (btn) btn.classList.add('active');
        }

        // ═══════════════════════════════════════════════════════════════════
        // MODAL STAND
        // ═══════════════════════════════════════════════════════════════════
        function openModal(cityId = null) {
            clearStandForm();
            document.getElementById('f_ciudad').value = cityId ?? currentCityId;
            document.getElementById('standModalTitle').innerHTML = 'NUEVO <span>STAND</span>';
            document.getElementById('standModal').classList.add('open');
        }

        function editStand(stand) {
            document.getElementById('standModalTitle').innerHTML = 'EDITAR <span>STAND</span>';
            document.getElementById('f_id').value = stand.id;
            document.getElementById('f_ciudad').value = stand.id_ciudad;
            document.getElementById('f_tipo').value = stand.tipo;
            document.getElementById('f_nombre').value = stand.nombre;
            document.getElementById('f_precio').value = stand.precio;
            document.getElementById('f_orden').value = stand.orden;
            document.getElementById('f_desc').value = stand.descripcion || '';
            document.getElementById('f_imagen_actual').value = stand.imagen || '';
            document.getElementById('f_activo').checked = stand.activo == 1;
            document.getElementById('f_destacado').checked = stand.destacado == 1;

            const prv = document.getElementById('standImgPreview');
            if (stand.imagen) {
                prv.src = stand.imagen;
                prv.style.display = 'block';
            } else {
                prv.style.display = 'none';
            }

            document.getElementById('standModal').classList.add('open');
        }

        function closeStandModal(e) {
            if (e && e.target !== document.getElementById('standModal')) return;
            document.getElementById('standModal').classList.remove('open');
            clearStandForm();
        }

        function clearStandForm() {
            document.getElementById('standForm').reset();
            document.getElementById('f_id').value = '0';
            document.getElementById('f_imagen_actual').value = '';
            document.getElementById('standImgPreview').style.display = 'none';
            document.getElementById('f_activo').checked = true;
            document.getElementById('f_destacado').checked = false;
        }

        function previewStandImg(input) {
            const file = input.files[0];
            if (!file) return;
            if (file.size > 5 * 1024 * 1024) {
                showToast('La imagen no debe superar 5MB', 'error');
                input.value = '';
                return;
            }
            const r = new FileReader();
            r.onload = e => {
                const p = document.getElementById('standImgPreview');
                p.src = e.target.result;
                p.style.display = 'block';
            };
            r.readAsDataURL(file);
        }

        async function saveStand() {
            const btn = document.getElementById('btnSaveStand');
            const orig = btn.innerHTML;
            btn.innerHTML = '<div class="spinner"></div> Guardando...';
            btn.disabled = true;

            // Convertir checkboxes a valores numéricos
            const fd = new FormData(document.getElementById('standForm'));
            fd.set('activo', document.getElementById('f_activo').checked ? '1' : '0');
            fd.set('destacado', document.getElementById('f_destacado').checked ? '1' : '0');

            try {
                const res = await fetch('dashboard.php', {
                    method: 'POST',
                    body: fd
                });
                const data = await res.json();
                if (data.ok) {
                    showToast('Stand guardado', 'success');
                    closeStandModal();
                    setTimeout(() => location.reload(), 700);
                } else showToast('Error al guardar', 'error');
            } catch {
                showToast('Error de conexión', 'error');
            }
            btn.innerHTML = orig;
            btn.disabled = false;
        }

        // ─── TOGGLE DESTACADO RÁPIDO ───────────────────────────────────────
        async function toggleDestacado(id, newVal, btn) {
            const fd = new FormData();
            fd.append('action', 'toggle_destacado');
            fd.append('id', id);
            fd.append('destacado', newVal);
            try {
                const res = await fetch('dashboard.php', {
                    method: 'POST',
                    body: fd
                });
                const data = await res.json();
                if (data.ok) {
                    showToast(newVal === 1 ? '★ Stand destacado' : 'Stand sin destacar', 'success');
                    setTimeout(() => location.reload(), 600);
                }
            } catch {
                showToast('Error', 'error');
            }
        }

        // ═══════════════════════════════════════════════════════════════════
        // MODAL CIUDAD
        // ═══════════════════════════════════════════════════════════════════
        function openCiudadModal(ciudad = null) {
            document.getElementById('ciudadForm').reset();
            if (ciudad) {
                document.getElementById('ciudadModalTitle').innerHTML = 'EDITAR <span>CIUDAD</span>';
                document.getElementById('ciudad_id').value = ciudad.id;
                document.getElementById('ciudad_nombre').value = ciudad.nombre;
            } else {
                document.getElementById('ciudadModalTitle').innerHTML = 'NUEVA <span>CIUDAD</span>';
                document.getElementById('ciudad_id').value = '0';
            }
            document.getElementById('ciudadModal').classList.add('open');
        }

        function closeCiudadModal(e) {
            if (e && e.target !== document.getElementById('ciudadModal')) return;
            document.getElementById('ciudadModal').classList.remove('open');
        }
        async function saveCiudad() {
            const fd = new FormData(document.getElementById('ciudadForm'));
            try {
                const res = await fetch('dashboard.php', {
                    method: 'POST',
                    body: fd
                });
                const data = await res.json();
                if (data.ok) {
                    showToast('Ciudad guardada', 'success');
                    closeCiudadModal();
                    setTimeout(() => location.reload(), 700);
                } else showToast(data.msg || 'Error al guardar', 'error');
            } catch {
                showToast('Error de conexión', 'error');
            }
        }

        // ═══════════════════════════════════════════════════════════════════
        // MODAL IMAGEN HERO
        // ═══════════════════════════════════════════════════════════════════
        function openImgModal(cityId, cityName, hasExisting) {
            document.getElementById('imgForm').reset();
            document.getElementById('img_ciudad_id').value = cityId;
            document.getElementById('imgCiudadLabel').textContent = 'Imagen para: ' + cityName;
            document.getElementById('heroImgPreview').style.display = 'none';
            document.getElementById('imgWarning').style.display = hasExisting ? 'flex' : 'none';
            document.getElementById('imgModal').classList.add('open');
        }

        function closeImgModal(e) {
            if (e && e.target !== document.getElementById('imgModal')) return;
            document.getElementById('imgModal').classList.remove('open');
        }

        function previewHeroImg(input) {
            const file = input.files[0];
            if (!file) return;
            if (file.size > 8 * 1024 * 1024) {
                showToast('La imagen no debe superar 8MB', 'error');
                input.value = '';
                return;
            }
            const r = new FileReader();
            r.onload = e => {
                const p = document.getElementById('heroImgPreview');
                p.src = e.target.result;
                p.style.display = 'block';
            };
            r.readAsDataURL(file);
        }
        async function saveImagen() {
            const btn = document.getElementById('btnSaveImg');
            const orig = btn.innerHTML;
            btn.innerHTML = '<div class="spinner"></div> Subiendo...';
            btn.disabled = true;
            try {
                const fd = new FormData(document.getElementById('imgForm'));
                const res = await fetch('dashboard.php', {
                    method: 'POST',
                    body: fd
                });
                const data = await res.json();
                if (data.ok) {
                    const msg = data.had_previous ? 'Imagen actualizada (anterior eliminada)' : 'Imagen subida correctamente';
                    showToast(msg, 'success');
                    closeImgModal();
                    setTimeout(() => location.reload(), 700);
                } else showToast(data.msg || 'Error al subir', 'error');
            } catch {
                showToast('Error de conexión', 'error');
            }
            btn.innerHTML = orig;
            btn.disabled = false;
        }
        async function deleteImagen(cityId, cityName) {
            deleteCallback = async () => {
                const fd = new FormData();
                fd.append('action', 'delete_imagen_principal');
                fd.append('img_ciudad', cityId);
                try {
                    const res = await fetch('dashboard.php', {
                        method: 'POST',
                        body: fd
                    });
                    const data = await res.json();
                    if (data.ok) {
                        showToast('Imagen eliminada', 'success');
                        closeDeleteModal();
                        setTimeout(() => location.reload(), 700);
                    } else showToast('Error al eliminar', 'error');
                } catch {
                    showToast('Error', 'error');
                }
            };
            document.getElementById('deleteModalType').textContent = 'IMAGEN';
            document.getElementById('deleteItemName').textContent = 'imagen hero de ' + cityName;
            document.getElementById('btnConfirmDelete').onclick = deleteCallback;
            document.getElementById('deleteModal').classList.add('open');
        }

        // ═══════════════════════════════════════════════════════════════════
        // ELIMINAR STAND
        // ═══════════════════════════════════════════════════════════════════
        function confirmDelete(id, name) {
            document.getElementById('deleteModalType').textContent = 'STAND';
            document.getElementById('deleteItemName').textContent = name;
            document.getElementById('btnConfirmDelete').onclick = async () => {
                const btn = document.getElementById('btnConfirmDelete');
                btn.textContent = 'Eliminando...';
                btn.disabled = true;
                const fd = new FormData();
                fd.append('action', 'delete_stand');
                fd.append('id', id);
                try {
                    const res = await fetch('dashboard.php', {
                        method: 'POST',
                        body: fd
                    });
                    const data = await res.json();
                    if (data.ok) {
                        showToast('Stand eliminado', 'success');
                        closeDeleteModal();
                        const card = document.getElementById('card-' + id);
                        if (card) {
                            card.style.cssText = 'opacity:0;transform:scale(.9);transition:.3s';
                            setTimeout(() => card.remove(), 300);
                        }
                    } else showToast('Error al eliminar', 'error');
                } catch {
                    showToast('Error', 'error');
                }
                btn.textContent = 'Sí, eliminar';
                btn.disabled = false;
            };
            document.getElementById('deleteModal').classList.add('open');
        }

        // ═══════════════════════════════════════════════════════════════════
        // ELIMINAR CIUDAD
        // ═══════════════════════════════════════════════════════════════════
        function confirmDeleteCiudad(id, name) {
            document.getElementById('deleteModalType').textContent = 'CIUDAD';
            document.getElementById('deleteItemName').textContent = name + ' (y todos sus stands e imágenes)';
            document.getElementById('btnConfirmDelete').onclick = async () => {
                const btn = document.getElementById('btnConfirmDelete');
                btn.textContent = 'Eliminando...';
                btn.disabled = true;
                const fd = new FormData();
                fd.append('action', 'delete_ciudad');
                fd.append('ciudad_id', id);
                try {
                    const res = await fetch('dashboard.php', {
                        method: 'POST',
                        body: fd
                    });
                    const data = await res.json();
                    if (data.ok) {
                        showToast('Ciudad eliminada', 'success');
                        closeDeleteModal();
                        const row = document.getElementById('ciudad-row-' + id);
                        if (row) row.remove();
                    } else showToast('Error al eliminar', 'error');
                } catch {
                    showToast('Error', 'error');
                }
                btn.textContent = 'Sí, eliminar';
                btn.disabled = false;
            };
            document.getElementById('deleteModal').classList.add('open');
        }

        function closeDeleteModal(e) {
            if (e && e.target !== document.getElementById('deleteModal')) return;
            document.getElementById('deleteModal').classList.remove('open');
        }

        // ═══════════════════════════════════════════════════════════════════
        // LOGOUT / TOAST / DRAG-DROP / ESC
        // ═══════════════════════════════════════════════════════════════════
        async function doLogout() {
            const fd = new FormData();
            fd.append('action', 'logout');
            await fetch('dashboard.php', {
                method: 'POST',
                body: fd
            });
            window.location.href = 'login.php';
        }

        function showToast(msg, type = 'success') {
            const toast = document.getElementById('toast'),
                icon = document.getElementById('toastIcon');
            document.getElementById('toastMsg').textContent = msg;
            toast.className = 'toast ' + type + ' show';
            icon.innerHTML = type === 'success' ?
                '<polyline points="20 6 9 17 4 12"/>' :
                '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>';
            clearTimeout(toastTimer);
            toastTimer = setTimeout(() => toast.classList.remove('show'), 3000);
        }

        // Drag & drop para upload de stands
        const standUpload = document.getElementById('standUploadArea');
        standUpload.addEventListener('dragover', e => {
            e.preventDefault();
            standUpload.classList.add('drag');
        });
        standUpload.addEventListener('dragleave', () => standUpload.classList.remove('drag'));
        standUpload.addEventListener('drop', e => {
            e.preventDefault();
            standUpload.classList.remove('drag');
            const f = e.dataTransfer.files[0];
            if (f) {
                document.getElementById('f_imagen').files = e.dataTransfer.files;
                previewStandImg(document.getElementById('f_imagen'));
            }
        });

        // Drag & drop para imagen hero
        const heroUpload = document.getElementById('heroUploadArea');
        heroUpload.addEventListener('dragover', e => {
            e.preventDefault();
            heroUpload.classList.add('drag');
        });
        heroUpload.addEventListener('dragleave', () => heroUpload.classList.remove('drag'));
        heroUpload.addEventListener('drop', e => {
            e.preventDefault();
            heroUpload.classList.remove('drag');
            const f = e.dataTransfer.files[0];
            if (f) {
                document.getElementById('f_img_principal').files = e.dataTransfer.files;
                previewHeroImg(document.getElementById('f_img_principal'));
            }
        });

        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') {
                closeStandModal();
                closeCiudadModal();
                closeImgModal();
                closeDeleteModal();
            }
        });

        // Inicializar: mostrar primer panel de ciudad
        (function init() {
            // ── Restaurar sección (stands/ciudades/imagenes) ──────────────────
            const savedSection = localStorage.getItem('dash_section') || 'stands';
            const sectionBtn = document.querySelector(`.sb-item[onclick*="${savedSection}"]`);
            switchSection(savedSection, sectionBtn);

            // ── Restaurar ciudad activa ───────────────────────────────────────
            const savedCity = parseInt(localStorage.getItem('dash_city') || '0');
            if (savedCity) {
                const savedTab = document.getElementById('tab-city-' + savedCity);
                if (savedTab) {
                    showCity(savedCity, savedTab);
                    return;
                }
            }
            // Fallback: primera ciudad disponible
            const firstCity = document.querySelector('.city-tab');
            if (firstCity) {
                const cid = parseInt(firstCity.id.replace('tab-city-', ''));
                showCity(cid, firstCity);
            }
        })();

        // ── SIDEBAR MÓVIL ──
        const dashHamburger = document.getElementById('dashHamburger');
        const sidebarEl = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');

        function openSidebar() {
            sidebarEl.classList.add('open');
            sidebarOverlay.classList.add('open');
            dashHamburger.classList.add('open');
            document.body.style.overflow = 'hidden';
        }

        function closeSidebar() {
            sidebarEl.classList.remove('open');
            sidebarOverlay.classList.remove('open');
            dashHamburger.classList.remove('open');
            document.body.style.overflow = '';
        }

        dashHamburger.addEventListener('click', (e) => {
            e.stopPropagation();
            sidebarEl.classList.contains('open') ? closeSidebar() : openSidebar();
        });

        sidebarOverlay.addEventListener('click', closeSidebar);

        document.querySelectorAll('#sidebar .nav-item').forEach(item => {
            item.addEventListener('click', () => {
                if (window.innerWidth <= 900) setTimeout(closeSidebar, 150);
            });
        });
    </script>
</body>

</html>