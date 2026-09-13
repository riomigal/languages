// One request at a time, cancelled as soon as the page becomes hidden.
export function visiblePoll(callback, delay) {
    let timer;
    let request;
    let active = false;
    async function tick() {
        if (!active || document.visibilityState !== 'visible' || request) return;
        request = new AbortController();
        try { await callback(request.signal); }
        finally {
            request = null;
            if (active && document.visibilityState === 'visible') timer = setTimeout(tick, delay);
        }
    }
    function stop() {
        active = false;
        clearTimeout(timer);
        request?.abort();
    }
    document.addEventListener('visibilitychange', () => {
        clearTimeout(timer);
        if (document.visibilityState !== 'visible') request?.abort();
        else tick();
    });
    window.addEventListener('pagehide', stop);
    return {
        start() { active = true; clearTimeout(timer); tick(); },
        stop,
    };
}
