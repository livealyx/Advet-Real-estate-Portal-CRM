<?php
// FILE: admin/users.php
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

// Handle role changes
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_role') {
    $userId = (int)$_POST['user_id'];
    $newRole = $_POST['role'];
    if (in_array($newRole, ['admin', 'agent', 'member']) && $userId !== (int)$_SESSION['user']['id']) {
        $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->execute([$newRole, $userId]);
        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'User role updated to ' . ucfirst($newRole) . '.'];
    }
    header('Location: ' . BASE . 'admin/users.php'); exit;
}

// Handle user deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_user') {
    $userId = (int)$_POST['user_id'];
    // Prevent self-deletion
    if ($userId !== (int)$_SESSION['user']['id']) {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'User deleted successfully.'];
    }
    header('Location: ' . BASE . 'admin/users.php'); exit;
}

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
        }
    } else {
        $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Please fill all fields.'];
    }
    header('Location: ' . BASE . 'admin/users.php'); exit;
}

// Fetch all users
$users = $pdo->query("SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users & Agents | Advet Studio</title>
    <script src="<?= BASE ?>assets/js/tailwind.min.js"></script>
    <?= getAdminTailwindConfig(isset($settings) ? $settings : []) ?>
    <link href="<?= BASE ?>assets/css/fonts.css" rel="stylesheet">
    <style>body{-webkit-font-smoothing:antialiased;} .form-reveal{opacity:0;transform:translateY(20px);animation:fadeIn .8s cubic-bezier(.2,.8,.2,1) forwards}@keyframes fadeIn{to{opacity:1;transform:none}}</style>
</head>
<body class="font-sans font-light min-h-screen bg-[#F4F1ED] flex" style="background-color: <?= $settings['theme_surface'] ?? '#F4F1ED' ?>">

<?php require_once '../includes/flash.php'; ?>
<?php include __DIR__ . '/partials/sidebar.php'; ?>

<main class="flex-grow p-8 sm:p-12 overflow-y-auto">
    <div class="max-w-6xl mx-auto">
        
        <header class="flex flex-col sm:flex-row justify-between sm:items-end mb-12 gap-6 form-reveal">
            <div>
                <p class="text-[10px] font-bold uppercase tracking-[0.4em] text-accent mb-4">Access Control</p>
                <h1 class="text-4xl font-serif font-light italic">Agents & <span class="text-muted">Users</span></h1>
            </div>
            <a href="<?= BASE ?>admin/add-user.php" class="px-8 py-4 bg-foreground text-background rounded-2xl text-[10px] font-bold uppercase tracking-widest shadow-xl hover:-translate-y-1 transition-all text-center">
                New User +
            </a>
        </header>

        <!-- Users Table -->
        <div class="bg-background rounded-[2.5rem] shadow-sm border border-sand/40 overflow-hidden form-reveal" style="animation-delay: 0.2s">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-surface/30">
                        <tr>
                            <th class="px-8 py-5 text-[10px] uppercase tracking-widest font-bold text-muted">User details</th>
                            <th class="px-8 py-5 text-[10px] uppercase tracking-widest font-bold text-muted">Email</th>
                            <th class="px-8 py-5 text-[10px] uppercase tracking-widest font-bold text-muted">Joined</th>
                            <th class="px-8 py-5 text-[10px] uppercase tracking-widest font-bold text-muted">Role</th>
                            <th class="px-8 py-5 text-[10px] uppercase tracking-widest font-bold text-muted text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-sand/30">
                        <?php foreach ($users as $u): ?>
                        <tr class="hover:bg-surface/10 transition-colors">
                            <td class="px-8 py-6 text-sm font-medium"><?= e($u['name']) ?></td>
                            <td class="px-8 py-6 text-sm text-muted"><?= e($u['email']) ?></td>
                            <td class="px-8 py-6 text-xs text-muted"><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
                            <td class="px-8 py-6">
                                <?php
                                $roleColors = [
                                    'admin' => 'bg-red-50 text-red-700 border-red-200',
                                    'agent' => 'bg-accent/10 text-accent border-accent/20',
                                    'member' => 'bg-surface text-muted border-sand',
                                ];
                                $cls = $roleColors[$u['role']] ?? $roleColors['member'];
                                ?>
                                <span class="px-3 py-1 text-[9px] uppercase font-bold tracking-widest rounded border <?= $cls ?>">
                                    <?= e($u['role']) ?>
                                </span>
                            </td>
                            <td class="px-8 py-6 text-right">
                                <?php if ((int)$u['id'] !== (int)$_SESSION['user']['id']): ?>
                                <form method="POST" action="" class="inline-flex gap-2">
                                    <input type="hidden" name="action" value="change_role">
                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                    <select name="role" class="px-3 py-1 bg-surface border border-sand/50 rounded text-xs text-muted outline-none cursor-pointer">
                                        <option value="member" <?= $u['role']==='member'?'selected':'' ?>>Member</option>
                                        <option value="agent" <?= $u['role']==='agent'?'selected':'' ?>>Agent</option>
                                        <option value="admin" <?= $u['role']==='admin'?'selected':'' ?>>Admin</option>
                                    </select>
                                    <button type="submit" class="px-3 py-1 bg-sand text-foreground rounded text-[9px] font-bold uppercase tracking-widest hover:bg-accent transition-colors">Apply</button>
                                </form>
                                <form method="POST" action="" class="inline-flex gap-2" onsubmit="return confirm('Are you sure you want to delete this user? This action cannot be undone.');">
                                    <input type="hidden" name="action" value="delete_user">
                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                    <button type="submit" class="px-3 py-1 bg-red-500/10 text-red-500 border border-red-500/20 rounded text-[9px] font-bold uppercase tracking-widest hover:bg-red-500 hover:text-white transition-all">Delete</button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</main>
</body>
</html>
