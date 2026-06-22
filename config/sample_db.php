<?php
// config/sample_db.php — Template for Advet Buildwell configuration.
// Rename this file to db.php and update the credentials below.

// ── Required includes ────────────────────────────────────────────────────────
// Ensure cache is available
if (file_exists(__DIR__ . '/../includes/cache.php')) {
    require_once __DIR__ . '/../includes/cache.php';
}

// ── Dynamic BASE URL Detection ──────────────────────────────────────────────
if (!defined('BASE')) {
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    $docRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? '');
    $projectRoot = str_replace('\\', '/', realpath(__DIR__ . '/../'));
    
    if ($docRoot && str_starts_with($projectRoot, $docRoot)) {
        $base = substr($projectRoot, strlen($docRoot));
    } else {
        $base = preg_replace('{/(admin|actions|config|includes|assets|public|auth|install|index\.php).*$}i', '', $script);
    }
    define('BASE', rtrim($base, '/\\') . '/');
}

define('DB_HOST', 'localhost');
define('DB_NAME', 'your_database_name');
define('DB_USER', 'your_database_user');
define('DB_PASS', 'your_database_password');

// Upload settings
define('UPLOAD_DIR', realpath(__DIR__ . '/../assets/uploads/properties/') . DIRECTORY_SEPARATOR);
define('UPLOAD_URL_PREFIX', 'assets/uploads/properties/');

/**
 * Helper Functions
 * (Wrapped in function_exists guards to prevent redeclaration errors)
 */

if (!function_exists('getPDO')) {
    function getPDO(): PDO {
        static $pdo = null;
        if ($pdo !== null) {
            try {
                // Quick ping to see if server is still there
                $pdo->query("SELECT 1");
            } catch (\Throwable $e) {
                $pdo = null; // Server gone away, force reconnect
            }
        }
        if ($pdo === null) {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
            try {
                $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                    PDO::ATTR_TIMEOUT            => 30, // Connect timeout
                ]);
            } catch (\PDOException $e) {
                throw new \RuntimeException('Database connection failed: ' . $e->getMessage());
            }
        }
        return $pdo;
    }
}

if (!function_exists('slugify')) {
    function slugify(string $text): string {
        $text = mb_strtolower(trim($text), 'UTF-8');
        $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
        $text = preg_replace('/[\s-]+/', '-', $text);
        return trim($text, '-');
    }
}

if (!function_exists('uniqueSlug')) {
    function uniqueSlug(PDO $pdo, string $base, ?int $excludeId = null): string {
        $slug = $base; $i = 2;
        while (true) {
            $sql  = 'SELECT COUNT(*) FROM properties WHERE slug = ?';
            $args = [$slug];
            if ($excludeId) { $sql .= ' AND id != ?'; $args[] = $excludeId; }
            $stmt = $pdo->prepare($sql); $stmt->execute($args);
            if ((int)$stmt->fetchColumn() === 0) break;
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }
}

if (!function_exists('uploadPropertyImage')) {
    function uploadPropertyImage(array $file, string $uploadDir): array {
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/pjpeg', 'image/png', 'image/webp', 'image/gif', 'image/avif'];
        $allowedExts  = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'avif'];

        if (!isset($file['tmp_name']) || $file['tmp_name'] === '') {
            return ['success' => false, 'message' => 'No file uploaded or temp path missing.'];
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errs = [
                UPLOAD_ERR_INI_SIZE   => 'File exceeds upload_max_filesize in php.ini.',
                UPLOAD_ERR_FORM_SIZE  => 'File exceeds MAX_FILE_SIZE in HTML form.',
                UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded.',
                UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
                UPLOAD_ERR_EXTENSION  => 'A PHP extension stopped the file upload.'
            ];
            return ['success' => false, 'message' => $errs[$file['error']] ?? 'Unknown PHP upload error.'];
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($file['type'], $allowedTypes) && !in_array($ext, $allowedExts)) {
            return ['success' => false, 'message' => 'Invalid file type: ' . e($file['type']) . ' (.' . e($ext) . ').'];
        }

        if ($file['size'] > 5 * 1024 * 1024) {
            return ['success' => false, 'message' => 'File size exceeds 5MB limit.'];
        }

        $targetDir = rtrim($uploadDir, '/\\');
        if (!is_dir($targetDir)) {
            if (!mkdir($targetDir, 0777, true)) {
                return ['success' => false, 'message' => 'Failed to create upload directory.'];
            }
        }

        $filename = uniqid('prop_', true) . '.' . $ext;
        $dest = $targetDir . DIRECTORY_SEPARATOR . $filename;
        
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            return ['success' => false, 'message' => 'Failed to move uploaded file. Check folder permissions.'];
        }

        return ['success' => true, 'path' => 'assets/uploads/properties/' . $filename];
    }
}

