<?php
// FILE: admin/analytics.php
session_start();
require_once '../config/db.php';
if (empty($_SESSION['user'])) {
    header('Location: ' . BASE . 'auth/login.php'); exit;
}
if ($_SESSION['user']['role'] !== 'admin') {
    header('Location: ' . BASE . 'index.php'); exit;
}

$pdo = getPDO();

// Properties by status
$byStatus = $pdo->query("SELECT status, COUNT(*) as count FROM properties GROUP BY status")->fetchAll();
$statusLabels = json_encode(array_column($byStatus, 'status') ?: ['none']);
$statusData   = json_encode(array_values(array_column($byStatus, 'count') ?: [0]));

// Inquiries per month last 6 months
$byMonth = $pdo->query(
    "SELECT DATE_FORMAT(created_at,'%b') as month, COUNT(*) as count
       FROM inquiries
      WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
      GROUP BY MONTH(created_at)
      ORDER BY created_at"
)->fetchAll();
$monthLabels = json_encode(array_column($byMonth, 'month') ?: ['Jan']);
$monthData   = json_encode(array_values(array_column($byMonth, 'count') ?: [0]));

// Price distribution
$priceBuckets = $pdo->query(
    "SELECT
       CASE WHEN price < 500000   THEN 'Under 500K'
            WHEN price < 1000000  THEN '500K–1M'
            WHEN price < 5000000  THEN '1M–5M'
            ELSE 'Above 5M' END as bucket,
       COUNT(*) as count
     FROM properties GROUP BY bucket"
)->fetchAll();
$priceLabels = json_encode(array_column($priceBuckets, 'bucket') ?: ['N/A']);
$priceData   = json_encode(array_values(array_column($priceBuckets, 'count') ?: [0]));

// Overview numbers
$totalProps    = (int)$pdo->query("SELECT COUNT(*) FROM properties")->fetchColumn();
$totalInq      = (int)$pdo->query("SELECT COUNT(*) FROM inquiries")->fetchColumn();
$totalSubs     = (int)$pdo->query("SELECT COUNT(*) FROM newsletter_subscribers")->fetchColumn();
$avgPrice      = (float)($pdo->query("SELECT AVG(price) FROM properties WHERE status='active'")->fetchColumn() ?: 0);
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics | Advet Studio</title>
    <script src="<?= BASE ?>assets/js/tailwind.min.js"></script>
    <?= getAdminTailwindConfig(isset($settings) ? $settings : []) ?>
    <link href="<?= BASE ?>assets/css/fonts.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>body{-webkit-font-smoothing:antialiased;}</style>
</head>
<body class="font-sans font-light min-h-screen bg-[#F4F1ED] flex" style="background-color: <?= $settings['theme_surface'] ?? '#F4F1ED' ?>">
<?php require_once '../includes/flash.php'; ?>
<?php include __DIR__ . '/partials/sidebar.php'; ?>

