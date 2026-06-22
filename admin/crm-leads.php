<?php
// FILE: admin/crm-leads.php
session_start();
require_once '../config/db.php';

if (empty($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'agent'])) {
    header('Location: ' . BASE . 'auth/login.php'); exit;
}

$pdo = getPDO();
$settings = loadSettings($pdo);
$userId = (int)$_SESSION['user']['id'];
$isAdmin = $_SESSION['user']['role'] === 'admin';

$search = trim($_GET['search'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;
$offset = ($page - 1) * $perPage;

$where = "WHERE 1=1";
$params = [];

if (!$isAdmin) {
    $where .= " AND c.assigned_to = ?";
    $params[] = $userId;
}

if ($search) {
    $where .= " AND (c.name LIKE ? OR c.email LIKE ? OR c.phone LIKE ? OR c.source LIKE ?)";
    $s = "%$search%";
    $params[] = $s; $params[] = $s; $params[] = $s; $params[] = $s;
}

// Count total
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM crm_contacts c $where");
$countStmt->execute($params);
$totalRows = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalRows / $perPage));

// Fetch
$stmt = $pdo->prepare("
    SELECT c.*, u.name as agent_name,
    (SELECT s.name FROM crm_deals d JOIN crm_stages s ON s.id = d.stage_id WHERE d.contact_id = c.id ORDER BY d.updated_at DESC LIMIT 1) as current_stage
    FROM crm_contacts c
    LEFT JOIN users u ON u.id = c.assigned_to
    $where
    ORDER BY c.created_at DESC
    LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$leads = $stmt->fetchAll();

// Fetch agents & properties for the "Add Lead" modal
$agents = $pdo->query("SELECT id, name FROM users WHERE role IN ('admin', 'agent') ORDER BY name ASC")->fetchAll();
$properties = $pdo->query("SELECT id, title FROM properties WHERE status = 'active' ORDER BY title ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leads & Inquiries | Advet CRM</title>
    <script src="<?= BASE ?>assets/js/tailwind.min.js"></script>
    <?= getAdminTailwindConfig($settings) ?>
    <link href="<?= BASE ?>assets/css/fonts.css" rel="stylesheet">
</head>
<body class="font-sans font-light min-h-screen bg-[#F4F1ED] flex" style="background-color: <?= $settings['theme_surface'] ?? '#F4F1ED' ?>">

<?php include __DIR__ . '/partials/sidebar.php'; ?>
<?php include __DIR__ . '/../includes/flash.php'; ?>

<main class="flex-grow p-8 sm:p-12 overflow-y-auto">
    <header class="flex flex-wrap justify-between items-end mb-12 gap-4">
        <div>
            <p class="text-[10px] font-bold uppercase tracking-[0.4em] text-accent mb-4">Lead Capture Archive</p>
            <h1 class="text-4xl font-serif font-light italic">Leads & <span class="text-muted">Inquiries</span></h1>
        </div>
        <div class="flex gap-4">
            <form method="GET" class="relative">
                <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search leads…" class="w-64 px-6 py-4 bg-background border border-sand/40 rounded-2xl text-xs focus:outline-none focus:border-accent transition-all">
                <button type="submit" class="absolute right-4 top-1/2 -translate-y-1/2 text-muted hover:text-accent">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </button>
            </form>
            <button onclick="toggleModal('leadModal')" class="px-8 py-4 bg-foreground text-background rounded-2xl text-[10px] font-bold uppercase tracking-widest shadow-xl hover:-translate-y-1 transition-all">+ Add Lead</button>
        </div>
    </header>

    <div class="bg-background rounded-[2.5rem] shadow-sm border border-sand/40 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-surface/40 border-b border-sand/30">
                    <tr>
                        <th class="px-8 py-5 text-[10px] uppercase tracking-widest font-bold text-muted">Contact</th>
                        <th class="px-8 py-5 text-[10px] uppercase tracking-widest font-bold text-muted">Source</th>
                        <th class="px-8 py-5 text-[10px] uppercase tracking-widest font-bold text-muted">Pipeline Stage</th>
                        <th class="px-8 py-5 text-[10px] uppercase tracking-widest font-bold text-muted">Assigned To</th>
                        <th class="px-8 py-5 text-[10px] uppercase tracking-widest font-bold text-muted text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-sand/20">
                    <?php if (empty($leads)): ?>
                        <tr><td colspan="5" class="px-8 py-20 text-center text-muted italic font-serif">No leads captured yet.</td></tr>
                    <?php else: foreach ($leads as $l): ?>
                        <tr class="hover:bg-surface/10 transition-colors group">
                            <td class="px-8 py-6">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 rounded-full bg-surface border border-sand/40 flex items-center justify-center font-serif text-accent font-bold"><?= substr($l['name'],0,1) ?></div>
                                    <div>
                                        <p class="text-sm font-medium text-foreground"><?= e($l['name']) ?></p>
                                        <p class="text-[10px] text-muted font-light"><?= e($l['email'] ?: $l['phone']) ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-8 py-6">
                                <span class="px-3 py-1 bg-sand/20 rounded-lg text-[9px] font-bold uppercase tracking-widest text-muted"><?= e($l['source']) ?></span>
                            </td>
                            <td class="px-8 py-6">
                                <span class="text-xs font-serif italic text-accent"><?= e($l['current_stage'] ?: 'No Deal Active') ?></span>
                            </td>
                            <td class="px-8 py-6">
                                <div class="flex items-center gap-2">
                                    <div class="w-5 h-5 rounded-full bg-accent/20 flex items-center justify-center text-[8px] font-bold text-accent"><?= $l['agent_name'] ? substr($l['agent_name'],0,1) : '?' ?></div>
                                    <p class="text-[11px] font-light text-muted"><?= e($l['agent_name'] ?: 'Unassigned') ?></p>
                                </div>
                            </td>
                            <td class="px-8 py-6 text-right">
                                <div class="flex items-center justify-end gap-3">
                                    <button onclick="location.href='crm-contact-detail.php?id=<?= $l['id'] ?>'" class="text-[10px] font-bold uppercase tracking-widest text-accent hover:text-foreground transition-colors underline">View Profile</button>
                                    <form action="<?= BASE ?>actions/crm-delete-lead.php" method="POST" onsubmit="return confirm('Securely purge this lead and all associated records? This action is irreversible.')">
                                        <input type="hidden" name="id" value="<?= $l['id'] ?>">
                                        <button type="submit" class="text-[10px] font-bold uppercase tracking-widest text-red-400 hover:text-red-600 transition-colors">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
        <div class="px-8 py-6 border-t border-sand/30 flex items-center justify-between">
            <p class="text-[9px] text-muted uppercase tracking-widest">Page <?= $page ?> of <?= $totalPages ?> · <?= $totalRows ?> leads</p>
            <div class="flex gap-2">
                <?php for($i=1; $i<=$totalPages; $i++): ?>
                    <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>" class="w-8 h-8 flex items-center justify-center rounded-xl text-[10px] font-bold <?= $i==$page ? 'bg-foreground text-background shadow-lg' : 'bg-surface hover:bg-sand text-muted' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</main>

</main>

<!-- Add Lead Modal -->
<div id="leadModal" class="fixed inset-0 bg-foreground/40 backdrop-blur-sm z-[100] hidden flex items-center justify-center p-6">
    <div class="bg-background w-full max-w-3xl rounded-[2.5rem] p-10 shadow-2xl border border-sand/40 max-h-[90vh] overflow-y-auto custom-scrollbar">
        <div class="flex justify-between items-center mb-10">
            <h3 class="text-xl font-serif italic text-foreground">Capture <span class="text-muted">Lead</span></h3>
            <button onclick="toggleModal('leadModal')" class="text-muted hover:text-foreground transition-colors"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12" /></svg></button>
        </div>

        <form id="leadForm" action="<?= BASE ?>actions/crm-add-lead.php" method="POST" class="space-y-8">
            <div class="grid grid-cols-2 gap-x-10 gap-y-6">
                <div class="space-y-2">
                    <label class="text-[10px] uppercase tracking-widest font-bold text-muted">Full Name</label>
                    <input type="text" name="name" required placeholder="John Doe" class="w-full px-6 py-4 bg-surface/30 border border-sand/40 rounded-2xl text-xs focus:outline-none focus:border-accent">
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] uppercase tracking-widest font-bold text-muted">Email</label>
                    <input type="email" name="email" placeholder="john@example.com" class="w-full px-6 py-4 bg-surface/30 border border-sand/40 rounded-2xl text-xs focus:outline-none focus:border-accent">
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] uppercase tracking-widest font-bold text-muted">Phone String</label>
                    <input type="tel" name="phone" required placeholder="+91 987..." class="w-full px-6 py-4 bg-surface/30 border border-sand/40 rounded-2xl text-xs focus:outline-none focus:border-accent font-bold">
                </div>
                <!-- Custom Source Selector -->
                <div class="space-y-2 relative" id="source-selector">
                    <label class="text-[10px] uppercase tracking-widest font-bold text-muted">Lead Source</label>
                    <input type="hidden" name="source" id="source_input" value="Walk-in">
                    <button type="button" onclick="toggleDropdown('source-options')" class="w-full flex items-center justify-between px-6 py-4 bg-surface/30 border border-sand/40 rounded-2xl text-[10px] font-bold uppercase tracking-widest focus:outline-none focus:border-accent transition-all">
                        <span id="selected-source">Walk-in</span>
                        <svg class="w-3 h-3 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7" /></svg>
                    </button>
                    <div id="source-options" class="absolute top-full left-0 right-0 mt-2 bg-background border border-sand/30 rounded-2xl shadow-2xl py-2 hidden z-50">
                        <?php foreach(['Walk-in','Website','Facebook Ads','WhatsApp','Magicbricks','99acres','Referral','Other'] as $s): ?>
                            <div class="px-6 py-3 hover:bg-surface/50 cursor-pointer text-[9px] font-bold uppercase tracking-widest" onclick="selectSource('<?= $s ?>')"><?= $s ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <!-- Custom Property Type Selector -->
                <div class="space-y-2 relative" id="ptype-selector">
                    <label class="text-[10px] uppercase tracking-widest font-bold text-muted">Property Interest</label>
                    <input type="hidden" name="property_type" id="ptype_input" value="Flat / Apartment">
                    <button type="button" onclick="toggleDropdown('ptype-options')" class="w-full flex items-center justify-between px-6 py-4 bg-surface/30 border border-sand/40 rounded-2xl text-[10px] font-bold uppercase tracking-widest focus:outline-none focus:border-accent transition-all">
                        <span id="selected-ptype">Flat / Apartment</span>
                        <svg class="w-3 h-3 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7" /></svg>
                    </button>
                    <div id="ptype-options" class="absolute top-full left-0 right-0 mt-2 bg-background border border-sand/30 rounded-2xl shadow-2xl py-2 hidden z-50">
                        <?php foreach(['Flat / Apartment','Plot / Land','Commercial','Villa'] as $pt): ?>
                            <div class="px-6 py-3 hover:bg-surface/50 cursor-pointer text-[9px] font-bold uppercase tracking-widest" onclick="selectPType('<?= $pt ?>')"><?= $pt ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] uppercase tracking-widest font-bold text-muted">Budget Range</label>
                    <input type="text" name="budget" placeholder="e.g. 50L - 80L" class="w-full px-6 py-4 bg-surface/30 border border-sand/40 rounded-2xl text-xs focus:outline-none focus:border-accent font-bold">
                </div>
                <!-- Custom Agent Selector -->
                <div class="space-y-2 relative" id="agent-selector">
                    <label class="text-[10px] uppercase tracking-widest font-bold text-muted">Assign Advisory</label>
                    <input type="hidden" name="assigned_to" id="agent_input" value="<?= $userId ?>">
                    <button type="button" onclick="toggleDropdown('agent-options')" class="w-full flex items-center justify-between px-6 py-4 bg-surface/30 border border-sand/40 rounded-2xl text-[10px] font-bold uppercase tracking-widest focus:outline-none focus:border-accent transition-all">
                        <span id="selected-agent"><?= e($_SESSION['user']['name']) ?></span>
                        <svg class="w-3 h-3 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7" /></svg>
                    </button>
                    <div id="agent-options" class="absolute top-full left-0 right-0 mt-2 bg-background border border-sand/30 rounded-2xl shadow-2xl py-2 hidden z-50 max-h-32 overflow-y-auto custom-scrollbar">
                        <?php foreach($agents as $a): ?>
                            <div class="px-6 py-3 hover:bg-surface/50 cursor-pointer text-[9px] font-bold uppercase tracking-widest" onclick="selectAgent(<?= $a['id'] ?>, '<?= e($a['name']) ?>')"><?= e($a['name']) ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <!-- Custom Property Selector -->
                <div class="space-y-2 relative" id="property-selector">
                    <label class="text-[10px] uppercase tracking-widest font-bold text-muted">Link Project</label>
                    <input type="hidden" name="property_id" id="property_input">
                    <button type="button" onclick="toggleDropdown('property-options')" class="w-full flex items-center justify-between px-6 py-4 bg-surface/30 border border-sand/40 rounded-2xl text-[10px] font-bold uppercase tracking-widest focus:outline-none focus:border-accent transition-all">
                        <span id="selected-property" class="text-muted/60 truncate max-w-[150px]">None</span>
                        <svg class="w-3 h-3 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7" /></svg>
                    </button>
                    <div id="property-options" class="absolute top-full left-0 right-0 mt-2 bg-background border border-sand/30 rounded-2xl shadow-2xl py-2 hidden z-50 max-h-32 overflow-y-auto custom-scrollbar">
                        <div class="px-6 py-3 hover:bg-surface/50 cursor-pointer text-[9px] font-bold uppercase tracking-widest" onclick="selectProperty('', 'None')">None</div>
                        <?php foreach($properties as $p): ?>
                            <div class="px-6 py-3 hover:bg-surface/50 cursor-pointer text-[9px] font-bold uppercase tracking-widest truncate" onclick="selectProperty(<?= $p['id'] ?>, '<?= e($p['title']) ?>')"><?= e($p['title']) ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="flex gap-4 pt-10 border-t border-sand/20">
                <button type="button" onclick="toggleModal('leadModal')" class="px-10 py-4 border border-sand/40 rounded-2xl text-[10px] font-bold uppercase tracking-widest hover:bg-surface transition-all text-muted">Discard</button>
                <button type="submit" class="flex-grow py-4 bg-foreground text-background rounded-2xl text-[10px] font-bold uppercase tracking-widest shadow-xl hover:-translate-y-1 transition-all">Capture Lead Now</button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleModal(id) {
    document.getElementById(id).classList.toggle('hidden');
}

