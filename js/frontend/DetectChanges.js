import { DiffDOM } from 'diff-dom';

export default class DetectChanges {
    constructor() {
        this.dd = new DiffDOM();
        this.observer = null;
        this.blocked = [];
        this.batch = [];
        this.debounceFn = null;
        this.included = [];
    }

    init() {
        if (window.gtbabel_detect_dom_changes_include === undefined) {
            return;
        }
        this.included = window.gtbabel_detect_dom_changes_include;
        this.setupMutationObserver();
        this.setupDebounce();
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
        // collect nodes
        let nodes = [];
        if (mutation.addedNodes.length > 0) {
            nodes = mutation.addedNodes;
        } else if (mutation.target !== null) {
            nodes = [mutation.target];
        }
        if (nodes.length === 0) {
            return;
        }

        for (let nodes__value of nodes) {
            if (nodes__value.nodeType != Node.ELEMENT_NODE && nodes__value.nodeType != Node.TEXT_NODE) {
                continue;
            }
            if (nodes__value.nodeType == Node.TEXT_NODE) {
                nodes__value = nodes__value.parentNode;
            }
            if (!this.isInsideGroup(nodes__value, this.included)) {
                return;
            }
            if (this.isInsideGroup(nodes__value, this.blocked)) {
                return;
            }
            if (!document.body.contains(mutation.target)) {
                return;
            }
            if (nodes__value.tagName === 'BODY') {
                continue;
            }
            if (nodes__value.closest('iframe') !== null) {
                continue;
            }
            this.hideNode(nodes__value);
            this.batch.push(nodes__value);
        }
        this.runDebounce();
    }

    translateBatch() {
        if (this.batch.length === 0) {
            return;
        }

        let batch = this.batch.slice(0); // copy
        this.batch = []; // destroy
        batch = batch.filter((x, i, a) => a.indexOf(x) == i); // sort out duplicates

        for (let batch__value of batch) {
            // sort out elements that are childs of other elements
            let hasParent = false;
            for (let batch__value_2 of batch) {
                if (batch__value !== batch__value_2 && batch__value_2.contains(batch__value)) {
                    hasParent = true;
                    break;
                }
            }
            if (hasParent === true) {
                continue;
            }
            // sort out elements that don't exist anymore
            if (!document.body.contains(batch__value)) {
                continue;
            }
            let clone = batch__value.cloneNode(true);
            this.showNode(clone);
            let html = clone.outerHTML;
            this.getTranslation(html).then(resp => {
                //console.log(resp.data);
                this.showNode(batch__value);
                if (
                    resp.success === false ||
                    !('data' in resp) ||
                    !('input' in resp.data) ||
                    !('output' in resp.data) ||
                    resp.data.input == resp.data.output
                ) {
                    return;
                }
                this.setHtmlAndKeepEventListeners(batch__value, resp.data);
            });
        }
    }

    hideNode(node) {
        node.setAttribute('data-gtbabel-hide', '');
    }

    showNode(node) {
        node.removeAttribute('data-gtbabel-hide');
        if (node.querySelectorAll('[data-gtbabel-hide]') !== null) {
            node.querySelectorAll('[data-gtbabel-hide]').forEach(el => {
                el.removeAttribute('data-gtbabel-hide');
            });
        }
    }

    setHtmlAndKeepEventListeners(node, data) {
        // stop mutationobserver
        this.blocked.push(node);
        // now this is interesting: we make a diff of input and output (not output and current node),
        // because we don't want to loose any attribute changes that have been applied in the meantime
        let diff = this.dd.diff(data.input, data.output);
        this.dd.apply(node, diff);
        requestAnimationFrame(() => {
            this.blocked = this.blocked.filter(blocked__value => blocked__value !== node);
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

    isInsideGroup(node, group) {
        for (let value of group) {
            if (typeof value === 'string' || value instanceof String) {
                let el = document.querySelectorAll(value);
                if (el.length === 0) {
                    continue;
                }
                for (let el__value of el) {
                    if (el__value.contains(node)) {
                        return true;
                    }
                }
            } else {
                if (value.contains(node)) {
                    return true;
                }
            }
        }
        return false;
    }

    setupDebounce() {
        this.debounceFn = this.debounce(() => {
            this.translateBatch();
        }, 300);
    }

    runDebounce() {
        this.debounceFn();
    }

    debounce(func, wait, immediate) {
        var timeout;
        return function () {
            var context = this,
                args = arguments;
            var later = function () {
                timeout = null;
                if (!immediate) func.apply(context, args);
            };
            var callNow = immediate && !timeout;
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
            if (callNow) func.apply(context, args);
        };
    }
}
