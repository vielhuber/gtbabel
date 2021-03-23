import { DiffDOM } from 'diff-dom';
var base64_encode = require('locutus/php/url/base64_encode');
var base64_decode = require('locutus/php/url/base64_decode');
var serialize = require('locutus/php/var/serialize');
var unserialize = require('locutus/php/var/unserialize');
import WysiwygEditor from './../wysiwygeditor/WysiwygEditor';

export default class FrontendEditor {
    constructor() {
        this.dd = new DiffDOM();
        this.editor = new WysiwygEditor();
        this.attrName = 'data-gtbabel-meta';
        this.attrNameKey = 'data-gtbabel-meta-key';
        this.classNameEdit = 'gtbabel-frontend-editor-edit-active';
        this.classNameButton = 'gtbabel-frontend-editor-edit-button';
        this.classNameContainer = 'gtbabel-frontend-editor-container';
        this.classNameContainerSubmit = 'gtbabel-frontend-editor-container-submit';
        this.classNameContainerClose = 'gtbabel-frontend-editor-container-close';
        this.classNameContainerList = 'gtbabel-frontend-editor-container-list';
        this.classNameContainerListitem = 'gtbabel-frontend-editor-container-listitem';
        this.classNameContainerListitemTextarea = 'gtbabel-frontend-editor-container-listitem-textarea';
        this.classNameContainerListitemStr = 'gtbabel-frontend-editor-container-listitem-str';
        this.classNameContainerListitemContext = 'gtbabel-frontend-editor-container-listitem-context';
        this.classNameContainerListitemTrans = 'gtbabel-frontend-editor-container-listitem-trans';
    }

    init() {
        this.bindHover();
        this.bindClick();
        this.bindClose();
        this.bindSave();
    }

    bindHover() {
        document.documentElement.addEventListener(
            'mouseenter',
            e => {
                if (e.target.hasAttribute(this.attrName) && !e.target.classList.contains(this.classNameEdit)) {
                    if (document.querySelectorAll('.' + this.classNameEdit).length > 0) {
                        document.querySelectorAll('.' + this.classNameEdit).forEach(el => {
                            if (!el.contains(e.target)) {
                                el.classList.remove(this.classNameEdit);
                                if (el.querySelectorAll(':scope > .' + this.classNameButton).length > 0) {
                                    el.querySelectorAll(':scope > .' + this.classNameButton).forEach(el2 => {
                                        el2.remove();
                                    });
                                }
                            }
                        });
                    }
                    e.target.classList.add(this.classNameEdit);
                    if (
                        e.target.querySelectorAll(':scope > .' + this.classNameButton).length === 0 &&
                        !['TEXTAREA', 'INPUT', 'SELECT', 'IFRAME', 'IMG'].includes(e.target.tagName)
                    ) {
                        let html = `
                            <a href="#" class="${this.classNameButton}"></a>
                        `;
                        e.target.insertAdjacentHTML('afterbegin', html);
                        let el = e.target.querySelector(':scope > .' + this.classNameButton);
                        if (window.getComputedStyle(e.target).display === 'inline') {
                            el.style.setProperty('display', 'inline', 'important');
                        }
                        this.positionAtTopLeft(el);
                        this.fixAllPositions(el);
                    }
                }
            },
            true
        );
    }

    positionAtTopLeft(el) {
        let max = null;
        max = 50;
        while (max > 0 && el.getBoundingClientRect().top - el.parentNode.getBoundingClientRect().top > 0) {
            let y = el.style.marginTop;
            if (y == '') {
                y = 0;
            } else {
                y = parseInt(y);
            }
            el.style.setProperty('margin-top', y - 1 + 'px', 'important');
            max--;
        }
        max = 50;
        while (
            max > 0 &&
            el.getBoundingClientRect().left > 0 &&
            el.getBoundingClientRect().left - el.parentNode.getBoundingClientRect().left > -20
        ) {
            let x = el.style.marginLeft;
            if (x == '') {
                x = 0;
            } else {
                x = parseInt(x);
            }
            el.style.setProperty('margin-left', x - 1 + 'px', 'important');
            max--;
        }
    }

