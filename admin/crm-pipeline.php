<?php
// FILE: admin/crm-pipeline.php
session_start();
require_once '../config/db.php';

if (empty($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'agent'])) {
    header('Location: ' . BASE . 'auth/login.php'); exit;
}

$pdo = getPDO();
$settings = loadSettings($pdo);
$userId = (int)$_SESSION['user']['id'];
$isAdmin = $_SESSION['user']['role'] === 'admin';

// Fetch stages
$stages = $pdo->query("SELECT * FROM crm_stages ORDER BY display_order ASC")->fetchAll();

// Fetch active deals
$where = "WHERE d.status = 'active'";
$params = [];
if (!$isAdmin) {
    $where .= " AND c.assigned_to = ?";
    $params[] = $userId;
}

$stmt = $pdo->prepare("
    SELECT d.*, c.name as contact_name, c.phone as contact_phone, p.title as property_title, p.featured_image
    FROM crm_deals d
    JOIN crm_contacts c ON c.id = d.contact_id
    LEFT JOIN properties p ON p.id = d.property_id
    $where
    ORDER BY d.updated_at DESC
");
$stmt->execute($params);
$allDeals = $stmt->fetchAll();

// Group deals by stage
$dealsByStage = [];
foreach ($stages as $s) {
    $dealsByStage[$s['id']] = array_filter($allDeals, fn($d) => $d['stage_id'] == $s['id']);
}

// NEW: Fetch Contacts & Properties for the "New Deal" modal
$allContacts = $pdo->query("SELECT id, name, email FROM crm_contacts ORDER BY name ASC")->fetchAll();
$allProps    = $pdo->query("SELECT id, title FROM properties WHERE status = 'active' ORDER BY title ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Pipeline | Advet CRM</title>
    <script src="<?= BASE ?>assets/js/tailwind.min.js"></script>
    <?= getAdminTailwindConfig($settings) ?>
    <link href="<?= BASE ?>assets/css/fonts.css" rel="stylesheet">
    <style>
        body { -webkit-font-smoothing: antialiased; }
        .kanban-column { min-width: 320px; width: 320px; }
        .deal-card { transition: all 0.3s cubic-bezier(0.2, 0.8, 0.2, 1); }
        .deal-card:hover { transform: translateY(-4px); }
        .drag-over { background: rgba(137, 145, 120, 0.1); border: 2px dashed rgba(137, 145, 120, 0.3); border-radius: 2rem; }
    </style>
</head>
<body class="font-sans font-light min-h-screen bg-[#F4F1ED] flex" style="background-color: <?= $settings['theme_surface'] ?? '#F4F1ED' ?>">

<?php include __DIR__ . '/partials/sidebar.php'; ?>

<main class="flex-grow flex flex-col h-screen overflow-hidden">
    <header class="p-8 sm:p-12 pb-6 border-b border-sand/30 flex justify-between items-end bg-background/50 backdrop-blur-md sticky top-0 z-20">
        <div>
            <p class="text-[10px] font-bold uppercase tracking-[0.4em] text-accent mb-4">Revenue Operations</p>
            <h1 class="text-4xl font-serif font-light italic">Sales <span class="text-muted">Pipeline</span></h1>
        </div>
        <div class="flex gap-4">
            <div class="bg-background rounded-2xl px-6 py-3 border border-sand/40 flex items-center gap-6 shadow-sm">
                <div class="text-center">
                    <p class="text-[8px] font-bold uppercase tracking-widest text-muted mb-1">Total Pipeline</p>
                    <p class="text-sm font-serif font-bold text-accent"><?= formatPrice(array_sum(array_column($allDeals, 'deal_value'))) ?></p>
                </div>
                <div class="h-8 w-px bg-sand/20"></div>
                <div class="text-center">
                    <p class="text-[8px] font-bold uppercase tracking-widest text-muted mb-1">Deals</p>
                    <p class="text-sm font-serif font-bold text-accent"><?= count($allDeals) ?></p>
                </div>
            </div>
            <button onclick="toggleModal('dealModal')" class="px-8 py-4 bg-foreground text-background rounded-2xl text-xs font-bold uppercase tracking-widest shadow-xl hover:-translate-y-1 transition-all">
                + New Deal
            </button>
        </div>
    </header>

    <!-- Kanban Board -->
    <div class="flex-grow overflow-x-auto p-8 sm:p-12 pt-10 flex gap-8">
        <?php foreach ($stages as $stage): ?>
            <div class="kanban-column flex flex-col h-full" data-stage-id="<?= $stage['id'] ?>">
                <div class="flex items-center justify-between mb-6 px-2">
                    <div class="flex items-center gap-3">
                        <div class="w-2.5 h-2.5 rounded-full" style="background-color: <?= $stage['color'] ?>"></div>
                        <h3 class="text-xs font-bold uppercase tracking-widest text-foreground"><?= e($stage['name']) ?></h3>
                    </div>
                    <span class="text-[10px] font-bold text-muted bg-sand/30 px-2.5 py-1 rounded-full"><?= count($dealsByStage[$stage['id']]) ?></span>
                </div>

                <div class="flex-grow space-y-5 kanban-dropzone overflow-y-auto pr-2 pb-20 custom-scrollbar" ondragover="event.preventDefault(); this.classList.add('drag-over')" ondragleave="this.classList.remove('drag-over')" ondrop="handleDrop(event, <?= $stage['id'] ?>)">
                    <?php foreach ($dealsByStage[$stage['id']] as $deal): ?>
                        <div class="deal-card bg-background p-6 rounded-[2rem] shadow-sm border border-sand/40 cursor-grab active:cursor-grabbing" draggable="true" ondragstart="handleDragStart(event, <?= $deal['id'] ?>)" onclick="location.href='crm-contact-detail.php?id=<?= $deal['contact_id'] ?>'">
                            <div class="flex justify-between items-start mb-4">
                                <span class="text-[9px] font-bold uppercase tracking-widest text-accent/60"><?= date('M j, Y', strtotime($deal['created_at'])) ?></span>
                                <span class="text-[9px] font-bold text-muted"><?= $deal['probability'] ?>% Prob.</span>
                            </div>
                            
                            <h4 class="text-base font-serif font-medium mb-1 truncate"><?= e($deal['contact_name']) ?></h4>
                            <p class="text-xs text-muted mb-4 font-light italic truncate"><?= $deal['property_id'] ? e($deal['property_title']) : 'Sourcing Sanctuary...' ?></p>
                            
                            <div class="w-full h-px bg-sand/20 mb-4"></div>
                            
                            <div class="flex items-center justify-between">
                                <p class="text-sm font-serif font-bold text-foreground"><?= formatPrice((float)$deal['deal_value']) ?></p>
                                <div class="flex -space-x-2">
                                    <div class="w-6 h-6 rounded-full bg-accent text-[8px] flex items-center justify-center font-bold text-foreground border-2 border-background">
                                        <?= substr($deal['contact_name'], 0, 1) ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</main>

<!-- New Deal Modal -->
<div id="dealModal" class="fixed inset-0 bg-foreground/40 backdrop-blur-sm z-[100] hidden flex items-center justify-center p-6">
    <div class="bg-background w-full max-w-lg rounded-[2.5rem] p-10 shadow-2xl border border-sand/40 max-h-[90vh] overflow-y-auto custom-scrollbar">
        <h3 class="text-xl font-serif italic text-foreground mb-8 text-center">Initiate <span class="text-muted">Deal</span></h3>
        <form action="<?= BASE ?>actions/crm-create-deal.php" method="POST" class="space-y-6">
            
            <div class="space-y-2 relative" id="contact-selector">
                <label class="text-[10px] uppercase tracking-widest font-bold text-muted">Primary Contact (Searchable)</label>
                <input type="hidden" name="contact_id" id="deal_contact_input" required>
                <button type="button" onclick="toggleDropdown('contact-options')" class="w-full flex items-center justify-between px-6 py-4 bg-surface/30 border border-sand/40 rounded-2xl text-[10px] font-bold uppercase tracking-widest focus:outline-none focus:border-accent transition-all text-left">
                    <span id="selected-contact" class="truncate max-w-[200px] text-muted/50 font-normal">Select a lead...</span>
                    <svg class="w-3 h-3 text-muted shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7" /></svg>
                </button>
                <div id="contact-options" class="absolute top-full left-0 right-0 mt-2 bg-background border border-sand/30 rounded-2xl shadow-2xl py-2 hidden z-[101] max-h-48 overflow-y-auto custom-scrollbar">
                    <div class="px-6 py-3 border-b border-sand/10">
                        <input type="text" onkeyup="filterList(this, '.contact-option')" placeholder="Search leads..." class="w-full bg-surface/30 rounded-lg px-3 py-2 text-[10px] focus:outline-none">
                    </div>
                    <?php foreach($allContacts as $c): ?>
                        <div class="px-6 py-3 hover:bg-surface/50 cursor-pointer text-[9px] font-bold uppercase tracking-widest contact-option" onclick="selectItem('contact', <?= $c['id'] ?>, '<?= e($c['name']) ?>')">
                            <?= e($c['name']) ?> <span class="text-[8px] opacity-40 lowercase font-light ml-2"> (<?= e($c['email']) ?>)</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="space-y-2 relative" id="prop-selector">
                <label class="text-[10px] uppercase tracking-widest font-bold text-muted">Link Property</label>
                <input type="hidden" name="property_id" id="deal_prop_input">
                <button type="button" onclick="toggleDropdown('prop-options')" class="w-full flex items-center justify-between px-6 py-4 bg-surface/30 border border-sand/40 rounded-2xl text-[10px] font-bold uppercase tracking-widest focus:outline-none focus:border-accent transition-all text-left">
                    <span id="selected-prop" class="truncate max-w-[200px] text-muted/50 font-normal">None Selected</span>
                    <svg class="w-3 h-3 text-muted shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7" /></svg>
                </button>
                <div id="prop-options" class="absolute top-full left-0 right-0 mt-2 bg-background border border-sand/30 rounded-2xl shadow-2xl py-2 hidden z-[101] max-h-48 overflow-y-auto custom-scrollbar">
                    <div class="px-6 py-3 border-b border-sand/10">
                        <input type="text" onkeyup="filterList(this, '.prop-option')" placeholder="Search properties..." class="w-full bg-surface/30 rounded-lg px-3 py-2 text-[10px] focus:outline-none">
                    </div>
                    <div class="px-6 py-3 hover:bg-surface/50 cursor-pointer text-[9px] font-bold uppercase tracking-widest prop-option" onclick="selectItem('prop', '', 'None Selected')">None Selected</div>
                    <?php foreach($allProps as $p): ?>
                        <div class="px-6 py-3 hover:bg-surface/50 cursor-pointer text-[9px] font-bold uppercase tracking-widest prop-option" onclick="selectItem('prop', <?= $p['id'] ?>, '<?= e($p['title']) ?>')"><?= e($p['title']) ?></div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-6">
                <div class="space-y-2">
                    <label class="text-[10px] uppercase tracking-widest font-bold text-muted">Projected Value</label>
                    <input type="number" name="value" placeholder="Total deal amount" class="w-full px-6 py-4 bg-surface/30 border border-sand/40 rounded-2xl text-xs font-bold focus:outline-none focus:border-accent">
                </div>
                <div class="space-y-2 relative" id="stage-selector">
                    <label class="text-[10px] uppercase tracking-widest font-bold text-muted">Initial Stage</label>
                    <input type="hidden" name="stage_id" id="deal_stage_input" value="<?= $stages[0]['id'] ?? '' ?>">
                    <button type="button" onclick="toggleDropdown('stage-options')" class="w-full flex items-center justify-between px-6 py-4 bg-surface/30 border border-sand/40 rounded-2xl text-[10px] font-bold uppercase tracking-widest focus:outline-none focus:border-accent transition-all text-left">
                        <span id="selected-stage" class="flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full" style="background-color: <?= $stages[0]['color'] ?? '#ccc' ?>"></span>
                            <?= $stages[0]['name'] ?? 'Initial Stage' ?>
                        </span>
                        <svg class="w-3 h-3 text-muted shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7" /></svg>
                    </button>
                    <div id="stage-options" class="absolute top-full left-0 right-0 mt-2 bg-background border border-sand/30 rounded-2xl shadow-2xl py-2 hidden z-[101]">
                        <?php foreach($stages as $s): ?>
                            <div class="px-6 py-3 hover:bg-surface/50 cursor-pointer text-[9px] font-bold uppercase tracking-widest flex items-center gap-3" onclick="selectItem('stage', <?= $s['id'] ?>, '<?= e($s['name']) ?>', '<?= $s['color'] ?>')">
                                <span class="w-2 h-2 rounded-full" style="background-color: <?= $s['color'] ?>"></span>
                                <?= e($s['name']) ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="flex gap-4 pt-6 border-t border-sand/10">
                <button type="button" onclick="toggleModal('dealModal')" class="flex-grow py-4 border border-sand/40 rounded-2xl text-[10px] font-bold uppercase tracking-widest hover:bg-surface transition-all text-muted">Cancel</button>
                <button type="submit" class="flex-grow py-4 bg-foreground text-background rounded-2xl text-[10px] font-bold uppercase tracking-widest shadow-xl hover:-translate-y-1 transition-all">Create Opportunity</button>
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

    function selectItem(type, id, name, extra) {
        const input = document.getElementById(`deal_${type}_input`);
        const label = document.getElementById(`selected-${type}`);
        input.value = id;
        if (type === 'stage') {
            label.innerHTML = `<span class="w-1.5 h-1.5 rounded-full" style="background-color: ${extra}"></span> ${name}`;
        } else {
            label.innerText = name;
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
        if (!event.target.closest('#contact-selector') && !event.target.closest('#prop-selector') && !event.target.closest('#stage-selector')) {
            document.querySelectorAll('[id$="-options"]').forEach(d => d.classList.add('hidden'));
        }
    }

    let draggedId = null;

    function handleDragStart(e, id) {
        draggedId = id;
        e.dataTransfer.setData('text/plain', id);
        e.target.style.opacity = '0.5';
    }

    function handleDrop(e, stageId) {
        e.preventDefault();
        const dropzone = e.target.closest('.kanban-dropzone');
        dropzone.classList.remove('drag-over');
        
        const dealId = draggedId;
        if (!dealId) return;

        // Visual update immediately
        const card = document.querySelector(`.deal-card[onclick*="id=${dealId}"]`);
        if (card) {
            card.style.opacity = '1';
            dropzone.appendChild(card);
        }

        // Server update
        fetch('<?= BASE ?>actions/crm-update-deal-stage.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `id=${dealId}&stage_id=${stageId}`
        }).then(r => r.json()).then(data => {
            if (!data.success) {
                alert('Failed to update stage: ' + data.message);
                location.reload();
            }
        });
    }

    document.querySelectorAll('.deal-card').forEach(card => {
        card.addEventListener('dragend', (e) => {
            e.target.style.opacity = '1';
        });
    });
</script>

</body>
</html>
