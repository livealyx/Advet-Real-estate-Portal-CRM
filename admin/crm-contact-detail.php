<?php
// FILE: admin/crm-contact-detail.php
session_start();
require_once '../config/db.php';

if (empty($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'agent'])) {
    header('Location: ' . BASE . 'auth/login.php'); exit;
}

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: crm-leads.php'); exit; }

$pdo = getPDO();
$settings = loadSettings($pdo);

// Fetch Contact
$stmt = $pdo->prepare("SELECT c.*, u.name as agent_name FROM crm_contacts c LEFT JOIN users u ON u.id = c.assigned_to WHERE c.id = ?");
$stmt->execute([$id]);
$contact = $stmt->fetch();
if (!$contact) { header('Location: crm-leads.php'); exit; }

// Fetch activities
$stmtAct = $pdo->prepare("SELECT a.*, u.name as agent_name FROM crm_activities a JOIN users u ON u.id = a.agent_id WHERE a.contact_id = ? ORDER BY a.activity_date DESC");
$stmtAct->execute([$id]);
$activities = $stmtAct->fetchAll();

// Fetch Deals
$stmtDeals = $pdo->prepare("SELECT d.*, s.name as stage_name, s.color as stage_color, p.title as property_title FROM crm_deals d JOIN crm_stages s ON s.id = d.stage_id LEFT JOIN properties p ON p.id = d.property_id WHERE d.contact_id = ?");
$stmtDeals->execute([$id]);
$deals = $stmtDeals->fetchAll();

// Fetch Tasks
$stmtTasks = $pdo->prepare("SELECT * FROM crm_tasks WHERE contact_id = ? ORDER BY due_date ASC");
$stmtTasks->execute([$id]);
$tasks = $stmtTasks->fetchAll();

