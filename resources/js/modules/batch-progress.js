import { json } from './http';
import { toast } from './toast';
import { visiblePoll } from './polling';

export function initBatchProgress() {
    const panel = document.getElementById('batch-progress');
    if (!panel) return;
    let id = panel.dataset.batchId || null;
    let warned = false;
    const poller = visiblePoll(async signal => {
        try {
            const url = new URL(panel.dataset.url, location.href);
            if (id) url.searchParams.set('id', id);
            const batch = await json(url, { signal });
            warned = false;
            panel.querySelector('progress').value = batch.progress;
            panel.querySelector('[data-progress-value]').textContent = batch.progress;
            if (batch.finished) {
                const tracked = id;
                poller.stop();
                id = null;
                panel.hidden = true;
                if (tracked) toast(batch.cancelled ? 'Batch cancelled.' : (batch.failed ? 'Batch finished with failures. Check notifications.' : 'Batch finished. Reload to see changes.'), batch.failed ? 'WARNING' : 'INFO', 6000);
                document.dispatchEvent(new CustomEvent('languages:batch-finished'));
            } else {
                id = batch.id;
                panel.hidden = false;
            }
        } catch (error) {
            if (error.name === 'AbortError') return;
            if ([401, 403, 419].includes(error.status)) poller.stop();
            if (!warned) toast('Batch progress could not be refreshed.', 'WARNING');
            warned = true;
        }
    }, 1000);
    document.addEventListener('languages:batch-start', event => {
        id = event.detail.id;
        if (id) poller.start();
        else { poller.stop(); panel.hidden = true; }
    });
    if (id) poller.start();
}
