<div class="page-header">
    <h1 class="page-title">Menu Editor</h1>
</div>
<?php include BASE_PATH . '/resources/views/partials/flash.php'; ?>

<nav class="settings-tab-nav" role="tablist">
    <a href="<?= url('/settings') ?>"               class="settings-tab" role="tab">General</a>
    <a href="<?= url('/settings/harvest') ?>"       class="settings-tab" role="tab">🌾 Harvest</a>
    <a href="<?= url('/settings/storage') ?>"       class="settings-tab" role="tab">Storage</a>
    <a href="<?= url('/settings/action-types') ?>"  class="settings-tab" role="tab">Action Types</a>
    <a href="<?= url('/settings/weather') ?>"       class="settings-tab" role="tab">🌤️ Weather</a>
    <a href="<?= url('/settings/calendar') ?>"      class="settings-tab" role="tab">📅 Calendar</a>
    <a href="<?= url('/settings/pwa') ?>"           class="settings-tab" role="tab">📱 PWA</a>
    <a href="<?= url('/settings/menus') ?>"         class="settings-tab settings-tab--active" role="tab">☰ Menus</a>
    <a href="<?= url('/logs/errors') ?>"            class="settings-tab" role="tab">Error Logs</a>
    <a href="<?= url('/settings/upcoming') ?>"      class="settings-tab" role="tab">🗺 Roadmap</a>
    <a href="<?= url('/settings/upgrade') ?>"       class="settings-tab" role="tab">⬆️ Upgrade</a>
</nav>

<?php
// Build grouped routes for left panel
$routes  = require BASE_PATH . '/config/menu_routes.php';
$icons   = require BASE_PATH . '/config/menu_icons.php';
$grouped = [];
foreach ($routes as $key => $r) {
    $grouped[$r['group']][$key] = $r;
}

// Load current menus
$menusPath = defined('STORAGE_PATH') ? STORAGE_PATH . '/menus.json' : null;
$savedMenus = null;
if ($menusPath && file_exists($menusPath)) {
    $savedMenus = json_decode(file_get_contents($menusPath), true);
}
if (!is_array($savedMenus)) {
    $savedMenus = require BASE_PATH . '/config/menu_defaults.php';
}
$savedMenus['main']   = $savedMenus['main']   ?? [];
$savedMenus['footer'] = $savedMenus['footer'] ?? [];

// Encode SVG icon data for JS (key => inner path string)
$iconsSvgMap = [];
foreach ($icons as $k => $path) {
    $iconsSvgMap[$k] = $path;
}
?>

<div class="menu-editor" id="menuEditor">

    <!-- ── Left panel: Add items ── -->
    <div class="menu-editor__left">

        <!-- Pages from routes -->
        <div class="card menu-editor-card">
            <div class="menu-editor-card__title">App Pages</div>
            <div class="menu-editor-pages" id="pagesPanel">
                <?php foreach ($grouped as $group => $groupRoutes): ?>
                <details class="menu-pages-group" open>
                    <summary class="menu-pages-group__title"><?= e($group) ?></summary>
                    <div class="menu-pages-group__list">
                        <?php foreach ($groupRoutes as $key => $r): ?>
                        <label class="menu-pages-item">
                            <input type="checkbox" class="menu-page-cb" value="<?= e($key) ?>"
                                   data-label="<?= e($r['label']) ?>">
                            <span><?= e($r['label']) ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </details>
                <?php endforeach; ?>
            </div>
            <div class="menu-editor-card__footer">
                <button type="button" class="btn btn-sm btn-primary" id="addPagesBtn">Add to Menu</button>
            </div>
        </div>

        <!-- Custom link -->
        <div class="card menu-editor-card">
            <div class="menu-editor-card__title">Custom Link</div>
            <div class="menu-editor-card__body">
                <label class="settings-label">URL</label>
                <input type="text" id="customUrl" class="settings-input" placeholder="https://example.com">
                <label class="settings-label" style="margin-top:8px">Label</label>
                <input type="text" id="customLabel" class="settings-input" placeholder="My Link">
            </div>
            <div class="menu-editor-card__footer">
                <button type="button" class="btn btn-sm btn-primary" id="addCustomBtn">Add to Menu</button>
            </div>
        </div>

    </div><!-- /.menu-editor__left -->

    <!-- ── Right panel: Live menu ── -->
    <div class="menu-editor__right">

        <div class="menu-editor__toolbar">
            <label class="settings-label" style="margin:0">Editing:</label>
            <select id="menuSelect" class="settings-input menu-editor__select">
                <option value="main">Main Menu</option>
                <option value="footer">Footer Menu</option>
            </select>
            <button type="button" class="btn btn-primary" id="saveMenuBtn">Save Menu</button>
        </div>

        <div class="card" style="padding:0;overflow:hidden">
            <div id="menuList" class="menu-list">
                <!-- rendered by JS -->
            </div>
            <div id="menuEmpty" class="menu-list-empty" style="display:none">
                <p>No items yet. Add pages or custom links from the left panel.</p>
            </div>
        </div>

    </div><!-- /.menu-editor__right -->

