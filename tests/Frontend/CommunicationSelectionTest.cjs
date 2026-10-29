const {test} = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

function tablePage() {
    let morph, visible = false, element = {}, selectedIds = [], count = '', scrolls = 0, restored;
    let rows = [{id:2},{id:3}];
    const handlers = {}, events = {}, documentEvents = {}, requests = [];
    const queryRoot = {dataset:{tableParameters:JSON.stringify({search:'cohort',recipient_type:'user',limit:15,offset:15})}};
    const table = {on:(event,fn)=>handlers[event]=fn,bootstrapTable:(action,data)=>{
        if(action==='getData')return rows;
        if(action==='checkBy')restored=data.values;
    }};
    const root = {dataset:{selectedRecipients:'[]'},getAttribute:()=> 'component',scrollIntoView:()=>scrolls++,querySelector:selector=>{
        if(selector==='#communication-recipients-table')return visible ? element : null;
        if(selector==='[data-selection-count]')return {dataset:{template:'Selected :count'},set textContent(value){count=value}};
    }};
    const wire = {hook:(event,callback)=>morph=callback,find:()=>({set:(field,ids)=>selectedIds=ids,call:async(method,filters)=>{requests.push({method,filters});}})};
    vm.runInNewContext(fs.readFileSync('public/backend/assets/communications.js','utf8'), {
        document:{readyState:'loading',addEventListener:(event,callback)=>documentEvents[event]=callback,querySelector:selector=>selector==='[data-selection-count]'?root.querySelector(selector):root,getElementById:()=>element},
        window:{Livewire:wire,addEventListener:(event,callback)=>events[event]=callback},Livewire:wire,jQuery:()=>table,
    });
    documentEvents.DOMContentLoaded();
    return {handlers,events,selected:()=>[...selectedIds],count:()=>count,scrolls:()=>scrolls,restored:()=>[...restored],
        click:button=>documentEvents.click({target:{closest:()=>button}}),requests,
        show:(ids=[])=>{visible=true;element={closest:()=>queryRoot};root.dataset.selectedRecipients=JSON.stringify(ids);morph();},morph:()=>morph()};
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

test('select all sends the applied search and filters across pages, then restores all server-selected ids',async()=>{
 const page=tablePage();page.show([1]);
 const button={disabled:false,isConnected:true,hasAttribute:()=>false};
 page.click(button);await new Promise(resolve=>setImmediate(resolve));
 assert.equal(button.disabled,false);
 assert.equal(page.requests[0].method,'selectFilteredRecipients');
 assert.deepEqual(JSON.parse(JSON.stringify(page.requests[0].filters)),{search:'cohort',recipient_type:'user',limit:15,offset:15});
 const ids=Array.from({length:100},(_,i)=>i+1);
 page.events['communication-selection-updated']({detail:{ids}});
 assert.deepEqual(page.restored(),ids);assert.equal(page.count(),'Selected 100');
 const clear={disabled:false,hasAttribute:()=>true};page.click(clear);
 assert.deepEqual(page.selected(),[]);assert.equal(page.count(),'Selected 0');
});
