<?php
// FILE: public/land-measurement-units.php
session_start();
require_once '../config/db.php';

$pdo = getPDO();
$settings = loadSettings($pdo);

$solidNav  = true;
$pageTitle = 'Land Measurement Units';
$pageDesc  = 'A comprehensive guide to land measurement units and area conversions used in Indian real estate.';

require_once '../includes/header.php';

// Unit categories and their data
$categories = [
    'Standard Units' => [
        ['name' => 'Square Feet', 'symbol' => 'sq ft', 'value' => '1 Sq Ft', 'to_sqft' => 1, 'desc' => 'The most common unit for floor area and flat sizes.'],
        ['name' => 'Square Yard (Gaj)', 'symbol' => 'sq yd', 'value' => '9 Sq Ft', 'to_sqft' => 9, 'desc' => 'Widely used in North India and for plot measurements.'],
        ['name' => 'Square Meter', 'symbol' => 'sq m', 'value' => '10.76 Sq Ft', 'to_sqft' => 10.7639, 'desc' => 'The SI unit for area, used in official government records.'],
        ['name' => 'Acre', 'symbol' => 'ac', 'value' => '43,560 Sq Ft', 'to_sqft' => 43560, 'desc' => 'The standard unit for large agricultural land and industrial plots.'],
        ['name' => 'Hectare', 'symbol' => 'ha', 'value' => '107,639 Sq Ft', 'to_sqft' => 107639, 'desc' => 'Metric unit equal to 10,000 square meters or ~2.47 acres.'],
    ],
    'North India Units' => [
        ['name' => 'Bigha', 'symbol' => 'bigha', 'value' => 'Varies (27,225 Sq Ft in UP)', 'to_sqft' => 27225, 'desc' => 'Commonly used in UP, Punjab, Haryana. Size varies by state.'],
        ['name' => 'Biswa', 'symbol' => 'biswa', 'value' => '1,350 Sq Ft (approx)', 'to_sqft' => 1350, 'desc' => 'Usually 1/20th of a Bigha.'],
        ['name' => 'Kanal', 'symbol' => 'kanal', 'value' => '5,445 Sq Ft', 'to_sqft' => 5445, 'desc' => 'Used primarily in Punjab and Haryana.'],
        ['name' => 'Marla', 'symbol' => 'marla', 'value' => '272.25 Sq Ft', 'to_sqft' => 272.25, 'desc' => 'Commonly used in Punjab and Haryana; 1/20th of a Kanal.'],
    ],
    'South & West India Units' => [
        ['name' => 'Ground', 'symbol' => 'ground', 'value' => '2,400 Sq Ft', 'to_sqft' => 2400, 'desc' => 'The standard unit for residential plots in Tamil Nadu.'],
        ['name' => 'Cent', 'symbol' => 'cent', 'value' => '435.6 Sq Ft', 'to_sqft' => 435.6, 'desc' => 'Commonly used in Kerala and Tamil Nadu; 1/100th of an Acre.'],
        ['name' => 'Guntha', 'symbol' => 'guntha', 'value' => '1,089 Sq Ft', 'to_sqft' => 1089, 'desc' => 'Standard unit in Maharashtra, Karnataka, and Gujarat.'],
        ['name' => 'Ankanam', 'symbol' => 'ankanam', 'value' => '72 Sq Ft', 'to_sqft' => 72, 'desc' => 'Used in parts of Andhra Pradesh and Telangana.'],
    ],
    'East India Units' => [
        ['name' => 'Katha', 'symbol' => 'katha', 'value' => '720 Sq Ft (approx)', 'to_sqft' => 720, 'desc' => 'Widely used in West Bengal, Bihar, and Assam.'],
        ['name' => 'Chatak', 'symbol' => 'chatak', 'value' => '45 Sq Ft', 'to_sqft' => 45, 'desc' => 'Used in West Bengal; 1/16th of a Katha.'],
        ['name' => 'Dhur', 'symbol' => 'dhur', 'value' => '6.25 Sq Ft (approx)', 'to_sqft' => 6.25, 'desc' => 'Smallest unit used in Bihar and Jharkhand.'],
    ]
];
?>
<style>
    .custom-scrollbar::-webkit-scrollbar { width: 4px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #DFD8CC; border-radius: 10px; }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #899178; }
</style>
<?php
?>

<main class="flex-grow pt-40 pb-24 bg-background">
    <!-- Hero Section -->
    <section class="max-w-7xl mx-auto px-6 sm:px-12 lg:px-16 mb-24 reveal">
        <div class="max-w-4xl">
            <p class="text-xs font-bold uppercase tracking-[0.5em] text-accent mb-6">Utilities & Tools</p>
            <h1 class="text-5xl sm:text-7xl font-serif font-light leading-tight mb-8">
                Land Measurement <br><span class="italic text-muted">Units & Conversions</span>
            </h1>
            <p class="text-xl text-muted font-light leading-relaxed max-w-2xl">
                A definitive guide to the diverse land measurement units used across different regions of India, provided by Advet Buildwell.
            </p>
        </div>
    </section>

    <!-- Area Calculator -->
    <section class="max-w-7xl mx-auto px-6 sm:px-12 lg:px-16 mb-32 reveal">
        <div class="bg-surface/30 rounded-[3rem] p-8 md:p-16 border border-sand/30 shadow-sm relative overflow-hidden">
            <!-- Decorative blur -->
            <div class="absolute -top-24 -right-24 w-64 h-64 bg-accent/5 rounded-full blur-3xl"></div>
            
            <div class="relative z-10 grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
                <div>
                    <h2 class="text-3xl font-serif font-light mb-6">Area <span class="italic text-muted">Converter</span></h2>
                    <p class="text-muted font-light mb-12">Quickly convert between different land units used in various Indian states.</p>
                    
                    <div class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <label class="text-[10px] uppercase tracking-widest text-muted font-bold ml-4">Value</label>
                                <input type="number" id="calc-input" value="1" step="any"
                                    class="w-full bg-white border border-sand/40 rounded-full px-6 py-4 focus:border-accent transition-colors text-lg">
                            </div>
                            <div class="space-y-2 relative" id="from-unit-selector">
                                <label class="text-[10px] uppercase tracking-widest text-muted font-bold ml-4">From Unit</label>
                                <input type="hidden" id="calc-from" value="1">
                                <button type="button" onclick="toggleUnitDropdown('from-unit-options')" 
                                    class="w-full flex items-center justify-between bg-white border border-sand/40 rounded-full px-6 py-4 focus:border-accent transition-all text-sm appearance-none cursor-pointer">
                                    <span id="selected-from-label">Square Feet (sq ft)</span>
                                    <svg class="w-4 h-4 text-muted transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </button>
                                <div id="from-unit-options" class="absolute top-full left-0 right-0 mt-2 bg-white border border-sand/30 rounded-3xl shadow-2xl py-4 hidden z-50 max-h-64 overflow-y-auto custom-scrollbar">
                                    <?php foreach ($categories as $cat => $units): ?>
                                        <div class="px-6 py-2 text-[10px] uppercase tracking-widest text-accent font-bold bg-surface/30 mb-2 mt-2 first:mt-0"><?= $cat ?></div>
                                        <?php foreach ($units as $u): ?>
                                            <div class="px-6 py-3 hover:bg-surface/50 cursor-pointer text-sm transition-colors" 
                                                onclick="selectUnit('from', '<?= $u['to_sqft'] ?>', '<?= $u['name'] ?> (<?= $u['symbol'] ?>)')">
                                                <?= $u['name'] ?> <span class="text-xs text-muted ml-1">(<?= $u['symbol'] ?>)</span>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-center py-2">
                            <div class="w-10 h-10 rounded-full bg-accent/10 flex items-center justify-center text-accent">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>
                            </div>
                        </div>

                        <div class="space-y-2 relative" id="to-unit-selector">
                            <label class="text-[10px] uppercase tracking-widest text-muted font-bold ml-4">To Unit</label>
                            <input type="hidden" id="calc-to" value="1">
                            <button type="button" onclick="toggleUnitDropdown('to-unit-options')" 
                                class="w-full flex items-center justify-between bg-white border border-sand/40 rounded-full px-6 py-4 focus:border-accent transition-all text-sm appearance-none cursor-pointer">
                                <span id="selected-to-label">Square Feet (sq ft)</span>
                                <svg class="w-4 h-4 text-muted transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div id="to-unit-options" class="absolute top-full left-0 right-0 mt-2 bg-white border border-sand/30 rounded-3xl shadow-2xl py-4 hidden z-50 max-h-64 overflow-y-auto custom-scrollbar">
                                <?php foreach ($categories as $cat => $units): ?>
                                    <div class="px-6 py-2 text-[10px] uppercase tracking-widest text-accent font-bold bg-surface/30 mb-2 mt-2 first:mt-0"><?= $cat ?></div>
                                    <?php foreach ($units as $u): ?>
                                        <div class="px-6 py-3 hover:bg-surface/50 cursor-pointer text-sm transition-colors" 
                                            onclick="selectUnit('to', '<?= $u['to_sqft'] ?>', '<?= $u['name'] ?> (<?= $u['symbol'] ?>)')">
                                            <?= $u['name'] ?> <span class="text-xs text-muted ml-1">(<?= $u['symbol'] ?>)</span>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="mt-12 p-8 bg-white/50 rounded-3xl border border-accent/20 text-center">
                            <p class="text-xs uppercase tracking-widest text-accent font-bold mb-2">Result</p>
                            <div class="text-4xl md:text-5xl font-serif" id="calc-result">1.00</div>
                            <p class="text-sm text-muted mt-2" id="calc-unit-label">Square Feet</p>
                        </div>
                    </div>
                </div>

                <div class="hidden lg:block relative aspect-square image-soft-clip overflow-hidden">
                    <img src="https://images.unsplash.com/photo-1500382017468-9049fed747ef?auto=format&fit=crop&q=80&w=1000" 
                         alt="Land Landscape" 
                         class="w-full h-full object-cover grayscale opacity-80">
                    <div class="absolute inset-0 bg-accent/10 mix-blend-multiply"></div>
                    <div class="absolute bottom-8 left-8 right-8 bg-white/10 backdrop-blur-md p-6 rounded-2xl border border-white/20">
                        <p class="text-white text-xs uppercase tracking-[0.3em] font-bold mb-2">Measurement Tip</p>
                        <p class="text-white/80 text-sm font-light italic">"Always verify local Bigha and Biswa values as they can vary significantly between states and even districts."</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Units Directory -->
    <section class="max-w-7xl mx-auto px-6 sm:px-12 lg:px-16 mb-32">
        <div class="text-center mb-20 reveal">
            <h2 class="text-4xl md:text-5xl font-serif font-light">Regional <span class="italic text-muted">Directory</span></h2>
            <div class="w-12 h-px bg-sand/60 mx-auto mt-8"></div>
        </div>

        <div class="space-y-24">
            <?php foreach ($categories as $cat => $units): ?>
                <div class="reveal">
                    <div class="flex items-center gap-6 mb-12">
                        <h3 class="text-2xl font-serif italic text-muted shrink-0"><?= $cat ?></h3>
                        <div class="h-px bg-sand/40 w-full"></div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                        <?php foreach ($units as $u): ?>
                            <div class="group p-8 rounded-[2.5rem] bg-surface/20 border border-sand/20 hover:border-accent/40 transition-all duration-500 hover:shadow-xl hover:shadow-accent/5">
                                <div class="w-12 h-12 rounded-2xl bg-white flex items-center justify-center text-accent mb-6 shadow-sm group-hover:bg-accent group-hover:text-white transition-all duration-500">
                                    <span class="text-xs font-bold uppercase tracking-tighter"><?= $u['symbol'] ?></span>
                                </div>
                                <h4 class="text-xl font-serif mb-2 uppercase tracking-tight"><?= $u['name'] ?></h4>
                                <div class="text-accent font-medium text-xs mb-4 uppercase tracking-widest">
                                    <?= $u['value'] ?>
                                </div>
                                <p class="text-muted text-sm font-light leading-relaxed">
                                    <?= $u['desc'] ?>
                                </p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Info Table Section -->
    <section class="max-w-7xl mx-auto px-6 sm:px-12 lg:px-16 mb-32 reveal">
        <div class="bg-foreground text-white rounded-[4rem] p-12 md:p-24 overflow-hidden relative">
            <div class="absolute top-0 right-0 w-1/2 h-full opacity-10 pointer-events-none">
                <svg viewBox="0 0 100 100" class="w-full h-full fill-current"><path d="M0 0 L100 0 L100 100 Z" /></svg>
            </div>
            
            <div class="relative z-10">
                <h2 class="text-4xl md:text-6xl font-serif font-light mb-12 leading-tight">
                    Quick Reference <br><span class="italic opacity-60">Conversion Table.</span>
                </h2>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-white/10 text-[10px] uppercase tracking-[0.4em] text-accent font-bold">
                                <th class="py-6 px-4">Unit Name</th>
                                <th class="py-6 px-4">Square Feet</th>
                                <th class="py-6 px-4">Square Yards</th>
                                <th class="py-6 px-4">Square Meters</th>
                                <th class="py-6 px-4">Acre</th>
                            </tr>
                        </thead>
                        <tbody class="text-white/80 font-light">
                            <?php 
                            $refUnits = [
                                ['Acre', 43560, 4840, 4046.86, 1],
                                ['Bigha (Standard)', 27225, 3025, 2529, 0.625],
                                ['Kanal', 5445, 605, 505.8, 0.125],
                                ['Ground', 2400, 266.67, 222.97, 0.055],
                                ['Cent', 435.6, 48.4, 40.47, 0.01],
                                ['Guntha', 1089, 121, 101.17, 0.025],
                                ['Square Yard', 9, 1, 0.836, 0.0002],
                            ];
                            foreach ($refUnits as $row): ?>
                                <tr class="border-b border-white/5 hover:bg-white/5 transition-colors">
                                    <td class="py-6 px-4 font-serif text-xl text-white"><?= $row[0] ?></td>
                                    <td class="py-6 px-4"><?= is_numeric($row[1]) ? number_format($row[1], 2) : $row[1] ?></td>
                                    <td class="py-6 px-4"><?= is_numeric($row[2]) ? number_format($row[2], 2) : $row[2] ?></td>
                                    <td class="py-6 px-4"><?= is_numeric($row[3]) ? number_format($row[3], 2) : $row[3] ?></td>
                                    <td class="py-6 px-4"><?= is_numeric($row[4]) ? number_format($row[4], 6) : $row[4] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact CTA -->
    <section class="max-w-7xl mx-auto px-6 sm:px-12 lg:px-16 mb-24 reveal text-center">
        <div class="inline-block px-12 py-16 bg-surface/40 rounded-[3rem] border border-sand/40 max-w-2xl">
            <h3 class="text-3xl font-serif font-light mb-6">Need expert <span class="italic text-muted">Guidance?</span></h3>
            <p class="text-muted font-light mb-10">Our advisors can help you with precise land valuations and legal documentation across all Indian regions.</p>
            <a href="<?= BASE ?>contact.php" class="inline-flex items-center gap-4 bg-foreground text-white px-10 py-5 rounded-full hover:bg-accent transition-all transform hover:-translate-y-1">
                <span class="text-xs font-bold uppercase tracking-widest">Consult an Advisor</span>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
            </a>
        </div>
    </section>
</main>

<script>
function toggleUnitDropdown(id) {
    const dropdown = document.getElementById(id);
    const isHidden = dropdown.classList.contains('hidden');
    
    // Close others
    document.querySelectorAll('[id$="-unit-options"]').forEach(d => {
        d.classList.add('hidden');
        d.previousElementSibling.querySelector('svg').classList.remove('rotate-180');
    });
    
    if (isHidden) {
        dropdown.classList.remove('hidden');
        dropdown.previousElementSibling.querySelector('svg').classList.add('rotate-180');
    }
}

function selectUnit(type, value, label) {
    document.getElementById('calc-' + type).value = value;
    document.getElementById('selected-' + type + '-label').textContent = label;
    document.getElementById(type + '-unit-options').classList.add('hidden');
    document.getElementById(type + '-unit-options').previousElementSibling.querySelector('svg').classList.remove('rotate-180');
    calculate();
}

// Global calculate function
function calculate() {
    const input = document.getElementById('calc-input');
    const fromUnitVal = document.getElementById('calc-from').value;
    const toUnitVal = document.getElementById('calc-to').value;
    const resultDisplay = document.getElementById('calc-result');
    const unitLabel = document.getElementById('calc-unit-label');

    const val = parseFloat(input.value) || 0;
    const fromFactor = parseFloat(fromUnitVal);
    const toFactor = parseFloat(toUnitVal);
    
    // Convert fromUnit to SqFt, then to toUnit
    const sqft = val * fromFactor;
    const result = sqft / toFactor;
    
    // Formatting
    let formatted;
    if (result === 0) formatted = "0.00";
    else if (result < 0.0001) formatted = result.toExponential(4);
    else formatted = result.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 4 });
    
    resultDisplay.textContent = formatted;
    
    // Update label (remove the symbol part for the result label)
    const fullLabel = document.getElementById('selected-to-label').textContent;
    unitLabel.textContent = fullLabel.split('(')[0].trim();
}

document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('calc-input');
    input.addEventListener('input', calculate);
    
    // Close on outside click
    window.addEventListener('click', (e) => {
        if (!e.target.closest('#from-unit-selector') && !e.target.closest('#to-unit-selector')) {
            document.querySelectorAll('[id$="-unit-options"]').forEach(d => {
                d.classList.add('hidden');
                d.previousElementSibling.querySelector('svg').classList.remove('rotate-180');
            });
        }
    });

    calculate();
});
</script>

<?php require_once '../includes/footer.php'; ?>