</div><!-- /.menu-editor -->

<style>
/* ── Menu editor layout ─────────────────────────────────── */
.menu-editor {
    display: grid;
    grid-template-columns: 300px 1fr;
    gap: 20px;
    align-items: start;
    margin-top: 16px;
}
@media (max-width: 768px) {
    .menu-editor { grid-template-columns: 1fr; }
}
.menu-editor__left { display: flex; flex-direction: column; gap: 16px; }

.menu-editor-card { padding: 0; overflow: hidden; }
.menu-editor-card__title {
    font-weight: 600; font-size: .85rem; text-transform: uppercase;
    letter-spacing: .04em; padding: 10px 14px;
    border-bottom: 1px solid var(--border-color, #e5e7eb);
    background: var(--bg-subtle, #f9fafb);
}
.menu-editor-card__body { padding: 12px 14px; }
.menu-editor-card__footer {
    padding: 10px 14px;
    border-top: 1px solid var(--border-color, #e5e7eb);
    background: var(--bg-subtle, #f9fafb);
}

/* Pages list */
.menu-editor-pages { max-height: 380px; overflow-y: auto; }
.menu-pages-group { border-bottom: 1px solid var(--border-color, #e5e7eb); }
.menu-pages-group:last-child { border-bottom: none; }
.menu-pages-group__title {
    padding: 8px 14px; font-size: .8rem; font-weight: 600;
    text-transform: uppercase; letter-spacing: .04em; cursor: pointer;
    user-select: none; color: var(--text-muted, #6b7280);
    background: transparent; list-style: none;
}
.menu-pages-group__title::-webkit-details-marker { display: none; }
.menu-pages-group__list { padding: 4px 14px 8px; display: flex; flex-direction: column; gap: 4px; }
.menu-pages-item {
    display: flex; align-items: center; gap: 8px;
    font-size: .88rem; cursor: pointer; padding: 3px 0;
}
.menu-pages-item input { cursor: pointer; flex-shrink: 0; }

/* Toolbar */
.menu-editor__toolbar {
    display: flex; align-items: center; gap: 10px; margin-bottom: 12px; flex-wrap: wrap;
}
.menu-editor__select { flex: 1; min-width: 140px; max-width: 200px; margin: 0; }

/* Menu list */
.menu-list { padding: 8px 0; }
.menu-list-empty { padding: 24px 16px; color: var(--text-muted, #6b7280); text-align: center; font-size: .9rem; }

/* Menu item row */
.menu-item {
    border-bottom: 1px solid var(--border-color, #e5e7eb);
}
.menu-item:last-child { border-bottom: none; }
.menu-item--dragging { opacity: .4; }
.menu-item--over { box-shadow: 0 -2px 0 var(--primary, #29402b) inset; }
.menu-item--over-bottom { box-shadow: 0 2px 0 var(--primary, #29402b); }

.menu-item__row {
    display: flex; align-items: center; gap: 0;
    padding: 8px 10px; background: #fff;
}
.menu-item__row:hover { background: var(--bg-subtle, #f9fafb); }
.menu-item__drag {
    cursor: grab; padding: 4px 8px; color: var(--text-muted, #9ca3af);
    font-size: 1rem; flex-shrink: 0; touch-action: none;
}
.menu-item__drag:active { cursor: grabbing; }
.menu-item__icon {
    display: flex; align-items: center; margin-right: 8px; color: var(--text-muted, #6b7280);
}
.menu-item__icon svg { width: 16px; height: 16px; }
.menu-item__label { flex: 1; font-size: .9rem; font-weight: 500; }
.menu-item__url { font-size: .75rem; color: var(--text-muted, #9ca3af); margin-left: 6px; }
.menu-item__actions { display: flex; gap: 4px; flex-shrink: 0; }
.menu-item__btn {
    background: none; border: none; cursor: pointer; padding: 4px 6px;
    color: var(--text-muted, #9ca3af); border-radius: 4px; font-size: .8rem; line-height: 1;
}
.menu-item__btn:hover { background: var(--bg-subtle, #f0f0f0); color: var(--text-main, #374151); }
.menu-item__btn--remove:hover { color: #dc2626; }

/* Edit panel */
.menu-item__edit {
    background: var(--bg-subtle, #f9fafb);
    border-top: 1px solid var(--border-color, #e5e7eb);
    padding: 12px 14px;
    display: none;
}
.menu-item__edit.is-open { display: block; }
.menu-edit-field { margin-bottom: 10px; }
.menu-edit-field:last-child { margin-bottom: 0; }
.menu-edit-field label { display: block; font-size: .78rem; font-weight: 600; margin-bottom: 4px; color: var(--text-muted, #6b7280); text-transform: uppercase; letter-spacing: .03em; }
.menu-edit-field input,
.menu-edit-field select { width: 100%; padding: 6px 8px; border: 1px solid var(--border-color, #d1d5db); border-radius: 6px; font-size: .88rem; background: #fff; }
.menu-edit-field input:focus,
.menu-edit-field select:focus { outline: none; border-color: var(--primary, #29402b); box-shadow: 0 0 0 2px rgba(41,64,43,.15); }

.menu-icon-preview {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 4px 8px; background: var(--bg-subtle, #f0f0f0);
    border-radius: 6px; margin-bottom: 6px;
}
.menu-icon-preview svg { width: 18px; height: 18px; }
.menu-icon-preview span { font-size: .8rem; color: var(--text-muted, #6b7280); }

.menu-edit-actions {
    display: flex; gap: 8px; justify-content: flex-end; margin-top: 12px; padding-top: 10px;
    border-top: 1px solid var(--border-color, #e5e7eb);
}

/* Sub-items */
.menu-item__children {
    padding-left: 28px;
    border-top: 1px dashed var(--border-color, #e5e7eb);
    background: var(--bg-subtle, #fafafa);
}
.menu-item__children .menu-item { border-bottom: 1px dashed var(--border-color, #e5e7eb); }
.menu-item__children .menu-item:last-child { border-bottom: none; }
.menu-item__children .menu-item__row { background: transparent; }

/* Save btn spinner */
#saveMenuBtn.is-saving { opacity: .6; cursor: default; }
</style>

<script>
(function() {
    // ── State ───────────────────────────────────────────────────────────────
    const ICONS_SVG = <?= json_encode($iconsSvgMap) ?>;
    const ICONS_KEYS = Object.keys(ICONS_SVG);
    let menus = <?= json_encode($savedMenus) ?>;
    let currentMenu = 'main';
    let dragState = null; // for DnD

    // ── Helpers ─────────────────────────────────────────────────────────────
    function uid() {
        return 'u' + Date.now().toString(36) + Math.random().toString(36).slice(2, 6);
    }

    function iconSvgHtml(iconKey, customSvg) {
        const path = customSvg || ICONS_SVG[iconKey] || '';
        if (!path) return '';
        return `<svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${path}</svg>`;
    }

    function iconSelectOptions(selectedKey) {
        let html = `<option value=""${selectedKey === '' ? ' selected' : ''}>(no icon)</option>`;
        for (const k of ICONS_KEYS) {
            html += `<option value="${k}"${selectedKey === k ? ' selected' : ''}>${k}</option>`;
        }
        html += `<option value="custom"${selectedKey === 'custom' ? ' selected' : ''}>custom…</option>`;
        return html;
    }

    function parentOptions(excludeId) {
        const items = menus[currentMenu];
        let html = `<option value="">(top level)</option>`;
        for (const item of items) {
            if (item.id === excludeId) continue;
            html += `<option value="${item.id}">${escHtml(item.label)}</option>`;
        }
        return html;
    }

    function escHtml(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function resolvedUrl(item) {
        if (item.type === 'route' && item.route_key) {
            return item.route_key;
        }
        return item.url || '';
    }

    // ── Render ───────────────────────────────────────────────────────────────
    function render() {
        const list = document.getElementById('menuList');
        const empty = document.getElementById('menuEmpty');
        const items = menus[currentMenu] || [];

        if (items.length === 0) {
            list.innerHTML = '';
            list.style.display = 'none';
            empty.style.display = '';
            return;
        }
        empty.style.display = 'none';
        list.style.display = '';

        list.innerHTML = items.map((item, idx) => renderItem(item, idx, null)).join('');
        attachItemEvents(list, null);
    }

    function renderItem(item, idx, parentId) {
        const iconHtml = iconSvgHtml(item.icon || '', item.icon_svg || null);
        const urlDisplay = resolvedUrl(item);
        const hasChildren = parentId === null && item.children && item.children.length > 0;
        const currentIconKey = item.icon_svg ? 'custom' : (item.icon || '');

        let childrenHtml = '';
        if (hasChildren) {
            childrenHtml = `<div class="menu-item__children" data-parent-id="${item.id}">
                ${item.children.map((c, ci) => renderItem(c, ci, item.id)).join('')}
            </div>`;
        } else if (parentId === null) {
            childrenHtml = `<div class="menu-item__children menu-item__children--empty" data-parent-id="${item.id}" style="display:none"></div>`;
        }

        const parentLabel = parentId ? '' : ''; // sub-items don't show parent selector
        const parentSelectHtml = parentId === null ? `
            <div class="menu-edit-field">
                <label>Parent</label>
                <select class="item-parent-select" data-id="${item.id}">
                    ${parentOptions(item.id)}
                </select>
            </div>` : '';

        return `<div class="menu-item" data-id="${item.id}" data-parent="${parentId || ''}" draggable="true">
            <div class="menu-item__row">
                <span class="menu-item__drag" title="Drag to reorder">⠿</span>
                ${iconHtml ? `<span class="menu-item__icon">${iconHtml}</span>` : '<span class="menu-item__icon" style="width:24px"></span>'}
                <span class="menu-item__label">${escHtml(item.label)}</span>
                <span class="menu-item__url">${escHtml(urlDisplay)}</span>
                <span class="menu-item__actions">
                    <button type="button" class="menu-item__btn item-toggle-btn" data-id="${item.id}" title="Edit">▾</button>
                    <button type="button" class="menu-item__btn menu-item__btn--remove item-remove-btn" data-id="${item.id}" data-parent="${parentId || ''}" title="Remove">✕</button>
                </span>
            </div>
            <div class="menu-item__edit" id="edit-${item.id}">
                <div class="menu-edit-field">
                    <label>Label</label>
                    <input type="text" class="item-label-input" data-id="${item.id}" value="${escHtml(item.label)}">
                </div>
                ${item.type === 'custom' ? `<div class="menu-edit-field">
                    <label>URL</label>
                    <input type="text" class="item-url-input" data-id="${item.id}" value="${escHtml(item.url || '')}">
                </div>` : `<div class="menu-edit-field">
                    <label>Route</label>
                    <input type="text" class="item-url-input" data-id="${item.id}" value="${escHtml(item.route_key || '')}" readonly style="background:#f3f4f6;color:#6b7280">
                </div>`}
                <div class="menu-edit-field">
                    <label>Icon</label>
                    <div class="menu-icon-preview" id="iconPreview-${item.id}">
                        ${iconHtml || '<span style="color:#9ca3af;font-size:.8rem">none</span>'}
                        <span>${escHtml(currentIconKey || 'none')}</span>
                    </div>
                    <select class="item-icon-select" data-id="${item.id}">
                        ${iconSelectOptions(currentIconKey)}
                    </select>
                </div>
                <div class="menu-edit-field" id="customSvgField-${item.id}" style="${currentIconKey === 'custom' ? '' : 'display:none'}">
                    <label>Custom SVG path</label>
                    <input type="text" class="item-custom-svg" data-id="${item.id}" value="${escHtml(item.icon_svg || '')}" placeholder="&lt;path d=&quot;M...&quot;/&gt;">
                </div>
                ${parentSelectHtml}
                <div class="menu-edit-actions">
                    <button type="button" class="btn btn-sm btn-secondary item-cancel-btn" data-id="${item.id}">Cancel</button>
                    <button type="button" class="btn btn-sm btn-primary item-save-btn" data-id="${item.id}" data-parent="${parentId || ''}">Apply</button>
                </div>
            </div>
            ${childrenHtml}
        </div>`;
    }

    // ── Event wiring ─────────────────────────────────────────────────────────
    function attachItemEvents(container, parentId) {
        // toggle edit panel
        container.querySelectorAll('.item-toggle-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = btn.dataset.id;
                const panel = document.getElementById('edit-' + id);
                if (panel) panel.classList.toggle('is-open');
            });
        });

        // remove
        container.querySelectorAll('.item-remove-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = btn.dataset.id;
                const pId = btn.dataset.parent;
                removeItem(id, pId || null);
            });
        });

        // icon select → preview
        container.querySelectorAll('.item-icon-select').forEach(sel => {
            sel.addEventListener('change', () => {
                const id = sel.dataset.id;
                const val = sel.value;
                const preview = document.getElementById('iconPreview-' + id);
                const customField = document.getElementById('customSvgField-' + id);
                if (val === 'custom') {
                    customField.style.display = '';
                    if (preview) preview.innerHTML = '<span style="color:#9ca3af;font-size:.8rem">custom</span><span>custom</span>';
                } else {
                    customField.style.display = 'none';
                    const svg = val ? ICONS_SVG[val] : '';
                    if (preview) {
                        const svgHtml = svg ? `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">${svg}</svg>` : '<span style="color:#9ca3af;font-size:.8rem">none</span>';
                        preview.innerHTML = svgHtml + `<span>${val || 'none'}</span>`;
                    }
                }
            });
        });

        // custom svg input → preview
        container.querySelectorAll('.item-custom-svg').forEach(inp => {
            inp.addEventListener('input', () => {
                const id = inp.dataset.id;
                const preview = document.getElementById('iconPreview-' + id);
                const val = inp.value.trim();
                if (preview && val) {
                    const svgHtml = `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">${val}</svg>`;
                    preview.innerHTML = svgHtml + '<span>custom</span>';
                }
            });
        });

        // apply edit
        container.querySelectorAll('.item-save-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = btn.dataset.id;
                const pId = btn.dataset.parent;
                applyItemEdit(id, pId || null);
            });
        });

        // cancel edit
        container.querySelectorAll('.item-cancel-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = btn.dataset.id;
                const panel = document.getElementById('edit-' + id);
                if (panel) panel.classList.remove('is-open');
            });
        });

        // drag events on item rows
        container.querySelectorAll('.menu-item').forEach(el => {
            el.addEventListener('dragstart', onDragStart);
            el.addEventListener('dragover',  onDragOver);
            el.addEventListener('dragleave', onDragLeave);
            el.addEventListener('drop',      onDrop);
            el.addEventListener('dragend',   onDragEnd);
        });
    }

    // ── Item operations ───────────────────────────────────────────────────────
    function findItem(id, items) {
        for (const item of items) {
            if (item.id === id) return item;
            if (item.children) {
                const found = findItem(id, item.children);
                if (found) return found;
            }
        }
        return null;
    }

    function removeItem(id, parentId) {
        const items = menus[currentMenu];
        if (parentId) {
            const parent = findItem(parentId, items);
            if (parent && parent.children) {
                parent.children = parent.children.filter(c => c.id !== id);
            }
        } else {
            // removing top-level: orphan its children to top level
            const idx = items.findIndex(i => i.id === id);
            if (idx !== -1) {
                const orphans = items[idx].children || [];
                items.splice(idx, 1, ...orphans.map(c => ({ ...c, children: [] })));
            }
        }
        render();
    }

    function applyItemEdit(id, parentId) {
        const items = menus[currentMenu];
        const item = findItem(id, items);
        if (!item) return;

        const labelInp = document.querySelector(`.item-label-input[data-id="${id}"]`);
        const urlInp   = document.querySelector(`.item-url-input[data-id="${id}"]`);
        const iconSel  = document.querySelector(`.item-icon-select[data-id="${id}"]`);
        const svgInp   = document.querySelector(`.item-custom-svg[data-id="${id}"]`);
        const parentSel = document.querySelector(`.item-parent-select[data-id="${id}"]`);

        if (labelInp) item.label = labelInp.value.trim() || item.label;
        if (urlInp && item.type === 'custom') item.url = urlInp.value.trim();

        const iconVal = iconSel ? iconSel.value : '';
        if (iconVal === 'custom') {
            item.icon = null;
            item.icon_svg = svgInp ? svgInp.value.trim() : null;
        } else {
            item.icon = iconVal || null;
            item.icon_svg = null;
        }

        // Handle parent change (top-level items only)
        if (parentSel && !parentId) {
            const newParentId = parentSel.value;
            if (newParentId && newParentId !== parentId) {
                // Move to child of newParentId
                const newParent = findItem(newParentId, items);
                if (newParent) {
                    const itemIdx = items.findIndex(i => i.id === id);
                    if (itemIdx !== -1) {
                        const [moved] = items.splice(itemIdx, 1);
                        moved.children = []; // sub-items can't have sub-sub-items
                        newParent.children = newParent.children || [];
                        newParent.children.push(moved);
                    }
                }
            }
        }

        render();
    }

    // ── Drag and Drop ─────────────────────────────────────────────────────────
    function onDragStart(e) {
        const el = e.currentTarget;
        dragState = { id: el.dataset.id, parentId: el.dataset.parent || null };
        el.classList.add('menu-item--dragging');
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', el.dataset.id);
    }

    function onDragOver(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
        const el = e.currentTarget;
        if (!dragState || el.dataset.id === dragState.id) return;
        // only allow drop within same level
        if ((el.dataset.parent || '') !== (dragState.parentId || '')) return;
        el.classList.add('menu-item--over');
    }

    function onDragLeave(e) {
        e.currentTarget.classList.remove('menu-item--over');
    }

    function onDrop(e) {
        e.preventDefault();
        const target = e.currentTarget;
        target.classList.remove('menu-item--over');
        if (!dragState || target.dataset.id === dragState.id) return;
        if ((target.dataset.parent || '') !== (dragState.parentId || '')) return;

        const parentId = dragState.parentId || null;
        const items = parentId
            ? (findItem(parentId, menus[currentMenu]) || {}).children || []
            : menus[currentMenu];

        const fromIdx = items.findIndex(i => i.id === dragState.id);
        const toIdx   = items.findIndex(i => i.id === target.dataset.id);
        if (fromIdx === -1 || toIdx === -1) return;

        const [moved] = items.splice(fromIdx, 1);
        items.splice(toIdx, 0, moved);

        if (parentId) {
            const parent = findItem(parentId, menus[currentMenu]);
            if (parent) parent.children = items;
        } else {
            menus[currentMenu] = items;
        }

        render();
    }

    function onDragEnd(e) {
        e.currentTarget.classList.remove('menu-item--dragging');
        document.querySelectorAll('.menu-item--over').forEach(el => el.classList.remove('menu-item--over'));
        dragState = null;
    }

    // ── Add items ─────────────────────────────────────────────────────────────
    document.getElementById('addPagesBtn').addEventListener('click', () => {
        const checked = document.querySelectorAll('.menu-page-cb:checked');
        if (!checked.length) { alert('Select at least one page.'); return; }
        const existing = new Set(
            (menus[currentMenu] || []).map(i => i.route_key)
                .concat(
                    (menus[currentMenu] || []).flatMap(i => (i.children || []).map(c => c.route_key))
                )
        );
        checked.forEach(cb => {
            if (existing.has(cb.value)) return;
            menus[currentMenu].push({
                id: uid(),
                type: 'route',
                route_key: cb.value,
                label: cb.dataset.label,
                icon: null,
                icon_svg: null,
                children: []
            });
            cb.checked = false;
        });
        render();
    });

    document.getElementById('addCustomBtn').addEventListener('click', () => {
        const url   = document.getElementById('customUrl').value.trim();
        const label = document.getElementById('customLabel').value.trim();
        if (!url || !label) { alert('Please enter both a URL and a label.'); return; }
        menus[currentMenu].push({
            id: uid(),
            type: 'custom',
            url: url,
            label: label,
            icon: null,
            icon_svg: null,
            children: []
        });
        document.getElementById('customUrl').value = '';
        document.getElementById('customLabel').value = '';
        render();
    });

    // ── Menu select ───────────────────────────────────────────────────────────
    document.getElementById('menuSelect').addEventListener('change', function() {
        currentMenu = this.value;
        render();
    });

    // ── Save ─────────────────────────────────────────────────────────────────
    document.getElementById('saveMenuBtn').addEventListener('click', () => {
        const btn = document.getElementById('saveMenuBtn');
        btn.classList.add('is-saving');
        btn.textContent = 'Saving…';

        fetch('<?= url('/settings/menus/save') ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify(menus)
        })
        .then(r => r.json())
        .then(data => {
            btn.classList.remove('is-saving');
            btn.textContent = 'Save Menu';
            if (data.ok) {
                btn.textContent = 'Saved ✓';
                setTimeout(() => { btn.textContent = 'Save Menu'; }, 2000);
            } else {
                alert('Save failed: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(() => {
            btn.classList.remove('is-saving');
            btn.textContent = 'Save Menu';
            alert('Network error. Please try again.');
        });
    });

    // ── Init ─────────────────────────────────────────────────────────────────
    render();
})();
</script>
