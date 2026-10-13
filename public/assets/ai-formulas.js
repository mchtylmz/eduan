(function () {
    'use strict';

    function render(root) {
        const formulas = root.querySelectorAll('[data-ai-latex]');
        for (const element of formulas) {
            const source = element.dataset.aiLatex;
            if (element.dataset.renderedLatex === source || !window.katex) continue;
            element.dataset.renderedLatex = source;
            try {
                window.katex.render(source, element, {
                    displayMode: true,
                    throwOnError: true,
                    trust: false,
                    maxExpand: 500,
                    maxSize: 20,
                    output: 'htmlAndMathml'
                });
                element.dataset.latexState = 'ready';
            } catch (error) {
                // Keep the readable source; never insert a failed formula as HTML or retry an API.
                element.textContent = source;
                element.dataset.latexState = 'invalid';
            }
        }
    }

    function start() {
        render(document);
        const observer = new MutationObserver(records => {
            for (const record of records) {
                if (record.type === 'attributes') {
                    render(record.target.parentElement || document);
                    continue;
                }
                for (const node of record.addedNodes) {
                    if (node.nodeType !== 1) continue;
                    if (node.matches('[data-ai-latex]')) render(node.parentElement || document);
                    else render(node);
                }
            }
        });
        observer.observe(document.body, { childList: true, subtree: true, attributes: true, attributeFilter: ['data-ai-latex'] });
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start, { once: true });
    else start();
})();
