<?php
// pages/dpri_import.php
if ($userRole !== 'Administrator' && $userRole !== 'Head Pharmacist') {
    echo '<div class="p-4 bg-red-100 text-red-700 rounded-xl font-bold">Access Denied. Only administrators can import DPRI data.</div>';
    return;
}
?>

<div class="max-w-6xl mx-auto space-y-8" x-data="dpriScanner()">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="space-y-1">
            <h2 class="text-3xl font-extrabold text-slate-800 dark:text-white tracking-tight">DPRI Intelligent Import</h2>
            <p class="text-slate-500 font-medium text-sm">Upload the DOH Drug Price Reference Index PDF to automatically sync items.</p>
        </div>
        <div class="flex items-center gap-3">
             <a href="https://dpri.doh.gov.ph/download" target="_blank" class="px-4 py-2 text-xs font-bold text-slate-600 hover:text-primary transition-colors flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                DOH Download Portal
             </a>
        </div>
    </div>

    <!-- Upload Zone -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-1 space-y-6">
            <div 
                class="relative group cursor-pointer border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-3xl p-10 text-center bg-white dark:bg-slate-800/50 hover:bg-slate-50 dark:hover:bg-slate-800 transition-all duration-300 shadow-sm overflow-hidden"
                @dragover.prevent="dragOver = true"
                @dragleave.prevent="dragOver = false"
                @drop.prevent="handleDrop($event)"
                :class="{'border-primary ring-4 ring-primary/5 bg-primary/5': dragOver}"
            >
                <input type="file" class="hidden" id="pdfUpload" accept="application/pdf" @change="handleFileSelect($event)">
                
                <div class="space-y-4" @click="document.getElementById('pdfUpload').click()">
                    <div class="w-20 h-20 mx-auto bg-slate-100 dark:bg-slate-700 rounded-2xl flex items-center justify-center text-slate-400 group-hover:text-primary group-hover:bg-primary/10 transition-all duration-500 transform group-hover:scale-110 group-hover:rotate-3 shadow-sm">
                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                    </div>
                    <div>
                        <p class="text-sm font-bold text-slate-700 dark:text-slate-200">Drop PDF here</p>
                        <p class="text-[10px] uppercase tracking-widest font-black text-slate-400 mt-1">or click to browse files</p>
                    </div>
                </div>

                <!-- Animated Border Background -->
                <div class="absolute inset-0 border-2 border-primary rounded-3xl transition-opacity duration-300 pointer-events-none opacity-0" :class="{'opacity-100': dragOver}"></div>
            </div>

            <div class="bg-teal-50 dark:bg-teal-900/20 border border-teal-100 dark:border-teal-900/30 rounded-2xl p-5 space-y-3">
                <h4 class="text-xs font-bold text-teal-800 dark:text-teal-400 uppercase tracking-wider flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Scanning Tips
                </h4>
                <p class="text-xs text-teal-700 dark:text-teal-300 leading-relaxed font-medium">
                    The scanner works best with official DOH DPRI booklets. It will attempt to extract medicine names, forms, and dosages.
                </p>
                <ul class="text-[10px] text-teal-600 dark:text-teal-400 font-bold space-y-1">
                    <li>✓ Medicine Category Detection</li>
                    <li>✓ Unit of Measure Extraction</li>
                    <li>✓ Duplicate Prevention</li>
                </ul>
            </div>
        </div>

        <!-- Results / Progress Section -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Progress Overlay -->
            <div x-show="isScanning" x-transition class="bg-white dark:bg-slate-800 rounded-3xl p-8 border border-slate-200 dark:border-slate-700 shadow-xl space-y-6">
                <div class="flex items-center justify-between">
                    <div class="space-y-1">
                        <h4 class="text-lg font-bold text-slate-800 dark:text-white" x-text="'Scanning ' + currentFilename"></h4>
                        <p class="text-xs text-slate-400 font-medium" x-text="'Processing Page ' + scanProgress + '...'"></p>
                    </div>
                    <div class="text-2xl font-black text-primary" x-text="Math.round(totalProgress) + '%'"></div>
                </div>
                <div class="w-full bg-slate-100 dark:bg-slate-700 h-3 rounded-full overflow-hidden">
                    <div class="h-full bg-primary transition-all duration-300" :style="'width: ' + totalProgress + '%'"></div>
                </div>
            </div>

            <!-- Empty State -->
            <div x-show="!isScanning && foundItems.length === 0" class="h-64 flex flex-col items-center justify-center text-slate-300 space-y-4">
                <svg class="w-16 h-16 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                <p class="text-sm font-bold uppercase tracking-widest opacity-40">No items scanned yet</p>
            </div>

            <!-- Scanned Items Table -->
            <div x-show="foundItems.length > 0" x-transition class="bg-white dark:bg-slate-800 rounded-3xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden flex flex-col h-[600px]">
                <div class="p-5 border-b border-slate-100 dark:border-slate-700 bg-white/50 dark:bg-slate-800/50 backdrop-blur-md space-y-3">
                    <div class="flex items-center justify-between gap-3 flex-wrap">
                        <div class="flex items-center gap-3 flex-wrap">
                            <div class="px-3 py-1 bg-primary/10 text-primary rounded-lg text-xs font-black" x-text="foundItems.length + ' Items Detected'"></div>
                            <input type="text" x-model="search" placeholder="Search scanned..." class="text-xs bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-2 w-48 focus:ring-primary/20">
                            <!-- Type Filter -->
                            <select x-model="filterType" class="text-xs bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 font-bold text-slate-600 dark:text-slate-300 focus:ring-primary/20">
                                <option value="">All Types</option>
                                <option value="Medicine">Medicine</option>
                                <option value="Supply">Supply</option>
                                <option value="Equipment">Equipment</option>
                            </select>
                        </div>
                        <div class="flex items-center gap-2">
                             <button @click="clearResults()" class="px-4 py-2 text-xs font-bold text-slate-400 hover:text-red-500 transition-colors">Clear</button>
                             <button @click="confirmImport()" :disabled="isImporting" class="bg-primary hover:bg-primary-hover text-white px-6 py-2 rounded-xl text-xs font-black shadow-lg shadow-teal-900/10 transition-all flex items-center gap-2">
                                <span x-show="!isImporting">Import Selection</span>
                                <span x-show="isImporting" class="animate-spin w-3 h-3 border-2 border-white/30 border-t-white rounded-full"></span>
                             </button>
                        </div>
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto custom-scrollbar">
                    <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-700">
                        <thead class="bg-slate-50/50 dark:bg-slate-900/30 sticky top-0 z-10">
                            <tr>
                                <th class="px-6 py-3 text-left">
                                    <input type="checkbox" @change="toggleAll()" :checked="allSelected" class="rounded border-slate-300 text-primary focus:ring-primary h-4 w-4">
                                </th>
                                <th class="px-6 py-3 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest cursor-pointer select-none hover:text-primary" @click="sortBy('ItemName')">
                                    Medicine/Generic Name
                                    <span x-show="sortField==='ItemName'" x-text="sortDir==='asc' ? ' ↑' : ' ↓'"></span>
                                </th>
                                <th class="px-6 py-3 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest cursor-pointer select-none hover:text-primary" @click="sortBy('ItemType')">
                                    Type
                                    <span x-show="sortField==='ItemType'" x-text="sortDir==='asc' ? ' ↑' : ' ↓'"></span>
                                </th>
                                <th class="px-6 py-3 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Unit</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 dark:divide-slate-800">
                            <template x-for="(item, index) in filteredItems" :key="index">
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/20 transition-colors group">
                                    <td class="px-6 py-4">
                                        <input type="checkbox" x-model="item.selected" class="rounded border-slate-300 text-primary focus:ring-primary h-4 w-4">
                                    </td>
                                    <td class="px-6 py-4">
                                        <input type="text" x-model="item.ItemName" class="text-sm font-semibold bg-transparent border-none p-0 focus:ring-0 text-slate-800 dark:text-slate-200 w-full">
                                    </td>
                                    <td class="px-6 py-4">
                                        <select x-model="item.ItemType" class="text-xs bg-transparent border-none p-0 focus:ring-0 text-blue-500 font-bold uppercase tracking-wider">
                                            <option>Medicine</option>
                                            <option>Supply</option>
                                            <option>Equipment</option>
                                        </select>
                                    </td>
                                    <td class="px-6 py-4">
                                        <input type="text" x-model="item.UnitOfMeasure" class="text-xs font-bold text-slate-400 bg-transparent border-none p-0 focus:ring-0 w-20">
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- pdf.js from CDN -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>

