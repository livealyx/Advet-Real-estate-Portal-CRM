<?php
// FILE: admin/crm-documents.php
session_start();
require_once '../config/db.php';

if (empty($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'manager', 'agent'])) {
    header('Location: ' . BASE . 'auth/login.php'); exit;
}

$pdo = getPDO();
$settings = loadSettings($pdo);
$user = $_SESSION['user'];
$isAdmin = $user['role'] === 'admin';
$isManager = $user['role'] === 'manager';

// Filters
$type   = $_GET['type']   ?? '';
$search = $_GET['search'] ?? '';
$leadId = (int)($_GET['lead_id'] ?? 0);

$where = "WHERE 1=1";
$params = [];

// Permissions: Agents see only docs for their own leads
if (!$isAdmin && !$isManager) {
    $where .= " AND c.assigned_to = ?";
    $params[] = $user['id'];
}

if ($type) {
    $where .= " AND d.doc_type = ?";
    $params[] = $type;
}
if ($search) {
    $where .= " AND (d.title LIKE ? OR c.name LIKE ?)";
    $s = "%$search%";
    $params[] = $s; $params[] = $s;
}
if ($leadId) {
    $where .= " AND d.contact_id = ?";
    $params[] = $leadId;
}

// Fetch Documents
$stmt = $pdo->prepare("
    SELECT d.*, c.name as lead_name, u.name as uploader_name
    FROM crm_documents d
    JOIN crm_contacts c ON c.id = d.contact_id
    LEFT JOIN users u ON u.id = d.uploaded_by
    $where
    ORDER BY d.created_at DESC
");
$stmt->execute($params);
$docs = $stmt->fetchAll();

// Fetch leads for the dropdown
$leadsStmt = $pdo->prepare("SELECT id, name FROM crm_contacts " . ($isAdmin || $isManager ? "" : "WHERE assigned_to = " . $user['id']) . " ORDER BY name ASC");
$leadsStmt->execute();
$leadsList = $leadsStmt->fetchAll();

// Fetch summary counts for widgets
$summaryCounts = $pdo->query("SELECT doc_type, COUNT(*) as cnt FROM crm_documents GROUP BY doc_type")->fetchAll(PDO::FETCH_KEY_PAIR);
$salesAgreementsCount = $summaryCounts['Sales Agreement'] ?? 0;
$kycRecordsCount      = $summaryCounts['KYC Records'] ?? 0;
$paymentReceiptsCount = $summaryCounts['Payment Receipts'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document Center | Advet CRM</title>
    <script src="<?= BASE ?>assets/js/tailwind.min.js"></script>
    <?= getAdminTailwindConfig($settings) ?>
    <link href="<?= BASE ?>assets/css/fonts.css" rel="stylesheet">
    <style>body { -webkit-font-smoothing: antialiased; }</style>
</head>
<body class="font-sans font-light min-h-screen bg-[#F4F1ED] flex" style="background-color: <?= $settings['theme_surface'] ?? '#F4F1ED' ?>">

<?php include __DIR__ . '/partials/sidebar.php'; ?>

<main class="flex-grow p-8 sm:p-12 overflow-y-auto">
    <header class="flex flex-wrap justify-between items-end mb-12 gap-4">
        <div>
            <p class="text-[10px] font-bold uppercase tracking-[0.4em] text-accent mb-4">Central Repository</p>
            <h1 class="text-4xl font-serif font-light italic">Document <span class="text-muted">Center</span></h1>
        </div>
        <div class="flex gap-4">
            <button onclick="toggleModal('uploadModal')" class="px-8 py-4 bg-foreground text-background rounded-2xl text-[10px] font-bold uppercase tracking-widest shadow-xl hover:-translate-y-1 transition-all">+ Upload Document</button>
        </div>
    </header>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-8 mb-12">
        <div class="bg-background p-8 rounded-[2rem] shadow-sm border border-sand/40 flex items-center justify-between group">
            <div>
                <p class="text-[9px] uppercase tracking-widest font-bold text-muted mb-2">Sales Agreements</p>
                <h3 class="text-3xl font-serif text-accent"><?= $salesAgreementsCount ?></h3>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-surface flex items-center justify-center text-muted group-hover:bg-accent group-hover:text-white transition-all">
                <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z"/>
                    <path d="M8 12L11 15L16 9"/>
                    <path d="M12 2V5"/>
                    <path d="M12 19V22"/>
                    <path d="M2 12H5"/>
                    <path d="M19 12H22"/>
                </svg>
            </div>
        </div>
        <div class="bg-background p-8 rounded-[2rem] shadow-sm border border-sand/40 flex items-center justify-between group">
            <div>
                <p class="text-[9px] uppercase tracking-widest font-bold text-muted mb-2">KYC Records</p>
                <h3 class="text-3xl font-serif text-accent"><?= $kycRecordsCount ?></h3>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-surface flex items-center justify-center text-muted group-hover:bg-accent group-hover:text-white transition-all">
                <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 11C13.6569 11 15 9.65685 15 8C15 6.34315 13.6569 5 12 5C10.3431 5 9 6.34315 9 8C9 9.65685 10.3431 11 12 11Z"/>
                    <path d="M19 21C19 17.134 15.866 14 12 14C8.13401 14 5 17.134 5 21"/>
                    <rect x="3" y="3" width="18" height="18" rx="4"/>
                </svg>
            </div>
        </div>
        <div class="bg-background p-8 rounded-[2rem] shadow-sm border border-sand/40 flex items-center justify-between group">
            <div>
                <p class="text-[9px] uppercase tracking-widest font-bold text-muted mb-2">Payment Receipts</p>
                <h3 class="text-3xl font-serif text-accent"><?= $paymentReceiptsCount ?></h3>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-surface flex items-center justify-center text-muted group-hover:bg-accent group-hover:text-white transition-all">
                <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 12H15"/>
                    <path d="M9 16H13"/>
                    <path d="M21 18V5C21 3.89543 20.1046 3 19 3H5C3.89543 3 3 3.89543 3 5V19C3 20.1046 3.89543 21 5 21H17.5M21 18L17.5 21M21 18V21M17.5 21H21"/>
                    <path d="M9 8H15"/>
                </svg>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="mb-8 flex flex-wrap gap-4 items-center">
        <form method="GET" class="flex flex-wrap gap-4 items-center">
            <select name="type" onchange="this.form.submit()" class="px-4 py-3 bg-background border border-sand/40 rounded-xl text-[10px] font-bold uppercase tracking-widest focus:outline-none focus:border-accent">
                <option value="">All Types</option>
                <option value="Sales Agreement" <?= $type==='Sales Agreement' ? 'selected' : '' ?>>Sales Agreements</option>
                <option value="KYC Records" <?= $type==='KYC Records' ? 'selected' : '' ?>>KYC Records</option>
                <option value="Payment Receipts" <?= $type==='Payment Receipts' ? 'selected' : '' ?>>Payment Receipts</option>
            </select>
            <select name="lead_id" onchange="this.form.submit()" class="px-4 py-3 bg-background border border-sand/40 rounded-xl text-[10px] font-bold uppercase tracking-widest focus:outline-none focus:border-accent">
                <option value="0">All Leads</option>
                <?php foreach ($leadsList as $l): ?>
                    <option value="<?= $l['id'] ?>" <?= $leadId==$l['id'] ? 'selected' : '' ?>><?= e($l['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <div class="relative">
                <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search files..." class="px-4 py-3 bg-background border border-sand/40 rounded-xl text-[10px] font-bold focus:outline-none focus:border-accent w-64">
                <button type="submit" class="absolute right-3 top-1/2 -translate-y-1/2 text-muted hover:text-accent"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg></button>
            </div>
            <?php if ($type || $search || $leadId): ?>
                <a href="?" class="text-[9px] font-bold uppercase tracking-widest text-red-400 hover:text-red-500 underline">Clear Filters</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Document List -->
    <div class="bg-background rounded-[2.5rem] shadow-sm border border-sand/40 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-surface/40 border-b border-sand/30">
                    <tr>
                        <th class="px-8 py-5 text-[10px] uppercase tracking-widest font-bold text-muted">File Name</th>
                        <th class="px-8 py-5 text-[10px] uppercase tracking-widest font-bold text-muted">Relationship</th>
                        <th class="px-8 py-5 text-[10px] uppercase tracking-widest font-bold text-muted">Type</th>
                        <th class="px-8 py-5 text-[10px] uppercase tracking-widest font-bold text-muted">Uploaded By</th>
                        <th class="px-8 py-5 text-[10px] uppercase tracking-widest font-bold text-muted">Date</th>
                        <th class="px-8 py-5 text-[10px] uppercase tracking-widest font-bold text-muted text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-sand/20">
                    <?php if (empty($docs)): ?>
                        <tr><td colspan="6" class="px-8 py-20 text-center text-muted italic font-serif">Empty archive. No matching documents found.</td></tr>
                    <?php else: foreach ($docs as $d): ?>
                        <tr class="hover:bg-surface/10 transition-colors group">
                            <td class="px-8 py-6">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 rounded-xl bg-surface flex items-center justify-center text-accent/60">
                                        <?php if (str_ends_with($d['file_path'], '.pdf')): ?>
                                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                                <path d="M9 17H15M9 13H15M9 9H10" stroke-linecap="round" stroke-linejoin="round"/>
                                                <path d="M13 2.5V6C13 7.65685 14.3431 9 16 9H19.5" stroke-linecap="round" stroke-linejoin="round"/>
                                                <path d="M13 2.5H7.5C5.29086 2.5 3.5 4.29086 3.5 6.5V17.5C3.5 19.7091 5.29086 21.5 7.5 21.5H16.5C18.7091 21.5 20.5 19.7091 20.5 17.5V7L16 2.5H13Z" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                        <?php else: ?>
                                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                                <path d="M22 12C22 17.5228 17.5228 22 12 22C6.47715 22 2 17.5228 2 12C2 6.47715 6.47715 2 12 2C17.5228 2 22 6.47715 22 12Z" stroke-linecap="round" stroke-linejoin="round"/>
                                                <path d="M21.5 12.5L18.5 9.5L16.5 11.5L13.5 8.5L8.5 13.5L2.5 13.5" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                        <?php endif; ?>
                                    </div>
                                    <p class="text-sm font-medium text-foreground truncate max-w-[200px]"><?= e($d['title']) ?></p>
                                </div>
                            </td>
                            <td class="px-8 py-6">
                                <a href="crm-contact-detail.php?id=<?= $d['contact_id'] ?>" class="text-[11px] font-serif italic text-muted hover:text-accent"><?= e($d['lead_name']) ?></a>
                            </td>
                            <td class="px-8 py-6">
                                <span class="px-3 py-1 bg-sand/20 rounded-lg text-[9px] font-bold uppercase tracking-widest text-muted"><?= e($d['doc_type']) ?></span>
                            </td>
                            <td class="px-8 py-6 text-[11px] font-light text-muted italic"><?= e($d['uploader_name'] ?: 'System') ?></td>
                            <td class="px-8 py-6 text-[10px] text-muted uppercase font-bold tracking-widest"><?= date('M j, Y', strtotime($d['created_at'])) ?></td>
                            <td class="px-8 py-6 text-right">
                                <div class="flex justify-end gap-3 transition-opacity">
                                    <a href="<?= imgUrl($d['file_path']) ?>" target="_blank" class="p-2.5 bg-background border border-sand/40 rounded-xl text-muted hover:text-accent transition-all shadow-sm" title="View"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg></a>
                                    <a href="<?= imgUrl($d['file_path']) ?>" download class="p-2.5 bg-background border border-sand/40 rounded-xl text-muted hover:text-accent transition-all shadow-sm" title="Download"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" /></svg></a>
                                    <?php if ($isAdmin || $d['uploaded_by'] == $user['id']): ?>
                                        <button onclick="deleteDoc(<?= $d['id'] ?>)" class="p-2.5 bg-background border border-sand/40 rounded-xl text-muted hover:text-red-500 transition-all shadow-sm" title="Delete"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg></button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<!-- Upload Modal -->
<div id="uploadModal" class="fixed inset-0 bg-foreground/40 backdrop-blur-sm z-[100] hidden flex items-center justify-center p-6">
    <div class="bg-background w-full max-w-lg rounded-[2.5rem] p-10 shadow-2xl border border-sand/40">
        <div class="flex justify-between items-center mb-10">
            <h3 class="text-xl font-serif italic text-foreground">Archive <span class="text-muted">New Document</span></h3>
            <button onclick="toggleModal('uploadModal')" class="text-muted hover:text-foreground transition-colors"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12" /></svg></button>
        </div>

        <form id="uploadForm" class="space-y-6">
            <div class="space-y-2">
                <label class="text-[10px] uppercase tracking-widest font-bold text-muted">Internal Name</label>
                <input type="text" name="title" required placeholder="e.g. Sales_Agreement_John" class="w-full px-6 py-4 bg-surface/30 border border-sand/40 rounded-2xl text-xs focus:outline-none focus:border-accent">
            </div>

            <div class="grid grid-cols-2 gap-6">
                <!-- Custom Document Type Selector -->
                <div class="space-y-2 relative" id="type-selector">
                    <label class="text-[10px] uppercase tracking-widest font-bold text-muted">Document Type</label>
                    <input type="hidden" name="doc_type" id="doc_type_input" value="Sales Agreement">
                    <button type="button" onclick="toggleDropdown('type-options')" class="w-full flex items-center justify-between px-6 py-4 bg-surface/30 border border-sand/40 rounded-2xl text-[10px] font-bold uppercase tracking-widest focus:outline-none focus:border-accent transition-all">
                        <span id="selected-type">Sales Agreement</span>
                        <svg class="w-3 h-3 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7" /></svg>
                    </button>
                    <div id="type-options" class="absolute top-full left-0 right-0 mt-2 bg-background border border-sand/30 rounded-2xl shadow-2xl py-2 hidden z-50 overflow-hidden">
                        <div class="option px-6 py-3 hover:bg-surface/50 cursor-pointer text-[9px] font-bold uppercase tracking-widest flex items-center gap-3" onclick="selectType('Sales Agreement')">
                            <div class="w-1.5 h-1.5 rounded-full bg-accent"></div> Sales Agreement
                        </div>
                        <div class="option px-6 py-3 hover:bg-surface/50 cursor-pointer text-[9px] font-bold uppercase tracking-widest flex items-center gap-3" onclick="selectType('KYC Records')">
                            <div class="w-1.5 h-1.5 rounded-full bg-blue-400"></div> KYC Records
                        </div>
                        <div class="option px-6 py-3 hover:bg-surface/50 cursor-pointer text-[9px] font-bold uppercase tracking-widest flex items-center gap-3" onclick="selectType('Payment Receipts')">
                            <div class="w-1.5 h-1.5 rounded-full bg-green-400"></div> Payment Receipts
                        </div>
                    </div>
                </div>

                <!-- Custom Lead Selector -->
                <div class="space-y-2 relative" id="lead-selector">
                    <label class="text-[10px] uppercase tracking-widest font-bold text-muted">Related Lead</label>
                    <input type="hidden" name="contact_id" id="contact_id_input" required>
                    <button type="button" onclick="toggleDropdown('lead-options')" class="w-full flex items-center justify-between px-6 py-4 bg-surface/30 border border-sand/40 rounded-2xl text-[10px] font-bold uppercase tracking-widest focus:outline-none focus:border-accent transition-all">
                        <span id="selected-lead" class="text-muted/60">Select Lead</span>
                        <svg class="w-3 h-3 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7" /></svg>
                    </button>
                    <div id="lead-options" class="absolute top-full left-0 right-0 mt-2 bg-background border border-sand/30 rounded-2xl shadow-2xl py-2 hidden z-50 max-h-48 overflow-y-auto custom-scrollbar">
                        <div class="px-4 py-2 border-b border-sand/10">
                            <input type="text" placeholder="Search leads..." onkeyup="filterLeads(this)" class="w-full px-4 py-2 bg-surface/20 rounded-xl text-[10px] focus:outline-none focus:border-accent">
                        </div>
                        <div id="leads-list">
                            <?php foreach ($leadsList as $l): ?>
                                <div class="lead-option px-6 py-3 hover:bg-surface/50 cursor-pointer text-[9px] font-bold uppercase tracking-widest truncate" data-name="<?= strtolower(e($l['name'])) ?>" onclick="selectLead(<?= $l['id'] ?>, '<?= e($l['name']) ?>')">
                                    <?= e($l['name']) ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-2">
                <label class="text-[10px] uppercase tracking-widest font-bold text-muted">File Archive (PDF, DOCX, JPG - Max 5MB)</label>
                <div id="drop-zone" class="border-2 border-dashed border-sand/40 p-10 rounded-[2rem] text-center hover:border-accent transition-colors cursor-pointer" onclick="document.getElementById('file-input').click()">
                    <svg class="w-8 h-8 text-accent/40 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" /></svg>
                    <p class="text-[10px] font-bold uppercase tracking-widest text-muted" id="file-label">Drag & Drop or Click to Browse</p>
                    <input type="file" name="file" id="file-input" class="hidden" accept=".pdf,.doc,.docx,.jpg,.jpeg">
                </div>
            </div>

            <div class="flex gap-4 pt-6">
                <button type="button" onclick="toggleModal('uploadModal')" class="flex-grow py-4 border border-sand/40 rounded-2xl text-[10px] font-bold uppercase tracking-widest hover:bg-surface transition-all text-muted">Cancel</button>
                <button type="submit" class="flex-grow py-4 bg-foreground text-background rounded-2xl text-[10px] font-bold uppercase tracking-widest shadow-xl hover:-translate-y-1 transition-all">Archieve Now</button>
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
    
    // Close all other custom dropdowns
    document.querySelectorAll('[id$="-options"]').forEach(d => d.classList.add('hidden'));
    
    if (isHidden) dropdown.classList.remove('hidden');
}

function selectType(val) {
    document.getElementById('doc_type_input').value = val;
    document.getElementById('selected-type').innerText = val;
    document.getElementById('type-options').classList.add('hidden');
}

function selectLead(id, name) {
    document.getElementById('contact_id_input').value = id;
    const label = document.getElementById('selected-lead');
    label.innerText = name;
    label.classList.remove('text-muted/60');
    document.getElementById('lead-options').classList.add('hidden');
}

function filterLeads(input) {
    const filter = input.value.toLowerCase();
    const options = document.querySelectorAll('.lead-option');
    options.forEach(opt => {
        const text = opt.getAttribute('data-name');
        opt.style.display = text.includes(filter) ? 'block' : 'none';
    });
}

// Close dropdowns on outside click
window.onclick = function(event) {
    if (!event.target.closest('#type-selector') && !event.target.closest('#lead-selector')) {
        document.querySelectorAll('[id$="-options"]').forEach(d => d.classList.add('hidden'));
    }
}

const fileInput = document.getElementById('file-input');
const fileLabel = document.getElementById('file-label');

fileInput.onchange = () => {
    if (fileInput.files.length > 0) {
        fileLabel.innerText = fileInput.files[0].name;
    }
};

document.getElementById('uploadForm').onsubmit = async (e) => {
    e.preventDefault();
    const btn = e.target.querySelector('button[type="submit"]');
    const oldText = btn.innerText;
    btn.innerText = 'Uploading...';
    btn.disabled = true;

    try {
        const formData = new FormData(e.target);
        const resp = await fetch('<?= BASE ?>actions/crm-upload-doc.php', { method: 'POST', body: formData });
        const data = await resp.json();
        if (data.success) {
            location.reload();
        } else {
            alert(data.message);
            btn.innerText = oldText;
            btn.disabled = false;
        }
    } catch (err) {
        alert('Network error');
        btn.innerText = oldText;
        btn.disabled = false;
    }
};

function deleteDoc(id) {
    if (!confirm('Permanent archive deletion? This cannot be undone.')) return;
    fetch('<?= BASE ?>actions/delete-crm-doc.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `id=${id}`
    }).then(r => r.json()).then(data => {
        if (data.success) location.reload();
        else alert(data.message);
    });
}
</script>

</body>
</html>
