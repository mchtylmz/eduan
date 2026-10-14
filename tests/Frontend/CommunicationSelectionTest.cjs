const {test} = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

function tablePage() {
    let morph, visible = false, element = {}, selectedIds = [], count = '', scrolls = 0, restored;
    let rows = [{id:2},{id:3}];
    const handlers = {}, events = {}, documentEvents = {};
    const table = {on:(event,fn)=>handlers[event]=fn,bootstrapTable:(action,data)=>{
        if(action==='getData')return rows;
        if(action==='checkBy')restored=data.values;
    }};
    const root = {dataset:{selectedRecipients:'[]'},getAttribute:()=> 'component',scrollIntoView:()=>scrolls++,querySelector:selector=>{
        if(selector==='#communication-recipients-table')return visible ? element : null;
        if(selector==='[data-selection-count]')return {dataset:{template:'Selected :count'},set textContent(value){count=value}};
    }};
    const wire = {hook:(event,callback)=>morph=callback,find:()=>({set:(field,ids)=>selectedIds=ids})};
    vm.runInNewContext(fs.readFileSync('public/backend/assets/communications.js','utf8'), {
        document:{readyState:'loading',addEventListener:(event,callback)=>documentEvents[event]=callback,querySelector:()=>root},
        window:{Livewire:wire,addEventListener:(event,callback)=>events[event]=callback},Livewire:wire,jQuery:()=>table,
    });
    documentEvents.DOMContentLoaded();
    return {handlers,events,selected:()=>[...selectedIds],count:()=>count,scrolls:()=>scrolls,restored:()=>[...restored],
        show:(ids=[])=>{visible=true;element={};root.dataset.selectedRecipients=JSON.stringify(ids);morph();},morph:()=>morph()};
}

test('recipient table binds lazily after the wizard opens, once per rendered table',()=>{
 const page=tablePage();assert.equal(Object.keys(page.handlers).length,0);
 page.show();assert.equal(Object.keys(page.handlers).length,5);
 page.handlers['check.bs.table'](null,{id:1});page.morph();assert.deepEqual(page.selected(),[1]);
});
test('clearing the visible page retains recipients selected on another page',()=>{
 const page=tablePage();page.show();page.handlers['check.bs.table'](null,{id:1});
 page.handlers['check-all.bs.table'](null,[{id:2},{id:3}]);assert.deepEqual(page.selected(),[1,2,3]);assert.equal(page.count(),'Selected 3');
 page.handlers['uncheck-all.bs.table']();assert.deepEqual(page.selected(),[1]);
});
test('going back restores the validated server selection and step transitions return to the form top',()=>{
 const page=tablePage();page.show([1,4]);page.handlers['load-success.bs.table']();assert.deepEqual(page.restored(),[1,4]);
 page.events['communication-step-changed']();assert.equal(page.scrolls(),1);
 page.events['communication-compose-started']();assert.equal(page.scrolls(),2);
 page.show([]);page.handlers['check.bs.table'](null,{id:9});assert.deepEqual(page.selected(),[9]);
});
