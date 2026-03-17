<!doctype html>
<html lang="es">
<?php
require './app/conexion.php';

// ── Imagen hero del index (id_ciudad = 4) ────────────────────────────────────
$stmtHero = $pdo->prepare("SELECT ruta FROM imagesprincipales WHERE id_ciudad = 4 LIMIT 1");
$stmtHero->execute();
$heroRow = $stmtHero->fetch();
$heroImg = $heroRow ? $heroRow['ruta'] : 'public/images/hero.webp';

// ── Stands DESTACADOS por ciudad ──────────────────────────────────────────────
// Cartagena = id_ciudad 1 | Santa Marta = 2 | Barranquilla = 3
$stmtCtg = $pdo->prepare("SELECT * FROM stands WHERE id_ciudad = 1 AND activo = 1 AND destacado = 1 ORDER BY orden ASC, id ASC");
$stmtCtg->execute();
$standsCartagena = $stmtCtg->fetchAll();

$stmtSM = $pdo->prepare("SELECT * FROM stands WHERE id_ciudad = 2 AND activo = 1 AND destacado = 1 ORDER BY orden ASC, id ASC");
$stmtSM->execute();
$standsSantaMarta = $stmtSM->fetchAll();

$stmtBQ = $pdo->prepare("SELECT * FROM stands WHERE id_ciudad = 3 AND activo = 1 AND destacado = 1 ORDER BY orden ASC, id ASC");
$stmtBQ->execute();
$standsBarranquilla = $stmtBQ->fetchAll();

include 'includes/head.php';
?>

