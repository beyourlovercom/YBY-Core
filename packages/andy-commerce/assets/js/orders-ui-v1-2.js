/* Orders UI V1.2: event handling INSIDE hidden native Screen Options only.
 * No edits to Orders table, no DOM moving of page controls, no orders queries.
 */
(function () {
  'use strict';
  function ready() {
    const panel = document.getElementById('ac-orders-native-options');
    if (!panel) return;
    const list = panel.querySelector('.ac-orders-column-sort');
    const save = panel.querySelector('.ac-orders-save');
    const result = panel.querySelector('.ac-orders-result');
    if (!list || !save || !result) return;

    function labels() {
      return Array.from(list.children).filter(li => li.matches('li[data-column]'));
    }
    function paintButtons() {
      const rows = labels();
      rows.forEach((row, i) => {
        const up = row.querySelector('[data-move="-1"]');
        const down = row.querySelector('[data-move="1"]');
        if (up) {
          up.disabled = i === 0;
          up.setAttribute('aria-label', '上移 ' + (row.querySelector('.ac-orders-sort-label')?.textContent || ''));
        }
        if (down) {
          down.disabled = i === rows.length - 1;
          down.setAttribute('aria-label', '下移 ' + (row.querySelector('.ac-orders-sort-label')?.textContent || ''));
        }
      });
    }
    list.addEventListener('click', (event) => {
      const button = event.target.closest('button[data-move]');
      if (!button || !list.contains(button)) return;
      event.preventDefault();
      const row = button.closest('li[data-column]');
      if (!row) return;
      const direction = Number(button.dataset.move);
      const other = direction === -1 ? row.previousElementSibling : row.nextElementSibling;
      if (!other || !other.matches('li[data-column]')) return;
      if (direction === -1) list.insertBefore(row, other);
      else list.insertBefore(other, row);
      paintButtons();
      result.textContent = '列顺序已调整，请点击保存布局并刷新';
    });

    save.addEventListener('click', async (event) => {
      event.preventDefault();
      const selected = panel.querySelector('input[name="ac_orders_layout"]:checked');
      if (!selected || !['standard', 'compact', 'cards'].includes(selected.value)) return;
      save.disabled = true;
      result.textContent = '保存中…';
      const params = new URLSearchParams();
      params.set('action', 'andy_commerce_orders_ui_save');
      params.set('nonce', panel.dataset.nonce || '');
      params.set('layout', selected.value);
      labels().forEach((row, i) => params.set('columns[' + i + ']', row.dataset.column));
      try {
        const response = await fetch(panel.dataset.endpoint, {
          method: 'POST', credentials: 'same-origin',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
          body: params.toString()
        });
        const value = await response.json();
        if (!response.ok || !value.success) throw new Error('Save failed');
        result.textContent = '保存成功，正在刷新…';
        window.location.reload(); // Next page is already final before first paint.
      } catch (error) {
        result.textContent = '保存失败，请重试';
        save.disabled = false;
      }
    });
    paintButtons();
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', ready, { once: true });
  else ready();
})();
