<?php
// FILE: public/rent-receipt.php
session_start();
require_once '../config/db.php';

$pdo = getPDO();
$settings = loadSettings($pdo);

$solidNav  = true;
$pageTitle = 'Rent Receipt Generator';
$pageDesc  = 'Generate professional rent receipts for tax saving purposes. Quick, easy, and free tool by Advet Buildwell.';

require_once '../includes/header.php';
?>

<main class="flex-grow pt-40 pb-24 bg-background no-print">
    <!-- Hero Section -->
    <section class="max-w-7xl mx-auto px-6 sm:px-12 lg:px-16 mb-24 reveal">
        <div class="max-w-4xl">
            <p class="text-xs font-bold uppercase tracking-[0.5em] text-accent mb-6">Tools & Utilities</p>
            <h1 class="text-5xl sm:text-7xl font-serif font-light leading-tight mb-8">
                Rent Receipt <br><span class="italic text-muted">Generator</span>
            </h1>
            <p class="text-xl text-muted font-light leading-relaxed max-w-2xl">
                Create valid rent receipts for your HRA claims in seconds. Fill in the details of the tenant and the rent recipient to generate a professional PDF.
            </p>
        </div>
    </section>

    <!-- Generator Interface -->
    <section class="max-w-7xl mx-auto px-6 sm:px-12 lg:px-16 mb-32 reveal">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-16">
            
            <!-- Form Section -->
            <div class="lg:col-span-5 space-y-12">
                <div class="bg-surface/30 rounded-[3rem] p-8 md:p-12 border border-sand/30 shadow-sm">
                    <h3 class="text-xl font-serif mb-8 italic">Receipt <span class="text-muted">Details</span></h3>
                    
                    <form id="receipt-form" class="space-y-8">
                        <!-- Recipient (Landlord) -->
                        <div class="space-y-6">
                            <p class="text-[10px] font-bold uppercase tracking-widest text-accent border-b border-sand/30 pb-2">Landlord / Recipient</p>
                            <div class="space-y-2">
                                <label class="text-[10px] uppercase tracking-widest text-muted font-bold ml-4">Full Name</label>
                                <input type="text" id="landlord_name" placeholder="Name of Landlord" 
                                    class="w-full bg-white border border-sand/40 rounded-2xl px-6 py-4 focus:border-accent transition-colors text-sm">
                            </div>
                            <div class="space-y-2">
                                <label class="text-[10px] uppercase tracking-widest text-muted font-bold ml-4">Landlord PAN (Optional)</label>
                                <input type="text" id="landlord_pan" placeholder="ABCDE1234F" 
                                    class="w-full bg-white border border-sand/40 rounded-2xl px-6 py-4 focus:border-accent transition-colors text-sm uppercase">
                            </div>
                        </div>

                        <!-- Tenant -->
                        <div class="space-y-6">
                            <p class="text-[10px] font-bold uppercase tracking-widest text-accent border-b border-sand/30 pb-2">Tenant Details</p>
                            <div class="space-y-2">
                                <label class="text-[10px] uppercase tracking-widest text-muted font-bold ml-4">Full Name</label>
                                <input type="text" id="tenant_name" placeholder="Name of Tenant" 
                                    class="w-full bg-white border border-sand/40 rounded-2xl px-6 py-4 focus:border-accent transition-colors text-sm">
                            </div>
                            <div class="space-y-2">
                                <label class="text-[10px] uppercase tracking-widest text-muted font-bold ml-4">Property Address</label>
                                <textarea id="property_address" rows="2" placeholder="Full address of the rented property" 
                                    class="w-full bg-white border border-sand/40 rounded-2xl px-6 py-4 focus:border-accent transition-colors text-sm"></textarea>
                            </div>
                        </div>

                        <!-- Payment -->
                        <div class="space-y-6">
                            <p class="text-[10px] font-bold uppercase tracking-widest text-accent border-b border-sand/30 pb-2">Rent & Period</p>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="space-y-2">
                                    <label class="text-[10px] uppercase tracking-widest text-muted font-bold ml-4">Monthly Rent</label>
                                    <input type="number" id="rent_amount" placeholder="e.g. 25000" 
                                        class="w-full bg-white border border-sand/40 rounded-2xl px-6 py-4 focus:border-accent transition-colors text-sm">
                                </div>
                                <div class="space-y-2">
                                    <label class="text-[10px] uppercase tracking-widest text-muted font-bold ml-4">Month/Year</label>
                                    <input type="month" id="rent_month" value="<?= date('Y-m') ?>"
                                        class="w-full bg-white border border-sand/40 rounded-2xl px-6 py-4 focus:border-accent transition-colors text-sm">
                                </div>
                            </div>
                            <div class="space-y-2">
                                <label class="text-[10px] uppercase tracking-widest text-muted font-bold ml-4">Payment Date</label>
                                <input type="date" id="payment_date" value="<?= date('Y-m-d') ?>"
                                    class="w-full bg-white border border-sand/40 rounded-2xl px-6 py-4 focus:border-accent transition-colors text-sm">
                            </div>
                        </div>

                        <button type="button" onclick="window.print()" 
                            class="w-full bg-foreground text-background py-5 rounded-full text-[10px] font-bold uppercase tracking-widest shadow-xl hover:bg-accent transition-all transform hover:-translate-y-1">
                            Print / Save as PDF
                        </button>
                    </form>
                </div>
            </div>

            <!-- Preview Section -->
            <div class="lg:col-span-7">
                <div class="sticky top-40 space-y-8">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-muted ml-4">Live Preview</p>
                    
                    <!-- Digital Receipt Card -->
                    <div id="receipt-preview" class="bg-white rounded-[3rem] shadow-2xl border border-sand/20 overflow-hidden relative aspect-[1/1.4] max-w-lg mx-auto">
                        <!-- Branding -->
                        <div class="bg-surface/50 p-12 flex justify-between items-start border-b border-sand/20">
                            <div>
                                <h4 class="text-2xl font-serif font-bold uppercase tracking-tight"><?= e($siteName) ?></h4>
                                <p class="text-[8px] uppercase tracking-[0.4em] text-accent font-bold">Rent Receipt</p>
                            </div>
                            <div class="text-right">
                                <p class="text-[9px] text-muted uppercase font-bold tracking-widest">Receipt No.</p>
                                <p class="text-xs font-mono">AV-<?= date('Ym') ?>-001</p>
                            </div>
                        </div>

                        <!-- Content -->
                        <div class="p-12 space-y-10">
                            <p class="text-sm text-muted font-light leading-relaxed">
                                Received with thanks from <span class="font-bold text-foreground border-b border-sand/40 pb-0.5 preview-tenant">___________</span>, 
                                a sum of <span class="font-bold text-foreground border-b border-sand/40 pb-0.5 preview-amount">___________</span> 
                                towards the rent of property located at <span class="italic preview-address">___________</span> 
                                for the month of <span class="font-medium preview-month">___________</span>.
                            </p>

                            <div class="grid grid-cols-2 gap-12 pt-8">
                                <div class="space-y-4">
                                    <p class="text-[8px] uppercase tracking-widest text-muted font-bold">Landlord Details</p>
                                    <div class="space-y-1">
                                        <p class="text-sm font-medium preview-landlord">___________</p>
                                        <p class="text-[10px] text-accent font-mono preview-pan">PAN: ___________</p>
                                    </div>
                                </div>
                                <div class="space-y-4 text-right">
                                    <p class="text-[8px] uppercase tracking-widest text-muted font-bold">Payment Info</p>
                                    <div class="space-y-1">
                                        <p class="text-sm font-medium preview-date"><?= date('M j, Y') ?></p>
                                        <p class="text-[10px] text-muted italic">Mode: Online/Cheque</p>
                                    </div>
                                </div>
                            </div>

                            <div class="pt-20 flex justify-between items-end">
                                <div class="w-24 h-24 border border-sand/40 flex items-center justify-center text-[8px] text-sand uppercase tracking-widest text-center px-4 leading-tight">
                                    Revenue Stamp
                                </div>
                                <div class="text-right space-y-2">
                                    <div class="w-40 h-px bg-sand/60 ml-auto"></div>
                                    <p class="text-[9px] uppercase tracking-widest font-bold">Signature of Recipient</p>
                                </div>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="absolute bottom-12 left-12 right-12 flex justify-between items-center text-[8px] text-sand uppercase tracking-widest font-bold">
                            <span>Generated via Advet Buildwell</span>
                            <span><?= date('d/m/Y') ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<!-- Print-only view -->