<body>

    <div class="cursor" id="cursor"></div>
    <div class="cursor-ring" id="cursor-ring"></div>

    <!-- NAVBAR -->
    <nav id="navbar">
        <a class="logo" href="#">
            <div class="logo-text">
                <div class="brand">MAR<span>CA</span> &amp; MEDIOS</div>
                <div class="sub">Publicidad · Eventos</div>
            </div>
        </a>
        <div class="nav-links">
            <a href="#stands">Diseño de Stands</a>
            <a href="#proceso">Proceso</a>
            <a href="#beneficios">¿Por qué nosotros?</a>
            <a href="#blog">Blog</a>
            <a href="#cotizar">Contáctanos</a>
            <div class="nav-dropdown" id="navDropdown">
                <button class="nav-dropdown-trigger" id="dropdownTrigger" aria-expanded="false" aria-haspopup="true">
                    Ciudades
                    <svg class="dropdown-arrow" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path d="m6 9 6 6 6-6" />
                    </svg>
                </button>
                <div class="nav-dropdown-menu" id="dropdownMenu" role="menu">
                    <div class="dropdown-glow"></div>
                    <a href="ciudades/cartagena.php" class="dropdown-item" role="menuitem">
                        <div class="dropdown-item-body">
                            <span class="dropdown-item-name">Cartagena de Indias</span>
                            <span class="dropdown-item-desc">Centro histórico del Caribe</span>
                        </div>
                        <svg class="dropdown-item-arrow" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path d="m9 18 6-6-6-6" />
                        </svg>
                    </a>
                    <a href="ciudades/barranquilla.php" class="dropdown-item" role="menuitem">
                        <div class="dropdown-item-body">
                            <span class="dropdown-item-name">Barranquilla</span>
                            <span class="dropdown-item-desc">Capital del Atlántico</span>
                        </div>
                        <svg class="dropdown-item-arrow" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path d="m9 18 6-6-6-6" />
                        </svg>
                    </a>
                    <a href="ciudades/santaMarta.php" class="dropdown-item" role="menuitem">x1
                        <div class="dropdown-item-body">
                            <span class="dropdown-item-name">Santa Marta</span>
                            <span class="dropdown-item-desc">Ciudad histórica de Colombia</span>
                        </div>
                        <svg class="dropdown-item-arrow" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path d="m9 18 6-6-6-6" />
                        </svg>
                    </a>
                </div>
            </div>
        </div>
        <a href="#cotizar" class="nav-cta">Cotizar ahora</a>
        <a href="login.php" class="nav-admin-btn desktop-only" aria-label="Panel de administración" title="Administrador">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                <circle cx="12" cy="7" r="4" />
            </svg>
        </a>

        <!-- HAMBURGER BUTTON — va justo antes de </nav> -->
        <button class="hamburger" id="hamburger" aria-label="Abrir menú" aria-expanded="false">
            <span></span>
            <span></span>
            <span></span>
        </button>
    </nav>

    <!-- MOBILE MENU — va inmediatamente después de </nav> -->
    <div class="mobile-menu" id="mobileMenu">
        <a href="#stands">Diseño de Stands</a>
        <a href="#proceso">Proceso</a>
        <a href="#beneficios">¿Por qué nosotros?</a>
        <a href="#blog">Blog</a>
        <a href="#cotizar">Contáctanos</a>

        <!-- Ciudades con submenu -->
        <div class="mobile-menu-item" id="mobileCitiesToggle">
            Ciudades
            <svg class="mobile-cities-arrow" width="14" height="14" fill="none"
                stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path d="m6 9 6 6 6-6" />
            </svg>
        </div>
        <div class="mobile-cities" id="mobileCities">
            <a href="ciudades/cartagena.php">Cartagena de Indias</a>
            <a href="ciudades/barranquilla.php">Barranquilla</a>
            <a href="ciudades/santaMarta.php">Santa Marta</a>
        </div>
        <!-- Botón Ingresar — solo en móvil -->
        <div class="mobile-menu-cta mobile-menu-login">
            <a href="login.php">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                    <circle cx="12" cy="7" r="4" />
                </svg>
                Ingresar
            </a>
        </div>

        <div class="mobile-menu-cta">
            <a href="#cotizar">Cotizar ahora</a>
        </div>
    </div>

    <!-- HERO -->
    <section class="hero">
        <div class="blob blob-1"></div>
        <div class="blob blob-2"></div>
        <div class="blob blob-3"></div>
        <div class="blob blob-4"></div>
        <div class="hero-grid-overlay"></div>
        <div class="hero-content">
            <p class="hero-eyebrow">Stands en Cartagena de Indias</p>
            <h1 class="hero-title">¿BUSCAS UN STAND QUE <span class="accent">REALMENTE</span> HAGA DESTACAR TU MARCA?</h1>
            <p class="hero-subtitle">Diseñamos y fabricamos stands para ferias y eventos en Cartagena y Barranquilla. Del concepto al montaje, todo en un solo lugar.</p>
            <div class="hero-actions">
                <a href="#cotizar" class="btn-primary">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z" />
                    </svg>
                    Cotiza tu Stand
                </a>
                <a href="#stands" class="btn-secondary">
                    Ver catálogo
                    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path d="m9 18 6-6-6-6" />
                    </svg>
                </a>
            </div>
            <div class="hero-stats">
                <div class="stat-item">
                    <div class="num">600<span>+</span></div>
                    <div class="label">Marcas atendidas</div>
                </div>
                <div class="stat-item">
                    <div class="num">5<span>+</span></div>
                    <div class="label">Años de experiencia</div>
                </div>
                <div class="stat-item">
                    <div class="num">3</div>
                    <div class="label">Ciudades</div>
                </div>
            </div>
        </div>
        <div class="hero-image">
            <img src="<?= htmlspecialchars($heroImg) ?>" alt="Stand Marca & Medios" onerror="this.style.display='none'" />
        </div>
    </section>

    <!-- MARQUEE VENUES -->
    <div class="marquee-section">
        <div class="marquee-track" id="marquee">
            <span class="venue-chip"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                    <polyline points="9,22 9,12 15,12 15,22" />
                </svg>Centro de Convenciones Cartagena</span>
            <span class="venue-chip"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                    <polyline points="9,22 9,12 15,12 15,22" />
                </svg>Hotel Las Américas Cartagena</span>
            <span class="venue-chip"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                    <polyline points="9,22 9,12 15,12 15,22" />
                </svg>Estelar Cartagena de Indias</span>
            <span class="venue-chip"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                    <polyline points="9,22 9,12 15,12 15,22" />
                </svg>Hyatt Regency Cartagena</span>
            <span class="venue-chip"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                    <polyline points="9,22 9,12 15,12 15,22" />
                </svg>Hilton Cartagena</span>
            <span class="venue-chip"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                    <polyline points="9,22 9,12 15,12 15,22" />
                </svg>Puerta de Oro Barranquilla</span>
            <span class="venue-chip"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                    <polyline points="9,22 9,12 15,12 15,22" />
                </svg>Centro de Convenciones Cartagena</span>
            <span class="venue-chip"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                    <polyline points="9,22 9,12 15,12 15,22" />
                </svg>Hotel Las Américas Cartagena</span>
            <span class="venue-chip"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                    <polyline points="9,22 9,12 15,12 15,22" />
                </svg>Estelar Cartagena de Indias</span>
            <span class="venue-chip"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                    <polyline points="9,22 9,12 15,12 15,22" />
                </svg>Hyatt Regency Cartagena</span>
            <span class="venue-chip"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                    <polyline points="9,22 9,12 15,12 15,22" />
                </svg>Hilton Cartagena</span>
            <span class="venue-chip"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                    <polyline points="9,22 9,12 15,12 15,22" />
                </svg>Puerta de Oro Barranquilla</span>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════
         TABS DE STANDS — DINÁMICOS DESDE BD (destacado = 1)
    ════════════════════════════════════════════════════════ -->
    <section class="tabs-section" id="stands">
        <div class="tabs-header reveal">
            <p class="section-eyebrow">Nuestros Servicios</p>
            <h2 class="section-title">STANDS EN <span class="accent">TRES CIUDADES</span></h2>
            <p class="section-subtitle">Diseño, producción y montaje de stands para ferias y eventos corporativos en Cartagena de Indias, Barranquilla y Santa Marta.</p>
        </div>

        <div class="tabs-nav reveal delay-1" id="tabsNav">
            <div class="tab-slider" id="tabSlider"></div>
            <button class="tab-btn active" data-tab="cartagena" onclick="switchTab('cartagena', this)">Cartagena de Indias</button>
            <button class="tab-btn" data-tab="barranquilla" onclick="switchTab('barranquilla', this)">Barranquilla</button>
            <button class="tab-btn" data-tab="santamarta" onclick="switchTab('santamarta', this)">Santa Marta</button>
        </div>

        <?php
        // Helper para renderizar el grid de stands de un tab
        function renderStandsGrid(array $stands, string $cityUrl, string $accentColor = 'var(--orange)', string $prefix = ''): void
        {
            if (empty($stands)): ?>
                <div class="products-grid">
                    <div class="empty-stands">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <rect x="2" y="3" width="20" height="14" rx="2" />
                            <line x1="8" y1="21" x2="16" y2="21" />
                            <line x1="12" y1="17" x2="12" y2="21" />
                        </svg>
                        <p>Próximamente agregaremos stands aquí.</p>
                        <a href="<?= $cityUrl ?>" class="btn-secondary" style="margin-top:16px;display:inline-flex;align-items:center;gap:6px">
                            Ver ciudad completa
                            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path d="m9 18 6-6-6-6" />
                            </svg>
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="products-grid">
                    <?php foreach ($stands as $i => $stand):
                        $delay  = ($i % 3) + 1;
                        $imgSrc = !empty($stand['imagen']) ? $stand['imagen'] : '';
                    ?>
                        <div class="product-card reveal delay-<?= $delay ?>">
                            <div class="product-img">
                                <?php if ($imgSrc): ?>
                                    <img src="<?= htmlspecialchars($imgSrc) ?>"
                                        alt="<?= htmlspecialchars($stand['nombre']) ?>"
                                        loading="lazy"
                                        onerror="this.style.display='none'" />
                                <?php endif; ?>
                                <div class="product-overlay">
                                    <a href="#cotizar" class="overlay-btn" style="background:<?= $accentColor ?>">
                                        Cotizar
                                        <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                            <path d="m9 18 6-6-6-6" />
                                        </svg>
                                    </a>
                                </div>
                            </div>
                            <div class="product-info">
                                <div class="product-type" style="color:<?= $accentColor ?>"><?= htmlspecialchars($stand['tipo']) ?></div>
                                <div class="product-name"><?= htmlspecialchars($stand['nombre']) ?></div>
                                <div class="product-price">Desde <strong style="color:<?= $accentColor ?>"><?= htmlspecialchars($stand['precio']) ?></strong></div>
                                <?php if (!empty($stand['descripcion'])): ?>
                                    <div class="product-desc"><?= htmlspecialchars($stand['descripcion']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div style="text-align:center;margin-top:32px">
                    <a href="<?= $cityUrl ?>" class="btn-secondary" style="display:inline-flex;align-items:center;gap:8px">
                        Ver todos los stands de esta ciudad
                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path d="m9 18 6-6-6-6" />
                        </svg>
                    </a>
                </div>
        <?php endif;
        }
        ?>

        <!-- TAB CARTAGENA -->
        <div class="tab-panel active" id="tab-cartagena">
            <div class="city-hero">
                <div class="city-intro reveal-left">
                    <p class="section-eyebrow">Cartagena de Indias</p>
                    <h3 class="city-title">STANDS QUE <span style="color:var(--orange)">DOMINAN</span> LA FERIA</h3>
                    <p class="city-desc">Somos la empresa líder en diseño y montaje de stands en Cartagena. Con más de 5 años operando en los principales recintos feriales, conocemos cada espacio, cada regla y cada oportunidad para hacer que tu marca brille.</p>
                    <div class="city-features">
                        <div class="city-feature"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="20,6 9,17 4,12" />
                            </svg>Diseño 3D personalizado sin costo adicional</div>
                        <div class="city-feature"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="20,6 9,17 4,12" />
                            </svg>Fabricación, transporte e instalación incluidos</div>
                        <div class="city-feature"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="20,6 9,17 4,12" />
                            </svg>Soporte durante todo el evento</div>
                        <div class="city-feature"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="20,6 9,17 4,12" />
                            </svg>Experiencia en +600 proyectos en Cartagena</div>
                    </div>
                </div>
                <div class="reveal-right">
                    <p class="products-label">Recintos donde operamos</p>
                    <div class="venues-grid">
                        <div class="venue-card">
                            <div class="venue-icon"><svg viewBox="0 0 24 24">
                                    <path d="M1 22h22M3 22V8l9-6 9 6v14M9 22v-6h6v6" />
                                </svg></div>
                            <div class="venue-name">Centro de Convenciones Julio César Turbay Ayala</div>
                        </div>
                        <div class="venue-card">
                            <div class="venue-icon"><svg viewBox="0 0 24 24">
                                    <path d="M1 22h22M3 22V8l9-6 9 6v14M9 22v-6h6v6" />
                                </svg></div>
                            <div class="venue-name">Hotel Las Américas · Centro de Convenciones</div>
                        </div>
                        <div class="venue-card">
                            <div class="venue-icon"><svg viewBox="0 0 24 24">
                                    <path d="M1 22h22M3 22V8l9-6 9 6v14M9 22v-6h6v6" />
                                </svg></div>
                            <div class="venue-name">CC Estelar Cartagena de Indias</div>
                        </div>
                        <div class="venue-card">
                            <div class="venue-icon"><svg viewBox="0 0 24 24">
                                    <path d="M1 22h22M3 22V8l9-6 9 6v14M9 22v-6h6v6" />
                                </svg></div>
                            <div class="venue-name">Hyatt Regency Cartagena</div>
                        </div>
                        <div class="venue-card">
                            <div class="venue-icon"><svg viewBox="0 0 24 24">
                                    <path d="M1 22h22M3 22V8l9-6 9 6v14M9 22v-6h6v6" />
                                </svg></div>
                            <div class="venue-name">Hotel Hilton Cartagena</div>
                        </div>
                        <div class="venue-card">
                            <div class="venue-icon"><svg viewBox="0 0 24 24">
                                    <path d="M1 22h22M3 22V8l9-6 9 6v14M9 22v-6h6v6" />
                                </svg></div>
                            <div class="venue-name">Y más centros en toda la ciudad</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="products-section reveal">
                <p class="products-label">Stands Destacados — Cartagena</p>
                <?php renderStandsGrid($standsCartagena, 'ciudades/cartagena.php', 'var(--orange)'); ?>
            </div>
        </div>

        <!-- TAB BARRANQUILLA -->
        <div class="tab-panel" id="tab-barranquilla">
            <div class="city-hero">
                <div class="city-intro reveal-left">
                    <p class="section-eyebrow" style="color:#1a3aff">Barranquilla</p>
                    <h3 class="city-title">STANDS QUE <span style="color:#1a3aff">IMPACTAN</span> EN BQ</h3>
                    <p class="city-desc">Llevamos nuestra experiencia a la capital del Atlántico. Diseñamos y montamos stands para ferias, congresos y eventos corporativos en Barranquilla, con los mismos estándares de calidad y atención personalizada.</p>
                    <div class="city-features">
                        <div class="city-feature"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="20,6 9,17 4,12" />
                            </svg>Equipo local con conocimiento del recinto</div>
                        <div class="city-feature"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="20,6 9,17 4,12" />
                            </svg>Stands modulares de entrega rápida</div>
                        <div class="city-feature"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="20,6 9,17 4,12" />
                            </svg>Diseño 3D y renders antes de producción</div>
                        <div class="city-feature"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="20,6 9,17 4,12" />
                            </svg>Cobertura en eventos de Confecámaras y Fenalco</div>
                    </div>
                </div>
                <div class="reveal-right">
                    <p class="products-label" style="color:#1a3aff">Recintos donde operamos</p>
                    <div class="venues-grid">
                        <div class="venue-card" style="border-color:rgba(26,58,255,0.15)">
                            <div class="venue-icon" style="background:linear-gradient(135deg,#1a3aff,#0033aa)"><svg viewBox="0 0 24 24">
                                    <path d="M1 22h22M3 22V8l9-6 9 6v14M9 22v-6h6v6" />
                                </svg></div>
                            <div class="venue-name">Centro de Convenciones Puerta de Oro</div>
                        </div>
                        <div class="venue-card" style="border-color:rgba(26,58,255,0.15)">
                            <div class="venue-icon" style="background:linear-gradient(135deg,#1a3aff,#0033aa)"><svg viewBox="0 0 24 24">
                                    <path d="M1 22h22M3 22V8l9-6 9 6v14M9 22v-6h6v6" />
                                </svg></div>
                            <div class="venue-name">Hotel El Prado · Centro de Eventos</div>
                        </div>
                        <div class="venue-card" style="border-color:rgba(26,58,255,0.15)">
                            <div class="venue-icon" style="background:linear-gradient(135deg,#1a3aff,#0033aa)"><svg viewBox="0 0 24 24">
                                    <path d="M1 22h22M3 22V8l9-6 9 6v14M9 22v-6h6v6" />
                                </svg></div>
                            <div class="venue-name">Centro Comercial Buenavista</div>
                        </div>
                        <div class="venue-card" style="border-color:rgba(26,58,255,0.15)">
                            <div class="venue-icon" style="background:linear-gradient(135deg,#1a3aff,#0033aa)"><svg viewBox="0 0 24 24">
                                    <path d="M1 22h22M3 22V8l9-6 9 6v14M9 22v-6h6v6" />
                                </svg></div>
                            <div class="venue-name">Corferias Barranquilla</div>
                        </div>
                        <div class="venue-card" style="border-color:rgba(26,58,255,0.15)">
                            <div class="venue-icon" style="background:linear-gradient(135deg,#1a3aff,#0033aa)"><svg viewBox="0 0 24 24">
                                    <path d="M1 22h22M3 22V8l9-6 9 6v14M9 22v-6h6v6" />
                                </svg></div>
                            <div class="venue-name">Eventos Fenalco Atlántico</div>
                        </div>
                        <div class="venue-card" style="border-color:rgba(26,58,255,0.15)">
                            <div class="venue-icon" style="background:linear-gradient(135deg,#1a3aff,#0033aa)"><svg viewBox="0 0 24 24">
                                    <path d="M1 22h22M3 22V8l9-6 9 6v14M9 22v-6h6v6" />
                                </svg></div>
                            <div class="venue-name">Y más centros en Barranquilla</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="products-section reveal">
                <p class="products-label" style="color:#1a3aff">Stands Destacados — Barranquilla</p>
                <?php renderStandsGrid($standsBarranquilla, 'ciudades/barranquilla.php', '#1a3aff'); ?>
            </div>
        </div>

        <!-- TAB SANTA MARTA -->
        <div class="tab-panel" id="tab-santamarta">
            <div class="city-hero">
                <div class="city-intro reveal-left">
                    <p class="section-eyebrow" style="color:#00b894">Santa Marta</p>
                    <h3 class="city-title">STANDS QUE <span style="color:#00b894">BRILLAN</span> EN SM</h3>
                    <p class="city-desc">Extendemos nuestra experiencia a la ciudad histórica de Colombia. Diseñamos y montamos stands para ferias, congresos y eventos corporativos en Santa Marta, con la misma calidad y atención personalizada que nos caracteriza.</p>
                    <div class="city-features">
                        <div class="city-feature"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="20,6 9,17 4,12" />
                            </svg>Conocimiento de los principales recintos de la ciudad</div>
                        <div class="city-feature"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="20,6 9,17 4,12" />
                            </svg>Stands modulares de entrega rápida</div>
                        <div class="city-feature"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="20,6 9,17 4,12" />
                            </svg>Diseño 3D y renders antes de producción</div>
                        <div class="city-feature"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="20,6 9,17 4,12" />
                            </svg>Transporte e instalación incluidos desde Cartagena</div>
                    </div>
                </div>
                <div class="reveal-right">
                    <p class="products-label" style="color:#00b894">Recintos donde operamos</p>
                    <div class="venues-grid">
                        <div class="venue-card" style="border-color:rgba(0,184,148,0.15)">
                            <div class="venue-icon" style="background:linear-gradient(135deg,#00b894,#007a63)"><svg viewBox="0 0 24 24">
                                    <path d="M1 22h22M3 22V8l9-6 9 6v14M9 22v-6h6v6" />
                                </svg></div>
                            <div class="venue-name">Centro de Convenciones Santa Marta</div>
                        </div>
                        <div class="venue-card" style="border-color:rgba(0,184,148,0.15)">
                            <div class="venue-icon" style="background:linear-gradient(135deg,#00b894,#007a63)"><svg viewBox="0 0 24 24">
                                    <path d="M1 22h22M3 22V8l9-6 9 6v14M9 22v-6h6v6" />
                                </svg></div>
                            <div class="venue-name">Hotel Irotama · Centro de Eventos</div>
                        </div>
                        <div class="venue-card" style="border-color:rgba(0,184,148,0.15)">
                            <div class="venue-icon" style="background:linear-gradient(135deg,#00b894,#007a63)"><svg viewBox="0 0 24 24">
                                    <path d="M1 22h22M3 22V8l9-6 9 6v14M9 22v-6h6v6" />
                                </svg></div>
                            <div class="venue-name">Hotel Zuana Beach Resort</div>
                        </div>
                        <div class="venue-card" style="border-color:rgba(0,184,148,0.15)">
                            <div class="venue-icon" style="background:linear-gradient(135deg,#00b894,#007a63)"><svg viewBox="0 0 24 24">
                                    <path d="M1 22h22M3 22V8l9-6 9 6v14M9 22v-6h6v6" />
                                </svg></div>
                            <div class="venue-name">Cámara de Comercio de Santa Marta</div>
                        </div>
                        <div class="venue-card" style="border-color:rgba(0,184,148,0.15)">
                            <div class="venue-icon" style="background:linear-gradient(135deg,#00b894,#007a63)"><svg viewBox="0 0 24 24">
                                    <path d="M1 22h22M3 22V8l9-6 9 6v14M9 22v-6h6v6" />
                                </svg></div>
                            <div class="venue-name">Eventos Fenalco Magdalena</div>
                        </div>
                        <div class="venue-card" style="border-color:rgba(0,184,148,0.15)">
                            <div class="venue-icon" style="background:linear-gradient(135deg,#00b894,#007a63)"><svg viewBox="0 0 24 24">
                                    <path d="M1 22h22M3 22V8l9-6 9 6v14M9 22v-6h6v6" />
                                </svg></div>
                            <div class="venue-name">Y más centros en Santa Marta</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="products-section reveal">
                <p class="products-label" style="color:#00b894">Stands Destacados — Santa Marta</p>
                <?php renderStandsGrid($standsSantaMarta, 'ciudades/santaMarta.php', '#00b894'); ?>
            </div>
        </div>
    </section>

    <!-- TICKER -->
    <div class="ticker-wrap">
        <div class="ticker-track" id="ticker">
            <span class="ticker-item">Stands en Cartagena</span><span class="ticker-item ticker-dot">●</span>
            <span class="ticker-item">Diseños a tu medida</span><span class="ticker-item ticker-dot">●</span>
            <span class="ticker-item">Stands en Barranquilla</span><span class="ticker-item ticker-dot">●</span>
            <span class="ticker-item">Alta personalización</span><span class="ticker-item ticker-dot">●</span>
            <span class="ticker-item">Montaje profesional</span><span class="ticker-item ticker-dot">●</span>
            <span class="ticker-item">+600 marcas atendidas</span><span class="ticker-item ticker-dot">●</span>
            <span class="ticker-item">Stands en Cartagena</span><span class="ticker-item ticker-dot">●</span>
            <span class="ticker-item">Diseños a tu medida</span><span class="ticker-item ticker-dot">●</span>
            <span class="ticker-item">Stands en Barranquilla</span><span class="ticker-item ticker-dot">●</span>
            <span class="ticker-item">Alta personalización</span><span class="ticker-item ticker-dot">●</span>
            <span class="ticker-item">Montaje profesional</span><span class="ticker-item ticker-dot">●</span>
            <span class="ticker-item">+600 marcas atendidas</span><span class="ticker-item ticker-dot">●</span>
        </div>
    </div>

    <!-- PROCESO -->
    <section class="process-section" id="proceso">
        <div class="process-header reveal">
            <p class="section-eyebrow">Producción Profesional</p>
            <h2 class="section-title">DEL CONCEPTO<br>A LA <span class="accent">REALIDAD</span></h2>
            <p class="section-subtitle">Un proceso claro, transparente y sin sorpresas. Así es como transformamos tu idea en un stand que cautiva.</p>
        </div>
        <div class="process-steps">
            <div class="process-connector"></div>
            <div class="step-card">
                <div class="step-num">01</div>
                <div class="step-icon"><svg viewBox="0 0 24 24">
                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z" />
                    </svg></div>
                <div class="step-title">Consulta inicial</div>
                <div class="step-desc">Analizamos tus objetivos, el espacio asignado, el tipo de evento y el público objetivo para entender qué necesita tu stand.</div>
            </div>
            <div class="step-card">
                <div class="step-num">02</div>
                <div class="step-icon"><svg viewBox="0 0 24 24">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2" />
                        <line x1="3" y1="9" x2="21" y2="9" />
                        <line x1="9" y1="21" x2="9" y2="9" />
                    </svg></div>
                <div class="step-title">Diseño y aprobación</div>
                <div class="step-desc">Nuestro equipo crea un diseño 3D fotorrealista de tu stand. Realizas los ajustes que necesites hasta quedar completamente satisfecho.</div>
            </div>
            <div class="step-card">
                <div class="step-num">03</div>
                <div class="step-icon"><svg viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="3" />
                        <path d="M19.07 4.93a10 10 0 0 1 0 14.14M4.93 19.07a10 10 0 0 1 0-14.14" />
                    </svg></div>
                <div class="step-title">Producción y montaje</div>
                <div class="step-desc">Fabricamos tu stand con materiales premium y lo montamos en el recinto con nuestro equipo especializado. En tiempo y con calidad garantizada.</div>
            </div>
            <div class="step-card">
                <div class="step-num">04</div>
                <div class="step-icon"><svg viewBox="0 0 24 24">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                        <circle cx="9" cy="7" r="4" />
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                        <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                    </svg></div>
                <div class="step-title">Soporte en el evento</div>
                <div class="step-desc">Permanecemos a tu disposición durante todo el evento para atender cualquier ajuste, necesidad técnica o imprevisto que surja.</div>
            </div>
        </div>
    </section>

    <!-- WHY US / BENEFICIOS -->
    <section class="why-section" id="beneficios">
        <div class="why-grid">
            <div class="reveal-left">
                <p class="section-eyebrow">¿Por qué elegirnos?</p>
                <h2 class="section-title">BENEFICIOS DE UN <span class="accent">STAND PROFESIONAL</span></h2>
                <p class="section-subtitle">Más que un stand: una inversión en visibilidad, impacto y oportunidades de negocio reales para tu empresa.</p>
                <div class="why-stat-row">
                    <div class="why-stat">
                        <div class="n">+600</div>
                        <div class="l">Marcas</div>
                    </div>
                    <div class="why-stat">
                        <div class="n">5+</div>
                        <div class="l">Años</div>
                    </div>
                    <div class="why-stat">
                        <div class="n">3</div>
                        <div class="l">Ciudades</div>
                    </div>
                </div>
                <div style="margin-top:32px"><a href="#cotizar" class="btn-primary">Solicitar presupuesto</a></div>
            </div>
            <div class="reveal-right">
                <div class="why-visual">
                    <div class="why-big-num">3</div>
                    <div class="benefits-list">
                        <div class="benefit-item">
                            <div class="benefit-num">1</div>
                            <div class="benefit-body">
                                <div class="benefit-title">Mayor visibilidad para tu marca</div>
                                <div class="benefit-desc">Un stand bien diseñado capta la atención de los asistentes y eleva tu presencia en el mercado, generando más oportunidades de negocio.</div>
                            </div>
                        </div>
                        <div class="benefit-item">
                            <div class="benefit-num">2</div>
                            <div class="benefit-body">
                                <div class="benefit-title">Atracción de clientes potenciales</div>
                                <div class="benefit-desc">Creamos espacios que inspiran conversación y despiertan el interés por tus productos o servicios, convirtiendo visitantes en leads.</div>
                            </div>
                        </div>
                        <div class="benefit-item">
                            <div class="benefit-num">3</div>
                            <div class="benefit-body">
                                <div class="benefit-title">Mejora de la imagen corporativa</div>
                                <div class="benefit-desc">Mostrar calidad y profesionalismo te posiciona como líder en tu sector frente a clientes, competidores y aliados estratégicos.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- BLOG -->
    <section class="blog-section" id="blog">
        <div class="blog-header reveal">
            <div>
                <p class="section-eyebrow">Blog</p>
                <h2 class="section-title">STANDS COMERCIALES<br>EN <span class="accent">CARTAGENA</span></h2>
            </div>
            <a href="#" class="btn-secondary">Ver todos los artículos <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path d="m9 18 6-6-6-6" />
                </svg></a>
        </div>
        <div class="blog-grid">
            <a class="blog-card reveal delay-1" href="#">
                <div class="blog-img-wrap"><img class="blog-img" src="https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=600&q=80" alt="Tipos de stands" /></div>
                <div class="blog-body">
                    <div class="blog-tag">Guías</div>
                    <div class="blog-title">¿Qué tipos de stands están disponibles para ferias y eventos en Colombia?</div>
                    <div class="blog-excerpt">Los stands son elementos esenciales en ferias y eventos comerciales. Conoce las diferencias entre cada tipo y cuál se adapta mejor a tu marca.</div>
                    <div class="blog-footer"><span>5 min de lectura</span>
                        <div class="blog-arrow"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path d="m9 18 6-6-6-6" />
                            </svg></div>
                    </div>
                </div>
            </a>
            <a class="blog-card reveal delay-2" href="#">
                <div class="blog-img-wrap"><img class="blog-img" src="https://images.unsplash.com/photo-1475721027785-f74eccf877e2?w=600&q=80" alt="Montaje de stand" /></div>
                <div class="blog-body">
                    <div class="blog-tag">Producción</div>
                    <div class="blog-title">Cuánto tiempo tarda el montaje de un stand: factores a considerar</div>
                    <div class="blog-excerpt">El montaje de un stand en ferias y eventos requiere planificación. Descubre los tiempos reales y cómo organizarte para no sufrir imprevistos.</div>
                    <div class="blog-footer"><span>4 min de lectura</span>
                        <div class="blog-arrow"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path d="m9 18 6-6-6-6" />
                            </svg></div>
                    </div>
                </div>
            </a>
            <a class="blog-card reveal delay-3" href="#">
                <div class="blog-img-wrap"><img class="blog-img" src="https://images.unsplash.com/photo-1559136555-9303baea8ebd?w=600&q=80" alt="Personalizar stand" /></div>
                <div class="blog-body">
                    <div class="blog-tag">Diseño</div>
                    <div class="blog-title">¿Puedo personalizar el diseño de mi stand para destacar en ferias?</div>
                    <div class="blog-excerpt">Personalizar el diseño de tu stand marca la diferencia entre pasar desapercibido y convertirte en el punto focal de la feria.</div>
                    <div class="blog-footer"><span>6 min de lectura</span>
                        <div class="blog-arrow"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path d="m9 18 6-6-6-6" />
                            </svg></div>
                    </div>
                </div>
            </a>
        </div>
    </section>

    <!-- CTA -->
    <div class="cta-section" id="cotizar">
        <div class="cta-left">
            <div class="cta-eyebrow">¿Listo para destacar?</div>
            <div class="cta-title">DISEÑAMOS Y FABRICAMOS<br>TU STAND <span style="color:var(--orange)">EN CARTAGENA</span></div>
            <div class="cta-sub">Solicita tu cotización hoy y recibe tu diseño 3D sin compromiso.</div>
        </div>
        <div class="cta-actions">
            <a href="https://wa.me/573002434036" class="btn-primary" target="_blank">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z" />
                </svg>
                Cotizar por WhatsApp
            </a>
        </div>
    </div>

    <!-- FOOTER -->
    <footer>
        <div class="footer-top">
            <div class="footer-brand">
                <a class="logo" href="#" style="margin-bottom:16px;display:inline-flex">
                    <div class="logo-text">
                        <div class="brand">MAR<span>CA</span> &amp; MEDIOS</div>
                        <div class="sub">Publicidad · Eventos</div>
                    </div>
                </a>
                <p class="footer-tagline">Diseñamos y fabricamos stands que hacen destacar tu marca en ferias y eventos en Cartagena de Indias y Barranquilla.</p>
            </div>
            <div class="footer-cols">
                <div class="footer-col">
                    <h4>Menú principal</h4>
                    <ul>
                        <li><a href="#">Nosotros</a></li>
                        <li><a href="#stands">Servicios</a></li>
                        <li><a href="#proceso">Proceso</a></li>
                        <li><a href="#blog">Blog</a></li>
                        <li><a href="#cotizar">Contacto</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Ciudades</h4>
                    <ul>
                        <li><a href="ciudades/cartagena.php">Stands Cartagena</a></li>
                        <li><a href="ciudades/barranquilla.php">Stands Barranquilla</a></li>
                        <li><a href="ciudades/santaMarta.php">Stands Santa Marta</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Contacto</h4>
                    <div class="footer-contact">
                        <div class="contact-line"><svg viewBox="0 0 24 24">
                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z" />
                            </svg>+57 320 6926 909</div>
                        <div class="contact-line"><svg viewBox="0 0 24 24">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" />
                                <polyline points="22,6 12,13 2,6" />
                            </svg><a href="mailto:standsCartagena.web@gmail.com" class="email-link">standsCartagena.web@gmail.com</a></div>
                        <div class="contact-line"><svg viewBox="0 0 24 24">
                                <rect x="2" y="2" width="20" height="20" rx="5" ry="5" />
                                <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z" />
                                <line x1="17.5" y1="6.5" x2="17.51" y2="6.5" />
                            </svg>@StandsCartagena</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p>© 2026 Stands Cartagena | Desarrollo Web <a class="cc" href="https://davidlopez09.github.io/edutechltda/" target="_blank" rel="noopener noreferrer" style="color:#2aa5a8;text-decoration:none;font-weight:bold;font-size:16px;">Edutech Ltda</a></p>
            <div class="social-links"><a class="social-link" href="#" aria-label="Instagram"><svg viewBox="0 0 24 24">
                        <rect x="2" y="2" width="20" height="20" rx="5" />
                        <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z" />
                        <line x1="17.5" y1="6.5" x2="17.51" y2="6.5" />
                    </svg></a></div>
        </div>
    </footer>

    <a class="whatsapp-fab" href="https://wa.me/573002434036" target="_blank" aria-label="WhatsApp">
        <svg viewBox="0 0 24 24">
            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z" />
        </svg>
    </a>

    <!-- Estilos extra para estado vacío -->
    <style>
        .empty-stands {
            text-align: center;
            padding: 60px 20px;
            color: #7a8694;
        }

        .empty-stands svg {
            width: 48px;
            height: 48px;
            stroke: currentColor;
            fill: none;
            stroke-width: 1.5;
            margin: 0 auto 16px;
            display: block;
            opacity: .4;
        }

        .empty-stands p {
            font-size: 14px;
            margin-bottom: 16px;
        }

        .product-desc {
            font-size: 12px;
            color: #7a8694;
            line-height: 1.5;
            margin-top: 6px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
    </style>

    <script src="public/js/script.js"></script>
</body>

</html>