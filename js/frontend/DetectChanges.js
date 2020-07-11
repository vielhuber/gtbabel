import { DiffDOM } from 'diff-dom';

export default class DetectChanges {
    constructor() {
        this.dd = new DiffDOM();
        this.observer = null;
        this.blocked = [];
        this.batch = [];
        this.debounceFn = null;
        this.removed = [];
        this.excluded = ['#wholesaler-map', '[class*="slider"]', '.gm-style'];
        this.included = ['.swal-overlay', '.top-button', '.wpcf7-response-output'];
    }

    async init() {
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
        /*
        if (this.isInsideGroup(mutation.target, this.excluded)) {
            return;
        }
        if (this.isInsideGroup(mutation.target, this.blocked)) {
            return;
        }
        if (!document.body.contains(mutation.target)) {
            return;
        }
        if (mutation.target.tagName === 'BODY') {
            return;
        }
        console.log([mutation, mutation.target.outerHTML]);
            console.log({
                0: mutation.addedNodes.length > 0 ? mutation.addedNodes[0].outerHTML : null,
                1: mutation.removedNodes.length > 0 ? mutation.removedNodes[0].outerHTML : null,
                2: mutation
            });
        return;
        */
        if (mutation.removedNodes.length > 0) {
            // keep track of removed nodes (if they are added later again they are ignored)
            this.removed.concat(mutation.removedNodes);
            // also remove removed nodes from already batched nodes
            mutation.removedNodes.forEach(removedNodes__value => {
                this.batch = this.batch.filter(x => x !== removedNodes__value);
            });
            return;
        }

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
            if (this.isInsideGroup(nodes__value, this.excluded)) {
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
            if (this.removed.indexOf(nodes__value) > -1) {
                continue;
            }
            this.hideNode(nodes__value);
            //console.log(nodes__value);
            this.batch.push(nodes__value);
        }
        this.runDebounce();
    }

    translateBatch() {
        console.log('translating batch');
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
                console.log(resp.data);
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
                this.setHtmlAndKeepEventListeners(batch__value, resp.data.output);
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

    setHtmlAndKeepEventListeners(node, html) {
        this.blocked.push(node);
        let diff = this.dd.diff(node, html);
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