<script>
window.pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

function dpriScanner() {
    return {
        dragOver: false,
        isScanning: false,
        isImporting: false,
        currentFilename: '',
        scanProgress: 0,
        totalProgress: 0,
        foundItems: [],
        search: '',
        filterType: '',
        sortField: 'ItemType',
        sortDir: 'asc',
        allSelected: true,

        get filteredItems() {
            let items = this.foundItems;
            if (this.search) items = items.filter(i => i.ItemName.toLowerCase().includes(this.search.toLowerCase()));
            if (this.filterType) items = items.filter(i => i.ItemType === this.filterType);
            // Sort
            const field = this.sortField;
            const dir = this.sortDir === 'asc' ? 1 : -1;
            return [...items].sort((a, b) => {
                if (a[field] < b[field]) return -1 * dir;
                if (a[field] > b[field]) return 1 * dir;
                return 0;
            });
        },

        sortBy(field) {
            if (this.sortField === field) {
                this.sortDir = this.sortDir === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortField = field;
                this.sortDir = 'asc';
            }
        },

        async handleFileSelect(e) {
            const file = e.target.files[0];
            if (!file) return;
            this.processFile(file);
        },

        async handleDrop(e) {
            this.dragOver = false;
            const file = e.dataTransfer.files[0];
            if (!file || file.type !== 'application/pdf') {
                alert('Please drop a valid PDF file.');
                return;
            }
            this.processFile(file);
        },

        async processFile(file) {
            this.isScanning = true;
            this.currentFilename = file.name;
            this.foundItems = [];
            this.totalProgress = 0;

            const reader = new FileReader();
            reader.onload = async (event) => {
                const typedarray = new Uint8Array(event.target.result);
                try {
                    const pdf = await pdfjsLib.getDocument(typedarray).promise;
                    const numPages = pdf.numPages;

                    for (let i = 1; i <= numPages; i++) {
                        this.scanProgress = i;
                        this.totalProgress = (i / numPages) * 100;

                        const page = await pdf.getPage(i);
                        const textContent = await page.getTextContent();
                        const pageText = textContent.items.map(item => item.str).join(' ');
                        
                        this.parsePageText(pageText);
                    }
                } catch (err) {
                    console.error('PDF Processing Error:', err);
                    alert('Error scanning PDF. Ensure it\'s a standard PDF booklet.');
                } finally {
                    this.isScanning = false;
                    this.deduplicate();
                }
            };
            reader.readAsArrayBuffer(file);
        },

        parsePageText(text) {
            // DOH DPRI Table Logic Strategy:
            // Tables usually look like: [Generic Name] [Form/Strength] [Unit] [Price Range]
            // We'll use a sliding window approach or robust regex.
            
            // Clean common PDF issues
            const cleanText = text.replace(/\s+/g, ' ');

            // REGEX: Look for patterns like "Paracetamol 500mg Tablet"
            // Or specific keywords that denote the start of a drug entry
            // This is a simplified extractor - ideally we would look for price indices too.
            const patterns = [
                // Generic Name + Form + Unit of Measure (Box/Vial/Tablet)
                /([A-Z][A-Za-z0-9\s,\-\/\(\)]+)\s(Tablet|Capsule|Vial|Ampule|Bottle|Sachet|Jar|Can|Tube|Ointment|Cream|Gel|Syrup|Suspension|Drop|Solution|Inhalation|Eardrop|Eyedrop|Infusion|Spray)\s([0-9\w\/]+)/g
            ];

            patterns.forEach(regex => {
                let match;
                while ((match = regex.exec(cleanText)) !== null) {
                    const name = match[1].trim();
                    const form = match[2].trim();
                    const unit = match[3].trim();

                    if (name.length > 3 && name !== 'Generic Name') {
                        this.foundItems.push({
                            ItemName: `${name} ${form}`,
                            ItemType: 'Medicine',
                            UnitOfMeasure: form, // Use form as unit if specific unit isn't found
                            selected: true
                        });
                    }
                }
            });

            // Improved Strategy for DPRI 12th Edition:
            // Look for Uppercase generic names followed by dosage
            const broadRegex = /([A-Z\-\s]{5,})\s*([\d\.mgmlvIU%\d,\s]+)\s*(\w+)/g;
            let broadMatch;
            while ((broadMatch = broadRegex.exec(cleanText)) !== null) {
                const name = broadMatch[1].trim();
                if (name === name.toUpperCase() && name.length > 5 && !['ANNEX', 'DRUG', 'PRICE', 'INDEX', 'METHODOLOGY'].includes(name)) {
                     this.foundItems.push({
                        ItemName: `${name} ${broadMatch[2].trim()}`.replace(/\s+/g, ' '),
                        ItemType: 'Medicine',
                        UnitOfMeasure: broadMatch[3].trim(),
                        selected: true
                    });
                }
            }
        },

        deduplicate() {
            // Remove exact duplicates and clean names
            const seen = new Set();
            this.foundItems = this.foundItems.filter(item => {
                const uniqueKey = item.ItemName.toLowerCase().replace(/\s/g, '');
                if (seen.has(uniqueKey)) return false;
                seen.add(uniqueKey);
                return true;
            });
            // Final sorting
            this.foundItems.sort((a, b) => a.ItemName.localeCompare(b.ItemName));
        },

        toggleAll() {
            this.allSelected = !this.allSelected;
            this.foundItems.forEach(i => i.selected = this.allSelected);
        },

        clearResults() {
            if (confirm('Clear all scanned results?')) {
                this.foundItems = [];
            }
        },

        async confirmImport() {
            const selected = this.foundItems.filter(i => i.selected);
            if (selected.length === 0) {
                alert('No items selected for import.');
                return;
            }

            if (!confirm(`Import ${selected.length} items into your inventory list?`)) return;

            this.isImporting = true;

            try {
                // Send as JSON to avoid PHP max_input_vars limit (default 1000)
                // which silently truncates large lists sent as individual form fields
                const res = await fetch('api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'bulk_import_items',
                        items: selected
                    })
                });
                const data = await res.json();
                if (data.success) {
                    const msg = `✅ Successfully imported ${data.count} items!` + (data.skipped > 0 ? `\n⚠️ ${data.skipped} items were skipped (duplicates or invalid).` : '');
                    alert(msg);
                    this.foundItems = this.foundItems.filter(i => !i.selected);
                    if (this.foundItems.length === 0) {
                        window.location.href = 'index.php?page=inventory';
                    }
                } else {
                    alert('Import failed: ' + (data.message || 'Unknown error'));
                }
            } catch (err) {
                alert('Connection error during import.');
            } finally {
                this.isImporting = false;
            }
        }
    };
}
</script>

<style>
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 10px;
}
.dark .custom-scrollbar::-webkit-scrollbar-thumb {
    background: #334155;
}
@keyframes fade-in {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in {
    animation: fade-in 0.5s ease-out forwards;
}
</style>
