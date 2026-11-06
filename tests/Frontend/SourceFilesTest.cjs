const fs=require('node:fs');
const vm=require('node:vm');
const assert=require('node:assert/strict');
const test=require('node:test');
const path=require('node:path');
const script=fs.readFileSync(path.join(__dirname,'../../public/assets/source-links.js'),'utf8');

test('source links preserve return query and always open a separate tab',()=>{
    const handlers={};
    const link={href:'https://hypotenuse.test/sources/12345678-1234-1234-1234-123456789abc/lesson',target:'',rel:''};
    const external={href:'https://other.test/sources/12345678-1234-1234-1234-123456789abc/lesson',target:'',rel:''};
    const document={readyState:'complete',body:{},querySelectorAll:()=>[link,external],addEventListener:(name,callback)=>handlers[name]=callback};
    vm.runInNewContext(script,{URL,document,location:{href:'https://hypotenuse.test/test/abc/start?lang=nl&page=2',origin:'https://hypotenuse.test',pathname:'/test/abc/start',search:'?lang=nl&page=2'},MutationObserver:class{observe(){}}});
    assert.equal(link.target,'_blank');assert.equal(link.rel,'noopener');
    assert.equal(new URL(link.href).searchParams.get('return_to'),'/test/abc/start?lang=nl&page=2');
    assert.equal(external.target,'');
    handlers.click({target:{closest:()=>link}});
    assert.equal(new URL(link.href).searchParams.getAll('return_to').length,1);
});

test('file action formatter escapes titles and URLs, never emits stored markup',()=>{
    const root={dataset:{messages:JSON.stringify({view:'View',copy:'Copy',remove:'Delete'})},addEventListener(){},querySelector:()=>null};
    const window={};
    vm.runInNewContext(fs.readFileSync(path.join(__dirname,'../../public/backend/assets/source-files.js'),'utf8'),{window,document:{querySelector:selector=>selector==='[data-source-manager]'?root:{content:'csrf-test'}}});
    const html=window.eduanSourceActionsFormatter(null,{view_url:'/sources/a/one?x=" onclick="bad',delete_url:'/admin/files/a" onmouseover="bad'});
    assert.ok(html.includes('&quot;'));assert.ok(!html.includes(' onclick="bad'));assert.ok(!html.includes(' onmouseover="bad'));
    assert.ok(html.includes('target="_blank"'));assert.ok(html.includes('rel="noopener"'));
});
