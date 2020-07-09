import { DiffDOM } from 'diff-dom';

export default class DetectChanges {
    constructor() {
        this.observer = null;
        this.exclude = [];
        this.dd = new DiffDOM();
    }

    async init() {
        this.setupMutationObserver();
    }

    setupMutationObserver() {
        this.observer = new MutationObserver(mutations => {
            mutations.forEach(mutations__value => {
                this.onDomChange(mutations__value);
            });
        }).observe(document.body, {
            childList: true,
            attributes: false,
            characterData: true,
            subtree: true
        });
    }

    async onDomChange(mutation) {
        let nodes = [];
        if (mutation.addedNodes.length > 0) {
            nodes = mutation.addedNodes;
        } else if (mutation.removedNodes.length === 0 && mutation.target !== null) {
            nodes = [mutation.target];
        }
        if (nodes.length === 0) {
            return;
        }
        for (let nodes__value of nodes) {
            if (this.isExcluded(nodes__value)) {
                continue;
            }
            if (nodes__value.nodeType != 1 && nodes__value.nodeType != 3) {
                continue;
            }
            if (nodes__value.nodeType == 3) {
                nodes__value = nodes__value.parentNode;
            }
            if (nodes__value.tagName === 'BODY') {
                continue;
            }
            let html = '';
            // this is important: previously set attributes must be removed (because they could have changed in the meantime again)
            nodes__value.removeAttribute('data-gtbabel-hide');
            html = nodes__value.outerHTML;
            nodes__value.setAttribute('data-gtbabel-hide', '');
            let resp = await this.getTranslation(html);
            console.log(resp);
            let trans = resp.success === true ? resp.data : html;
            this.setHtmlAndKeepEventListeners(nodes__value, trans);
        }
    }

    setHtmlAndKeepEventListeners(node, html) {
        this.exclude.push(node);
        let diff = this.dd.diff(node, html);
        this.dd.apply(node, diff);
        requestAnimationFrame(() => {
            this.exclude = this.exclude.filter(exclude__value => exclude__value !== node);
        });
    }

    getTranslation(html) {
        return new Promise(resolve => {
            let data = new URLSearchParams();
            data.append('html', html);
            let url = window.location.protocol + '//' + window.location.host + window.location.pathname;
            url += (url.indexOf('?') > -1 ? '&' : '?') + 'gtbabel_translate_part=1';
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

    isExcluded(node) {
        for (let exclude__value of this.exclude) {
            if (exclude__value.contains(node)) {
                return true;
            }
        }
        return false;
    }
}
