document.addEventListener('DOMContentLoaded', function() {
    document.addEventListener('click', e => {
        let el = e.target.closest('.gtbabel__repeater-add');
        if (el) {
            el.closest('.gtbabel__repeater')
                .querySelector('.gtbabel__repeater-list')
                .insertAdjacentHTML(
                    'beforeend',
                    el.closest('.gtbabel__repeater').querySelector('.gtbabel__repeater-listitem:last-child').outerHTML
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
                '<div class="gtbabel__auto-translate" data-error-text="' +
                    el.getAttribute('data-error-text') +
                    '">' +
                    el.getAttribute('data-loading-text') +
                    '</div>'
            );
            let href = el.getAttribute('data-href');
            if (
                document.querySelector('#gtbabel_delete_unused') !== null &&
                document.querySelector('#gtbabel_delete_unused').checked === true
            ) {
                href += '&gtbabel_delete_unused=1';
            }
            el.remove();
            fetchNextAutoTranslate(href);
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

    document.addEventListener('change', e => {
        let el = e.target.closest('.gtbabel__input--on-change');
        if (el) {
            e.target.setAttribute('name', e.target.getAttribute('data-name'));
        }
    });

    document.addEventListener('change', e => {
        let el = e.target.closest('.gtbabel__input--inverse');
        if (el) {
            e.target.nextElementSibling.value = e.target.checked === true ? '0' : '1';
        }
    });

    document.addEventListener('submit', e => {
        if (e.target.closest('.gtbabel--trans form')) {
            let form = e.target.closest('.gtbabel--trans form'),
                els = null;
            els = form.querySelectorAll(
                '.gtbabel__input--submit-unchecked:not(:checked)[name]:not([name$=\'[]\']):not([disabled="disabled"])'
            );
            if (els.length > 0) {
                els.forEach(el => {
                    if (
                        el.previousElementSibling === null ||
                        el.previousElementSibling.getAttribute('type') !== 'hidden' ||
                        el.previousElementSibling.value != '0'
                    ) {
                        el.insertAdjacentHTML(
                            'beforebegin',
                            '<input type="hidden" value="0" name="' + el.getAttribute('name') + '" />'
                        );
                    }
                });
            }
        }
    });
});

function fetchNextAutoTranslate(url, tries = 0) {
    if (tries > 10) {
        if (document.querySelector('.gtbabel__auto-translate') !== null) {
            document.querySelector('.gtbabel__auto-translate').innerHTML =
                '<span class="gtbabel__auto-translate-error">' +
                document.querySelector('.gtbabel__auto-translate').getAttribute('data-error-text') +
                '</span>';
        }
        return;
    }
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
                fetchNextAutoTranslate(url, tries + 1);
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
