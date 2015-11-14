$.request = (function () {
    var apiMap = {};
    function request(queryStr) {
        var api = {};
        if (apiMap[queryStr]) { return apiMap[queryStr]; }
        api.queryString = (function () {
            var urlParams = {};
            var e,
            d = function (s) { return decodeURIComponent(s.replace(/\+/g, " ")); },
            q = queryStr.substring(queryStr.indexOf('?') + 1),
            r = /([^&=]+)=?([^&]*)/g;
            while (e = r.exec(q)) urlParams[d(e[1])] = d(e[2]);
            return urlParams;
        })();
        api.getUrl = function () {
            var url = queryStr.substring(0, queryStr.indexOf('?') + 1);
            for (var p in api.queryString) { url += p + '=' + api.queryString[p] + "&"; }
            if (url.lastIndexOf('&') == url.length - 1) { return url.substring(0, url.lastIndexOf('&')); }
            return url;
        }
        apiMap[queryStr] = api;
        return api;
    }
    $.extend(request, request(window.location.href));
    return request;
})();
$['loading'] = function (a) {
    if (a) {
        window.loader = new iDialog();
        window.loader.open({ classList: "loading", title: "", close: "", content: "" })
    } else {
        window.loader.die();
        delete window.loader
    }
};
$['getPageSize'] = function () {
    var xScroll, yScroll;
    if (window.innerHeight && window.scrollMaxY) {
        xScroll = window.innerWidth + window.scrollMaxX;
        yScroll = window.innerHeight + window.scrollMaxY;
    } else {
        if (document.body.scrollHeight > document.body.offsetHeight) { // all but Explorer Mac    
            xScroll = document.body.scrollWidth;
            yScroll = document.body.scrollHeight;
        } else { // Explorer Mac...would also work in Explorer 6 Strict, Mozilla and Safari    
            xScroll = document.body.offsetWidth;
            yScroll = document.body.offsetHeight;
        }
    }
    var windowWidth, windowHeight;
    if (self.innerHeight) { // all except Explorer    

        if (document.documentElement.clientWidth) {
            windowWidth = document.documentElement.clientWidth;
        } else {
            windowWidth = self.innerWidth;
        }
        windowHeight = self.innerHeight;
    } else {
        if (document.documentElement && document.documentElement.clientHeight) { // Explorer 6 Strict Mode    
            windowWidth = document.documentElement.clientWidth;
            windowHeight = document.documentElement.clientHeight;
        } else {
            if (document.body) { // other Explorers    
                windowWidth = document.body.clientWidth;
                windowHeight = document.body.clientHeight;
            }
        }
    }
    if (yScroll < windowHeight) {
        pageHeight = windowHeight;
    } else {
        pageHeight = yScroll;
    }
    if (xScroll < windowWidth) {
        pageWidth = xScroll;
    } else {
        pageWidth = windowWidth;
    }
    arrayPageSize = new Array(pageWidth, pageHeight, windowWidth, windowHeight);
    return arrayPageSize;
};
var iDialog = (function () {
    var f = '<header><dl><dd><label>{title}</label></dd><dd><span onclick="this.parentNode.parentNode.parentNode.parentNode.classList.remove(\'on\');">{close}</span></dd></dl></header><article class="dialogContent">{content}</article><footer></footer>';
    var d = {
        wrapper: null,
        cover: null,
        lastIndex: 1000,
        list: null
    };
    var e = function () {
        this.options = {
            id: "dialogWindow_",
            classList: "",
            type: "",
            wrapper: "",
            title: "",
            close: "",
            content: "",
            cover: true,
            btns: []
        }
    };
    e.prototype = {
        init: function () {
            if (d.list) {
                return this
            } else {
                d.list = {}
            }
            var a = document.createElement("section");
            a.setAttribute("id", id = "dialoger");
            var b = document.createElement("div");
            b.setAttribute("class", "dialogCover");
            a.appendChild(b);
            d.container = a;
            d.cover = b;
            document.body.insertBefore(d.container, document.body.childNodes[0]);
            return this
        },
        open: function (c) {
            window.scrollTo(0, 0);
            this.init();
            this.options = e.merge(this.options, c || {});
            this.options.zIndex = d.lastIndex += 100;
            this.options.id = "dialogWindow_" + this.options.zIndex;
            d.list[this.options.id] = this;
            this.options.wrapper = document.createElement("div");
            this.options.wrapper.setAttribute("data-type", this.options.type);
            this.options.wrapper.setAttribute("id", this.options.id);
            this.options.wrapper.setAttribute("class", "dialogWindow on " + this.options.classList);
            this.options.wrapper.setAttribute("style", "z-index:" + this.options.zIndex);
            this.options.wrapper.innerHTML = iTemplate.makeList(f, [this.options], function (g, h) { });
            d.container.insertBefore(this.options.wrapper, this.options.cover ? d.cover : null);
            if (this.options.btns.length) {
                var b = this;
                var a = document.createElement("div");
                a.setAttribute("class", "box");
                for (var i = 0, j; j = this.options.btns[i]; i++) {
                    (function (l) {
                        var h = document.createElement("a");
                        h.setAttribute("href", "javascript:;");
                        h.setAttribute("class", "dialogBtn");
                        h.innerHTML = l.name;
                        if (l.fn) {
                            h.onclick = function () {
                                l.fn.call(this, b)
                            }
                        }
                        var g = document.createElement("div");
                        g.appendChild(h);
                        a.appendChild(g)
                    })(j)
                }
                this.options.wrapper.querySelectorAll("footer")[0].appendChild(a)
            }
            return this
        },
        show: function () {
            var a = this.options.wrapper.classList;
            a.add("on");
            return this
        },
        hide: function () {
            var a = this.options.wrapper.classList;
            a.remove("on");
            return this
        },
        die: function () {
            try {
                var b = this;
                this.hide();
                setTimeout(function () {
                    delete d.list[b.options.id];
                    d.container.removeChild(b.options.wrapper)
                }, 300)
            } catch (a) {
                $("#dialoger div[data-type]").remove()
            } finally { }
            return this
        }
    };
    e.merge = function (b, c, a) {
        for (var h in c) {
            b[h] = c[h]
        }
        return b
    };
    return e
})();
var iTemplate = (function () {
    var b = function () { };
    b.prototype = {
        makeList: function (o, a, k) {
            var m = [],
				l = [],
				q = /{(.+?)}/g,
				p = {},
				n = 0;
            for (var r in a) {
                if (typeof k === "function") {
                    p = k.call(this, r, a[r], n++) || {}
                }
                m.push(o.replace(q, function (d, c) {
                    return (c in p) ? p[c] : (undefined === a[r][c] ? a[r] : a[r][c])
                }))
            }
            return m.join("")
        }
    };
    return new b()
})();
String.prototype.format = function (params) {
    if (arguments.length == 0) throw 'format arguments is null.';
    if (arguments.length > 1 && params.constructor != Array) {
        params = $.makeArray(arguments).slice(0);
    }
    if (params.constructor != Array) {
        params = [params];
    }
    var source = this;
    $.each(params, function (i, n) {
        source = source.replace(new RegExp("\\{" + i + "\\}", "g"), n);
    });
    return source;
};
String.prototype.trim = function () {
    if (this) return $.trim(this).replace(/(^[\s\xA0]*)|([\s\xA0]*$)/g, "");
    else return '';
};
