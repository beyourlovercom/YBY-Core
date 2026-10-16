'use strict';
/** Syntax + side-effect contract for the in-page tertiary rail. */
const fs = require('node:fs');
const vm = require('node:vm');
const assert = require('node:assert/strict');
const path = require('node:path');
const root = path.join(__dirname, '..');
const script = fs.readFileSync(path.join(root, 'assets/js/andy-core-native-l3-v2.js'), 'utf8');
const style = fs.readFileSync(path.join(root, 'assets/css/andy-core-native-l3-v2.css'), 'utf8');
new vm.Script(script, { filename: 'andy-core-native-l3-v2.js' });
assert.match(script, /document\.createElement\('nav'\)/, 'L3 uses a true navigation element');
assert.match(script, /\.className = 'andy-core-l3-v2__rail'/, 'L3 in right content area');
assert.match(script, /aria-current/, 'selected page is marked');
assert.match(script, /aria-label/, 'accessible navigation heading');
assert.match(script, /block-editor-page/, 'native Gutenberg editing remains isolated');
assert.doesNotMatch(script, /\b(fetch|XMLHttpRequest|localStorage|sessionStorage)\s*\(/, 'no network or browser persistence');
assert.match(style, /grid-template-columns:\s*184px/, 'desktop L3 only inside right work area');
assert.match(style, /max-width:\s*782px/, 'mobile responsive L3');
const context = {
	window: {},
	document: { readyState: 'complete', getElementById: () => null }
};
vm.runInNewContext(script, context); // no configured data -> safely no DOM edits
assert.equal(Object.keys(context.window).length, 0, 'does not create runtime globals');
console.log('PASS andy-core-native-l3-v2-harness');
