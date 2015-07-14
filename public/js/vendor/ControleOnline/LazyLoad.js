/*!
 * Lazy Load Images without jQuery
 * http://ezyz.github.com/Lazy-Load-Images-without-jQuery/
 *
 * Original by Mike Pulaski - http://www.mikepulaski.com
 * Modified by Kai Zau - http://kaizau.com
 * Modified by Luiz Kim <luizkim@gmail.com>- http://controleonline.com
 */
var lazyLoad = {
    timer: document.querySelectorAll('[timer-ll]')[0] || 400,
    cache: [],
    verify: null,
    throttleTimer: new Date().getTime(),
    __construct: (function () {
        document.addEventListener("DOMContentLoaded", function () {
            lazyLoad.init();
        });
    })(),
    addObservers: function () {
        addEventListener('scroll', lazyLoad.throttledLoad);
        addEventListener('resize', lazyLoad.throttledLoad);
        addEventListener('DOMSubtreeModified', lazyLoad.throttledLoad);
    },
    removeObservers: function () {
        removeEventListener('scroll', lazyLoad.throttledLoad, false);
        removeEventListener('resize', lazyLoad.throttledLoad, false);
        removeEventListener('DOMSubtreeModified', lazyLoad.throttledLoad, false);
    },
    throttledLoad: function () {
        var now = new Date().getTime();
        if ((now - lazyLoad.throttleTimer) >= 200) {
            lazyLoad.throttleTimer = now;
            lazyLoad.loadVisibleImages();
        }
    },
    loadVisibleImages: function () {
        var scrollY = window.pageYOffset || document.documentElement.scrollTop;
        var pageHeight = window.innerHeight || document.documentElement.clientHeight;
        var range = {
            min: scrollY - 200,
            max: scrollY + pageHeight + 200
        };
        var i = 0;
        var ll;
        while (i < lazyLoad.cache.length) {
            var image = lazyLoad.cache[i];
            var imagePosition = lazyLoad.getOffsetTop(image);
            var imageHeight = image.height || 0;
            if ((imagePosition >= range.min - imageHeight) && (imagePosition <= range.max)) {
                ll = image.getAttribute('data-ll');
                image.onload = function () {
                    this.className = this.className.replace(/(^|\s+)lazy-load(\s+|$)/, '$1lazy-loaded$2');
                };
                image.src = ll;
                //image.removeAttribute('data-ll');
                lazyLoad.cache.splice(i, 1);
                continue;
            }
            i++;
        }

        if (lazyLoad.cache.length === 0) {
            lazyLoad.removeObservers();
            clearInterval(lazyLoad.verify);
        }
    },
    removeScripts: function () {
        var ns = document.querySelectorAll('.ns-ll');
        for (var i = 0; i < ns.length; i++) {
            var n = ns[i];
            n.parentNode.removeChild(n);
        }
    },
    init: function () {
        lazyLoad.removeScripts();
        if (!document.querySelectorAll) {
            document.querySelectorAll = function (selector) {
                var doc = document, head = doc.documentElement.firstChild, styleTag = doc.createElement('STYLE');
                head.appendChild(styleTag);
                doc.__qsaels = [];
                styleTag.styleSheet.cssText = selector + "{x:expression(document.__qsaels.push(this))}";
                window.scrollBy(0, 0);
                return doc.__qsaels;
            };
        }
        var imageNodes = document.querySelectorAll('img[data-ll]');
        for (var i = 0; i < imageNodes.length; i++) {
            var imageNode = imageNodes[i];
            lazyLoad.cache.push(imageNode);
        }
        lazyLoad.addObservers();
        lazyLoad.loadVisibleImages();
        lazyLoad.verify = setInterval(function () {
            if (document.createEventObject) {
                window.dispatchEvent(new Event('scroll'));
            } else {
                var evt = document.createEvent('UIEvents');
                evt.initUIEvent('scroll', true, false, window, 0);
                window.dispatchEvent(evt);
            }
        }, lazyLoad.timer);
    },
    // For IE7 compatibility
    // Adapted from http://www.quirksmode.org/js/findpos.html
    getOffsetTop: function (el) {
        var val = 0;
        if (el.offsetParent) {
            do {
                val += el.offsetTop;
            } while (el = el.offsetParent);
            return val;
        }
    }
};  