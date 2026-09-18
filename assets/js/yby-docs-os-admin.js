(function () {
	'use strict';

	var editorId = 'yby_docs_content_editor';

	function syncEditor() {
		if (window.tinymce && tinymce.get(editorId)) {
			tinymce.get(editorId).save();
		}
	}

	function editorContent() {
		if (window.tinymce && tinymce.get(editorId) && !tinymce.get(editorId).isHidden()) {
			return tinymce.get(editorId).getContent();
		}
		var textarea = document.getElementById(editorId);
		return textarea ? textarea.value : '';
	}

	function switchEditorMode(mode) {
		var nativeBox = document.querySelector('[data-yby-editor-native]');
		var previewBox = document.querySelector('[data-yby-editor-preview]');
		if (!nativeBox || !previewBox) { return; }
		document.querySelectorAll('[data-yby-editor-mode]').forEach(function (button) {
			button.classList.toggle('is-active', button.getAttribute('data-yby-editor-mode') === mode);
		});

		if (mode === 'preview') {
			syncEditor();
			nativeBox.hidden = true;
			previewBox.hidden = false;
			var frame = previewBox.querySelector('[data-yby-editor-preview-frame]');
			if (frame) {
				frame.srcdoc = '<!doctype html><meta charset="utf-8"><style>body{font:16px/1.7 -apple-system,BlinkMacSystemFont,Segoe UI,sans-serif;padding:28px;color:#24352e}img{max-width:100%;height:auto}table{border-collapse:collapse;width:100%}td,th{border:1px solid #ddd;padding:8px}</style>' + editorContent();
			}
			return;
		}

		previewBox.hidden = true;
		nativeBox.hidden = false;
		if (window.switchEditors && typeof switchEditors.go === 'function') {
			switchEditors.go(editorId, mode === 'html' ? 'html' : 'tmce');
		}
	}
	document.addEventListener('click', function (event) {
		var copyLink = event.target.closest('.yby-copy-link');
		if (copyLink) {
			event.preventDefault();
			var value = copyLink.getAttribute('data-copy') || '';
			if (value && navigator.clipboard) {
				navigator.clipboard.writeText(value).then(function () {
					var original = copyLink.textContent;
					copyLink.textContent = '已复制';
					setTimeout(function () { copyLink.textContent = original; }, 1200);
				});
			}
			return;
		}

		var modeButton = event.target.closest('[data-yby-editor-mode]');
		if (modeButton) {
			event.preventDefault();
			switchEditorMode(modeButton.getAttribute('data-yby-editor-mode'));
			return;
		}
		var draftButton = event.target.closest('[data-yby-save-draft]');
		if (draftButton) {
			event.preventDefault();
			var status = document.getElementById('yby-docs-status');
			var form = document.querySelector('[data-yby-docs-editor-form]');
			if (status) { status.value = 'draft'; }
			syncEditor();
			if (form) { form.submit(); }
		}
	});

	document.addEventListener('submit', function (event) {
		if (event.target.matches('[data-yby-docs-editor-form]')) {
			syncEditor();
		}
	});

	var tree = document.querySelector('[data-yby-category-tree]');
	var orderInput = document.querySelector('[data-yby-category-order]');
	var dragged = null;
	function syncCategoryOrder() {
		if (!tree || !orderInput) { return; }
		orderInput.value = Array.prototype.map.call(tree.querySelectorAll('[data-yby-term-id]'), function (row) { return row.getAttribute('data-yby-term-id'); }).join(',');
	}
	if (tree) {
		tree.addEventListener('dragstart', function (event) { dragged = event.target.closest('[data-yby-term-id]'); if (dragged) { dragged.classList.add('is-dragging'); } });
		tree.addEventListener('dragover', function (event) { var target = event.target.closest('[data-yby-term-id]'); if (!dragged || !target || target === dragged || target.getAttribute('data-parent') !== dragged.getAttribute('data-parent')) { return; } event.preventDefault(); var rect = target.getBoundingClientRect(); tree.insertBefore(dragged, event.clientY < rect.top + rect.height / 2 ? target : target.nextSibling); syncCategoryOrder(); });
		tree.addEventListener('dragend', function () { if (dragged) { dragged.classList.remove('is-dragging'); } dragged = null; syncCategoryOrder(); });
	}
	var categorySearch = document.querySelector('[data-yby-category-search]');
	if (categorySearch && tree) { categorySearch.addEventListener('input', function () { var q = categorySearch.value.trim().toLowerCase(); tree.querySelectorAll('.yby-tree-row').forEach(function (row) { if (row.classList.contains('yby-tree-root')) { return; } row.style.display = !q || (row.getAttribute('data-name') || '').indexOf(q) !== -1 ? '' : 'none'; }); }); }

	var selectAll = document.querySelector('[data-yby-select-all]');
	if (selectAll) { selectAll.addEventListener('change', function () { document.querySelectorAll('[data-yby-doc-select]').forEach(function (box) { box.checked = selectAll.checked; }); }); }

	var title = document.getElementById('yby-docs-title');
	var slug = document.getElementById('yby-docs-slug');
	if (title && slug) {
		title.addEventListener('blur', function () {
			if (!slug.value.trim()) {
				slug.value = title.value.trim().toLowerCase()
					.replace(/[^a-z0-9\s-]/g, '')
					.replace(/\s+/g, '-').replace(/-+/g, '-');
			}
		});
	}
}());
