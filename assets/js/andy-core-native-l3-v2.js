/* Andy Core V2: presentation-only tertiary rail. No network, state or writes. */
(() => {
	'use strict';
	function initialize() {
		const config = window.AndyCoreNativeL3V2;
		if (!config || !Array.isArray(config.items) || !config.items.length) return;
		const content = document.getElementById('wpbody-content');
		if (!content || content.querySelector('.andy-core-l3-v2')) return;
		const isProjectStudio = config.page === 'yby-project-studio';
		let wrap = content.querySelector(':scope > .wrap');
		// Project Studio can render through a legacy callback without a top-level .wrap.
		// Wrap only the existing controller output; never duplicate or replace its handlers.
		if (!wrap && isProjectStudio) {
			const nodes = [...content.children].filter(el =>
				!el.matches('#screen-meta, #screen-meta-links, .notice, .updated, .error'));
			if (nodes.length) {
				wrap = document.createElement('div');
				wrap.className = 'wrap andy-core-studio-legacy-wrap';
				content.insertBefore(wrap, nodes[0]);
				for (const node of nodes) wrap.appendChild(node);
			}
		}
		if (!wrap || !wrap.parentElement) return;
		// Never move the WP block editor, payment forms or unknown screens.
		if (document.body.classList.contains('block-editor-page') ||
			wrap.querySelector('#editor, #poststuff #post-body')) return;

		const shell = document.createElement('div');
		shell.className = 'andy-core-l3-v2';
		const rail = document.createElement('nav');
		rail.className = 'andy-core-l3-v2__rail';
		rail.setAttribute('aria-label', '当前模块功能');
		const heading = document.createElement('div');
		heading.className = 'andy-core-l3-v2__label';
		heading.textContent = '功能导航';
		rail.appendChild(heading);
		const url = new URL(window.location.href);
		for (const item of config.items) {
			if (!item || typeof item.title !== 'string' || typeof item.url !== 'string') continue;
			const link = document.createElement('a');
			link.className = 'andy-core-l3-v2__link';
			link.textContent = item.title;
			link.href = item.url;
			try {
				const target = new URL(item.url, url.href);
				const currentPage = url.searchParams.get('page') || '';
				const tab = url.searchParams.get('tab') || '';
				const exact = target.pathname === url.pathname &&
					(target.searchParams.get('page') || '') === currentPage &&
					(target.searchParams.get('tab') || '') === tab;
				if (exact) link.setAttribute('aria-current', 'page');
			} catch (_) {
				// A malformed route is never reconstructed from user data.
				continue;
			}
			rail.appendChild(link);
		}
		if (rail.querySelectorAll('a').length === 0) return;

		// Replace only the redundant top TAB NAVIGATION of known Andy pages.
		// The full original controller DOM remains untouched and operational.
		const existingTabs = wrap.querySelector(':scope > nav.nav-tab-wrapper');
		if (existingTabs && /^(andy-core-leads|andy-core-newsletter|andy-core-email|andy-commerce|yby-core)$/.test(config.page)) {
			existingTabs.hidden = true;
			existingTabs.style.setProperty('display', 'none', 'important');
			existingTabs.setAttribute('aria-hidden', 'true');
		}
		const view = document.createElement('div');
		view.className = 'andy-core-l3-v2__view';
		wrap.parentElement.insertBefore(shell, wrap);
		shell.appendChild(rail);
		shell.appendChild(view);
		view.appendChild(wrap);
	}
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initialize, { once: true });
	} else {
		initialize();
	}
})();
