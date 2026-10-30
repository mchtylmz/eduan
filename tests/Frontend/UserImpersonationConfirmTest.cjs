const {test} = require('node:test');
const assert = require('node:assert/strict');
const vm = require('node:vm');
const fs = require('node:fs');
function setup(dialog) {
    let submit, prevented=0, sent=0, prompts=0;
    const button={disabled:false};
    const form={dataset:{confirm:'Switch account?',ok:'Enter',cancel:'Cancel'},isConnected:true,querySelector:()=>button,submit:()=>sent++};
    vm.runInNewContext(fs.readFileSync('public/backend/assets/user-impersonation.js','utf8'),{document:{addEventListener:(_,callback)=>submit=callback},Swal:{fire:async()=>{prompts++;return dialog();}}});
    return {form,button,run:(target=form)=>submit({target:{closest:()=>target},preventDefault:()=>prevented++}),sent:()=>sent,prompts:()=>prompts,prevented:()=>prevented};
}
test('cancel leaves the account unchanged and permits retry',async()=>{
    const page=setup(()=>({isConfirmed:false}));await page.run();
    assert.equal(page.sent(),0);assert.equal(page.button.disabled,false);assert.equal(page.form.dataset.busy,'false');
});
test('confirmation submits one native CSRF protected form and disables its button',async()=>{
    const page=setup(()=>({isConfirmed:true}));await page.run();
    assert.equal(page.sent(),1);assert.equal(page.button.disabled,true);assert.equal(page.prevented(),1);
});
test('a second submit cannot open another dialog while one is pending',async()=>{
    let resolve;const page=setup(()=>new Promise(r=>resolve=r));const pending=page.run();await page.run();
    assert.equal(page.prompts(),1);resolve({isConfirmed:false});await pending;
    await page.run(null);assert.equal(page.prompts(),1);
});
