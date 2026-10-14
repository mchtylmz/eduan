document.addEventListener('DOMContentLoaded', () => {
    const root = document.querySelector('[data-communication-form]');
    if (!root) return;
    const table = jQuery('#communication-recipients-table');
    const selected = new Set();
    const sync = () => {
        const component = Livewire.find(root.getAttribute('wire:id'));
        if (component) component.set('recipients', [...selected], false);
        const count=root.querySelector('[data-selection-count]');
        if(count)count.textContent=count.dataset.template.replace(':count',String(selected.size));
    };
    table.on('check.bs.table', (_, row) => {selected.add(Number(row.id));sync();});
    table.on('uncheck.bs.table', (_, row) => {selected.delete(Number(row.id));sync();});
    table.on('check-all.bs.table', (_, rows) => {rows.forEach(row => selected.add(Number(row.id)));sync();});
    table.on('uncheck-all.bs.table', () => {table.bootstrapTable('getData').forEach(row => selected.delete(Number(row.id)));sync();});
    table.on('load-success.bs.table', () => {if(selected.size)table.bootstrapTable('checkBy',{field:'id',values:[...selected]});});
    window.addEventListener('communication-created', () => {selected.clear();table.bootstrapTable('uncheckAll');sync();});
});
