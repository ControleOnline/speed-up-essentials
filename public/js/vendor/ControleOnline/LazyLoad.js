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
            while (i < lazyLoader.cache.length) {
                var image = lazyLoader.cache[i];
                var imagePosition = getOffsetTop(image);
                var imageHeight = image.height || 0;
                if ((imagePosition >= range.min - imageHeight) && (imagePosition <= range.max)) {
                    var src = image.getAttribute('data-src');
                    image.onload = function () {
                        this.className = this.className.replace(/(^|\s+)lazy-load(\s+|$)/, '$1lazy-loaded$2');
                    };
                    image.src = src;
                    image.removeAttribute('data-src');
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
        init: function () {
            // Patch IE7- (querySelectorAll)
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

            //addEventListener('load', function _lazyLoaderInit() {
            var imageNodes = document.querySelectorAll('img[data-src]');
            for (var i = 0; i < imageNodes.length; i++) {
                var imageNode = imageNodes[i];
                lazyLoader.cache.push(imageNode);
            }
            lazyLoader.addObservers();
            lazyLoader.loadVisibleImages();
            lazyLoader.verify = setInterval(function () {
                window.dispatchEvent(new Event('resize'));
            }, 500);
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
(function () {
    if (document.addEventListener) {
        document.addEventListener("DOMContentLoaded", function () {
            document.removeEventListener("DOMContentLoaded", arguments.callee, false);
            lazyLoad();
        }, false);

        // If IE event model is used
    } else if (document.attachEvent) {
        // ensure firing before onload
        document.attachEvent("onreadystatechange", function () {
            if (document.readyState === "complete") {
                document.detachEvent("onreadystatechange", arguments.callee);
                lazyLoad();
            }
        });
    }
    lazyLoad();
})();

function replace_text(id, text) {
    var $id = new CustomEvent(id, {"detail": text});
    var elem = document.getElementById(id);
    elem.dispatchEvent($id);
    /*
     var elem = document.getElementById(id);
     var src = 'https://www.googletagservices.com/tag/js/gpt.js';
     if (1 === 1) {
     loadScript(src, elem, function () {
     });
     } else {
     var noscript = (text + '').replace(/[\\"']/g, '\\$&').replace(/\u0000/g, '\\0').replace('</script>', '</s\' + \'cript>');
     elem.innerHTML = elem.innerHTML + 'document.write(\'' + noscript + '\');';
     }
     */
}
/*
 function loadScript(url, elem, callback) {
 var script = document.createElement("script");
 script.type = "text/javascript";
 if (script.readyState) {  
 script.onreadystatechange = function () {
 if (script.readyState == "loaded" || script.readyState == "complete") {
 script.onreadystatechange = null;
 callback();
 }
 };
 } else {  
 script.onload = function () {
 callback();
 };
 }
 script.src = url;
 elem.parentNode.insertBefore(script, elem.nextSibling);
 elem.parentNode.removeChild(elem);
 }
 */

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