<div id="print-area" class="hidden print:block p-12 bg-white text-black font-sans leading-relaxed">
    <div class="max-w-3xl mx-auto border-2 border-black p-12">
        <div class="flex justify-between items-start border-b-2 border-black pb-8 mb-8">
            <div>
                <h1 class="text-3xl font-bold uppercase"><?= e($siteName) ?></h1>
                <p class="text-xs tracking-widest font-bold">RENT RECEIPT</p>
            </div>
            <div class="text-right">
                <p class="text-xs font-bold uppercase">Date: <span class="preview-date"><?= date('M j, Y') ?></span></p>
                <p class="text-xs font-bold uppercase">Receipt No: AV-<?= date('Ym') ?>-001</p>
            </div>
        </div>

        <div class="space-y-8 text-base">
            <p>
                Received with thanks from <strong><span class="preview-tenant text-lg uppercase">___________</span></strong>, 
                a sum of <strong><span class="preview-amount-words text-lg capitalize">___________</span></strong> 
                (<strong><span class="preview-amount">___________</span></strong>) 
                vide Cash / Cheque / Online Transfer towards the rent of property situated at 
                <strong><span class="preview-address italic">___________</span></strong> 
                for the month of <strong><span class="preview-month">___________</span></strong>.
            </p>

            <div class="grid grid-cols-2 gap-12 py-8">
                <div class="space-y-4">
                    <p class="text-xs font-bold uppercase border-b border-black inline-block">Landlord / Recipient Details</p>
                    <div>
                        <p class="text-lg font-bold preview-landlord">___________</p>
                        <p class="text-sm preview-pan">PAN: ___________</p>
                    </div>
                </div>
                <div class="flex flex-col items-end justify-between">
                    <div class="w-20 h-20 border border-black flex items-center justify-center text-[10px] text-center px-2">
                        REVENUE STAMP
                    </div>
                    <div class="text-right mt-12">
                        <div class="w-48 h-px bg-black"></div>
                        <p class="text-xs font-bold uppercase mt-2">Signature of Recipient</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="mt-12 pt-8 border-t border-gray-200 text-center text-[10px] text-gray-400 italic">
            This is a computer generated receipt and does not require a physical signature if verified by transaction ID.
        </div>
    </div>
