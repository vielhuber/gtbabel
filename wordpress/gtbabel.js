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
});
