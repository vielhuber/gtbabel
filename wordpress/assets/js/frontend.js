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
            if (document.querySelector('.gtbabel-frontend-editor') === null) {
                document.body.insertAdjacentHTML(
                    'beforeend',
                    `
                    <div class="gtbabel-frontend-editor">
                        <iframe class="gtbabel-frontend-editor__iframe"></iframe>
                    </div>
                    `
                );
            }
            document.querySelector('.gtbabel-frontend-editor__iframe').setAttribute('src', el.getAttribute('href'));

            e.preventDefault();
        }
    });
});
