// assets/js/realtime.js
(function () {
    let lastEventId = localStorage.getItem('lastRealtimeId') || 0;
    let eventSource = null;
    let refreshTimer = null;

    function initSSE() {
        if (eventSource) eventSource.close();

        // Include the last ID in the URL for the server to resume
        eventSource = new EventSource(`stream.php?lastId=${lastEventId}`);

        eventSource.addEventListener('connected', function (e) {
            const data = JSON.parse(e.data);
            console.log('Real-time connection established. Last ID:', data.lastId);
            lastEventId = data.lastId;
            localStorage.setItem('lastRealtimeId', lastEventId);
        });

        // Universal update event handler
        const genericHandler = function (e) {
            console.log(`Update received: ${e.type}`, e.data);

            // Persist the last event ID immediately to prevent loops
            if (e.lastEventId) {
                localStorage.setItem('lastRealtimeId', e.lastEventId);
            }

            // Debounce and delay the refresh. 
            // A 1.5s delay allows backend operations and local redirects to finish first.
            if (refreshTimer) clearTimeout(refreshTimer);

            const attemptRefresh = function () {
                // Don't auto-refresh if we are on a processing page to avoid interrupted workflows
                const urlParams = new URLSearchParams(window.location.search);
                const page = urlParams.get('page');
                const processingPages = ['process_issuance', 'receive_items'];

                if (processingPages.includes(page)) {
                    console.log('Update detected, but suppressing auto-refresh on processing page.');
                    return;
                }

                // IMPROVED: Check if any modal is open. 
                // Checks for specific ID patterns, common classes, and backdrop presence
                const modalBackdrop = document.querySelector('.backdrop-blur-sm, .bg-slate-900\\/60, .bg-slate-900\\/75');
                const anyOpenModal = document.querySelector('[id$="Modal"]:not(.hidden), .fixed.inset-0:not(.hidden)');

                let isModalOpen = !!(modalBackdrop || anyOpenModal);

                // Check if user is typing in an input
                const activeElement = document.activeElement;
                const isInputFocused = activeElement && (
                    activeElement.tagName === 'INPUT' ||
                    activeElement.tagName === 'TEXTAREA' ||
                    activeElement.tagName === 'SELECT'
                );

                // Check for "dirty" forms (any input with a value that isn't default)
                // This is a safety catch in case focus was lost
                const inputs = document.querySelectorAll('input:not([type="hidden"]), textarea, select');
                let isUserTyping = false;
                inputs.forEach(input => {
                    if (input.value && input.value !== input.defaultValue && input.type !== 'checkbox' && input.type !== 'radio') {
                        isUserTyping = true;
                    }
                });

                if (isModalOpen || isInputFocused || isUserTyping) {
                    console.log('User is interacting with the system. Deferring refresh for 5 seconds...');
                    // Try again in 5 seconds instead of 2 for less aggressive checking
                    refreshTimer = setTimeout(attemptRefresh, 5000);
                    return;
                }

                console.log('Auto-refreshing page for new updates...');
                window.location.reload();
            };

            refreshTimer = setTimeout(attemptRefresh, 2000); // Increased initial delay to 2s
        };

        // List of events we care about
        const events = [
            'users_updated', 'items_updated', 'inventory_updated',
            'requisitions_updated', 'issuances_updated', 'receivings_updated',
            'procurement_orders_updated', 'warehouses_updated', 'suppliers_updated'
        ];

        events.forEach(eventName => {
            eventSource.addEventListener(eventName, genericHandler);
        });

        eventSource.onerror = function (e) {
            console.error('SSE connection failed. Retrying in 5s...');
            eventSource.close();
            setTimeout(initSSE, 5000);
        };
    }

    // Initialize on load
    if (window.EventSource) {
        initSSE();
    } else {
        console.warn('Browser does not support EventSource. Real-time updates disabled.');
    }

})();
