document.addEventListener('DOMContentLoaded', function() {
    if (document.querySelector('.gtbabel--settings') !== null) {
        document.addEventListener('click', e => {
            let el = e.target.closest('.gtbabel__repeater-add');
            if (el) {
                el.closest('.gtbabel__repeater')
                    .querySelector('.gtbabel__repeater-list')
                    .insertAdjacentHTML(
                        'beforeend',
                        el.closest('.gtbabel__repeater').querySelector('.gtbabel__repeater-listitem:last-child')
                            .outerHTML
                    );
                el.closest('.gtbabel__repeater')
                    .querySelectorAll('.gtbabel__repeater-listitem:last-child input')
                    .forEach(function(el__value) {
                        el__value.value = '';
                    });
                e.preventDefault();
            }
        });

        document.addEventListener('click', e => {
            let el = e.target.closest('.gtbabel__repeater-remove');
            if (el) {
                if (el.closest('.gtbabel__repeater').querySelectorAll('.gtbabel__repeater-listitem').length > 1) {
                    el.parentNode.remove();
                } else {
                    el.parentNode.querySelectorAll('.gtbabel__input').forEach(function(el__value) {
                        el__value.value = '';
                    });
                }
                e.preventDefault();
            }
        });

        document.addEventListener('click', e => {
            let el = e.target.closest('.gtbabel__submit--auto-translate');
            if (el) {
                if (document.querySelector('.gtbabel__auto-translate') !== null) {
                    document.querySelector('.gtbabel__auto-translate').remove();
                }
                el.insertAdjacentHTML(
                    'afterend',
                    '<div class="gtbabel__auto-translate">' + el.getAttribute('data-loading-text') + '</div>'
                );
                el.remove();
                fetchNextAutoTranslate(el.getAttribute('href'));
                e.preventDefault();
            }
        });

        document.addEventListener('click', e => {
            let el = e.target.closest('.gtbabel__submit--reset');
            if (el) {
                let answer = prompt(el.getAttribute('data-question'));
                if (answer !== 'REMOVE') {
                    e.preventDefault();
                }
            }
        });
    }

    if (document.querySelector('.gtbabel--trans') !== null) {
        document.addEventListener('change', e => {
            let el = e.target.closest('.gtbabel__input--on-change');
            if (el) {
                e.target.setAttribute('name', e.target.getAttribute('data-name'));
            }
        });
    }
});

function fetchNextAutoTranslate(url) {
    fetch(url)
        .then(response => {
            if (response.status == 200 || response.status == 304) {
                return response.text();
            }
            return null;
        })
        .catch(v => v)
        .then(response => {
            // something went wrong, try again
            if (response === null || response === undefined || response.trim() === '') {
                fetchNextAutoTranslate(url);
            }
            let html = new DOMParser().parseFromString(response, 'text/html');
            if (document.querySelector('.gtbabel__auto-translate') !== null) {
                document.querySelector('.gtbabel__auto-translate').innerHTML = html.querySelector(
                    '.gtbabel__auto-translate'
                ).innerHTML;
            }
            if (document.querySelector('.gtbabel__api-stats') !== null) {
                document.querySelector('.gtbabel__api-stats').innerHTML = html.querySelector(
                    '.gtbabel__api-stats'
                ).innerHTML;
            }
            if (html.querySelector('.gtbabel__auto-translate-next') !== null) {
                fetchNextAutoTranslate(html.querySelector('.gtbabel__auto-translate-next').getAttribute('href'));
            }
        });
}
