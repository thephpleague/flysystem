import './index.css';

let copyElementText = (element) => {
    let selection = window.getSelection();
    let range = document.createRange();
    document.execCommand('copy');
    range.selectNodeContents(element);
    selection.removeAllRanges();
    selection.addRange(range);
    window.getSelection().removeAllRanges();
};

document
    .querySelectorAll('.highlighter-copy')
    .forEach(btn => btn.addEventListener('click', () => copyElementText(btn.previousSibling)));

let menuBtn = document.getElementById('menu-toggle');
let navigation = document.getElementById('navigation');
let article = document.getElementById('article');

let toggleClassName = function (el, className) {
    if (el.classList.contains(className)) {
        el.classList.remove(className);
    } else {
        el.classList.add(className);
    }
};
menuBtn.addEventListener('click', function (e) {
    e.preventDefault();
    toggleClassName(menuBtn, 'menu-closed');
    toggleClassName(navigation, 'hidden');
    toggleClassName(article, 'hidden');
});
