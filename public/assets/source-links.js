(() => {
    'use strict';
    const prepare = link => {
        if (!link) return;
        const url = new URL(link.href, location.href);
        if (url.origin !== location.origin || !/^\/sources\/[a-f0-9-]{36}\/[^/]+$/.test(url.pathname)) return;
        if (!location.pathname.startsWith('/sources/')) url.searchParams.set('return_to', location.pathname + location.search);
        link.href = url.href;
        link.target = '_blank';
        link.rel = 'noopener';
    };
    const boot = () => {
        document.querySelectorAll('a[href]').forEach(prepare);
        new MutationObserver(changes => changes.forEach(change => change.addedNodes.forEach(node => {
            if (node.nodeType !== 1) return;
            if (node.matches('a[href]')) prepare(node);
            node.querySelectorAll('a[href]').forEach(prepare);
        }))).observe(document.body, {childList:true, subtree:true});
    };
    document.addEventListener('click', event => prepare(event.target.closest?.('a[href]')), true);
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot, {once:true});
    else boot();
})();
