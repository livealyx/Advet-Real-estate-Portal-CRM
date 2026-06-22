<?php
// FILE: includes/header.php
if (session_status() === PHP_SESSION_NONE)
    session_start();

// ── Absolute base URL: works from any subdirectory on any server ──────────────
// Find the project root segment from SCRIPT_NAME, e.g. /advet/
// We walk up SCRIPT_NAME until we reach the project root (where index.php lives)
if (!function_exists('siteBase')) {
    function siteBase(): string
    {
        if (defined('BASE'))
            return BASE;

        // Robust detection: Strips D: if leaked by Wamp, finds /advet/ segment
        $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        $script = preg_replace('/^[a-z]:/i', '', $script);

        $folder = basename(dirname(__DIR__));
        $pos = stripos($script, '/' . $folder . '/');

        if ($pos !== false) {
            $base = substr($script, 0, $pos + strlen($folder) + 1) . '/';
        } else {
            $base = preg_replace('{/(admin|actions|config|includes|assets|public|auth|install|index\.php).*$}i', '', $script);
        }

        $base = rtrim($base, '/\\') . '/';
        define('BASE', $base);

        // Auto-load DB if needed
        $projectDir = str_replace('\\', '/', dirname(__DIR__)); // dirname of includes folder
        if (file_exists($projectDir . '/config/db.php')) {
            require_once $projectDir . '/config/db.php';
        }

        return $base;
    }
}

$BASE = siteBase(); // Calculates and ensures BASE constant is defined

function navPath(string $page): string
{
    $path = ltrim($page, '/');
    // Strip .php for non-admin pages for clean URLs
    if (!str_starts_with($path, 'admin/') && str_ends_with($path, '.php')) {
        $path = substr($path, 0, -4);
    }
    return BASE . $path;
}

$projectsArr = [];
try {
    $pdo = getPDO();
    $stmt = $pdo->prepare("SELECT title, slug FROM projects WHERE status='active' ORDER BY created_at DESC LIMIT 10");
    $stmt->execute();
    $dbProjects = $stmt->fetchAll();
    $projectsArr['Discovery Hub'] = BASE . 'projects';
    foreach ($dbProjects as $dp) {
        $projectsArr[$dp['title']] = BASE . 'projects/' . $dp['slug'];
    }
} catch (\Throwable $e) {
    $projectsArr = BASE . 'projects';
}

$nav = [
    'Home' => BASE,
    'Properties' => [
        'All Properties' => BASE . 'all-properties',
        'Residential' => BASE . 'properties',
        'Commercial' => BASE . 'commercial',
    ],
    'Projects' => $projectsArr,
    'Gallery' => BASE . 'gallery',
    'Contact Us' => BASE . 'contact',
];

$currentUri = strtok($_SERVER['REQUEST_URI'], '?');

// Track daily visits and rich session metadata
try {
    $pdoTracker = getPDO();
    $pdoTracker->exec("INSERT INTO page_views (view_date, views) VALUES (CURDATE(), 1) ON DUPLICATE KEY UPDATE views = views + 1");

    // Skip tracking for common static assets if somehow hitting header
    if (!preg_match('/\.(js|css|png|jpg|jpeg|gif|ico|svg)$/i', $currentUri)) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

        $os = 'Unknown OS';
        if (preg_match('/windows/i', $ua))
            $os = 'Windows';
        elseif (preg_match('/mac OS/i', $ua))
            $os = 'Mac OS';
        elseif (preg_match('/linux/i', $ua))
            $os = 'Linux';
        elseif (preg_match('/android/i', $ua))
            $os = 'Android';
        elseif (preg_match('/iphone|ipad|ipod/i', $ua))
            $os = 'iOS';

        $browser = 'Unknown Browser';
        if (preg_match('/edge|edg/i', $ua))
            $browser = 'Edge';
        elseif (preg_match('/chrome/i', $ua))
            $browser = 'Chrome';
        elseif (preg_match('/firefox/i', $ua))
            $browser = 'Firefox';
        elseif (preg_match('/safari/i', $ua))
            $browser = 'Safari';

        $device = 'Desktop';
        if (preg_match('/mobile/i', $ua))
            $device = 'Mobile';
        if (preg_match('/tablet|ipad/i', $ua))
            $device = 'Tablet';

        $stmtLog = $pdoTracker->prepare("INSERT INTO visitor_logs (ip_address, user_agent, device_type, os, browser, page_url) VALUES (?, ?, ?, ?, ?, ?)");
        $stmtLog->execute([$ip, $ua, $device, $os, $browser, $currentUri]);
    }
} catch (\Throwable $e) {
}

