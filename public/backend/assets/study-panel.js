(() => {
    'use strict';
    const escape = value => String(value ?? '').replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll('"', '&quot;').replaceAll("'", '&#039;');
    window.eduanStudyStudentFormatter = (value, row) => `<strong>${escape(value)}</strong><div class="small text-muted text-break">${escape(row.email)}</div>`;
    window.eduanStudyNoteFormatter = value => {
        const text = String(value ?? '');
        return text.length > 180 ? `<details class="study-panel-note"><summary>${escape(text.slice(0, 180))}…</summary><p>${escape(text)}</p></details>` : `<div class="study-panel-note">${escape(text)}</div>`;
    };
    window.eduanStudyEditFormatter = (value, row) => {
        if (!row.can_edit || !Number.isSafeInteger(Number(row.id))) return '—';
        const type = row.table === 'study-notes' ? 'note' : 'entry';
        return `<button type="button" class="btn btn-sm btn-alt-secondary" data-study-edit-${type}="${Number(row.id)}"><i class="fa fa-pen me-1"></i>${escape(document.querySelector('[data-study-panel]')?.dataset.editLabel)}</button>`;
    };
    const page = document.querySelector('[data-study-panel]');
    if (!page) return;
    let opener = null;
    let busy = false;
    function component(type) {
        const root = page.querySelector(`[data-study-component="${type}"]`);
        if (!root || !window.Livewire) throw new Error('Component unavailable');
        return Livewire.find(root.getAttribute('wire:id'));
    }
    function show(kind) {
        const drawer = page.querySelector(`#study-panel-${kind}`);
        if (!drawer) return;
        bootstrap.Offcanvas.getOrCreateInstance(drawer).show();
    }
    page.querySelectorAll('.offcanvas').forEach(drawer => {
        drawer.addEventListener('shown.bs.offcanvas', () => drawer.querySelector('input:not(:disabled), textarea:not(:disabled)')?.focus());
        drawer.addEventListener('hidden.bs.offcanvas', () => {
            const fallback = page.querySelector(`[data-study-panel-open="${drawer.id.replace('study-panel-', '')}"]`);
            (opener?.isConnected ? opener : fallback)?.focus({preventScroll: true});
        });
    });
    page.addEventListener('click', async event => {
        const button = event.target.closest('[data-study-panel-open], [data-study-edit-entry], [data-study-edit-note]');
        if (!button || busy) return;
        busy = true;
        opener = button;
        button.disabled = true;
        const error = page.querySelector('[data-study-action-error]');
        error.classList.add('d-none');
        try {
            if (button.hasAttribute('data-study-edit-entry')) {
                await component('editor').call('editActivity', Number(button.dataset.studyEditEntry));
            } else if (button.hasAttribute('data-study-edit-note')) {
                await component('notes').call('edit', Number(button.dataset.studyEditNote));
            } else if (button.dataset.studyPanelOpen === 'note') {
                await component('notes').call('cancel');
                show('note');
            } else {
                await component('editor').call('openEditor', button.dataset.studyPanelOpen);
            }
        } catch {
            error.textContent = page.dataset.actionError;
            error.classList.remove('d-none');
        } finally {
            button.disabled = false;
            busy = false;
        }
    });
    const refresh = table => window.dispatchEvent(new CustomEvent('refresh-server-table', {detail: {table}}));
    window.addEventListener('study-editor-opened', event => show(event.detail?.kind));
    window.addEventListener('study-note-opened', () => show('note'));
    window.addEventListener('study-editor-saved', event => {
        const drawer = page.querySelector(`#study-panel-${event.detail?.kind}`);
        if (drawer) bootstrap.Offcanvas.getInstance(drawer)?.hide();
        ['study-history', 'study-days', 'study-entries', 'study-topics'].forEach(refresh);
    });
    window.addEventListener('study-note-saved', () => {
        const drawer = page.querySelector('#study-panel-note');
        if (drawer) bootstrap.Offcanvas.getInstance(drawer)?.hide();
        refresh('study-notes');
    });
})();
