(() => {
    const helpers = {
        isIOS: (agent, platform, touches) => /iPad|iPhone|iPod/.test(agent) || (platform === 'MacIntel' && touches > 1),
        standalone: () => window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true,
    };
    if (typeof module !== 'undefined') module.exports = helpers;
    if (typeof document === 'undefined') return;
    const element = document.querySelector('#eduan-web-push-config');
    if (!element) return;
    const data = JSON.parse(element.textContent);
    const page = document.querySelector('[data-web-push-page]');
    const button = page?.querySelector('[data-push-enable]');
    const checkbox = page?.querySelector('[data-push-consent]');
    const status = page?.querySelector('[data-push-status]');
    const badge = page?.querySelector('[data-push-badge]');
    const key = 'eduan-web-push-consent:' + data.accountKey;
    let sdk, loading, currentRecord, saving = false, explicitConsent = false, registered = false;
    const message = name => {
        if(status)status.textContent = data.messages[name] || data.messages.service_error;
        const states={allowed:['active','enabled_state'],denied:['inactive','disabled_state'],loading:['loading','loading_state'],prepare:['pending','ready_state'],service_error:['error','unavailable_state'],unsupported:['unavailable','unavailable_state']};
        if(badge && states[name]){badge.dataset.state=states[name][0];badge.textContent=data.messages[states[name][1]];}
    };
    const restoreButton = () => {if(button)button.disabled = !data.ready || browserTabIOS || registered;};
    const storedConsent = () => {try{return localStorage.getItem(key)==='1';}catch{return false;}};
    const consent = granted => {try{if(granted)localStorage.setItem(key,'1');else localStorage.removeItem(key);}catch{}};
    const ios = helpers.isIOS(navigator.userAgent,navigator.platform,navigator.maxTouchPoints);
    const browserTabIOS = ios && !helpers.standalone();
    if(page){page.querySelector('[data-ios-guide]').hidden=!browserTabIOS;page.querySelector('[data-browser-guide]').hidden=browserTabIOS;if(browserTabIOS){button.disabled=true;message('ios_browser');}if(checkbox)checkbox.checked=storedConsent();}
    async function request(url,body){
        const response=await fetch(url,{method:body?'POST':'GET',credentials:'same-origin',headers:{'Accept':'application/json',...(body?{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]')?.content}: {})},...(body?{body:JSON.stringify(body)}:{})});
        if(!response.ok)throw Error('request_failed');return response.json();
    }
    function load(){
        if(loading)return loading;
        loading=(async()=>{
            const context=await request(data.contextUrl);
            await new Promise((resolve,reject)=>{
                const script=document.createElement('script');script.src='https://cdn.onesignal.com/sdks/web/v16/OneSignalSDK.page.js';script.defer=true;
                script.onerror=reject;
                window.OneSignalDeferred=window.OneSignalDeferred||[];
                window.OneSignalDeferred.push(async os=>{try{os.setConsentRequired(true);await os.init(context.config);os.setConsentGiven(true);await os.login(context.externalId);sdk=os;resolve();}catch(e){reject(e);}});
                document.head.appendChild(script);
            });
            sdk.User.PushSubscription.addEventListener('change',()=>{if(sdk.User.PushSubscription.optedIn){sync().catch(()=>message('service_error'));}else{registered=false;consent(false);message('denied');if(button)button.textContent=data.messages.enable;restoreButton();}});
            return sdk;
        })().catch(error=>{loading=null;throw error;});
        return loading;
    }
    async function sync(){
        if(saving)return;
        if(!sdk?.User.PushSubscription.optedIn || !sdk.User.PushSubscription.id){registered=false;message('denied');restoreButton();return;}
        saving=true;
        try{
            const browser=ios?'iPhone / iPad':(/Safari/.test(navigator.userAgent)&& !/Chrome|Chromium|Edg/.test(navigator.userAgent)?'Safari':'Web browser');
            const record=await request(data.registerUrl,{subscription_id:sdk.User.PushSubscription.id,label:browser,consent:true,explicit_consent:explicitConsent});
            currentRecord=record.id;registered=true;explicitConsent=false;consent(true);message('allowed');
            if(button)button.textContent=data.messages.enabled_state;
            restoreButton();
        }finally{saving=false;}
    }
    button?.addEventListener('click',async()=>{
        if(!checkbox.checked){message('consent_required');return;}
        explicitConsent=true;
        if(!sdk){
            button.disabled=true;message('loading');
            try{await load();consent(true);if(sdk.User.PushSubscription.optedIn){await sync();}else{button.textContent=data.messages.request;message('prepare');}}
            catch{message('service_error');}finally{restoreButton();}
            return;
        }
        if(!sdk.Notifications.isPushSupported()){message('unsupported');return;}
        if(typeof Notification!=='undefined' && Notification.permission==='denied'){message('denied');return;}
        button.disabled=true;
        try{
            // This call is made directly from the user's second click. No SDK download/login precedes it.
            await sdk.Notifications.requestPermission();
            if(sdk.Notifications.permission){await sdk.User.PushSubscription.optIn();await sync();}else message('denied');
        }catch{message('service_error');}finally{restoreButton();}
    });
    page?.querySelector('[data-push-check]')?.addEventListener('click',async()=>{try{if(storedConsent()){await load();await sync();}else message('consent_required');}catch{message('service_error');}});
    document.querySelectorAll('[data-push-revoke]').forEach(form=>form.addEventListener('submit',async event=>{
        if(sdk?.User.PushSubscription.id!==form.dataset.subscription)return;
        event.preventDefault();consent(false);
        try{await sdk.User.PushSubscription.optOut();sdk.setConsentGiven(false);}catch{}
        form.submit();
    }));
    document.querySelectorAll('a').forEach(link=>{if(!/\/logout(?:\?|$)/.test(link.href))return;link.addEventListener('click',async event=>{
        if(!sdk)return;event.preventDefault();consent(false);
        await Promise.race([sdk.logout().catch(()=>{}),new Promise(resolve=>setTimeout(resolve,1500))]);
        location.assign(link.href);
    });});
    // Existing consent permits silent identity synchronization; it never opens a permission dialog.
    if(data.ready&&storedConsent()&&!browserTabIOS)load().then(sync).catch(()=>message('service_error'));
})();
