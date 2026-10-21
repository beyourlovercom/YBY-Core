/* Orders UI V1.1: three bands — utility / native Orders / display framework. */
(function () {
  'use strict';
  function init() {
    const table = document.querySelector('.wrap table.andy-commerce-orders-table');
    const toolbar = document.querySelector('.andy-commerce-orders-toolbar');
    if (!table || !toolbar || document.querySelector('.andy-commerce-orders-utility-slot')) return;
    const wrap = table.closest('.wrap');
    const form = table.closest('form');
    const settings = toolbar.querySelector('.andy-commerce-orders-config-button');
    const panel = toolbar.nextElementSibling?.classList.contains('andy-commerce-orders-panel')
      ? toolbar.nextElementSibling : null;
    if (!settings || !panel) return;
    const utilitySlot = document.createElement('div');
    utilitySlot.className = 'andy-commerce-orders-utility-slot';
    utilitySlot.appendChild(settings);
    const screenMeta = document.querySelector('#screen-meta-links');
    if (screenMeta) screenMeta.appendChild(utilitySlot);
    else {
      const utilityRow = document.createElement('div');
      utilityRow.className = 'andy-commerce-orders-utility-row';
      utilityRow.appendChild(utilitySlot);
      (wrap || toolbar.parentNode).insertBefore(utilityRow, (wrap || toolbar.parentNode).firstChild);
    }

    // Native form controls are moved as existing DOM nodes, never cloned.
    if (form && wrap) {
      const title = wrap.querySelector('h1.wp-heading-inline');
      const add = wrap.querySelector('.page-title-action');
      const statuses = wrap.querySelector('.subsubsub');
      const search = form.querySelector('.search-box');
      const top = form.querySelector('.tablenav.top');
      const bulk = top?.querySelector('.bulkactions');
      const otherActions = top ? Array.from(top.querySelectorAll('.actions'))
        .filter(node => node !== bulk && !node.closest('.bulkactions')) : [];
      const pages = top?.querySelector('.tablenav-pages');
      const bar = document.createElement('section');
      bar.className = 'andy-commerce-orders-native-bar';
      bar.setAttribute('aria-label', 'Orders 操作');
      const group = (className, nodes) => {
        const existing = nodes.filter(Boolean);
        if (!existing.length) return;
        const el = document.createElement('div');
        el.className = className;
        existing.forEach(node => el.appendChild(node));
        bar.appendChild(el);
      };
      group('andy-commerce-orders-title-group', [title, add]);
      group('andy-commerce-orders-status-group', [statuses]);
      group('andy-commerce-orders-bulk-group', [bulk]);
      group('andy-commerce-orders-search-group', [search]);
      if (otherActions.length) {
        const toggle = document.createElement('button');
        const filters = document.createElement('div');
        toggle.type = 'button';
        toggle.className = 'button andy-commerce-orders-filter-toggle';
        toggle.textContent = '更多筛选 ▾';
        toggle.setAttribute('aria-expanded', 'false');
        filters.className = 'andy-commerce-orders-extra-filters';
        filters.hidden = true;
        filters.id = 'andy-commerce-orders-extra-filters';
        toggle.setAttribute('aria-controls', filters.id);
        otherActions.forEach(node => filters.appendChild(node));
        toggle.addEventListener('click', () => {
          filters.hidden = !filters.hidden;
          toggle.setAttribute('aria-expanded', String(!filters.hidden));
          toggle.textContent = filters.hidden ? '更多筛选 ▾' : '收起筛选 ▴';
        });
        bar.append(toggle, filters);
      }
      group('andy-commerce-orders-pages-group', [pages]);
      const anchor = top || table;
      anchor.parentNode.insertBefore(bar, anchor);
      if (top && !top.querySelector('a,button,input,select,.actions,.tablenav-pages')) {
        top.classList.add('andy-commerce-orders-empty-topnav');
      }
    }

    toolbar.classList.add('andy-commerce-orders-framework-bar');
    toolbar.setAttribute('aria-label', '列表展示模式');
    const label = document.createElement('span');
    label.className = 'andy-commerce-orders-layout-label';
    label.textContent = '展示方式';
    toolbar.insertBefore(label, toolbar.firstChild);

    panel.classList.add('andy-commerce-orders-floating-panel');
    document.body.appendChild(panel);
    function position() {
      if (panel.hidden) return;
      const rect = settings.getBoundingClientRect();
      const width = panel.offsetWidth;
      panel.style.left = Math.max(8, Math.min(window.innerWidth - width - 8, rect.right - width)) + 'px';
      panel.style.top = Math.max(8, rect.bottom + 6) + 'px';
    }
    settings.addEventListener('click', position); // Original V1 click handler runs first.
    window.addEventListener('resize', position, { passive: true });
    window.addEventListener('scroll', position, { passive: true });
    document.addEventListener('keydown', event => {
      if (event.key !== 'Escape' || panel.hidden) return;
      panel.hidden = true;
      settings.setAttribute('aria-expanded', 'false');
      settings.focus();
    });
    document.addEventListener('click', event => {
      if (panel.hidden || panel.contains(event.target) || utilitySlot.contains(event.target)) return;
      panel.hidden = true;
      settings.setAttribute('aria-expanded', 'false');
    });
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init, { once: true });
  else init();
})();
