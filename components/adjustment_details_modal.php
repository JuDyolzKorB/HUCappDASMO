<!-- Adjustment Details Modal -->
<template x-teleport="body">
<div x-show="showAdjustmentModal" 
     x-cloak
     @keydown.escape.window="showAdjustmentModal = false"
     class="fixed inset-0 z-[9999] overflow-y-auto" 
     aria-labelledby="modal-title" 
     role="dialog" 
     aria-modal="true">
    
    <!-- Background overlay -->
    <div x-show="showAdjustmentModal"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"></div>

    <!-- Modal panel -->
    <div class="flex min-h-full items-center justify-center p-4">
        <div x-show="showAdjustmentModal"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             class="relative transform overflow-hidden rounded-3xl bg-white dark:bg-slate-800 shadow-2xl transition-all w-full max-w-3xl border border-slate-200/60 dark:border-slate-700/60">
            
            <!-- Header -->
            <div class="bg-gradient-to-r from-teal-500 to-teal-600 px-8 py-6">
                <div class="flex items-center justify-between">
                    <h3 class="text-2xl font-bold text-white" x-text="editMode ? 'Edit Adjustment' : 'Adjustment Details'"></h3>
                    <button @click="showAdjustmentModal = false" class="text-white/80 hover:text-white transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Content -->
            <div class="px-8 py-6 max-h-[70vh] overflow-y-auto">
                <template x-if="selectedAdjustment">
                    <div class="space-y-6">
                        <!-- Type Badge -->
                        <div>
                            <span class="px-4 py-2 text-sm font-bold uppercase rounded-full"
                                  :class="selectedAdjustment.Type === 'Return' ? 'bg-blue-50 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400' : 'bg-orange-50 text-orange-600 dark:bg-orange-900/20 dark:text-orange-400'"
                                  x-text="selectedAdjustment.Type"></span>
                        </div>

                        <!-- Details Grid -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Reference -->
                            <div>
                                <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Reference</label>
                                <p class="text-sm font-medium text-slate-700 dark:text-slate-300" x-text="selectedAdjustment.Reference"></p>
                            </div>

                            <!-- Quantity -->
                            <div>
                                <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Quantity</label>
                                <template x-if="!editMode">
                                    <p class="text-sm font-medium text-slate-700 dark:text-slate-300" x-text="selectedAdjustment.Quantity"></p>
                                </template>
                                <template x-if="editMode && selectedAdjustment.Type === 'Disposal'">
                                    <input type="number" x-model="editData.quantity" min="1" 
                                           class="form-input bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-700 text-sm py-2 px-3 rounded-lg w-full">
                                </template>
                            </div>

                            <!-- Date -->
                            <div>
                                <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Date</label>
                                <p class="text-sm font-medium text-slate-700 dark:text-slate-300" x-text="new Date(selectedAdjustment.Date).toLocaleString()"></p>
                            </div>
                        </div>

                        <!-- Reason -->
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Reason</label>
                            <template x-if="!editMode">
                                <p class="text-sm text-slate-600 dark:text-slate-400" x-text="selectedAdjustment.Reason"></p>
                            </template>
                            <template x-if="editMode">
                                <textarea x-model="editData.reason" rows="3"
                                          class="form-input bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-700 text-sm py-2 px-3 rounded-lg w-full resize-none"></textarea>
                            </template>
                        </div>

                        <!-- Photo Display (only for disposals with photos) -->
                        <template x-if="selectedAdjustment.Type === 'Disposal' && selectedAdjustment.PhotoPath">
                            <div>
                                <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-3">Photo Evidence</label>
                                <div class="relative rounded-xl overflow-hidden border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900">
                                    <img :src="selectedAdjustment.PhotoPath" 
                                         :alt="'Photo for ' + selectedAdjustment.Reference"
                                         class="w-full h-auto max-h-96 object-contain"
                                         @error="$el.src = 'data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'400\' height=\'300\'%3E%3Crect fill=\'%23f1f5f9\' width=\'400\' height=\'300\'/%3E%3Ctext fill=\'%2394a3b8\' font-family=\'Arial\' font-size=\'18\' x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dy=\'.3em\'%3EImage not found%3C/text%3E%3C/svg%3E'">
                                </div>
                                <template x-if="editMode">
                                    <div class="mt-3">
                                        <input type="file" x-ref="editPhotoInput" accept="image/*"
                                               class="form-input bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-700 text-sm py-2 px-3 rounded-lg w-full file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-teal-50 file:text-teal-700 hover:file:bg-teal-100">
                                        <p class="text-xs text-slate-400 mt-2">Upload a new photo to replace the current one</p>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>
                </template>
            </div>

            <!-- Footer Actions -->
            <div class="bg-slate-50 dark:bg-slate-900/50 px-8 py-4 flex justify-end gap-3">
                <template x-if="!editMode">
                    <button @click="enterEditMode()" 
                            class="px-6 py-2.5 bg-teal-600 hover:bg-teal-700 text-white rounded-xl font-bold transition-all shadow-lg shadow-teal-900/10">
                        Edit
                    </button>
                </template>
                <template x-if="editMode">
                    <div class="flex gap-3">
                        <button @click="cancelEdit()" 
                                class="px-6 py-2.5 bg-slate-200 hover:bg-slate-300 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-300 rounded-xl font-bold transition-all">
                            Cancel
                        </button>
                        <button @click="saveEdit()" 
                                class="px-6 py-2.5 bg-teal-600 hover:bg-teal-700 text-white rounded-xl font-bold transition-all shadow-lg shadow-teal-900/10">
                            Save Changes
                        </button>
                    </div>
                </template>
                <button @click="showAdjustmentModal = false" 
                        class="px-6 py-2.5 bg-slate-200 hover:bg-slate-300 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-300 rounded-xl font-bold transition-all">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>
</template>
