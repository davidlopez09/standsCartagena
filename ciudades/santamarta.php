<?php
require '../app/conexion.php';

// ── Cartagena = id_ciudad 1 ───────────────────────────────────────────────
$ID_CIUDAD = 2;

// Imagen hero desde la BD
$stmtHero = $pdo->prepare("SELECT ruta FROM imagesprincipales WHERE id_ciudad = ? LIMIT 1");
$stmtHero->execute([$ID_CIUDAD]);
$heroRow  = $stmtHero->fetch();
$heroImg  = $heroRow ? '../' . ltrim($heroRow['ruta'], '/') : '../public/images/hero.webp';

// Stands de Cartagena
$stmt = $pdo->prepare("SELECT * FROM stands WHERE id_ciudad = ? AND activo = 1 ORDER BY orden ASC, id ASC");
$stmt->execute([$ID_CIUDAD]);
$stands = $stmt->fetchAll();

?>
<!doctype html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Stands en Santa Marta — Marca & Medios</title>
    <meta
        name="description"
        content="Diseño y fabricación de stands para ferias y eventos en Santa Marta. Presencia en los principales recintos. Cotiza hoy." />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
        href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;600;700&family=Montserrat:wght@700;900&display=swap"
        rel="stylesheet" />
    <link rel="stylesheet" href="../public/css/santaMarta/santaMarta.css" />
</head>

