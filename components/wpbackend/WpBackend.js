import WysiwygEditor from './../wysiwygeditor/WysiwygEditor';
import Tooltips from './../tooltips/Tooltips';

export default class WpBackend {
    init() {
        document.addEventListener('DOMContentLoaded', () => {
            this.initRepeater();
            this.initAutoTranslate();
            this.initAutoGrab();
            this.initSearchMainUrlReset();
            this.initFormResetQuestion();
            this.initFileUpload();
            this.initVideoChange();
            this.initFormChange();
            this.initFormInverse();
            this.initSubmitUnchecked();
            this.initWysiwyg();
            this.initWizardAutoTranslationChange();
            this.initTooltips();
        });
    }

    initRepeater() {
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
                    .querySelector('.gtbabel__repeater-listitem:last-child')
                    .classList.remove('gtbabel__repeater-listitem--default');
                el.closest('.gtbabel__repeater')
                    .querySelector('.gtbabel__repeater-listitem:last-child')
                    .classList.remove('gtbabel__repeater-listitem--missing');
                el.closest('.gtbabel__repeater')
                    .querySelectorAll('.gtbabel__repeater-listitem:last-child input')
                    .forEach(el__value => {
                        el__value.value = '';
                        if (!el__value.hasAttribute('disabled')) {
                            el__value.removeAttribute('readonly');
                        }
                        if (el__value.hasAttribute('data-name')) {
                            el__value.setAttribute('name', el__value.getAttribute('data-name'));
                            el__value.removeAttribute('data-name');
                        }
                    });
                e.preventDefault();
            }
        });

        document.addEventListener('click', e => {
            let el = e.target.closest('.gtbabel__repeater-action--remove');
            if (el) {
                if (el.closest('.gtbabel__repeater').querySelectorAll('.gtbabel__repeater-listitem').length > 1) {
                    el.parentNode.remove();
                } else {
                    el.parentNode.querySelectorAll('.gtbabel__input').forEach(el__value => {
                        el__value.value = '';
                    });
                }
                e.preventDefault();
            }
        });

        document.addEventListener('click', e => {
            let el = e.target.closest('.gtbabel__repeater-action--enable, .gtbabel__repeater-action--disable');
            if (el) {
                el.closest('.gtbabel__repeater-listitem')
                    .querySelectorAll('.gtbabel__input')
                    .forEach(el2 => {
                        if (el.classList.contains('gtbabel__repeater-action--enable')) {
                            el.closest('.gtbabel__repeater-listitem').classList.remove(
                                'gtbabel__repeater-listitem--missing'
                            );
                            el2.setAttribute('name', el2.getAttribute('data-name'));
                            el2.removeAttribute('data-name');
                        } else {
                            el.closest('.gtbabel__repeater-listitem').classList.add(
                                'gtbabel__repeater-listitem--missing'
                            );
                            el2.setAttribute('data-name', el2.getAttribute('name'));
                            el2.removeAttribute('name');
                        }
                    });
                e.preventDefault();
            }
        });
    }

    initAutoTranslate() {
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
                if (
                    document.querySelector('#gtbabel_auto_set_discovered_strings_checked') === null || // on wizard, if checkbox is not available
                    document.querySelector('#gtbabel_auto_set_discovered_strings_checked').checked === true
                ) {
                    href += '&gtbabel_auto_set_discovered_strings_checked=1';
                }
                el.remove();
                this.fetchNextAutoTranslate(href);
                e.preventDefault();
            }
        });
    }

    fetchNextAutoTranslate(url, tries = 0) {
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
            .catch(() => {
                return null;
            })
            .then(response => {
                let html = null;
                if (response !== null || response !== undefined || response != '') {
                    html = new DOMParser().parseFromString(response, 'text/html');
                }
                // something went wrong, try again
                if (html === null || html.querySelector('.gtbabel__auto-translate') === null) {
                    setTimeout(() => {
                        this.fetchNextAutoTranslate(url, tries + 1);
                    }, 3000);
                } else {
                    if (
                        document.querySelector('.gtbabel__auto-translate') !== null &&
                        html.querySelector('.gtbabel__auto-translate') !== null
                    ) {
                        document.querySelector('.gtbabel__auto-translate').innerHTML = html.querySelector(
                            '.gtbabel__auto-translate'
                        ).innerHTML;
                    }
                    if (
                        document.querySelector('.gtbabel__stats-log') !== null &&
                        html.querySelector('.gtbabel__stats-log') !== null
                    ) {
                        document.querySelector('.gtbabel__stats-log').innerHTML = html.querySelector(
                            '.gtbabel__stats-log'
                        ).innerHTML;
                    }
                    if (html.querySelector('.gtbabel__auto-translate-next') !== null) {
                        this.fetchNextAutoTranslate(
                            html.querySelector('.gtbabel__auto-translate-next').getAttribute('href')
                        );
                    }
                }
            });
    }

    initAutoGrab() {
        document.addEventListener('click', e => {
            let el = e.target.closest('.gtbabel__submit--auto-grab');
            if (el) {
                if (document.querySelector('.gtbabel__auto-grab') !== null) {
                    document.querySelector('.gtbabel__auto-grab').remove();
                }
                el.insertAdjacentHTML(
                    'afterend',
                    '<div class="gtbabel__auto-grab" data-error-text="' +
                        el.getAttribute('data-error-text') +
                        '">' +
                        el.getAttribute('data-loading-text') +
                        '</div>'
                );
                let href = el.getAttribute('data-href');
                if (
                    document.querySelector('#gtbabel_auto_grab_url') !== null &&
                    document.querySelector('#gtbabel_auto_grab_url').value != ''
                ) {
                    href += '&gtbabel_auto_grab_url=' + document.querySelector('#gtbabel_auto_grab_url').value;
                }
                if (
                    document.querySelector('#gtbabel_auto_grab_dry_run') !== null &&
                    document.querySelector('#gtbabel_auto_grab_dry_run').checked === true
                ) {
                    href += '&gtbabel_auto_grab_dry_run=1';
                }
                el.remove();
                this.fetchNextAutoGrab(href);
                e.preventDefault();
            }
        });
    }

    fetchNextAutoGrab(url, tries = 0) {
        if (tries > 10) {
            if (document.querySelector('.gtbabel__auto-grab') !== null) {
                document.querySelector('.gtbabel__auto-grab').innerHTML =
                    '<span class="gtbabel__auto-grab-error">' +
                    document.querySelector('.gtbabel__auto-grab').getAttribute('data-error-text') +
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
            .catch(() => {
                return null;
            })
            .then(response => {
                let html = null;
                if (response !== null || response !== undefined || response != '') {
                    html = new DOMParser().parseFromString(response, 'text/html');
                }
                // something went wrong, try again
                if (html === null || html.querySelector('.gtbabel__auto-grab') === null) {
                    setTimeout(() => {
                        this.fetchNextAutoGrab(url, tries + 1);
                    }, 3000);
                } else {
                    if (
                        document.querySelector('.gtbabel__auto-grab') !== null &&
                        html.querySelector('.gtbabel__auto-grab') !== null
                    ) {
                        document.querySelector('.gtbabel__auto-grab').innerHTML = html.querySelector(
                            '.gtbabel__auto-grab'
                        ).innerHTML;
                    }
                    if (html.querySelector('.gtbabel__auto-grab-next') !== null) {
                        this.fetchNextAutoGrab(html.querySelector('.gtbabel__auto-grab-next').getAttribute('href'));
                    }
                }
            });
    }

    initFileUpload() {
        document.addEventListener('click', e => {
            let el = e.target.closest('.gtbabel__file-info-upload');
            if (el) {
                let image_frame;
                if (image_frame) {
                    image_frame.open();
                }

                image_frame = wp.media({
                    multiple: false
                });

                image_frame.on('close', () => {
                    let url = null,
                        selection = image_frame.state().get('selection'),
                        preview = el.closest('.gtbabel__table-cell').querySelector('.gtbabel__file-info-img'),
                        textarea = el.closest('.gtbabel__table-cell').querySelector('.gtbabel__input--textarea'),
                        abs = textarea !== null && textarea.value.indexOf('http') === 0;
                    if (selection.length > 0) {
                        selection.forEach(attachment => {
                            url = attachment.attributes.url;
                        });
                    }
                    if (url !== null) {
                        preview.setAttribute('src', url);
                        if (abs === false) {
                            url = url.replace(window.location.protocol + '//' + window.location.host, '');
                            url = url.replace(/^\/+|\/+$/g, '');
                        }
                        textarea.value = url;
                        textarea.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                });

                image_frame.open();

                e.preventDefault();
            }
        });
    }

    initVideoChange() {
        if (document.querySelector('.gtbabel__video-id') !== null) {
            document.querySelectorAll('.gtbabel__video-id').forEach(el => {
                el.addEventListener('keyup', () => {
                    el.closest('.gtbabel__video-info').previousElementSibling.value = el
                        .getAttribute('data-str-orig')
                        .split(el.getAttribute('data-id-orig'))
                        .join(el.value);
                    el.closest('.gtbabel__video-info').previousElementSibling.dispatchEvent(
                        new Event('change', { bubbles: true })
                    );
                });
            });
        }
    }

    initSearchMainUrlReset() {
        document.addEventListener('click', e => {
            let el = e.target.closest('.gtbabel__transmeta-reset');
            if (el) {
                document.querySelector('.gtbabel__form input[name="url"]').value = '';
                document.querySelector('.gtbabel__form input[name="p"]').value = '';
                document.querySelector('.gtbabel__form input[name="post_id"]').value = '';
                document.querySelector('.gtbabel__form').submit();
                e.preventDefault();
            }
        });
    }

    initFormResetQuestion() {
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

    initFormChange() {
        document.addEventListener('change', e => {
            let el = e.target.closest('.gtbabel__input--on-change');
            if (el) {
                e.target.setAttribute('name', e.target.getAttribute('data-name'));
            }
        });
    }

    initFormInverse() {
        document.addEventListener('change', e => {
            let el = e.target.closest('.gtbabel__input--inverse');
            if (el) {
                e.target.nextElementSibling.value = e.target.checked === true ? '0' : '1';
            }
        });
    }

    initSubmitUnchecked() {
        document.addEventListener('submit', e => {
            if (e.target.closest('.gtbabel__form')) {
                let form = e.target.closest('.gtbabel__form'),
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
    }

    initWysiwyg() {
        if (document.querySelector('.gtbabel__wysiwyg-textarea') !== null) {
            document.querySelectorAll('.gtbabel__wysiwyg-textarea').forEach(el => {
                let editor = new WysiwygEditor();
                editor.init(el);
            });
        }
    }

    initWizardAutoTranslationChange() {
        let el = document.querySelector('.gtbabel__input--wizard-auto-translation-service-provider');
        if (el !== null) {
            this.initWizardAutoTranslationChangeUpdate(el);
            el.addEventListener('change', e => {
                this.initWizardAutoTranslationChangeUpdate(el);
            });
        }
    }

    initWizardAutoTranslationChangeUpdate(el) {
        let el2 = el.closest('form').querySelector('.gtbabel__input--wizard-auto-translation-service-api-key');
        el2.style.display = el.value === 'manual' || el.value === 'custom' ? 'none' : 'inline-block';
        el2.required = el.value === 'manual' || el.value === 'custom' ? false : true;
        el2.value = el.options[el.selectedIndex].getAttribute('data-api-key');
    }

    initTooltips() {
        let tooltips = new Tooltips();
        tooltips.init();
    }
}
