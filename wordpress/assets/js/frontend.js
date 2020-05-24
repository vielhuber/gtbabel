const ready = new Promise(resolve => {
    if (document.readyState !== 'loading') {
        return resolve();
    } else {
        document.addEventListener('DOMContentLoaded', () => {
            return resolve();
        });
    }
});
ready.then(() => {
    document.addEventListener('click', e => {
        let el = e.target.closest('#wp-admin-bar-gtbabel-frontend-editor .ab-item');
        if (el) {
            e.preventDefault();
        }
    });
});
