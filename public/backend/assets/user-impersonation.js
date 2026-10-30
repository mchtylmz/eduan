(() => {
    document.addEventListener('submit', async event => {
        const form = event.target.closest('[data-account-switch]');
        if (!form) return;
        event.preventDefault();
        if (form.dataset.busy === 'true') return;
        form.dataset.busy = 'true';
        try {
            const result = await Swal.fire({icon:'warning', text:form.dataset.confirm,
                showCancelButton:true, confirmButtonText:form.dataset.ok, cancelButtonText:form.dataset.cancel});
            if (result.isConfirmed && form.isConnected) {
                form.querySelector('button[type=submit]').disabled = true;
                form.submit();
            }
        } finally {form.dataset.busy = 'false';}
    });
})();
