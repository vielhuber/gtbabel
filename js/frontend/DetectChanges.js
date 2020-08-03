import { DiffDOM } from 'diff-dom';

export default class DetectChanges {
    constructor() {
        this.dd = new DiffDOM();
        this.observer = null;
        this.blocked = [];
        this.included = [];
        this.dom_prev = document.createElement('body');
        this.dom_not_translated = document.createElement('body');
        this.jobCur = null;
        this.jobTodo = null;
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

            this.hideNode(node);

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

            this.sync();

            let node_not_translated = this.dom_not_translated.querySelectorAll(selector)[index];
            let html = node_not_translated.outerHTML;

            await this.wait(1500);

            if (!this.jobIsActive(id)) {
                return;
            }

            // translate (this takes time)
            let resp = await this.getTranslation(html);
            if (
                resp.success === false ||
                !('data' in resp) ||
                !('input' in resp.data) ||
                !('output' in resp.data) ||
                resp.data.input == resp.data.output
            ) {
                return;
            }

            if (!this.jobIsActive(id)) {
                return;
            }

            this.sync();

            if (!this.jobIsActive(id)) {
                return;
            }

            await this.setHtmlAndKeepEventListeners(node, resp.data);
            this.dom_prev = document.body.cloneNode(true);
            this.sync();

            this.showNode(node);
        }
    }

    sync() {
        let diff = this.dd.diff(this.dom_prev, document.body);
        this.dd.apply(this.dom_not_translated, diff);
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
            this.translateAll();
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

    async setHtmlAndKeepEventListeners(node, data) {
        this.pauseMutationObserverForNode(node);
        // now this is interesting: we make a diff of input and output (not output and current node),
        // because we don't want to loose any attribute changes that have been applied in the meantime
        let diff = this.dd.diff(data.input, data.output);
        this.dd.apply(node, diff);
        await this.resumeMutationObserverForNode(node);
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
}
