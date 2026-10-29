(() => {
    'use strict';
    const escape = value => String(value ?? '').replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll('"', '&quot;').replaceAll("'", '&#039;');
    window.eduanStudyStudentFormatter = (value, row) => `<strong>${escape(value)}</strong><div class="small text-muted text-break">${escape(row.email)}</div>`;
    window.eduanStudyGoalFormatter = (value, row) => Object.entries(row.goals || {}).map(([label, amount]) => `<div class="small d-flex justify-content-between gap-3"><span class="text-muted">${escape(label)}</span><strong>${escape(amount ?? '—')}</strong></div>`).join('');
    window.eduanStudyStateFormatter = (value, row) => {
        const tone = {no_target:'secondary', no_activity:'secondary', zero_activity:'warning', low:'warning', on_track:'success'}[row.state_key] || 'secondary';
        return `<span class="badge bg-${tone}-light text-${tone} text-wrap">${escape(value)}</span>`;
    };
    const weekPicker = () => document.querySelectorAll('[data-study-week]').forEach(input => {
        if (input._flatpickr || !window.flatpickr) return;
        const startOfWeek = date => {
            const start = new Date(date.getFullYear(), date.getMonth(), date.getDate());
            start.setDate(start.getDate() - (start.getDay() + 1) % 7);
            return start;
        };
        const initial = new URL(window.location.href).searchParams.get('week') || input.value;
        const update = picker => {
            const date = picker.selectedDates[0];
            if (!date) return;
            const start = startOfWeek(date), end = new Date(start);
            end.setDate(end.getDate() + 6);
            picker.setDate(start, false);
            picker.altInput.value = picker.formatDate(start, 'j F Y') + ' — ' + picker.formatDate(end, 'j F Y');
        };
        flatpickr(input, {
            locale: input.dataset.locale, disableMobile: true, allowInput: false,
            dateFormat: 'Y-m-d', altInput: true, altFormat: 'study-week-range', maxDate: 'today',
            formatDate: (date, format, locale) => {
                if (format !== 'study-week-range') return flatpickr.formatDate(date, format, locale);
                const start = startOfWeek(date), end = new Date(start); end.setDate(end.getDate() + 6);
                return flatpickr.formatDate(start, 'j F Y', locale) + ' — ' + flatpickr.formatDate(end, 'j F Y', locale);
            },
            defaultDate: startOfWeek(flatpickr.parseDate(initial, 'Y-m-d') || new Date()),
            onReady: (_, __, picker) => {
                picker.altInput.id = input.id + '-display';
                picker.altInput.setAttribute('aria-describedby', input.getAttribute('aria-describedby') || '');
                document.querySelector(`label[for="${input.id}"]`)?.setAttribute('for', picker.altInput.id);
                update(picker);
            },
            onChange: (_, __, picker) => {
                if (!picker.selectedDates.length) picker.setDate(startOfWeek(new Date()), false);
                update(picker);
            }
        });
        // Keep direct links and the shared table's initial filter state on the same week.
        const url = new URL(window.location.href);
        if (url.searchParams.has('week')) {url.searchParams.set('week', input.value); window.history.replaceState(null, '', url);}
        input.closest('form')?.addEventListener('reset', () => setTimeout(() => update(input._flatpickr), 0));
    });
    document.addEventListener('DOMContentLoaded', weekPicker, {once:true});
    if (document.readyState !== 'loading') weekPicker();
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