if (!function_exists('uploadSiteLogo')) {
    function uploadSiteLogo(array $file): string|false {
        $allowed = ['image/jpeg','image/png','image/svg+xml'];
        if (!isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) return false;
        if (!in_array($file['type'], $allowed, true)) return false;
        if ($file['size'] > 2 * 1024 * 1024) return false;

        $uploadDir = __DIR__ . '/../assets/uploads/site/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = 'logo_' . time() . '.' . $ext;
        $dest     = $uploadDir . $filename;
        if (!move_uploaded_file($file['tmp_name'], $dest)) return false;
        return 'assets/uploads/site/' . $filename;
    }
}

if (!function_exists('uploadFavicon')) {
    function uploadFavicon(array $file): string|false {
        $allowed = ['image/x-icon', 'image/png', 'image/vnd.microsoft.icon', 'image/svg+xml'];
        if (!isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) return false;
        if (!in_array($file['type'], $allowed, true)) {
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['ico', 'png', 'svg'])) return false;
        }
        if ($file['size'] > 1 * 1024 * 1024) return false;
        $uploadDir = __DIR__ . '/../assets/uploads/site/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = 'favicon_' . time() . '.' . $ext;
        $dest     = $uploadDir . $filename;
        if (!move_uploaded_file($file['tmp_name'], $dest)) return false;
        return 'assets/uploads/site/' . $filename;
    }
}

if (!function_exists('uploadProfilePicture')) {
    function uploadProfilePicture(array $file): array {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        $allowedExts  = ['jpg', 'jpeg', 'png', 'webp'];
        if (!isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => 'File upload error (code ' . $file['error'] . ').'];
        }
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($file['type'], $allowedTypes) && !in_array($ext, $allowedExts)) {
            return ['success' => false, 'error' => 'Invalid picture format. Use JPG, PNG, or WEBP.'];
        }
        if ($file['size'] > 2 * 1024 * 1024) return ['success' => false, 'error' => 'Max 2MB file size exceeded.'];
        
        $uploadDir = __DIR__ . '/../assets/uploads/users/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $filename = 'avatar_' . uniqid() . '.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
            return ['success' => false, 'error' => 'Failed to securely store avatar.'];
        }
        return ['success' => true, 'path' => 'assets/uploads/users/' . $filename];
    }
}

if (!function_exists('formatPrice')) {
    function formatPrice(float $price): string {
        static $currency = null;
        if ($currency === null) {
            try {
                $currency = getPDO()->query("SELECT setting_value FROM settings WHERE setting_key='currency'")->fetchColumn();
            } catch (\Throwable $e) {}
            if (!$currency) $currency = 'USD';
        }

        if ($currency === 'INR') {
            $num = number_format($price, 0, '.', '');
            $num = preg_replace("/(\d)(?=(\d\d)+\d$)/", "$1,", $num);
            return '₹' . $num;
        }

        return '$' . number_format($price, 0, '.', ',');
    }
}

