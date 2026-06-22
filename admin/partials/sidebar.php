<?php
// FILE: admin/partials/sidebar.php
// Reusable admin sidebar — redesigned for a premium, high-end studio experience.
// Session must already be started before including this file.

$currentScript = basename($_SERVER['SCRIPT_FILENAME']);
$pdo = getPDO();
$settings = loadSettings($pdo);
$siteLogo = $settings['site_logo'] ?? '';
$siteName = $settings['site_name'] ?? 'Advet Studio';

function sidebarLink(string $href, string $label, string $icon, string $current, int $badge = 0): void {
    $isActive = (basename($href) === $current) || ($label === 'Overview' && $current === 'dashboard.php');
    
    $cls = $isActive
        ? 'bg-accent/10 text-accent border-accent/20'
        : 'text-white/40 hover:text-white/80 hover:bg-white/5 border-transparent';

    echo '<a href="' . htmlspecialchars($href) . '" class="group flex items-center justify-between px-4 py-3 rounded-2xl border transition-all duration-300 ' . $cls . '">';
    echo '<div class="flex items-center gap-3.5">';
    echo '<span class="shrink-0 transition-transform duration-300 group-hover:scale-110">' . $icon . '</span>';
    echo '<span class="text-[10px] font-bold uppercase tracking-[0.2em]">' . htmlspecialchars($label) . '</span>';
    echo '</div>';
    
    if ($badge > 0) {
        echo '<span class="bg-accent text-foreground text-[8px] font-bold rounded-full px-2 py-0.5 shadow-lg shadow-accent/20">' . $badge . '</span>';
    } else {
        echo '<svg class="w-3 h-3 opacity-0 group-hover:opacity-100 -translate-x-2 group-hover:translate-x-0 transition-all text-accent" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>';
    }
    echo '</a>';
}

// Hugeicons/Outline format - Standardized icons
$i_dash   = '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" /></svg>';
$i_list   = '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m0-13.5h3.75m-3.75 3h3.75m-3.75 3h3.75m-3.75 3h3.75m-9-12h3.75m-3.75 3h3.75m-3.75 3h3.75m-3.75 3h3.75" /></svg>';
$i_inq    = '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.909A2.25 2.25 0 012.25 6.993V6.75" /></svg>';
$i_users  = '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg>';
$i_star   = '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-1.81.688l1.15 5.424c.123.582-.5.1.043-.225l-4.705-2.88a.563.563 0 00-.594 0l-4.705 2.88c-.5.305-1.116-.143-.996-.726l1.15-5.424a.563.563 0 00-.18-.688l-4.204-3.602c-.38-.325-.178-.948.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z" /></svg>';
$i_cog    = '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>';

// Badges logic
$newCount = 0; $newFeedbackCount = 0;
try {
    $uid = (int)($_SESSION['user']['id'] ?? 0);
    $isAdmin = ($_SESSION['user']['role'] ?? '') === 'admin';
    if ($isAdmin) {
        $newCount = (int)$pdo->query("SELECT COUNT(*) FROM inquiries WHERE status='new'")->fetchColumn();
        $newFeedbackCount = (int)$pdo->query("SELECT COUNT(*) FROM testimonials WHERE status='pending'")->fetchColumn();
    } else {
        $stmt = $pdo->prepare("SELECT COUNT(i.id) FROM inquiries i JOIN properties p ON i.property_id = p.id WHERE i.status='new' AND p.agent_id = ?");
        $stmt->execute([$uid]);
        $newCount = (int)$stmt->fetchColumn();
    }
} catch (\Throwable $e) {}