// NEW: Fetch All Stages & Active Properties for modals
$allStages = $pdo->query("SELECT id, name, color FROM crm_stages ORDER BY display_order ASC")->fetchAll();
$allProps = $pdo->query("SELECT id, title FROM properties WHERE status = 'active' ORDER BY title ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($contact['name']) ?> | CRM Details</title>
    <script src="<?= BASE ?>assets/js/tailwind.min.js"></script>
    <?= getAdminTailwindConfig($settings) ?>
    <link href="<?= BASE ?>assets/css/fonts.css" rel="stylesheet">
    <style>body { -webkit-font-smoothing: antialiased; }</style>
</head>
<body class="font-sans font-light min-h-screen bg-[#F4F1ED] flex" style="background-color: <?= $settings['theme_surface'] ?? '#F4F1ED' ?>">

<?php include __DIR__ . '/partials/sidebar.php'; ?>
<?php require_once '../includes/flash.php'; ?>

<main class="flex-grow p-8 sm:p-12 overflow-y-auto">
    <header class="flex flex-wrap justify-between items-end mb-12 gap-4">
        <div>
            <p class="text-[10px] font-bold uppercase tracking-[0.4em] text-accent mb-4 cursor-pointer hover:text-accent-dark transition-colors flex items-center gap-2" onclick="history.back()">
                <svg class="w-3 h-3" stroke="currentColor" stroke-width="2" fill="none" viewBox="0 0 24 24"><path d="M12 3V16M12 16L8 11.6364M12 16L16 11.6364" stroke-linecap="round" stroke-linejoin="round" transform="rotate(90 12 12)"/></svg>
                Back to Leads
            </p>
            <h1 class="text-4xl font-serif font-light italic"><?= e($contact['name']) ?></h1>
            <div class="flex items-center gap-3 mt-4">
                <span class="px-3 py-1 bg-accent/10 border border-accent/20 rounded-full text-[9px] font-bold uppercase tracking-[0.2em] text-accent"><?= e($contact['type']) ?></span>
                <span class="text-muted text-[10px] uppercase font-bold tracking-widest text-muted/50">ID: #<?= $contact['id'] ?></span>
            </div>
        </div>
        <div class="flex gap-4">
            <button onclick="toggleModal('activityModal')" class="px-8 py-4 border border-sand/40 bg-background text-foreground rounded-2xl text-[10px] font-bold uppercase tracking-widest hover:bg-surface transition-all">Log Activity</button>
            <button onclick="toggleModal('editModal')" class="px-8 py-4 bg-foreground text-background rounded-2xl text-[10px] font-bold uppercase tracking-widest shadow-xl hover:-translate-y-1 transition-all">Edit Contact</button>
        </div>
    </header>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-12">
        
        <!-- Sidebar Info -->
        <div class="lg:col-span-4 space-y-8">
            <div class="bg-background rounded-[2.5rem] p-8 border border-sand/40 shadow-sm space-y-8">
                <h3 class="text-[10px] font-bold uppercase tracking-[0.3em] text-accent mb-8 border-b border-sand/30 pb-4">Essential Info</h3>
                
                <div class="space-y-6">
                    <div>
                        <p class="text-[9px] font-bold uppercase tracking-widest text-muted mb-1">Email Base</p>
                        <p class="text-sm font-light text-foreground"><?= e($contact['email'] ?: 'N/A') ?></p>
                    </div>
                    <div>
                        <p class="text-[9px] font-bold uppercase tracking-widest text-muted mb-1">Phone String</p>
                        <p class="text-sm font-light text-foreground"><?= e($contact['phone'] ?: 'N/A') ?></p>
                    </div>
                    <div>
                        <p class="text-[9px] font-bold uppercase tracking-widest text-muted mb-1">Source</p>
                        <p class="text-xs font-bold uppercase text-accent"><?= e($contact['source']) ?></p>
                    </div>
                    <div>
                        <p class="text-[9px] font-bold uppercase tracking-widest text-muted mb-1">Assigned Agent</p>
                        <p class="text-sm font-serif italic text-foreground"><?= e($contact['agent_name'] ?: 'Unassigned') ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-background rounded-[2.5rem] p-8 border border-sand/40 shadow-sm space-y-8">
                <h3 class="text-[10px] font-bold uppercase tracking-[0.3em] text-accent mb-8 border-b border-sand/30 pb-4">Interests & Preferences</h3>
                <div class="space-y-6">
                    <div>
                        <p class="text-[9px] font-bold uppercase tracking-widest text-muted mb-2">Property Interest</p>
                        <div class="flex items-center gap-3">
                            <div class="px-3 py-1 bg-background rounded-lg border border-sand/30 text-[9px] font-bold text-foreground uppercase tracking-wider">
                                <?= e($contact['property_type'] ?: 'Any / Undeclared') ?>
                            </div>
                        </div>
                    </div>
                    <div>
                        <p class="text-[9px] font-bold uppercase tracking-widest text-muted mb-1">Investment Budget</p>
                        <p class="text-base font-serif font-bold text-accent italic"><?= e($contact['budget'] ?: 'Not Specified') ?></p>
                    </div>
                    <div>
                        <p class="text-[9px] font-bold uppercase tracking-widest text-muted mb-1">Orientation / Location</p>
                        <p class="text-sm font-light text-foreground italic"><?= e($contact['preferred_loc'] ?: 'Broad Search') ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Timeline & Deals -->
        <div class="lg:col-span-8 space-y-12">
            
            <!-- Deals Section -->
            <section>
                <div class="flex justify-between items-center mb-8">
                    <h3 class="text-xs font-bold uppercase tracking-[0.3em] text-foreground">Open Deals</h3>
                    <button onclick="toggleModal('dealModal')" class="text-[10px] font-bold text-accent uppercase tracking-widest hover:underline">+ New Deal</button>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <?php if (empty($deals)): ?>
                        <div class="md:col-span-2 p-12 bg-background border border-sand/30 border-dashed rounded-[2rem] text-center italic text-muted font-serif">No active deals found.</div>
                    <?php else: foreach ($deals as $deal): ?>
                        <div class="bg-background p-6 rounded-[2rem] border border-sand/40 shadow-sm">
                            <div class="flex justify-between mb-4">
                                <span class="px-2.5 py-0.5 rounded-full text-[8px] font-bold uppercase tracking-widest" style="background-color: <?= $deal['stage_color'] ?>20; color: <?= $deal['stage_color'] ?>"><?= e($deal['stage_name']) ?></span>
                                <span class="text-sm font-serif font-bold"><?= formatPrice((float)$deal['deal_value']) ?></span>
                            </div>
                            <h4 class="text-base font-serif italic mb-2"><?= $deal['property_id'] ? e($deal['property_title']) : 'Sourcing Sanctuary...' ?></h4>
                            <p class="text-[9px] text-muted uppercase tracking-widest">Added <?= date('M j', strtotime($deal['created_at'])) ?></p>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </section>

            <!-- Documents Section -->
            <section>
                <div class="flex justify-between items-center mb-8">
                    <h3 class="text-xs font-bold uppercase tracking-[0.3em] text-foreground">Client Documents</h3>
                </div>
                <div class="bg-background rounded-[2rem] border border-sand/40 overflow-hidden shadow-sm">
                    <div class="p-8 border-b border-sand/30 flex justify-between items-center">
                        <div>
                            <p class="text-[9px] font-bold uppercase tracking-widest text-muted">KYC, Agreements & Receipts</p>
                        </div>
                        <label for="doc-upload" class="px-5 py-2.5 bg-accent/10 border border-accent/20 rounded-xl text-[9px] font-bold uppercase tracking-widest text-accent hover:bg-accent hover:text-white cursor-pointer transition-all">
                            Upload Doc
                            <input type="file" id="doc-upload" class="hidden" data-uploader-mode="mini" data-contact-id="<?= $id ?>" onchange="uploadCrmDoc(this)">
                        </label>
                    </div>
                    <div class="p-2">
                        <?php 
                        $stmtDocs = $pdo->prepare("SELECT * FROM crm_documents WHERE contact_id = ? ORDER BY created_at DESC");
                        $stmtDocs->execute([$id]);
                        $docs = $stmtDocs->fetchAll();
                        if (empty($docs)): ?>
                            <div class="p-12 text-center text-muted italic font-serif text-sm">No documents uploaded.</div>
                        <?php else: foreach ($docs as $doc): ?>
                            <div class="flex items-center justify-between p-4 hover:bg-surface/30 rounded-2xl transition-all">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 rounded-xl bg-surface flex items-center justify-center text-muted">
                                        <svg class="w-5 h-5" stroke="currentColor" stroke-width="1.5" fill="none" viewBox="0 0 24 24"><path d="M19 19C19 19.5523 18.5523 20 18 20H6C5.44772 20 5 19.5523 5 19V5C5 4.44772 5.44772 4 6 4H13.1716C13.4368 4 13.6911 4.10536 13.8787 4.29289L18.7071 9.12132C18.8946 9.30886 19 9.56321 19 9.82843V19Z" stroke-linejoin="round"/><path d="M13 4V9H19" stroke-linejoin="round"/></svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium"><?= e($doc['title']) ?></p>
                                        <p class="text-[9px] text-muted uppercase tracking-widest"><?= $doc['doc_type'] ?> · <?= date('M j, Y', strtotime($doc['created_at'])) ?></p>
                                    </div>
                                </div>
                                <a href="<?= imgUrl($doc['file_path']) ?>" target="_blank" class="p-2 text-muted hover:text-accent transition-colors">
                                    <svg class="w-5 h-5" stroke="currentColor" stroke-width="1.5" fill="none" viewBox="0 0 24 24"><path d="M12 3V16M12 16L16 11.6364M12 16L8 11.6364" stroke-linecap="round" stroke-linejoin="round"/><path d="M15 21H9C6.17157 21 4.75736 21 3.87868 20.1213C3 19.2426 3 17.8284 3 15M21 15C21 17.8284 21 19.2426 20.1213 20.1213C19.8215 20.4211 19.4594 20.6278 19 20.7398" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </a>
                            </div>
                        <?php endforeach; endif; ?>
                    </div>
                </div>
            </section>

            <!-- Activity Timeline -->
            <section>
                <div class="flex justify-between items-center mb-8">
                    <h3 class="text-xs font-bold uppercase tracking-[0.3em] text-foreground">Interaction Timeline</h3>
                </div>
                <div class="space-y-8 relative before:content-[''] before:absolute before:left-4 before:top-2 before:bottom-2 before:w-px before:bg-sand/30">
                    <?php if (empty($activities)): ?>
                        <div class="pl-12 text-muted italic font-serif">No activities logged yet.</div>
                    <?php else: foreach ($activities as $act): 
                        $type_lower = strtolower($act['activity_type']);
                        $icon = match($type_lower) {
                            'call' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M4.91186 10.5413L7.55229 7.90088C8.09091 7.36227 8.27728 6.56642 8.05944 5.83652C7.8891 5.26577 7.69718 4.57964 7.56961 3.99292C7.45162 3.45027 6.97545 3 6.42012 3H4.91186C3.8012 3 2.88911 3.90384 3.01094 5.0078C3.93709 13.3996 10.6004 20.0629 18.9922 20.9891C20.0962 21.1109 21 20.1988 21 19.0881V17.5799C21 17.0246 20.5479 16.569 20.0015 16.4696C19.3988 16.36 18.7611 16.1804 18.2276 16.0103C17.4611 15.7659 16.6091 15.9377 16.0403 16.5065L13.4587 19.0881" stroke-linecap="round" stroke-linejoin="round"/></svg>',
                            'email', 'mail' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M2 6L8.91302 9.91697C11.4616 11.361 12.5384 11.361 15.087 9.91697L22 6" stroke-linejoin="round"/><path d="M2.01577 13.4756C2.08114 16.5412 2.11383 18.0739 3.24496 19.2094C4.37608 20.3448 5.95033 20.3843 9.09883 20.4634C11.0393 20.5122 12.9607 20.5122 14.9012 20.4634C18.0497 20.3843 19.6239 20.3448 20.7551 19.2094C21.8862 18.0739 21.9189 16.5412 21.9842 13.4756C22.0053 12.4899 22.0053 11.5101 21.9842 10.5244C21.9189 7.45886 21.8862 5.92609 20.7551 4.79066C19.6239 3.65523 18.0497 3.61568 14.9012 3.53657C12.9607 3.48781 11.0393 3.48781 9.09882 3.53656C5.95033 3.61566 4.37608 3.65521 3.24495 4.79065C2.11382 5.92608 2.08114 7.45885 2.01576 10.5244C1.99474 11.5101 1.99475 12.4899 2.01577 13.4756Z" stroke-linejoin="round"/></svg>',
                            'meeting' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M17 16V8C17 5.64298 17 4.46447 16.2678 3.73223C15.5355 3 14.357 3 12 3H8C5.64298 3 4.46447 3 3.73223 3.73223C3 4.46447 3 5.64298 3 8V16C3 18.357 3 19.5355 3.73223 20.2678C4.46447 21 5.64298 21 8 21H12C14.357 21 15.5355 21 16.2678 20.2678C17 19.5355 17 18.357 17 16Z" /><path d="M11 21H17C18.8856 21 19.8284 21 20.4142 20.4142C21 19.8284 21 18.8856 21 17V10C21 8.11438 21 7.17157 20.4142 6.58579C19.8284 6 18.8856 6 17 6" /><path d="M13 11V13" stroke-linecap="round"/></svg>',
                            'note' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M12.8809 7.01656L17.6538 8.28825M11.8578 10.8134L14.2442 11.4492M11.9765 17.9664L12.9311 18.2208C15.631 18.9401 16.981 19.2998 18.0445 18.6893C19.108 18.0787 19.4698 16.7363 20.1932 14.0516L21.2163 10.2548C21.9398 7.57005 22.3015 6.22768 21.6875 5.17016C21.0735 4.11264 19.7235 3.75295 17.0235 3.03358L16.0689 2.77924C13.369 2.05986 12.019 1.70018 10.9555 2.31074C9.89196 2.9213 9.53023 4.26367 8.80678 6.94841L7.78366 10.7452C7.0602 13.4299 6.69848 14.7723 7.3125 15.8298C7.92652 16.8874 9.27651 17.2471 11.9765 17.9664Z" stroke-linecap="round"/><path d="M12 20.9462L11.0477 21.2055C8.35403 21.939 7.00722 22.3057 5.94619 21.6832C4.88517 21.0607 4.52429 19.692 3.80253 16.9546L2.78182 13.0833C2.06006 10.3459 1.69918 8.97718 2.31177 7.89892C2.84167 6.96619 4 7.00015 5.5 7.00003" stroke-linecap="round"/></svg>',
                            default => '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6m-6-8h6M5 19V5a2 2 0 012-2h10a2 2 0 012 2v14l-2-2-2 2-2-2-2 2-2-2-2 2z" stroke-linecap="round" stroke-linejoin="round"/></svg>'
                        };
                    ?>
                        <div class="relative pl-12">
                            <div class="absolute left-0 top-1 w-8 h-8 rounded-full bg-background border border-sand/40 flex items-center justify-center text-accent z-10 shadow-sm">
                                <?= $icon ?>
                            </div>
                            <div class="bg-background p-6 rounded-[1.5rem] border border-sand/20 shadow-sm">
                                <div class="flex justify-between mb-2">
                                    <p class="text-[10px] font-bold uppercase tracking-widest text-accent"><?= e($act['activity_type']) ?></p>
                                    <time class="text-[10px] text-muted"><?= date('M j, Y — g:ia', strtotime($act['activity_date'])) ?></time>
                                </div>
                                <p class="text-sm font-light text-muted leading-relaxed"><?= e($act['details']) ?></p>
                                <div class="mt-4 pt-4 border-t border-sand/10 flex items-center gap-2">
                                    <span class="text-[9px] font-bold text-muted/40 uppercase tracking-widest">Logged by</span>
                                    <span class="text-[10px] font-serif italic text-muted"><?= e($act['agent_name']) ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </section>
        </div>
    </div>
</main>

<script>
function toggleModal(id) {
    document.getElementById(id).classList.toggle('hidden');
}

function toggleDropdown(id) {
    const d = document.getElementById(id);
    const hidden = d.classList.contains('hidden');
    document.querySelectorAll('[id$="-options"]').forEach(x => x.classList.add('hidden'));
    if (hidden) d.classList.remove('hidden');
}

function selectType(val) {
    document.getElementById('act_type_input').value = val;
    document.getElementById('selected-act-type').innerText = val;
    document.getElementById('activity-options').classList.add('hidden');
}

function selectStage(id, name, color) {
    document.getElementById('stage_input').value = id;
    document.getElementById('selected-stage').innerHTML = `<span class="w-1.5 h-1.5 rounded-full" style="background-color: ${color}"></span> ${name}`;
    document.getElementById('stage-options').classList.add('hidden');
}

function selectProp(id, name) {
    document.getElementById('deal_prop_input').value = id;
    document.getElementById('selected-prop').innerText = name;
    document.getElementById('prop-options').classList.add('hidden');
}

function filterProps(input) {
    const f = input.value.toLowerCase();
    document.querySelectorAll('.prop-option').forEach(o => {
        o.style.display = o.innerText.toLowerCase().includes(f) ? 'block' : 'none';
    });
}

function uploadCrmDoc(input) {
    const file = input.files[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('file', file);
    formData.append('contact_id', input.dataset.contactId);
    formData.append('title', file.name);

    fetch('<?= BASE ?>actions/crm-upload-doc.php', {
        method: 'POST',
        body: formData
    }).then(r => r.json()).then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message);
        }
    });
}
</script>

