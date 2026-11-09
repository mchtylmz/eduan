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

test('file cards show compact safe metadata and keep ready or failed actions',()=>{
    const root={dataset:{messages:JSON.stringify({view:'View',copy:'Copy',remove:'Delete',created_at:'Uploaded'})},addEventListener(){},querySelector:()=>null};
    const window={};
    vm.runInNewContext(fs.readFileSync(path.join(__dirname,'../../public/backend/assets/source-files.js'),'utf8'),{window,document:{querySelector:selector=>selector==='[data-source-manager]'?root:{content:'csrf-test'}}});
    const row={title:'<img src=x onerror=bad()>',type:'PDF',status:'Ready',status_key:'ready',created_at:'09/11/2026 12:00',name:'Hidden original name',uploader:'Hidden uploader',size:'99 MB',view_url:'/sources/demo',delete_url:'/admin/files/demo'};
    const html=window.eduanSourceCardFormatter(row);
    assert.ok(html.includes('&lt;img'));assert.ok(!html.includes('<img'));
    assert.ok(html.includes('fa-file-pdf'));assert.ok(html.includes('text-success'));
    assert.ok(html.includes('data-source-copy-url'));assert.ok(html.includes('data-source-delete'));
    assert.ok(!html.includes(row.name));assert.ok(!html.includes(row.uploader));assert.ok(!html.includes(row.size));
    const failed=window.eduanSourceCardFormatter({...row,status_key:'failed',status:'Failed',view_url:null});
    assert.ok(failed.includes('text-danger'));assert.ok(!failed.includes('data-source-copy-url'));assert.ok(failed.includes('data-source-delete'));
    const unknown=window.eduanSourceCardFormatter({...row,status_key:'" onclick="bad',type:'" onerror="bad'});
    assert.ok(unknown.includes('text-secondary'));assert.ok(!unknown.includes(' onclick="bad'));assert.ok(!unknown.includes(' onerror="bad'));
});