// Set up PDO and Load Settings
try {
    $pdo = getPDO();
    $siteSettings = loadSettings($pdo);
} catch (\Throwable $e) {
    $siteSettings = [];
}

$siteName = $siteSettings['site_name'] ?? 'Advet Buildwell';
$siteTagline = $siteSettings['site_tagline'] ?? 'Human-Centric Spaces';
$siteAccent = $siteSettings['accent_color'] ?? '#899178';

// Count new inquiries for badge (admin & agent)
$newInquiryCount = 0;
if (!empty($_SESSION['user']) && in_array($_SESSION['user']['role'], ['admin', 'agent'])) {
    try {
        if ($_SESSION['user']['role'] === 'admin') {
            $newInquiryCount = (int) $pdo->query("SELECT COUNT(*) FROM inquiries WHERE status='new'")->fetchColumn();
        } else {
            $stmt = $pdo->prepare("SELECT COUNT(i.id) FROM inquiries i JOIN properties p ON i.property_id = p.id WHERE i.status='new' AND p.agent_id = ?");
            $stmt->execute([(int) $_SESSION['user']['id']]);
            $newInquiryCount = (int) $stmt->fetchColumn();
        }
    } catch (\Throwable $e) {
    }
}
// Header Theme Logic (Default to light for the new airy theme)
$headerTheme = $headerTheme ?? 'light';
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?= (isset($pageTitle) && $pageTitle) ? e($pageTitle) . ' — ' . e($siteName) : e($siteName) . ' | ' . e($siteTagline) ?>
    </title>
    <meta name="description"
        content="<?= isset($pageDesc) ? e($pageDesc) : e($siteName) . ' — curating minimal, intentional real estate sanctuaries.' ?>">
    <?php if (!empty($pageKeywords)): ?>
        <meta name="keywords" content="<?= e($pageKeywords) ?>">
    <?php endif; ?>
    <script src="<?= navPath('assets/js/tailwind.min.js') ?>"></script>
    <?= getFrontendTailwindConfig($siteSettings) ?>
    <link href="<?= navPath('assets/css/fonts.css') ?>" rel="stylesheet">
    <?php if (!empty($siteSettings['site_favicon'])): ?>
        <link rel="icon" href="<?= imgUrl($siteSettings['site_favicon']) ?>"
            type="image/<?= strtolower(pathinfo($siteSettings['site_favicon'], PATHINFO_EXTENSION)) === 'ico' ? 'x-icon' : strtolower(pathinfo($siteSettings['site_favicon'], PATHINFO_EXTENSION)) ?>">
    <?php else: ?>
        <link rel="icon"
            href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23899178' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'><path d='M12 3l8 7L12 21l-8-11 8-7z'/><path d='M12 3v18'/><path d='M4 10l8 4 8-4'/></svg>">
    <?php endif; ?>
    <style>
        :root {
            --hero-accent:
                <?= $siteSettings['accent_color'] ?? '#899178' ?>
            ;
        }

        body {
            background-color:
                <?= $siteSettings['theme_background'] ?? '#FDFCF9' ?>
            ;
            color:
                <?= $siteSettings['theme_foreground'] ?? '#2A2925' ?>
            ;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        .image-soft-clip {
            clip-path: inset(0 round 2rem);
        }

        .reveal {
            opacity: 0;
            transform: translateY(30px);
            animation: soft-reveal 1.2s cubic-bezier(0.2, 0.8, 0.2, 1) forwards;
        }

        @keyframes soft-reveal {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        input:focus,
        textarea:focus,
        select:focus {
            outline: none;
        }
    </style>
    <!-- Premium Navigation Styles for All Pages -->
    <link rel="stylesheet"
        href="<?= navPath('assets/css/hero-redesign.css') ?>?v=<?= filemtime(dirname(__DIR__) . '/assets/css/hero-redesign.css') ?>">
    <?= isset($extraHead) ? $extraHead : '' ?>
</head>

<body class="font-sans font-light min-h-screen flex flex-col selection:bg-accent selection:text-white">

    <?php require_once __DIR__ . '/flash.php'; ?>

    <?php if (!isset($noNav) || !$noNav): ?>
        <nav class="hero-nav" style="position: absolute; width: 100%; top: 0; left: 0; z-index: 1000; padding: 2.5rem 6%;">
            <!-- Unified Brand Logo -->
            <a href="<?= BASE ?>" class="hero-logo-container">
                <div class="hero-logo-icon">
                    <?php if (!empty($siteSettings['site_logo'])): ?>
                        <img src="<?= imgUrl($siteSettings['site_logo']) ?>" alt="Icon">
                    <?php else: ?>
                        <svg viewBox="0 0 24 24" fill="none" stroke="<?= $siteSettings['accent_color'] ?? '#899178' ?>"
                            stroke-width="2">
                            <path d="M12 3l8 7L12 21l-8-11 8-7z" />
                            <path d="M12 3v18" />
                        </svg>
                    <?php endif; ?>
                </div>
                <div class="hero-logo-text"
                    style="color: <?= isset($isHomepage) ? '#fff' : ($siteSettings['theme_foreground'] ?? '#111') ?>">
                    <?php
                    $logoText = $siteSettings['site_logo_text'] ?? ($siteSettings['site_name'] ?? 'Advet');
                    $parts = explode(' ', e($logoText), 2);
                    echo '<span class="font-bold">' . $parts[0] . '</span>';
                    if (isset($parts[1]))
                        echo ' <span class="hero-logo-light" style="color: inherit; opacity: 0.5;">' . $parts[1] . '</span>';
                    ?>
                </div>
            </a>

            <!-- Premium Central Menu -->
            <div class="hero-nav-center hidden lg:flex"
                style="<?= isset($isHomepage) ? 'border: 1px solid rgba(255,255,255,0.2); box-shadow: none; background: rgba(255,255,255,0.08);' : '' ?>">
                <?php foreach ($nav as $label => $href): ?>
                    <div class="hero-nav-item">
                        <?php if (is_array($href)): ?>
                            <a class="hero-nav-link" onclick="void(0)"
                                style="color: <?= isset($isHomepage) ? '#fff' : ($siteSettings['theme_foreground'] ?? '#333') ?>">
                                <?= e($label) ?>
                                <svg class="w-2.5 h-2.5 opacity-50 transition-transform" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="3">
                                    <path d="M6 9l6 6 6-6" />
                                </svg>
                            </a>
                            <div class="hero-dropdown">
                                <?php foreach ($href as $subLabel => $subHref): ?>
                                    <a href="<?= e($subHref) ?>" class="hero-dropdown-link"><?= e($subLabel) ?></a>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <a href="<?= e($href) ?>" class="hero-nav-link"
                                style="color: <?= isset($isHomepage) ? '#fff' : ($siteSettings['theme_foreground'] ?? '#333') ?>">
                                <?= e($label) ?>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Right Controls -->
            <div class="hero-nav-right flex items-center gap-4 lg:gap-6">
                <!-- Post Property CTA (Desktop) -->
                <?php if (empty($_SESSION['user']) || in_array($_SESSION['user']['role'], ['admin', 'agent'])): ?>
                    <?php
                    $postUrl = empty($_SESSION['user'])
                        ? navPath('auth/login.php') . '?redirect=' . urlencode(navPath('post-property.php'))
                        : navPath('post-property.php');
                    ?>
                    <a href="<?= $postUrl ?>" class="hero-btn-couture group" 
                       style="background: <?= isset($isHomepage) ? 'rgba(255,255,255,0.03)' : 'rgba(0,0,0,0.03)' ?>; border-color: <?= isset($isHomepage) ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.1)' ?>;">
                        <div class="hero-btn-couture-content">
                            <span class="hero-btn-couture-label italic font-serif lowercase" style="color: <?= isset($isHomepage) ? '#fff' : ($siteSettings['theme_foreground'] ?? '#333') ?>">Post Property</span>
                            <div class="hero-btn-couture-icon">
                                <svg class="w-2.5 h-2.5 transition-transform duration-500 group-hover:rotate-90" fill="none"
                                    stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                                    <path d="M12 4.5v15m7.5-7.5h-15" />
                                </svg>
                            </div>
                        </div>
                        <div class="hero-btn-couture-border"></div>
                    </a>
                <?php endif; ?>

                <?php if (!empty($_SESSION['user'])):
                    $u = $_SESSION['user'];
                    $initials = strtoupper(substr($u['name'], 0, 1)) . (strpos($u['name'], ' ') !== false ? strtoupper(substr(strrchr($u['name'], ' '), 1, 1)) : '');
                    ?>
                    <div class="hero-nav-item">
                        <div class="flex items-center gap-3 cursor-pointer group">
                            <div class="hero-nav-link hidden md:flex"
                                style="color: <?= isset($isHomepage) ? '#fff' : ($siteSettings['theme_foreground'] ?? '#333') ?>; padding-right: 0.5rem;">
                                <span>My Profile</span>
                                <svg class="w-2.5 h-2.5 opacity-50 transition-transform group-hover:rotate-180"
                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                    <path d="M6 9l6 6 6-6" />
                                </svg>
                            </div>
                            <div
                                class="w-10 h-10 rounded-full bg-accent flex items-center justify-center font-bold text-xs text-white ring-2 ring-transparent group-hover:ring-accent/30 transition-all overflow-hidden relative shrink-0">
                                <?php if (!empty($u['profile_picture'])): ?>
                                    <img src="<?= imgUrl($u['profile_picture']) ?>" alt="Profile"
                                        class="w-full h-full object-cover">
                                <?php else: ?>
                                    <?= htmlspecialchars($initials) ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Profile Dropdown -->
                        <div class="hero-dropdown" style="left: auto; right: 0; transform: translateY(10px);">
                            <div class="px-4 py-3 border-b border-white/5 mb-2">
                                <p class="text-[10px] font-bold uppercase tracking-widest text-accent mb-0.5">
                                    <?= e($u['role']) ?>
                                </p>
                                <p class="text-sm font-serif text-white truncate"><?= e($u['name']) ?></p>
                            </div>
                            <a href="<?= navPath('profile.php') ?>" class="hero-dropdown-link flex items-center gap-3">
                                <svg class="w-4 h-4 opacity-50" fill="none" stroke="currentColor" stroke-width="2"
                                    viewBox="0 0 24 24">
                                    <path
                                        d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                                </svg>
                                My Profile
                            </a>
                            <?php if (in_array($u['role'], ['admin', 'agent'])): ?>
                                <a href="<?= navPath('admin/listings.php') ?>" class="hero-dropdown-link flex items-center gap-3">
                                    <svg class="w-4 h-4 opacity-50" fill="none" stroke="currentColor" stroke-width="2"
                                        viewBox="0 0 24 24">
                                        <path
                                            d="M2.25 21h19.5m-18-18v18m10.5-18v18m0-13.5h3.75m-3.75 3h3.75m-3.75 3h3.75m-3.75 3h3.75m-9-12h3.75m-3.75 3h3.75m-3.75 3h3.75m-3.75 3h3.75" />
                                    </svg>
                                    My Listings
                                </a>
                                <a href="<?= navPath('admin/dashboard.php') ?>" class="hero-dropdown-link flex items-center gap-3">
                                    <svg class="w-4 h-4 opacity-50" fill="none" stroke="currentColor" stroke-width="2"
                                        viewBox="0 0 24 24">
                                        <path
                                            d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
                                    </svg>
                                    Dashboard
                                </a>
                            <?php endif; ?>
                            <div class="h-px bg-white/5 my-2"></div>
                            <a href="<?= navPath('auth/logout.php') ?>"
                                class="hero-dropdown-link flex items-center gap-3 text-red-400 hover:text-red-300">
                                <svg class="w-4 h-4 opacity-50" fill="none" stroke="currentColor" stroke-width="2"
                                    viewBox="0 0 24 24">
                                    <path
                                        d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" />
                                </svg>
                                Logout
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="<?= navPath('auth/login.php') ?>" class="btn-contact hidden sm:flex"
                        style="color: <?= isset($isHomepage) ? '#fff' : ($siteSettings['theme_foreground'] ?? '#333') ?>; background: <?= isset($isHomepage) ? 'rgba(255,255,255,0.15)' : 'rgba(0,0,0,0.05)' ?>; border-color: <?= isset($isHomepage) ? 'rgba(255,255,255,0.2)' : 'rgba(0,0,0,0.1)' ?>;">Login</a>
                    <a href="<?= navPath('auth/register.php') ?>" class="btn-signup hidden md:flex">Sign Up</a>
                <?php endif; ?>

                <button id="hero-mobile-btn"
                    class="lg:hidden p-2 text-white bg-white/10 rounded-xl backdrop-blur-md border border-white/20">
                    <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>
        </nav>
    <?php endif; ?>

    <!-- Full-Screen Mobile Drawer -->
    <div id="mobile-menu"
        class="hidden lg:hidden fixed inset-0 z-[100] translate-x-full transition-transform duration-500 ease-in-out pointer-events-auto">
        <div id="mobile-menu-close" class="absolute inset-0 bg-black/20 backdrop-blur-md"></div>
        <div
            class="absolute right-0 top-0 bottom-0 w-[85%] max-w-sm bg-white shadow-2xl flex flex-col p-10 overflow-y-auto">
            <div class="flex items-center justify-between mb-12">
                <span class="text-xs font-bold uppercase tracking-[0.4em] text-accent">Menu</span>
                <button id="hero-mobile-close"
                    class="w-10 h-10 flex items-center justify-center rounded-2xl bg-black/5 text-muted hover:text-foreground">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M18 6 6 18M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <?php if (!empty($_SESSION['user'])):
                $u = $_SESSION['user'];
                $initials = strtoupper(substr($u['name'], 0, 1)) . (strpos($u['name'], ' ') !== false ? strtoupper(substr(strrchr($u['name'], ' '), 1, 1)) : '');
                ?>
                <div class="flex items-center gap-4 mb-10 pb-10 border-b border-black/5">
                    <div
                        class="w-14 h-14 rounded-2xl bg-accent flex items-center justify-center font-bold text-lg text-foreground overflow-hidden relative shrink-0">
                        <?php if (!empty($u['profile_picture'])): ?>
                            <img src="<?= imgUrl($u['profile_picture']) ?>" alt="Profile" class="w-full h-full object-cover">
                        <?php else: ?>
                            <?= htmlspecialchars($initials) ?>
                        <?php endif; ?>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-widest text-accent mb-0.5"><?= e($u['role']) ?>
                        </p>
                        <p class="text-xl font-serif text-foreground truncate"><?= e($u['name']) ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <div class="space-y-8">
                <!-- Post Property CTA (Mobile) -->
                <?php if (empty($_SESSION['user']) || in_array($_SESSION['user']['role'], ['admin', 'agent'])): ?>
                    <a href="<?= $postUrl ?>"
                        class="flex items-center justify-center gap-3 w-full py-5 bg-accent text-white text-xs font-bold uppercase tracking-[0.2em] rounded-2xl shadow-xl transform active:scale-95 transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        Post Property
                    </a>
                <?php endif; ?>

                <?php if (!empty($_SESSION['user'])): ?>
                    <div class="space-y-4">
                        <p class="text-[9px] uppercase tracking-[0.4em] text-muted/40 font-bold">Account</p>
                        <div class="flex flex-col gap-5 pl-4 border-l border-black/5">
                            <a href="<?= navPath('profile.php') ?>" class="text-xl font-serif font-light text-foreground">My
                                Profile</a>
                            <?php if (in_array($u['role'], ['admin', 'agent'])): ?>
                                <a href="<?= navPath('admin/dashboard.php') ?>"
                                    class="text-xl font-serif font-light text-foreground">Dashboard</a>
                                <a href="<?= navPath('admin/crm.php') ?>"
                                    class="text-xl font-serif font-light text-foreground">CRM Overview</a>
                            <?php endif; ?>
                            <a href="<?= navPath('auth/logout.php') ?>"
                                class="text-xl font-serif font-light text-red-500">Sign Out</a>
                        </div>
                    </div>
                <?php endif; ?>

                <?php foreach ($nav as $label => $href): ?>
                    <?php if (is_array($href)): ?>
                        <div class="space-y-4">
                            <p class="text-[9px] uppercase tracking-[0.4em] text-muted/40 font-bold"><?= e($label) ?></p>
                            <div class="flex flex-col gap-5 pl-4 border-l border-black/5">
                                <?php foreach ($href as $subLabel => $subHref): ?>
                                    <a href="<?= e($subHref) ?>"
                                        class="text-xl font-serif font-light text-foreground"><?= e($subLabel) ?></a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="<?= e($href) ?>"
                            class="block text-2xl font-serif font-light text-foreground"><?= e($label) ?></a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <?php if (!isset($solidNav) || !$solidNav): ?>
        <script>
            (function () {
                const nav = document.getElementById('main-nav');
                if (!nav) return;
                function onScroll() {
                    if (window.scrollY > 40) {
                        nav.classList.add('nav-scrolled');
                    } else {
                        nav.classList.remove('nav-scrolled');
                    }
                }
                window.addEventListener('scroll', onScroll, { passive: true });
                onScroll();
                const btn = document.getElementById('mobile-menu-btn');
                const mob = document.getElementById('mobile-menu');
                const backdrop = document.getElementById('mobile-menu-close');
                const closeBtn = document.getElementById('mobile-menu-close-btn');
                if (btn && mob) {
                    btn.addEventListener('click', () => {
                        mob.classList.remove('translate-x-full');
                        mob.classList.add('translate-x-0');
                    });
                    const closeMenu = () => {
                        mob.classList.remove('translate-x-0');
                        mob.classList.add('translate-x-full');
                    };
                    closeBtn?.addEventListener('click', closeMenu);
                    backdrop?.addEventListener('click', closeMenu);
                }
            })();
        </script>
    <?php else: ?>
        <script>
            (function () {
                const btn = document.getElementById('hero-mobile-btn');
                const mob = document.getElementById('mobile-menu');
                const backdrop = document.getElementById('mobile-menu-close');
                const closeBtn = document.getElementById('hero-mobile-close');

                if (btn && mob) {
                    btn.addEventListener('click', () => {
                        mob.classList.remove('hidden'); // Ensure it's not hidden
                        setTimeout(() => {
                            mob.classList.remove('translate-x-full');
                            mob.classList.add('translate-x-0');
                        }, 10);
                    });

                    const closeMenu = () => {
                        mob.classList.remove('translate-x-0');
                        mob.classList.add('translate-x-full');
                        setTimeout(() => mob.classList.add('hidden'), 500);
                    }

                    closeBtn?.addEventListener('click', closeMenu);
                    backdrop?.addEventListener('click', closeMenu);
                }
            })();
        </script>
    <?php endif; ?>