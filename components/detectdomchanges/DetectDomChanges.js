import { DiffDOM } from 'diff-dom';

export default class DetectDomChanges {
    constructor() {
        this.dd = new DiffDOM({
            maxChildCount: false
        });
        this.observer = null;
        this.blocked = [];
        this.included = [];
        this.dom_prev = document.createElement('body');
        this.dom_not_translated = document.createElement('body');
        this.jobCur = null;
        this.jobTodo = null;
        this.cache = {};
        this.translateAllDebounce = this.debounce(() => {
            this.translateAll();
        }, 1000);
    }

    init() {
        this.included = window.gtbabel_detect_dom_changes_include;
        if (this.included === undefined) {
            return;
        }
        this.setupMutationObserver();
    }

    async translateAll() {
        if (this.jobCur !== this.jobTodo) {
            let id = this.jobTodo;
            this.jobCur = id;
            for (let included__value of this.included) {
                await this.translate(included__value, id);
            }
        }
    }

    async translate(selector, id) {
        if (document.querySelector(selector) === null) {
            return;
        }
        let nodes = document.querySelectorAll(selector);
        let index = -1;
        for (let node of nodes) {
            index++;

            if (!this.jobIsActive(id)) {
                return;
            }

            // delete html comments (diffdom needs this)
            this.pauseMutationObserverForNode(node);
            await this.deleteCommentsFromNode(node);
            await this.resumeMutationObserverForNode(node);

            // normalize html code (diffdom needs this)
            this.pauseMutationObserverForNode(node);
            node.normalize();
            await this.resumeMutationObserverForNode(node);

            if (!this.jobIsActive(id)) {
                return;
            }

            this.sync(id);

            let node_not_translated = this.dom_not_translated.querySelectorAll(selector)[index];
            // disable notranslate from most outer element (this is added, because we don't want it to be translated via php)
            node_not_translated.classList.remove('notranslate');
            node_not_translated.classList.add('notranslate_OFF');
            let html = node_not_translated.outerHTML;

            await this.wait(document.readyState === 'complete' ? 250 : 1000);

            if (!this.jobIsActive(id)) {
                return;
            }

            // translate (this takes time)
            let resp = null;
            if (html in this.cache) {
                resp = this.cache[html];
            } else {
                resp = await this.getTranslation(html);
                this.cache[html] = resp;
            }
            if (
                resp.success === false ||
                !('data' in resp) ||
                !('input' in resp.data) ||
                !('output' in resp.data) ||
                resp.data.input == resp.data.output
            ) {
                this.showNode(node);
                return;
            }

            if (!this.jobIsActive(id)) {
                return;
            }

            resp.data.input = resp.data.input.replace('notranslate_OFF', 'notranslate');
            resp.data.output = resp.data.output.replace('notranslate_OFF', 'notranslate');

            this.sync(id);

            if (!this.jobIsActive(id)) {
                return;
            }

            // apply translation
            this.setHtmlAndKeepEventListeners(node, resp.data, id);

            // this is the most important step: SKIP the translation from the diff!
            this.dom_prev = document.body.cloneNode(true);

            this.sync(id);

            this.showNode(node);
        }
    }

    sync(id) {
        let diff = this.dd.diff(this.dom_prev, document.body);
        try {
            this.dd.apply(this.dom_not_translated, diff);
        } catch (e) {
            console.log(e);
        }
        this.dom_prev = document.body.cloneNode(true);
    }

    jobIsActive(id) {
        return this.jobTodo === id;
    }

    setupMutationObserver() {
        this.observer = new MutationObserver(mutations => {
            mutations.forEach(mutations__value => {
                this.onDomChange(mutations__value);
            });
        }).observe(document.documentElement, {
            childList: true,
            attributes: false,
            characterData: true,
            subtree: true
        });
    }

