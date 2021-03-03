export default class Tooltips {
    init() {
        if (document.querySelector('.tooltip') !== null) {
            document.querySelectorAll('.tooltip').forEach(el => {
                // wrap all text nodes
                Array.from(el.childNodes)
                    .filter(node => node.nodeType === 3 && node.textContent.trim().length > 1)
                    .forEach(node => {
                        const p = document.createElement('p');
                        node.after(p);
                        p.appendChild(node);
                    });
                el.innerHTML = `
                    <div class="tooltip__icon">❓</div>
                    <div class="tooltip__inner">${el.innerHTML}</div>
                `;
                el.style.display = 'inline-block';
                el.addEventListener('mouseenter', () => {
                    let inner = el.querySelector('.tooltip__inner');
                    inner.style = '';
                    inner.style.width = '300px';
                    inner.style.height = 'auto';
                    inner.classList.add('tooltip__inner--visible');
                    let windowWidthWithoutScrollbar = document.documentElement.clientWidth || document.body.clientWidth,
                        windowHeightWithoutScrollbar =
                            document.documentElement.clientHeight || document.body.clientHeight,
                        scrollTop =
                            (window.pageYOffset || document.documentElement.scrollTop) -
                            (document.documentElement.clientTop || 0),
                        scrollBottom = scrollTop + window.innerHeight;

                    if (inner.offsetWidth > windowWidthWithoutScrollbar) {
                        inner.style.width = windowWidthWithoutScrollbar + 'px';
                    }
                    if (inner.offsetHeight > windowHeightWithoutScrollbar) {
                        inner.style.height = windowHeightWithoutScrollbar + 'px';
                    }

                    let offsetTop = Math.ceil(
                            inner.getBoundingClientRect().top + window.pageYOffset - document.documentElement.clientTop
                        ),
                        offsetLeft = Math.ceil(
                            inner.getBoundingClientRect().left +
                                window.pageXOffset -
                                document.documentElement.clientLeft
                        ),
                        offsetRight = Math.ceil(
                            inner.getBoundingClientRect().left +
                                window.pageXOffset -
                                document.documentElement.clientLeft +
                                inner.offsetWidth
                        ),
                        offsetBottom = Math.ceil(
                            inner.getBoundingClientRect().top +
                                window.pageYOffset -
                                document.documentElement.clientTop +
                                inner.offsetHeight
                        ),
                        max = Math.max(windowWidthWithoutScrollbar, windowHeightWithoutScrollbar);
                    while (
                        max > 0 &&
                        (offsetRight > windowWidthWithoutScrollbar ||
                            offsetBottom > scrollBottom ||
                            offsetLeft < 0 ||
                            offsetTop < 0)
                    ) {
                        if (offsetRight > windowWidthWithoutScrollbar) {
                            inner.style.left = parseInt(window.getComputedStyle(inner).left) - 1 + 'px';
                            offsetLeft--;
                            offsetRight--;
                        }
                        if (offsetBottom > scrollBottom) {
                            inner.style.top = parseInt(window.getComputedStyle(inner).top) - 1 + 'px';
                            offsetTop--;
                            offsetBottom--;
                        }
                        if (offsetLeft < 0) {
                            inner.style.left = parseInt(window.getComputedStyle(inner).left) + 1 + 'px';
                            offsetLeft++;
                            offsetRight++;
                        }
                        if (offsetTop < 0) {
                            inner.style.top = parseInt(window.getComputedStyle(inner).top) + 1 + 'px';
                            offsetTop++;
                            offsetBottom++;
                        }
                        max--;
                    }
                });
                el.addEventListener('mouseleave', () => {
                    let inner = el.querySelector('.tooltip__inner');
                    inner.classList.remove('tooltip__inner--visible');
                    // clear custom position
                    setTimeout(() => {
                        if (!inner.classList.contains('tooltip__inner--visible')) {
                            inner.style = '';
                        }
                    }, 300);
                });
            });
        }
    }
}