<!-- Log Activity Modal -->
<div id="activityModal" class="fixed inset-0 bg-foreground/40 backdrop-blur-sm z-[100] hidden flex items-center justify-center p-6">
    <div class="bg-background w-full max-w-lg rounded-[2.5rem] p-10 shadow-2xl border border-sand/40">
        <h3 class="text-xl font-serif italic text-foreground mb-8 text-center">Log <span class="text-muted">Activity</span></h3>
        <form action="<?= BASE ?>actions/crm-log-activity.php" method="POST" class="space-y-6">
            <input type="hidden" name="contact_id" value="<?= $id ?>">
            
            <div class="space-y-2 relative" id="act-selector">
                <label class="text-[10px] uppercase tracking-widest font-bold text-muted">Activity Type</label>
                <input type="hidden" name="type" id="act_type_input" value="Note">
                <button type="button" onclick="toggleDropdown('activity-options')" class="w-full flex items-center justify-between px-6 py-4 bg-surface/30 border border-sand/40 rounded-2xl text-[10px] font-bold uppercase tracking-widest focus:outline-none focus:border-accent transition-all">
                    <span id="selected-act-type">Note</span>
                    <svg class="w-3 h-3 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7" /></svg>
                </button>
                <div id="activity-options" class="absolute top-full left-0 right-0 mt-2 bg-background border border-sand/30 rounded-2xl shadow-2xl py-2 hidden z-50">
                    <?php foreach(['Call','Email','Meeting','Note'] as $t): ?>
                        <div class="px-6 py-3 hover:bg-surface/50 cursor-pointer text-[9px] font-bold uppercase tracking-widest" onclick="selectType('<?= $t ?>')"><?= $t ?></div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="space-y-2">
                <label class="text-[10px] uppercase tracking-widest font-bold text-muted">Details</label>
                <textarea name="details" required rows="4" placeholder="Briefly describe the interaction..." class="w-full px-6 py-4 bg-surface/30 border border-sand/40 rounded-2xl text-xs focus:outline-none focus:border-accent resize-none"></textarea>
            </div>

            <div class="flex gap-4 pt-6">
                <button type="button" onclick="toggleModal('activityModal')" class="flex-grow py-4 border border-sand/40 rounded-2xl text-[10px] font-bold uppercase tracking-widest hover:bg-surface transition-all text-muted">Cancel</button>
                <button type="submit" class="flex-grow py-4 bg-foreground text-background rounded-2xl text-[10px] font-bold uppercase tracking-widest shadow-xl hover:-translate-y-1 transition-all">Record Interaction</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Contact Modal -->