<main class="flex-grow p-8 sm:p-12 overflow-y-auto">
    <header class="mb-12">
        <p class="text-[10px] font-bold uppercase tracking-[0.4em] text-accent mb-4">Studio Intelligence</p>
        <h1 class="text-4xl font-serif font-light italic">Market <span class="text-muted">Analytics</span></h1>
    </header>

    <!-- KPI Cards -->
    <div class="grid grid-cols-2 xl:grid-cols-4 gap-6 mb-12">
        <?php foreach ([
            ['Properties',         $totalProps,             ''],
            ['Total Inquiries',    $totalInq,               ''],
            ['Newsletter Subs',    $totalSubs,              ''],
            ['Avg Active Price',   formatPrice($avgPrice),  ''],
        ] as $kpi): ?>
        <div class="bg-background p-8 rounded-[2.5rem] shadow-sm border border-sand/40">
            <p class="text-[10px] uppercase tracking-widest font-bold text-muted mb-4"><?= $kpi[0] ?></p>
            <h3 class="text-4xl font-serif"><?= is_numeric($kpi[1]) ? $kpi[1] : $kpi[1] ?></h3>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Charts -->
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-8 mb-8">

        <!-- Inquiries Bar Chart -->
        <div class="bg-background p-10 rounded-[2.5rem] shadow-sm border border-sand/40">
            <h3 class="text-xs uppercase tracking-[0.3em] font-bold text-foreground mb-2">Inquiry Volume</h3>
            <p class="text-[10px] text-muted uppercase tracking-widest mb-8">Monthly · Last 6 Months</p>
            <div class="h-[280px]">
                <canvas id="inqChart"></canvas>
            </div>
        </div>

        <!-- Status Doughnut -->
        <div class="bg-background p-10 rounded-[2.5rem] shadow-sm border border-sand/40">
            <h3 class="text-xs uppercase tracking-[0.3em] font-bold text-foreground mb-2">Listings by Status</h3>
            <p class="text-[10px] text-muted uppercase tracking-widest mb-8">Current Breakdown</p>
            <div class="h-[280px] flex items-center justify-center">
                <canvas id="statusChart"></canvas>
            </div>
        </div>

        <!-- Price Distribution Line Chart -->
        <div class="bg-background p-10 rounded-[2.5rem] shadow-sm border border-sand/40 xl:col-span-2">
            <h3 class="text-xs uppercase tracking-[0.3em] font-bold text-foreground mb-2">Price Distribution</h3>
            <p class="text-[10px] text-muted uppercase tracking-widest mb-8">Properties per Price Tier</p>
            <div class="h-[280px]">
                <canvas id="priceChart"></canvas>
            </div>
        </div>
    </div>
</main>

<script>
const chartDefaults = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
        tooltip: {
            backgroundColor: '#2A2925',
            titleFont: { family:'DM Sans', size:10 },
            bodyFont:  { family:'DM Sans', size:12 },
            padding: 12, displayColors: false
        }
    }
};

// Inquiry Bar
new Chart(document.getElementById('inqChart').getContext('2d'), {
    type: 'bar',
    data: {
        labels: <?= $monthLabels ?>,
        datasets: [{ label:'Inquiries', data: <?= $monthData ?>, backgroundColor:'#899178', hoverBackgroundColor:'#6E755F', borderRadius:10, borderSkipped:false, barThickness:32 }]
    },
    options: { ...chartDefaults, scales: {
        y: { beginAtZero:true, grid:{color:'rgba(223,216,204,0.3)'}, ticks:{color:'#6D685C',font:{family:'DM Sans',size:10}} },
        x: { grid:{display:false}, ticks:{color:'#6D685C',font:{family:'DM Sans',size:10}} }
    }}
});

// Status Doughnut
new Chart(document.getElementById('statusChart').getContext('2d'), {
    type: 'doughnut',
    data: {
        labels: <?= $statusLabels ?>,
        datasets: [{ data: <?= $statusData ?>, backgroundColor:['#899178','#DFD8CC','#2A2925'], borderWidth:0, hoverOffset:6 }]
    },
    options: { ...chartDefaults, plugins:{ ...chartDefaults.plugins, legend:{ display:true, position:'bottom', labels:{color:'#6D685C',font:{family:'DM Sans',size:10},padding:16} } }, cutout:'70%' }
});

// Price Line
new Chart(document.getElementById('priceChart').getContext('2d'), {
    type: 'line',
    data: {
        labels: <?= $priceLabels ?>,
        datasets: [{ label:'Properties', data: <?= $priceData ?>, borderColor:'#899178', backgroundColor:'rgba(137,145,120,0.1)', borderWidth:2, pointBackgroundColor:'#899178', pointRadius:6, fill:true, tension:0.4 }]
    },
    options: { ...chartDefaults, scales: {
        y: { beginAtZero:true, grid:{color:'rgba(223,216,204,0.3)'}, ticks:{color:'#6D685C',font:{family:'DM Sans',size:10}} },
        x: { grid:{display:false}, ticks:{color:'#6D685C',font:{family:'DM Sans',size:10}} }
    }}
});
</script>
</body>
</html>
