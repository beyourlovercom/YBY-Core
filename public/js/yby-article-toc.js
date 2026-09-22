(function (root) {
	'use strict';

	var DEFAULTS = {
		title: 'Summary',
		minH2Count: 2,
		floatingBreakpoint: 1200,
		labels: {
			inline: 'Article summary',
			floating: 'Article contents'
		}
	};

	var LEGACY_TOC_SELECTOR = [
		'[data-andy-article-toc]',
		'#toc-spy',
		'.articre_toc',
		'.articre_toc_ul'
	].join(',');

	var CONTENT_ROOT_SELECTORS = [
		'[data-andy-article-content]',
		'.entry-content',
		'.post-content',
		'.article-content',
		'.brxe-post-content',
		'article .content',
		'article'
	];

	var EXCLUSION_SELECTOR = [
		'nav',
		'aside',
		'footer',
		'.related-posts',
		'.related-post',
		'.recommended-posts',
		'.recommended',
		'.author-box',
		'.post-author',
		'.entry-author',
		'.comments-area',
		'#comments',
		'.comment-respond',
		'.comment-form',
		'[data-andy-toc-exclude]'
	].join(',');

	function mergeConfig(input) {
		input = input && typeof input === 'object' ? input : {};
		var labels = input.labels && typeof input.labels === 'object' ? input.labels : {};
		return {
			title: String(input.title || DEFAULTS.title),
			minH2Count: Math.max(2, parseInt(input.minH2Count, 10) || DEFAULTS.minH2Count),
			floatingBreakpoint: Math.max(900, parseInt(input.floatingBreakpoint, 10) || DEFAULTS.floatingBreakpoint),
			labels: {
				inline: String(labels.inline || DEFAULTS.labels.inline),
				floating: String(labels.floating || DEFAULTS.labels.floating)
			}
		};
	}

	function slugify(text) {
		var value = String(text || '').trim().toLowerCase();
		if (value.normalize) {
			value = value.normalize('NFKD').replace(/[\u0300-\u036f]/g, '');
		}
		value = value
			.replace(/[^\p{L}\p{N}]+/gu, '-')
			.replace(/^-+|-+$/g, '')
			.replace(/-+/g, '-');
		return value || 'section';
	}

	function hasCompatibleToc(doc) {
		return !!(doc && doc.querySelector && doc.querySelector(LEGACY_TOC_SELECTOR));
	}

	function findContentRoot(doc) {
		if (!doc || !doc.querySelector) return null;
		for (var i = 0; i < CONTENT_ROOT_SELECTORS.length; i += 1) {
			var node = doc.querySelector(CONTENT_ROOT_SELECTORS[i]);
			if (node) return node;
		}
		return null;
	}

	function isExcludedHeading(heading) {
		return !!(heading && heading.closest && heading.closest(EXCLUSION_SELECTOR));
	}

	function discoverHeadings(contentRoot) {
		if (!contentRoot || !contentRoot.querySelectorAll) return [];
		return Array.prototype.slice.call(contentRoot.querySelectorAll('h2')).filter(function (heading) {
			return !isExcludedHeading(heading) && String(heading.textContent || '').trim() !== '';
		});
	}

	function assignStableIds(headings, doc) {
		var used = Object.create(null);
		if (doc && doc.querySelectorAll) {
			Array.prototype.forEach.call(doc.querySelectorAll('[id]'), function (node) {
				var id = String(node.id || '').trim();
				if (id) used[id] = (used[id] || 0) + 1;
			});
		}

		headings.forEach(function (heading, index) {
			var existing = String(heading.id || '').trim();
			if (existing && used[existing] === 1) {
				heading.classList.add('andy-article-toc-target');
				return;
			}

			if (existing && used[existing] > 1) {
				used[existing] -= 1;
			}

			var base = 'andy-toc-' + slugify(heading.textContent || ('section-' + (index + 1)));
			var candidate = base;
			var suffix = 2;
			while (used[candidate]) {
				candidate = base + '-' + suffix;
				suffix += 1;
			}
			heading.id = candidate;
			used[candidate] = 1;
			heading.classList.add('andy-article-toc-target');
		});
		return headings;
	}

	function createToc(doc, headings, config, mode) {
		var element = doc.createElement(mode === 'floating' ? 'aside' : 'nav');
		element.className = 'andy-article-toc andy-article-toc--' + mode;
		element.setAttribute('data-andy-article-toc', mode);
		element.setAttribute('aria-label', mode === 'floating' ? config.labels.floating : config.labels.inline);

		var title = doc.createElement('p');
		title.className = 'andy-article-toc__title';
		title.textContent = config.title;
		element.appendChild(title);

		var list = doc.createElement('ul');
		list.className = 'andy-article-toc__list';

		headings.forEach(function (heading, index) {
			var item = doc.createElement('li');
			item.className = 'andy-article-toc__item';

			var link = doc.createElement('a');
			link.className = 'andy-article-toc__link';
			link.href = '#' + heading.id;
			link.textContent = String(heading.textContent || '').trim();
			link.setAttribute('data-andy-toc-index', String(index));

			item.appendChild(link);
			list.appendChild(item);
		});

		element.appendChild(list);
		return element;
	}

	function setActiveIndex(tocNodes, index) {
		tocNodes.forEach(function (toc) {
			Array.prototype.forEach.call(toc.querySelectorAll('.andy-article-toc__link'), function (link) {
				var active = parseInt(link.getAttribute('data-andy-toc-index'), 10) === index;
				link.classList.toggle('is-active', active);
				if (active) {
					link.setAttribute('aria-current', 'location');
				} else {
					link.removeAttribute('aria-current');
				}
			});
		});
	}

	function bindSmoothLinks(tocNodes, headings) {
		tocNodes.forEach(function (toc) {
			toc.addEventListener('click', function (event) {
				var link = event.target && event.target.closest ? event.target.closest('.andy-article-toc__link') : null;
				if (!link) return;
				var index = parseInt(link.getAttribute('data-andy-toc-index'), 10);
				var heading = headings[index];
				if (!heading) return;
				event.preventDefault();
				heading.scrollIntoView({ behavior: 'smooth', block: 'start' });
				if (root.history && root.history.replaceState) {
					root.history.replaceState(null, '', '#' + heading.id);
				}
			});
		});
	}

	function createRuntime(win, doc, inputConfig) {
		var config = mergeConfig(inputConfig);
		if (!doc || hasCompatibleToc(doc)) return null;

		var contentRoot = findContentRoot(doc);
		if (!contentRoot) return null;

		var headings = assignStableIds(discoverHeadings(contentRoot), doc);
		if (headings.length < config.minH2Count) return null;

		var inlineToc = createToc(doc, headings, config, 'inline');
		var floatingToc = createToc(doc, headings, config, 'floating');

		headings[0].parentNode.insertBefore(inlineToc, headings[0]);
		doc.body.appendChild(floatingToc);

		var tocNodes = [inlineToc, floatingToc];
		var framePending = false;

		function update() {
			framePending = false;
			var viewportWidth = win.innerWidth || doc.documentElement.clientWidth || 0;
			var rootRect = contentRoot.getBoundingClientRect();
			var scrollY = win.pageYOffset || doc.documentElement.scrollTop || 0;
			var absoluteTop = rootRect.top + scrollY;
			var absoluteBottom = rootRect.bottom + scrollY;
			var topOffset = 100;
			var floatingWidth = 196;
			var gap = 20;
			var desiredLeft = rootRect.left - floatingWidth - gap;
			var inVerticalRange = scrollY + topOffset >= absoluteTop - 40 && scrollY + topOffset < absoluteBottom - 80;
			var canFloat = viewportWidth >= config.floatingBreakpoint && desiredLeft >= 16 && inVerticalRange;

			floatingToc.style.setProperty('--andy-toc-left', Math.round(Math.max(16, desiredLeft)) + 'px');
			floatingToc.classList.toggle('is-visible', canFloat);

			var activeIndex = 0;
			for (var i = 0; i < headings.length; i += 1) {
				if (headings[i].getBoundingClientRect().top <= topOffset + 36) {
					activeIndex = i;
				} else {
					break;
				}
			}
			setActiveIndex(tocNodes, activeIndex);
		}

		function scheduleUpdate() {
			if (framePending) return;
			framePending = true;
			(win.requestAnimationFrame || function (callback) { return win.setTimeout(callback, 16); })(update);
		}

		bindSmoothLinks(tocNodes, headings);
		win.addEventListener('scroll', scheduleUpdate, { passive: true });
		win.addEventListener('resize', scheduleUpdate);
		scheduleUpdate();

		return {
			config: config,
			contentRoot: contentRoot,
			headings: headings,
			inlineToc: inlineToc,
			floatingToc: floatingToc,
			update: update
		};
	}

	var api = {
		mergeConfig: mergeConfig,
		slugify: slugify,
		hasCompatibleToc: hasCompatibleToc,
		discoverHeadings: discoverHeadings,
		assignStableIds: assignStableIds,
		createRuntime: createRuntime
	};

	if (typeof module === 'object' && module.exports) {
		module.exports = api;
		return;
	}

	root.AndyArticleTOC = api;

	function init() {
		if (!root.document || hasCompatibleToc(root.document)) return;
		createRuntime(root, root.document, root.AndyArticleTOCConfig || DEFAULTS);
	}

	if (root.document.readyState === 'loading') {
		root.document.addEventListener('DOMContentLoaded', init, { once: true });
	} else {
		init();
	}
})(typeof window !== 'undefined' ? window : globalThis);
