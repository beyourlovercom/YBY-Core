/* Andy Commerce Orders UI V1 — DOM-only view switching (no order requests). */
(function () {
  'use strict';
  const cfg = window.AndyCommerceOrdersUIV1;
  if (!cfg || !cfg.preferences) return;

  function ready() {
    const table = document.querySelector('.wrap table.wp-list-table');
    if (!table || !table.tHead || !table.tBodies.length || !table.tHead.rows.length) return;

    const header = table.tHead.rows[0];
    const keyFor = function (cell, position) {
      if (cell.id && /^[a-z0-9_-]+$/i.test(cell.id)) return cell.id.toLowerCase();
      const match = (cell.className || '').match(/(?:^|\s)column-([a-z0-9_-]+)/i);
      return match ? match[1].toLowerCase() : 'column_' + position;
    };
    const initialKeys = Array.from(header.cells).map(keyFor);
    if (!initialKeys.length || new Set(initialKeys).size !== initialKeys.length) return;

    const pinned = function (key) {
      return key === 'cb' || key === 'order_actions' || key === 'wc_actions';
    };
    const movableDefault = initialKeys.filter(key => !pinned(key));
    const labels = new Map(Array.from(header.cells).map((cell, i) => {
      const label = (cell.innerText || cell.textContent || '').replace(/\s+/g, ' ').trim();
      return [initialKeys[i], label || initialKeys[i]];
    }));
    let movable = mergeOrder(cfg.preferences.columns || [], movableDefault);
    let layout = ['standard', 'compact', 'cards'].includes(cfg.preferences.layout)
      ? cfg.preferences.layout : 'standard';

    table.classList.add('andy-commerce-orders-table');
    const toolbar = document.createElement('div');
    toolbar.className = 'andy-commerce-orders-toolbar';
    toolbar.setAttribute('aria-label', '订单列表布局控制');
    const left = document.createElement('div');
    left.className = 'andy-commerce-layout-switch';
    const settingsBtn = document.createElement('button');
    settingsBtn.type = 'button';
    settingsBtn.className = 'button andy-commerce-orders-config-button';
    settingsBtn.textContent = '⚙ 列设置';
    settingsBtn.setAttribute('aria-expanded', 'false');
    const panel = document.createElement('div');
    panel.className = 'andy-commerce-orders-panel';
    panel.hidden = true;
    const status = document.createElement('span');
    status.className = 'andy-commerce-orders-save-status';
    status.setAttribute('role', 'status');
    status.setAttribute('aria-live', 'polite');

    const choiceButtons = {};
    for (const [value, label] of [
      ['standard', cfg.strings.standard],
      ['compact', cfg.strings.compact],
      ['cards', cfg.strings.cards]
    ]) {
      const button = document.createElement('button');
      button.type = 'button';
      button.textContent = label;
      button.className = 'andy-commerce-layout-choice';
      button.dataset.view = value;
      button.addEventListener('click', () => {
        layout = value;
        applyView();
        status.textContent = '';
      });
      choiceButtons[value] = button;
      left.appendChild(button);
    }

    toolbar.appendChild(left);
    toolbar.appendChild(settingsBtn);
    table.parentNode.insertBefore(toolbar, table);
    toolbar.parentNode.insertBefore(panel, toolbar.nextSibling);

    function mergeOrder(preferred, known) {
      const ordered = [];
      for (const key of preferred) {
        if (known.includes(key) && !ordered.includes(key)) ordered.push(key);
      }
      for (const key of known) {
        if (!ordered.includes(key)) ordered.push(key);
      }
      return ordered;
    }

    function targetKeys() {
      const choices = mergeOrder(movable, movableDefault);
      // Native bulk-checkbox is always first and order-action columns last.
      return [
        ...initialKeys.filter(k => k === 'cb'),
        ...choices,
        ...initialKeys.filter(k => k !== 'cb' && pinned(k))
      ];
    }

    function rearrangeRow(row, currentKeys, nextKeys) {
      const cells = Array.from(row.children);
      if (cells.length !== currentKeys.length) return; // No-items / colSpan rows.
      const byKey = new Map(currentKeys.map((key, i) => [key, cells[i]]));
      nextKeys.forEach(key => {
        const cell = byKey.get(key);
        if (cell) row.appendChild(cell); // Move existing nodes; preserve events.
      });
    }

    function applyOrder() {
      const currentKeys = Array.from(header.cells).map(keyFor);
      const nextKeys = targetKeys();
      if (currentKeys.length !== nextKeys.length) return;
      for (const row of [...table.tHead.rows, ...(table.tFoot ? table.tFoot.rows : []), ...table.tBodies[0].rows]) {
        rearrangeRow(row, currentKeys, nextKeys);
      }
      for (const row of table.tBodies[0].rows) {
        if (row.children.length !== nextKeys.length) continue;
        Array.from(row.children).forEach((cell, i) => {
          cell.dataset.andyColumn = nextKeys[i];
          cell.dataset.andyLabel = labels.get(nextKeys[i]) || nextKeys[i];
        });
      }
    }

    function applyView() {
      table.dataset.andyLayout = layout;
      for (const [key, button] of Object.entries(choiceButtons)) {
        const selected = key === layout;
        button.classList.toggle('is-active', selected);
        button.setAttribute('aria-pressed', String(selected));
      }
      applyOrder();
    }

    function reorder(from, to) {
      if (from === to || from < 0 || to < 0 || from >= movable.length || to >= movable.length) return;
      const updated = movable.slice();
      const item = updated.splice(from, 1)[0];
      updated.splice(to, 0, item);
      movable = updated;
      applyOrder();
      renderPanel();
      status.textContent = '';
    }

    function renderPanel() {
      panel.replaceChildren();
      const headline = document.createElement('div');
      headline.className = 'andy-commerce-orders-panel-heading';
      headline.textContent = cfg.strings.columns;
      panel.appendChild(headline);

      const note = document.createElement('p');
      note.className = 'description';
      note.textContent = cfg.strings.visibility;
      panel.appendChild(note);
      const list = document.createElement('ol');
      list.className = 'andy-commerce-orders-column-list';

      movable.forEach((key, index) => {
        const li = document.createElement('li');
        li.className = 'andy-commerce-orders-column-item';
        li.draggable = true;
        li.dataset.key = key;
        const handle = document.createElement('span');
        handle.className = 'andy-commerce-orders-drag';
        handle.textContent = '⋮⋮';
        handle.setAttribute('aria-hidden', 'true');
        const label = document.createElement('span');
        label.className = 'andy-commerce-orders-column-label';
        label.textContent = labels.get(key) || key;

        const up = document.createElement('button');
        up.type = 'button';
        up.className = 'button button-small';
        up.textContent = '↑';
        up.title = cfg.strings.moveUp;
        up.setAttribute('aria-label', (labels.get(key) || key) + cfg.strings.moveUp);
        up.disabled = index === 0;
        up.addEventListener('click', () => reorder(index, index - 1));

        const down = document.createElement('button');
        down.type = 'button';
        down.className = 'button button-small';
        down.textContent = '↓';
        down.title = cfg.strings.moveDown;
        down.setAttribute('aria-label', (labels.get(key) || key) + cfg.strings.moveDown);
        down.disabled = index === movable.length - 1;
        down.addEventListener('click', () => reorder(index, index + 1));

        li.addEventListener('dragstart', event => {
          event.dataTransfer.setData('text/plain', key);
          event.dataTransfer.effectAllowed = 'move';
        });
        li.addEventListener('dragover', event => event.preventDefault());
        li.addEventListener('drop', event => {
          event.preventDefault();
          const moved = event.dataTransfer.getData('text/plain');
          reorder(movable.indexOf(moved), movable.indexOf(key));
        });
        li.append(handle, label, up, down);
        list.appendChild(li);
      });
      panel.appendChild(list);

      const actions = document.createElement('div');
      actions.className = 'andy-commerce-orders-panel-actions';
      const resetBtn = document.createElement('button');
      resetBtn.type = 'button';
      resetBtn.className = 'button';
      resetBtn.textContent = cfg.strings.reset;
      resetBtn.addEventListener('click', () => {
        movable = movableDefault.slice();
        layout = 'standard';
        applyView();
        renderPanel();
        status.textContent = '';
      });

      const saveBtn = document.createElement('button');
      saveBtn.type = 'button';
      saveBtn.className = 'button button-primary';
      saveBtn.textContent = cfg.strings.save;
      saveBtn.addEventListener('click', async () => {
        saveBtn.disabled = true;
        status.textContent = cfg.strings.saving;
        try {
          const params = new URLSearchParams();
          params.set('action', 'andy_commerce_orders_ui_save');
          params.set('nonce', cfg.nonce);
          params.set('layout', layout);
          movable.forEach((key, i) => params.set('columns[' + i + ']', key));
          const response = await fetch(cfg.ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
            body: params.toString()
          });
          const result = await response.json();
          if (!response.ok || !result.success) throw new Error('save failed');
          status.textContent = cfg.strings.saved;
        } catch (error) {
          status.textContent = cfg.strings.failed;
        } finally {
          saveBtn.disabled = false;
        }
      });
      actions.append(resetBtn, saveBtn, status);
      panel.appendChild(actions);
    }

    settingsBtn.addEventListener('click', () => {
      panel.hidden = !panel.hidden;
      settingsBtn.setAttribute('aria-expanded', String(!panel.hidden));
      if (!panel.hidden) renderPanel();
    });
    applyView();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', ready, { once: true });
  } else {
    ready();
  }
})();
