<?php
function renderPagePlaceholder($pageName) {
?>
<div class="bg-white dark:bg-slate-800 p-8 rounded-xl shadow-lg h-full flex flex-col items-center justify-center text-center border-2 border-dashed border-slate-300 dark:border-slate-700">
    <div class="text-6xl text-slate-400 mb-4">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-24 w-24 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 0 1 6 0Z" />
        </svg>
    </div>
    <h2 class="text-2xl font-bold text-slate-800 dark:text-white"><?php echo htmlspecialchars($pageName); ?></h2>
    <p class="mt-2 text-slate-500 dark:text-slate-400">This page is currently under construction.</p>
    <p class="text-slate-500 dark:text-slate-400">Functionality will be added in a future update.</p>
</div>
<?php
}
?>