</div>

<style>
@media print {
    .no-print { display: none !important; }
    body { background: white !important; }
    #print-area { display: block !important; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const inputs = ['landlord_name', 'landlord_pan', 'tenant_name', 'property_address', 'rent_amount', 'rent_month', 'payment_date'];
    
    function numberToWords(number) {
        if (isNaN(number) || number === 0) return '___________';
        const words = new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR' }).format(number);
        // Simple word conversion could be added here if needed, but formatted currency is often enough.
        return words;
    }

    function updatePreview() {
        const data = {};
        inputs.forEach(id => {
            data[id] = document.getElementById(id).value || '___________';
        });

        document.querySelectorAll('.preview-landlord').forEach(el => el.textContent = data.landlord_name);
        document.querySelectorAll('.preview-pan').forEach(el => el.textContent = 'PAN: ' + (data.landlord_pan === '___________' ? '___________' : data.landlord_pan.toUpperCase()));
        document.querySelectorAll('.preview-tenant').forEach(el => el.textContent = data.tenant_name);
        document.querySelectorAll('.preview-address').forEach(el => el.textContent = data.property_address);
        
        const amount = parseFloat(data.rent_amount) || 0;
        const formattedAmount = amount ? new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR' }).format(amount) : '___________';
        document.querySelectorAll('.preview-amount').forEach(el => el.textContent = formattedAmount);
        
        // Month formatting
        let monthStr = '___________';
        if (data.rent_month !== '___________') {
            const date = new Date(data.rent_month + '-01');
            monthStr = date.toLocaleString('default', { month: 'long', year: 'numeric' });
        }
        document.querySelectorAll('.preview-month').forEach(el => el.textContent = monthStr);

        // Date formatting
        let dateStr = '___________';
        if (data.payment_date !== '___________') {
            const date = new Date(data.payment_date);
            dateStr = date.toLocaleDateString('default', { day: 'numeric', month: 'short', year: 'numeric' });
        }
        document.querySelectorAll('.preview-date').forEach(el => el.textContent = dateStr);
    }

    inputs.forEach(id => {
        document.getElementById(id).addEventListener('input', updatePreview);
    });

    updatePreview();
});
</script>

<?php require_once '../includes/footer.php'; ?>
