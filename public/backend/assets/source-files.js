(() => {
    'use strict';
    const root = document.querySelector('[data-source-manager]');
    if (!root) return;
    const m = JSON.parse(root.dataset.messages);
    const escape = value => String(value ?? '').replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;').replaceAll('"','&quot;').replaceAll("'",'&#039;');
    const token = document.querySelector('meta[name="csrf-token"]').content;
    window.eduanSourceActionsFormatter = (value,row) => `${row.view_url ? `<a class="btn btn-sm btn-info me-1" target="_blank" rel="noopener" href="${escape(row.view_url)}">${escape(m.view)}</a><button class="btn btn-sm btn-alt-primary me-1" data-source-copy-url="${escape(row.view_url)}">${escape(m.copy)}</button>` : ''}${row.delete_url ? `<button class="btn btn-sm btn-danger" data-source-delete="${escape(row.delete_url)}">${escape(m.remove)}</button>` : ''}`;
    window.eduanSourceCardFormatter = row => {
        const icon = row.type === 'PDF' ? 'fa-file-pdf' : row.type === 'HTML' ? 'fa-file-code' : ['JPG','JPEG','PNG','WEBP'].includes(row.type) ? 'fa-file-image' : 'fa-file';
        const tone = {ready:'success',uploading:'warning',failed:'danger'}[row.status_key] || 'secondary';
        const title = row.view_url ? `<a href="${escape(row.view_url)}" target="_blank" rel="noopener">${escape(row.title)}</a>` : escape(row.title);
        return `<article class="admin-user-card admin-source-card">
            <div class="admin-user-card-heading"><span class="admin-user-card-icon bg-primary-lighter text-primary"><i class="fa ${icon}" aria-hidden="true"></i></span><div><span class="source-card-type">${escape(row.type)}</span><h3>${title}</h3></div></div>
            <span class="badge bg-${tone}-lighter text-${tone} source-card-state">${escape(row.status)}</span>
            <dl class="source-card-date"><dt>${escape(m.created_at)}</dt><dd>${escape(row.created_at)}</dd></dl>
            <div class="source-card-actions">${window.eduanSourceActionsFormatter(null,row)}</div>
        </article>`;
    };
    const refresh = () => jQuery('#source-files-table').bootstrapTable('refresh');
    const request = async (url, options={}) => {
        const response = await fetch(url,{...options,credentials:'same-origin',headers:{'Accept':'application/json','X-CSRF-TOKEN':token,...options.headers}});
        let data; try {data = await response.json();} catch {throw new Error(m.failed);}
        if (!response.ok) throw new Error(Object.values(data.errors || {}).flat().join(' ') || data.message || m.failed);
        return data;
    };
    const copy = async url => { await navigator.clipboard.writeText(url); };
    root.addEventListener('click', async event => {
        const button = event.target.closest('[data-source-delete], [data-source-copy-url]');
        if (!button) return;
        const error = root.querySelector('[data-source-list-error]');
        try {
            if (button.dataset.sourceCopyUrl) { await copy(button.dataset.sourceCopyUrl); return; }
            if (!confirm(m.confirm_delete)) return;
            button.disabled=true;
            await request(button.dataset.sourceDelete,{method:'DELETE'});
            refresh();
        } catch { error.textContent=m.failed; }
        finally { button.disabled=false; }
    });
    const form = root.querySelector('[data-source-upload-form]');
    if (!form) return;
    const submit=form.querySelector('[data-source-submit]'), fallback=form.querySelector('[data-source-fallback]');
    const progress=form.querySelector('[data-source-progress]'), status=form.querySelector('[data-source-status]'), error=form.querySelector('[data-source-error]');
    const progressRegion=form.querySelector('[data-source-progress-region]'), percent=form.querySelector('[data-source-percent]'), selectedLabel=form.querySelector('[data-source-selected-file]');
    let prepared=null, selected=null, busy=false;
    const setProgress = value => {progress.value=value;percent.textContent=`${value}%`;};
    const onProgress = event => {if(event.lengthComputable) {setProgress(Math.round(event.loaded/event.total*100));if(progress.value===100)status.textContent=m.checking;}};
    const lock = value => {busy=value; submit.disabled=value; form.elements.file.disabled=value; form.elements.title.disabled=value; fallback.disabled=value;};
    const upload = (url, body, headers={}) => new Promise((resolve,reject) => {
        const xhr=new XMLHttpRequest(); xhr.open('POST',url); xhr.setRequestHeader('Accept','application/json'); xhr.setRequestHeader('X-CSRF-TOKEN',token);
        Object.entries(headers).forEach(([key,value]) => xhr.setRequestHeader(key,value));
        xhr.upload.onprogress=onProgress;
        xhr.onload=() => { let data; try {data=JSON.parse(xhr.responseText);} catch {reject(new Error(m.failed));return;} xhr.status>=200&&xhr.status<300 ? resolve(data) : reject(new Error(Object.values(data.errors||{}).flat().join(' ')||data.message||m.failed)); };
        xhr.onerror=()=>reject(new Error(m.failed)); xhr.send(body);
    });
    const cloudUpload = uploadInfo => new Promise((resolve,reject) => {
        const xhr=new XMLHttpRequest(); xhr.open('PUT',uploadInfo.url);
        Object.entries(uploadInfo.headers||{}).forEach(([key,value])=> {if(!['host','content-length'].includes(key.toLowerCase())) xhr.setRequestHeader(key,Array.isArray(value)?value.join(','):value);});
        xhr.upload.onprogress=onProgress;
        xhr.onload=()=>xhr.status>=200&&xhr.status<300?resolve():reject(new Error(m.cloud_failed)); xhr.onerror=()=>reject(new Error(m.cloud_failed)); xhr.send(selected);
    });
    const finishUrl=()=>root.dataset.finish.replace('SOURCE_CODE',prepared.code);
    const done = data => {form.querySelector('[data-source-link]').value=data.url; form.querySelector('[data-source-preview]').href=data.url; form.querySelector('[data-source-success]').classList.remove('d-none');status.textContent=m.status_ready;setProgress(100);prepared=null;refresh();};
    const local = async allowFallback => {const body=new FormData();body.append('file',selected);body.append('fallback',allowFallback?'1':'0');done(await upload(finishUrl(),body));};
    const fail = message => {error.textContent=message;error.classList.remove('d-none');status.textContent='';progressRegion.classList.add('d-none');};
    form.addEventListener('submit',async event=> {
        event.preventDefault();if(busy)return;
        selected=form.elements.file.files[0];if(!selected)return;
        lock(true);error.classList.add('d-none');fallback.classList.add('d-none');form.querySelector('[data-source-success]').classList.add('d-none');setProgress(0);progressRegion.classList.remove('d-none');prepared=null;status.textContent=m.uploading;
        try {
            prepared=await request(root.dataset.prepare,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({title:form.elements.title.value,name:selected.name,size:selected.size})});
            if(prepared.disk==='r2') {
                try {if(prepared.cloud_error)throw new Error(m.cloud_failed);await cloudUpload(prepared.upload);status.textContent=m.checking;done(await request(finishUrl(),{method:'POST'}));}
                catch {fail(m.cloud_failed);fallback.classList.remove('d-none');}
            } else await local(false);
        } catch(e) {fail(e.message);}
        finally {lock(false);}
    });
    form.addEventListener('change',()=>{if(!busy){prepared=null;selected=null;fallback.classList.add('d-none');form.querySelector('[data-source-success]').classList.add('d-none');progressRegion.classList.add('d-none');error.classList.add('d-none');selectedLabel.textContent=form.elements.file.files[0]?.name || m.no_file_selected;}});
    fallback.addEventListener('click',async()=> {if(busy||!prepared||!selected)return;lock(true);error.classList.add('d-none');progressRegion.classList.remove('d-none');setProgress(0);status.textContent=m.uploading;try {await local(true);fallback.classList.add('d-none');}catch(e){fail(e.message);}finally{lock(false);}});
    form.querySelector('[data-source-copy]').addEventListener('click',()=>copy(form.querySelector('[data-source-link]').value).catch(()=>fail(m.failed)));
    root.querySelector('#source-upload').addEventListener('hide.bs.offcanvas',event=> {if(busy)event.preventDefault();});
    root.querySelector('#source-upload').addEventListener('hidden.bs.offcanvas',()=> {form.reset();prepared=null;selected=null;setProgress(0);progressRegion.classList.add('d-none');error.classList.add('d-none');fallback.classList.add('d-none');form.querySelector('[data-source-success]').classList.add('d-none');selectedLabel.textContent=m.no_file_selected;status.textContent='';});
})();
