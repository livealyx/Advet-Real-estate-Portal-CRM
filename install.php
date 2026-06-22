<?php
// FILE: install.php — Advet Buildwell Web Installer
// DELETE this file after installation is complete.

define('LOCK_FILE', __DIR__ . '/.install.lock');

// ── If already installed, block access ───────────────────────────────────────
if (file_exists(LOCK_FILE)) {
    http_response_code(403);
    die('Installation is already complete. Delete <code>.install.lock</code> to run again.');
}

// ── Helpers ───────────────────────────────────────────────────────────────────
function check(string $label, bool $pass, string $note = ''): array {
    return ['label' => $label, 'pass' => $pass, 'note' => $note];
}

// ── System Requirements ───────────────────────────────────────────────────────
$checks = [
    check('PHP ≥ 8.1',          version_compare(PHP_VERSION, '8.1', '>='), 'PHP ' . PHP_VERSION . ' detected'),
    check('PDO extension',      extension_loaded('pdo'),       ''),
    check('PDO MySQL driver',   extension_loaded('pdo_mysql'), ''),
    check('JSON extension',     extension_loaded('json'),      ''),
    check('Fileinfo extension', extension_loaded('fileinfo'),  ''),
    check('uploads/ writable',  is_writable(__DIR__ . '/assets/uploads/properties') || @mkdir(__DIR__ . '/assets/uploads/properties', 0755, true), 'assets/uploads/properties/'),
    check('config/ writable',   is_writable(__DIR__ . '/config'), 'config/db.php will be written'),
];

$allPassed = array_reduce($checks, fn($c, $i) => $c && $i['pass'], true);

// ── Read current DB config (if exists) ────────────────────────────────────────
$cfgPath    = __DIR__ . '/config/db.php';
$cfgContent = file_exists($cfgPath) ? file_get_contents($cfgPath) : '';
preg_match("/define\('DB_HOST',\s*'(.+?)'\)/",  $cfgContent, $mHost);
preg_match("/define\('DB_NAME',\s*'(.+?)'\)/",  $cfgContent, $mName);
preg_match("/define\('DB_USER',\s*'(.+?)'\)/",  $cfgContent, $mUser);

$step = $_GET['step'] ?? 'requirements';
$errors = [];
$success = '';