if (!function_exists('e')) {
    function e(mixed $val): string {
        return htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('loadSettings')) {
    function loadSettings(PDO $pdo): array {
        $rows = $pdo->query('SELECT setting_key, setting_value FROM settings')->fetchAll();
        $settings = array_column($rows, 'setting_value', 'setting_key');
        if (class_exists('AdvetCache')) {
            AdvetCache::init($settings);
        }
        return $settings;
    }
}

if (!function_exists('imgUrl')) {
    function imgUrl(?string $path): string {
        if (!$path) return 'https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?auto=format&fit=crop&q=80&w=800';
        if (preg_match('/^https?:\/\//i', $path)) return $path;
        $base = rtrim(BASE, '/');
        $p = ltrim($path, '/');
        return ($base ? $base . '/' : '/') . $p;
    }
}

if (!function_exists('timeGreeting')) {
    function timeGreeting(): string {
        $h = (int) date('G');
        if ($h < 12) return 'Good morning';
        if ($h < 17) return 'Good afternoon';
        return 'Good evening';
    }
}

if (!function_exists('getAdminTailwindConfig')) {
    function getAdminTailwindConfig(array $settings): string {
        $bg          = $settings['theme_background']     ?? '#FDFCF9';
        $fg          = $settings['theme_foreground']     ?? '#2A2925';
        $surface     = $settings['theme_surface']        ?? '#F4F0E6';
        $muted       = $settings['theme_muted']          ?? '#6D685C';
        $sand        = $settings['theme_sand']           ?? '#DFD8CC';
        $accent      = $settings['accent_color']         ?? '#899178';
        $accentDark  = $settings['theme_accent_dark']    ?? '#6E755F';

        return "<script>tailwind.config={theme:{extend:{colors:{background:'$bg',foreground:'$fg',muted:'$muted',surface:'$surface',accent:{DEFAULT:'$accent',dark:'$accentDark'},sand:'$sand'},fontFamily:{sans:['\"DM Sans\"','system-ui','sans-serif'],serif:['\"Cormorant Garamond\"','serif']}}}}</script>";
    }
}

if (!function_exists('getFrontendTailwindConfig')) {
    function getFrontendTailwindConfig(array $settings): string {
        $bg          = $settings['theme_background']     ?? '#FDFCF9';
        $fg          = $settings['theme_foreground']     ?? '#2A2925';
        $surface     = $settings['theme_surface']        ?? '#F4F0E6';
        $muted       = $settings['theme_muted']          ?? '#6D685C';
        $sand        = $settings['theme_sand']           ?? '#DFD8CC';
        $accent      = $settings['accent_color']         ?? '#899178';
        $accentDark  = $settings['theme_accent_dark']    ?? '#6E755F';

        return "<script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        background: '$bg', foreground: '$fg',
                        muted: '$muted', surface: '$surface',
                        accent: { DEFAULT: '$accent', dark: '$accentDark' },
                        sand: '$sand'
                    },
                    fontFamily: {
                        sans: ['\"Outfit\"', 'system-ui', 'sans-serif'],
                        serif: ['\"Cormorant Garamond\"', 'Playfair Display', 'serif'],
                    },
                    borderRadius: { '4xl': '2rem' },
                    transitionTimingFunction: { 'soft': 'cubic-bezier(0.4, 0, 0.2, 1)' }
                }
            }
        }
        </script>";
    }
}

if (!function_exists('hexToRgb')) {
    function hexToRgb($hex, $asString = false) {
        $hex = ltrim($hex, '#');
        if (strlen($hex) == 3) {
            $r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
            $g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
            $b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
        } else {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
        }
        if ($asString) return "$r, $g, $b";
        return ['r' => $r, 'g' => $g, 'b' => $b];
    }
}

if (!function_exists('adjustBrightness')) {
    function adjustBrightness($hex, $steps) {
        $steps = max(-255, min(255, $steps));
        $hex = ltrim($hex, '#');
        if (strlen($hex) == 3) {
            $hex = str_repeat(substr($hex, 0, 1), 2) . str_repeat(substr($hex, 1, 1), 2) . str_repeat(substr($hex, 2, 1), 2);
        }
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        $r = max(0, min(255, $r + $steps));
        $g = max(0, min(255, $g + $steps));
        $b = max(0, min(255, $b + $steps));
        $r_hex = str_pad(dechex($r), 2, '0', STR_PAD_LEFT);
        $g_hex = str_pad(dechex($g), 2, '0', STR_PAD_LEFT);
        $b_hex = str_pad(dechex($b), 2, '0', STR_PAD_LEFT);
        return "#$r_hex$g_hex$b_hex";
    }
}

if (!function_exists('uploadHeroBackground')) {
    function uploadHeroBackground(array $file): string|false {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/pjpeg', 'image/x-png'];
        $allowedExts  = ['jpg', 'jpeg', 'png', 'webp'];
        
        if (!isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) return false;
        
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($file['type'], $allowedTypes) && !in_array($ext, $allowedExts)) return false;
        if ($file['size'] > 5 * 1024 * 1024) return false; // 5MB limit

        $uploadDir = __DIR__ . '/../assets/uploads/site/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $filename = 'hero_' . time() . '.' . $ext;
        $dest     = $uploadDir . $filename;
        if (!move_uploaded_file($file['tmp_name'], $dest)) return false;
        return 'assets/uploads/site/' . $filename;
    }
}
?>
