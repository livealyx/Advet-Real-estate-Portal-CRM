<?php
/**
 * FILE: public/profile.php
 * Updated Member Portal — Sophisticated personalized profile.
 * Located in public/ for clean URL mapping via .htaccess.
 */
session_start();
require_once '../config/db.php';

// Require login
if (empty($_SESSION['user'])) {
    header('Location: ' . BASE . 'auth/login.php');
    exit;
}

$pdo = getPDO();
$uid = (int)$_SESSION['user']['id'];

// Fetch fresh user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$uid]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header('Location: ' . BASE . 'auth/login.php');
    exit;
}

// Fetch user's inquiries
$stmtInq = $pdo->prepare(
    "SELECT i.*, p.title as property_title 
     FROM inquiries i 
     LEFT JOIN properties p ON p.id = i.property_id 
     WHERE i.user_id = ? OR i.email = ?
     ORDER BY i.created_at DESC"
);
$stmtInq->execute([$user['id'], $user['email']]);
$inquiries = $stmtInq->fetchAll();

// Greetings logic
$hour = (int)date('H');
$greeting = 'Good evening';
if ($hour < 12) {
    $greeting = 'Good morning';
} elseif ($hour < 17) {
    $greeting = 'Good afternoon';
}

$firstName = explode(' ', $user['name'])[0];

require_once '../includes/header.php';
?>

