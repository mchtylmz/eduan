const fs = require('node:fs');
const vm = require('node:vm');
const assert = require('node:assert/strict');
const sandbox = {
    window: {location: {origin: 'http://localhost'}, addEventListener() {}},
    document: {readyState: 'loading', addEventListener() {}}, URL,
};
vm.runInNewContext(fs.readFileSync('public/backend/assets/server-table.js', 'utf8'), sandbox);
const format = sandbox.window.eduanTableLatexImageFormatter;
const html = format('/uploads/latex/formula.svg', {formula: '"><script>alert(1)</script>'});
assert.match(html, /class="latex-ai-image bg-white rounded px-2"/);
assert.match(html, /http:\/\/localhost\/uploads\/latex\/formula.svg/);
assert.match(html, /&lt;script&gt;/);
assert.doesNotMatch(html, /<script>/);
assert.match(html, /rel="noopener"/);
assert.equal(format('javascript:alert(1)', {formula: 'x'}), '—');
assert.equal(format('data:image/svg+xml,<svg/>', {formula: 'x'}), '—');
assert.match(format('', {formula: 'x'}), /text-muted/);
assert.match(sandbox.window.eduanTableImageFormatter('/image.png', {code: 'A'}), /class="admin-learning-image"/);
console.log('Latex formatter: 9 assertions passed');
