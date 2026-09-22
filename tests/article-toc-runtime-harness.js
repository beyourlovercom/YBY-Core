const fs = require('fs');
const path = require('path');
const assert = (condition, message) => {
  if (!condition) {
    console.error('FAIL:', message);
    process.exit(1);
  }
};

const api = require('../public/js/yby-article-toc.js');
const css = fs.readFileSync(path.join(__dirname, '../public/css/yby-article-toc.css'), 'utf8');
const js = fs.readFileSync(path.join(__dirname, '../public/js/yby-article-toc.js'), 'utf8');

const cfg = api.mergeConfig({
  title: '',
  minH2Count: 1,
  floatingBreakpoint: 700,
  labels: {}
});
assert(cfg.title === 'Summary', 'empty title should fall back to Summary');
assert(cfg.minH2Count === 2, 'minimum H2 count must clamp to 2');
assert(cfg.floatingBreakpoint === 900, 'floating breakpoint must clamp to 900');

assert(api.slugify('Hello World') === 'hello-world', 'ASCII slugify mismatch');
assert(api.slugify('  Café & Irrigation  ') === 'cafe-irrigation', 'diacritic slugify mismatch');
assert(api.slugify('滴灌 系统') === '滴灌-系统', 'Unicode/CJK slugify mismatch');
assert(api.slugify('---') === 'section', 'empty slug fallback mismatch');

const legacyDoc = {
  querySelector(selector) {
    return selector.includes('#toc-spy') ? { id: 'toc-spy' } : null;
  }
};
assert(api.hasCompatibleToc(legacyDoc) === true, 'legacy TOC marker must block duplicate runtime');
assert(api.hasCompatibleToc({ querySelector() { return null; } }) === false, 'clean page must not be treated as duplicate');

function classList() {
  const values = new Set();
  return {
    add(value) { values.add(value); },
    contains(value) { return values.has(value); }
  };
}

const h1 = {
  id: 'existing-id',
  textContent: 'Existing Heading',
  classList: classList(),
  closest() { return null; }
};
const h2 = {
  id: '',
  textContent: 'Pump Selection Guide',
  classList: classList(),
  closest() { return null; }
};
const h3 = {
  id: '',
  textContent: 'Pump Selection Guide',
  classList: classList(),
  closest() { return null; }
};
const excluded = {
  id: '',
  textContent: 'Related Posts',
  classList: classList(),
  closest(selector) { return selector.includes('.related-posts') ? {} : null; }
};

const contentRoot = {
  querySelectorAll(selector) {
    assert(selector === 'h2', 'runtime must discover H2 only');
    return [h1, h2, h3, excluded];
  }
};
const discovered = api.discoverHeadings(contentRoot);
assert(discovered.length === 3, 'template/related heading exclusion failed');
assert(discovered.includes(h1) && discovered.includes(h2) && discovered.includes(h3), 'valid content H2 discovery mismatch');

const fakeDoc = {
  querySelectorAll(selector) {
    if (selector === '[id]') return [{ id: 'existing-id' }, { id: 'andy-toc-pump-selection-guide' }];
    return [];
  }
};
api.assignStableIds(discovered, fakeDoc);
assert(h1.id === 'existing-id', 'existing unique heading ID must be preserved');
assert(h2.id === 'andy-toc-pump-selection-guide-2', 'generated anchor must avoid document ID collisions');
assert(h3.id === 'andy-toc-pump-selection-guide-3', 'generated anchors must be unique and deterministic');
assert(h1.classList.contains('andy-article-toc-target'), 'preserved heading must receive target class');

assert(js.includes("data-andy-article-toc"), 'canonical TOC marker missing');
assert(js.includes("aria-current"), 'scroll-spy accessibility state missing');
assert(js.includes("requestAnimationFrame"), 'scroll spy must be requestAnimationFrame throttled');
assert(js.includes("scrollIntoView"), 'smooth heading navigation missing');
assert(js.includes("headings[0].parentNode.insertBefore(inlineToc, headings[0])"), 'Inline Summary must be inserted immediately before first H2');
assert(js.includes("doc.body.appendChild(floatingToc)"), 'Floating TOC renderer missing');
assert(js.includes("viewportWidth >= config.floatingBreakpoint"), 'runtime breakpoint gate missing');
assert(js.includes("desiredLeft >= 16"), 'floating TOC must fail closed when there is insufficient left-side space');
assert(js.includes("LEGACY_TOC_SELECTOR"), 'legacy duplicate guard missing');
assert(js.includes("EXCLUSION_SELECTOR"), 'template heading exclusion contract missing');

assert(css.includes('position: fixed'), 'Floating TOC must use fixed positioning');
assert(css.includes('--andy-toc-left'), 'Floating TOC dynamic left-position variable missing');
assert(css.includes('.andy-article-toc--floating.is-visible'), 'Floating TOC visibility gate missing');
assert(css.includes('@media (max-width: 767px)'), 'mobile defensive media rule missing');
assert(css.includes('display: none !important'), 'mobile floating TOC must be forcibly hidden');
assert(css.includes('.andy-article-toc--inline'), 'Inline Summary CSS missing');
assert(css.includes('--andy-toc-accent'), 'theme color token seam missing');
assert(css.includes('--andy-toc-radius'), 'theme radius token seam missing');

console.log('PASS article-toc-runtime-harness');
