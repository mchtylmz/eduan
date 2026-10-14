const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const source = fs.readFileSync('public/assets/web-push.js', 'utf8');

function screen({stored=false, ios=false, standalone=false, ready=true}={}) {
    const events={}; let requests=0, scripts=0;
    const button={disabled:!ready,addEventListener:(event,fn)=>events.enable=fn};
    const checkbox={checked:false}; const status={textContent:''}; const badge={dataset:{state:'pending'},textContent:'Permission status'};
    const guide={hidden:true}, browser={hidden:false};
    const selectors={'[data-push-enable]':button,'[data-push-consent]':checkbox,'[data-push-status]':status,'[data-push-badge]':badge,'[data-ios-guide]':guide,'[data-browser-guide]':browser,'[data-push-check]':{addEventListener:(event,fn)=>events.check=fn}};
    const page={querySelector:selector=>selectors[selector]};
    const data={ready,accountKey:'private-id',contextUrl:'/context',messages:{consent_required:'Consent needed',ios_browser:'Use installed app',service_error:'Unavailable',unavailable_state:'Not available',enabled_state:'Enabled',disabled_state:'Disabled'}};
    const context={module:{exports:{}},window:{matchMedia:()=>({matches:standalone}),navigator:{standalone}},navigator:{userAgent:ios?'iPhone':'Chrome',platform:ios?'iPhone':'MacIntel',maxTouchPoints:ios?5:0},localStorage:{getItem:()=>stored?'1':null},fetch:async()=>{requests++;return {ok:false}},document:{querySelector:s=>s==='#eduan-web-push-config'?{textContent:JSON.stringify(data)}:s==='[data-web-push-page]'?page:null,querySelectorAll:()=>[],head:{appendChild:()=>scripts++}},setTimeout};
    vm.runInNewContext(source,context);
    return {events,button,checkbox,status,badge,guide,browser,context,requests:()=>requests,scripts:()=>scripts};
}

test('iPhone and desktop-mode iPad detection does not misclassify a Mac',()=>{
 const module={exports:{}};vm.runInNewContext(source,{module});
 assert.equal(module.exports.isIOS('iPhone','iPhone',1),true);
 assert.equal(module.exports.isIOS('Safari','MacIntel',5),true);
 assert.equal(module.exports.isIOS('Safari','MacIntel',0),false);
});
test('a member without prior consent causes no SDK or context request',()=>{
 const view=screen();assert.equal(view.requests(),0);assert.equal(view.scripts(),0);
});
test('permission button requires explicit consent before requesting anything',async()=>{
 const view=screen();await view.events.enable();assert.equal(view.status.textContent,'Consent needed');assert.equal(view.requests(),0);
});
test('iOS browser tab shows installation guide and never silently loads SDK',()=>{
 const view=screen({ios:true,stored:true});assert.equal(view.guide.hidden,false);assert.equal(view.browser.hidden,true);assert.equal(view.button.disabled,true);assert.equal(view.requests(),0);
});
test('stored consent may silently check context but context failure never downloads SDK',async()=>{
 const view=screen({stored:true});await new Promise(r=>setImmediate(r));assert.equal(view.requests(),1);assert.equal(view.scripts(),0);assert.equal(view.status.textContent,'Unavailable');
});
test('selection is retained across pages and clearing a page keeps other selected recipients',()=>{
 let domReady;const handlers={},windowEvents={};let selectedIds=[],count='';let visible=[{id:2},{id:3}];
 const table={on:(event,fn)=>handlers[event]=fn,bootstrapTable:(action)=>action==='getData'?visible:null};
 const counter={dataset:{template:'Selected :count'},set textContent(value){count=value}};
 const root={getAttribute:()=> 'component',querySelector:()=>counter};
 const context={Set,document:{addEventListener:(e,fn)=>domReady=fn,querySelector:()=>root},window:{addEventListener:(e,fn)=>windowEvents[e]=fn},jQuery:()=>table,Livewire:{find:()=>({set:(key,ids)=>selectedIds=ids})}};
 vm.runInNewContext(fs.readFileSync('public/backend/assets/communications.js','utf8'),context);domReady();
 handlers['check.bs.table'](null,{id:1});handlers['check-all.bs.table'](null,visible);
 assert.deepEqual([...selectedIds],[1,2,3]);assert.equal(count,'Selected 3');
 handlers['uncheck-all.bs.table']();assert.deepEqual([...selectedIds],[1]);
 windowEvents['communication-created']();assert.deepEqual([...selectedIds],[]);assert.equal(count,'Selected 0');
});

 test('context failure updates the visible state and keeps the retry control usable',async()=>{
 const view=screen();view.checkbox.checked=true;await view.events.enable();
 assert.equal(view.status.textContent,'Unavailable');assert.equal(view.badge.dataset.state,'error');assert.equal(view.badge.textContent,'Not available');assert.equal(view.button.disabled,false);assert.equal(view.scripts(),0);
 });
 test('configuration failure cannot accidentally enable a disabled permission button',async()=>{
 const view=screen({ready:false});view.checkbox.checked=true;await view.events.enable();
 assert.equal(view.button.disabled,true);assert.equal(view.scripts(),0);
 });
