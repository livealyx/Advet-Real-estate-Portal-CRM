<?php
// FILE: actions/save-settings.php
session_start();
require_once '../config/db.php';
if (empty($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ' . BASE . 'auth/login.php'); exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ' . BASE . 'admin/settings.php'); exit; }

$pdo      = getPDO();
$settings = $_POST['settings'] ?? [];

$allowed = [
    'site_name','site_logo_text','site_tagline','accent_color','contact_email','studio_address','studio_phone', 'studio_landline',
    'site_copyright',
    'hours_mon_fri','hours_sat','hours_sun','social_instagram','social_linkedin','social_facebook','social_youtube',
    'social_socialvynk','newsletter_enabled','newsletter_frequency','inquiry_notifications','mfa_enabled','currency',
    'privacy_policy','terms_of_use','theme_background','theme_foreground','theme_surface','theme_muted','theme_sand','theme_accent_dark',
    'ai_enabled','ai_provider','ai_api_key', 'ai_model', 'ai_system_instruction', 'ai_chat_title', 'ai_welcome_msg',
    'cache_enabled', 'cache_ttl_listing', 'cache_ttl_detail'
];

$stmt = $pdo->prepare(
    "INSERT INTO settings (setting_key, setting_value)
         VALUES (?, ?)
     ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)"
);

foreach ($allowed as $key) {
    if (isset($settings[$key])) {
        $value = trim((string)$settings[$key]);
        $stmt->execute([$key, $value]);
    }
}

// Handle Logo Upload
$asyncLogo = $_POST['async_site_logo'] ?? null;
if ($asyncLogo) {
    $stmt->execute(['site_logo', $asyncLogo]);
} elseif (isset($_FILES['site_logo']) && $_FILES['site_logo']['error'] === UPLOAD_ERR_OK) {
    $logoPath = uploadSiteLogo($_FILES['site_logo']);
    if ($logoPath) {
        $stmt->execute(['site_logo', $logoPath]);
    }
}

// Handle Favicon Upload
$asyncFav = $_POST['async_site_favicon'] ?? null;
if ($asyncFav) {
    $stmt->execute(['site_favicon', $asyncFav]);
} elseif (isset($_FILES['site_favicon']) && $_FILES['site_favicon']['error'] === UPLOAD_ERR_OK) {
    $favPath = uploadFavicon($_FILES['site_favicon']);
    if ($favPath) {
        $stmt->execute(['site_favicon', $favPath]);
    }
}

// Handle Hero Slider Images
$uploadedPaths = $_POST['existing_slider_images'] ?? [];
if (!empty($_POST['clear_hero'])) {
    $uploadedPaths = [];
}

// Add asynchronously uploaded images
$asyncHero = $_POST['async_site_hero_slider_images'] ?? [];
if (!empty($asyncHero)) {
    $uploadedPaths = array_merge($uploadedPaths, $asyncHero);
}

if (isset($_FILES['site_hero_slider_images'])) {
    $files = $_FILES['site_hero_slider_images'];
    for ($i = 0; $i < count($files['name']); $i++) {
        if ($files['error'][$i] === UPLOAD_ERR_OK) {
            $fileArr = [
                'name'     => $files['name'][$i],
                'type'     => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error'    => $files['error'][$i],
                'size'     => $files['size'][$i]
            ];
            $heroPath = uploadHeroBackground($fileArr);
            if ($heroPath) {
                $uploadedPaths[] = $heroPath;
            }
        }
    }
}

$uploadedPaths = array_values(array_unique($uploadedPaths));
$stmt->execute(['site_hero_slider_images', json_encode($uploadedPaths)]);

if (!empty($uploadedPaths)) {
    $stmt->execute(['site_hero_image', $uploadedPaths[0]]);
} else {
    $stmt->execute(['site_hero_image', '']);
}

$_SESSION['flash'] = ['type' => 'success', 'msg' => 'Settings saved successfully.'];
$redir = $_POST['redirect'] ?? 'admin/settings.php';
header('Location: ' . BASE . $redir);
exit;
