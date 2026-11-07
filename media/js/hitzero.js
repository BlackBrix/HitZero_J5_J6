/** HitZero admin interface for Joomla 5/6. Vanilla JavaScript + Bootstrap 5 modal. */
(() => {
    'use strict';

    const options = window.Joomla?.getOptions?.('plg_system_hitzero');
    if (!options?.token) return;
    const labels = options.labels || {};

    function validIds(values) {
        return [...new Set(values.map(Number).filter(id => Number.isSafeInteger(id) && id > 0))];
    }

    /** The only operation which modifies hit counters: CSRF-protected POST. */
    function postReset(ids, editing) {
        const form = document.createElement('form');
        form.method = 'post';
        form.action = 'index.php?option=com_content&hitzero_action=reset';
        const field = (name, value) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = name;
            input.value = String(value);
            form.appendChild(input);
        };
        ids.forEach(id => field('hitzero_ids[]', id));
        field('return_view', editing ? 'article' : (options.listView === 'featured' ? 'featured' : 'articles'));
        if (editing) field('return_id', options.editId);
        field(options.token, '1');
        document.body.appendChild(form);
        form.submit();
    }

    // Build the dialog once using safe DOM operations (no inline HTML/scripts).
    // Joomla's Web Asset Manager explicitly loads bootstrap.modal for us.
    function createModal() {
        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.id = 'hitzeroModal';
        modal.tabIndex = -1;
        modal.setAttribute('aria-labelledby', 'hitzeroModalTitle');
        modal.setAttribute('aria-hidden', 'true');

        const el = (tag, className, text) => {
            const node = document.createElement(tag);
            if (className) node.className = className;
            if (text !== undefined) node.textContent = text;
            return node;
        };
        const dialog = el('div', 'modal-dialog');
        dialog.setAttribute('role', 'document');
        const content = el('div', 'modal-content');
        const header = el('div', 'modal-header');
        const title = el('h3', 'modal-title fs-5', labels.modalTitle || labels.single);
        title.id = 'hitzeroModalTitle';
        const headerClose = el('button', 'btn-close');
        headerClose.type = 'button';
        headerClose.setAttribute('data-bs-dismiss', 'modal');
        headerClose.setAttribute('aria-label', labels.cancel || 'Cancel');
        header.append(title, headerClose);

        const body = el('div', 'modal-body px-3');
        const prompt = el('p');
        const warning = el('p', 'text-center fst-italic fw-bold text-danger', labels.irreversible || 'This cannot be undone!');
        body.append(prompt, warning);

        const footer = el('div', 'modal-footer');
        const cancel = el('button', 'btn btn-secondary', labels.cancel || 'Cancel');
        cancel.type = 'button';
        cancel.setAttribute('data-bs-dismiss', 'modal');
        const confirm = el('button', 'btn btn-success', labels.confirmButton || labels.single);
        confirm.type = 'button';
        footer.append(cancel, confirm);
        content.append(header, body, footer);
        dialog.append(content);
        modal.append(dialog);
        document.body.appendChild(modal);
        return { modal, prompt, confirm };
    }

    let dialog;
    function askReset(rawIds, editing) {
        const ids = validIds(rawIds);
        if (!ids.length) {
            window.alert(labels.noneSelected || 'No articles selected.');
            return;
        }

        if (!dialog) dialog = createModal();
        dialog.prompt.textContent = ids.length > 1 ? labels.confirmMultiple : labels.confirmSingle;
        // Replace the previous action, so only the currently confirmed IDs are posted.
        dialog.confirm.onclick = () => {
            dialog.confirm.disabled = true;
            postReset(ids, editing);
        };
        dialog.confirm.disabled = false;

        if (window.bootstrap?.Modal) {
            window.bootstrap.Modal.getOrCreateInstance(dialog.modal).show();
        } else {
            // A graceful fallback if a custom administrator template blocks Bootstrap.
            if (window.confirm(dialog.prompt.textContent)) postReset(ids, editing);
        }
    }

    /** Compact icon-only button with accessible label and native Joomla icon. */
    function resetButton(label, onClick, classes) {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = classes;
        button.title = label;
        button.setAttribute('aria-label', label);
        const icon = document.createElement('span');
        icon.className = 'icon-refresh';
        icon.setAttribute('aria-hidden', 'true');
        button.appendChild(icon);
        button.addEventListener('click', event => {
            event.preventDefault();
            event.stopPropagation();
            onClick();
        });
        return button;
    }

    function initEdit() {
        const hits = document.querySelector('#jform_hits');
        const id = Number(options.editId);
        if (!hits || !Number.isSafeInteger(id) || id < 1) return;
        if (document.querySelector('#hitzero-edit-button')) return;

        const button = resetButton(labels.single, () => askReset([id], true), 'btn btn-primary');
        button.id = 'hitzero-edit-button';
        button.disabled = !(Number(hits.value) > 0);

        // Move the original input into Bootstrap's input-group; don't recreate it.
        const group = document.createElement('div');
        group.className = 'input-group w-auto';
        hits.parentNode.insertBefore(group, hits);
        group.append(hits, button);
    }

    function initList() {
        const table = document.querySelector('table#articleList');
        if (!table) return;
        const eligible = new Set();
        table.querySelectorAll('tbody tr').forEach(row => {
            const checkbox = row.querySelector('input[name="cid[]"]');
            const badge = row.querySelector('td span.badge.bg-info');
            const editLink = row.querySelector('a[href*="task=article.edit"]');
            const id = Number(checkbox?.value);
            if (!badge || !checkbox || !editLink || !Number.isSafeInteger(id) || id < 1) return;
            eligible.add(id);
            if (row.querySelector('.hitzero-row-button')) return;

            const button = resetButton(labels.single, () => askReset([id], false),
                'btn btn-sm btn-outline-secondary ms-1 hitzero-row-button');
            // Compact ~25 x 25 px button in list only; retain the icon's font size.
            button.style.width = '25px';
            button.style.height = '25px';
            button.style.minWidth = '25px';
            button.style.minHeight = '25px';
            button.style.padding = '0';
            button.style.display = 'inline-flex';
            button.style.alignItems = 'center';
            button.style.justifyContent = 'center';
            button.style.verticalAlign = 'middle';
            button.disabled = !(Number(badge.textContent.trim()) > 0);
            badge.insertAdjacentElement('afterend', button);
        });

        if (!eligible.size || document.querySelector('#hitzero-toolbar-btn')) return;
        const selected = () => [...table.querySelectorAll('tbody input[name="cid[]"]:checked')]
            .map(input => Number(input.value)).filter(id => eligible.has(id));

        // The toolbar action needs visible text, unlike the icon-only per-row buttons.
        const batch = resetButton(labels.multiple, () => askReset(selected(), false), 'dropdown-item');
        batch.id = 'hitzero-toolbar-btn';
        batch.append(document.createTextNode(' ' + labels.multiple));
        const menu = document.querySelector('#toolbar-status-group .dropdown-menu');
        if (menu) {
            const item = document.createElement('div');
            item.appendChild(batch);
            menu.appendChild(item);
        } else {
            batch.className = 'btn btn-sm btn-outline-secondary';
            (document.querySelector('#toolbar') || table.parentElement)?.prepend(batch);
        }
    }

    const init = () => options.editing ? initEdit() : initList();
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init, { once: true });
    } else init();
})();
