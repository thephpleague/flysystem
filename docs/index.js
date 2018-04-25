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

let firstSubHeading = document.querySelector('#article h2:first-of-type');

if (firstSubHeading) {
    let carbon = document.createElement('script');
    carbon.setAttribute('async', true);
    carbon.setAttribute('type', 'text/javascript');
    carbon.setAttribute('src', '//cdn.carbonads.com/carbon.js?zoneid=1673&serve=C6AILKT&placement=flysystemthephpleaguecom');
    carbon.setAttribute('id', '_carbonads_js');
    firstSubHeading.before(carbon);
}

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