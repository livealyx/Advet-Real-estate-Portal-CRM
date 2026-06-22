<?php
// FILE: admin/add-user.php
session_start();
require_once '../config/db.php';

// Only admins can manage users
if (empty($_SESSION['user'])) {
    header('Location: ' . BASE . 'auth/login.php'); exit;
}
if ($_SESSION['user']['role'] !== 'admin') {
    header('Location: ' . BASE . 'index.php'); exit;
}

$pdo = getPDO();

// Handle adding a new agent/user directly from admin panel
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_user') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'agent';

    if ($name && $email && $password && in_array($role, ['admin', 'agent', 'member'])) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $_SESSION['flash'] = ['type' => 'error', 'msg' => 'User with this email already exists.'];
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $email, $hash, $role]);
            $_SESSION['flash'] = ['type' => 'success', 'msg' => 'New user added successfully.'];
            header('Location: ' . BASE . 'admin/users.php'); exit;
        }
    } else {
        $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Please fill all fields.'];
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create User | Advet Studio</title>
    <script src="<?= BASE ?>assets/js/tailwind.min.js"></script>
    <?= getAdminTailwindConfig(isset($settings) ? $settings : []) ?>
    <link href="<?= BASE ?>assets/css/fonts.css" rel="stylesheet">
    <style>body{-webkit-font-smoothing:antialiased;} .form-reveal{opacity:0;transform:translateY(20px);animation:fadeIn .8s cubic-bezier(.2,.8,.2,1) forwards}@keyframes fadeIn{to{opacity:1;transform:none}}</style>
</head>
<body class="font-sans font-light min-h-screen bg-[#F4F1ED] flex" style="background-color: <?= $settings['theme_surface'] ?? '#F4F1ED' ?>">

<?php require_once '../includes/flash.php'; ?>
<?php include __DIR__ . '/partials/sidebar.php'; ?>

<main class="flex-grow p-8 sm:p-12 overflow-y-auto">
    <div class="max-w-4xl mx-auto">
        
        <!-- Header -->
        <header class="flex justify-between items-end mb-12 form-reveal">
            <div>
                <p class="text-[10px] font-bold uppercase tracking-[0.4em] text-accent mb-4">Access Control</p>
                <h1 class="text-4xl font-serif font-light italic">Create <span class="text-muted">User</span></h1>
            </div>
            <a href="<?= BASE ?>admin/users.php" class="text-[9px] font-bold uppercase tracking-widest text-muted hover:text-accent transition-colors flex items-center gap-2">
                <svg class="w-3 h-3 rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                Back to Users
            </a>
        </header>

        <!-- Add User Form -->
        <div class="bg-background p-8 md:p-12 rounded-[2.5rem] shadow-sm border border-sand/40 form-reveal" style="animation-delay: 0.1s">
            <h2 class="text-xs uppercase tracking-[0.3em] font-bold text-accent mb-8 border-b border-sand pb-4">Account Details</h2>
            <form method="POST" action="" class="space-y-8">
                <input type="hidden" name="action" value="add_user">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="col-span-1 md:col-span-2">
                        <label class="block text-[10px] uppercase tracking-widest font-bold text-muted mb-3 ml-1">Full Name</label>
                        <input type="text" name="name" required placeholder="Jane Doe"
                               class="w-full px-6 py-4 bg-surface/30 border border-sand/30 rounded-2xl text-sm focus:border-accent focus:outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-[10px] uppercase tracking-widest font-bold text-muted mb-3 ml-1">Email Address</label>
                        <input type="email" name="email" required placeholder="jane@advetbuildwell.com"
                               class="w-full px-6 py-4 bg-surface/30 border border-sand/30 rounded-2xl text-sm focus:border-accent focus:outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-[10px] uppercase tracking-widest font-bold text-muted mb-3 ml-1">Role Assignment</label>
                        <select name="role" class="w-full px-6 py-4 bg-surface/30 border border-sand/30 rounded-2xl text-sm focus:border-accent focus:outline-none transition-all appearance-none cursor-pointer">
                            <option value="agent" selected>Agent</option>
                            <option value="admin">Administrator</option>
                            <option value="member">Member</option>
                        </select>
                    </div>
                    <div class="col-span-1 md:col-span-2">
                        <label class="block text-[10px] uppercase tracking-widest font-bold text-muted mb-3 ml-1">Temporary Password</label>
                        <input type="password" name="password" required placeholder="Minimum 8 characters"
                               class="w-full px-6 py-4 bg-surface/30 border border-sand/30 rounded-2xl text-sm focus:border-accent focus:outline-none transition-all">
                    </div>
                </div>

                <div class="pt-8 mt-8 border-t border-sand/40 flex justify-end gap-4">
                    <a href="<?= BASE ?>admin/users.php" class="px-8 py-4 border border-sand text-muted rounded-2xl text-[10px] font-bold uppercase tracking-widest hover:bg-surface transition-all">
                        Cancel
                    </a>
                    <button type="submit" class="px-8 py-4 bg-foreground text-background rounded-2xl text-[10px] font-bold uppercase tracking-widest shadow-xl hover:-translate-y-1 transition-all">
                        Create Account
                    </button>
                </div>
            </form>
        </div>

    </div>
</main>
</body>
</html>
