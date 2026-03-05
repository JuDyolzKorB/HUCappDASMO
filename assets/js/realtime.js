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
            refreshTimer = setTimeout(() => {
                // Don't auto-refresh if we are on a processing page to avoid interrupted workflows
                const urlParams = new URLSearchParams(window.location.search);
                const page = urlParams.get('page');
                const processingPages = ['process_issuance', 'receive_items'];

                if (processingPages.includes(page)) {
                    console.log('Update detected, but suppressing auto-refresh on processing page.');
                    return;
                }

                console.log('Auto-refreshing page for new updates...');
                window.location.reload();
            }, 1500);
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