    fixAllPositions(el) {
        if (document.querySelectorAll('.' + this.classNameButton).length > 0) {
            document.querySelectorAll('.' + this.classNameButton).forEach(el2 => {
                if (el2 !== el) {
                    if (this.elementsCollide(el2, el)) {
                        let max = 50;
                        while (max > 0 && this.elementsCollide(el2, el)) {
                            let x = el.style.marginLeft;
                            if (x == '') {
                                x = 0;
                            } else {
                                x = parseInt(x);
                            }
                            el.style.setProperty('margin-left', x + 1 + 'px', 'important');
                            max--;
                        }
                        this.fixAllPositions(el);
                        return;
                    }
                }
            });
        }
    }

    elementsCollide(el1, el2) {
        let rect1 = el1.getBoundingClientRect(),
            rect2 = el2.getBoundingClientRect();
        return !(
            rect1.right < rect2.left ||
            rect1.left > rect2.right ||
            rect1.bottom < rect2.top ||
            rect1.top > rect2.bottom
        );
    }

    bindClick() {
        document.addEventListener('click', e => {
            let button = e.target.closest('.' + this.classNameButton);
            if (button !== null) {
                if (button.closest('[' + this.attrName + ']') !== null) {
                    let data = button.closest('[' + this.attrName + ']').getAttribute(this.attrName);
                    data = this.decodeData(data);
                    if (data !== '' && data !== null && data !== undefined && Array.isArray(data) && data.length > 0) {
                        this.closeContainer();
                        let html = '';
                        html +=
                            '<div class="' +
                            this.classNameContainer +
                            '" data-key="' +
                            button.closest('[' + this.attrName + ']').getAttribute(this.attrNameKey) +
                            '">';
                        html += '<a class="' + this.classNameContainerClose + '" href="#"></a>';
                        html += '<ul class="' + this.classNameContainerList + '">';
                        data.forEach(data__value => {
                            html +=
                                '<li class="' +
                                this.classNameContainerListitem +
                                '" data-lng-source="' +
                                data__value.lng_source +
                                '" data-lng-target="' +
                                data__value.lng_target +
                                '" data-context="' +
                                (data__value.context ?? '') +
                                '">';
                            if (data__value.context !== null) {
                                html +=
                                    '<span class="' +
                                    this.classNameContainerListitemContext +
                                    '">' +
                                    (data__value.context ?? '') +
                                    '</span>';
                            }
                            html +=
                                '<textarea class="' +
                                this.classNameContainerListitemTextarea +
                                ' ' +
                                this.classNameContainerListitemStr +
                                '" readonly="readonly">' +
                                data__value.str +
                                '</textarea>';
                            html +=
                                '<textarea class="' +
                                this.classNameContainerListitemTextarea +
                                ' ' +
                                this.classNameContainerListitemTrans +
                                '" data-context="' +
                                (data__value.context ?? '') +
                                '">' +
                                data__value.trans +
                                '</textarea>';
                            html += '</li>';
                        });
                        html += '</ul>';
                        html += '<input type="submit" class="' + this.classNameContainerSubmit + '" value="" />';
                        html += '</div>';
                        document.body.insertAdjacentHTML('beforeend', html);
                        let container = document.querySelector('.' + this.classNameContainer);
                        let x = button.getBoundingClientRect().left + document.documentElement.scrollLeft;
                        let y = button.getBoundingClientRect().top + document.documentElement.scrollTop;
                        y += button.offsetHeight;
                        container.style.left = x + 'px';
                        container.style.top = y + 'px';

                        document.querySelectorAll('.' + this.classNameContainerListitemTextarea).forEach(el => {
                            this.editor.init(el);
                        });
                    }
                }
                e.preventDefault();
            }
        });
    }

