<!doctype html>
<html lang="es">
<?php
require './app/conexion.php';

// ── Constantes ────────────────────────────────────────────────────────────────
define('SITE_URL', 'https://www.standscartagena.com.co');
define('WA_NUMBER', '573002434036');

// ── Helper: genera link de WhatsApp con mensaje pre-armado ───────────────────
function waLink(string $standNombre, string $standTipo, int $standId, string $pagina = ''): string
{
    $url     = SITE_URL . ($pagina ? '/' . ltrim($pagina, '/') : '') . '#stand-' . $standId;
    $mensaje = urlencode(
        "¡Hola!, Vi este stand en la galería y me interesa cotizarlo:\n\n" .
            "*Stand:* {$standNombre}\n" .
            "*Tipo:* {$standTipo}\n\n" .
            " Ver stand: {$url}\n\n" .
            "¿Me pueden dar más información y precio?"
    );
    return 'https://wa.me/' . WA_NUMBER . '?text=' . $mensaje;
}

/*
 * Trae TODOS los stands activos con imagen, de todas las ciudades.
 * Ciudades: 1=Cartagena, 2=Santa Marta, 3=Barranquilla
 */
$stmt = $pdo->query("
    SELECT s.*, c.nombre AS nombre_ciudad
    FROM stands s
    LEFT JOIN ciudades c ON c.id = s.id_ciudad
    WHERE s.activo = 1
      AND s.imagen IS NOT NULL
      AND s.imagen != ''
    ORDER BY s.id_ciudad ASC, s.orden ASC, s.id ASC
");
$todosStands = $stmt->fetchAll();

// Mapeo ciudad → página para el link del stand
$paginaCiudad = [
    1 => 'ciudades/cartagena.php',
    2 => 'ciudades/santaMarta.php',
    3 => 'ciudades/barranquilla.php',
];

// Agrupar por ciudad
$porCiudad = [];
$ciudades  = [];
foreach ($todosStands as $s) {
    $cid  = $s['id_ciudad'];
    $cnom = $s['nombre_ciudad'] ?? 'Sin ciudad';
    if (!isset($porCiudad[$cid])) {
        $porCiudad[$cid] = [];
        $ciudades[$cid]  = $cnom;
    }
    $porCiudad[$cid][] = $s;
}

$totalFotos = count($todosStands);
?>

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Galería de Trabajos — Marca &amp; Medios</title>
    <meta name="description" content="Galería de stands diseñados y montados por Marca & Medios en Cartagena, Barranquilla y Santa Marta. Más de 600 marcas atendidas." />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;700&family=Montserrat:wght@700;900&display=swap" rel="stylesheet" />
    <style>
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0
        }

        :root {
            --dark: #080c14;
            --dark2: #0e1520;
            --dark3: #141e2e;
            --orange: #f05a1a;
            --orange2: #ff7a3d;
            --orange-glow: rgba(240, 90, 26, .35);
            --white: #ffffff;
            --gray: #8a9ab0;
            --gray2: #c5d0de;
            --nav-height: 72px
        }

        html {
            scroll-behavior: smooth
        }

        body {
            font-family: "DM Sans", sans-serif;
            background: var(--dark);
            color: var(--white);
            min-height: 100vh;
            overflow-x: hidden
        }

        /* CURSOR */
        .cursor {
            width: 12px;
            height: 12px;
            background: var(--orange);
            border-radius: 50%;
            position: fixed;
            top: 0;
            left: 0;
            pointer-events: none;
            z-index: 9999;
            transform: translate(-50%, -50%);
            transition: transform .1s, background .2s;
            mix-blend-mode: difference
        }

        .cursor-ring {
            width: 36px;
            height: 36px;
            border: 1.5px solid rgba(240, 90, 26, .6);
            border-radius: 50%;
            position: fixed;
            top: 0;
            left: 0;
            pointer-events: none;
            z-index: 9998;
            transform: translate(-50%, -50%);
            transition: transform .18s ease, width .2s, height .2s
        }

        /* NAVBAR */
        nav {
            position: sticky;
            top: 0;
            z-index: 100;
            height: var(--nav-height);
            background: rgba(8, 12, 20, .92);
            backdrop-filter: blur(20px);
            display: flex;
            align-items: center;
            padding: 0 60px;
            gap: 40px;
            border-bottom: 1px solid rgba(255, 255, 255, .06);
            transition: background .3s
        }

        nav.scrolled {
            background: rgba(8, 12, 20, .98)
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            flex-shrink: 0
        }

        .logo-text .brand {
            font-size: 16px;
            font-weight: 900;
            color: var(--white);
            letter-spacing: 2px;
            font-family: "Montserrat", sans-serif
        }

        .logo-text .brand span {
            color: var(--orange)
        }

        .logo-text .sub {
            font-size: 8px;
            color: var(--gray);
            letter-spacing: 3px;
            text-transform: uppercase
        }

        .nav-links {
            display: flex;
            gap: 28px;
            margin-left: 20px
        }

        .nav-links a {
            color: var(--gray2);
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            letter-spacing: .3px;
            transition: color .2s;
            position: relative
        }

        .nav-links a::after {
            content: "";
            position: absolute;
            bottom: -4px;
            left: 0;
            width: 0;
            height: 1.5px;
            background: var(--orange);
            transition: width .25s ease
        }

        .nav-links a:hover,
        .nav-links a.active {
            color: var(--white)
        }

        .nav-links a:hover::after,
        .nav-links a.active::after {
            width: 100%
        }

        .nav-cta {
            margin-left: auto;
            background: var(--orange);
            color: #fff;
            text-decoration: none;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            padding: 10px 24px;
            border-radius: 50px;
            transition: transform .2s, box-shadow .2s, background .2s;
            box-shadow: 0 4px 20px var(--orange-glow);
            font-family: "Montserrat", sans-serif
        }

        .nav-cta:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px var(--orange-glow);
            background: var(--orange2)
        }

        .nav-admin-btn {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: rgba(255, 255, 255, .05);
            border: 1px solid rgba(255, 255, 255, .08);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: var(--gray);
            text-decoration: none;
            transition: all .2s;
            flex-shrink: 0
        }

        .nav-admin-btn:hover {
            background: rgba(240, 90, 26, .1);
            border-color: rgba(240, 90, 26, .3);
            color: var(--orange)
        }

        /* Dropdown */
        .nav-dropdown {
            position: relative
        }

        .nav-dropdown-trigger {
            background: transparent;
            border: none;
            cursor: pointer;
            color: var(--gray2);
            font-size: 13px;
            font-weight: 500;
            letter-spacing: .3px;
            font-family: "DM Sans", sans-serif;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 0;
            transition: color .2s;
            position: relative
        }

        .nav-dropdown-trigger::after {
            content: "";
            position: absolute;
            bottom: -4px;
            left: 0;
            width: 0;
            height: 1.5px;
            background: var(--orange);
            transition: width .25s ease
        }

        .nav-dropdown-trigger:hover,
        .nav-dropdown-trigger[aria-expanded="true"] {
            color: var(--white)
        }

        .nav-dropdown-trigger:hover::after,
        .nav-dropdown-trigger[aria-expanded="true"]::after {
            width: 100%
        }

        .dropdown-arrow {
            transition: transform .3s cubic-bezier(.34, 1.56, .64, 1);
            flex-shrink: 0
        }

        .nav-dropdown-trigger[aria-expanded="true"] .dropdown-arrow {
            transform: rotate(180deg)
        }

        .nav-dropdown-menu {
            position: absolute;
            top: calc(100% + 20px);
            left: 50%;
            transform: translateX(-50%) translateY(-8px);
            width: 300px;
            background: rgba(14, 21, 32, .97);
            backdrop-filter: blur(24px);
            border: 1px solid rgba(255, 255, 255, .09);
            border-radius: 20px;
            padding: 8px;
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            transition: opacity .25s ease, transform .3s cubic-bezier(.34, 1.56, .64, 1), visibility .25s;
            z-index: 500;
            box-shadow: 0 24px 60px rgba(0, 0, 0, .6)
        }

        .dropdown-glow {
            position: absolute;
            top: -1px;
            left: 50%;
            transform: translateX(-50%);
            width: 60%;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(240, 90, 26, .6), transparent);
            border-radius: 50%
        }

        .nav-dropdown-menu.open {
            opacity: 1;
            visibility: visible;
            pointer-events: auto;
            transform: translateX(-50%) translateY(0)
        }

        .dropdown-item {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 12px 14px;
            border-radius: 14px;
            text-decoration: none;
            transition: background .2s ease, transform .2s ease
        }

        .dropdown-item:hover {
            background: rgba(255, 255, 255, .05);
            transform: translateX(4px)
        }

        .dropdown-item-body {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 2px
        }

        .dropdown-item-name {
            font-size: 13px;
            font-weight: 700;
            color: var(--white);
            font-family: "Montserrat", sans-serif;
            letter-spacing: .3px
        }

        .dropdown-item-desc {
            font-size: 11px;
            color: var(--gray);
            font-weight: 300
        }

        .dropdown-item-arrow {
            opacity: 0;
            transform: translateX(-4px);
            transition: opacity .2s, transform .2s;
            stroke: var(--orange);
            flex-shrink: 0
        }

        .dropdown-item:hover .dropdown-item-arrow {
            opacity: 1;
            transform: translateX(0)
        }

        /* Hamburger */
        .hamburger {
            display: none;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            gap: 5px;
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, .05);
            border: 1px solid rgba(255, 255, 255, .1);
            border-radius: 10px;
            cursor: pointer;
            flex-shrink: 0;
            margin-left: auto
        }

        .hamburger span {
            display: block;
            width: 20px;
            height: 2px;
            background: var(--white);
            border-radius: 2px;
            transition: transform .35s cubic-bezier(.34, 1.56, .64, 1), opacity .2s
        }

        .hamburger.open span:nth-child(1) {
            transform: translateY(7px) rotate(45deg);
            background: var(--orange)
        }

        .hamburger.open span:nth-child(2) {
            opacity: 0;
            transform: scaleX(0)
        }

        .hamburger.open span:nth-child(3) {
            transform: translateY(-7px) rotate(-45deg);
            background: var(--orange)
        }

        .mobile-menu {
            display: none;
            position: fixed;
            top: var(--nav-height);
            left: 0;
            right: 0;
            z-index: 99;
            background: rgba(8, 12, 20, .98);
            backdrop-filter: blur(24px);
            border-bottom: 1px solid rgba(255, 255, 255, .07);
            max-height: 0;
            overflow: hidden;
            transition: max-height .4s cubic-bezier(.34, 1.56, .64, 1), padding .3s
        }

        .mobile-menu.open {
            max-height: 500px;
            padding: 16px 0 24px
        }

        .mobile-menu a {
            display: flex;
            align-items: center;
            padding: 14px 24px;
            color: var(--gray2);
            text-decoration: none;
            font-size: 15px;
            font-weight: 500;
            border-bottom: 1px solid rgba(255, 255, 255, .05);
            transition: color .2s, background .2s, padding-left .2s
        }

        .mobile-menu a:hover {
            color: var(--white);
            background: rgba(255, 255, 255, .03);
            padding-left: 30px
        }

        .mobile-menu-cta {
            padding: 16px 24px 0
        }

        .mobile-menu-cta a {
            display: flex !important;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: var(--orange) !important;
            color: #fff !important;
            border-radius: 50px !important;
            font-weight: 700 !important;
            font-size: 13px !important;
            letter-spacing: 1px !important;
            padding: 14px 24px !important;
            font-family: "Montserrat", sans-serif !important;
            border-bottom: none !important
        }

        @media(max-width:768px) {

            .nav-links,
            .nav-cta,
            .nav-dropdown {
                display: none
            }

            .hamburger {
                display: flex
            }

            .mobile-menu {
                display: block
            }

            nav {
                padding: 0 20px;
                gap: 12px
            }
        }

        /* HERO */
        .gallery-hero {
            position: relative;
            padding: 80px 80px 60px;
            overflow: hidden;
            background: var(--dark)
        }

        .gallery-hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(ellipse at 70% 50%, rgba(240, 90, 26, .12) 0%, transparent 55%), radial-gradient(ellipse at 20% 80%, rgba(26, 58, 255, .1) 0%, transparent 50%);
            pointer-events: none
        }

        .gallery-hero-grid {
            position: absolute;
            inset: 0;
            pointer-events: none;
            background-image: linear-gradient(rgba(255, 255, 255, .02) 1px, transparent 1px), linear-gradient(90deg, rgba(255, 255, 255, .02) 1px, transparent 1px);
            background-size: 60px 60px;
            mask-image: radial-gradient(ellipse at 50% 50%, black 40%, transparent 80%)
        }

        .gallery-hero-inner {
            position: relative;
            z-index: 2;
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 40px
        }

        .gallery-hero-left {
            max-width: 600px
        }

        .gallery-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 3px;
            color: var(--orange);
            text-transform: uppercase;
            margin-bottom: 16px;
            font-family: "Montserrat", sans-serif
        }

        .gallery-eyebrow::before {
            content: "";
            width: 20px;
            height: 1.5px;
            background: var(--orange)
        }

        .gallery-hero-title {
            font-family: "Bebas Neue", sans-serif;
            font-size: 88px;
            line-height: .9;
            color: var(--white);
            letter-spacing: 1px;
            margin-bottom: 20px
        }

        .gallery-hero-title .accent {
            color: var(--orange)
        }

        .gallery-hero-sub {
            font-size: 15px;
            line-height: 1.7;
            color: var(--gray2);
            font-weight: 300;
            max-width: 480px
        }

        .gallery-hero-stats {
            display: flex;
            gap: 0;
            border: 1px solid rgba(255, 255, 255, .08);
            border-radius: 20px;
            overflow: hidden;
            background: var(--dark2);
            flex-shrink: 0
        }

        .ghs {
            padding: 24px 36px;
            text-align: center;
            border-right: 1px solid rgba(255, 255, 255, .08)
        }

        .ghs:last-child {
            border-right: none
        }

        .ghs-num {
            font-family: "Bebas Neue", sans-serif;
            font-size: 48px;
            color: var(--orange);
            line-height: 1
        }

        .ghs-num span {
            color: var(--white);
            font-size: 28px
        }

        .ghs-label {
            font-size: 11px;
            color: var(--gray);
            letter-spacing: 1.5px;
            text-transform: uppercase;
            margin-top: 4px
        }

        /* FILTROS */
        .gallery-filters-wrap {
            position: sticky;
            top: var(--nav-height);
            z-index: 50;
            background: rgba(8, 12, 20, .95);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, .07);
            padding: 16px 80px;
            display: flex;
            align-items: center;
            gap: 12px;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none
        }

        .gallery-filters-wrap::-webkit-scrollbar {
            display: none
        }

        .gallery-filters-wrap::after {
            content: '';
            position: absolute;
            right: 0;
            top: 0;
            width: 60px;
            height: 100%;
            background: linear-gradient(to right, transparent, var(--dark));
            pointer-events: none
        }

        .filter-label {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 2px;
            color: var(--gray);
            text-transform: uppercase;
            font-family: "Montserrat", sans-serif;
            flex-shrink: 0;
            margin-right: 8px
        }

        .filter-pills {
            display: flex;
            gap: 10px;
            flex-wrap: nowrap
        }

        .filter-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--dark3);
            border: 1px solid rgba(255, 255, 255, .08);
            color: var(--gray);
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            padding: 9px 18px;
            border-radius: 50px;
            cursor: pointer;
            font-family: "Montserrat", sans-serif;
            transition: all .25s ease;
            user-select: none;
            white-space: nowrap;
            min-width: 110px;
            justify-content: center
        }

        .filter-pill:hover {
            border-color: rgba(240, 90, 26, .4);
            color: var(--white);
            transform: translateY(-1px)
        }

        .filter-pill.active {
            background: linear-gradient(135deg, var(--orange), #d4380d);
            border-color: transparent;
            color: #fff;
            box-shadow: 0 6px 20px var(--orange-glow);
            min-width: 125px
        }

        .pill-count {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: rgba(255, 255, 255, .15);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 9.5px;
            font-weight: 700
        }

        .filter-pill.active .pill-count {
            background: rgba(255, 255, 255, .25)
        }

        .gallery-count-info {
            margin-left: auto;
            font-size: 12px;
            color: var(--gray);
            white-space: nowrap;
            flex-shrink: 0
        }

        .gallery-count-info strong {
            color: var(--orange)
        }

        /* GRID */
        .gallery-main {
            padding: 48px 80px 100px;
            background: var(--dark)
        }

        .gallery-grid {
            columns: 4 240px;
            column-gap: 18px
        }

        .gallery-item {
            break-inside: avoid;
            margin-bottom: 18px;
            border-radius: 16px;
            overflow: hidden;
            position: relative;
            cursor: zoom-in;
            background: var(--dark3);
            border: 1px solid rgba(255, 255, 255, .06);
            transition: transform .35s ease, box-shadow .35s ease, border-color .35s ease;
            animation: fadeInUp .5s ease both
        }

        .gallery-item:hover {
            transform: translateY(-6px) scale(1.01);
            border-color: rgba(240, 90, 26, .4);
            box-shadow: 0 24px 60px rgba(0, 0, 0, .5), 0 0 0 1px rgba(240, 90, 26, .2)
        }

        .gallery-item.hidden {
            display: none
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px)
            }

            to {
                opacity: 1;
                transform: translateY(0)
            }
        }

        .gallery-item img {
            width: 100%;
            display: block;
            transition: transform .4s ease
        }

        .gallery-item:hover img {
            transform: scale(1.04)
        }

        .gallery-item-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(to top, rgba(8, 12, 20, .95) 0%, rgba(8, 12, 20, .4) 40%, transparent 65%);
            opacity: 0;
            transition: opacity .3s ease;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            padding: 20px
        }

        .gallery-item:hover .gallery-item-overlay {
            opacity: 1
        }

        .gallery-item-city {
            font-size: 9px;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: var(--orange);
            font-family: "Montserrat", sans-serif;
            margin-bottom: 4px
        }

        .gallery-item-name {
            font-size: 13px;
            font-weight: 700;
            color: var(--white);
            line-height: 1.3;
            margin-bottom: 6px
        }

        .gallery-item-tipo {
            font-size: 10px;
            color: var(--gray2)
        }

        .gallery-item-zoom {
            position: absolute;
            top: 12px;
            right: 12px;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: rgba(240, 90, 26, .9);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transform: scale(.8);
            transition: opacity .25s, transform .25s
        }

        .gallery-item:hover .gallery-item-zoom {
            opacity: 1;
            transform: scale(1)
        }

        .gallery-item-zoom svg {
            width: 14px;
            height: 14px;
            stroke: #fff
        }

        .gallery-item-badge {
            position: absolute;
            top: 12px;
            left: 12px;
            background: rgba(8, 12, 20, .8);
            border: 1px solid rgba(255, 255, 255, .1);
            backdrop-filter: blur(8px);
            font-size: 9px;
            font-weight: 700;
            letter-spacing: 1.5px;
            color: var(--orange);
            text-transform: uppercase;
            padding: 4px 10px;
            border-radius: 50px;
            font-family: "Montserrat", sans-serif
        }

        /* LIGHTBOX */
        .lightbox {
            position: fixed;
            inset: 0;
            z-index: 2000;
            background: rgba(4, 7, 12, .95);
            backdrop-filter: blur(16px);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            pointer-events: none;
            transition: opacity .3s ease
        }

        .lightbox.open {
            opacity: 1;
            pointer-events: auto
        }

        .lightbox-inner {
            max-width: 90vw;
            max-height: 90vh;
            position: relative;
            display: flex;
            align-items: center;
            gap: 20px
        }

        .lightbox-img-wrap {
            position: relative;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 40px 100px rgba(0, 0, 0, .8);
            max-width: 75vw;
            max-height: 82vh
        }

        .lightbox-img-wrap img {
            max-width: 75vw;
            max-height: 82vh;
            width: auto;
            height: auto;
            display: block;
            object-fit: contain
        }

        .lightbox-info {
            width: 260px;
            flex-shrink: 0;
            background: var(--dark2);
            border: 1px solid rgba(255, 255, 255, .08);
            border-radius: 20px;
            padding: 28px 24px
        }

        .lb-city {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 2px;
            color: var(--orange);
            text-transform: uppercase;
            font-family: "Montserrat", sans-serif;
            margin-bottom: 8px
        }

        .lb-name {
            font-family: "Bebas Neue", sans-serif;
            font-size: 28px;
            color: var(--white);
            line-height: 1.1;
            margin-bottom: 8px
        }

        .lb-tipo {
            font-size: 12px;
            color: var(--gray2);
            margin-bottom: 16px
        }

        .lb-desc {
            font-size: 12px;
            color: var(--gray);
            line-height: 1.6;
            margin-bottom: 20px
        }

        /* ── Botón WA en el lightbox ── */
        .lb-cta-wa {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: linear-gradient(135deg, #25d366, #1aad53);
            color: #fff;
            text-decoration: none;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .5px;
            padding: 12px 20px;
            border-radius: 50px;
            font-family: "Montserrat", sans-serif;
            transition: transform .2s, box-shadow .2s;
            width: 100%;
            box-shadow: 0 4px 14px rgba(37, 211, 102, .3);
            margin-bottom: 10px;
        }

        .lb-cta-wa:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(37, 211, 102, .45)
        }

        .lb-cta-general {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: var(--dark3);
            border: 1px solid rgba(255, 255, 255, .12);
            color: var(--gray2);
            text-decoration: none;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .5px;
            padding: 10px 20px;
            border-radius: 50px;
            font-family: "Montserrat", sans-serif;
            transition: all .2s;
            width: 100%
        }

        .lb-cta-general:hover {
            border-color: var(--orange);
            color: var(--orange)
        }

        .lb-counter {
            font-size: 11px;
            color: var(--gray);
            margin-top: 14px;
            text-align: center
        }

        .lb-counter strong {
            color: var(--white)
        }

        .lightbox-close {
            position: fixed;
            top: 20px;
            right: 20px;
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: rgba(255, 255, 255, .08);
            border: 1px solid rgba(255, 255, 255, .12);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: var(--white);
            transition: background .2s, transform .2s;
            z-index: 10
        }

        .lightbox-close:hover {
            background: var(--orange);
            transform: rotate(90deg)
        }

        .lightbox-nav {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: rgba(255, 255, 255, .08);
            border: 1px solid rgba(255, 255, 255, .12);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: var(--white);
            flex-shrink: 0;
            transition: background .2s, transform .2s
        }

        .lightbox-nav:hover {
            background: var(--orange);
            transform: scale(1.1)
        }

        /* CTA */
        .gallery-cta {
            padding: 80px;
            background: linear-gradient(135deg, rgba(240, 90, 26, .15), rgba(26, 58, 255, .1));
            border-top: 1px solid rgba(240, 90, 26, .15);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 40px;
            position: relative;
            overflow: hidden
        }

        .gallery-cta::before {
            content: "";
            position: absolute;
            inset: 0;
            background: radial-gradient(ellipse at 30% 50%, rgba(240, 90, 26, .1) 0%, transparent 60%)
        }

        .gc-left {
            position: relative;
            z-index: 1
        }

        .gc-eyebrow {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 3px;
            color: var(--orange);
            text-transform: uppercase;
            margin-bottom: 12px;
            font-family: "Montserrat", sans-serif
        }

        .gc-title {
            font-family: "Bebas Neue", sans-serif;
            font-size: 52px;
            line-height: 1;
            color: var(--white);
            margin-bottom: 10px
        }

        .gc-sub {
            font-size: 15px;
            color: var(--gray2);
            font-weight: 300
        }

        .gc-actions {
            display: flex;
            gap: 16px;
            align-items: center;
            position: relative;
            z-index: 1;
            flex-shrink: 0
        }

        .btn-primary {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--orange);
            color: #fff;
            text-decoration: none;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: .5px;
            padding: 16px 32px;
            border-radius: 50px;
            transition: transform .2s, box-shadow .2s, background .2s;
            box-shadow: 0 8px 30px var(--orange-glow);
            font-family: "Montserrat", sans-serif
        }

        .btn-primary:hover {
            background: var(--orange2);
            transform: translateY(-3px);
            box-shadow: 0 16px 40px var(--orange-glow)
        }

        /* FOOTER */
        footer {
            background: var(--dark2);
            border-top: 1px solid rgba(255, 255, 255, .05);
            padding: 40px 80px 24px
        }

        .footer-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 40px
        }

        .footer-brand .brand {
            font-size: 15px;
            font-weight: 900;
            color: var(--white);
            letter-spacing: 2px;
            font-family: "Montserrat", sans-serif
        }

        .footer-brand .brand span {
            color: var(--orange)
        }

        .footer-brand .sub {
            font-size: 8px;
            color: var(--gray);
            letter-spacing: 3px;
            text-transform: uppercase
        }

        .footer-bottom {
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, .05);
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 13px;
            color: var(--gray)
        }

        .footer-nav {
            display: flex;
            gap: 28px
        }

        .footer-nav a {
            color: var(--gray);
            text-decoration: none;
            font-size: 13px;
            transition: color .2s
        }

        .footer-nav a:hover {
            color: var(--white)
        }

        /* WA FAB */
        .whatsapp-fab {
            position: fixed;
            bottom: 28px;
            right: 28px;
            z-index: 200;
            width: 56px;
            height: 56px;
            background: #25d366;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 6px 24px rgba(37, 211, 102, .5);
            cursor: pointer;
            transition: transform .2s, box-shadow .2s;
            animation: pulse-wa 2s infinite
        }

        .whatsapp-fab:hover {
            transform: scale(1.12);
            box-shadow: 0 10px 30px rgba(37, 211, 102, .65);
            animation: none
        }

        .whatsapp-fab svg {
            width: 28px;
            height: 28px;
            fill: #fff
        }

        @keyframes pulse-wa {

            0%,
            100% {
                box-shadow: 0 6px 24px rgba(37, 211, 102, .5), 0 0 0 0 rgba(37, 211, 102, .4)
            }

            50% {
                box-shadow: 0 6px 24px rgba(37, 211, 102, .5), 0 0 0 12px rgba(37, 211, 102, 0)
            }
        }

        /* RESPONSIVE */
        @media(max-width:1200px) {

            .gallery-hero,
            .gallery-main,
            .gallery-cta,
            footer {
                padding-left: 60px;
                padding-right: 60px
            }

            .gallery-filters-wrap {
                padding: 14px 60px
            }

            .gallery-grid {
                columns: 3 200px
            }

            .gallery-hero-title {
                font-size: 72px
            }
        }

        @media(max-width:1024px) {

            .gallery-hero,
            .gallery-main,
            .gallery-cta,
            footer {
                padding-left: 40px;
                padding-right: 40px
            }

            .gallery-filters-wrap {
                padding: 14px 40px
            }

            .gallery-grid {
                columns: 3 180px
            }

            .gallery-hero-inner {
                flex-direction: column;
                align-items: flex-start
            }

            .gallery-hero-stats {
                align-self: stretch;
                justify-content: center
            }
        }

        @media(max-width:768px) {
            .gallery-hero {
                padding: 50px 24px 40px
            }

            .gallery-hero-title {
                font-size: 54px
            }

            .gallery-filters-wrap {
                padding: 12px 16px;
                gap: 10px
            }

            .filter-pill {
                font-size: 10.5px;
                padding: 9px 16px;
                min-width: 110px
            }

            .gallery-count-info {
                display: none
            }

            .gallery-main {
                padding: 32px 24px 60px
            }

            .gallery-grid {
                columns: 2 140px;
                column-gap: 12px
            }

            .gallery-cta {
                flex-direction: column;
                text-align: center;
                padding: 50px 24px
            }

            .gc-title {
                font-size: 40px
            }

            .gc-actions {
                flex-direction: column;
                width: 100%
            }

            .gc-actions .btn-primary {
                width: 100%;
                justify-content: center
            }

            footer {
                padding: 32px 24px 20px
            }

            .footer-inner {
                flex-direction: column;
                gap: 16px
            }

            .footer-bottom {
                flex-direction: column;
                gap: 12px;
                text-align: center
            }
        }

        @media(max-width:480px) {
            .gallery-hero-title {
                font-size: 44px
            }

            .gallery-grid {
                columns: 1
            }

            .filter-pill {
                font-size: 10px;
                padding: 8px 14px;
                min-width: 100px
            }
        }
    </style>
