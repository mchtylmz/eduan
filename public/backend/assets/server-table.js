(() => {
    'use strict';

    const bound = new WeakSet();
    const escapeHtml = value => String(value ?? '')
        .replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;').replaceAll("'", '&#039;');

    const imageLink = (value, label = '', className = 'admin-learning-image') => {
        if (!value) return '<span class="text-muted">—</span>';
        try {
            const url = new URL(value, window.location.origin);
            if (!['http:', 'https:'].includes(url.protocol)) return '—';
            return `<a href="${escapeHtml(url.href)}" target="_blank" rel="noopener"><img class="${escapeHtml(className)}" src="${escapeHtml(url.href)}" alt="${escapeHtml(label)}" loading="lazy"></a>`;
        } catch { return '—'; }
    };
    window.eduanTableImageFormatter = (value, row) => imageLink(value, row.code || row.topic_title || '');
    window.eduanTableLatexImageFormatter = (value, row) => imageLink(value, row.formula, 'latex-ai-image bg-white rounded px-2');
    window.eduanTableSecondsFormatter = (value, row) => {
        const root = document.querySelector(`[data-server-table-page="${row.table}"]`);
        return `${Number(value) || 0} ${escapeHtml(root ? JSON.parse(root.dataset.tableMessages).seconds : '')}`;
    };
    window.eduanTableQuestionsFormatter = (value, row) => `<button type="button" class="btn btn-sm btn-alt-secondary js-table-questions" data-record-id="${Number(row.id)}">${Number(value) || 0}</button>`;

    window.eduanTableTextFormatter = value => escapeHtml(value ?? '-');
    window.eduanTableStatusFormatter = value => {
        const type = ['success', 'danger', 'warning'].includes(value?.class) ? value.class : 'secondary';
        return `<span class="badge bg-${type}-lighter text-${type}">${escapeHtml(value?.label ?? '-')}</span>`;
    };
    window.eduanTableRolesFormatter = roles => Array.isArray(roles) && roles.length ?
        `<div class="d-flex flex-wrap gap-1">${roles.map(role => `<span class="badge bg-primary-lighter text-primary">${escapeHtml(role)}</span>`).join('')}</div>` : '-';
    window.eduanTableActionsFormatter = function (value, row) {
        const root = document.querySelector(`[data-server-table-page="${row.table}"]`);
        const messages = root ? JSON.parse(root.dataset.tableMessages) : {};
        const detail = row.hide_detail ? '' : row.view_url ? `<a class="btn btn-sm btn-info" href="${escapeHtml(row.view_url)}"><i class="fa fa-eye me-1"></i>${escapeHtml(messages.view)}</a>` : `<button type="button" class="btn btn-sm btn-info js-table-detail" data-record-id="${Number(row.id)}"><i class="fa fa-eye me-1"></i>${escapeHtml(messages.view)}</button>`;
        const edit = row.edit_url ? `<a class="btn btn-sm btn-warning" href="${escapeHtml(row.edit_url)}"><i class="fa fa-pen me-1"></i>${escapeHtml(messages.edit)}</a>` : '';
        const sections = row.sections_url ? `<a class="btn btn-sm btn-alt-primary" href="${escapeHtml(row.sections_url)}">${escapeHtml(messages.sections)}</a>` : '';
        const translate = row.translate_url ? `<a class="btn btn-sm btn-alt-primary" href="${escapeHtml(row.translate_url)}"><i class="fa fa-language me-1"></i>${escapeHtml(messages.translate)}</a>` : '';
        const remove = row.can_delete ? `<button type="button" class="btn btn-sm btn-danger js-table-delete" data-record-id="${Number(row.id)}"><i class="fa fa-trash-alt me-1"></i>${escapeHtml(messages.delete)}</button>` : '';
        const results = row.results_url ? `<a class="btn btn-sm btn-alt-primary" href="${escapeHtml(row.results_url)}"><i class="fa fa-poll me-1"></i>${escapeHtml(messages.results)}</a><button type="button" class="btn btn-sm btn-alt-secondary js-table-favorites" data-record-id="${Number(row.id)}"><i class="fa fa-heart me-1"></i>${escapeHtml(messages.favorites)}</button>` : '';
        return `<div class="d-inline-flex flex-column align-items-stretch gap-1 admin-table-actions">${detail}${results}${sections}${translate}${edit}${remove}</div>`;
    };

    window.eduanTableLogFormatter = (value, row) => `<div>${escapeHtml(value)}</div><small class="text-muted">${escapeHtml(row.table_name)}</small>`;

    const learningCard = (row, messages) => {
        const ai = row.table === 'ai';
        const title = ai ? row.title || row.topic_title : row.code;
        const items = ai ? [
            [messages.language, escapeHtml(row.language)], [messages.views, escapeHtml(row.usages_count)],
            [messages.votes, escapeHtml(row.votes_count)], [messages.average, escapeHtml(row.votes_avg_vote ?? '-')],
            [messages.report, window.eduanTableStatusFormatter(row.report)], [messages.created, escapeHtml(row.created_at)],
        ] : [
            [messages.language, escapeHtml(row.language)], [messages.time, `${Number(row.time) || 0} ${escapeHtml(messages.seconds)}`],
            [messages.sort, escapeHtml(row.sort)], [messages.status, window.eduanTableStatusFormatter(row.status)],
        ];
        return `<article class="admin-user-card admin-learning-card">
            <div class="admin-user-card-heading"><span class="admin-user-card-icon bg-primary-lighter text-primary"><i class="fa ${ai ? 'fa-wand-magic-sparkles' : 'fa-question'}" aria-hidden="true"></i></span><div><h3>${escapeHtml(title || row.topic_title)}</h3><p>${escapeHtml(row.lesson_name)}</p></div></div>
            <p class="admin-learning-topic"><span class="text-muted">${escapeHtml(messages.topic)}:</span> ${escapeHtml(row.topic_title)}</p>
            <div class="admin-learning-preview">${imageLink(row.attachment, title)}</div>
            <dl class="admin-user-card-meta">${items.map(([label, value]) => `<div><dt>${escapeHtml(label)}</dt><dd>${value}</dd></div>`).join('')}</dl>
            ${window.eduanTableActionsFormatter(null, row)}</article>`;
    };

    const boot = () => document.querySelectorAll('[data-server-table-page]').forEach(root => {
        if (bound.has(root)) return;
        bound.add(root);
        const table = jQuery(root.querySelector('table'));
        const form = root.querySelector('[data-table-filters]');
        const fields = form ? [...form.querySelectorAll('[data-table-filter]')] : [];
        const messages = JSON.parse(root.dataset.tableMessages);
        const panel = root.querySelector('[data-table-detail-panel]');
        const component = () => window.Livewire.find(root.getAttribute('wire:id'));
        const keepUrl = root.dataset.tableUrlState !== 'false';
        const url = new URL(keepUrl ? window.location.href : window.location.origin + window.location.pathname);
        let forceRefresh = false;
        let currentParameters = {};

        fields.forEach(field => {
            const key = field.name + (field.multiple ? '[]' : '');
            if (!url.searchParams.has(key)) return;
            if (field.multiple) {
                const values = url.searchParams.getAll(key);
                [...field.options].forEach(option => option.selected = values.includes(option.value));
            } else {
                field.value = url.searchParams.get(key);
            }
        });
        jQuery(form).find('.selectpicker').selectpicker('refresh');
        fields.forEach(field => {
            if (field._flatpickr && field.value) field._flatpickr.setDate(field.value, false);
        });

        const saveUrl = params => {
            if (!keepUrl) return;
            const current = new URL(window.location.href);
            ['limit', 'offset', 'sort', 'order', 'search', ...fields.map(field => field.name)].forEach(key => {
                current.searchParams.delete(key);
                current.searchParams.delete(key + '[]');
            });
            Object.entries(params).forEach(([key, value]) => {
                if (key === 'refresh' || value === '' || value === null) return;
                if (Array.isArray(value)) value.forEach(item => current.searchParams.append(key + '[]', item));
                else current.searchParams.set(key, value);
            });
            window.history.replaceState(null, '', current);
        };
        const cards = root.querySelector('[data-user-cards]');
        let updateViewButton;
        if (cards) {
            const placeCards = () => {
                const layout = table.closest('.bootstrap-table')[0];
                const pagination = layout.querySelector('.fixed-table-pagination');
                if (pagination) pagination.before(cards); else layout.append(cards);
            };
            const renderCards = rows => {
                placeCards();
                cards.innerHTML = rows.length ? rows.map(row => ['questions', 'ai'].includes(row.table) ? learningCard(row, messages) : `<article class="admin-user-card">
                    <div class="admin-user-card-heading"><span class="admin-user-card-icon bg-primary-lighter text-primary"><i class="fa fa-user" aria-hidden="true"></i></span>
                        <div><h3>${escapeHtml([row.name, row.surname].filter(Boolean).join(' ') || row.username)}</h3><p>${escapeHtml(row.username)}</p></div></div>
                    ${window.eduanTableRolesFormatter(row.roles)}
                    <dl class="admin-user-card-meta">
                        <div><dt>${escapeHtml(messages.status)}</dt><dd>${window.eduanTableStatusFormatter(row.status)}</dd></div>
                        <div><dt>${escapeHtml(messages.emailVerified)}</dt><dd>${window.eduanTableStatusFormatter(row.email_verified)}</dd></div>
                        <div><dt>${escapeHtml(messages.lastLogin)}</dt><dd>${escapeHtml(row.last_login_at)}</dd></div>
                    </dl>${window.eduanTableActionsFormatter(null, row)}</article>`).join('') : `<div class="admin-user-card-message">${escapeHtml(messages.empty)}</div>`;
            };
            table.on('post-body.bs.table', () => { if (table.data('bootstrap.table')) renderCards(table.bootstrapTable('getData')); })
                .on('load-success.bs.table', (event, data) => renderCards(data.rows || []))
                .on('load-error.bs.table', () => { cards.innerHTML = `<div class="admin-user-card-message text-danger">${escapeHtml(messages.error)}</div>`; })
                .on('refresh.bs.table page-change.bs.table search.bs.table sort.bs.table', () => { cards.innerHTML = `<div class="admin-user-card-message" role="status">${escapeHtml(messages.loading)}</div>`; });
            updateViewButton = button => {
                const active = root.classList.contains('is-card-view');
                button.innerHTML = `<i class="fa ${active ? 'fa-table' : 'fa-th-large'}" aria-hidden="true"></i>`;
                button.title = active ? messages.tableView : messages.cardView;
                button.setAttribute('aria-label', button.title);
                button.setAttribute('aria-pressed', String(active));
                cards.classList.toggle('d-none', !active);
            };
        }
        const errorBox = root.querySelector('[data-table-error]');
        table.on('load-error.bs.table', () => {
            errorBox.textContent = messages.error;
            errorBox.classList.remove('d-none');
        }).on('load-success.bs.table', () => errorBox.classList.add('d-none'));

        const sortField = form?.querySelector('[data-table-sort]');
        const orderField = form?.querySelector('[data-table-order]');
        if (sortField && url.searchParams.has('sort')) sortField.value = url.searchParams.get('sort');
        if (orderField && url.searchParams.has('order')) orderField.value = url.searchParams.get('order');
        table.on('sort.bs.table', (event, name, order) => { if (sortField) sortField.value = name; if (orderField) orderField.value = order; });
        const allowedSizes = table.data('page-list') || [15, 25, 50, 100];
        const pageSize = Number(url.searchParams.get('limit')) || Number(table.data('page-size')) || 15;
        table.bootstrapTable({
            cache: false,
            pageSize: allowedSizes.includes(pageSize) ? pageSize : 15,
            pageNumber: Math.floor((Number(url.searchParams.get('offset')) || 0) / pageSize) + 1,
            searchText: url.searchParams.get('search') || '',
            sortName: url.searchParams.get('sort') || table.data('sort-name'),
            sortOrder: url.searchParams.has('order') ? (url.searchParams.get('order') === 'desc' ? 'desc' : 'asc') : table.data('sort-order'),
            ajaxOptions: {headers: {'Accept': 'application/json'}},
            queryParams: params => {
                fields.forEach(field => {
                    params[field.name] = field.multiple ? [...field.selectedOptions].map(option => option.value) : field.value;
                });
                currentParameters = {...params};
                root.querySelectorAll('[data-table-export]').forEach(link => {
                    const target = new URL(link.href);
                    target.search = '';
                    Object.entries(params).forEach(([key,value]) => {
                        if (['limit','offset','refresh'].includes(key) || value === '' || value === null) return;
                        if (Array.isArray(value)) value.forEach(item => target.searchParams.append(key+'[]',item));
                        else target.searchParams.set(key,value);
                    });
                    link.href = target.href;
                    link.classList.toggle('disabled', root.dataset.serverTablePage === 'result-stats' && (!params.result_type || (params.result_type === 'topics' && !params.topic_id)));
                });
                root.querySelectorAll('[data-table-mail]').forEach(button => {
                    button.disabled = !params.result_type || (params.result_type === 'topics' && !params.topic_id);
                });
                saveUrl(params);
                if (forceRefresh) params.refresh = 1;
                forceRefresh = false;
                return params;
            },
        });

        if (cards) {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'btn btn-secondary';
            button.dataset.userViewToggle = '';
            updateViewButton(button);
            table.closest('.bootstrap-table').find('button[name="refresh"]')[0].after(button);
            button.addEventListener('click', () => {
                root.classList.toggle('is-card-view');
                updateViewButton(button);
            });
        }

        if (form) form.addEventListener('submit', event => {
            event.preventDefault();
            if (sortField) { table.bootstrapTable('sortBy', {field: sortField.value, sortOrder: orderField.value}); }
            else table.bootstrapTable('refresh', {pageNumber: 1});
        });
        root.querySelector('[data-table-reset]')?.addEventListener('click', () => {
            form.reset();
            fields.forEach(field => { if (field._flatpickr) field._flatpickr.clear(); });
            jQuery(form).find('.selectpicker').selectpicker('refresh');
            if (sortField) { table.bootstrapTable('sortBy', {field: sortField.value, sortOrder: orderField.value}); }
            else table.bootstrapTable('refresh', {pageNumber: 1});
        });
        root.addEventListener('click', event => {
            if (event.target.closest('button[name="refresh"]')) forceRefresh = true;
        }, true);
        root.addEventListener('click', async event => {
            if (event.target.closest('[data-table-mail]')) { await component().call('showSendEmails', currentParameters); return; }
            const detail = event.target.closest('.js-table-detail');
            const remove = event.target.closest('.js-table-delete');
            const questions = event.target.closest('.js-table-questions');
            const favorites = event.target.closest('.js-table-favorites');
            const button = detail || remove || questions || favorites;
            if (!button) return;
            if (remove) {
                const result = await Swal.fire({icon: 'warning', text: messages.confirm, showCancelButton: true,
                    confirmButtonText: messages.ok, cancelButtonText: messages.cancel});
                if (!result.isConfirmed) return;
            }
            button.disabled = true;
            try {
                await component().call(detail ? 'showDetail' : questions ? 'showModal' : favorites ? 'showFavorites' : 'delete', Number(button.dataset.recordId));
            } catch (error) {
                Swal.fire({icon: 'error', text: messages.error, confirmButtonText: messages.ok});
            } finally {
                button.disabled = false;
            }
        });
        panel?.addEventListener('hidden.bs.offcanvas', () => component().call('closeDetail'));
        window.addEventListener('open-table-detail', event => {
            if (panel && event.detail.table === root.dataset.serverTablePage) bootstrap.Offcanvas.getOrCreateInstance(panel).show();
        });
        window.addEventListener('refresh-server-table', event => {
            if (event.detail.table !== root.dataset.serverTablePage) return;
            forceRefresh = true;
            table.bootstrapTable('refresh');
        });
    });

    window.addEventListener('learning-table-opened', () => {
        const panel = document.getElementById('livewireOffcanvas');
        if (panel) bootstrap.Offcanvas.getOrCreateInstance(panel).show();
    });

    window.addEventListener('stats-mail-opened', () => { const panel=document.getElementById('livewireModal'); if(panel) bootstrap.Modal.getOrCreateInstance(panel).show(); });

    let morphBound = false;
    const bindMorph = () => { if (!morphBound && window.Livewire) { morphBound = true; window.Livewire.hook('morphed', boot); } };
    document.addEventListener('livewire:init', bindMorph, {once: true});
    bindMorph();

    document.addEventListener('DOMContentLoaded', boot, {once: true});
    document.addEventListener('livewire:navigated', boot);
    if (document.readyState !== 'loading') boot();
})();