function toggleDropdown(id) {
    const dropdown = document.getElementById(id);
    const isHidden = dropdown.classList.contains('hidden');
    document.querySelectorAll('[id$="-options"]').forEach(d => d.classList.add('hidden'));
    if (isHidden) dropdown.classList.remove('hidden');
}

function selectSource(val) {
    document.getElementById('source_input').value = val;
    document.getElementById('selected-source').innerText = val;
    document.getElementById('source-options').classList.add('hidden');
}

function selectPType(val) {
    document.getElementById('ptype_input').value = val;
    document.getElementById('selected-ptype').innerText = val;
    document.getElementById('ptype-options').classList.add('hidden');
}

function selectAgent(id, name) {
    document.getElementById('agent_input').value = id;
    document.getElementById('selected-agent').innerText = name;
    document.getElementById('agent-options').classList.add('hidden');
}

function selectProperty(id, name) {
    document.getElementById('property_input').value = id;
    const label = document.getElementById('selected-property');
    label.innerText = name;
    if (id) label.classList.remove('text-muted/60');
    else label.classList.add('text-muted/60');
    document.getElementById('property-options').classList.add('hidden');
}

window.onclick = function(event) {
    if (!event.target.closest('#source-selector') && !event.target.closest('#agent-selector') && !event.target.closest('#property-selector') && !event.target.closest('#ptype-selector')) {
        document.querySelectorAll('[id$="-options"]').forEach(d => d.classList.add('hidden'));
    }
}
</script>

</body>
</html>