<body>
    <div class="cursor" id="cursor"></div>
    <div class="cursor-ring" id="cursor-ring"></div>

    <nav id="navbar">
        <a class="logo" href="../index.php">
            <div class="logo-text">
                <div class="brand">MAR<span>CA</span> &amp; MEDIOS</div>
                <div class="sub">Publicidad · Eventos</div>
            </div>
        </a>
        <div class="nav-links">
            <a href="../index.php#">Inicio</a>
            <a href="../index.php#stands">Servicios</a>
            <a href="../index.php#proceso">Proceso</a>
            <a href="../index.php#beneficios">¿Por qué nosotros?</a>
            <a href="../index.php#blog">Blog</a>
            <a href="cartagena.php">Cartagena</a>
            <a href="barranquilla.php">Barranquilla</a>
        </div>
        <a href="#cotizar" class="nav-cta" style="margin-left: auto">Cotizar ahora</a>
        <a href="../login.php" class="nav-admin-btn" aria-label="Panel de administración" title="Administrador">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                <circle cx="12" cy="7" r="4" />
            </svg>
        </a>
    </nav>

    <section class="hero">
        <div class="hero-bg"></div>
        <div class="hero-grid"></div>
        <div class="hero-blob hero-blob-1"></div>
        <div class="hero-blob hero-blob-2"></div>

        <div class="hero-content">
            <div class="hero-badge">
                <svg viewBox="0 0 24 24">
                    <path
                        d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z" />
                </svg>
                Santa Marta
            </div>
            <h1 class="hero-title">
                STANDS QUE<br />
                <span class="accent">BRILLAN</span><br />
                EN Santa Marta
            </h1>
            <p class="hero-subtitle">
                Extendemos nuestra experiencia a la ciudad histórica de Colombia. Stands para ferias, congresos y
                eventos corporativos con la miSanta Martaa calidad que nos caracteriza.
            </p>
            <div class="hero-actions">
                <a href="#cotizar" class="btn-primary">
                    <svg
                        width="16"
                        height="16"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2.5"
                        viewBox="0 0 24 24">
                        <path
                            d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z" />
                    </svg>
                    Cotiza tu Stand
                </a>
                <a href="#catalogo" class="btn-secondary">
                    Ver catálogo
                    <svg
                        width="14"
                        height="14"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2.5"
                        viewBox="0 0 24 24">
                        <path d="m9 18 6-6-6-6" />
                    </svg>
                </a>
            </div>
            <div class="hero-stats">
                <div class="stat">
                    <div class="stat-num">Santa Marta<span>+</span></div>
                    <div class="stat-label">Ciudad histórica</div>
                </div>
                <div class="stat">
                    <div class="stat-num">5<span>+</span></div>
                    <div class="stat-label">Recintos activos</div>
                </div>
                <div class="stat">
                    <div class="stat-num">3<span>D</span></div>
                    <div class="stat-label">Diseño incluido</div>
                </div>
            </div>
        </div>

        <div class="hero-visual">
            <img src="<?= htmlspecialchars($heroImg) ?>" alt="Stand Cartagena" onerror="this.style.display='none'" />
            <div class="hero-visual-overlay"></div>
            <div class="hero-float-card card-1">
                <div class="fc-label">Ciudad</div>
                <div class="fc-value">Santa Marta</div>
                <div class="fc-sub">Historia y mar</div>
            </div>
            <div class="hero-float-card card-2">
                <div class="fc-label">Diseño</div>
                <div class="fc-value">3D</div>
                <div class="fc-sub">Incluido sin costo</div>
            </div>
        </div>
    </section>

    <div class="venues-strip">
        <div class="strip-track">
            <span class="strip-item"><svg viewBox="0 0 24 24">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                    <polyline points="9,22 9,12 15,12 15,22" />
                </svg>Centro de Convenciones Santa Marta</span>
            <span class="strip-item"><svg viewBox="0 0 24 24">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                    <polyline points="9,22 9,12 15,12 15,22" />
                </svg>Hotel Irotama · Centro de Eventos</span>
            <span class="strip-item"><svg viewBox="0 0 24 24">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                    <polyline points="9,22 9,12 15,12 15,22" />
                </svg>Hotel Zuana Beach Resort</span>
            <span class="strip-item"><svg viewBox="0 0 24 24">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                    <polyline points="9,22 9,12 15,12 15,22" />
                </svg>Cámara de Comercio de Santa Marta</span>
            <span class="strip-item"><svg viewBox="0 0 24 24">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                    <polyline points="9,22 9,12 15,12 15,22" />
                </svg>Fenalco Magdalena</span>
            <span class="strip-item"><svg viewBox="0 0 24 24">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                    <polyline points="9,22 9,12 15,12 15,22" />
                </svg>Centro de Convenciones Santa Marta</span>
            <span class="strip-item"><svg viewBox="0 0 24 24">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                    <polyline points="9,22 9,12 15,12 15,22" />
                </svg>Hotel Irotama · Centro de Eventos</span>
            <span class="strip-item"><svg viewBox="0 0 24 24">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                    <polyline points="9,22 9,12 15,12 15,22" />
                </svg>Hotel Zuana Beach Resort</span>
            <span class="strip-item"><svg viewBox="0 0 24 24">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                    <polyline points="9,22 9,12 15,12 15,22" />
                </svg>Cámara de Comercio de Santa Marta</span>
            <span class="strip-item"><svg viewBox="0 0 24 24">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                    <polyline points="9,22 9,12 15,12 15,22" />
                </svg>Fenalco Magdalena</span>
        </div>
    </div>

    <section class="about-section" id="nosotros">
        <div class="about-grid">
            <div class="reveal-left">
                <p class="section-eyebrow">Nuestra presencia en Santa Marta</p>
                <h2 class="section-title">STANDS<br /><span class="accent">SANTA MARTA</span><br />PROFESIONAL</h2>
                <p class="section-sub">
                    Llevamos la experiencia y calidad de Cartagena a la ciudad más antigua de Colombia. Diseño,
                    producción y montaje con los miSanta Martaos estándares de excelencia.
                </p>
                <div class="about-features">
                    <div class="feat">
                        <div class="feat-icon">
                            <svg viewBox="0 0 24 24">
                                <path d="M1 3h15v13H1zM16 8h4l3 3v5h-7V8z" />
                                <circle cx="5.5" cy="18.5" r="2.5" />
                                <circle cx="18.5" cy="18.5" r="2.5" />
                            </svg>
                        </div>
                        <div>
                            <div class="feat-title">Transporte e instalación desde Cartagena</div>
                            <div class="feat-desc">
                                Logística completa incluida. Tu stand llega perfecto y a tiempo al recinto en Santa
                                Marta.
                            </div>
                        </div>
                    </div>
                    <div class="feat">
                        <div class="feat-icon">
                            <svg viewBox="0 0 24 24">
                                <rect x="3" y="3" width="18" height="18" rx="2" />
                                <line x1="3" y1="9" x2="21" y2="9" />
                                <line x1="9" y1="21" x2="9" y2="9" />
                            </svg>
                        </div>
                        <div>
                            <div class="feat-title">Diseño 3D fotorrealista incluido</div>
                            <div class="feat-desc">
                                Ve tu stand antes de construirlo. Hacemos los ajustes que necesites sin costo
                                adicional.
                            </div>
                        </div>
                    </div>
                    <div class="feat">
                        <div class="feat-icon">
                            <svg viewBox="0 0 24 24">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                            </svg>
                        </div>
                        <div>
                            <div class="feat-title">Conocimiento de los recintos de la ciudad</div>
                            <div class="feat-desc">
                                Trabajamos con los principales centros de eventos y hoteles de Santa Marta.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="reveal-right">
                <div class="venues-label">Recintos donde operamos en Santa Marta</div>
                <div class="venues-list">
                    <div class="venue-item">
                        <div class="venue-icon">
                            <svg viewBox="0 0 24 24">
                                <path d="M1 22h22M3 22V8l9-6 9 6v14M9 22v-6h6v6" />
                            </svg>
                        </div>
                        <div class="venue-name-text">CC Santa Marta</div>
                    </div>
                    <div class="venue-item">
                        <div class="venue-icon">
                            <svg viewBox="0 0 24 24">
                                <path d="M1 22h22M3 22V8l9-6 9 6v14M9 22v-6h6v6" />
                            </svg>
                        </div>
                        <div class="venue-name-text">Hotel Irotama Eventos</div>
                    </div>
                    <div class="venue-item">
                        <div class="venue-icon">
                            <svg viewBox="0 0 24 24">
                                <path d="M1 22h22M3 22V8l9-6 9 6v14M9 22v-6h6v6" />
                            </svg>
                        </div>
                        <div class="venue-name-text">Zuana Beach Resort</div>
                    </div>
                    <div class="venue-item">
                        <div class="venue-icon">
                            <svg viewBox="0 0 24 24">
                                <path d="M1 22h22M3 22V8l9-6 9 6v14M9 22v-6h6v6" />
                            </svg>
                        </div>
                        <div class="venue-name-text">Cámara de Comercio Santa Marta</div>
                    </div>
                    <div class="venue-item">
                        <div class="venue-icon">
                            <svg viewBox="0 0 24 24">
                                <path d="M1 22h22M3 22V8l9-6 9 6v14M9 22v-6h6v6" />
                            </svg>
                        </div>
                        <div class="venue-name-text">Fenalco Magdalena</div>
                    </div>
                    <div class="venue-item">
                        <div class="venue-icon">
                            <svg viewBox="0 0 24 24">
                                <path d="M1 22h22M3 22V8l9-6 9 6v14M9 22v-6h6v6" />
                            </svg>
                        </div>
                        <div class="venue-name-text">Y más recintos Santa Marta</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══ CATÁLOGO DINÁMICO DESDE BD ═══ -->
    <section class="catalog-section" id="catalogo">
        <div class="catalog-header reveal">
            <div>
                <p class="section-eyebrow">Catálogo Cartagena</p>
                <h2 class="section-title">NUESTROS <span class="accent">STANDS</span></h2>
            </div>
            <a href="#cotizar" class="btn-primary">Solicitar cotización</a>
        </div>

        <div class="products-grid">
            <?php if (empty($stands)): ?>
                <div class="empty-catalog">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <rect x="2" y="3" width="20" height="14" rx="2" />
                        <line x1="8" y1="21" x2="16" y2="21" />
                        <line x1="12" y1="17" x2="12" y2="21" />
                    </svg>
                    <p>Próximamente agregaremos nuestros stands aquí.</p>
                    <a href="#cotizar" class="btn-primary">Consultar disponibilidad</a>
                </div>
            <?php else: ?>
                <?php foreach ($stands as $i => $stand):
                    $delay  = ($i % 3) + 1;
                    $imgSrc = !empty($stand['imagen']) ? '../' . ltrim($stand['imagen'], '/') : '';
                ?>
                    <div class="product-card reveal delay-<?= $delay ?>">
                        <div class="product-img">
                            <?php if ($imgSrc): ?>
                                <img src="<?= htmlspecialchars($imgSrc) ?>" alt="<?= htmlspecialchars($stand['nombre']) ?>" loading="lazy" onerror="this.parentElement.innerHTML='<div class=\'img-placeholder\'><svg viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'currentColor\' stroke-width=\'1.5\'><rect x=\'3\' y=\'3\' width=\'18\' height=\'18\' rx=\'2\'/><circle cx=\'8.5\' cy=\'8.5\' r=\'1.5\'/><polyline points=\'21 15 16 10 5 21\'/></svg></div>'" />
                            <?php else: ?>
                                <div class="img-placeholder">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                        <rect x="3" y="3" width="18" height="18" rx="2" />
                                        <circle cx="8.5" cy="8.5" r="1.5" />
                                        <polyline points="21 15 16 10 5 21" />
                                    </svg>
                                </div>
                            <?php endif; ?>
                            <div class="product-overlay">
                                <a href="#cotizar" class="overlay-btn">Cotizar <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                        <path d="m9 18 6-6-6-6" />
                                    </svg></a>
                            </div>
                        </div>
                        <div class="product-info">
                            <div class="product-type"><?= htmlspecialchars($stand['tipo']) ?></div>
                            <div class="product-name"><?= htmlspecialchars($stand['nombre']) ?></div>
                            <div class="product-price">Desde <strong><?= htmlspecialchars($stand['precio']) ?></strong></div>
                            <?php if (!empty($stand['descripcion'])): ?>
                                <div class="product-desc"><?= htmlspecialchars($stand['descripcion']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <section class="process-section" id="proceso">
        <div class="process-wrap">
            <div class="process-header reveal">
                <p class="section-eyebrow">Producción Profesional</p>
                <h2 class="section-title">DEL CONCEPTO A LA <span class="accent">REALIDAD</span></h2>
                <p class="section-sub">Proceso claro para llevar tu stand a Santa Marta sin complicaciones.</p>
            </div>
            <div class="steps">
                <div class="step reveal delay-1">
                    <div class="step-circle">
                        <span class="step-num">1</span><svg viewBox="0 0 24 24">
                            <path
                                d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z" />
                        </svg>
                    </div>
                    <div class="step-title">Consulta inicial</div>
                    <div class="step-desc">Analizamos objetivos, recinto y presupuesto.</div>
                </div>
                <div class="step reveal delay-2">
                    <div class="step-circle">
                        <span class="step-num">2</span><svg viewBox="0 0 24 24">
                            <rect x="3" y="3" width="18" height="18" rx="2" />
                            <line x1="3" y1="9" x2="21" y2="9" />
                            <line x1="9" y1="21" x2="9" y2="9" />
                        </svg>
                    </div>
                    <div class="step-title">Diseño 3D</div>
                    <div class="step-desc">Renders y aprobación antes de producción.</div>
                </div>
                <div class="step reveal delay-3">
                    <div class="step-circle">
                        <span class="step-num">3</span><svg viewBox="0 0 24 24">
                            <path d="M1 3h15v13H1zM16 8h4l3 3v5h-7V8z" />
                            <circle cx="5.5" cy="18.5" r="2.5" />
                            <circle cx="18.5" cy="18.5" r="2.5" />
                        </svg>
                    </div>
                    <div class="step-title">Transporte a Santa Marta</div>
                    <div class="step-desc">Logística directa desde Cartagena hasta el recinto.</div>
                </div>
                <div class="step reveal delay-2">
                    <div class="step-circle">
                        <span class="step-num">4</span><svg viewBox="0 0 24 24">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                            <circle cx="9" cy="7" r="4" />
                        </svg>
                    </div>
                    <div class="step-title">Montaje y soporte</div>
                    <div class="step-desc">Instalación profesional y acompañamiento en el evento.</div>
                </div>
            </div>
        </div>
    </section>

    <section class="why-section" id="beneficios">
        <div class="why-grid">
            <div class="reveal-left">
                <p class="section-eyebrow">¿Por qué elegirnos en Santa Marta?</p>
                <h2 class="section-title">STANDS<br /><span class="accent">AL NIVEL</span><br />DE Santa Marta</h2>
                <div class="benefits">
                    <div class="benefit">
                        <div class="benefit-num">1</div>
                        <div>
                            <div class="benefit-title">Experiencia del Caribe colombiano</div>
                            <div class="benefit-desc">
                                Más de 5 años en la región nos dan el conocimiento para operar con éxito en
                                cualquier recinto de Santa Marta.
                            </div>
                        </div>
                    </div>
                    <div class="benefit">
                        <div class="benefit-num">2</div>
                        <div>
                            <div class="benefit-title">Logística integral sin complicaciones</div>
                            <div class="benefit-desc">
                                Transporte, instalación y deSanta Martaontaje coordinados desde nuestra base en
                                Cartagena hasta cualquier punto de Santa Marta.
                            </div>
                        </div>
                    </div>
                    <div class="benefit">
                        <div class="benefit-num">3</div>
                        <div>
                            <div class="benefit-title">MiSanta Martaa calidad, otra ciudad</div>
                            <div class="benefit-desc">
                                Los miSanta Martaos materiales, el miSanta Martao nivel de detalle y el miSanta
                                Martao compromiso que nos hace líderes en toda la Costa Caribe.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="reveal-right">
                <div class="why-cta-box">
                    <div class="why-big">Santa Marta</div>
                    <p class="section-eyebrow">Santa Marta</p>
                    <h3 class="section-title" style="font-size: 36px">
                        CALIDAD<br /><span class="accent">EN EL</span> CARIBE
                    </h3>
                    <div class="stat-row">
                        <div class="s">
                            <div class="s-num">5+</div>
                            <div class="s-label">Recintos Santa Marta</div>
                        </div>
                        <div class="s">
                            <div class="s-num">3D</div>
                            <div class="s-label">Diseño libre</div>
                        </div>
                        <div class="s">
                            <div class="s-num">100%</div>
                            <div class="s-label">A tiempo</div>
                        </div>
                    </div>
                    <a href="#cotizar" class="btn-primary">Solicitar presupuesto</a>
                </div>
            </div>
        </div>
    </section>

    <div class="cta-full" id="cotizar">
        <div class="cta-wrap">
            <div>
                <div class="cta-eyebrow">¿Listo para brillar en Santa Marta?</div>
                <div class="cta-title">
                    DISEÑAMOS Y FABRICAMOS<br />TU STAND
                    <span style="color: var(--green-light)">EN SANTA MARTA</span>
                </div>
                <div class="cta-sub">Solicita tu cotización hoy y recibe tu diseño 3D sin compromiso.</div>
            </div>
            <div class="cta-actions">
                <a href="https://wa.me/573002434036" class="btn-primary" target="_blank">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                        <path
                            d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z" />
                    </svg>
                    Cotizar por WhatsApp
                </a>
            </div>
        </div>
    </div>

    <footer>
        <div class="footer-inner">
            <div class="footer-brand">
                <a class="logo" href="../index.php">
                    <div class="logo-text">
                        <div class="brand">MAR<span>CA</span> &amp; MEDIOS</div>
                        <div class="sub">Publicidad · Eventos</div>
                    </div>
                </a>
                <p class="footer-tagline">
                    Stands para ferias y eventos en Santa Marta y la Costa Caribe colombiana.
                </p>
            </div>
            <div class="footer-col">
                <h4>Ciudades</h4>
                <ul>
                    <li><a href="cartagena.php">Cartagena de Indias</a></li>
                    <li><a href="barranquilla.php">Barranquilla</a></li>
                    <li><a href="santaMarta.php">Santa Marta</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Contacto</h4>
                <ul>
                    <li><a href="tel:+573206926909">+57 320 6926 909</a></li>
                    <li><a href="mailto:standsCartagena.web@gmail.com">standsCartagena.web@gmail.com</a></li>
                    <li><a href="#">@StandsCartagena</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>
                © 2026 Stands Cartagena | Desarrollo Web
                <a class="cc" href="https://davidlopez09.github.io/edutechltda/" target="_blank" rel="noopener noreferrer" style="color: #2aa5a8; text-decoration: none; font-weight: bold; font-size: 16px;">
                    Edutech Ltda </>
                </a>
            </p>
        </div>
    </footer>

    <a class="whatsapp-fab" href="https://wa.me/573002434036" target="_blank" aria-label="WhatsApp">
        <svg viewBox="0 0 24 24">
            <path
                d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z" />
        </svg>
    </a>

    <script src="../public/js/santaMarta/santaMarta.js"></script>
</body>

</html>