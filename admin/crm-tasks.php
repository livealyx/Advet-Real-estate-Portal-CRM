<?php
// FILE: admin/crm-tasks.php
session_start();
require_once '../config/db.php';

if (empty($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'agent'])) {
    header('Location: ' . BASE . 'auth/login.php'); exit;
}

$pdo = getPDO();
$settings = loadSettings($pdo);
$userId = (int)$_SESSION['user']['id'];
$isAdmin = $_SESSION['user']['role'] === 'admin';

// Filter
$status = $_GET['status'] ?? 'pending';

$where = "WHERE 1=1";
$params = [];
if (!$isAdmin) {
    $where .= " AND t.agent_id = ?";
    $params[] = $userId;
}
if ($status === 'pending') {
    $where .= " AND t.status = 'pending'";
} else {
    $where .= " AND t.status = ?";
    $params[] = $status;
}

// Fetch
$stmt = $pdo->prepare("
    SELECT t.*, c.name as contact_name
    FROM crm_tasks t
    LEFT JOIN crm_contacts c ON c.id = t.contact_id
    $where
    ORDER BY t.due_date ASC
");
$stmt->execute($params);
$tasks = $stmt->fetchAll();

// NEW: Fetch Contacts for Add Task modal
$allContacts = $pdo->query("SELECT id, name, email FROM crm_contacts ORDER BY name ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Tasks | Advet CRM</title>
    <script src="<?= BASE ?>assets/js/tailwind.min.js"></script>
    <?= getAdminTailwindConfig($settings) ?>
    <link href="<?= BASE ?>assets/css/fonts.css" rel="stylesheet">
</head>
<body class="font-sans font-light min-h-screen bg-[#F4F1ED] flex" style="background-color: <?= $settings['theme_surface'] ?? '#F4F1ED' ?>">

<?php include __DIR__ . '/partials/sidebar.php'; ?>

<main class="flex-grow p-8 sm:p-12 overflow-y-auto">
    <header class="flex flex-wrap justify-between items-end mb-12 gap-4">
        <div>
            <p class="text-[10px] font-bold uppercase tracking-[0.4em] text-accent mb-4">Operations & Habits</p>
            <h1 class="text-4xl font-serif font-light italic">Daily <span class="text-muted">Tasks</span></h1>
        </div>
        <div class="flex gap-4">
            <div class="flex bg-background border border-sand/40 rounded-2xl p-1 shrink-0">
                <a href="?status=pending" class="px-5 py-2.5 rounded-xl text-[9px] font-bold uppercase tracking-widest <?= $status==='pending' ? 'bg-accent text-foreground shadow-sm' : 'text-muted hover:bg-surface' ?>">Pending</a>
                <a href="?status=completed" class="px-5 py-2.5 rounded-xl text-[9px] font-bold uppercase tracking-widest <?= $status==='completed' ? 'bg-accent text-foreground shadow-sm' : 'text-muted hover:bg-surface' ?>">Completed</a>
            </div>
            <button onclick="toggleModal('taskModal')" class="px-8 py-4 bg-foreground text-background rounded-2xl text-[10px] font-bold uppercase tracking-widest shadow-xl hover:-translate-y-1 transition-all">+ Add Task</button>
        </div>
    </header>

    <div class="space-y-6 max-w-5xl">
        <?php if (empty($tasks)): ?>
            <div class="bg-background rounded-[2.5rem] p-20 text-center border border-sand/30 shadow-sm border-dashed">
                <h4 class="text-2xl font-serif italic text-muted mb-4">A moment of tranquility.</h4>
                <p class="text-xs text-muted/60 uppercase tracking-widest">No <?= $status ?> tasks found for this period.</p>
            </div>
        <?php else: foreach ($tasks as $t): 
            $overdue = (strtotime($t['due_date']) < time() && $t['status'] === 'pending');
            $priorityCls = match($t['priority']) {
                'high' => 'text-red-500 bg-red-50',
                'medium' => 'text-accent bg-accent/10',
                default => 'text-muted bg-sand/20'
            };
        ?>
            <div class="bg-background p-8 rounded-[2rem] border <?= $overdue ? 'border-red-200' : 'border-sand/40' ?> shadow-sm flex items-center gap-8 group">
                <div class="shrink-0">
                    <form action="<?= BASE ?>actions/crm-toggle-task.php" method="POST">
                        <input type="hidden" name="id" value="<?= $t['id'] ?>">
                        <button type="submit" class="w-10 h-10 rounded-full border-2 <?= $t['status'] === 'completed' ? 'bg-accent border-accent text-foreground' : 'border-sand hover:border-accent' ?> flex items-center justify-center transition-all">
                            <?php if ($t['status'] === 'completed'): ?>
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path d="M4.5 12.75l6 6 9-13.5" /></svg>
                            <?php endif; ?>
                        </button>
                    </form>
                </div>
                <div class="flex-grow">
                    <div class="flex items-center gap-4 mb-2">
                        <span class="px-3 py-0.5 rounded-full text-[8px] font-bold uppercase tracking-[0.15em] <?= $priorityCls ?>"><?= e($t['priority']) ?></span>
                        <span class="text-[9px] font-bold uppercase tracking-widest <?= $overdue ? 'text-red-500' : 'text-muted' ?>"><?= date('M j, Y — g:ia', strtotime($t['due_date'])) ?> <?= $overdue ? '— OVERDUE' : '' ?></span>
                    </div>
                    <h3 class="text-lg font-serif italic <?= $t['status'] === 'completed' ? 'line-through opacity-40' : '' ?>"><?= e($t['title']) ?></h3>
                    <?php if ($t['contact_name']): ?>
                        <p class="text-[10px] text-accent font-bold uppercase tracking-widest mt-2">Related to: <?= e($t['contact_name']) ?></p>
                    <?php endif; ?>
                </div>
                <div class="shrink-0 flex gap-2 transition-opacity">
                    <form action="<?= BASE ?>actions/crm-delete-task.php" method="POST" onsubmit="return confirm('Archive this task permanently?')">
                        <input type="hidden" name="id" value="<?= $t['id'] ?>">
                        <button type="submit" class="p-3 bg-surface rounded-xl text-muted hover:text-red-500 transition-colors"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg></button>
                    </form>
                </div>
            </div>
        <?php endforeach; endif; ?>
    </div>
</main>

<!-- Add Task Modal -->
<div id="taskModal" class="fixed inset-0 bg-foreground/40 backdrop-blur-sm z-[100] hidden flex items-center justify-center p-6">
    <div class="bg-background w-full max-w-lg rounded-[2.5rem] p-10 shadow-2xl border border-sand/40 max-h-[90vh] overflow-y-auto custom-scrollbar">
        <h3 class="text-xl font-serif italic text-foreground mb-10 text-center">New <span class="text-muted">Task</span></h3>
        <form action="<?= BASE ?>actions/crm-add-task.php" method="POST" class="space-y-8">
            <div class="space-y-2">
                <label class="text-[10px] uppercase tracking-widest font-bold text-muted">Title / Objective</label>
                <input type="text" name="title" required placeholder="Follow up with buyer..." class="w-full px-6 py-4 bg-surface/30 border border-sand/40 rounded-2xl text-xs focus:outline-none focus:border-accent">
            </div>

            <div class="grid grid-cols-2 gap-6">
                <div class="space-y-2">
                    <label class="text-[10px] uppercase tracking-widest font-bold text-muted">Due At</label>
                    <input type="datetime-local" name="due_date" required class="w-full px-6 py-4 bg-surface/30 border border-sand/40 rounded-2xl text-xs focus:outline-none focus:border-accent">
                </div>
                <!-- Custom Priority Selector -->
                <div class="space-y-2 relative" id="priority-selector">
                    <label class="text-[10px] uppercase tracking-widest font-bold text-muted">Urgency</label>
                    <input type="hidden" name="priority" id="task_prio_input" value="medium">
                    <button type="button" onclick="toggleDropdown('priority-options')" class="w-full flex items-center justify-between px-6 py-4 bg-surface/30 border border-sand/40 rounded-2xl text-[10px] font-bold uppercase tracking-widest focus:outline-none focus:border-accent transition-all text-left">
                        <span id="selected-priority">Medium</span>
                        <svg class="w-3 h-3 text-muted shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7" /></svg>
                    </button>
                    <div id="priority-options" class="absolute top-full left-0 right-0 mt-2 bg-background border border-sand/30 rounded-2xl shadow-2xl py-2 hidden z-[101]">
                        <?php foreach(['low' => 'Low', 'medium' => 'Medium', 'high' => 'High'] as $val => $lab): ?>
                            <div class="px-6 py-3 hover:bg-surface/50 cursor-pointer text-[9px] font-bold uppercase tracking-widest" onclick="selectItem('priority', '<?= $val ?>', '<?= $lab ?>')"><?= $lab ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="space-y-2 relative" id="contact-selector">
                <label class="text-[10px] uppercase tracking-widest font-bold text-muted">Related Contact (Optional)</label>
                <input type="hidden" name="contact_id" id="task_contact_input">
                <button type="button" onclick="toggleDropdown('contact-options')" class="w-full flex items-center justify-between px-6 py-4 bg-surface/30 border border-sand/40 rounded-2xl text-[10px] font-bold uppercase tracking-widest focus:outline-none focus:border-accent transition-all text-left">
                    <span id="selected-contact" class="truncate max-w-[200px] text-muted/50 font-normal">Select a lead...</span>
                    <svg class="w-3 h-3 text-muted shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7" /></svg>
                </button>
                <div id="contact-options" class="absolute top-full left-0 right-0 mt-2 bg-background border border-sand/30 rounded-2xl shadow-2xl py-2 hidden z-[101] max-h-48 overflow-y-auto custom-scrollbar">
                    <div class="px-6 py-3 border-b border-sand/10">
                        <input type="text" onkeyup="filterList(this, '.contact-option')" placeholder="Search leads..." class="w-full bg-surface/30 rounded-lg px-3 py-2 text-[10px] focus:outline-none">
                    </div>
                    <div class="px-6 py-3 hover:bg-surface/50 cursor-pointer text-[9px] font-bold uppercase tracking-widest contact-option" onclick="selectItem('contact', '', 'None Selected')">None Selected</div>
                    <?php foreach($allContacts as $c): ?>
                        <div class="px-6 py-3 hover:bg-surface/50 cursor-pointer text-[9px] font-bold uppercase tracking-widest contact-option" onclick="selectItem('contact', <?= $c['id'] ?>, '<?= e($c['name']) ?>')">
                            <?= e($c['name']) ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="flex gap-4 pt-10 border-t border-sand/20">
                <button type="button" onclick="toggleModal('taskModal')" class="flex-grow py-4 border border-sand/40 rounded-2xl text-[10px] font-bold uppercase tracking-widest hover:bg-surface transition-all text-muted">Cancel</button>
                <button type="submit" class="flex-grow py-4 bg-foreground text-background rounded-2xl text-[10px] font-bold uppercase tracking-widest shadow-xl hover:-translate-y-1 transition-all">Create Task</button>
            </div>
        </form>
    </div>
</div>

<script>
    function toggleModal(id) { document.getElementById(id).classList.toggle('hidden'); }
    
    function toggleDropdown(id) {
        const d = document.getElementById(id);
        const hidden = d.classList.contains('hidden');
        document.querySelectorAll('[id$="-options"]').forEach(x => x.classList.add('hidden'));
        if (hidden) d.classList.remove('hidden');
    }

    function selectItem(type, id, name) {
        const input = document.getElementById(`task_${type}_input`);
        const label = document.getElementById(`selected-${type}`);
        input.value = id;
        label.innerText = name;
        if (id === '') {
            label.classList.add('text-muted/50', 'font-normal');
        } else {
            label.classList.remove('text-muted/50', 'font-normal');
        }
        document.getElementById(`${type}-options`).classList.add('hidden');
    }

    function filterList(input, selector) {
        const f = input.value.toLowerCase();
        document.querySelectorAll(selector).forEach(o => {
            o.style.display = o.innerText.toLowerCase().includes(f) ? 'block' : 'none';
        });
    }

    window.onclick = function(event) {
        if (!event.target.closest('#priority-selector') && !event.target.closest('#contact-selector')) {
            document.querySelectorAll('[id$="-options"]').forEach(d => d.classList.add('hidden'));
        }
    }
</script>

</body>
</html>

</body>
</html>