<main class="flex-grow pt-48 pb-32 bg-[#FDFCF9]">
    <div class="max-w-7xl mx-auto px-6 sm:px-12">
        
        <!-- Member Portal Header -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-20 gap-8 reveal">
            <div>
                <p class="text-[10px] font-bold uppercase tracking-[0.4em] text-foreground/40 mb-3">Member Portal</p>
                <h1 class="text-5xl sm:text-6xl font-serif font-light leading-tight">
                    <?= $greeting ?>, <span class="italic text-muted"><?= e($firstName) ?>.</span>
                </h1>
            </div>
            
            <div class="flex items-center gap-4">
                <a href="<?= BASE ?>auth/logout.php" class="px-8 py-3.5 rounded-2xl border border-sand text-[9px] font-bold uppercase tracking-widest text-foreground/60 hover:bg-surface transition-all">
                    Sign Out
                </a>
                <a href="<?= BASE ?>admin/dashboard.php" class="px-8 py-3.5 rounded-2xl bg-[#2A2925] text-background text-[9px] font-bold uppercase tracking-widest hover:bg-neutral-800 transition-all shadow-xl">
                    Studio Dashboard
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12">
            
            <!-- Left Info Panel -->
            <div class="lg:col-span-4 space-y-8">
                
                <!-- Account Details Card -->
                <div class="bg-background border border-sand/30 rounded-[2.5rem] p-10 reveal reveal-delay-1 shadow-sm">
                    <h3 class="text-[10px] font-bold uppercase tracking-[0.3em] text-accent mb-8 border-b border-sand/20 pb-6">Account Details</h3>
                    
                    <div class="space-y-6">
                        <div>
                            <p class="text-[9px] font-bold uppercase tracking-widest text-foreground/30 mb-1">Full Name</p>
                            <p class="text-base font-serif italic text-foreground"><?= e($user['name']) ?></p>
                        </div>
                        <div>
                            <p class="text-[9px] font-bold uppercase tracking-widest text-foreground/30 mb-1">Email Address</p>
                            <p class="text-sm font-light text-muted break-all"><?= e($user['email']) ?></p>
                        </div>
                        <div>
                            <p class="text-[9px] font-bold uppercase tracking-widest text-foreground/30 mb-1">Member Since</p>
                            <p class="text-sm font-light text-muted italic"><?= date('F Y', strtotime($user['created_at'])) ?></p>
                        </div>
                        <div>
                            <p class="text-[9px] font-bold uppercase tracking-widest text-foreground/30 mb-3">Portal Access</p>
                            <span class="inline-block px-3 py-1 bg-surface border border-sand/50 rounded-lg text-[8px] font-bold uppercase tracking-widest text-accent">
                                <?= e($user['role']) ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Interaction Summary -->
                <div class="bg-surface/30 border border-sand/30 rounded-[2.5rem] p-10 reveal reveal-delay-2 shadow-sm">
                    <h3 class="text-[10px] font-bold uppercase tracking-[0.3em] text-accent mb-8">Interaction Summary</h3>
                    <div class="grid grid-cols-2 gap-4 divide-x divide-sand/20">
                        <div class="text-center md:text-left">
                            <p class="text-2xl font-serif text-foreground"><?= count($inquiries) ?></p>
                            <p class="text-[8px] font-bold uppercase tracking-widest text-foreground/30">Inquiries</p>
                        </div>
                        <div class="text-center md:text-left pl-4">
                            <p class="text-2xl font-serif text-foreground">0</p>
                            <p class="text-[8px] font-bold uppercase tracking-widest text-foreground/30">Favorites</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content Area -->
            <div class="lg:col-span-8 bg-background border border-sand/30 rounded-[2.5rem] p-12 lg:p-20 reveal reveal-delay-3 shadow-sm min-h-[500px] flex flex-col">
                <h3 class="text-[10px] font-bold uppercase tracking-[0.3em] text-accent mb-12 border-b border-sand/20 pb-6 w-full">Submission Archive</h3>
                
                <?php if (empty($inquiries)): ?>
                    <div class="flex-grow flex flex-col items-center justify-center text-center">
                        <div class="w-16 h-16 rounded-full bg-surface flex items-center justify-center mb-6">
                            <svg class="w-6 h-6 text-sand" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M12 3v12m0 0l-4-4m4 4l4-4M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2" />
                            </svg>
                        </div>
                        <h4 class="text-2xl font-serif italic text-foreground/40 mb-4">No interactions yet.</h4>
                        <p class="text-sm font-light text-muted max-w-xs mb-10 leading-relaxed">
                            Once you inquire about a sanctuary, the record will appear here.
                        </p>
                        <a href="<?= navPath('properties.php') ?>" class="px-10 py-4 bg-surface rounded-2xl text-[10px] font-bold uppercase tracking-widest text-foreground/60 hover:bg-sand/20 transition-all border border-sand/30">
                            Browse Sanctuaries
                        </a>
                    </div>
                <?php else: ?>
                    <div class="space-y-6">
                        <?php foreach ($inquiries as $inq): 
                            $statusCls = match($inq['status']) {
                                'replied' => 'text-green-600 bg-green-50',
                                'read'    => 'text-amber-600 bg-amber-50',
                                default   => 'text-accent bg-accent/5'
                            };
                        ?>
                        <div class="group border border-sand/20 rounded-2xl p-6 hover:bg-surface/30 transition-all">
                            <div class="flex flex-col md:flex-row justify-between md:items-center gap-4 mb-4">
                                <div>
                                    <h4 class="text-sm font-bold uppercase tracking-widest mb-1 font-serif italic text-accent">
                                        <?= $inq['property_id'] ? e($inq['property_title']) : 'General Inquiry' ?>
                                    </h4>
                                    <p class="text-[9px] text-muted uppercase tracking-widest"><?= date('M j, Y', strtotime($inq['created_at'])) ?></p>
                                </div>
                                <span class="px-3 py-1 rounded-full text-[9px] font-bold uppercase tracking-widest w-fit <?= $statusCls ?>">
                                    <?= e($inq['status']) ?>
                                </span>
                            </div>
                            <p class="text-sm text-muted font-light leading-relaxed italic line-clamp-2">
                                "<?= e($inq['message']) ?>"
                            </p>
                            <?php if ($inq['property_id']): ?>
                            <div class="mt-4 pt-4 border-t border-sand/10">
                                <a href="<?= navPath('property-detail.php?id=' . (int)$inq['property_id']) ?>" class="text-[10px] font-bold uppercase tracking-[0.2em] text-accent hover:text-foreground transition-all flex items-center gap-2 group/link">
                                    View Property Details
                                    <svg class="w-3 h-3 group-hover/link:translate-x-1 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</main>

<?php require_once '../includes/footer.php'; ?>
