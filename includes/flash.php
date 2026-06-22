<?php
// FILE: includes/flash.php
if (!empty($_SESSION['flash'])):
    $f = $_SESSION['flash'];
    unset($_SESSION['flash']);
    $typeClasses = [
        'success' => 'bg-white/95 backdrop-blur-2xl border-accent/20 text-accent shadow-accent/5',
        'error'   => 'bg-white/95 backdrop-blur-2xl border-red-200 text-red-900 shadow-red-500/5',
        'warning' => 'bg-white/95 backdrop-blur-2xl border-amber-200 text-amber-900 shadow-amber-500/5',
        'info'    => 'bg-white/95 backdrop-blur-2xl border-sand text-foreground shadow-black/5',
    ];
    $cls = $typeClasses[$f['type']] ?? $typeClasses['info'];
?>
<div id="flash-toast"
     class="fixed top-6 right-6 z-[9999] max-w-sm w-full px-6 py-4 rounded-2xl border shadow-xl font-sans text-sm font-medium <?= $cls ?> flex items-start gap-3 transition-all duration-300"
     role="alert">
    <svg class="w-5 h-5 shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
        <?php if ($f['type'] === 'success'): ?>
            <path d="M5 13l4 4L19 7"/>
        <?php elseif ($f['type'] === 'error'): ?>
            <path d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        <?php elseif ($f['type'] === 'warning'): ?>
            <path d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
        <?php else: ?>
            <path d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        <?php endif; ?>
    </svg>
    <span><?= htmlspecialchars($f['msg'], ENT_QUOTES, 'UTF-8') ?></span>
    <button onclick="document.getElementById('flash-toast').remove()" class="ml-auto shrink-0 opacity-60 hover:opacity-100 transition-opacity">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
    </button>
</div>
<script>
    setTimeout(function(){ var t = document.getElementById('flash-toast'); if(t){ t.style.opacity='0'; t.style.transform='translateX(20px)'; setTimeout(function(){t.remove();},300); } }, 4000);
</script>
<?php endif; ?>
