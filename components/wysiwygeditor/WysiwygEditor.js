import pell from 'pell';

export default class WysiwygEditor {
    init(el) {
        if (el !== null) {
            let isHtml = Array.from(new DOMParser().parseFromString(el.value, 'text/html').body.childNodes).some(
                    node => node.nodeType === 1
                ),
                context = el.getAttribute('data-context');

            el.classList.add('gtbabel__wysiwyg-textarea');
            el.setAttribute('spellcheck', 'false');
            if (isHtml === false) {
                el.style.display = 'block';
            }
            this.textareaAutoHeight(el);

            let wrapper = document.createElement('div');
            wrapper.setAttribute(
                'class',
                'gtbabel__wysiwyg gtbabel__wysiwyg--mode-' + (isHtml === true ? 'visual' : 'html')
            );
            el.parentNode.insertBefore(wrapper, el.nextSibling);
            wrapper.appendChild(el);

            if (isHtml === true) {
                wrapper.insertAdjacentHTML(
                    'afterbegin',
                    `
                <div class="gtbabel__wysiwyg-buttons">
                    <a class="gtbabel__wysiwyg-button gtbabel__wysiwyg-button--visual" href="#">Visuell</a>
                    <a class="gtbabel__wysiwyg-button gtbabel__wysiwyg-button--html" href="#">HTML</a>
                </div>
            `
                );
            }
            wrapper.querySelectorAll('.gtbabel__wysiwyg-button').forEach(button => {
                button.addEventListener('click', e => {
                    if (
                        e.target.classList.contains('gtbabel__wysiwyg-button--html') &&
                        wrapper.classList.contains('gtbabel__wysiwyg--mode-visual')
                    ) {
                        wrapper.classList.remove('gtbabel__wysiwyg--mode-visual');
                        wrapper.classList.add('gtbabel__wysiwyg--mode-html');
                        this.textareaAutoHeight(el);
                    }
                    if (
                        e.target.classList.contains('gtbabel__wysiwyg-button--visual') &&
                        wrapper.classList.contains('gtbabel__wysiwyg--mode-html')
                    ) {
                        wrapper.classList.remove('gtbabel__wysiwyg--mode-html');
                        wrapper.classList.add('gtbabel__wysiwyg--mode-visual');
                    }
                    e.preventDefault();
                });
            });

            if (context === 'slug') {
                el.addEventListener('blur', () => {
                    el.value = this.slugify(el.value);
                });
            }

            if (isHtml === true) {
                let container = document.createElement('div');
                container.setAttribute('class', 'gtbabel__wysiwyg-editor');
                if (el.hasAttribute('readonly')) {
                    container.classList.add('gtbabel__wysiwyg-editor--readonly');
                }
                wrapper.appendChild(container);

                let editor = pell.init({
                    element: container,
                    onChange: html => {
                        // strip away surrounding p tags
                        if (1 == 1) {
                            html = html
                                .replace(/^<div>/g, '')
                                .replace(/<\/div>$/g, '')
                                .replace(/<\/div>(\r\n|\r|\n)?<div.*?>/g, '<br/>')
                                .replace(/<div.*?>/g, '')
                                .replace(/<\/div>/g, '');
                        }
                        el.value = html;
                    },
                    defaultParagraphSeparator: 'div',
                    styleWithCSS: false,
                    actions: ['bold', 'underline', 'italic', 'ulist', 'olist']
                });
                if (el.hasAttribute('readonly')) {
                    editor.content.removeAttribute('contenteditable');
                }
                editor.content.innerHTML = '<div>' + el.value + '</div>';
                el.addEventListener('keyup', () => {
                    editor.content.innerHTML = '<div>' + el.value + '</div>';
                });
            }
        }
    }

    textareaAutoHeight(el) {
        this.textareaSetHeight(el);
        el.addEventListener('keyup', e => {
            this.textareaSetHeight(e.target);
        });
    }

    textareaSetHeight(el) {
        el.style.height = '5px';
        el.style.height = el.scrollHeight + 'px';
    }

    slugify(text) {
        return text
            .toString()
            .toLowerCase()
            .trim()
            .split('ä')
            .join('ae')
            .split('ö')
            .join('oe')
            .split('ü')
            .join('ue')
            .split('ß')
            .join('ss')
            .replace(/[^\w\s-]/g, '')
            .replace(/[\s_-]+/g, '-')
            .replace(/^-+|-+$/g, '');
    }
}
