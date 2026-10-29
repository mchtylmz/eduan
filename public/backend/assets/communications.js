(() => {
    const selected = new Set();
    const bound = new WeakSet();
    let morphBound = false;
    let selectionControls = null;
    const boot = () => {
        const root = document.querySelector('[data-communication-form]');
        const element = root?.querySelector('#communication-recipients-table');
        if (!element || bound.has(element)) return;
        bound.add(element);
        selected.clear();
        JSON.parse(root.dataset.selectedRecipients || '[]').forEach(id => selected.add(Number(id)));
        const table = jQuery(element);
        const sync = () => {
            const component = Livewire.find(root.getAttribute('wire:id'));
            if (component) component.set('recipients', [...selected], false);
            const count = root.querySelector('[data-selection-count]');
            if (count) count.textContent = count.dataset.template.replace(':count', String(selected.size));
        };
        selectionControls = async button => {
            const component = Livewire.find(root.getAttribute('wire:id'));
            if (button.hasAttribute('data-clear-selection')) {
                selected.clear();
                table.bootstrapTable('uncheckAll');
                sync();
                return;
            }
            button.disabled = true;
            root.querySelector('[data-selection-error-box]')?.classList.add('d-none');
            try {
                const filters = JSON.parse(element.closest('[data-server-table-page]').dataset.tableParameters || '{}');
                await component.call('selectFilteredRecipients', filters);
            } finally { if (button.isConnected) button.disabled = false; }
        };
        table.on('check.bs.table', (_, row) => {selected.add(Number(row.id));sync();});
        table.on('uncheck.bs.table', (_, row) => {selected.delete(Number(row.id));sync();});
        table.on('check-all.bs.table', (_, rows) => {rows.forEach(row => selected.add(Number(row.id)));sync();});
        table.on('uncheck-all.bs.table', () => {table.bootstrapTable('getData').forEach(row => selected.delete(Number(row.id)));sync();});
        table.on('load-success.bs.table', () => {if(selected.size)table.bootstrapTable('checkBy',{field:'id',values:[...selected]});});
    };
    window.addEventListener('communication-selection-updated', event => {
        selected.clear();
        (event.detail.ids || []).forEach(id => selected.add(Number(id)));
        const element = document.getElementById('communication-recipients-table');
        if (element) jQuery(element).bootstrapTable('checkBy', {field:'id', values:[...selected]});
        const count = document.querySelector('[data-selection-count]');
        if (count) count.textContent = count.dataset.template.replace(':count', String(selected.size));
    });
    document.addEventListener('click', event => {
        const button = event.target.closest('[data-select-filtered], [data-clear-selection]');
        if (button && !button.disabled && selectionControls) selectionControls(button).catch(() => {
            const root = document.querySelector('[data-communication-form]');
            const error = root?.querySelector('[data-selection-error-box]');
            if (error) {error.textContent = root.dataset.selectionError; error.classList.remove('d-none');}
        });
    });
    window.addEventListener('communication-created', () => {
        selected.clear();
        const history=document.getElementById('communication-history-table');
        if(history && jQuery(history).data('bootstrap.table'))jQuery(history).bootstrapTable('resetView');
    });
    const focusStep = () => document.querySelector('[data-communication-form]')?.scrollIntoView({block:'start',behavior:'auto'});
    window.addEventListener('communication-compose-started', () => {selected.clear();focusStep();});
    window.addEventListener('communication-step-changed', focusStep);
    const bindMorph = () => {if(!morphBound && window.Livewire){morphBound=true;window.Livewire.hook('morphed', boot);}};
    document.addEventListener('livewire:init', bindMorph, {once:true});
    bindMorph();
    document.addEventListener('DOMContentLoaded', boot, {once:true});
    document.addEventListener('livewire:navigated', boot);
    if(document.readyState!=='loading')boot();
})();