<div id="editModal" class="fixed inset-0 bg-foreground/40 backdrop-blur-sm z-[100] hidden flex items-center justify-center p-6">
    <div class="bg-background w-full max-w-xl rounded-[2.5rem] p-10 shadow-2xl border border-sand/40 max-h-[90vh] overflow-y-auto custom-scrollbar">
        <h3 class="text-xl font-serif italic text-foreground mb-8 text-center">Update <span class="text-muted">Profile</span></h3>
        <form action="<?= BASE ?>actions/crm-update-contact.php" method="POST" class="space-y-6">
            <input type="hidden" name="id" value="<?= $id ?>">
            <div class="grid grid-cols-2 gap-6">
                <div class="space-y-2 col-span-2">
                    <label class="text-[10px] uppercase tracking-widest font-bold text-muted">Full Name</label>
                    <input type="text" name="name" value="<?= e($contact['name']) ?>" required class="w-full px-6 py-4 bg-surface/30 border border-sand/40 rounded-2xl text-xs focus:outline-none focus:border-accent">
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] uppercase tracking-widest font-bold text-muted">Email</label>
                    <input type="email" name="email" value="<?= e($contact['email']) ?>" class="w-full px-6 py-4 bg-surface/30 border border-sand/40 rounded-2xl text-xs focus:outline-none focus:border-accent">
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] uppercase tracking-widest font-bold text-muted">Phone</label>
                    <input type="tel" name="phone" value="<?= e($contact['phone']) ?>" class="w-full px-6 py-4 bg-surface/30 border border-sand/40 rounded-2xl text-xs focus:outline-none focus:border-accent">
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] uppercase tracking-widest font-bold text-muted">Property Type Interest</label>
                    <select name="property_type" class="w-full px-6 py-4 bg-surface/30 border border-sand/40 rounded-2xl text-xs focus:outline-none focus:border-accent appearance-none bg-[url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20width%3D%2220%22%20height%3D%2220%22%20viewBox%3D%220%200%2020%2020%22%20fill%3D%22none%22%20xmlns%3D%22http%3A//www.w3.org/2000/svg%22%3E%3Cpath%20d%3D%22M5%207.5L10%2012.5L15%207.5%22%20stroke%3D%22%236D685C%22%20stroke-width%3D%221.5%22%20stroke-linecap%3D%22round%22%20stroke-linejoin%3D%22round%22/%3E%3C/svg%3E')] bg-[length:20px_20px] bg-[right_1.5rem_center] bg-no-repeat">
                        <?php foreach(['Flat / Apartment','Plot / Land','Commercial','Villa','Tenant'] as $pt): ?>
                            <option value="<?= $pt ?>" <?= $contact['property_type'] === $pt ? 'selected' : '' ?>><?= $pt ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] uppercase tracking-widest font-bold text-muted">Budget</label>
                    <input type="text" name="budget" value="<?= e($contact['budget']) ?>" class="w-full px-6 py-4 bg-surface/30 border border-sand/40 rounded-2xl text-xs focus:outline-none focus:border-accent">
                </div>
            </div>
            <div class="flex gap-4 pt-6">
                <button type="button" onclick="toggleModal('editModal')" class="flex-grow py-4 border border-sand/40 rounded-2xl text-[10px] font-bold uppercase tracking-widest hover:bg-surface transition-all text-muted">Cancel</button>
                <button type="submit" class="flex-grow py-4 bg-accent text-background rounded-2xl text-[10px] font-bold uppercase tracking-widest shadow-xl hover:-translate-y-1 transition-all">Update Info</button>
            </div>
        </form>
    </div>
