/*!
 * Lazy Load Images without jQuery
 * http://ezyz.github.com/Lazy-Load-Images-without-jQuery/
 *
 * Original by Mike Pulaski - http://www.mikepulaski.com
 * Modified by Kai Zau - http://kaizau.com
 * Modified by Luiz Kim <luizkim@gmail.com>- http://controleonline.com
 */
var lazyLoad = function () {
    var addEventListener = window.addEventListener || function (n, f, b) {
        window.attachEvent('on' + n, f);
    };
    var addEventOnElement = function (e, n, f) {
        e.attachEvent('on' + n, f);
    };
    var removeEventListener = window.removeEventListener || function (n, f, b) {
        window.detachEvent('on' + n, f);
    };
    var lazyLoader = {
        timer: document.querySelectorAll('[timer-ll]')[0] || 400,
        cache: [],
        verify: null,
        addObservers: function () {
            addEventListener('scroll', lazyLoader.throttledLoad);
            addEventListener('resize', lazyLoader.throttledLoad);
            addEventListener('DOMSubtreeModified', lazyLoader.throttledLoad);
        },
        removeObservers: function () {
            removeEventListener('scroll', lazyLoader.throttledLoad, false);
            removeEventListener('resize', lazyLoader.throttledLoad, false);
            removeEventListener('DOMSubtreeModified', lazyLoader.throttledLoad, false);
        },
        throttleTimer: new Date().getTime(),
        throttledLoad: function () {
            var now = new Date().getTime();
            if ((now - lazyLoader.throttleTimer) >= 200) {
                lazyLoader.throttleTimer = now;
                lazyLoader.loadVisibleImages();
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
            while (i < lazyLoader.cache.length) {
                var image = lazyLoader.cache[i];
                var imagePosition = getOffsetTop(image);
                var imageHeight = image.height || 0;
                if ((imagePosition >= range.min - imageHeight) && (imagePosition <= range.max)) {
                    ll = image.getAttribute('data-ll');
                    image.onload = function () {
                        this.className = this.className.replace(/(^|\s+)lazy-load(\s+|$)/, '$1lazy-loaded$2');
                    };
                    image.src = ll;
                    image.removeAttribute('data-ll');
                    lazyLoader.cache.splice(i, 1);
                    continue;
                }
                i++;
            }

            if (lazyLoader.cache.length === 0) {
                lazyLoader.removeObservers();
                clearInterval(lazyLoader.verify);
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
            lazyLoader.removeScripts();
            if (!document.querySelectorAll) {
                document.querySelectorAll = function (selector) {
                    var doc = document,
                            head = doc.documentElement.firstChild,
                            styleTag = doc.createElement('STYLE');
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
                lazyLoader.cache.push(imageNode);
            }
            lazyLoader.addObservers();
            lazyLoader.loadVisibleImages();
            lazyLoader.verify = setInterval(function () {
                if (document.createEventObject) {
                    window.dispatchEvent(new Event('scroll'));
                } else {
                    var evt = document.createEvent('UIEvents');
                    evt.initUIEvent('scroll', true, false, window, 0);
                    window.dispatchEvent(evt);
                }
            }, lazyLoader.timer);
        }
    };
    // For IE7 compatibility
    // Adapted from http://www.quirksmode.org/js/findpos.html
    function getOffsetTop(el) {
        var val = 0;
        if (el.offsetParent) {
            do {
                val += el.offsetTop;
            } while (el = el.offsetParent);
            return val;
        }
    }
    lazyLoader.init();
};

if (document.readyState === "complete") {
    lazyLoad();
} else {
    window.onload = function () {
        lazyLoad();
    };

}


var localCache = {
    /**
     * timeout for cache in millis
     * @type {number}
     */
    timeout: 30000,
    /** 
     * @type {{_: number, data: {}}}
     **/
    data: {},
    remove: function (url) {
        delete localCache.data[url];
    },
    exist: function (url) {
        return !!localCache.data[url] && ((new Date().getTime() - localCache.data[url]._) < localCache.timeout);
    },
    get: function (url) {
        console.log('Getting in cache for url' + url);
        return localCache.data[url].data;
    },
    set: function (url, cachedData, callback) {
        localCache.remove(url);
        localCache.data[url] = {
            _: new Date().getTime(),
            data: cachedData
        };
        if ($.isFunction(callback))
            callback(cachedData);
    }
};
/*
 $.ajaxPrefilter(function (options, originalOptions, jqXHR) {
 if (options.cache) {
 var complete = originalOptions.complete || $.noop,
 url = originalOptions.url;
 //remove jQuery cache as we have our own localCache
 options.cache = false;
 options.beforeSend = function () {
 if (localCache.exist(url)) {
 complete(localCache.get(url));
 return false;
 }
 return true;
 };
 options.complete = function (data, textStatus) {
 localCache.set(url, data, complete);
 };
 }
 });
 */            