    bindClose() {
        document.addEventListener('click', e => {
            let button = e.target.closest('.' + this.classNameContainerClose);
            if (button !== null) {
                this.closeContainer();
                e.preventDefault();
            }
        });
    }

    closeContainer() {
        if (document.querySelector('.' + this.classNameContainer) !== null) {
            document.querySelector('.' + this.classNameContainer).remove();
        }
    }

    bindSave() {
        document.addEventListener('click', async e => {
            let button = e.target.closest('.' + this.classNameContainerSubmit);
            if (button !== null) {
                let container = button.closest('.' + this.classNameContainer),
                    keys = [];
                for (let el of container.querySelectorAll('.' + this.classNameContainerListitem)) {
                    let data = [
                        el.querySelector('.' + this.classNameContainerListitemStr).value,
                        el.getAttribute('data-context'),
                        el.getAttribute('data-lng-source'),
                        el.getAttribute('data-lng-target'),
                        el.querySelector('.' + this.classNameContainerListitemTrans).value
                    ];
                    keys = keys.concat(this.saveTranslationLocal(...data));
                    await this.saveTranslationRemote(...data);
                }
                this.closeContainer();
                this.reloadPage(keys);
                e.preventDefault();
            }
        });
    }

    saveTranslationLocal(str, context, lng_source, lng_target, trans) {
        let keys = [];
        document.querySelectorAll('[' + this.attrName + ']').forEach(el => {
            let data = this.decodeData(el.getAttribute(this.attrName)),
                modified = false;
            if (data !== '' && data !== null && data !== undefined && Array.isArray(data) && data.length > 0) {
                data.forEach(data__value => {
                    if (
                        data__value.str === str &&
                        (data__value.context ?? '') === (context ?? '') &&
                        data__value.lng_source === lng_source &&
                        data__value.lng_target === lng_target
                    ) {
                        data__value.trans = trans;
                        if (!keys.includes(el.getAttribute(this.attrNameKey))) {
                            keys.push(el.getAttribute(this.attrNameKey));
                        }
                        modified = true;
                    }
                });
            }
            if (modified === true) {
                el.setAttribute(this.attrName, this.encodeData(data));
            }
        });
        return keys;
    }

    saveTranslationRemote(str, context, lng_source, lng_target, trans) {
        return new Promise(resolve => {
            let data = new URLSearchParams();
            data.append('str', str);
            data.append('context', context);
            data.append('lng_source', lng_source);
            data.append('lng_target', lng_target);
            data.append('trans', trans);
            let url = window.location.href.split('#')[0];
            url += (url.indexOf('?') > -1 ? '&' : '?') + 'gtbabel_frontend_editor_save=1';
            fetch(url, {
                method: 'POST',
                body: data,
                cache: 'no-cache'
            })
                .then(result => {
                    return result.json();
                })
                .catch(error => {
                    return { success: false, message: error };
                })
                .then(result => {
                    resolve(result);
                });
        });
    }

    reloadPage(keys) {
        if (keys.length > 0) {
            fetch(window.location.href)
                .then(v => v.text())
                .catch(v => v)
                .then(html => {
                    let dom = new DOMParser().parseFromString(html, 'text/html');
                    keys.forEach(keys__value => {
                        let elOld = document.querySelector('[' + this.attrNameKey + '="' + keys__value + '"]'),
                            elNew = dom.querySelector('[' + this.attrNameKey + '="' + keys__value + '"]');
                        if (elOld !== null && elNew !== null) {
                            let diff = this.dd.diff(elOld, elNew);
                            this.dd.apply(elOld, diff);
                        }
                    });
                });
        }
    }

    encodeData(data) {
        return base64_encode(serialize(data));
    }

    decodeData(data) {
        return unserialize(base64_decode(data));
    }
}
