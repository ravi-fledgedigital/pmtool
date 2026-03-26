(function (t) {
    var e = {
        url: !1,
        callback: !1,
        target: !1,
        duration: 120,
        on: "mouseover",
        touch: !0,
        onZoomIn: !1,
        onZoomOut: !1,
        magnify: 1
    };
    t.zoom = function(e, i, n, o) {
        var s, r, a, l, c, u, d, h = t(e).css("position"), p = t(i);
        return e.style.position = /(absolute|fixed)/.test(h) ? h : "relative",
            e.style.overflow = "hidden",
            n.style.width = n.style.height = "",
            t(n).addClass("zoomImg").css({
                position: "absolute",
                top: 0,
                left: 0,
                opacity: 0,
                width: n.width * o,
                height: n.height * o,
                border: "none",
                maxWidth: "none",
                maxHeight: "none"
            }).appendTo(e),
            {
                init: function() {
                    r = t(e).outerWidth(),
                        s = t(e).outerHeight(),
                        i === e ? (l = r,
                            a = s) : (l = p.outerWidth(),
                            a = p.outerHeight()),
                        c = (n.width - r) / l,
                        u = (n.height - s) / a,
                        d = p.offset()
                },
                move: function(t) {
                    var e = t.pageX - d.left
                        , i = t.pageY - d.top;
                    i = Math.max(Math.min(i, a), 0),
                        e = Math.max(Math.min(e, l), 0),
                        n.style.left = e * -c + "px",
                        n.style.top = i * -u + "px"
                }
            }
    }, t.fn.zoom = function(i) {
        return this.each(function() {
            var n, o = t.extend({}, e, i || {}), s = o.target || this, r = this, a = t(r), l = document.createElement("img"), c = t(l), u = "mousemove.zoom", d = !1, h = !1;
            (o.url || (n = a.find("img"),
            n[0] && (o.url = n.data("src") || n.attr("src")),
                o.url)) && (function() {
                var t = s.style.position
                    , e = s.style.overflow;
                a.one("zoom.destroy", function() {
                    a.off(".zoom"),
                        s.style.position = t,
                        s.style.overflow = e,
                        c.remove()
                })
            }(),
                l.onload = function() {
                    function e(e) {
                        n.init(),
                            n.move(e),
                            c.stop().fadeTo(t.support.opacity ? o.duration : 0, 1, !!t.isFunction(o.onZoomIn) && o.onZoomIn.call(l))
                    }
                    function i() {
                        c.stop().fadeTo(o.duration, 0, !!t.isFunction(o.onZoomOut) && o.onZoomOut.call(l))
                    }
                    var n = t.zoom(s, r, l, o.magnify);
                    "grab" === o.on ? a.on("mousedown.zoom", function(o) {
                        1 === o.which && (t(document).one("mouseup.zoom", function() {
                            i(),
                                t(document).off(u, n.move)
                        }),
                            e(o),
                            t(document).on(u, n.move),
                            o.preventDefault())
                    }) : "click" === o.on ? a.on("click.zoom", function(o) {
                        return d ? void 0 : (d = !0,
                            e(o),
                            t(document).on(u, n.move),
                            t(document).one("click.zoom", function() {
                                i(),
                                    d = !1,
                                    t(document).off(u, n.move)
                            }),
                            !1)
                    }) : "toggle" === o.on ? a.on("click.zoom", function(t) {
                        d ? i() : e(t),
                            d = !d
                    }) : "mouseover" === o.on && (n.init(),
                        a.on("mouseenter.zoom", e).on("mouseleave.zoom", i).on(u, n.move)),
                    o.touch && a.on("touchstart.zoom", function(t) {
                        t.preventDefault(),
                            h ? (h = !1,
                                i()) : (h = !0,
                                e(t.originalEvent.touches[0] || t.originalEvent.changedTouches[0]))
                    }).on("touchmove.zoom", function(t) {
                        t.preventDefault(),
                            n.move(t.originalEvent.touches[0] || t.originalEvent.changedTouches[0])
                    }),
                    t.isFunction(o.callback) && o.callback.call(l)
                }
                ,
                l.src = o.url)
        })
    }, t.fn.zoom.defaults = e
})(jQuery);
