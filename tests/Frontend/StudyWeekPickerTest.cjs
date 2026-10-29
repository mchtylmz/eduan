const {test} = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

function pickerPage(locale) {
    const events = {};
    let href = 'http://localhost/admin/study?week=2026-10-28';
    let labelTarget;
    const input = {id:'study-week', value:'2026-10-24', dataset:{locale},getAttribute:()=> 'study-week-help',closest:()=>({addEventListener:()=>{}})};
    const parseDate = value => value instanceof Date ? new Date(value) : new Date(value + 'T00:00:00');
    const formatDate = (date,format,code) => format === 'Y-m-d' ? `${date.getFullYear()}-${String(date.getMonth()+1).padStart(2,'0')}-${String(date.getDate()).padStart(2,'0')}` : `${date.getDate()} ${new Intl.DateTimeFormat(code, {month:'long'}).format(date)} ${date.getFullYear()}`;
    function flatpickr(element, options) {
        const picker = {selectedDates:[parseDate(options.defaultDate)],altInput:{setAttribute:()=>{}},formatDate:(date,format)=>options.formatDate(date,format,locale),setDate(value,trigger=false){
            this.selectedDates = [parseDate(value)];
            element.value = this.formatDate(this.selectedDates[0],options.dateFormat);
            this.altInput.value = this.formatDate(this.selectedDates[0],options.altFormat);
            if (trigger) options.onChange(this.selectedDates,element.value,this);
        }};
        element._flatpickr = picker;
        options.onReady(picker.selectedDates,element.value,picker);
        return picker;
    }
    flatpickr.parseDate = parseDate;flatpickr.formatDate = formatDate;
    const document = {readyState:'loading',addEventListener:(name,fn)=>events[name]=fn,querySelectorAll:()=>[input],querySelector:selector=>selector.startsWith('label')?{setAttribute:(_,value)=>labelTarget=value}:null};
    vm.runInNewContext(fs.readFileSync('public/backend/assets/study-panel.js','utf8'),{document,Date,URL,flatpickr,setTimeout,window:{flatpickr,location:{get href(){return href;}},history:{replaceState:(_,__,url)=>href=String(url)}}});
    events.DOMContentLoaded();
    return {input,href:()=>href,labelTarget:()=>labelTarget};
}
for (const locale of ['tr','nl','en','fr']) test(`week display survives shared table date restoration in ${locale}`,()=>{
    const page = pickerPage(locale), picker = page.input._flatpickr;
    assert.equal(page.input.value,'2026-10-24');
    assert.equal(new URL(page.href()).searchParams.get('week'),'2026-10-24');
    assert.equal(page.labelTarget(),'study-week-display');
    picker.setDate('2026-10-29',true);
    assert.equal(page.input.value,'2026-10-24');
    assert.match(picker.altInput.value,/24 .*2026 — 30 .*2026/);
    picker.setDate(page.input.value,false);
    assert.match(picker.altInput.value,/24 .*2026 — 30 .*2026/);
});
