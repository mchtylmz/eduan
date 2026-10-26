const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const source = fs.readFileSync('public/assets/web-push.js', 'utf8');

function screen({stored=false, ios=false, standalone=false, ready=true, provider=false, optedIn=true, registerOK=true, refreshFail=false, pendingDevice=false}={}) {
    const events={}; let requests=0, scripts=0;
    const button={disabled:!ready,addEventListener:(event,fn)=>events.enable=fn};
    const checkbox={checked:false}; const status={textContent:''}; const badge={dataset:{state:'pending'},textContent:'Permission status'};
    const layout={dataset:{}},title={textContent:''},setupTitle={textContent:''},benefits={hidden:false};const deviceFeedback={textContent:''};const guide={hidden:true}, browser={hidden:false}, setup={open:false}, onboarding={hidden:false}, activeHelp={hidden:true};
    let prompts=0;const registrations=[];const subscription={optedIn,id:'mock-subscription',addEventListener:(event,fn)=>events.change=fn,optIn:async()=>{subscription.optedIn=true;}};
    const sdk={setConsentRequired:()=>{},setConsentGiven:()=>{},init:async()=>{},login:async()=>{},User:{PushSubscription:subscription},Notifications:{permission:true,isPushSupported:()=>true,requestPermission:async()=>{prompts++;}}};
    const selectors={'[data-push-layout]':layout,'[data-push-title]':title,'[data-push-setup-title]':setupTitle,'[data-push-benefits]':benefits,'[data-push-device-panel]':refreshFail?{}:null,'[data-push-devices-status]':deviceFeedback,'[data-push-setup]':setup,'[data-push-onboarding]':onboarding,'[data-push-active-help]':activeHelp,'[data-push-enable]':button,'[data-push-consent]':checkbox,'[data-push-status]':status,'[data-push-badge]':badge,'[data-ios-guide]':guide,'[data-browser-guide]':browser,'[data-push-check]':{addEventListener:(event,fn)=>events.check=fn}};
    const page={querySelector:selector=>selectors[selector],querySelectorAll:()=>pendingDevice?[{dataset:{pushDevice:'mock-subscription',pushRevoked:'1'},querySelector:()=>({hidden:true})}]:[]};
    const data={ready,accountKey:'private-id',contextUrl:'/context',registerUrl:'/register',devicesUrl:'/devices',messages:{consent_required:'Consent needed',ios_browser:'Use installed app',service_error:'Unavailable',unavailable_state:'Not available',enabled_state:'Enabled',disabled_state:'Disabled',devices_refresh_error:'Refresh devices again',start_title:'Turn on notifications',active_title:'Your notifications are on',device_title:'This device',manage_settings:'Settings',enable:'Enable'}};
    const context={module:{exports:{}},window:{matchMedia:()=>({matches:standalone}),navigator:{standalone}},navigator:{userAgent:ios?'iPhone':'Chrome',platform:ios?'iPhone':'MacIntel',maxTouchPoints:ios?5:0},localStorage:{getItem:()=>stored?'1':null,setItem:()=>stored=true,removeItem:()=>stored=false},fetch:async(url,options)=>{requests++;if(url==='/register')registrations.push(JSON.parse(options.body));return {ok:provider&&url!=='/devices'&&(url!=='/register'||registerOK),json:async()=>url==='/context'?{config:{},externalId:'member'}:{id:1}}},document:{addEventListener:()=>{},createElement:()=>({}),querySelector:s=>s==='#eduan-web-push-config'?{textContent:JSON.stringify(data)}:s==='[data-web-push-page]'?page:null,querySelectorAll:()=>[],head:{appendChild:()=>{scripts++;queueMicrotask(()=>context.window.OneSignalDeferred[0](sdk));}}},setTimeout,queueMicrotask};
    vm.runInNewContext(source,context);
    return {layout,title,setupTitle,benefits,deviceFeedback,events,button,checkbox,status,badge,guide,browser,context,setup,onboarding,activeHelp,sdk,registrations,prompts:()=>prompts,requests:()=>requests,scripts:()=>scripts};
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

 test('context failure updates the visible state and keeps the retry control usable',async()=>{
 const view=screen();view.checkbox.checked=true;await view.events.enable();
 assert.equal(view.status.textContent,'Unavailable');assert.equal(view.badge.dataset.state,'error');assert.equal(view.badge.textContent,'Not available');assert.equal(view.button.disabled,false);assert.equal(view.scripts(),0);
 });
 test('configuration failure cannot accidentally enable a disabled permission button',async()=>{
 const view=screen({ready:false});view.checkbox.checked=true;await view.events.enable();
 assert.equal(view.button.disabled,true);assert.equal(view.scripts(),0);
 });

const settle=async()=>{await new Promise(r=>setImmediate(r));await new Promise(r=>setImmediate(r));};
test('verified returning device closes setup, hides consent and never requests permission',async()=>{
 const view=screen({stored:true,provider:true});await settle();
 assert.equal(view.badge.dataset.state,'active');assert.equal(view.setup.open,false);assert.equal(view.onboarding.hidden,true);assert.equal(view.activeHelp.hidden,false);assert.equal(view.prompts(),0);assert.equal(view.registrations[0].explicit_consent,false);assert.equal(view.layout.dataset.state,'active');assert.equal(view.title.textContent,'Your notifications are on');assert.equal(view.setupTitle.textContent,'Settings');assert.equal(view.benefits.hidden,true);
});
test('failed backend registration cannot show enabled state or hide consent',async()=>{
 const view=screen({stored:true,provider:true,registerOK:false});await settle();
 assert.equal(view.badge.dataset.state,'error');assert.equal(view.setup.open,true);assert.equal(view.onboarding.hidden,false);assert.equal(view.activeHelp.hidden,true);assert.equal(view.prompts(),0);assert.equal(view.benefits.hidden,false);assert.equal(view.setupTitle.textContent,'Enable');
});
test('an opted-out returning device offers setup without automatically prompting',async()=>{
 const view=screen({stored:true,provider:true,optedIn:false});await settle();
 assert.equal(view.badge.dataset.state,'inactive');assert.equal(view.setup.open,true);assert.equal(view.onboarding.hidden,false);assert.equal(view.prompts(),0);
});
test('explicit consent prepares service then a separate click opens permission and registers',async()=>{
 const view=screen({provider:true,optedIn:false});view.checkbox.checked=true;
 await view.events.enable();assert.equal(view.prompts(),0);assert.equal(view.onboarding.hidden,false);
 await view.events.enable();assert.equal(view.prompts(),1);assert.equal(view.registrations[0].consent,true);assert.equal(view.registrations[0].explicit_consent,true);assert.equal(view.setup.open,false);assert.equal(view.onboarding.hidden,true);
});
test('subscription opt-out restores the setup controls and clears enabled presentation',async()=>{
 const view=screen({stored:true,provider:true});await settle();view.sdk.User.PushSubscription.optedIn=false;view.events.change();
 assert.equal(view.badge.dataset.state,'inactive');assert.equal(view.onboarding.hidden,false);assert.equal(view.button.disabled,false);assert.equal(view.setup.open,true);
});

test('device-list refresh failure preserves verified permission and provides a retry message',async()=>{
 const view=screen({stored:true,provider:true,refreshFail:true});await settle();
 assert.equal(view.badge.dataset.state,'active');assert.equal(view.deviceFeedback.textContent,'Refresh devices again');assert.equal(view.onboarding.hidden,true);
});
test('a re-enabled pending device refreshes its stale revoked row rather than reusing it',async()=>{
 const view=screen({stored:true,provider:true,refreshFail:true,pendingDevice:true});await settle();
 assert.equal(view.requests(),3);assert.equal(view.deviceFeedback.textContent,'Refresh devices again');assert.equal(view.badge.dataset.state,'active');
});