</head>

<body>
    <div class="cursor" id="cursor"></div>
    <div class="cursor-ring" id="cursor-ring"></div>

    <!-- NAVBAR -->
    <nav id="navbar">
        <a class="logo" href="index.php">
            <div class="logo-text">
                <div class="brand">MAR<span>CA</span> &amp; MEDIOS</div>
                <div class="sub">Publicidad · Eventos</div>
            </div>
        </a>
        <div class="nav-links">
            <a href="index.php">Inicio</a>
            <a href="index.php#stands">Servicios</a>
            <a href="galeria.php" class="active">Galería</a>
            <a href="index.php#proceso">Proceso</a>
            <a href="index.php#beneficios">¿Por qué nosotros?</a>
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
                        <div class="dropdown-item-body"><span class="dropdown-item-name">Cartagena de Indias</span><span class="dropdown-item-desc">Centro histórico del Caribe</span></div>
                        <svg class="dropdown-item-arrow" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path d="m9 18 6-6-6-6" />
                        </svg>
                    </a>
                    <a href="ciudades/barranquilla.php" class="dropdown-item" role="menuitem">
                        <div class="dropdown-item-body"><span class="dropdown-item-name">Barranquilla</span><span class="dropdown-item-desc">Capital del Atlántico</span></div>
                        <svg class="dropdown-item-arrow" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path d="m9 18 6-6-6-6" />
                        </svg>
                    </a>
                    <a href="ciudades/santaMarta.php" class="dropdown-item" role="menuitem">
                        <div class="dropdown-item-body"><span class="dropdown-item-name">Santa Marta</span><span class="dropdown-item-desc">Ciudad histórica de Colombia</span></div>
                        <svg class="dropdown-item-arrow" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path d="m9 18 6-6-6-6" />
                        </svg>
                    </a>
                </div>
            </div>
            <a href="index.php#cotizar">Contacto</a>
        </div>
        <a href="index.php#cotizar" class="nav-cta">Cotizar ahora</a>
        <a href="login.php" class="nav-admin-btn desktop-only" title="Administrador">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                <circle cx="12" cy="7" r="4" />
            </svg>
        </a>
        <button class="hamburger" id="hamburger" aria-label="Abrir menú"><span></span><span></span><span></span></button>
    </nav>

    <!-- MOBILE MENU -->
    <div class="mobile-menu" id="mobileMenu">
        <a href="index.php">Inicio</a>
        <a href="index.php#stands">Servicios</a>
        <a href="galeria.php">Galería</a>
        <a href="index.php#proceso">Proceso</a>
        <a href="ciudades/cartagena.php">Cartagena</a>
        <a href="ciudades/barranquilla.php">Barranquilla</a>
        <a href="ciudades/santaMarta.php">Santa Marta</a>
        <div class="mobile-menu-cta"><a href="index.php#cotizar">Cotizar ahora</a></div>
    </div>

    <!-- HERO -->
    <div class="gallery-hero">
        <div class="gallery-hero-grid"></div>
        <div class="gallery-hero-inner">
            <div class="gallery-hero-left">
                <p class="gallery-eyebrow">Trabajos Realizados</p>
                <h1 class="gallery-hero-title">NUESTRA<br /><span class="accent">GALERÍA</span><br />DE STANDS</h1>
                <p class="gallery-hero-sub">Fotos reales de proyectos entregados. Así lucen nuestros stands en Cartagena, Barranquilla y Santa Marta — en ferias, congresos y eventos de las marcas más importantes del Caribe colombiano.</p>
            </div>
            <div class="gallery-hero-stats">
                <div class="ghs">
                    <div class="ghs-num"><?= $totalFotos ?></div>
                    <div class="ghs-label">Proyectos</div>
                </div>
                <?php foreach ($ciudades as $cid => $cnom): ?>
                    <div class="ghs">
                        <div class="ghs-num"><?= count($porCiudad[$cid]) ?></div>
                        <div class="ghs-label"><?= htmlspecialchars($cnom) ?></div>
                    </div>
                <?php endforeach; ?>
                <div class="ghs">
                    <div class="ghs-num">600<span>+</span></div>
                    <div class="ghs-label">Marcas</div>
                </div>
            </div>
        </div>
    </div>

    <!-- FILTROS -->
    <div class="gallery-filters-wrap" id="filtersBar">
        <span class="filter-label">Filtrar:</span>
        <div class="filter-pills">
            <button class="filter-pill active" data-filter="all" onclick="galFiltrar('all',this)">
                Todos <span class="pill-count"><?= $totalFotos ?></span>
            </button>
            <?php foreach ($ciudades as $cid => $cnom): ?>
                <button class="filter-pill" data-filter="ciudad-<?= $cid ?>" onclick="galFiltrar('ciudad-<?= $cid ?>',this)">
                    <?= htmlspecialchars($cnom) ?> <span class="pill-count"><?= count($porCiudad[$cid]) ?></span>
                </button>
            <?php endforeach; ?>
        </div>
        <div class="gallery-count-info" id="countInfo">
            Mostrando <strong id="countNum"><?= $totalFotos ?></strong> trabajos
        </div>
    </div>

    <!-- GALERÍA MASONRY -->
    <div class="gallery-main">
        <?php if (empty($todosStands)): ?>
            <div style="text-align:center;padding:80px 20px;color:var(--gray)">
                <p>Próximamente publicaremos nuestra galería de trabajos.</p>
            </div>
        <?php else: ?>
            <div class="gallery-grid" id="galleryGrid">
                <?php foreach ($todosStands as $i => $s):
                    $img    = htmlspecialchars($s['imagen']);
                    $nombre = htmlspecialchars($s['nombre']);
                    $tipo   = htmlspecialchars($s['tipo'] ?? '');
                    $ciudad = htmlspecialchars($s['nombre_ciudad'] ?? '');
                    $desc   = htmlspecialchars($s['descripcion'] ?? '');
                    $cid    = $s['id_ciudad'];
                    $delay  = ($i % 8) * 0.05;
                    // ── Link WA para este stand ──────────────────────────────
                    $pagina = $paginaCiudad[$cid] ?? '';
                    $waHref = waLink($s['nombre'], $s['tipo'] ?? 'Stand', $s['id'], $pagina);
                ?>
                    <div class="gallery-item"
                        data-ciudad="ciudad-<?= $cid ?>"
                        data-idx="<?= $i ?>"
                        data-nombre="<?= $nombre ?>"
                        data-tipo="<?= $tipo ?>"
                        data-ciudad-nombre="<?= $ciudad ?>"
                        data-desc="<?= $desc ?>"
                        data-img="<?= $img ?>"
                        data-wa="<?= htmlspecialchars($waHref) ?>"
                        style="animation-delay:<?= $delay ?>s"
                        onclick="lbAbrir(<?= $i ?>)">
                        <img src="<?= $img ?>" alt="<?= $nombre ?>" loading="lazy" onerror="this.closest('.gallery-item').style.display='none'" />
                        <div class="gallery-item-badge"><?= $ciudad ?></div>
                        <div class="gallery-item-zoom">
                            <svg viewBox="0 0 24 24" stroke-width="2.5">
                                <circle cx="11" cy="11" r="8" />
                                <path d="m21 21-4.35-4.35" />
                                <line x1="11" y1="8" x2="11" y2="14" />
                                <line x1="8" y1="11" x2="14" y2="11" />
                            </svg>
                        </div>
                        <div class="gallery-item-overlay">
                            <div class="gallery-item-city"><?= $ciudad ?></div>
                            <div class="gallery-item-name"><?= $nombre ?></div>
                            <?php if ($tipo): ?><div class="gallery-item-tipo"><?= $tipo ?></div><?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- LIGHTBOX -->
    <div class="lightbox" id="lightbox" onclick="lbCerrar(event)">
        <button class="lightbox-close" onclick="lbCerrar()">
            <svg viewBox="0 0 24 24" stroke-width="2">
                <path d="M18 6 6 18M6 6l12 12" />
            </svg>
        </button>
        <div class="lightbox-inner" onclick="event.stopPropagation()">
            <button class="lightbox-nav" id="lbPrev" onclick="lbNav(-1)">
                <svg viewBox="0 0 24 24">
                    <path d="m15 18-6-6 6-6" />
                </svg>
            </button>
            <div class="lightbox-img-wrap">
                <img id="lbImg" src="" alt="" />
            </div>
            <div class="lightbox-info">
                <div class="lb-city" id="lbCity"></div>
                <div class="lb-name" id="lbName"></div>
                <div class="lb-tipo" id="lbTipo"></div>
                <div class="lb-desc" id="lbDesc"></div>
                <!-- ↓ Botón WA específico para el stand abierto en el lightbox -->
                <a href="#" id="lbCtaWa" class="lb-cta-wa" target="_blank" rel="noopener noreferrer">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z" />
                    </svg>
                    Cotizar este stand
                </a>
                <a href="index.php#cotizar" class="lb-cta-general">
                    Ver todos los stands
                </a>
                <div class="lb-counter"><strong id="lbCurrent">1</strong> / <strong id="lbTotal"><?= $totalFotos ?></strong></div>
            </div>
            <button class="lightbox-nav" id="lbNext" onclick="lbNav(1)">
                <svg viewBox="0 0 24 24">
                    <path d="m9 18 6-6-6-6" />
                </svg>
            </button>
        </div>
    </div>

    <!-- CTA -->
    <div class="gallery-cta">
        <div class="gc-left">
            <div class="gc-eyebrow">¿Te gustó lo que viste?</div>
            <div class="gc-title">TU MARCA MERECE<br />UN STAND <span style="color:var(--orange)">ASÍ DE IMPACTANTE</span></div>
            <div class="gc-sub">Cotiza hoy y recibe tu diseño 3D sin costo adicional.</div>
        </div>
        <div class="gc-actions">
            <a href="https://wa.me/<?= WA_NUMBER ?>?text=<?= urlencode("¡Hola! 👋 Vi la galería de stands y me gustaría cotizar uno. ¿Me pueden ayudar?") ?>"
                class="btn-primary"
                target="_blank"
                rel="noopener noreferrer">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z" />
                </svg>
                Cotizar por WhatsApp
            </a>
            <a href="index.php#cotizar" class="btn-primary" style="background:transparent;border:1px solid rgba(255,255,255,.2);box-shadow:none">
                Solicitar cotización
            </a>
        </div>
    </div>

    <!-- FOOTER -->
    <footer>
        <div class="footer-inner">
            <div class="footer-brand">
                <div class="brand">MAR<span>CA</span> &amp; MEDIOS</div>
                <div class="sub">Publicidad · Eventos</div>
            </div>
            <div class="footer-nav">
                <a href="index.php">Inicio</a>
                <a href="ciudades/cartagena.php">Cartagena</a>
                <a href="ciudades/barranquilla.php">Barranquilla</a>
                <a href="ciudades/santaMarta.php">Santa Marta</a>
                <a href="galeria.php">Galería</a>
                <a href="index.php#cotizar">Contacto</a>
            </div>
        </div>
        <div class="footer-bottom">
            <p>© 2026 Marca &amp; Medios | Desarrollo Web <a href="https://davidlopez09.github.io/edutechltda/" target="_blank" style="color:#2aa5a8;font-weight:700;text-decoration:none">Edutech Ltda</a></p>
            <p>+57 320 6926 909 · standsCartagena.web@gmail.com</p>
        </div>
    </footer>

    <a class="whatsapp-fab"
        href="https://wa.me/<?= WA_NUMBER ?>?text=<?= urlencode("¡Hola! 👋 Quisiera información sobre stands.") ?>"
        target="_blank"
        aria-label="WhatsApp">
        <svg viewBox="0 0 24 24">
            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z" />
        </svg>
    </a>

    <script>
        /* ── CURSOR ── */
        const cur = document.getElementById('cursor');
        const ring = document.getElementById('cursor-ring');
        let mx = 0,
            my = 0,
            rx = 0,
            ry = 0;
        document.addEventListener('mousemove', e => {
            mx = e.clientX;
            my = e.clientY;
            cur.style.transform = `translate(${mx-6}px,${my-6}px)`
        });
        (function anim() {
            rx += (mx - rx - 18) * .12;
            ry += (my - ry - 18) * .12;
            ring.style.transform = `translate(${rx}px,${ry}px)`;
            requestAnimationFrame(anim)
        })();
        document.querySelectorAll('a,button,.gallery-item').forEach(el => {
            el.addEventListener('mouseenter', () => {
                ring.style.width = '54px';
                ring.style.height = '54px';
                ring.style.borderColor = 'rgba(240,90,26,.8)'
            });
            el.addEventListener('mouseleave', () => {
                ring.style.width = '36px';
                ring.style.height = '36px';
                ring.style.borderColor = 'rgba(240,90,26,.6)'
            });
        });

        /* ── NAV SCROLL ── */
        window.addEventListener('scroll', () => document.getElementById('navbar').classList.toggle('scrolled', scrollY > 30));

        /* ── HAMBURGER ── */
        const ham = document.getElementById('hamburger'),
            mob = document.getElementById('mobileMenu');
        ham.addEventListener('click', () => {
            const o = mob.classList.toggle('open');
            ham.classList.toggle('open', o)
        });
        mob.querySelectorAll('a').forEach(a => a.addEventListener('click', () => {
            mob.classList.remove('open');
            ham.classList.remove('open')
        }));

        /* ── DROPDOWN CIUDADES ── */
        (function() {
            const trigger = document.getElementById('dropdownTrigger');
            const menu = document.getElementById('dropdownMenu');
            if (!trigger || !menu) return;
            trigger.addEventListener('click', e => {
                e.stopPropagation();
                const o = trigger.getAttribute('aria-expanded') === 'true';
                trigger.setAttribute('aria-expanded', String(!o));
                menu.classList.toggle('open', !o)
            });
            document.addEventListener('click', () => {
                trigger.setAttribute('aria-expanded', 'false');
                menu.classList.remove('open')
            });
            menu.addEventListener('click', e => e.stopPropagation());
            document.addEventListener('keydown', e => {
                if (e.key === 'Escape') {
                    trigger.setAttribute('aria-expanded', 'false');
                    menu.classList.remove('open');
                    trigger.focus()
                }
            });
        })();

        /* ══════════════════════════════════════════════════════
           FILTROS
        ══════════════════════════════════════════════════════ */
        function galFiltrar(filtro, btn) {
            document.querySelectorAll('.filter-pill').forEach(p => p.classList.remove('active'));
            btn.classList.add('active');
            let visible = 0;
            document.querySelectorAll('.gallery-item').forEach((item, i) => {
                const match = filtro === 'all' || item.dataset.ciudad === filtro;
                if (match) {
                    item.classList.remove('hidden');
                    item.style.animationDelay = (i % 8 * .04) + 's';
                    item.style.animation = 'none';
                    requestAnimationFrame(() => {
                        item.style.animation = ''
                    });
                    visible++
                } else item.classList.add('hidden');
            });
            document.getElementById('countNum').textContent = visible;
            buildVisibleIndex();
        }

        /* ══════════════════════════════════════════════════════
           LIGHTBOX
        ══════════════════════════════════════════════════════ */
        let lbItems = [],
            lbIndex = 0;

        function buildVisibleIndex() {
            lbItems = Array.from(document.querySelectorAll('.gallery-item:not(.hidden)'));
        }
        buildVisibleIndex();

        function lbAbrir(globalIdx) {
            const item = document.querySelector(`.gallery-item[data-idx="${globalIdx}"]`);
            if (!item) return;
            lbIndex = lbItems.indexOf(item);
            if (lbIndex === -1) lbIndex = 0;
            lbCargar(lbIndex);
            document.getElementById('lightbox').classList.add('open');
            document.body.style.overflow = 'hidden';
        }

        function lbCargar(idx) {
            const item = lbItems[idx];
            if (!item) return;
            document.getElementById('lbImg').src = item.dataset.img;
            document.getElementById('lbImg').alt = item.dataset.nombre;
            document.getElementById('lbCity').textContent = item.dataset.ciudadNombre;
            document.getElementById('lbName').textContent = item.dataset.nombre;
            document.getElementById('lbTipo').textContent = item.dataset.tipo;
            document.getElementById('lbDesc').textContent = item.dataset.desc;
            document.getElementById('lbCurrent').textContent = idx + 1;
            document.getElementById('lbTotal').textContent = lbItems.length;
            // ↓ Actualizar el link de WhatsApp con el stand actual
            document.getElementById('lbCtaWa').href = item.dataset.wa;
            lbIndex = idx;
        }

        function lbNav(dir) {
            let next = lbIndex + dir;
            if (next < 0) next = lbItems.length - 1;
            if (next >= lbItems.length) next = 0;
            lbCargar(next);
        }

        function lbCerrar(e) {
            if (e && e.target !== document.getElementById('lightbox') && !e.currentTarget.classList.contains('lightbox-close')) return;
            document.getElementById('lightbox').classList.remove('open');
            document.body.style.overflow = '';
        }

        document.addEventListener('keydown', e => {
            if (!document.getElementById('lightbox').classList.contains('open')) return;
            if (e.key === 'ArrowRight') lbNav(1);
            if (e.key === 'ArrowLeft') lbNav(-1);
            if (e.key === 'Escape') {
                document.getElementById('lightbox').classList.remove('open');
                document.body.style.overflow = ''
            }
        });
    </script>
</body>

</html>