$userName  = $_SESSION['user']['name'] ?? 'Admin';
$userRole  = $_SESSION['user']['role'] ?? 'member';
$isAdmin   = $userRole === 'admin';
$isAgent   = in_array($userRole, ['admin', 'agent']);
$initials  = strtoupper(substr($userName, 0, 1)) . (strpos($userName, ' ') !== false ? strtoupper(substr(strrchr($userName, ' '), 1, 1)) : '');
?>
<aside class="w-72 flex-shrink-0 bg-[#1A1917] text-[#FDFCF9] flex flex-col hidden lg:flex h-screen sticky top-0 overflow-hidden border-r border-white/5">
    
    <!-- Sidebar Header -->
    <div class="p-10 pb-8 flex flex-col items-center">
        <a href="<?= BASE ?>" class="group flex flex-col items-center gap-4 transition-all duration-500">
            <div class="w-14 h-14 rounded-[1.25rem] bg-accent/10 border border-accent/20 flex items-center justify-center group-hover:scale-110 transition-all shadow-2xl">
                <?php if (!empty($siteLogo)): ?>
                    <img src="<?= imgUrl($siteLogo) ?>" class="w-8 h-8 object-contain" alt="Logo">
                <?php else: ?>
                    <svg class="w-7 h-7 text-accent" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 3l8 7L12 21l-8-11 8-7z"/><path d="M12 3v18"/></svg>
                <?php endif; ?>
            </div>
            <div class="text-center">
                <span class="block text-xs font-serif italic tracking-widest text-white/90"><?= e($siteName) ?></span>
                <span class="block text-[8px] font-bold uppercase tracking-[0.4em] text-accent mt-1 opacity-60">Admin Management</span>
            </div>
        </a>
    </div>

    <!-- Navigation Scroll Area -->
    <nav class="flex-grow px-6 overflow-y-auto custom-scrollbar pb-10 space-y-8">
        
        <!-- Section: Main -->
        <div class="space-y-1">
            <?php 
            sidebarLink(BASE . 'admin/dashboard.php', 'Overview', $i_dash, $currentScript);
            sidebarLink(BASE . 'admin/profile.php',   'My Profile', '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" /></svg>', $currentScript);
            ?>
        </div>

        <!-- Section: Real Estate -->
        <?php if ($isAgent): ?>
        <div class="space-y-1">
            <div class="px-4 mb-3 text-[9px] font-bold uppercase tracking-[0.4em] text-white/20">Real Estate</div>
            <?php 
            sidebarLink(BASE . 'admin/listings.php',      'All Listing',    $i_list,  $currentScript);
            sidebarLink(BASE . 'admin/projects.php',      'Projects',  '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>', $currentScript);
            sidebarLink(BASE . 'admin/add-property.php',  'New Listing',   '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>', $currentScript);
            if ($isAdmin) {
                sidebarLink(BASE . 'admin/featured-commercial.php', 'Featured Commercial',   '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/></svg>', $currentScript);
                sidebarLink(BASE . 'admin/featured-projects.php', 'Featured Project', $i_star, $currentScript);
            }
            ?>
        </div>
        <?php endif; ?>

        <!-- Section: CRM System -->
        <?php if ($isAgent): ?>
        <div class="space-y-1">
            <div class="px-4 mb-3 text-[9px] font-bold uppercase tracking-[0.4em] text-white/20">CRM System</div>
            <?php 
            sidebarLink(BASE . 'admin/crm.php',              'CRM Overview',   '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6a7.5 7.5 0 107.5 7.5h-7.5V6z" /><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5H21A7.5 7.5 0 0013.5 3v7.5z" /></svg>', $currentScript);
            sidebarLink(BASE . 'admin/crm-pipeline.php',     'Sales Pipeline', '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>', $currentScript);
            sidebarLink(BASE . 'admin/inquiries.php',        'Direct Inquiries', $i_inq,   $currentScript, $newCount);
            sidebarLink(BASE . 'admin/crm-leads.php',        'Leads & Archive',   $i_users, $currentScript);
            sidebarLink(BASE . 'admin/crm-contacts.php',     'Contacts',     $i_users, $currentScript);
            sidebarLink(BASE . 'admin/crm-tasks.php',        'Daily Tasks',    '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/></svg>', $currentScript);
            sidebarLink(BASE . 'admin/crm-documents.php',    'Documents',    '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m5.231 13.481L15 17.25m-4.5-15H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9zm3.75 11.625a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg>', $currentScript);
            sidebarLink(BASE . 'admin/crm-transactions.php', 'Transactions',   '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a2.25 2.25 0 00-2.25-2.25H15a3 3 0 11-6 0H5.25A2.25 2.25 0 003 12m18 0v6a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 18v-6m18 0V9M3 12V9m18 0a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 9m18 0V6a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 6v3"/></svg>', $currentScript);
            ?>
        </div>
        <?php endif; ?>

        <!-- Section: Engagement -->
        <?php if ($isAgent): ?>
        <div class="space-y-1">
            <div class="px-4 mb-3 text-[9px] font-bold uppercase tracking-[0.4em] text-white/20">Engagement</div>
            <?php 
            sidebarLink(BASE . 'admin/testimonials.php', 'Testimonials', $i_star, $currentScript, $newFeedbackCount);
            sidebarLink(BASE . 'admin/visitors.php',     'Visitor Logs', '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>', $currentScript);
            ?>
        </div>
        <?php endif; ?>

        <!-- Section: Content -->
        <?php if ($isAdmin): ?>
        <div class="space-y-1">
            <div class="px-4 mb-3 text-[9px] font-bold uppercase tracking-[0.4em] text-white/20">Content</div>
            <?php 
            sidebarLink(BASE . 'admin/stories.php',      'All Stories',  '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" /></svg>', $currentScript);
            sidebarLink(BASE . 'admin/comments.php',     'Story Comments', $i_inq, $currentScript);
            sidebarLink(BASE . 'admin/albums.php',       'Gallery', '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>', $currentScript);
            sidebarLink(BASE . 'admin/faq.php',          'FAQ', '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM3.75 12h.007v.008H3.75V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm-.375 5.25h.007v.008H3.75v-.008zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" /></svg>', $currentScript);
            sidebarLink(BASE . 'admin/team.php',         'Our Team',   $i_users, $currentScript);
            ?>
        </div>
        <?php endif; ?>

        <!-- Section: System -->
        <?php if ($isAdmin): ?>
        <div class="space-y-1">
            <div class="px-4 mb-3 text-[9px] font-bold uppercase tracking-[0.4em] text-white/20">System</div>
            <?php 
            sidebarLink(BASE . 'admin/users.php',        'Agents & Users', '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg>', $currentScript);
            sidebarLink(BASE . 'admin/analytics.php',    'Analytics',    '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>', $currentScript);
            sidebarLink(BASE . 'admin/pages.php',        'Legal Pages',  '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m5.231 13.481L15 17.25m-4.5-15H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9zm3.75 11.625a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg>', $currentScript);
            sidebarLink(BASE . 'admin/settings.php',     'Settings',     $i_cog,  $currentScript);
            sidebarLink(BASE . 'admin/cache-settings.php', 'Cache Management', '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>', $currentScript);
            sidebarLink(BASE . 'admin/ai-settings.php',  'AI Chatbot',   '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a.75.75 0 01-1.074-.765 5.99 5.99 0 011.632-3.78c-1.31-1.308-2.118-3.003-2.118-4.875 0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" /></svg>',  $currentScript);
            sidebarLink(BASE . 'admin/theme.php',        'Theme',        '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.53 16.122a3 3 0 00-5.78 1.128 2.25 2.25 0 01-2.4 2.245 4.5 4.5 0 008.4-2.245c0-.399-.078-.78-.22-1.128zm0 0a15.998 15.998 0 003.388-1.62m-5.043-.025a15.994 15.994 0 011.622-3.395m3.42 3.42a15.995 15.995 0 004.764-4.648l3.813-6.873a.75.75 0 00-1.012-1.011l-6.874 3.813a16.036 16.036 0 00-4.648 4.764m3.42 3.42l-1.5 1.5M10.5 13.5l1.5-1.5" /></svg>', $currentScript);
            sidebarLink(BASE . 'admin/system.php',       'Maintenance',  $i_cog, $currentScript);
            ?>
        </div>
        <?php endif; ?>

    </nav>

    <!-- Sidebar Footer (Dedicated Account Pill) -->
    <div class="p-6">
        <div class="bg-white/5 rounded-3xl p-5 border border-white/5 flex items-center gap-4 group transition-all hover:bg-white/10 hover:border-accent/10">
            <a href="<?= BASE ?>admin/profile.php" class="relative shrink-0">
                <div class="w-12 h-12 rounded-2xl bg-accent flex items-center justify-center font-bold text-sm text-foreground overflow-hidden ring-2 ring-transparent group-hover:ring-accent/30 transition-all shadow-xl">
                    <?php if (!empty($_SESSION['user']['profile_picture'])): ?>
                        <img src="<?= imgUrl($_SESSION['user']['profile_picture']) ?>" alt="Profile" class="w-full h-full object-cover">
                    <?php else: ?>
                        <?= htmlspecialchars($initials) ?>
                    <?php endif; ?>
                </div>
                <div class="absolute -bottom-1 -right-1 w-4 h-4 rounded-full bg-[#1A1917] flex items-center justify-center border-2 border-[#1A1917]">
                    <div class="w-1.5 h-1.5 rounded-full bg-accent animate-pulse"></div>
                </div>
            </a>
            <div class="flex-grow min-w-0">
                <p class="text-[11px] font-bold text-white/90 truncate"><?= htmlspecialchars($userName) ?></p>
                <div class="flex items-center gap-3 mt-1 underline-offset-4">
                    <span class="text-[8px] font-bold uppercase tracking-widest text-accent opacity-60"><?= htmlspecialchars($userRole) ?></span>
                    <a href="<?= BASE ?>auth/logout.php" class="text-[9px] text-white/30 hover:text-red-400 hover:underline transition-all">Sign Out</a>
                </div>
            </div>
            <a href="<?= BASE ?>admin/profile.php" class="p-2 text-white/10 group-hover:text-accent transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>
    </div>

</aside>

<script>
// Uploader initialization script (standard)
document.addEventListener('DOMContentLoaded', () => {
    const obs = new MutationObserver((muts) => {
        muts.forEach(m => m.addedNodes.forEach(node => {
            if (node.nodeType === 1) {
                const inputs = node.querySelectorAll('input[type="file"][accept*="image"]');
                inputs.forEach(input => {
                    if (!input.dataset.uploaderActive) {
                        new AdvetUploader(input);
                    }
                });
            }
        }));
    });
    obs.observe(document.body, { childList: true, subtree: true });
});
</script>

<?php include_once __DIR__ . '/../../includes/chat-widget.php'; ?>
<?php include_once __DIR__ . '/../../includes/upload-sheet.php'; ?>
