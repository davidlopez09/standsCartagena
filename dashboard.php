    <?php
    session_start();

    if (!isset($_SESSION['admin_logged']) || $_SESSION['admin_logged'] !== true) {
        header('Location: login.php');
        exit;
    }

    require_once './app/conexion.php';

    $admin = htmlspecialchars($_SESSION['admin_usuario']);

    // ─── Mapeo id_ciudad → clave interna ─────────────────────────────────────────
    // Según tu BD: 1=Cartagena, 2=Santa Marta, 3=Barranquilla
    $cityMap = [
        1 => 'cartagena',
        2 => 'santamarta',
        3 => 'barranquilla',
    ];

    // ─── Manejo de acciones POST (AJAX) ───────────────────────────────────────────
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        header('Content-Type: application/json');
        $action = $_POST['action'];

        if ($action === 'logout') {
            session_destroy();
            echo json_encode(['ok' => true]);
            exit;
        }

        if ($action === 'save_stand') {
            $id       = intval($_POST['id']        ?? 0);
            $id_ciudad = intval($_POST['id_ciudad'] ?? 0);   // ← número real de la BD
            $tipo     = trim($_POST['tipo']         ?? '');
            $nombre   = trim($_POST['nombre']       ?? '');
            $precio   = trim($_POST['precio']       ?? '');
            $desc     = trim($_POST['descripcion']  ?? '');
            $orden    = intval($_POST['orden']      ?? 0);

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
                $stmt = $pdo->prepare("UPDATE stands SET id_ciudad=?, tipo=?, nombre=?, precio=?, descripcion=?, imagen=?, orden=?, updated_at=NOW() WHERE id=?");
                $stmt->execute([$id_ciudad, $tipo, $nombre, $precio, $desc, $imagen_path, $orden, $id]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO stands (id_ciudad, tipo, nombre, precio, descripcion, imagen, orden, activo) VALUES (?,?,?,?,?,?,?,1)");
                $stmt->execute([$id_ciudad, $tipo, $nombre, $precio, $desc, $imagen_path, $orden]);
                $id = $pdo->lastInsertId();
            }
            echo json_encode(['ok' => true, 'id' => $id, 'imagen' => $imagen_path]);
            exit;
        }

        if ($action === 'delete_stand') {
            $id = intval($_POST['id'] ?? 0);
            if ($id > 0) {
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

    // ─── Leer ciudades desde la BD ────────────────────────────────────────────────
    $ciudadesRaw = $pdo->query("SELECT id, nombre FROM ciudades ORDER BY id ASC")->fetchAll();

    // ─── Leer stands con JOIN para tener nombre de ciudad ────────────────────────
    $stands = $pdo->query("
        SELECT s.*, c.nombre AS ciudad_nombre
        FROM stands s
        JOIN ciudades c ON c.id = s.id_ciudad
        ORDER BY s.id_ciudad ASC, s.orden ASC, s.id ASC
    ")->fetchAll();

    // ─── Agrupar por id_ciudad ────────────────────────────────────────────────────
    $byCity = [];   // [id_ciudad => [stands...]]
    foreach ($ciudadesRaw as $c) {
        $byCity[$c['id']] = [];
    }
    foreach ($stands as $s) {
        $byCity[$s['id_ciudad']][] = $s;
    }

    $totalStands = count($stands);

    // Colores por id_ciudad según tu BD: 1=Cartagena, 2=Santa Marta, 3=Barranquilla
    $cityMeta = [
        1 => ['label' => 'Cartagena',   'color' => '#f05a1a', 'abbr' => 'CTG', 'tab' => 'cartagena'],
        2 => ['label' => 'Santa Marta', 'color' => '#00b894', 'abbr' => 'SMR', 'tab' => 'santamarta'],
        3 => ['label' => 'Barranquilla', 'color' => '#1a3aff', 'abbr' => 'BQA', 'tab' => 'barranquilla'],
    ];
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
        <link rel="stylesheet" href="./public/css/dashboard/dashboard.css" />
    </head>

    <body>

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
                <?php foreach ($ciudadesRaw as $i => $c):
                    $meta = $cityMeta[$c['id']] ?? ['color' => '#fff', 'tab' => 'ciudad' . $c['id'], 'abbr' => '?'];
                    $count = count($byCity[$c['id']] ?? []);
                ?>
                    <button class="nav-item <?= $i === 0 ? 'active' : '' ?>"
                        onclick="showCity(<?= $c['id'] ?>, this)"
                        data-city-id="<?= $c['id'] ?>">
                        <svg viewBox="0 0 24 24">
                            <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z" />
                            <circle cx="12" cy="9" r="2.5" />
                        </svg>
                        <?= htmlspecialchars($c['nombre']) ?>
                        <span class="count"><?= $count ?></span>
                    </button>
                <?php endforeach; ?>
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

        <div class="main">
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
                    <?php foreach ($ciudadesRaw as $c):
                        $meta  = $cityMeta[$c['id']] ?? ['color' => '#f05a1a', 'abbr' => '?'];
                        $count = count($byCity[$c['id']] ?? []);
                        // clase de color según ciudad
                        $cls = $c['id'] == 3 ? 'c-blue' : ($c['id'] == 2 ? 'c-green' : 'c-orange');
                    ?>
                        <div class="stat-card <?= $cls ?>">
                            <div class="stat-icon <?= $cls ?>"><svg viewBox="0 0 24 24">
                                    <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z" />
                                </svg></div>
                            <div class="stat-num"><?= $count ?></div>
                            <div class="stat-label"><?= htmlspecialchars($c['nombre']) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- TABS -->
                <div class="city-tabs" id="cityTabs">
                    <?php foreach ($ciudadesRaw as $i => $c):
                        $meta = $cityMeta[$c['id']] ?? ['color' => '#fff'];
                    ?>
                        <button class="city-tab <?= $i === 0 ? 'active' : '' ?>"
                            id="tab-city-<?= $c['id'] ?>"
                            style="color:<?= $meta['color'] ?>"
                            onclick="showCity(<?= $c['id'] ?>, null, this)">
                            <span class="dot" style="background:<?= $meta['color'] ?>"></span>
                            <?= htmlspecialchars($c['nombre']) ?>
                        </button>
                    <?php endforeach; ?>
                </div>

                <!-- PANELS — uno por ciudad -->
                <?php foreach ($ciudadesRaw as $i => $c):
                    $meta      = $cityMeta[$c['id']] ?? ['color' => '#f05a1a', 'abbr' => '?', 'label' => $c['nombre']];
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
                                    <button class="btn-new-stand" onclick="openModal(<?= $c['id'] ?>)">
                                        <svg viewBox="0 0 24 24">
                                            <line x1="12" y1="5" x2="12" y2="19" />
                                            <line x1="5" y1="12" x2="19" y2="12" />
                                        </svg>
                                        Agregar primer stand
                                    </button>
                                </div>
                            <?php else: ?>
                                <?php foreach ($cityStands as $stand): ?>
                                    <div class="stand-card" id="card-<?= $stand['id'] ?>">
                                        <div class="stand-img">
                                            <?php if ($stand['imagen'] && file_exists($stand['imagen'])): ?>
                                                <img src="<?= htmlspecialchars($stand['imagen']) ?>?v=<?= time() ?>"
                                                    alt="<?= htmlspecialchars($stand['nombre']) ?>" loading="lazy" />
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
                                            <div class="stand-city-badge"
                                                style="background:<?= $meta['color'] ?>22;color:<?= $meta['color'] ?>;border:1px solid <?= $meta['color'] ?>44">
                                                <?= $meta['abbr'] ?>
                                            </div>
                                        </div>
                                        <div class="stand-body">
                                            <div class="stand-type" style="color:<?= $meta['color'] ?>"><?= htmlspecialchars($stand['tipo']) ?></div>
                                            <div class="stand-name"><?= htmlspecialchars($stand['nombre']) ?></div>
                                            <div class="stand-price">Precio: <strong style="color:<?= $meta['color'] ?>"><?= htmlspecialchars($stand['precio']) ?></strong></div>
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

            </div>
        </div>

        <!-- MODAL CREAR/EDITAR -->
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
                                <select name="id_ciudad" id="f_ciudad" required>
                                    <option value="">Seleccione ciudad</option>
                                    <?php foreach ($ciudadesRaw as $c): ?>
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

                        <div class="field-row full" style="margin-bottom:16px">
                            <div class="field">
                                <label>Descripción</label>
                                <textarea name="descripcion" id="f_desc" placeholder="Descripción breve..."></textarea>
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

        <!-- MODAL ELIMINAR -->
        <div class="modal-overlay" id="deleteOverlay" onclick="closeDelete(event)">
            <div class="modal confirm-modal" onclick="event.stopPropagation()">
                <div class="modal-header">
                    <div class="modal-title">ELIMINAR <span>STAND</span></div>
                    <button class="modal-close" onclick="closeDelete()">✕</button>
                </div>
                <div class="modal-body">
                    <div class="confirm-body">
                        <div class="confirm-icon"><svg viewBox="0 0 24 24">
                                <polyline points="3 6 5 6 21 6" />
                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2" />
                            </svg></div>
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

        <div class="toast" id="toast">
            <svg id="toastIcon" viewBox="0 0 24 24">
                <polyline points="20 6 9 17 4 12" />
            </svg>
            <span id="toastMsg"></span>
        </div>

        <script>
            let deleteId = 0;
            let currentCityId = <?= $ciudadesRaw[0]['id'] ?? 1 ?>; // primera ciudad activa

            // ── Mostrar panel por id numérico de ciudad ───────────────────────────────────
            function showCity(cityId, sidebarBtn, tabBtn) {
                currentCityId = cityId;

                document.querySelectorAll('.city-panel').forEach(p => p.style.display = 'none');
                const panel = document.getElementById('panel-city-' + cityId);
                if (panel) panel.style.display = 'block';

                if (sidebarBtn) {
                    document.querySelectorAll('.nav-item').forEach(b => b.classList.remove('active'));
                    sidebarBtn.classList.add('active');
                    // Sincronizar tab superior
                    document.querySelectorAll('.city-tab').forEach(t => t.classList.remove('active'));
                    const t = document.getElementById('tab-city-' + cityId);
                    if (t) t.classList.add('active');
                }
                if (tabBtn) {
                    document.querySelectorAll('.city-tab').forEach(t => t.classList.remove('active'));
                    tabBtn.classList.add('active');
                    // Sincronizar sidebar
                    document.querySelectorAll('.nav-item[data-city-id]').forEach(b => b.classList.remove('active'));
                    const sb = document.querySelector('.nav-item[data-city-id="' + cityId + '"]');
                    if (sb) sb.classList.add('active');
                }
            }

            // ── Modal crear ───────────────────────────────────────────────────────────────
            function openModal(cityId = null) {
                clearForm();
                document.getElementById('f_ciudad').value = cityId ?? currentCityId;
                document.getElementById('modalTitle').innerHTML = 'NUEVO <span>STAND</span>';
                document.getElementById('modalOverlay').classList.add('open');
            }

            // ── Modal editar ──────────────────────────────────────────────────────────────
            function editStand(stand) {
                document.getElementById('modalTitle').innerHTML = 'EDITAR <span>STAND</span>';
                document.getElementById('f_id').value = stand.id;
                document.getElementById('f_ciudad').value = stand.id_ciudad; // ← id numérico
                document.getElementById('f_tipo').value = stand.tipo;
                document.getElementById('f_nombre').value = stand.nombre;
                document.getElementById('f_precio').value = stand.precio;
                document.getElementById('f_orden').value = stand.orden;
                document.getElementById('f_desc').value = stand.descripcion || '';
                document.getElementById('f_imagen_actual').value = stand.imagen || '';

                const preview = document.getElementById('imgPreview');
                if (stand.imagen) {
                    preview.src = stand.imagen;
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
                    showToast('Imagen máx 5MB', 'error');
                    input.value = '';
                    return;
                }
                const reader = new FileReader();
                reader.onload = e => {
                    const p = document.getElementById('imgPreview');
                    p.src = e.target.result;
                    p.style.display = 'block';
                };
                reader.readAsDataURL(file);
            }

            // ── Guardar ───────────────────────────────────────────────────────────────────
            async function saveStand() {
                const btn = document.getElementById('btnSave');
                const orig = btn.innerHTML;
                btn.innerHTML = '<div class="spinner"></div> Guardando...';
                btn.disabled = true;
                try {
                    const res = await fetch('dashboard.php', {
                        method: 'POST',
                        body: new FormData(document.getElementById('standForm'))
                    });
                    const data = await res.json();
                    if (data.ok) {
                        showToast('Stand guardado', 'success');
                        closeModal();
                        setTimeout(() => location.reload(), 800);
                    } else showToast('Error al guardar', 'error');
                } catch {
                    showToast('Error de conexión', 'error');
                }
                btn.innerHTML = orig;
                btn.disabled = false;
            }

            // ── Eliminar ──────────────────────────────────────────────────────────────────
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
                            card.style.cssText = 'opacity:0;transform:scale(.9);transition:.3s';
                            setTimeout(() => card.remove(), 300);
                        }
                    } else showToast('Error al eliminar', 'error');
                } catch {
                    showToast('Error de conexión', 'error');
                }
                btn.textContent = 'Sí, eliminar';
                btn.disabled = false;
            }

            async function doLogout() {
                const fd = new FormData();
                fd.append('action', 'logout');
                await fetch('dashboard.php', {
                    method: 'POST',
                    body: fd
                });
                window.location.href = 'login.php';
            }

            let toastTimer;

            function showToast(msg, type = 'success') {
                const toast = document.getElementById('toast'),
                    icon = document.getElementById('toastIcon');
                document.getElementById('toastMsg').textContent = msg;
                toast.className = 'toast ' + type + ' show';
                icon.innerHTML = type === 'success' ? '<polyline points="20 6 9 17 4 12"/>' :
                    '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>';
                clearTimeout(toastTimer);
                toastTimer = setTimeout(() => toast.classList.remove('show'), 3000);
            }

            const uploadArea = document.getElementById('uploadArea');
            uploadArea.addEventListener('dragover', e => {
                e.preventDefault();
                uploadArea.classList.add('drag');
            });
            uploadArea.addEventListener('dragleave', () => uploadArea.classList.remove('drag'));
            uploadArea.addEventListener('drop', e => {
                e.preventDefault();
                uploadArea.classList.remove('drag');
                const f = e.dataTransfer.files[0];
                if (f) {
                    document.getElementById('f_imagen').files = e.dataTransfer.files;
                    previewImage(document.getElementById('f_imagen'));
                }
            });
            document.addEventListener('keydown', e => {
                if (e.key === 'Escape') {
                    closeModal();
                    closeDelete();
                }
            });
        </script>
    </body>

    </html>