// ── Step 2: Test connection then install ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['db_host'])) {
    $dbHost  = trim($_POST['db_host']  ?? 'localhost');
    $dbName  = trim($_POST['db_name']  ?? 'advet_buildwell');
    $dbUser  = trim($_POST['db_user']  ?? 'root');
    $dbPass  = $_POST['db_pass']       ?? '';
    $step    = 'install';

    // 1. Test connection and set up schema
    try {
        $pdo = new PDO(
            "mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4",
            $dbUser, $dbPass,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    } catch (\PDOException $e) {
        $errors[] = 'Database connection failed: ' . $e->getMessage();
        $step = 'database';
    }

    if (empty($errors)) {
        // 3. Run schema from schema.sql file
        $schemaPath = __DIR__ . '/schema.sql';
        if (file_exists($schemaPath)) {
            $sql = file_get_contents($schemaPath);
            // Remove any legacy CREATE DATABASE / USE just in case to avoid shared hosting errors
            $sql = preg_replace('/CREATE\s+DATABASE.*?;/is', '', $sql);
            $sql = preg_replace('/USE\s+[a-zA-Z0-9_-]+;/is', '', $sql);
            try {
                $pdo->exec($sql);
            } catch (\PDOException $e) {
                $errors[] = 'Schema creation failed: ' . $e->getMessage();
            }
        } else {
            $errors[] = 'schema.sql file is missing!';
        }
    }

    if (empty($errors)) {
        // 4. Seed settings & admin user
        $seedSettings = [
            ['site_name',            'Advet Buildwell'],
            ['accent_color',         '#899178'],
            ['contact_email',        'studio@advetbuildwell.com'],
            ['newsletter_enabled',   '1'],
            ['newsletter_frequency', 'weekly'],
            ['mfa_enabled',          '0'],
        ];
        $stmtS = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)");
        foreach ($seedSettings as [$k,$v]) $stmtS->execute([$k,$v]);

        // Admin user — all fields required, no defaults
        $adminName  = trim($_POST['admin_name']  ?? '');
        $adminEmail = trim($_POST['admin_email'] ?? '');
        $adminPass  = $_POST['admin_pass']        ?? '';

        if (!$adminName || !$adminEmail || !filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Admin name and a valid email address are required.';
        }
        if (strlen($adminPass) < 8) {
            $errors[] = 'Admin password must be at least 8 characters.';
        }
        if (!empty($errors)) { $step = 'database'; }

        if (empty($errors)) {
        $hash = password_hash($adminPass, PASSWORD_BCRYPT, ['cost' => 12]);
        try {
            $pdo->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?,?,?,'admin') ON DUPLICATE KEY UPDATE password_hash=VALUES(password_hash), role='admin'")
                ->execute([$adminName, $adminEmail, $hash]);
        } catch (\PDOException $e) {
            $errors[] = 'Admin user creation failed: ' . $e->getMessage();
        }
        } // end empty($errors) guard

        // Seed sample properties
        $sampleProps = [
            ['The Obsidian Villa','the-obsidian-villa','Silverlake Hills',4250000,4,4,4200,'active','Crafted with the precision of a watchmaker and the soul of an artist.','Raw Travertine','Blackened Steel','94/100','https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?auto=format&fit=crop&q=80&w=800','[]'],
            ['The Haven','the-haven','Coastal Bluffs',2800000,3,3,3100,'active','Perched above the Pacific, The Haven is an exercise in restraint and panoramic living.','Lime Wash Plaster','Smoked Oak','89/100','https://images.unsplash.com/photo-1512917774080-9991f1c4c750?auto=format&fit=crop&q=80&w=800','[]'],
            ['The Outline','the-outline','Urban Minimal District',1950000,2,2,2200,'active','A study in precision and negative space.','Exposed Concrete','Tempered Glass','88/100','https://images.unsplash.com/photo-1613977257363-707ba9348227?auto=format&fit=crop&q=80&w=800','[]'],
        ];
        $stmtP = $pdo->prepare("INSERT IGNORE INTO properties (title,slug,location,price,bedrooms,bathrooms,sqft,status,description,primary_material,secondary_material,acoustic_stillness,featured_image,gallery_images) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        foreach ($sampleProps as $r) $stmtP->execute($r);

        // Seed sample stories
        $sampleStories = [
            ['Designing for the Sun','designing-for-the-sun','How we trace the solar path across a plot of land before laying a single foundation stone.','<p>At Advet Buildwell, every design begins not with sketches but with a solar study.</p>','https://images.unsplash.com/photo-1544457070-4cd773b4d71e?auto=format&fit=crop&q=80&w=800','2026-01-15 09:00:00'],
            ['The Imperfection of Plaster','the-imperfection-of-plaster','Why we choose hand-troweled tadelakt walls over perfect dry-wall.','<p>In a world obsessed with perfection, Advet Buildwell celebrates the subtle imperfection of artisan plaster.</p>','https://images.unsplash.com/photo-1595521624992-48a59aef95e3?auto=format&fit=crop&q=80&w=800','2026-02-10 09:00:00'],
        ];
        $stmtSt = $pdo->prepare("INSERT IGNORE INTO stories (title,slug,excerpt,content,cover_image,published_at) VALUES (?,?,?,?,?,?)");
        foreach ($sampleStories as $r) $stmtSt->execute($r);
    }

    if (empty($errors)) {
        // 5. Update existing config/db.php to preserve advanced settings (imgUrl, avif config, etc).
        $cfgPath = __DIR__ . '/config/db.php';
        if (file_exists($cfgPath)) {
            $cfg = file_get_contents($cfgPath);
            $cfg = preg_replace("/define\('DB_HOST',\s*'.*?'\);/is", "define('DB_HOST', '$dbHost');", $cfg);
            $cfg = preg_replace("/define\('DB_NAME',\s*'.*?'\);/is", "define('DB_NAME', '$dbName');", $cfg);
            $cfg = preg_replace("/define\('DB_USER',\s*'.*?'\);/is", "define('DB_USER', '$dbUser');", $cfg);
            $cfg = preg_replace("/define\('DB_PASS',\s*'.*?'\);/is", "define('DB_PASS', '$dbPass');", $cfg);

            // Automatically set BASE URI for this server installation.
            $basePath = dirname($_SERVER['SCRIPT_NAME']); // e.g., '/' or '/advet'
            $basePath = rtrim(str_replace('\\', '/', $basePath), '/') . '/';
            $cfg = preg_replace("/define\('BASE',\s*'.*?'\);/is", "define('BASE', '$basePath');", $cfg);

            if (file_put_contents($cfgPath, $cfg) === false) {
                $errors[] = 'Could not write config/db.php. Check folder permissions.';
            }
        }

        // 5.1 Update .htaccess to remove any hardcoded RewriteBase
        $htaPath = __DIR__ . '/.htaccess';
        if (file_exists($htaPath)) {
            $hta = file_get_contents($htaPath);
            // Remove hardcoded RewriteBase if it exists
            $hta = preg_replace('/RewriteBase\s+.*?\n/i', '', $hta);
            file_put_contents($htaPath, $hta);
        }
    }

    if (empty($errors)) {
        // 6. Write lock file
        file_put_contents(LOCK_FILE, date('Y-m-d H:i:s'));
        $step = 'done';
    } else {
        $step = 'database'; // show form again with errors
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Advet Buildwell — Installer</title>
<style>
  :root {
    --bg: #FDFCF9; --fg: #2A2925; --muted: #6D685C;
    --surface: #F4F0E6; --accent: #899178; --sand: #DFD8CC;
    --red: #dc2626; --green: #16a34a;
  }
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { background: var(--bg); color: var(--fg); font-family: "DM Sans", system-ui, sans-serif; font-weight: 300; min-height: 100vh; display: flex; flex-direction: column; -webkit-font-smoothing: antialiased; }
  a { color: var(--accent); }

  /* Top bar */
  .topbar { background: var(--fg); padding: 1.25rem 2rem; display:flex; align-items:center; gap:0.75rem; }
  .topbar svg { color: var(--accent); }
  .topbar span { color: white; font-size: 1.1rem; letter-spacing: 0.05em; }
  .topbar small { color: rgba(255,255,255,.4); font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.15em; margin-left: auto; }

  /* Progress bar */
  .steps { background: var(--surface); border-bottom: 1px solid var(--sand); display:flex; }
  .step  { flex: 1; padding: 1rem 1.5rem; font-size: 0.65rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.2em; color: var(--muted); border-bottom: 3px solid transparent; transition: all .3s; }
  .step.active { color: var(--accent); border-color: var(--accent); }
  .step.done   { color: var(--green); border-color: var(--green); }

  /* Container */
  .container { max-width: 680px; width: 100%; margin: 3rem auto; padding: 0 1.5rem 4rem; flex-grow:1; }
  h1 { font-size: 2.5rem; font-weight: 300; margin-bottom: 0.5rem; }
  h1 span { font-style: italic; color: var(--muted); }
  .sub { color: var(--muted); font-size: 0.9rem; margin-bottom: 2.5rem; line-height: 1.7; }

  /* Check rows */
  .check-row { display:flex; align-items:center; gap:1rem; padding: 0.9rem 1.25rem; border-radius:1rem; background: var(--surface); margin-bottom:0.5rem; border: 1px solid var(--sand); }
  .check-row .icon { width:1.75rem; height:1.75rem; border-radius:50%; display:flex; align-items:center; justify-content:center; flex-shrink:0; font-size:0.8rem; font-weight:700; }
  .pass .icon { background: #dcfce7; color: var(--green); }
  .fail .icon { background: #fee2e2; color: var(--red); }
  .check-row .label { font-size:0.85rem; font-weight:500; flex:1; }
  .check-row .note  { font-size:0.72rem; color:var(--muted); }

  /* Form */
  .card { background: white; border: 1px solid var(--sand); border-radius: 2rem; padding: 2.5rem; margin-bottom: 1.5rem; }
  .card h2 { font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.25em; color: var(--accent); margin-bottom: 1.5rem; padding-bottom: 0.75rem; border-bottom: 1px solid var(--sand); }
  .field { margin-bottom: 1.25rem; }
  label { display:block; font-size:0.65rem; font-weight:700; text-transform:uppercase; letter-spacing:0.2em; color:var(--muted); margin-bottom:0.5rem; }
  input[type=text], input[type=email], input[type=password] {
    width: 100%; padding: 0.875rem 1.25rem;
    background: var(--surface); border: 1px solid var(--sand);
    border-radius: 1rem; font-size: 0.875rem; font-family: inherit;
    color: var(--fg); transition: border-color .2s;
  }
  input:focus { outline: none; border-color: var(--accent); background: white; }
  .hint { font-size: 0.7rem; color: var(--muted); margin-top: 0.35rem; }
  .grid2 { display:grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
  @media(max-width:520px) { .grid2 { grid-template-columns:1fr; } }

  /* Buttons */
  .btn { display:inline-flex; align-items:center; gap:0.5rem; padding:1rem 2.5rem; border-radius:3rem; font-size:0.75rem; font-weight:700; text-transform:uppercase; letter-spacing:0.2em; cursor:pointer; border:none; transition:all .25s; }
  .btn-primary { background:var(--fg); color:white; }
  .btn-primary:hover { background: #3a3830; transform:translateY(-2px); box-shadow:0 8px 24px rgba(42,41,37,.2); }
  .btn-primary:disabled { opacity:.5; cursor:not-allowed; transform:none; box-shadow:none; }
  .btn-outline { background:transparent; color:var(--muted); border:1px solid var(--sand); }
  .btn-outline:hover { background: var(--surface); }
  .btn-success { background: var(--green); color:white; }
  .btn-success:hover { background: #15803d; transform:translateY(-2px); }

  /* Alerts */
  .alert { padding:1rem 1.25rem; border-radius:1rem; margin-bottom:1rem; font-size:0.85rem; border: 1px solid; }
  .alert-error   { background:#fee2e2; border-color:#fca5a5; color:var(--red); }
  .alert-success { background:#dcfce7; border-color:#86efac; color:var(--green); }
  .alert ul { margin: 0.5rem 0 0 1.25rem; }

  /* Result table */
  .result-table { width:100%; border-collapse:collapse; font-size:0.85rem; }
  .result-table td { padding: 0.75rem 1rem; border-bottom:1px solid var(--sand); vertical-align:top; }
  .result-table td:first-child { color:var(--muted); width:40%; font-size:0.75rem; text-transform:uppercase; letter-spacing:0.1em; }
  .badge { display:inline-block; padding:0.2rem 0.75rem; border-radius:2rem; font-size:0.65rem; font-weight:700; text-transform:uppercase; letter-spacing:0.1em; }
  .badge-green { background:#dcfce7; color:var(--green); }
  .badge-blue  { background:#dbeafe; color:#1d4ed8; }

  /* Big success check */
  .success-icon { width:5rem; height:5rem; background:#dcfce7; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 2rem; }
  .success-icon svg { color:var(--green); }
  .actions-row { display:flex; gap:1rem; flex-wrap:wrap; justify-content:center; margin-top:2rem; }

  footer { text-align:center; padding: 1.5rem; font-size:0.7rem; color:var(--muted); border-top:1px solid var(--sand); }
</style>
</head>
<body>

<!-- Top Bar -->
<div class="topbar">
    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round">
        <path d="M12 3l8 7L12 21l-8-11 8-7z"/><path d="M12 3v18"/><path d="M4 10l8 4 8-4"/>
    </svg>
    <span>Advet Buildwell</span>
    <small>Installation Wizard</small>
</div>

<!-- Progress Steps -->
<div class="steps">
    <?php
    $stepOrder = ['requirements','database','install','done'];
    $stepLabels = ['01 Requirements','02 Database','03 Install','04 Complete'];
    $currentIdx = array_search($step, $stepOrder);
    foreach ($stepLabels as $si => $sl):
        $cls = $si < $currentIdx ? 'done' : ($si === $currentIdx ? 'active' : '');
    ?>
    <div class="step <?= $cls ?>"><?= $sl ?></div>
    <?php endforeach; ?>
</div>

<div class="container">

<?php if ($step === 'requirements'): ?>
<!-- ═══════════════════════════════════════════════════════════════════════════
     STEP 1 — REQUIREMENTS
═══════════════════════════════════════════════════════════════════════════ -->
<h1>System <span>Requirements</span></h1>
<p class="sub">Verifying your server environment before installation. All checks must pass to continue.</p>

<?php foreach ($checks as $c): ?>
<div class="check-row <?= $c['pass'] ? 'pass' : 'fail' ?>">
    <div class="icon"><?= $c['pass'] ? '✓' : '✕' ?></div>
    <div class="label"><?= htmlspecialchars($c['label']) ?></div>
    <?php if ($c['note']): ?><div class="note"><?= htmlspecialchars($c['note']) ?></div><?php endif; ?>
</div>
<?php endforeach; ?>

<br>
<?php if ($allPassed): ?>
    <a href="?step=database" class="btn btn-primary">
        Continue
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
    </a>
<?php else: ?>
    <div class="alert alert-error">
        ✕ One or more requirements failed. Please fix the issues above and refresh this page.
    </div>
<?php endif; ?>


<?php elseif ($step === 'database' || ($step === 'install' && !empty($errors))): ?>
<!-- ═══════════════════════════════════════════════════════════════════════════
     STEP 2 — DATABASE CONFIGURATION
═══════════════════════════════════════════════════════════════════════════ -->
<h1>Database <span>Configuration</span></h1>
<p class="sub">Provide your MySQL / MariaDB credentials. The installer will create the database and all tables automatically.</p>

<?php if (!empty($errors)): ?>
<div class="alert alert-error">
    <strong>Installation errors:</strong>
    <ul><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
</div>
<?php endif; ?>

<form method="POST" action="install.php">

    <div class="card">
        <h2>Database Settings</h2>
        <div class="grid2">
            <div class="field">
                <label>DB Host</label>
                <input type="text" name="db_host" id="db_host" value="<?= htmlspecialchars($_POST['db_host'] ?? $mHost[1] ?? 'localhost') ?>" required>
            </div>
            <div class="field">
                <label>Database Name</label>
                <input type="text" name="db_name" id="db_name" value="<?= htmlspecialchars($_POST['db_name'] ?? $mName[1] ?? 'advet_buildwell') ?>" required>
                <p class="hint">Will be created if it doesn't exist.</p>
            </div>
        </div>
        <div class="grid2">
            <div class="field">
                <label>DB Username</label>
                <input type="text" name="db_user" id="db_user" value="<?= htmlspecialchars($_POST['db_user'] ?? $mUser[1] ?? 'root') ?>" required>
            </div>
            <div class="field">
                <label>DB Password</label>
                <input type="password" name="db_pass" id="db_pass" value="" placeholder="Leave blank if none">
            </div>
        </div>
    </div>

    <div class="card">
        <h2>Admin Account</h2>
        <div class="field">
            <label>Your Name <span style="color:var(--red)">*</span></label>
            <input type="text" name="admin_name" id="admin_name" value="<?= htmlspecialchars($_POST['admin_name'] ?? '') ?>" required placeholder="e.g. John Smith">
        </div>
        <div class="grid2">
            <div class="field">
                <label>Admin Email <span style="color:var(--red)">*</span></label>
                <input type="email" name="admin_email" id="admin_email" value="<?= htmlspecialchars($_POST['admin_email'] ?? '') ?>" required placeholder="you@yourdomain.com">
            </div>
            <div class="field">
                <label>Password <span style="color:var(--red)">*</span></label>
                <input type="password" name="admin_pass" id="admin_pass" placeholder="Min 8 characters" minlength="8" required>
                <p class="hint">Choose a strong password — min 8 characters.</p>
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary" id="install_btn">
        Run Installation
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
    </button>
</form>


<?php elseif ($step === 'done'): ?>
<!-- ═══════════════════════════════════════════════════════════════════════════
     STEP 4 — DONE
═══════════════════════════════════════════════════════════════════════════ -->
<div style="text-align:center; padding-top: 1rem;">
    <div class="success-icon">
        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
    </div>
    <h1>Installation <span>Complete!</span></h1>
    <p class="sub" style="max-width:480px;margin:0 auto 2rem;">Your Advet Buildwell platform is ready. The database has been created, tables built, sample data seeded, and your configuration saved.</p>
</div>

<div class="card">
    <h2>Summary</h2>
    <table class="result-table">
        <tr><td>Database</td><td><?= htmlspecialchars($dbName ?? '') ?> <span class="badge badge-green">Created</span></td></tr>
        <tr><td>Tables</td><td>6 tables <span class="badge badge-green">Built</span></td></tr>
        <tr><td>Sample Properties</td><td>3 listings <span class="badge badge-green">Seeded</span></td></tr>
        <tr><td>Admin Account</td><td><?= htmlspecialchars($adminEmail ?? '') ?> <span class="badge badge-green">Ready</span></td></tr>
        <tr><td>Config File</td><td>config/db.php <span class="badge badge-green">Written</span></td></tr>
        <tr><td>Lock File</td><td>.install.lock <span class="badge badge-green">Created</span></td></tr>
    </table>
</div>

<div class="alert alert-error" style="border-radius:1rem;">
    <strong>⚠ Security:</strong> Delete or restrict <code>install.php</code> from your server immediately. The lock file prevents re-running, but removing the file is safer.
</div>

<div class="actions-row">
    <a href="index.php" class="btn btn-success">
        View Site
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
    </a>
    <a href="auth/login.php" class="btn btn-primary">
        Admin Login
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
    </a>
    <a href="#" onclick="if(confirm('Delete install.php now?')) window.location='install.php?selfdelete=1'" class="btn btn-outline">
        Delete installer
    </a>
</div>

<?php
// Self-delete endpoint
if (isset($_GET['selfdelete'])) {
    @unlink(__FILE__);
    header('Location: index.php'); exit;
}
?>

<?php endif; ?>

</div><!-- /container -->

<footer>Advet Buildwell &copy; <?= date('Y') ?> &nbsp;·&nbsp; Installation Wizard &nbsp;·&nbsp; PHP <?= PHP_VERSION ?></footer>

<script>
// Disable submit button after click to prevent double-submit
var f = document.querySelector('form');
if (f) {
    f.addEventListener('submit', function() {
        var btn = document.getElementById('install_btn');
        if (btn) { btn.disabled = true; btn.innerHTML = 'Installing…'; }
    });
}
</script>
</body>
</html>