    async onDomChange(mutation) {
        // collect nodes
        let nodeType = null;
        let nodes = [];
        if (mutation.addedNodes.length > 0) {
            nodes = mutation.addedNodes;
            nodeType = 'added';
        } else if (mutation.target !== null) {
            nodes = [mutation.target];
            nodeType = 'modified';
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
            if (nodes__value === null) {
                continue;
            }
            // if added dom node consists of included
            if (nodeType === 'added') {
                let parent = this.getIncludedChildrenIfAvailable(nodes__value, this.included);
                if (parent !== null) {
                    nodes__value = parent;
                }
            }
            if (!this.isInsideGroup(nodes__value, this.included)) {
                continue;
            }
            if (this.isInsideGroup(nodes__value, this.blocked)) {
                continue;
            }
            // hide most specific node
            this.hideNode(nodes__value);
            // only add most parent
            nodes__value = this.getIncludedParent(nodes__value);
            if (!document.body.contains(nodes__value)) {
                continue;
            }
            if (nodes__value.closest('body') === null) {
                continue;
            }
            if (nodes__value.tagName === 'BODY') {
                continue;
            }
            if (nodes__value.closest('iframe') !== null) {
                continue;
            }
            this.jobTodo = ~~(Math.random() * (9999 - 1000 + 1)) + 1000;
            //this.translateAll();
            this.translateAllDebounce();
        }
    }

    async deleteCommentsFromNode(node) {
        this.pauseMutationObserverForNode(node);
        let nodes = [],
            walker = document.createTreeWalker(node, NodeFilter.SHOW_COMMENT, null, false),
            cur;
        while ((cur = walker.nextNode())) {
            nodes.push(cur);
        }
        for (let nodes__value of nodes) {
            nodes__value.remove();
        }
        await this.resumeMutationObserverForNode(node);
    }

    async hideNode(node) {
        this.pauseMutationObserverForNode(node);
        node.setAttribute('data-gtbabel-hide', '');
        await this.resumeMutationObserverForNode(node);
    }

    async showNode(node) {
        this.pauseMutationObserverForNode(node);
        node.removeAttribute('data-gtbabel-hide');
        if (node.querySelectorAll('[data-gtbabel-hide]') !== null) {
            node.querySelectorAll('[data-gtbabel-hide]').forEach(el => {
                el.removeAttribute('data-gtbabel-hide');
            });
        }
        await this.resumeMutationObserverForNode(node);
    }

    setHtmlAndKeepEventListeners(node, data, id) {
        this.pauseMutationObserverForNode(node);
        // now this is interesting: we make a diff of input and output (not output and current node),
        // because we don't want to loose any attribute changes that have been applied in the meantime
        let diff = this.dd.diff(data.input, data.output);
        try {
            this.dd.apply(node, diff);
        } catch (e) {
            console.log(e);
        }
        // intentionally don't call this with await (setHtmlAndKeepEventListeners must be asap)
        this.resumeMutationObserverForNode(node);
    }

    async wait(timer = 10000) {
        await new Promise(resolve => setTimeout(() => resolve(), timer));
    }

    pauseMutationObserverForNode(node) {
        this.blocked.push(node);
    }

    async resumeMutationObserverForNode(node) {
        await new Promise(resolve =>
            requestAnimationFrame(() => {
                this.blocked = this.blocked.filter(blocked__value => blocked__value !== node);
                resolve();
            })
        );
    }

    getTranslation(html) {
        return new Promise(resolve => {
            let data = new URLSearchParams();
            data.append('html', html);
            let url = window.location.href;
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

    getIncludedParent(node) {
        let el = document.querySelectorAll(this.included);
        if (el.length === 0) {
            return null;
        }
        for (let el__value of el) {
            if (el__value.contains(node)) {
                return el__value;
            }
        }
        return null;
    }

    getIncludedChildrenIfAvailable(node, group) {
        for (let value of group) {
            if (typeof value === 'string' || value instanceof String) {
                let child = node.querySelector(value);
                if (child !== null) {
                    return child;
                }
            } else {
                if (node.contains(value)) {
                    return value;
                }
            }
        }
        return null;
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
