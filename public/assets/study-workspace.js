(function () {
    'use strict';
    const page = document.querySelector('.study-page');
    if (!page || !window.bootstrap?.Offcanvas) return;
    const drawers = new Map(Array.from(page.querySelectorAll('[data-study-drawer]'), element => [element.dataset.studyDrawer, element]));
    const openers = new Map();
    let requested = null;
    let switching = false;
    function showRequested() {
        if (!requested || switching) return;
        const other = Array.from(drawers.values()).find(element => element !== requested && element.matches('.show, .showing, .hiding'));
        if (other) {
            switching = true;
            other.addEventListener('hidden.bs.offcanvas', () => {
                switching = false;
                showRequested();
            }, {once: true});
            bootstrap.Offcanvas.getInstance(other)?.hide();
            return;
        }
        bootstrap.Offcanvas.getOrCreateInstance(requested).show();
    }
    drawers.forEach((element, kind) => {
        element.addEventListener('shown.bs.offcanvas', () => {
            element.querySelector('input:not(:disabled)')?.focus();
        });
        element.addEventListener('hidden.bs.offcanvas', () => {
            if (switching || Array.from(drawers.values()).some(drawer => drawer.matches('.show, .showing'))) return;
            const previous = openers.get(kind);
            const fallback = page.querySelector(`[data-study-open="${kind}"]`);
            (previous?.isConnected && !previous.disabled ? previous : fallback)?.focus({preventScroll: true});
        });
    });
    window.addEventListener('study-editor-opened', event => {
        const element = drawers.get(event.detail?.kind);
        if (!element) return;
        openers.set(event.detail.kind, document.activeElement);
        requested = element;
        showRequested();
    });
    window.addEventListener('study-editor-saved', event => {
        const element = drawers.get(event.detail?.kind);
        if (element) {
            if (requested === element) requested = null;
            bootstrap.Offcanvas.getInstance(element)?.hide();
        }
    });
}());