</div>

<!-- New Deal Modal -->
<div id="dealModal" class="fixed inset-0 bg-foreground/40 backdrop-blur-sm z-[100] hidden flex items-center justify-center p-6">
    <div class="bg-background w-full max-w-lg rounded-[2.5rem] p-10 shadow-2xl border border-sand/40">
        <h3 class="text-xl font-serif italic text-foreground mb-8 text-center">Initiate <span class="text-muted">Deal</span></h3>
        <form action="<?= BASE ?>actions/crm-create-deal.php" method="POST" class="space-y-6">
            <input type="hidden" name="contact_id" value="<?= $id ?>">
            
            <div class="space-y-2 relative" id="prop-selector">
                <label class="text-[10px] uppercase tracking-widest font-bold text-muted">Target Property (Optional)</label>
                <input type="hidden" name="property_id" id="deal_prop_input">
                <button type="button" onclick="toggleDropdown('prop-options')" class="w-full flex items-center justify-between px-6 py-4 bg-surface/30 border border-sand/40 rounded-2xl text-[10px] font-bold uppercase tracking-widest focus:outline-none focus:border-accent transition-all">
                    <span id="selected-prop" class="truncate max-w-[200px]">None Selected</span>
                    <svg class="w-3 h-3 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7" /></svg>
                </button>
                <div id="prop-options" class="absolute top-full left-0 right-0 mt-2 bg-background border border-sand/30 rounded-2xl shadow-2xl py-2 hidden z-50 max-h-48 overflow-y-auto custom-scrollbar">
                    <div class="px-6 py-3 border-b border-sand/10">
                        <input type="text" onkeyup="filterProps(this)" placeholder="Search..." class="w-full bg-surface/30 rounded-lg px-3 py-2 text-[10px] focus:outline-none">
                    </div>
                    <div class="px-6 py-3 hover:bg-surface/50 cursor-pointer text-[9px] font-bold uppercase tracking-widest prop-option" onclick="selectProp('', 'None Selected')">None</div>
                    <?php foreach($allProps as $p): ?>
                        <div class="px-6 py-3 hover:bg-surface/50 cursor-pointer text-[9px] font-bold uppercase tracking-widest prop-option" onclick="selectProp(<?= $p['id'] ?>, '<?= e($p['title']) ?>')"><?= e($p['title']) ?></div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-6">
                <div class="space-y-2">
                    <label class="text-[10px] uppercase tracking-widest font-bold text-muted">Deal Value</label>
                    <input type="number" name="value" placeholder="e.g. 5000000" class="w-full px-6 py-4 bg-surface/30 border border-sand/40 rounded-2xl text-xs font-bold focus:outline-none focus:border-accent">
                </div>
                <div class="space-y-2 relative" id="stage-selector">
                    <label class="text-[10px] uppercase tracking-widest font-bold text-muted">Stage</label>
                    <input type="hidden" name="stage_id" id="stage_input" value="<?= $allStages[0]['id'] ?? '' ?>">
                    <button type="button" onclick="toggleDropdown('stage-options')" class="w-full flex items-center justify-between px-6 py-4 bg-surface/30 border border-sand/40 rounded-2xl text-[10px] font-bold uppercase tracking-widest focus:outline-none focus:border-accent transition-all">
                        <span id="selected-stage" class="flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full" style="background-color: <?= $allStages[0]['color'] ?? '#ccc' ?>"></span>
                            <?= $allStages[0]['name'] ?? 'Initial' ?>
                        </span>
                        <svg class="w-3 h-3 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7" /></svg>
                    </button>
                    <div id="stage-options" class="absolute top-full left-0 right-0 mt-2 bg-background border border-sand/30 rounded-2xl shadow-2xl py-2 hidden z-50">
                        <?php foreach($allStages as $s): ?>
                            <div class="px-6 py-3 hover:bg-surface/50 cursor-pointer text-[9px] font-bold uppercase tracking-widest flex items-center gap-3" onclick="selectStage(<?= $s['id'] ?>, '<?= e($s['name']) ?>', '<?= $s['color'] ?>')">
                                <span class="w-2 h-2 rounded-full" style="background-color: <?= $s['color'] ?>"></span>
                                <?= e($s['name']) ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="flex gap-4 pt-6">
                <button type="button" onclick="toggleModal('dealModal')" class="flex-grow py-4 border border-sand/40 rounded-2xl text-[10px] font-bold uppercase tracking-widest hover:bg-surface transition-all text-muted">Cancel</button>
                <button type="submit" class="flex-grow py-4 bg-foreground text-background rounded-2xl text-[10px] font-bold uppercase tracking-widest shadow-xl hover:-translate-y-1 transition-all">Open Deal</button>
            </div>
        </form>
    </div>
</div>

<script>
window.onclick = function(event) {
    if (!event.target.closest('#act-selector') && !event.target.closest('#prop-selector') && !event.target.closest('#stage-selector')) {
        document.querySelectorAll('[id$="-options"]').forEach(d => d.classList.add('hidden'));
    }
}
</script>
</body>
</html>
