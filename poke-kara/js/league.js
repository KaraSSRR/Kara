var f = !0,
    h = null,
    k = !1;

function aa() {
    return function() {}
}

function ba(a) {
    return function() {
        return this[a]
    }
}
VERSION = "2.5.1";
var ca, da = 100,
    l = {};
Game = {
    Fd: f,
    ce: function() {
        moment.locale(Lang.locale);
        this.paused = k;
        ea();
        globalCallbacks = {};
        document.title = n("{{game_title}}");
        new p("init", {
            J: function(a) {
                l = (new ga(a.trainer)).addClassName("limited");
                l.Lb = a.loc_id;
                l.Vk = a.access;
                l.key = a.key;
                l.dc = k;
                l.Bb = k;
                l.cc = k;
                l.Ng = function() {
                    return l.Bb || l.dc || l.cc
                };
                l.eb = h;
                l.ea = h;
                ha = new ha(a.libs);
                ia = new ia;
                ja = new ja;
                q = new q;
                ma = new ma;
                r = new r;
                s = new s;
                t = new t;
                u = new u;
                w = new w;
                x = new x(a.servertime);
                na = new na;
                oa = new oa;
                y = new y;
                z = new z;
                A = new A;
                B = new B;
                C = new C;
                pa = new pa;
                D = new D;
                E = new E;
                F = new F;
                sa = new sa;
                ta = new ta;
                ua = new ua;
                va = new va;
                H = new H;
                I = new I;
                J = new J;
                l.lj();
                $("body").on(wa ? "touchstart" : "mousedown", Game.click);
                Event.observe(window, "focus", function() {
                    Game.Fd = f;
                    K.To()
                });
                Event.observe(window, "blur", function() {
                    Game.Fd = k
                });
                $("divGame").setStyle({
                    display: "block"
                });
                window.onresize = Game.ok;
                Game.ok();
                Exp.ce();
                window.op = function() {
                    VK.init({
                        Yo: 4561803
                    })
                };
                setTimeout(function() {
                    $("vk_api_transport").C(new Element("script", {
                        type: "text/javascript",
                        src: "//vk.com/js/api/openapi.js",
                        async: f
                    }))
                }, 0);
                $("body").setStyle({
                    backgroundImage: "none"
                });
                0 < document.location.search.indexOf("updver") && new L(M, "info", "{{game_update:" + VERSION + "}}");
                var b = (new N("{{game_enter}}", "btnStart")).click(function() {
                    b.Cb();
                    na.start()
                });
                u.start(b, {
                    zn: f
                });
                if (CONF_DEV) b.onclick()
            }.bind(this)
        })
    },
    Pn: function(a) {
        l.Ed = a.ugroup;
        l.Za = a.clan_id;
        l.lc = a.clan_is_lead
    },
    reload: function(a) {
        window.location.replace("http://" + CONF_DOMAIN_GAME + (a ? "?" + a : ""))
    },
    pause: function(a, b, c) {
        a ? (this.paused = b, u.start(c)) : this.paused ==
            b && (this.paused = k, u.stop())
    },
    ok: function() {
        D.fc();
        I.fc();
        B.fc()
    },
    click: function(a) {
        wa || (xa(a, q.B) || q.hide(), r.kd && r.Ia(a));
        ca = a
    },
    Gm: function() {
        Hotkeys.hotkeys = [];
        E.params.hotkeys && (Hotkeys.bind("alt+d", A.R), Hotkeys.bind("alt+o", D.open, "profile"), Hotkeys.bind("alt+p", D.open, "pokes"), Hotkeys.bind("alt+i", D.open, "items"), Hotkeys.bind("alt+m", D.open, "talks"), Hotkeys.bind("alt+q", B.li.bind(B)), Hotkeys.bind("alt+q", B.focus.bind(B)), Hotkeys.bind("alt+w", B.Jl.bind(B)), Hotkeys.bind("alt+c", B.Fc.bind(B, "")),
            Hotkeys.bind("alt+v", B.focus.bind(B)), Hotkeys.bind("alt+f", y.close), Hotkeys.bind("alt+e", PC.Eg.bind(PC)), Hotkeys.bind("alt+1", function() {
                y.Gd(y.O.V.R.wa[1], 1)
            }), Hotkeys.bind("alt+2", function() {
                y.Gd(y.O.V.R.wa[2], 2)
            }), Hotkeys.bind("alt+3", function() {
                y.Gd(y.O.V.R.wa[3], 3)
            }), Hotkeys.bind("alt+4", function() {
                y.Gd(y.O.V.R.wa[4], 4)
            }));
        Hotkeys.bind("esc", function() {
            $("divAlerten").empty() ? (t.close(), D.close(), w.close(), A.If(0)) : $("divAlerten").C()
        })
    },
    xj: function() {
        O("{{game_logout_confirm}}") && (u.start("{{game_logout}}"),
            new p("logout", {
                onComplete: function() {
                    window.location = "/"
                }
            }))
    },
    Do: function(a) {
        window.location = "http://" + CONF_DOMAIN_WWW + (a ? a : "")
    }
};
window.Game = Game;
window.Game.init = Game.ce;

function P(a, b) {
    return Math.floor(Math.random() * (b - a + 1)) + a
}

function ya(a) {
    a = parseInt(a);
    return a.toPaddedString(3)
}

function za(a) {
    var b, c, d;
    b = b || ".";
    c = c || ",";
    "undefined" == typeof d && (d = 0);
    for (var e = parseInt(a).toString(), m = /(-?\d+)(\d{3})/; m.test(e);) e = e.replace(m, "$1" + b + "$2");
    if (!d) return e;
    a = a.toString();
    0 <= a.indexOf(".") ? (a = a.toString().substr(a.indexOf(".") + 1, d), a += Array(d - a.length + 1).join("0")) : a = Array(d + 1).join("0");
    return e + c + a
}

function Aa(a, b) {
    if (!b || b == n("{{currency_credit}}")) b = n("{{currency_credit_alias}}");
    return 4 < b.length ? b + " x" + za(a) : za(a) + " " + b
}

function Ba(a, b) {
    return '<span class="' + (0 <= a ? 'greennumber">+' : 'rednumber">') + a + (b ? b : "") + "</span>"
}

function Ca(a, b) {
    var c;
    if (0 > (c = $("body").getWidth() - (b.x + a.getWidth() + 10))) b.x += c;
    if (0 > (c = $("body").getHeight() - (b.y + a.getHeight() + 10))) b.y += c;
    3 > b.x && (b.x = 3);
    3 > b.y && (b.y = 3);
    a.setStyle({
        left: b.x + "px",
        top: b.y + "px"
    })
}

function Da(a, b) {
    b || (b = {
        x: 5,
        y: 5
    });
    var c = Ea(ca);
    c.x += b.x;
    c.y += b.y;
    Ca(a, c)
}

function Fa(a, b, c) {
    c || (c = {
        x: 0,
        y: 0
    });
    b = {
        x: b.cumulativeOffset().left + c.x,
        y: b.cumulativeOffset().top + c.y
    };
    Ca(a, b)
}

function Ea(a) {
    var b = {
        x: 0,
        y: 0
    };
    if (!a) return b;
    if ("touchstart" == a.type || "touchmove" == a.type || "touchend" == a.type || "touchcancel" == a.type) a = a.touches[0] || a.changedTouches[0], b = {
        x: a.pageX,
        y: a.pageY
    };
    else if ("mousedown" == a.type || "mouseup" == a.type || "mousemove" == a.type || "mouseover" == a.type || "mouseout" == a.type || "mouseenter" == a.type || "mouseleave" == a.type) b = {
        x: a.pageX,
        y: a.pageY
    };
    return b
}

function Ga(a) {
    return {
        x: a.cumulativeOffset().left + Math.round(a.getWidth() / 2),
        y: a.cumulativeOffset().top + Math.round(a.getHeight() / 2)
    }
}

function xa(a, b) {
    if (!b.visible()) return k;
    var c = b.cumulativeOffset().left,
        d = b.cumulativeOffset().left + b.getWidth(),
        e = b.cumulativeOffset().top,
        m = b.cumulativeOffset().top + b.getHeight();
    a = Ea(a);
    return a.x > c && a.x < d && a.y > e && a.y < m
}

function Ha(a) {
    var b, c, d;
    if (0 >= a) return "{{date_timer_sec:0}}";
    if (b = Math.floor(a / 60)) a -= 60 * b;
    if (c = Math.floor(b / 60)) b -= 60 * c;
    if (d = Math.floor(c / 24)) c -= 24 * d;
    return (d ? "{{date_timer_day:" + d + "}} " : "") + (c ? "{{date_timer_hour:" + c + "}} " : "") + (b ? "{{date_timer_min:" + b + "}} " : "") + (a ? "{{date_timer_sec:" + a + "}} " : "")
}

function Ia(a) {
    var b = "",
        c = 10 >= a.length;
    a = moment(a);
    if (!a.isValid()) return "";
    if (2 >= x.na.diff(a, "minute")) return "{{date_just_now}}";
    x.na.isSame(a, "day") && (b = "{{date_today}}");
    x.na.isSame(moment(a).add(1, "day"), "day") && (b = "{{date_yesterday}}");
    b || (b = a.format("D MMM"));
    return b = x.na.isSame(a, "year") ? b + (c ? "" : a.format(", HH:mm")) : b + a.format(" YYYY")
}

function Ja(a) {
    var b = "";
    a = moment(a);
    if (!a.isValid()) return "";
    var c = x.na.diff(a, "hours"),
        d = x.na.diff(a, "years");
    24 >= c && (b = "{{date_one_day}} ");
    if (1 <= d) {
        b = moment(x.na).subtract(d, "years").from(x.na, f) + " ";
        a.add(d, "years");
        if (31 > x.na.diff(a, "days")) return b;
        11 <= x.na.diff(a, "months") && a.add("months", 1);
        b += " {{date_and}} "
    }
    return n(b + a.from(x.na, f))
}

function Q(a, b) {
    switch (a) {
        case "spinpt":
            return (new Element("span")).addClassName("inpt").C(b);
        case "empty":
            return (new Element("span")).addClassName("empty").C(b ? b : "{{empty}}")
    }
}

function R() {
    return (new Element("div")).addClassName("hr")
}

function O(a) {
    return confirm(n(a))
}
var oa = Class.create({
        initialize: function() {
            this.socket = 0
        },
        start: function() {
            this.socket.connected || (this.socket = io("http://" + CONF_DOMAIN_IO), this.socket.on("reconnect", aa()), this.socket.on("event", function(a, b) {
                evalsaver = b;
                eval(a + "(" + (b ? "evalsaver" : "") + ");")
            }))
        },
        emit: function(a, b, c) {
            this.socket.emit(a, b, c)
        }
    }),
    ja = Class.create({
        initialize: function() {
            this.ue = {};
            this.G = {};
            this.rb = 0;
            this.B = (new Element("div")).addClassName("acomplete");
            $("body").ins(this.B);
            return this
        },
        Sa: function(a, b, c) {
            b.on("keyup",
                this.sk.bind(this, a, b, c));
            b.on("keydown", this.navigate.bind(this, 0));
            b.on("blur", this.hide.bind(this))
        },
        navigate: function(a, b) {
            var c = this.B.select(".variant.active"),
                c = c.size() ? c[0] : 0;
            if (38 == b.keyCode || 40 == b.keyCode) {
                var d = this.B.select(".variant");
                c ? (c.removeClassName("active"), 40 == b.keyCode ? (c = c.next()) || (c = d[0]) : (c = c.previous()) || (c = d[d.size() - 1]), c.addClassName("active")) : d[40 == b.keyCode ? 0 : d.size() - 1].addClassName("active")
            }
            a && (c && c.removeClassName("active"), a.addClassName("active"));
            if (13 == b.keyCode ||
                "mousedown" == b.type && a) {
                if (!c) return this.hide();
                b.preventDefault();
                txt = c.innerHTML.stripTags();
                this.rb.Ba.value = txt;
                Object.isFunction(this.rb.$b) && this.rb.$b(txt);
                this.hide()
            }
        },
        sk: function(a, b, c, d) {
            if (13 != d.keyCode) {
                var e = b.value.trim().toLocaleLowerCase();
                if (!(this.rb && this.rb.la == e)) {
                    this.B.update("");
                    if (e) {
                        if (!this.ue[a]) return this.bn(a, this.sk.bind(this, a, b, c, d));
                        for (var m = d = 0; d < this.ue[a].size() && 5 > m; d++) {
                            var g = this.ue[a][d].title;
                            0 === g.toLowerCase().indexOf(e) && (g = "<b>" + g.slice(0, e.length) +
                                "</b>" + g.slice(e.length), g = (new Element("div")).addClassName("variant").update(g), g.on("mouseover", this.navigate.bind(this, g)), g.on("mousedown", this.navigate.bind(this, g)), this.B.insert(g), m++)
                        }
                        this.rb = {
                            Ba: b,
                            $b: c,
                            la: e
                        }
                    }
                    this.show(b)
                }
            }
        },
        show: function(a) {
            if (this.B.empty()) return this.hide();
            this.B.style.width = a.getWidth() + "px";
            this.B.style.left = a.cumulativeOffset().left + "px";
            this.B.style.top = a.cumulativeOffset().top + (a.cumulativeOffset().top < $("body").getHeight() / 2 ? a.getHeight() : -this.B.getHeight()) +
                "px";
            this.B.show()
        },
        hide: function() {
            this.B.hide();
            this.rb = 0
        },
        bn: function(a, b) {
            this.G[a] || (this.G[a] = f, new p("acomplete/" + a, {
                J: function(c) {
                    this.ue[a] = c && Object.isArray(c) && c.size() ? c : [];
                    b && b()
                }.bind(this),
                onComplete: function() {
                    this.G[a] = k
                }.bind(this)
            }))
        }
    }),
    p = Class.create(Ajax.Request, {
        initialize: function($super, b, c, d) {
            Object.isArray(c) || (d = c, c = []);
            d || (d = {});
            d.parameters || (d.parameters = {});
            d.aa && (d.parameters.navi_p = d.aa[0], d.parameters.navi_s = d.aa[1] ? d.aa[1] : "", d.parameters.navi_o = d.aa[2] ? d.aa[2] :
                "", d.parameters.navi_r = d.aa[3] ? d.aa[3] : "", d.parameters.navi_f = d.aa[4] ? d.aa[4].join("/") : "");
            c.size() && (d.parameters.vars = c.join("/"));
            l && l.key && (d.parameters.t_key = l.key);
            this.params = d;
            this.Cb(d.G);
            this.se = d.se ? d.se : {};
            d.method = "post";
            d.onSuccess = function(b) {
                b = b.responseJSON;
                if (!b) return k;
                b.wait ? u.start(b.wait.txt, {
                    duration: b.wait.dur,
                    Wo: b.wait.worry,
                    callback: this.finish.bind(this, b)
                }) : this.finish(b)
            }.bind(this);
            d.onFailure = function(b) {
                this.hn();
                new L(M, "error", "Error!<br>" + b.responseText);
                if (this.params.onError) this.params.onError()
            }.bind(this);
            $super("/do/" + b, d)
        },
        finish: function(a) {
            this.fb(500 != a.code);
            a.alerten && new L(M, a.alerten.type, a.alerten.text, this.se);
            switch (a.code) {
                case 500:
                    Game.paused && Game.pause(k, "php");
                    this.params.J && this.params.J(a.object);
                    break;
                case 450:
                    Game.pause(f, "php", a.object);
                    break;
                case 400:
                    if (this.params.onError) this.params.onError(a.object);
                    break;
                case 403:
                    window.location = "http://" + CONF_DOMAIN_WWW + "#auth"
            }
            if (a.evals) try {
                eval(a.evals)
            } catch (b) {
                console.log(b, 'evals: "' + a.evals + '"')
            }
        },
        Cb: function(a) {
            (this.G = a) ? (Object.isElement(a) &&
                this.G.C().addClassName("ajxloading"), Object.isFunction(a.Cb) && a.Cb()) : this.G = k
        },
        fb: function(a) {
            this.G && (Object.isElement(this.G) && this.G.C().removeClassName("ajxloading"), Object.isFunction(this.G.fb) && this.G.fb(a))
        },
        hn: function() {
            this.G && (Object.isElement(this.G) && this.G.removeClassName("ajxloading").C((new Element("div")).addClassName("ajxerror")), Object.isFunction(this.G.fb) && this.G.fb(f))
        }
    }),
    M = 0,
    L = Class.create({
        initialize: function(a, b, c, d) {
            d || (d = {});
            this.timeout = "undefined" !== typeof d.timeout ?
                parseInt(d.timeout) : ALERTEN_TIMEOUT;
            this.B = (new Element("div")).addClassName("alerten " + b);
            S && T(this.B, this.hide.bind(this));
            this.va = (new Element("div")).addClassName("divIcon");
            this.B.ins(this.va);
            this.Vc = (new Element("div")).addClassName("divContainer");
            this.B.ins(this.Vc);
            this.hg = (new Element("div")).addClassName("divDat txtgray").C(d.sa ? d.sa : "");
            this.Vc.ins(this.hg);
            this.ib = (new N("", "ctrl nobg close")).click(this.hide.bind(this, 1));
            this.Vc.ins(this.ib);
            d.from && (this.Yl = (new Element("div")).addClassName("divFrom").C(d.from),
                this.Vc.ins(this.Yl));
            this.lb = (new Element("div")).addClassName("divContent").C(c);
            this.Vc.ins(this.lb);
            if (a) switch (a) {
                case M:
                    break;
                case 1:
                    this.timeout = 0;
                    this.xe = (new N(d.Uf ? d.Uf : "{{button_ok}}", "gray")).click(d.J ? d.J.bind(this) : this.hide.bind(this));
                    this.Ye(this.xe);
                    break;
                case 2:
                    this.ai = (new N(d.bi ? d.bi : "{{button_yes}}", "gray")).click(this.hide.bind(this)).click(d.lf ? d.lf.bind(this) : h, 1);
                    this.vl = (new N(d.Th ? d.Th : "{{button_no}}", "gray")).click(this.hide.bind(this)).click(d.Wg ? d.Wg.bind(this) : h, 1);
                    d.Wg && this.ai.click(d.lf.bind(this), 1);
                    this.Ye(this.ai).Ye(this.vl);
                    break;
                default:
                    a && this.Ye(a)
            }(a = $("divAlerten").firstDescendant()) ? a.ins({
                before: this.B
            }) : $("divAlerten").ins(this.B);
            this.show();
            return this
        },
        hide: function(a) {
            a ? this.remove() : (this.B.addClassName("fadeOutDown animated"), this.remove.bind(this).delay(0.5))
        },
        remove: function() {
            this.B && Object.isFunction(this.B.remove) && this.B.remove()
        },
        show: function() {
            this.B.show();
            this.timeout && this.hide.bind(this).delay(this.timeout)
        },
        Ye: function(a) {
            this.Wb ||
                (this.Wb = (new Element("div")).addClassName("divButtons"), this.Vc.ins(this.Wb));
            this.Wb.ins(a);
            return this
        }
    }),
    V = Class.create({
        initialize: function(a) {
            a || (a = {});
            this.params = a;
            this.disabled = 0;
            return this
        },
        toElement: ba("B"),
        hide: function() {
            this.B.hide();
            return this
        },
        show: function() {
            this.B.show();
            return this
        },
        U: function(a) {
            this.B.U(a);
            return this
        },
        visible: function() {
            return this.B.visible()
        },
        Ha: function(a) {
            this.B.Ha(a);
            return this
        },
        addClassName: function(a) {
            this.B.addClassName(a);
            return this
        },
        qa: function(a,
            b) {
            this.B.qa(a, b);
            return this
        },
        removeClassName: function(a) {
            this.B.removeClassName(a);
            return this
        },
        hasClassName: function(a) {
            return this.B.hasClassName(a)
        },
        disable: function(a) {
            a ? this.B.addClassName("disabled") : this.B.removeClassName("disabled");
            this.disabled = a;
            return this
        },
        remove: function() {
            this.B.remove()
        },
        C: function(a) {
            this.B.C(a);
            return this
        },
        Ra: function(a) {
            this.B.Ra(a);
            return this
        },
        ins: function(a) {
            this.B.ins(a);
            return this
        },
        Cb: function() {
            this.B.C().addClassName("ajxloading")
        },
        fb: function() {
            this.B.C().removeClassName("ajxloading")
        }
    }),
    N = Class.create(V, {
        initialize: function($super, b, c, d) {
            $super(d);
            this.sj = 0;
            this.B = new Element("div", {
                "class": "button " + (c ? c : "")
            });
            this.params.id && (this.B.id = this.params.id);
            this.params.U && this.B.U(this.params.U, f);
            this.params.title && (this.B.title = n(this.params.title));
            this.C(b);
            T(this.B, this.onclick.bind(this));
            return this
        },
        C: function(a) {
            this.title = a;
            this.B.C(this.title);
            return this
        },
        click: function(a, b) {
            b || (b = 0);
            this.Na || (this.Na = []);
            this.Na[b] = a;
            return this
        },
        onclick: function(a) {
            if (!this.G && !this.disabled) {
                if (!S) {
                    if (!this.params.Ej) {
                        var b =
                            (new Date).getTime();
                        if (500 > b - this.sj) return;
                        this.sj = b
                    }
                    this.params.ta || (this.B.addClassName("highlight animated3"), this.B.removeClassName.bind(this.B, "highlight animated3").delay(0.3))
                }
                if (this.Na) {
                    if (!a || !a.altKey) {
                        if (this.Na[0]) this.Na[0]();
                        if (this.Na[1]) this.Na[1]();
                        if (this.Na[2]) this.Na[2]()
                    }
                    if (a && a.altKey && this.Na[9]) this.Na[9]();
                    a && a.stopPropagation && a.stopPropagation()
                }
                return this
            }
        },
        Cb: function() {
            this.G = f;
            this.B.C("&nbsp;").addClassName("loading")
        },
        fb: function() {
            this.G = k;
            this.B.C(this.title).removeClassName("loading")
        }
    }),
    w = Class.create({
        initialize: function() {
            this.B = $("divCards");
            var a = (new Element("div")).addClassName("divCardsContainer");
            this.nl = (new N("", "btnCardsClose", {
                ta: f
            })).click(this.close);
            this.B.ins(a).ins(this.nl);
            this.he = ["divPokedex", "divPokeCard", "divTrainerCard", "divClanCard"];
            this.he.each(function(b) {
                a.ins(new Element("div", {
                    id: b,
                    "class": "card"
                }))
            });
            this.B.hide()
        },
        open: function(a) {
            for (var b = 0; b < this.he.length; b++) this.he[b] !== a && $(this.he[b]).hide();
            $(a).show();
            w.B.removeClassName("bounceOutUp animated").show();
            w.B.setStyle({
                zIndex: da++
            });
            Ka()
        },
        close: function() {
            w.B.visible() && (S ? (w.B.hide(), Ka()) : (w.B.addClassName("bounceOutUp animated"), w.B.hide.bind(w.B).delay(0.5)))
        },
        rb: function() {
            var a = "";
            this.he.each(function(b) {
                $(b).visible() && (a = b)
            });
            return a
        }
    }),
    x = Class.create({
        initialize: function(a) {
            this.na = moment(a);
            this.diff = -moment().diff(a, "seconds");
            this.sg = 0;
            this.B = $("divDockClock");
            this.go.bind(this).delay(3);
            return this
        },
        $n: function() {
            return this.na.subtract(this.diff, "second").unix()
        },
        go: function() {
            this.na =
                moment().add(this.diff, "second");
            this.sg = "hidden" == this.sg ? "visible" : "hidden";
            this.B.update(this.na.format("HH") + '<span style="visibility:' + this.sg + '">:</span>' + this.na.format("mm"));
            x.go.bind(this).delay(1);
            E.ag();
            I.ag()
        }
    }),
    q = Class.create({
        initialize: function() {
            this.F = [];
            this.B = (new Element("div")).addClassName("divContext").hide();
            this.pa = (new Element("div")).addClassName("divTitle");
            this.kg = (new Element("div")).addClassName("divLoading");
            this.Yc = (new Element("div")).addClassName("divElements");
            this.B.ins(this.pa).ins(this.kg).ins(this.Yc);
            S && this.B.ins(T((new Element("div")).addClassName("divElement").C("{{button_close}}"), this.hide.bind(this)));
            $("body").ins(this.B);
            return this
        },
        add: function(a, b, c) {
            if ("---" == a) return this.ld();
            c || (c = {});
            a = {
                B: (new Element("div")).addClassName("divElement").C(a),
                $b: b
            };
            b && a.B.addClassName("clickable");
            c.ra && a.B.addClassName(c.ra);
            c.selected && a.B.addClassName("selected");
            c.ta || a.B.addClassName("highlighted");
            T(a.B, this.click.bind(this, a));
            this.F.push(a);
            this.Yc.ins(a.B);
            this.B.visible() && this.Yb();
            return this
        },
        ld: function() {
            this.Yc.ins(R());
            return this
        },
        click: function(a) {
            a.$b && (this.Tb = this.hide.bind(this).delay(0.3), a.$b(), a.B.addClassName("highlight animated3"), a.B.removeClassName.bind(a.B, "highlight animated3").delay(0.3))
        },
        clear: function() {
            this.F.clear();
            this.Yc.C();
            return this
        },
        show: function(a, b) {
            this.clear();
            this.fb();
            b || (b = {});
            clearTimeout(this.Tb);
            this.Tb = 0;
            this.B.setStyle({
                zIndex: da++
            });
            b.mf ? Fa(this.B, b.mf, {
                x: b.mf.getWidth() + 2,
                y: 5
            }) : Da(this.B, b.jp ? {
                x: -this.B.getWidth(),
                y: 0
            } : 0);
            ma.hide();
            this.B.show();
            this.pa.C(a ? a : "");
            return this
        },
        Yb: function() {
            Fa(this.B, this.B)
        },
        hide: function() {
            this.B.hide();
            return this
        },
        Cb: function() {
            this.kg.show();
            this.Yc.hide()
        },
        fb: function(a) {
            this.kg.hide();
            this.Yc.show();
            a && this.hide()
        }
    });

function ea() {
    wa = (S = (La = /iphone|ipad|ipod|android|blackberry|mini|windows\sce|palm/i.test(navigator.userAgent.toLowerCase())) && 1 != Cookie.get("notdevice")) && "ontouchstart" in window;
    $("body").qa("device", S);
    $("body").qa("full", !S);
    if (La && S) {
        Ma = (new N("", "btnDockDevice")).click(function() {
            $("divDock").toggleClassName("active");
            Ma.qa("active", $("divDock").visible());
            $("divOnline").hide()
        });
        Na = (new N("", "btnChatVisio infinite blinkred")).click(function() {
            Na.removeClassName("animated");
            Ka($("divVisios").visible() ?
                "chat" : "visio")
        });
        Oa = (new N("", "btnClose infinite blinkred")).click(function() {
            if (Pa) {
                Oa.removeClassName("animated");
                for (var a = 0; a < Pa.size(); a++)
                    if (Pa[a].B.visible()) {
                        Pa[a].close();
                        break
                    }
            }
        }).hide();
        $("divDockUpper").C(Ma).ins(Na).ins(Oa);
        $("divInput").ins($("divOnlineAmount"));
        $("divChat").ins({
            before: $("divInput")
        });
        T($("divOnlineAmount"), function() {
            $("divOnline").toggle();
            $("divDock").removeClassName("active")
        });
        var a = (new Element("div")).addClassName("divFightIH").ins((new Element("div")).addClassName("wrap").ins($("divFightI")).ins($("divFightH")));
        $("divFightData").ins({
            before: a
        }).ins({
            before: $("divFightCaptcha")
        }).ins({
            before: $("divFightButtons")
        });
        Ka("visio")
    }
}

function Qa() {
    Cookie.set("notdevice", 1 == Cookie.get("notdevice") ? 0 : 1);
    Game.reload()
}

function Ka(a) {
    if (S)
        if (q && q.hide && q.hide(), r && r.Ia && r.Ia(), $("divDock").removeClassName("active"), a) $("divOnline").hide(), w.close && w.close(), "visio" == a && ($("divVisios").show(), $("divChat").hide(), $("divInput").hide(), Na.removeClassName("active")), "chat" == a && (Na.C(), $("divVisios").hide(), $("divChat").show(), $("divInput").show(), Na.addClassName("active"));
        else if (w.B) {
        Pa || (Pa = [w, t, D, J]);
        for (a = 0; a < Pa.size(); a++) Pa[a].B.addClassName("hidden");
        $("divOnline").hide();
        $("divVisios").hide();
        $("divChat").hide();
        $("divInput").hide();
        Na.hide();
        a = 0;
        for (var b = k; a < Pa.size(); a++)
            if (Pa[a].B.visible()) {
                Pa[a].B.removeClassName("hidden");
                b = f;
                break
            }
        b ? (Na.hide(), Oa.show()) : (Ka(Na.hasClassName("active") ? "chat" : "visio"), Na.show(), Oa.hide())
    }
}

function Ra() {
    S && (Oa.visible() && Oa.addClassName("animated"), Na.hasClassName("active") && Na.addClassName("animated"), Na.hasClassName("active"))
}
var La, S, wa, Ma, Na, Oa, Pa, t = Class.create({
        initialize: function() {
            this.B = (new Element("div")).addClassName("divDialog");
            this.ib = (new N("", "ctrl nobg close")).click(this.close.bind(this));
            this.pa = (new Element("div")).addClassName("divDialogTitle");
            this.lb = (new Element("div")).addClassName("divDialogContent");
            this.B.ins(this.ib).ins(this.pa).ins(R()).ins(this.lb);
            $("body").ins(this.B);
            ia.Ql(this.pa, this.B);
            this.close();
            return this
        },
        show: function(a, b) {
            b || (b = {});
            S && w.B.visible() && w.close();
            this.name = b.name;
            this.B.setStyle({
                zIndex: da++,
                width: b.width ? b.width + "px" : "auto"
            });
            this.fb();
            this.title(a);
            this.C();
            if (!this.bc()) {
                switch (b.position) {
                    case "center":
                        var c = this.B,
                            d = {
                                x: $("body").getWidth() / 2 - c.getWidth() / 2,
                                y: 150
                            };
                        Ca(c, d);
                        break;
                    case "upleft":
                        Ca(this.B, {
                            x: 0,
                            y: 0
                        });
                        break;
                    default:
                        Da(this.B)
                }
                this.B.show()
            }
            Ka();
            return this
        },
        close: function() {
            this.B.hide();
            this.pa.C();
            this.C();
            Ka()
        },
        title: function(a) {
            this.pa.C(a)
        },
        C: function(a) {
            this.lb.C();
            a && this.ins(a);
            return this
        },
        ins: function(a) {
            this.lb.ins(a);
            this.Cj();
            return this
        },
        Cj: function() {
            var a = this.B,
                b = {
                    x: a.cumulativeOffset().left,
                    y: a.cumulativeOffset().top
                };
            Ca(a, b);
            return this
        },
        bc: function(a) {
            return this.B.visible() && (!a || a == this.name)
        },
        wc: function(a) {
            a || (a = "{{button_cancel}}");
            this.ins((new N(a, "gray")).click(this.close.bind(this)))
        },
        Cb: function() {
            this.B.addClassName("loading")
        },
        fb: function() {
            this.B.removeClassName("loading")
        },
        alert: function(a, b) {
            this.show(a);
            this.C(b)
        }
    }),
    D = Class.create({
        initialize: function() {
            this.size = 100;
            this.k = 0.85;
            this.Ig = Math.round(this.size *
                this.k);
            this.Ib = (new Element("div")).addClassName("divDockIcons");
            this.Xc = (new Element("div")).addClassName("divDockIn");
            this.sb = (new Element("div")).addClassName("divDockHint hint downside");
            this.Li = (new Element("div")).addClassName("divDockPanelsArrow");
            this.B = (new Element("div")).addClassName("divDockPanels");
            this.Mi = (new Element("div")).addClassName("divDockPanelsTitle");
            this.cm = (new N("", "ctrl nobg close")).click(this.close.bind(this));
            this.ng = (new Element("div")).addClassName("divDockPanelsContent");
            this.B.ins(this.Li).ins(this.cm).ins(this.Mi).ins(this.ng).hide();
            this.Ib.ins(this.Xc);
            $("divDockMenu").ins(this.Ib).ins(this.sb).ins(this.B);
            this.pf = $H();
            S ? (this.Di = (new Element("div")).addClassName("divDockDeviceButtons"), this.Di.ins(B.F.Qc).ins(B.F.Ae).ins(B.F.Xf).ins(B.F.Rf), B.F.Qc.C(""), B.F.Rf.C("").addClassName("ctrl"), $("divDock").ins({
                after: this.B
            }), $("divDockUser").ins({
                before: this.Di
            })) : (this.Ib.setStyle({
                height: this.size + "px"
            }), La || ($("body").on("mousemove", this.ge.bind(this)), this.Xc.on("mouseout",
                this.Po.bind(this))));
            $("divDockUser").C(l);
            this.sl = (new N("", "circle btnHelp", {
                ta: f,
                title: "{{dock_help}}"
            })).click(this.fj);
            this.tl = (new N("", "circle btnLogout red", {
                ta: f,
                title: "{{dock_logout}}"
            })).click(Game.xj);
            $("divDockClock").ins({
                before: this.sl
            }).ins({
                before: this.tl
            })
        },
        fj: function() {
            q.show();
            q.add("{{dock_forum}}", function() {
                window.open("http://forum." + CONF_DOMAIN, "_blank")
            });
            q.add("{{dock_wiki}}", function() {
                window.open("http://wiki." + CONF_DOMAIN, "_blank")
            });
            q.add("{{dock_support}}", function() {
                window.open("http://help." +
                    CONF_DOMAIN, "_blank")
            })
        },
        add: function(a, b) {
            return new Sa(a, "{{dock_" + a + "}}", b)
        },
        ge: function(a) {
            if (!S) {
                a = Ea(a);
                var b = a.x < this.qb.left || a.x > this.qb.right || a.y < this.qb.top || a.y > this.qb.bottom + 100;
                this.pf.each(function(c) {
                    c = c.value;
                    if (b) e = this.k;
                    else var d = Math.abs(c.$d().x - a.x),
                        e = Math.abs(c.$d().y - a.y),
                        d = this.bj(d, this.Ig, 165),
                        e = this.bj(e, this.Ig - 12, 100),
                        e = d < e ? d : e;
                    c.gk(this.size * e)
                }, this);
                b && this.sb.hide()
            }
        },
        bj: function(a, b, c) {
            var d = 0;
            a > b && a < c ? d = 1 + b * (1 - D.k) / (c - b) - a * (1 - D.k) / (c - b) : (a >= c && (d = D.k), a <= b &&
                (d = 1));
            return d
        },
        Po: function(a) {
            xa(a, this.Xc) || this.sb.hide()
        },
        close: function() {
            D.Xc.childElements().invoke("removeClassName", "active");
            this.B.hide();
            Ka()
        },
        Y: function(a) {
            return this.pf.get(a)
        },
        Bg: function(a) {
            return this.pf.get(a).cd
        },
        open: function(a) {
            D.Y(a).open()
        },
        fc: function() {
            var a = Math.round(this.size / 2);
            this.qb = {
                top: 0,
                bottom: this.Ib.cumulativeOffset().top + this.Ib.getHeight() + a,
                left: this.Ib.cumulativeOffset().left - a,
                right: this.Ib.cumulativeOffset().left + this.Ib.getWidth() + a
            };
            this.sb.setStyle({
                top: this.Ib.cumulativeOffset().top +
                    this.size + 6 + "px"
            });
            this.sb.hide();
            !S && 1300 > $("body").getWidth() ? $("divDockUser").visible() && ($("divOnlineUser").show().ins(l), $("divDockUser").hide()) : $("divOnlineUser").visible() && ($("divDockUser").show().ins(l), $("divOnlineUser").hide())
        }
    }),
    Sa = Class.create({
        initialize: function(a, b, c) {
            this.id = a;
            this.title = b;
            this.onopen = c;
            this.va = (new Element("img", {
                src: "//" + CONF_DOMAIN_IMG + "/pub/interface/dock/" + a + ("profile" == a ? l.Fb : "") + ".png"
            })).addClassName("icon");
            S || (this.va.on("mousemove", this.hint.bind(this)),
                this.gk(D.Ig));
            D.Xc.ins(this.va);
            T(this.va, this.open.bind(this, 1));
            this.cd = (new Element("div")).addClassName("panel panel" + a);
            D.ng.ins(this.cd);
            D.pf.set(a, this)
        },
        ins: function(a) {
            this.cd.ins(a);
            return this
        },
        gk: function(a) {
            a = Math.round(a);
            a != this.va.width && (this.va.width = a);
            this.Va() && D.Li.setStyle({
                marginLeft: Math.round(this.$d().x - D.B.cumulativeOffset().left - 23) + "px"
            });
            this.ik()
        },
        ik: function() {
            this.nb && !S && Ca(this.nb, {
                x: this.va.positionedOffset().left + 5,
                y: 0
            })
        },
        $d: function() {
            return Ga(this.va)
        },
        hint: function() {
            !this.Va() &&
                !this.va.hasClassName("onhelp") ? (D.sb.C(this.title).show(), D.sb.setStyle({
                    left: this.$d().x - Math.round(D.sb.getWidth() / 2) + "px"
                })) : D.sb.hide()
        },
        ka: function() {
            this.Va() && this.reload();
            return this
        },
        Va: function() {
            return this.va.hasClassName("active")
        },
        reload: function() {
            this.open();
            return this
        },
        Ob: function(a, b) {
            this.nb || (this.nb = (new Element("div")).addClassName("notify"), this.va.ins({
                after: this.nb
            }));
            b && parseInt(this.nb.innerHTML) && (a += parseInt(this.nb.innerHTML));
            if (0 > a || !a) a = 0;
            this.nb.C(a);
            a ? this.nb.show() :
                this.nb.hide();
            this.ik();
            return this
        },
        sm: function() {
            return this.nb && this.nb.visible() ? parseInt(this.nb.innerHTML) : 0
        },
        open: function(a) {
            D.sb.hide();
            if (a && this.Va()) D.close();
            else {
                D.B.show();
                D.sb.setStyle({
                    zIndex: 2 * da
                });
                D.Xc.childElements().invoke("removeClassName", "active");
                D.ng.childElements().invoke("hide");
                this.va.addClassName("active");
                D.Mi.C(this.title);
                this.cd.show();
                if (this.onopen) this.onopen();
                a = Math.round(this.$d().x - this.cd.getWidth() + 100);
                15 > a && (a = 15);
                var b = D.size + 25;
                D.B.getHeight() + b > $("body").getHeight() &&
                    (b = $("body").getHeight() - D.B.getHeight());
                0 > b && (b = 0);
                D.B.setStyle({
                    left: a + "px",
                    top: b + "px"
                });
                ca && D.ge(ca);
                S && (t.close(), w.close());
                Ka()
            }
        }
    }),
    ia = Class.create({
        initialize: function() {
            this.Ua = 0;
            this.Mb = {
                B: 0,
                le: 0
            };
            if (!S) $("body").on("mousemove", this.un.bind(this));
            return this
        },
        Ql: function(a, b) {
            S || (a.addClassName("dragable"), a.on("mousedown", function(a) {
                a = Ea(a);
                this.Mb.B = b;
                this.Mb.le = {
                    x: a.x - b.cumulativeOffset().left,
                    y: a.y - b.cumulativeOffset().top
                }
            }.bind(this)), a.onmousedown = function() {
                return k
            }, a.on("mouseup",
                function() {
                    this.Mb.B = 0;
                    this.Mb.le = 0
                }.bind(this)))
        },
        un: function(a) {
            this.Mb.B && this.Mb.le && (a = Ea(a), Ca(this.Mb.B, {
                x: a.x - this.Mb.le.x,
                y: a.y - this.Mb.le.y
            }))
        },
        ri: function(a, b) {
            var c = Object.isElement(a) ? a : a.B;
            a.Ri = b;
            c.setAttribute("draggable", "true");
            c.addEventListener("dragstart", this.Bm.bind(this, a), k);
            c.addEventListener("dragend", this.xm.bind(this, a), k)
        },
        dg: function(a, b, c) {
            var d = Object.isElement(a) ? a : a.B;
            a.tg || (a.tg = []);
            a.tg.push(b);
            a.Hj = c;
            d.addEventListener("dragenter", this.ym.bind(this, a), k);
            d.addEventListener("dragover",
                this.Am.bind(this, a), k);
            d.addEventListener("dragleave", this.zm.bind(this, a), k);
            d.addEventListener("drop", this.wm.bind(this, a), k)
        },
        Bm: function(a, b) {
            b.dataTransfer.setData("text/plain", a.id);
            r.hide();
            a.addClassName("dragging");
            this.Ua = a
        },
        xm: function(a) {
            a.removeClassName("dragging");
            $$(".dragover").invoke("removeClassName", "dragover")
        },
        ym: function(a) {
            this.Lg(a) && a.addClassName("dragover")
        },
        zm: function(a) {
            a.removeClassName("dragover")
        },
        Am: function(a, b) {
            b.preventDefault && b.preventDefault();
            b.dataTransfer.dropEffect =
                "move";
            this.Lg(a) && !a.hasClassName("dragover") && a.addClassName("dragover");
            return k
        },
        wm: function(a, b) {
            b.stopPropagation && b.stopPropagation();
            b.preventDefault && b.preventDefault();
            this.Lg(a) && Object.isFunction(a.Hj) && a.Hj(this.Ua);
            this.Ua = 0
        },
        Lg: function(a) {
            return !this.Ua || (!this.Ua.Ri || -1 === a.tg.indexOf(this.Ua.Ri)) || this.Ua.id && a.id && this.Ua.id == a.id ? k : f
        }
    });
Element.prototype.qa = function(a, b) {
    b ? this.addClassName(a) : this.removeClassName(a);
    return this
};

function Ta(a) {
    return S ? a.cumulativeOffset() : a.viewportOffset()
}

function Ua(a, b) {
    0 <= b.indexOf("int") && a.setAttribute("type", "number");
    a.on("keyup", function() {
        var a;
        switch (b) {
            case "double":
                a = /^-?[0-9]+(([\.|\,]?[0-9]+)|[0-9]*)$/;
                break;
            case "int":
                a = /^-?[0-9]+$/;
                break;
            case "unsign int":
                a = /^[0-9]+$/
        }
        this.qa("error", !a.test(this.value))
    });
    return a
}

function Va(a, b) {
    a.on("keydown", function(a) {
        13 == a.keyCode && (b && Object.isFunction(b)) && b()
    });
    return a
}

function T(a, b) {
    a.on("click", b);
    return a
}

function Wa(a, b) {
    b || (b = 0);
    return 0 >= a.scrollHeight ? f : a.scrollTop >= a.scrollHeight - a.getHeight() - b
}

function Xa(a, b) {
    a.on("scroll", function() {
        Wa(this) && b()
    }.bind(a));
    return a
}
Element.prototype.C = function(a) {
    return this.update(n(a))
};
Element.prototype.ins = function(a) {
    return this.insert(n(a))
};
Element.prototype.Ra = function(a) {
    return this.C(Q("empty", a))
};
Element.prototype.Nc = function(a, b) {
    ja.Sa(a, this, b)
};
Element.prototype.Ha = function(a) {
    return this.setStyle({
        visibility: a ? "visible" : "hidden"
    })
};
var r = Class.create({
        initialize: function() {
            this.O = "down";
            this.ra = "";
            this.B = (new Element("div")).addClassName("hint").hide();
            this.pa = (new Element("div")).addClassName("hinttitle");
            this.lb = (new Element("div")).addClassName("hintcontent").hide();
            this.bd = (new Element("div")).addClassName("hintmenu").hide();
            this.kd = k;
            this.Ba = h;
            $("body").ins(this.B);
            this.B.ins(this.pa).ins(this.lb).ins(this.bd);
            S && this.B.ins((new N("{{button_close}}", "btnClose")).click(this.Ia.bind(this)))
        },
        visible: function() {
            return this.B.visible()
        },
        content: function(a) {
            this.lb.show().C(a);
            this.um().Yb();
            return this
        },
        ins: function(a) {
            this.lb.show().ins(a);
            this.Yb();
            return this
        },
        wc: function(a) {
            a || (a = "{{button_cancel}}");
            r.T(a, this.Ia.bind(this), f).addClassName("red")
        },
        title: function(a) {
            this.pa.C(a);
            return this
        },
        Cb: function() {
            this.lb.show().C().addClassName("loading");
            this.bd.hide();
            this.Yb();
            return this
        },
        fb: function() {
            this.lb.removeClassName("loading");
            this.bd.show();
            this.Yb();
            return this
        },
        show: function(a, b, c, d, e) {
            if (this.kd && this.Ba == a) return k;
            b ? Object.isFunction(b) && (b = b()) : b = "";
            this.type = e;
            this.O = c ? "up" : "down";
            this.qa(d);
            this.kd = k;
            this.B.setStyle({
                zIndex: da++
            });
            this.lb.C().hide();
            this.Sd();
            Object.isElement(a) || (a = a.B);
            this.Ba = a;
            this.title(b);
            this.B.show();
            this.Yb();
            return this
        },
        qa: function(a) {
            this.ra && this.B.removeClassName(this.ra);
            (this.ra = a) && this.B.addClassName(this.ra)
        },
        Yb: function() {
            var a = k;
            this.B.removeClassName("upside").removeClassName("downside").removeClassName("noarrow").removeClassName("wide");
            var b = Ga(this.Ba).x - Math.round(this.B.getWidth() /
                2);
            0 > b ? (b = Ta(this.Ba).left, this.B.addClassName("left")) : this.B.removeClassName("left");
            b + this.B.getWidth() > $("body").getWidth() ? (b = Ta(this.Ba).left + this.Ba.getWidth() - this.B.getWidth() + 20, this.B.addClassName("right")) : this.B.removeClassName("right");
            var c = Ta(this.Ba).top + this.Ba.getHeight() + 3,
                d = Ta(this.Ba).top - this.B.getHeight() - 4;
            S ? (this.O = "down", this.B.getWidth() > $("body").getWidth() / 2 && (this.B.addClassName("wide noarrow"), b = 0)) : "down" == this.O && c + this.B.getHeight() > $("body").getHeight() && (0 < d ? this.O =
                "up" : (a = f, c = $("body").getHeight() - this.B.getHeight() - 2));
            c = "down" == this.O ? c : d;
            this.B.addClassName(this.O + "side" + (a ? " noarrow" : ""));
            this.B.setStyle({
                left: b + "px",
                top: c + "px"
            });
            return this
        },
        hide: function() {
            if (this.kd) return this;
            this.B.hide();
            return this
        },
        Sa: function(a, b, c, d) {
            a = Object.isElement(a) ? a : a.B;
            a.Te = {
                tn: a.on("mouseover", this.show.bind(this, a, b, c, d)),
                sn: a.on("mouseout", this.hide.bind(this))
            }
        },
        Jk: function(a) {
            a.Te && (a.Te.tn.stop(), a.Te.sn.stop(), a.Te = h)
        },
        um: function() {
            this.kd = f;
            return this
        },
        Ia: function(a) {
            if (a &&
                xa(a, this.B)) return this;
            this.kd = k;
            this.hide();
            return this
        },
        T: function(a, b, c) {
            this.bd.show();
            a = (new N(a)).click(b);
            c || a.click(this.Ia.bind(this), 1);
            this.bd.ins(a);
            this.Yb();
            return a
        },
        Sd: function() {
            this.bd.C().hide();
            return this
        }
    }),
    $a = Class.create({
        initialize: function(a, b, c) {
            this.aa = {
                page: b.page,
                zj: b.maxpage,
                search: b.search,
                $g: b.other,
                vd: b.order,
                filters: b.filters
            };
            this.Bc = c.Bc;
            this.params = c;
            c.ih && (this.zd = new Ya(c.ih, function() {
                    this.aa.page = 1;
                    this.go()
                }.bind(this)), this.aa.search && this.zd.set(this.aa.search),
                (b = a.previous()) && b.hasClassName("searchfield") ? b.C(this.zd) : a.ins({
                    before: this.zd
                }));
            c.of && Object.isArray(c.of) && (this.qc = (new Element("div")).addClassName("divOrderer"), this.kh = new W(this.aa.vd, c.of, c.filters ? 0 : function() {
                this.aa.page = 1;
                this.go()
            }.bind(this)), c.filters && this.qc.ins(Q("spinpt", "{{orderer}}: ")), this.qc.ins(this.kh), a.ins({
                before: this.qc
            }));
            if (c.filters) {
                this.Ec = [];
                this.oc = (new Element("div")).addClassName("divFilters");
                for (b = 0; b < c.filters.length; b++) {
                    var d = c.filters[b];
                    this.Ec[b] =
                        new W(this.aa.filters && this.aa.filters[b] ? this.aa.filters[b] : 0, d[1]);
                    this.oc.ins(Q("spinpt", d[0] + ": ")).ins(this.Ec[b]).ins("<br>")
                }
                this.pl = (new N("{{button_apply}}", "gray small")).click(function() {
                    this.aa.page = 1;
                    this.go()
                }.bind(this));
                this.ql = (new N("{{button_reset}}", "gray small")).click(this.On.bind(this));
                this.oc.ins(Q("spinpt", " ")).ins(this.pl).ins(this.ql);
                a.ins({
                    before: this.oc
                })
            }
            c.Fg && (this.qc && this.qc.hide(), this.oc && this.oc.hide(), this.Xh = (new N("{{navigater_showoptions}}", "nobg link showoptions")).click(function() {
                this.Xh.hide();
                this.qc && this.qc.show();
                this.oc && this.oc.show();
                t.Cj();
                this.params.Fg = k
            }.bind(this)), a.ins({
                before: this.Xh
            }));
            0 < this.aa.zj && (this.Jj = new Za(this.aa.page, this.aa.zj, function(a) {
                this.aa.page = a;
                this.go()
            }.bind(this)), (b = a.next()) && b.hasClassName("pages") ? b.C(this.Jj) : a.ins({
                after: this.Jj
            }))
        },
        Qe: function() {
            this.aa.search = this.zd ? this.zd.get() : "";
            this.aa.vd = this.kh ? this.kh.get() : 0;
            this.aa.filters = [];
            if (this.Ec)
                for (var a = 0; a < this.Ec.length; a++) this.aa.filters[a] = this.Ec[a].get();
            return [this.aa.page, this.aa.search,
                this.aa.$g, this.aa.vd, this.aa.filters
            ]
        },
        On: function() {
            this.aa.search = "";
            this.zd.set("");
            for (var a = 0; a < this.Ec.length; a++) this.Ec[a].set(0, f)
        },
        go: function() {
            Object.isFunction(this.Bc) && this.Bc(this.Qe())
        }
    }),
    Za = Class.create(V, {
        initialize: function($super, b, c, d, e) {
            $super(e);
            this.page = b;
            this.page = c;
            this.B = new Element("div", {
                "class": "pages"
            });
            this.Na = d;
            d = 1;
            for (e = 0; d <= c; d++) 4 < d && d < c - 4 && d != b && d != b - 1 && d != b + 1 && d != b - 2 && d != b + 2 || (d - 1 != e && (this.Ee = (new Element("div")).addClassName("page").C("..."), T(this.Ee, this.ho.bind(this)),
                r.Sa(this.Ee, "{{button_go}}"), this.B.ins(this.Ee)), divPage = (new Element("div")).addClassName("page").C(d), b == d && divPage.addClassName("active"), T(divPage, this.dj.bind(this, d)), this.B.ins(divPage), e = d);
            return this
        },
        ho: function() {
            var a = function() {
                    this.dj(b.value)
                }.bind(this),
                b = Va(Ua((new Element("input")).addClassName("amount"), "unsign int"), a);
            r.visible() || r.show(this.Ee);
            r.title("").Sd().content("&nbsp;<br>").ins(b).T("{{button_go}}", a);
            b.focus()
        },
        dj: function(a) {
            this.Na(a)
        }
    }),
    ab = Class.create(V, {
        initialize: function($super,
            b, c, d) {
            $super(d);
            this.pd = c;
            this.Cc = 0;
            this.title = this.params.title ? this.params.title + ": " : "";
            this.B = (new Element("div")).addClassName("progressbar " + (this.params.ra ? this.params.ra : ""));
            this.Gj = new Element("div");
            this.B.ins(this.Gj);
            this.params.U || (this.params.U = "absolute");
            this.set(b);
            "none" != this.params.U && this.B.U(this.Re.bind(this));
            return this
        },
        nh: function(a) {
            if (this.Ya == a) {
                if (this.Ya == this.pd && this.params.oncomplete && Object.isFunction(this.params.oncomplete)) this.params.oncomplete()
            } else return this.set(this.Ya +
                (a > this.Ya ? 1 : -1)), this.Tb = this.nh.bind(this, a).delay(1), this
        },
        rk: function() {
            this.Tb && clearTimeout(this.Tb)
        },
        set: function(a) {
            this.Ya = a;
            this.Cc = this.pd ? Math.floor(100 * this.Ya / this.pd) : 0;
            this.params.Ll && (this.B.removeClassName("min max mid"), 70 <= this.Cc && this.B.addClassName("max"), 30 < this.Cc && 70 > this.Cc && this.B.addClassName("mid"), 30 >= this.Cc && this.B.addClassName("min"));
            this.Gj.setStyle({
                width: this.Cc + "%"
            });
            ma.visible() && this.B == ma.Ba && ma.ek(this.Re());
            return this
        },
        kk: function(a) {
            this.rk();
            this.pd =
                a;
            this.set(this.Ya);
            return this
        },
        add: function(a) {
            this.set(this.Ya + a);
            return this
        },
        Re: function() {
            var a;
            switch (this.params.U) {
                case "absolute":
                    a = za(this.Ya) + " / " + za(this.pd);
                    break;
                case "percent":
                    a = this.Cc + "%"
            }
            return this.title + a
        }
    }),
    Ya = Class.create(V, {
        initialize: function($super, b, c, d) {
            d || (d = {});
            $super(d);
            this.Yg = c;
            this.B = (new Element("div")).addClassName("searchfield");
            this.rl = (new N("", "ctrl nobg btnGo")).click(function() {
                this.Yg(this.input.value)
            }.bind(this));
            this.$h = (new N("", "ctrl nobg btnX")).Ha(k).click(this.clear.bind(this));
            this.input = new Element("input", {
                placeholder: n(b)
            });
            this.input.on("keypress", function(b) {
                13 === b.keyCode && !this.input.value.blank() && this.Yg(this.input.value)
            }.bind(this));
            d.Nc && this.input.Nc(d.Nc[0], d.Nc[1]);
            this.B.ins(this.input).ins(this.$h).ins(this.rl);
            return this
        },
        focus: function() {
            this.input.focus()
        },
        get: function() {
            return this.input.value
        },
        clear: function() {
            this.set("");
            this.Yg()
        },
        set: function(a) {
            this.input.value = a ? a : "";
            this.$h.Ha(this.input.value);
            return this
        }
    }),
    W = Class.create(V, {
        initialize: function($super,
            b, c, d, e) {
            $super(e);
            if (c)
                if (Object.isArray(c)) {
                    this.jb = {};
                    for (e = 0; e < c.size(); e++) this.jb[e] = c[e]
                } else this.jb = c;
            else this.jb = 0;
            this.B = (new Element("div")).addClassName("select " + (!this.jb ? "text" : "") + (this.params.ra ? this.params.ra : ""));
            this.jb && (this.span = new Element("span"), this.B.ins(this.span), T(this.B, this.list.bind(this)));
            c = {};
            c.type = this.jb ? "hidden" : "text";
            this.params.name && (c.name = this.params.name);
            this.input = new Element("input", c);
            this.B.ins(this.input);
            this.onchange = d;
            this.set(b, f);
            return this
        },
        get: function() {
            return this.input.value
        },
        set: function(a, b) {
            !this.jb && !a && (a = "");
            this.input.value = a;
            this.jb && this.span.C(this.jb[a]);
            if (!b && this.onchange) this.onchange(a);
            return this
        },
        list: function() {
            if (this.jb) {
                q.show(0, {
                    mf: this.B
                });
                for (var a in this.jb) q.add(this.jb[a], this.set.bind(this, a))
            }
        }
    });
W.Bh = {};
for (var X = 1; 31 >= X; X++) W.Bh[X] = X;
W.Ch = {};
for (X = 0; X < Lang.months.size(); X++) W.Ch[X] = Lang.months[X];
W.Dh = {};
for (X = 1970; X <= (new Date).getFullYear() - 10; X++) W.Dh[X] = X;
W.Ah = {};
for (X = 1; X < Lang.colors.size(); X++) W.Ah[X] = '<span class="textColor' + X + '">' + Lang.colors[X] + "</span>";
var cb = Class.create(V, {
        initialize: function($super, b) {
            $super();
            this.B = (new Element("div")).addClassName("slots");
            this.id = b[0];
            this.all = b.size() - 1;
            this.Kf = 0;
            for (var c = 1; c <= this.all; c++) {
                var d = (new Element("img", {
                    src: "//" + CONF_DOMAIN_IMG + "/pub/balls/slots/" + b[c][0] + ".png"
                })).addClassName("ball");
                b[c][1] ? this.Kf++ : d.addClassName("defeat");
                this.B.ins(d);
                d.addClassName("clickable");
                T(d, this.En.bind(this, c - 1, d))
            }
            return this
        },
        En: function(a, b) {
            r.show(b, " ", f, "white");
            new p("trainers/peep", [this.id, a], {
                G: r,
                J: function(a) {
                    a = new bb(a, 0, {
                        size: 2,
                        ra: "peep",
                        Ub: f
                    });
                    r.content(a)
                },
                onError: function() {
                    r.hide()
                }
            })
        }
    }),
    eb = Class.create(V, {
        initialize: function($super, b, c) {
            $super(c);
            this.Bf = {};
            this.last = h;
            this.B = (new Element("div")).addClassName("tabBody " + (b ? b : ""));
            this.Wc = (new Element("div")).addClassName("tabControls");
            this.eg = (new Element("div")).addClassName("tabContents");
            this.B.ins(this.Wc).ins(this.eg)
        },
        add: function(a, b, c, d) {
            this.last || (this.last = a);
            b = new db(b, c, d);
            T(b.control, this.open.bind(this, a));
            this.Wc.ins(b.control);
            this.eg.ins(b.B);
            this.Bf[a] = b;
            return this.Bf[a]
        },
        tab: function(a) {
            return this.Bf[a]
        },
        open: function(a) {
            "last" == a && (a = this.last);
            this.Wc.childElements().invoke("removeClassName", "selected");
            this.eg.childElements().invoke("hide");
            this.last = a;
            this.Bf[a].reload();
            return this
        }
    }),
    db = Class.create(V, {
        initialize: function($super, b, c, d) {
            $super();
            this.control = new Element("div");
            b ? (this.control.addClassName("iconed"), b = (new Element("span")).addClassName("tabicon icon" + b).U(c, f)) : b = (new Element("span")).addClassName("tabtitle").C(c);
            this.control.ins(b);
            this.B = (new Element("div")).addClassName("tabContent");
            this.onopen = d;
            return this
        },
        Va: function() {
            return this.control.hasClassName("selected")
        },
        reload: function() {
            this.control.addClassName("selected");
            this.B.show();
            if (this.onopen) this.onopen()
        }
    }),
    ma = Class.create({
        initialize: function() {
            this.B = (new Element("div")).addClassName("tip");
            this.B.hide();
            this.Ba = 0;
            $$("body")[0].ins(this.B);
            T(this.B, this.hide.bind(this))
        },
        ek: function(a) {
            this.B.C(a);
            return this
        },
        show: function(a, b, c) {
            ca = c;
            b &&
                (Object.isFunction(b) && (b = b()), this.ek(b));
            Da(this.B);
            this.B.show();
            this.Ba = a
        },
        hide: function() {
            this.B.hide();
            this.Ba = 0
        },
        visible: function() {
            return this.B.visible()
        },
        Sa: function(a, b) {
            a = $(a);
            a.on("mouseover", this.show.bind(this, a, b));
            a.on("mouseout", this.hide.bind(this))
        }
    });
Element.prototype.U = function(a, b) {
    if (b && S) return this;
    ma.Sa(this, a);
    return this
};
var u = Class.create({
        initialize: function() {
            this.B = (new Element("div")).addClassName("waiter").hide();
            this.la = (new Element("div")).addClassName("txt");
            this.je = new ab(0, 100, {
                U: "percent"
            });
            this.B.ins(this.la).ins(this.je);
            $("body").ins(this.B);
            return this
        },
        start: function(a, b) {
            b || (b = {});
            this.xk && clearTimeout(this.xk);
            "string" == typeof a ? this.la.C((new Element("div")).addClassName("ajxloading").C(a)) : this.la.C(a ? a : "");
            this.B.show();
            this.B.setStyle({
                zIndex: 10 * da
            });
            b.duration ? (this.je.show(), this.je.kk(b.duration).set(0).nh(b.duration),
                b.Wo && (b.duration = P(1, b.duration - 1)), this.xk = this.stop.bind(this).delay(b.duration), b.callback && b.callback.delay(b.duration)) : this.je.hide();
            b.zn && this.B.addClassName("notransparent")
        },
        stop: function() {
            this.B.removeClassName("notransparent");
            this.B.hide();
            this.je.rk()
        }
    }),
    fb = Class.create(V, {
        initialize: function($super, b, c) {
            $super();
            this.id = b.id;
            this.title = b.title;
            this.ia = c;
            this.socket = 0;
            this.La = "";
            this.B = (new Element("div")).addClassName("divAbility").C(this.title);
            T(this.B, this.Ub.bind(this));
            return this
        },
        Ub: function() {
            r.show(this.B);
            if (this.La) {
                var a = (new Element("div")).addClassName("divAbilityDex"),
                    b = (new Element("div")).addClassName("divAbilityDex title").C(this.title),
                    c = (new Element("div")).addClassName("divAbilityDex socket").C("{{dex_ability_socket." + this.socket + "}}"),
                    d = (new Element("div")).addClassName("divAbilityDex descr").C(this.La);
                a.ins(b).ins(c).ins(d);
                r.content(a)
            } else this.Pg()
        },
        Pg: function() {
            new p("dex/ability", [this.id, this.ia], {
                G: r,
                J: function(a) {
                    this.La = a.descr;
                    this.socket = a.socket;
                    this.Ub()
                }.bind(this)
            })
        }
    }),
    gb = Class.create(V, {
        initialize: function($super, b, c) {
            $super();
            this.id = b.id;
            this.title = b.title;
            this.La = b.descr;
            this.Ya = b.val;
            this.wn = b.need;
            this.gh = b.reached;
            this.oo = b.showvals;
            this.B = (new Element("div")).addClassName("achive").setStyle({
                backgroundPosition: -80 * this.id + "px 0"
            }); - 1 < [42, 55, 81].indexOf(this.id) && this.B.setStyle({
                background: "none"
            }).C("?");
            c || (this.gh || this.B.addClassName("notreached"), this.Ea = (new Element("div")).addClassName("achiveInfo"), this.title && this.Ea.insert((new Element("span")).addClassName("title").C(this.title)),
                this.La && this.Ea.insert((new Element("span")).addClassName("descr").C(this.La)), this.oo && this.Ea.insert((new Element("span")).addClassName("vals").C(this.Ya + "/" + this.wn)), this.gh && this.Ea.insert((new Element("span")).addClassName("reached").C(Ia(this.gh))), r.Sa(this.B, this.Ea));
            return this
        }
    });
Auc = {
    td: 0,
    sd: h,
    load: function(a, b) {
        b || (b = [1, ""]);
        J.close();
        t.show("{{auc_title}}");
        new p("auc/load", [a], {
            aa: b,
            G: t,
            J: function(b) {
                var d = (new Element("div")).addClassName("divAuc"),
                    e = (new Element("div")).addClassName("divItemCats"),
                    m = (new Element("div")).addClassName("divAucList");
                d.ins(e).ins(m);
                t.ins(d);
                Auc.sd = new $a(m, b.navi, {
                    total: b.lots.total,
                    ih: "{{search_char_or_item}}",
                    of: Lang.auc_order,
                    Bc: Auc.load.curry(a)
                });
                Auc.td = a;
                for (d = 0; d < sa.Ga.length; d++) 9 == d || 7 == d || e.ins((new N("", "invcategory nobg cat" +
                    sa.Ga[d] + (Auc.sd.aa.$g == sa.Ga[d] ? " pressed" : ""), {
                        ta: f,
                        U: "{{invent_groups." + sa.Ga[d] + "}}"
                    })).click(Auc.co.curry(sa.Ga[d])));
                0 >= b.lots.total ? m.Ra() : b.lots.items.each(function(b) {
                    m.ins(new hb(b, a)).ins(R())
                })
            }
        })
    },
    reload: function() {
        Auc.load(Auc.td, Auc.sd.Qe())
    },
    co: function(a) {
        Auc.sd.aa.$g = a;
        Auc.reload()
    },
    jo: function(a) {
        Auc.load(Auc.td, [1, "!" + a, 0])
    },
    Gh: function(a, b) {
        var c = I.yg();
        if (!c) return k;
        t.show("{{auc_add_lot}} " + a.title);
        if ("item" == b) {
            var d = Ua(new Element("input", {
                value: 1
            }), "unsign int");
            t.ins(Q("spinpt",
                "{{amount}}:")).ins(d).ins("<br>");
            d.select()
        }
        var e = Ua(new Element("input"), "unsign int"),
            m = Ua(new Element("input"), "unsign int"),
            g = Ua(new Element("input", {
                value: 3
            }), "unsign int"),
            v = Ua(new Element("input"), "unsign int");
        t.ins(Q("spinpt", "{{auc_start_price}}:")).ins(e).ins(" {{currency_credit_alias}}<br>");
        t.ins(Q("spinpt", "{{auc_step}}:")).ins(m).ins(" {{currency_credit_alias}}<br>");
        t.ins(Q("spinpt", "{{auc_expir}}:")).ins(g).ins("<br>");
        t.ins(Q("spinpt", "{{auc_buynow}}:")).ins(v).ins(" {{currency_credit_alias}}<br>");
        t.ins(Q("spinpt", "")).ins((new N("{{auc_add_lot}}", "gray")).click(function() {
            new p("auc/add", {
                G: t,
                parameters: {
                    id: a.id,
                    npc_id: c,
                    type: b,
                    amount: "item" == b ? $F(d) : 0,
                    price: $F(e),
                    step: $F(m),
                    expir: $F(g),
                    buynow: $F(v)
                },
                J: function() {
                    t.close();
                    D.Y("items").ka();
                    D.Y("pokes").ka()
                }
            })
        }));
        t.ins(R()).ins("<br>{{auc_add_price:50000}}")
    }
};
var hb = Class.create({
        initialize: function(a) {
            this.cf = a[9];
            this.Wa = a[10];
            this.step = a[11];
            this.Zf = a[12];
            this.el = a[13];
            this.an = a[14];
            this.vg = a[15];
            this.Lf = new Y({
                id: a[16],
                uname: a[17],
                ugroup: a[18],
                sex: a[19]
            });
            this.Pd = a[20] ? new Y({
                id: a[20],
                uname: a[21],
                ugroup: a[22],
                sex: a[23]
            }) : h;
            this.vg = 3600 * Math.floor(this.vg / 3600);
            a[1] ? (this.data = new ib(a), this.title = this.data.Ab()) : (this.data = new bb({
                sp_id: a[24],
                pokename: a[25],
                shine: a[26]
            }, {
                $b: kb.curry(this.cf, "auc"),
                Ba: "pic"
            }, {
                size: 1
            }), this.title = this.data.title);
            this.Wa &&
                this.Pd && (this.Wa += this.step);
            this.B = (new Element("div")).addClassName("lot");
            this.Lf.T("---").T("{{auc_author_all_lots}}", Auc.jo.curry(this.Lf.ga));
            a = (new Element("div")).addClassName("buttons");
            if (this.Wa) {
                var b = new N("{{auc_lets_bid:" + this.Wa + "}}", "gray");
                this.Pd && this.Pd.id == l.id ? b.C("{{auc_last_bid_yours}}").disable(f) : b.click(this.dl.bind(this));
                a.ins(b)
            }
            this.Zf && (b = (new N("{{auc_lets_buy:" + this.Zf + "}}", "gray")).click(this.El.bind(this)), a.ins(b));
            b = (new Element("div")).addClassName("author");
            b.ins("{{auc_from}} ").ins(this.Lf).ins(" {{auc_lot:" + this.cf + "}}");
            b.ins(" {{auc_remain:" + this.vg + "}}");
            this.data.N.pa.ins(b).ins(a);
            a = (new Element("div")).addClassName("info");
            a.ins(this.data);
            this.B.ins(a);
            return this
        },
        dl: function() {
            r.show(this, this.title);
            r.ins("<br>{{auc_last_bid}}: ").ins(this.Pd ? this.Pd.ga + " " + Ia(this.an) : "{{empty}}");
            r.ins("<br>{{auc_bidcounter:" + this.el + "}}<br>&nbsp;<br>");
            var a = Ua((new Element("input", {
                value: this.Wa
            })).addClassName("amount"), "unsign int");
            r.ins(a).ins(" {{currency_credit_alias}}<br>");
            a.select();
            r.T("{{auc_bid_confirm}}", function() {
                new p("auc/bid", [this.cf, $F(a)], {
                    G: t,
                    onComplete: function() {
                        Auc.reload()
                    }.bind(this)
                })
            }.bind(this));
            r.wc()
        },
        El: function() {
            O("{{auc_buynow_confirm:" + this.Zf + "}}") && new p("auc/buynow", [this.cf], {
                G: t,
                onComplete: function() {
                    Auc.reload()
                }.bind(this)
            })
        },
        toElement: ba("B")
    }),
    C = Class.create({
        initialize: function() {
            var a = ["chat", "fight", "alert"];
            this.vb = {};
            for (var b = 0; b < a.length; b++) this.load.bind(this, a[b]);
            return this
        },
        load: function(a, b) {
            var c = {};
            if (this.vb[a]) return this.vb[a];
            c.src = "//" + CONF_DOMAIN_IMG + "/pub/sound/" + a + ".mp3";
            b && (c.autoplay = 1);
            c = new Element("audio", c);
            c.volume = 0.9;
            return this.vb[a] = c
        },
        play: function(a, b) {
            if (E.params.sound) {
                if (C.vb[a]) return b && (C.vb[a].loop = f), C.vb[a].play(), C.vb[a];
                var c = C.load(a, f);
                b && (c.loop = f);
                return c
            }
        },
        stop: function(a) {
            if (!C.vb[a]) return k;
            C.vb[a].pause();
            C.vb[a].currentTime = 0;
            C.vb[a].loop = k;
            return f
        },
        Fm: function(a) {
            this.hj && this.hj.pause();
            a && (this.hj = this.play(a))
        }
    });
Ava = {
    Kl: function() {
        q.show("{{ava_your_ava}}");
        new p("ava/clothes", {
            G: q,
            J: function(a) {
                a && a.size() && a.each(function(a) {
                    q.add(a.title, Ava.Sk.curry(a.id, k))
                });
                q.add("\u0421\u043e\u0445\u0440\u0430\u043d\u0438\u0442\u044c \u0438\u0437\u043e\u0431\u0440\u0430\u0436\u0435\u043d\u0438\u0435...", function() {
                    var a = document.createElement("a");
                    a.target = "_blank";
                    a.ap = "img.jpg";
                    a.href = l.Kh;
                    a.click()
                })
            }
        })
    },
    Sk: function(a, b) {
        u.start(b ? "{{ava_waring}}" : "{{ava_unwaring}}");
        new p("ava/" + (b ? "wear" : "unwear"), [a], {
            J: function(a) {
                D.Y("items").ka();
                l.tf(a)
            },
            onComplete: function() {
                u.stop()
            }
        })
    },
    vm: function(a) {
        var b, c, d = " #ffffff #f4c96f #dba252 #a55a1e #ffdc71 #893ea8 #3f3936 #97b580 #d14f45 #4d5366".split(" ");
        J.close();
        t.show("{{ava_haircut_title}}", {
            position: "center"
        });
        new p("ava/haircut/list", [a], {
            G: t,
            J: function(e) {
                b = +e.current.hair;
                c = e.current.color;
                var m = (new Element("div")).addClassName("divHaircutContainer"),
                    g = (new N("", "nobg ctrl prev")).click(function() {
                        var a = e.list.indexOf(b) - 1;
                        b = e.list[a] ? e.list[a] : e.list[e.list.size() - 1];
                        Ava.Se(b,
                            c)
                    }),
                    v = (new N("", "nobg ctrl next")).click(function() {
                        var a = e.list.indexOf(b) + 1;
                        b = e.list[a] ? e.list[a] : e.list[0];
                        Ava.Se(b, c)
                    });
                Ava.Fi = (new Element("div")).addClassName("divHaircutModel");
                m.ins(g).ins(Ava.Fi).ins(v);
                for (var g = (new N("{{ava_haircut_go}}", "btnHaircutMake")).click(function() {
                        if (!O("{{ava_haircut_confirm:" + e.price + "}}")) return k;
                        new p("ava/haircut/make", [a, b, c], {
                            J: function() {
                                t.close()
                            }
                        })
                    }), v = (new Element("div")).addClassName("divHaircutPalette"), G = 1; 10 >= G; G++) {
                    var U = new Element("div");
                    U.style.backgroundColor =
                        d[G];
                    T(U, function(a) {
                        c = a;
                        Ava.Se(b, a)
                    }.curry(G));
                    v.insert(U)
                }
                t.ins(m).ins(v).ins(g);
                Ava.Se(b, c)
            }
        })
    },
    Se: function(a, b) {
        Ava.Fi.style.backgroundImage = "url(//" + CONF_DOMAIN_IMG + "/dyn/dolls/" + a + "_" + b + ".png)"
    },
    zo: function(a) {
        J.close();
        t.show("{{ava_tatoo_title}}", {
            position: "center"
        });
        new p("ava/tatoo/list", [a], {
            G: t,
            J: function(b) {
                for (var c = ["{{button_select}}"], d = 0; d < b.items.size(); d++) c.push(b.items[d][4]);
                var d = (new Element("div")).addClassName("divTatooContainer"),
                    e = (new Element("div")).addClassName("divTatooModel"),
                    m = new W(0, c, function(a) {
                        0 == a ? (e.style.backgroundImage = "none", g.C("&nbsp;").disable(f)) : (e.style.backgroundImage = "url(//" + CONF_DOMAIN_IMG + "/dyn/dolls/" + b.items[a - 1][10] + ".png)", g.C("{{ava_tatoo_make:" + b.items[a - 1][9] + "}}").disable(k))
                    }, {
                        name: "ava_tatoo"
                    }),
                    g = (new N("&nbsp;")).disable(f).click(function() {
                        if (!O("{{ava_tatoo_confirm}}")) return k;
                        new p("ava/tatoo/make", [a, b.items[m.get() - 1][0]], {
                            J: function() {
                                t.close()
                            }
                        })
                    });
                d.ins(m).ins(e).ins(g);
                t.ins(d)
            }
        })
    }
};
Breed = {
    ce: function(a) {
        t.show("{{breed_title}}", {
            position: "center"
        });
        new p("breed/load/", [a], {
            G: t,
            J: function(b) {
                var c = new Y(b.master);
                b = new lb(b);
                var d = (new Element("div")).addClassName("divBreed"),
                    e = (new Element("div")).addClassName("ivcode").C(b.qj);
                d.C(c).ins(" {{breed_smbd_offer_breed}}: <br>").ins(b).ins(e).ins("<br>");
                t.C(d);
                Breed.xe = (new N("{{breed_choose_partner}}", "gray")).click(F.show.curry("{{breed_choose_partner}}", "breedable", Breed.go.curry(a)));
                t.ins(Breed.xe).wc()
            },
            onError: function() {
                t.close()
            }
        })
    },
    go: function(a, b) {
        new p("breed/go", [a, b], {
            G: Breed.xe,
            J: function() {
                t.close()
            }
        })
    }
};
var mb = 200,
    B = Class.create({
        initialize: function() {
            this.ae = [];
            this.uc = 0;
            this.Mg = k;
            this.areas = {};
            this.te = [];
            this.F = {
                Hb: (new Element("input", {
                    maxlength: "100",
                    placeholder: n("{{chat_tip_to}}")
                })).addClassName("txtToName"),
                Qa: (new Element("textarea", {
                    maxlength: "500",
                    placeholder: n("{{chat_tip_message}}"),
                    autocomplete: "off"
                })).addClassName("txtInput"),
                Zh: (new N("", "fielded ctrl x red btnToNameClear", {
                    title: "{{button_reset}}",
                    ta: 1
                })).click(this.Fc.bind(this)),
                ye: (new N("", "fielded ctrl btnSend", {
                    title: "{{chat_tip_send}}",
                    ta: 1
                })).click(this.send.bind(this)),
                ze: (new N("", "fielded btnSendCancel", {
                    title: "{{chat_tip_cancel}}",
                    ta: 1
                })).click(this.lh.bind(this)).hide(),
                Wf: (new N("", "ctrl nobg btnSmile", {
                    title: "{{chat_tip_smiles}}",
                    ta: 1
                })).click(this.Dk.bind(this)),
                Qc: (new N("{{chat_tip_wilds}}", "ctrl btnSwitchWilds", {
                    title: "{{chat_tip_wilds}}",
                    ta: 1
                })).click(this.tk.bind(this)),
                Ae: (new N("", "ctrl btnSwitchShowf", {
                    title: "{{chat_tip_showf}}",
                    ta: 1
                })).click(this.xo.bind(this)),
                Xf: (new N("", "ctrl btnVights", {
                    title: "{{chat_tip_battles}}",
                    ta: 1
                })).click(Vight.list),
                Rf: (new N("DEX", "btnDex", {
                    title: "{{chat_tip_dex}}",
                    ta: 1
                })).click(A.R),
                Oi: (new Element("div")).addClassName("divSmiles"),
                Dd: (new Element("input", {
                    type: "text"
                })).addClassName("txtSrchOnline"),
                $o: (new N("&nbsp;", "nobg btnChatControl")).click(this.mo.bind(this))
            };
            $("divOnlineSearch").C(this.F.Dd);
            $("divInputFields").ins(this.F.Hb).ins(this.F.Zh).ins(this.F.Qa).ins(this.F.Wf).ins(this.F.ye).ins(this.F.ze);
            $("divInputButtons").ins(this.F.Qc).ins(this.F.Xf).ins(this.F.Ae).ins(this.F.Rf);
            this.Zg("loc");
            S && ($("divInputButtons").ins({
                before: this.F.Hb
            }).ins({
                before: this.F.Zh
            }), $("divInputButtons").ins(this.F.ye).ins(this.F.ze).ins(this.F.Wf), mb = 100);
            $("divSmiles").hide();
            this.F.Dd.on("keyup", Onlines.search);
            this.F.Dd.on("focus", function() {
                this.select.bind(this).delay(0.1)
            });
            this.F.Hb.on("focus", function() {
                this.select.bind(this).delay(0.1)
            });
            this.F.Hb.on("keyup", this.Gg.bind(this));
            this.F.Hb.Nc("friend", this.focus.bind(this));
            this.F.Qa.on("keyup", this.Gg.bind(this));
            this.F.Qa.on("keydown",
                function(a) {
                    13 == a.keyCode && this.send();
                    if (38 == a.keyCode || 40 == a.keyCode) {
                        var b = this.zg();
                        if (38 == a.keyCode) {
                            if (0 >= this.uc) return;
                            this.uc--
                        } else {
                            if (this.uc >= this.ae.length - 1) return;
                            this.uc++
                        }
                        this.F.Qa.value = this.ae[this.uc];
                        this.Fc(b.to)
                    } else this.uc = this.ae.length;
                    if (9 == a.keyCode) return a.preventDefault(), this.Dk(), k
                }.bind(this))
        },
        fc: function() {
            S || ($("divChat").setStyle({
                height: document.viewport.getHeight() - $("divInput").getHeight() - $("divGameTop").getHeight() - 5 + "px"
            }), $("divOnline").setStyle({
                height: $("divChat").getHeight() +
                    "px"
            }));
            200 > $("divChat").getHeight() ? $("divChat").addClassName("small") : $("divChat").removeClassName("small");
            this.Zd().fc()
        },
        mo: aa(),
        Gg: function() {
            var a = this.zg().Aa;
            this.F.Qa.qa("privat", a.yd);
            this.F.Qa.qa("clanchat", a.Rd);
            this.F.Qa.qa("console", a.console);
            this.F.Qa.qa("ad", a.jc)
        },
        tk: function(a) {
            na.params.isLoadWilds = "undefined" === typeof a ? !na.params.isLoadWilds : a;
            this.F.Qc.qa("pressed", na.params.isLoadWilds)
        },
        xo: function() {
            E.Pb("showf", E.params.showf ? 0 : 1)
        },
        Kg: function(a) {
            return -1 !== B.te.indexOf(+a)
        },
        Yi: function(a) {
            a || (a = []);
            this.te = a
        },
        focus: function() {
            S && Ka("chat");
            this.F.Qa.focus()
        },
        zg: function() {
            var a = {
                la: this.F.Qa.value.trim(),
                to: this.F.Hb.value.trim()
            };
            a.Aa = {
                yd: "=" == a.la[0] && "=" != a.la[1],
                Rd: "=" == a.la[0] && "=" == a.la[1],
                hf: "@" == a.la[0] && "@" == a.la[1],
                jc: "*" == a.la[0] && 2 < a.la.length && a.la.endsWith("*"),
                console: "/" == a.la[0]
            };
            a.Aa.yd && (a.la = a.la.substr(1));
            if (a.Aa.Rd || a.Aa.hf) a.la = a.la.substr(2);
            a.Aa.jc && (a.la = a.la.substr(1, a.la.length - 2));
            if (!a.Aa.yd && !a.Aa.Rd && !a.Aa.hf && !a.Aa.jc && !a.Aa.console) {
                var b =
                    this.Zd();
                "clan" == b.id && (a.Aa.Rd = f);
                "moder" == b.id && (a.Aa.hf = f);
                "ad" == b.id && (a.Aa.jc = f);
                "u" == b.id[0] && (a.Aa.yd = f)
            }
            return a
        },
        Fc: function(a) {
            !a && this.Zd().ya ? this.Zg("loc").Fc(a) : this.Zd().Fc(a)
        },
        Jl: function() {
            this.Zd().clear()
        },
        ji: function() {
            this.F.Qa.clear();
            this.Gg()
        },
        Dk: function() {
            r.visible() && "smiles" == r.type ? r.Ia() : (r.show(this.F.Wf, "", f, "white", "smiles").content(this.F.Oi.C()), nb.each(function(a) {
                var b = new Element("img", {
                    src: "//" + CONF_DOMAIN_IMG + "/pub/smiles/" + a[0] + ".gif",
                    title: a[1]
                });
                T(b, this.mj.bind(this,
                    a[1]));
                this.F.Oi.ins(b)
            }.bind(this)))
        },
        mj: function(a) {
            r.Ia();
            this.focus();
            var b = this.F.Qa;
            document.selection ? (b.focus(), sel = document.selection.createRange(), sel.text = a) : b.value = b.selectionStart || "0" == b.selectionStart ? b.value.substring(0, b.selectionStart) + a + b.value.substring(b.selectionEnd, b.value.length) : b.value + a
        },
        send: function() {
            var a = this.zg();
            if (a.la.blank() || this.Mg) return k;
            if (a.Aa.yd && !a.to) return new L(0, "warning", "{{chat_no_destination}}");
            this.Mg = f;
            this.F.ye.hide();
            this.F.ze.show();
            this.F.Qa.disable();
            var b = {
                txt: a.la,
                to: a.to,
                is: {
                    privat: a.Aa.yd,
                    clanchat: a.Aa.Rd,
                    moder: a.Aa.hf,
                    ad: a.Aa.jc
                }
            };
            a.Aa.console ? new p("console", {
                parameters: b,
                J: function() {
                    this.ae.push(a.la);
                    this.uc = this.ae.length;
                    this.ji()
                }.bind(this),
                onComplete: this.lh.bind(this)
            }) : oa.emit("chatmsg", b, function(a) {
                a === f ? this.ji() : new L(M, "warning", a);
                this.lh()
            }.bind(this))
        },
        lh: function() {
            this.Mg = k;
            this.F.ye.show();
            this.F.ze.hide();
            this.F.Qa.enable();
            this.F.Qa.focus()
        },
        Ro: function(a) {
            this.areas.loc.isEmpty() || (a = (new Element("div")).addClassName("loc txtgray").C("&nbsp&nbsp&nbsp" +
                a), this.Ze("loc", a).Dc())
        },
        Zk: function(a) {
            if (!a.is.unignorable && a.userFrom[0] != l.id && (!a.is.ad && !a.is.clanchat && 1 == E.params.chat_mode && a.userTo[0] != l.id || !a.is.ad && 2 == E.params.chat_mode && a.userTo[0] != l.id || a.is.ad && 0 == E.params.chat_ad || a.is.ad && 1 == E.params.chat_ad && a.region != l.region || B.Kg(a.userFrom[0]))) return k;
            var b = new Y(a.userFrom),
                c = a.userTo ? new Y(a.userTo) : 0,
                d = new ob(b, c, a),
                e = "loc";
            a.is.clanchat && (e = "clan");
            a.is.moder && (e = "moder");
            a.is.ad && (e = "ad");
            a.is.privat && (e = b.id == l.id ? c : b);
            d = this.Ze(e,
                d);
            (c && c.id == l.id || a.is.clanchat || a.is.moder) && d.kj(1);
            c && c.id == l.id && K.Lc("chat");
            b.id == l.id && d.open();
            b.id == l.id && d.Dc()
        },
        Eh: function(a) {
            return this.Ze(a, 0)
        },
        Zg: function(a) {
            return this.Eh(a).open()
        },
        Ze: function(a, b) {
            var c = "string" === typeof a ? a : "u" + a.id;
            this.areas[c] || (this.areas[c] = new pb(a));
            b && this.areas[c].ins(b);
            return this.areas[c]
        },
        li: function(a) {
            for (var b in this.areas) b != a && this.areas[b].ya && this.areas[b].close();
            a && this.areas[a] && this.areas[a].open()
        },
        Zd: function() {
            for (var a in this.areas)
                if (this.areas[a].Va()) return this.areas[a]
        },
        Ul: function(a) {
            var b = a.id,
                c = new Y(a.deleter),
                d = a.period,
                e = a.periodid;
            a = a.from;
            b = !d ? "[data-id=" + b + "]" : "[data-from-id=" + a + "]";
            $$("#divChat .divChatArea > .post" + b).each(function(a) {
                if (!d || a.getAttribute("data-id") > e) a.addClassName("deleted"), a.select(".text")[0].C("...{{chat_post_deleted}}...")
            });
            $$("#divChat .divChatArea .abuse .post" + b).each(function(a) {
                if (!d || a.getAttribute("data-id") > e) a.addClassName("deleted"), a.next().ins(", " + (!d ? "{{chat_post_deleted}}" : "{{chat_post_deleted_all:" + d + "}}") + " ").ins(new Y(c))
            })
        }
    }),
    ob = Class.create(V, {
        initialize: function($super, b, c, d) {
            $super();
            d.color || (d.color = 0);
            this.id = d.id;
            this.lo = ((b.id == l.id ? "<i>" + b.ga + "</i>: " : "") + d.txt).truncate(40);
            this.B = (new Element("div")).addClassName("post");
            this.sa = moment(d.dat);
            var e = (new Element("span")).addClassName("time").C(this.sa.format(this.sa.diff(x.na, "days") ? "DD MMM HH:mm " : "HH:mm ")),
                m = (new Element("span")).addClassName("users").C(b);
            if (c) {
                var g = (new Element("span")).addClassName("to").ins(" &gt; ").ins(c);
                m.ins(g)
            }
            m.ins(": ");
            g = (new Element("span")).addClassName("text textColor" +
                d.color).C(Parser.Qb(d.txt));
            this.B.setAttribute("data-id", this.id);
            this.B.setAttribute("data-from-id", b.id);
            this.B.qa("mine", b.id == l.id || c && c.id == l.id);
            this.B.qa("ad", d.is.ad);
            this.B.ins(e).ins(m).ins(g);
            T(e, this.pb.bind(this))
        },
        pb: function() {
            this.B.hasClassName("deleted") || (q.show(), q.add("{{chat_post_abuse}}", this.Uk.bind(this)), -1 !== l.Vk.indexOf("moder") && (q.add("{{chat_post_del}}", this.del.bind(this)), q.add("{{chat_post_del_all:3}}", this.del.bind(this, 3)), q.add("{{chat_post_del_all:10}}", this.del.bind(this,
                10)), q.add("{{chat_post_del_all:30}}", this.del.bind(this, 30))))
        },
        Uk: function() {
            new L(M, "success", "{{chat_post_abuse_ok}}");
            oa.emit("abuse", this.id)
        },
        del: function(a) {
            if (!a || O("{{chat_post_del_all:" + a + "}}")) oa.emit("delpost", {
                id: this.id,
                period: a ? a : 0,
                from: a ? this.B.getAttribute("data-from-id") : 0
            })
        }
    }),
    pb = Class.create({
        initialize: function(a) {
            this.si = k;
            "string" === typeof a ? (this.id = a, this.ya = 0, "loc" == this.id && (this.title = "{{chat_area_loc}}"), "clan" == this.id && (this.title = "{{chat_area_clan}}"), "moder" == this.id &&
                (this.title = "{{chat_area_moder}}"), "ad" == this.id && (this.title = "{{chat_area_ad}}")) : (this.id = "u" + a.id, this.ya = a, this.title = this.to = this.ya.ga);
            this.Kb = (new Element("div")).addClassName("divChatTab");
            this.Pi = (new Element("div")).addClassName("divChatTabTitle").C(this.title);
            this.Wd = (new Element("div")).addClassName("divChatTabCount");
            this.Kb.ins(this.Pi).ins(this.Wd);
            "loc" != this.id && "clan" != this.id && (this.xl = (new N("", "nobg")).click(this.close.bind(this)).click(B.li.bind(B, this.id), 9), this.Kb.ins(this.xl));
            T(this.Kb, this.open.bind(this));
            $("divChatTabs").ins(this.Kb);
            this.Ta = (new Element("div")).addClassName("divChatArea").hide();
            $("divChatAreas").ins(this.Ta);
            this.ya ? (this.Kb.addClassName("ugroup" + this.ya.Ed), this.Ta.addClassName("privat"), "u0" == this.id && this.Kb.addClassName("l17")) : this.Kb.addClassName("ugroup0");
            this.uj()
        },
        uj: function(a) {
            this.ya && (a ? this.si = f : a = 0, new p("talks/history", [this.ya.id, a], {
                J: function(b) {
                    var c = new Y(l);
                    c.Za = 0;
                    b.each(function(a) {
                        var b = new Y(a.fromID == l.id ? c : this.ya);
                        a.is = {
                            privat: 1
                        };
                        this.ins(new ob(b, 0, a), f)
                    }.bind(this));
                    b.size() >= TALK_PERPAGE_POSTS && (this.Rh = (new N("{{chat_load_more}}", "gray btnLoadMore")).click(function() {
                        this.Rh.remove();
                        this.uj(b[b.size() - 1].id)
                    }.bind(this)), this.ins(this.Rh, f));
                    a || (B.Kg(this.ya.id) && this.ins('<span class="redtext">{{blacklist_inlist}}</span>'), ta.vf(this.ya.id, this.Va()))
                }.bind(this)
            }))
        },
        np: function(a) {
            this.Pi.C(a);
            return this
        },
        Fc: function(a) {
            this.ya || (this.to = a, B.F.Hb.value = this.to || "", B.focus())
        },
        kj: function(a) {
            a || (a = 1);
            a +=
                this.aj();
            this.fk(a);
            return this
        },
        fk: function(a) {
            this.Va() && (a = 0);
            this.Wd.C("+" + a);
            a ? this.Wd.show() : this.Wd.hide();
            return this
        },
        aj: function() {
            return parseInt(this.Wd.innerHTML) || 0
        },
        ins: function(a, b) {
            var c = Wa(this.Ta, 20),
                d = S && !b || !S && b;
            if (!a.id || !this.Ta.select(".post[data-id=" + a.id + "]").length) return d ? this.Ta.ins({
                top: a
            }) : this.Ta.ins(a), d = this.Ta.select(".post"), d.length > mb + 10 && !this.si && (d = !S ? d.slice(0, d.length - mb) : d.slice(d.length - mb, d.length), d.invoke("remove")), !b && (this.ya && a.id) && ta.vf(this.ya.id,
                this.Va(), a.lo), c && this.Dc(), this
        },
        Dc: function() {
            S || (this.Ta.scrollTop = 65E3)
        },
        open: function(a) {
            if (a && a.altKey) this.clear();
            else {
                B.focus && (B.focus(), B.F.Hb.value = this.to || "", this.ya ? B.F.Hb.disable() : B.F.Hb.enable());
                if (this.Va()) return this;
                this.ya && this.aj() && ta.vf(this.ya.id, f);
                this.fc();
                $("divChatTabs").select(".divChatTab").invoke("removeClassName", "selected");
                $("divChatAreas").select(".divChatArea").invoke("hide");
                this.Kb.addClassName("selected");
                this.Ta.show();
                this.fk(0);
                this.Dc();
                return this
            }
        },
        fc: function() {
            S || this.Ta.setStyle({
                height: $("divChat").getHeight() - $("divChatTabs").getHeight() + "px"
            })
        },
        close: function() {
            this.Kb.remove();
            this.Ta.remove();
            delete B.areas[this.id];
            B.areas.loc.open()
        },
        Va: function() {
            return this.Ta.visible()
        },
        isEmpty: function() {
            return this.Ta.innerHTML.empty()
        },
        clear: function() {
            this.Ta.C()
        }
    });
Clan = {
    sd: h,
    ie: 0,
    Rc: function(a) {
        a || (a = l.Za);
        w.open("divClanCard");
        new p("clan/card", [a], {
            G: $("divClanCard"),
            J: function(a) {
                Clan.ie = a.id;
                var c = (new Element("div")).addClassName("clancard");
                $("divClanCard").C(c);
                var d = new qb(a.id),
                    e = (new Element("div")).addClassName("title"),
                    m = (new Element("div")).addClassName("clantitle").C(a.title),
                    g = (new Element("div")).addClassName("claninfo txtgray2"),
                    v = (new Element("span")).addClassName("division").C("{{clan_division:" + a.division + "}}"),
                    G = (new Element("span")).addClassName("position").U("{{clan_position}}"),
                    U = (new Element("span")).addClassName("rate").U("{{clan_rate}}"),
                    ka = (new Element("span")).addClassName("size").U("{{clan_size}}"),
                    qa = (new Element("span")).addClassName("est").U("{{clan_est:" + a.est + "}}").C("est. " + a.est);
                g.ins(v).ins(G).ins(a.position).ins(U).ins(a.rate).ins(ka).ins(a.members.size());
                a.est < x.na.year() && g.ins(qa);
                e.ins(m).ins(g);
                var ra = (new Element("div")).addClassName("members");
                a.members ? a.members.each(function(c) {
                    var d = (new Element("div")).addClassName("member"),
                        e = new Y(c),
                        m = "<b>" +
                        e.ga + "</b>";
                    e.id == a.founder && (e.addClassName("founder"), m += "<br>{{clan_member_founder}}");
                    e.lc && (e.addClassName("leader"), m += "<br>{{clan_member_leader}}");
                    c.probation && (m += "<br>{{clan_member_probation}}");
                    m += "<br>{{clan_member_title}}: " + (c.rank || " - ");
                    m += "<br>{{clan_member_rate}}: " + Ba(c.rate);
                    m += "<br>{{clan_member_dat}}: " + Ja(c.dat);
                    e.U(m);
                    c = (new Element("span")).addClassName("rank").C(c.rank || "").ins(c.rate ? " " + Ba(c.rate) : "");
                    d.ins(e).ins(c);
                    ra.ins(d)
                }) : ra.ins(la("empty"));
                var m = (new Element("div")).addClassName("infos"),
                    fa = (new Element("div")).addClassName("dominations");
                a.master && (fa.addClassName("masters"), fa.ins((new Element("span")).C("{{clan_slave}}")).ins(new qb(a.master.id, a.master.title)));
                a.slaves && (fa.addClassName("slaves"), a.slaves.each(function(a) {
                    fa.ins((new Element("span")).C("{{clan_dominate}}")).ins(new qb(a.id, a.title))
                }));
                m.ins(fa);
                if (a.occupancy) {
                    g = (new Element("div")).addClassName("occupancy");
                    for (v = 0; v < a.occupancy.length; v++) {
                        var la = (new Element("div")).C(a.occupancy[v].title);
                        T(la, Clan.Em.curry(la,
                            a.occupancy[v].id, a.occupancy[v].expire, a.occupancy[v].pretend));
                        a.occupancy[v].pretend && la.addClassName("pretend");
                        g.ins(la)
                    }
                    fa.ins(g)
                }
                c.ins(d).ins(e).ins(R()).ins(ra).ins(m);
                l.Za == a.id && (d = (new Element("div")).addClassName("emblems txtgray2"), d.ins("{{clan_emblems:" + a.emblems + "}}").ins("<br>"), d.ins("{{clan_fond:" + a.fond + "}}"), m.ins(d), d = (new Element("div")).addClassName("buttons"), e = (new N("", "ctrl nobg btnAd", {
                    ta: f,
                    U: "{{clan_menu_ad}}"
                })).click(Clan.jc), la = (new N("", "ctrl nobg btnFond", {
                    ta: f,
                    U: "{{clan_menu_fond}}"
                })).click(Clan.nm), g = (new N("", "ctrl nobg btnRank", {
                    ta: f,
                    U: "{{clan_menu_ranks}}"
                })).click(Clan.Gn), v = (new N("", "ctrl nobg btnLeave", {
                    ta: f,
                    U: "{{clan_menu_leave}}"
                })).click(Clan.mn), d.ins(e).ins(la).ins(g).ins(v), m.ins(d));
                d = (new Element("div")).addClassName("log tabbox");
                c.ins(R()).ins((new Element("span")).addClassName("events").C("{{clan_events}}")).ins(d);
                Clan.log(a.id)
            }
        })
    },
    ka: function() {
        l.Za == Clan.ie && "divClanCard" == w.rb() && Clan.Rc(Clan.ie)
    },
    Em: function(a, b, c, d) {
        r.show(a,
            "<b>{{clan_occupied_by:" + c + "}}</b>");
        new p("loc/path", [b], {
            G: r,
            J: function(a) {
                a = "&nbsp;<br>" + a;
                l.Za == Clan.ie && d && (d = moment(d), a += '<br>&nbsp;<br><span class="rednumber">{{clan_pretended_loc:' + d.diff(x.na, "seconds") + "}}</span><br>", r.T("{{clan_pretended_loc_accept}}", y.start.curry(rb, b)));
                r.content(a)
            }
        })
    },
    log: function(a, b) {
        b || (b = [1, ""]);
        var c = $$("#divClanCard div.log")[0];
        new p("clan/log", [a], {
            aa: b,
            G: c,
            J: function(b) {
                Clan.sd = new $a(c, b.navi, {
                    Bc: Clan.log.curry(a)
                });
                c.C();
                b.events.each(function(a) {
                    c.ins(new sb(a))
                })
            }
        })
    },
    jc: function() {
        if (!l.lc) return new L(M, "warning", "{{clan_you_cant_rule}}");
        t.show("{{clan_menu_ad}}");
        var a = (new Element("input")).addClassName("txtClanAd");
        t.ins(a).ins("<br>");
        a.select();
        var b = (new N("{{button_ok}}", "gray")).click(function() {
            new p("clan/ad", {
                parameters: {
                    txt: $F(a)
                },
                J: Clan.ka
            });
            t.close()
        });
        t.ins(b);
        t.wc()
    },
    nm: function() {
        t.show("{{clan_menu_fond}}", {
            width: 300
        });
        var a = Ua(new Element("input"), "int"),
            b = new Element("input");
        t.ins(Q("spinpt", "{{amount}}:")).ins(a).ins("<br>");
        t.ins(Q("spinpt",
            "{{clan_fond_purpose}}:")).ins(b).ins("<br>");
        a.select();
        var c = (new N("{{button_ok}}", "gray")).click(function() {
            new p("clan/fond", {
                parameters: {
                    amount: $F(a),
                    txt: $F(b)
                },
                J: Clan.ka
            });
            t.close()
        });
        t.ins(c);
        t.wc()
    },
    Gn: function() {
        q.show("{{clan_menu_ranks}}");
        new p("clan/ranklist", {
            G: q,
            J: function(a) {
                a && a.size() && a.each(function(a) {
                    q.add("<b>#" + a.id + "</b> " + a.title, Clan.Uj.curry(a.id, a.title))
                });
                q.add("<b>{{clan_add_rank}}</b>", Clan.Uj.curry("", ""))
            }
        })
    },
    Uj: function(a, b) {
        if (!l.lc) return new L(M, "warning", "{{clan_you_cant_rule}}");
        t.close();
        t.show("{{clan_menu_ranks}}", {
            width: 360
        });
        var c = Ua(new Element("input", {
                value: a
            }), "unsign int"),
            d = new Element("input", {
                value: b,
                maxlength: 24
            });
        t.ins(Q("spinpt", "{{clan_rank_val}}:")).ins(c).ins("<br>");
        t.ins(Q("spinpt", "{{clan_rank_title}}:")).ins(d).ins("<br>");
        c.select();
        var e = (new N("{{button_ok}}", "gray")).click(function() {
            new p("clan/rankadd", {
                parameters: {
                    rank_id: $F(c),
                    title: $F(d)
                },
                J: Clan.ka
            });
            t.close()
        });
        t.ins(e).wc();
        a && (c.disable(), e = (new N("{{button_del}}", "gray")).click(function() {
            if (!O("{{clan_rank_del_confirm}}")) return k;
            new p("clan/rankadd", {
                parameters: {
                    rank_id: $F(c)
                },
                J: Clan.ka
            });
            t.close()
        }), t.ins(e))
    },
    Aj: function(a, b) {
        new p("clan/member_rank", [a.id, b], {
            J: Clan.ka
        })
    },
    ln: function(a, b) {
        new p("clan/member_lead", [a.id, b], {
            J: Clan.ka
        })
    },
    nn: function(a) {
        O("{{clan_drop_confirm:" + a.ga + "}}") && new p("clan/member_rem", [a.id], {
            J: Clan.ka
        })
    },
    mn: function() {
        O("{{clan_leave_confirm}}") && (new p("clan/member_leave"), w.close())
    }
};
var qb = Class.create({
        initialize: function(a, b, c, d) {
            this.B = (new Element("div")).addClassName(c ? "clantiny" : "clanlogo");
            this.B.style.backgroundImage = 'url("//' + CONF_DOMAIN_IMG + "/dyn/clans/" + a + (c ? "m" : "") + '.png")';
            b && (d ? this.B.C("<span>" + b + "</span>") : this.B.U(b, f));
            T(this.B, function(a) {
                Clan.Rc(a);
                ma.hide()
            }.curry(a));
            return this
        },
        toElement: ba("B")
    }),
    sb = Class.create(V, {
        initialize: function($super, b) {
            this.id = b.id;
            this.fixed = b.fixed ? f : k;
            this.B = (new Element("div")).addClassName("event" + (this.fixed ? " fixed" : ""));
            this.sa = (new Element("span")).addClassName("dat txtgray2").C(Ia(b.dat));
            this.xa = new Y(b.user);
            this.B.ins(this.sa).ins(this.xa).ins(b.txt);
            T(this.sa, this.pb.bind(this))
        },
        pb: function() {
            l.Za === Clan.ie && l.lc && (q.show(), q.add(this.fixed ? "{{clan_logevent_unfix}}" : "{{clan_logevent_fix}}", this.lm.bind(this)))
        },
        lm: function() {
            new p("clan/event_fix", [this.id, this.fixed ? 0 : 1], {
                J: Clan.ka
            })
        }
    }),
    va = Class.create({
        initialize: function() {
            this.$a = [];
            this.$a[1] = {
                kb: (new Element("div")).addClassName("divClanList"),
                Id: (new N("{{clan_division:1}}",
                    "", {
                        ta: f
                    })).click(this.oh.curry(1))
            };
            this.$a[2] = {
                kb: (new Element("div")).addClassName("divClanList"),
                Id: (new N("{{clan_division:2}}", "", {
                    ta: f
                })).click(this.oh.curry(2))
            };
            var a = D.add("clans", this.load);
            a.ins(R());
            a.ins(this.$a[1].Id).ins(this.$a[2].Id);
            a.ins(this.$a[1].kb).ins(this.$a[2].kb)
        },
        load: function() {
            va.oh(1);
            new p("clan/load", {
                G: va.$a[1].kb,
                J: function(a) {
                    for (var b = 1; 2 >= b; b++) va.$a[b].kb.C();
                    a.each(function(a) {
                        var b = (new Element("div")).addClassName("divClan"),
                            e = new qb(a.id, a.title),
                            m = (new Element("div")).addClassName("divClanTitle").C(a.title),
                            g = (new Element("div")).addClassName("divClanPosition").C(a.rate),
                            v = (new Element("div")).addClassName("divClanMaster");
                        a.master && (m.addClassName("dominate"), v.ins(new qb(a.master.id, "{{clan_dominated_by:" + a.master.title + "}}", f)));
                        b.ins(e).ins(m).ins(v).ins(g);
                        va.$a[a.division].kb.ins(b).ins(R())
                    })
                }
            })
        },
        oh: function(a) {
            for (var b = 1; 2 >= b; b++) va.$a[b].kb.hide(), va.$a[b].Id.removeClassName("pressed");
            va.$a[a].kb.show();
            va.$a[a].Id.addClassName("pressed")
        }
    });
Compete = {
    show: function() {
        J.close();
        t.show("{{compete_title}}", {
            name: "compete"
        });
        this.zi = (new Element("div")).addClassName("divCompete");
        this.Da = (new Element("div")).addClassName("divCompeteInner");
        this.Qf = (new N("\u043d\u0430\u0441\u0442\u0440\u043e\u0439\u043a\u0438", "gray small btnCompeteOptions")).click(this.pn.bind(this)).hide();
        this.we = (new N("\u0437\u0430\u0440\u0435\u0433\u0438\u0441\u0442\u0440\u0438\u0440\u043e\u0432\u0430\u0442\u044c\u0441\u044f", "btnCompeteContribute")).click(this.Nl.bind(this)).hide();
        this.zi.C(this.Da);
        t.ins(this.Qf).ins(this.we).ins(this.zi);
        this.load()
    },
    load: function() {
        new p("compete/load", {
            G: this.Da,
            J: function(a) {
                this.af = a.is_staff;
                this.C(a)
            }.bind(this),
            onError: function() {
                t.close()
            }
        })
    },
    C: function(a) {
        if (this.bc()) {
            this.id = a.id;
            this.title = a.title;
            this.ua = a.laps;
            this.ni = a.contribute;
            this.Ol = a.contributors;
            this.pj = -1 < this.Ol.indexOf(l.id);
            this.ff = this.ua.size() - 1;
            t.title(this.title);
            this.Da.setStyle({
                width: 250 * this.ff + "px"
            }).C();
            this.af ? (this.Qf.show(), this.we.hide()) : this.Qf.hide();
            this.pj ? this.we.hide() : this.we.C("\u0417\u0430\u0440\u0435\u0433\u0438\u0441\u0442\u0440\u0438\u0440\u043e\u0432\u0430\u0442\u044c\u0441\u044f \u0438 \u043e\u043f\u043b\u0430\u0442\u0438\u0442\u044c \u0432\u0437\u043d\u043e\u0441 " + Aa(this.ni)).show();
            for (a = 1; a <= this.ff; a++) {
                var b = k,
                    c = h;
                this.ua[a].mode = this.ua[a].mode;
                this.ua[a].zc = this.ua[a].members;
                this.ua[a].Hc = [];
                var d = (new Element("div")).addClassName("divLap"),
                    e = (new Element("div")).addClassName("divLapTitle").C("\u0422\u0443\u0440 " + a),
                    m = (new N("\u0440\u0435\u0433\u0438\u0441\u0442\u0440\u0430\u0446\u0438\u044f",
                        "gray")).click(this.Xm.bind(this, a)).hide(),
                    g = (new Element("div")).addClassName("divLapMode txtgray").C("{{compete_lap_modes." + this.ua[a].mode + "}}"),
                    v = (new Element("div")).addClassName("divLapJoined greennumber").C("\u0432\u044b \u0437\u0430\u0440\u0435\u0433\u0438\u0441\u0442\u0440\u0438\u0440\u043e\u0432\u0430\u043d\u044b").hide(),
                    G = (new Element("div")).addClassName("divLapMembers"),
                    U = (new Element("div")).addClassName("divLapMembersTotal txtgray");
                d.ins(e).ins(g).ins(v).ins(m).ins(G).ins(U);
                this.af &&
                    (g = (new N("", "nobg ctrl options")).click(this.rn.bind(this, a)), e.ins(g));
                for (e = 0; e < this.ua[a].zc.size(); e++) g = new Y(this.ua[a].zc[e]), g.$m = a, g.gc = this.ua[a].zc[e].subgroup, g.result = this.ua[a].zc[e].result, g.hd = this.ua[a].zc[e].fightID, g.addClassName(g.result), this.af && (g.T("{{compete_user_menu_set_result_win}}", this.mh.bind(this, a, g, 1)), g.T("{{compete_user_menu_set_result_lose}}", this.mh.bind(this, a, g, -1)), g.T("{{compete_user_menu_set_result_reset}}", this.mh.bind(this, a, g, 0)), g.T("---"), g.T("{{compete_user_menu_subgroup_change}}",
                    this.jk.bind(this, a, g, h)), g.gc && (g.T("{{compete_user_menu_subgroup_remove}}", this.jk.bind(this, a, g, 0)), g.T("{{compete_user_menu_subgroup_replay}}", this.Mn.bind(this, a, g.gc))), g.T("---"), g.T("{{compete_user_menu_lap_remove}}", this.Kn.bind(this, a, g))), g.id == l.id && (b = f, c = g.gc), this.ua[a].Hc[g.gc] || (this.ua[a].Hc[g.gc] = []), this.ua[a].Hc[g.gc].push(g), this.ua[a].zc[e] = g;
                for (e = 0; e < this.ua[a].Hc.size(); e++)
                    if (this.ua[a].Hc[e]) {
                        for (var ka = (new Element("div")).addClassName("divSubgroup" + (e ? "" : " nosubgroup")).ins((new Element("span")).addClassName("spSubgroupNum txtgray2").C(e ?
                                e : "")), qa = {}, ra = 0; ra < this.ua[a].Hc[e].size(); ra++) {
                            g = this.ua[a].Hc[e][ra];
                            if (qa[g.hd]) {
                                if (g.hd) {
                                    var fa = (new N("", "ctrl nobg view btnVight")).click(Vight.join.curry(g.hd));
                                    qa[g.hd].ins(fa)
                                }
                            } else ka.ins(qa[g.hd] = (new Element("div")).addClassName("divSubgroupFight"));
                            qa[g.hd].ins(g);
                            c === g.gc && (g.id != l.id && 2 == this.ua[a].mode) && (g.T("---"), g.T("{{compete_user_menu_call}}", g.call.bind(g, tb)))
                        }
                        G.ins(ka)
                    }
                U.C("\u0423\u0447\u0430\u0441\u0442\u043d\u0438\u043a\u043e\u0432: " + this.ua[a].zc.size());
                1 == this.ua[a].mode &&
                    (!b && this.pj) && m.show();
                b && v.show();
                this.Da.ins(d)
            }
        }
    },
    pn: function() {
        q.show();
        q.add("\u041d\u043e\u0432\u044b\u0439 \u0442\u0443\u0440\u043d\u0438\u0440", this.Pl.bind(this));
        q.add("\u0418\u0437\u043c\u0435\u043d\u0438\u0442\u044c \u043d\u0430\u0437\u0432\u0430\u043d\u0438\u0435", this.rename.bind(this));
        q.add("\u0414\u043e\u0431\u0430\u0432\u0438\u0442\u044c \u0442\u0443\u0440", this.Yk.bind(this))
    },
    rn: function(a) {
        q.show();
        for (var b = 0; 3 >= b; b++) q.add("{{compete_lap_modes." + b + "}}", this.eo.bind(this, a,
            b), this.ua[a].mode == b ? {
            selected: f
        } : h);
        q.add("---");
        q.add("\u0420\u0430\u0437\u0431\u0438\u0442\u044c \u0443\u0447\u0430\u0441\u0442\u043d\u0438\u043a\u043e\u0432 \u043d\u0430 \u0433\u0440\u0443\u043f\u043f\u044b...", this.kn.bind(this, a));
        q.add("\u0414\u043e\u0431\u0430\u0432\u0438\u0442\u044c \u043f\u043e\u0431\u0435\u0434\u0438\u0442\u0435\u043b\u0435\u0439 \u0442\u0443\u0440\u0430...", this.rj.bind(this, a, 1));
        q.add("\u0414\u043e\u0431\u0430\u0432\u0438\u0442\u044c \u043f\u0440\u043e\u0438\u0433\u0440\u0430\u0432\u0448\u0438\u0445 \u0442\u0443\u0440\u0430...",
            this.rj.bind(this, a, 0));
        q.add("---");
        q.add("\u041e\u0447\u0438\u0441\u0442\u0438\u0442\u044c \u0440\u0435\u0437\u0443\u043b\u044c\u0442\u0430\u0442\u044b \u0431\u043e\u0451\u0432", this.ki.bind(this, a, 1));
        q.add("\u041e\u0447\u0438\u0441\u0442\u0438\u0442\u044c \u0441\u043f\u0438\u0441\u043e\u043a \u0443\u0447\u0430\u0441\u0442\u043d\u0438\u043a\u043e\u0432", this.ki.bind(this, a))
    },
    Pl: function() {
        O("\u0422\u0435\u043a\u0443\u0449\u0438\u0439 \u0442\u0443\u0440\u043d\u0438\u0440 \u0431\u0443\u0434\u0435\u0442 \u0437\u0430\u0432\u0435\u0440\u0448\u0451\u043d. \u041f\u0440\u043e\u0434\u043e\u043b\u0436\u0438\u0442\u044c?") &&
            new p("compete/staff/create", {
                G: this.Da,
                onComplete: this.load.bind(this)
            })
    },
    rename: function() {
        var a = prompt(n("\u0412\u0432\u0435\u0434\u0438\u0442\u0435 \u043d\u0430\u0437\u0432\u0430\u043d\u0438\u0435 \u0442\u0443\u0440\u043d\u0438\u0440\u0430:"), this.title);
        a && new p("compete/staff/rename", {
            parameters: {
                title: a
            },
            G: this.Da,
            onComplete: this.load.bind(this)
        })
    },
    Yk: function() {
        O("\u0414\u043e\u0431\u0430\u0432\u0438\u0442\u044c \u0442\u0443\u0440?") && new p("compete/staff/addlap", {
            G: this.Da,
            onComplete: this.load.bind(this)
        })
    },
    eo: function(a, b) {
        new p("compete/staff/setlapmode", [a, b], {
            G: this.Da,
            onComplete: this.load.bind(this)
        })
    },
    ki: function(a, b) {
        O(b ? "\u041e\u0447\u0438\u0441\u0442\u0438\u0442\u044c \u0440\u0435\u0437\u0443\u043b\u044c\u0442\u0430\u0442\u044b \u0431\u043e\u0451\u0432?" : "\u041e\u0447\u0438\u0441\u0442\u0438\u0442\u044c \u0441\u043f\u0438\u0441\u043e\u043a \u0443\u0447\u0430\u0441\u0442\u043d\u0438\u043a\u043e\u0432 \u0438 \u0440\u0435\u0437\u0443\u043b\u044c\u0442\u0430\u0442\u043e\u0432 \u0431\u043e\u0451\u0432?") &&
            new p("compete/staff/clearlap", [a, b], {
                G: this.Da,
                onComplete: this.load.bind(this)
            })
    },
    kn: function(a) {
        if (O("\u0412\u0441\u0435 \u0442\u0435\u043a\u0443\u0449\u0438\u0435 \u0433\u0440\u0443\u043f\u043f\u044b \u0438 \u0440\u0435\u0437\u0443\u043b\u044c\u0442\u0430\u0442\u044b \u0431\u043e\u0451\u0432 \u0431\u0443\u0434\u0443\u0442 \u0441\u0431\u0440\u043e\u0448\u0435\u043d\u044b. \u041f\u0440\u043e\u0434\u043e\u043b\u0436\u0438\u0442\u044c?")) {
            var b = prompt(n("\u0423\u043a\u0430\u0436\u0438\u0442\u0435 \u0440\u0430\u0437\u043c\u0435\u0440 \u0433\u0440\u0443\u043f\u043f:"),
                2);
            new p("compete/staff/makesubgroups", [a, b], {
                G: this.Da,
                onComplete: this.load.bind(this)
            })
        }
    },
    rj: function(a, b) {
        var c = prompt(n(b ? "\u041f\u043e\u0431\u0435\u0434\u0438\u0442\u0435\u043b\u0435\u0439 \u043a\u0430\u043a\u043e\u0433\u043e \u0442\u0443\u0440\u0430 \u0434\u043e\u0431\u0430\u0432\u0438\u0442\u044c?" : "\u041f\u0440\u043e\u0438\u0433\u0440\u0430\u0432\u0448\u0438\u0445 \u043a\u0430\u043a\u043e\u0433\u043e \u0442\u0443\u0440\u0430 \u0434\u043e\u0431\u0430\u0432\u0438\u0442\u044c?"), a - 1);
        c && new p("compete/staff/joinusersbyresult",
            [a, c, b], {
                G: this.Da,
                onComplete: this.load.bind(this)
            })
    },
    Ym: function(a) {
        (this.fe = prompt(n("\u0412 \u043a\u0430\u043a\u043e\u0439 \u0442\u0443\u0440 \u0434\u043e\u0431\u0430\u0432\u0438\u0442\u044c \u0442\u0440\u0435\u043d\u0435\u0440\u0430 " + a.ga + "?"), this.fe ? this.fe : this.ff)) && !(this.fe > this.ff) && new p("compete/staff/joinuserlap", [this.fe, a.id], {
            G: this.Da,
            onComplete: this.load.bind(this)
        })
    },
    Kn: function(a, b) {
        new p("compete/staff/removeuserlap", [a, b.id], {
            G: this.Da,
            onComplete: this.load.bind(this)
        })
    },
    jk: function(a,
        b, c) {
        c === h && (c = prompt(n("\u0412 \u043a\u0430\u043a\u0443\u044e \u0433\u0440\u0443\u043f\u043f\u0443 \u0434\u043e\u0431\u0430\u0432\u0438\u0442\u044c \u0442\u0440\u0435\u043d\u0435\u0440\u0430 " + b.ga + "?"), b.gc));
        new p("compete/staff/setusersubgroup", [a, b.id, c], {
            G: this.Da,
            onComplete: this.load.bind(this)
        })
    },
    Mn: function(a, b) {
        O("\u0421\u0431\u0440\u043e\u0441\u0438\u0442\u044c \u0440\u0435\u0437\u0443\u043b\u044c\u0442\u0430\u0442\u044b \u0433\u0440\u0443\u043f\u043f\u044b #" + b + "?") && new p("compete/staff/replaysubgroup",
            [a, b], {
                G: this.Da,
                onComplete: this.load.bind(this)
            })
    },
    mh: function(a, b, c) {
        O(c ? (0 < c ? "\u0421\u0434\u0435\u043b\u0430\u0442\u044c \u041f\u041e\u0411\u0415\u0414\u0418\u0422\u0415\u041b\u0415\u041c \u0442\u0440\u0435\u043d\u0435\u0440\u0430 " : "\u0421\u0434\u0435\u043b\u0430\u0442\u044c \u041f\u0420\u041e\u0418\u0413\u0420\u0410\u0412\u0428\u0418\u041c \u0442\u0440\u0435\u043d\u0435\u0440\u0430 ") + b.ga + "?" : "\u0421\u0431\u0440\u043e\u0441\u0438\u0442\u044c \u0440\u0435\u0437\u0443\u043b\u044c\u0442\u0430\u0442 \u0442\u0440\u0435\u043d\u0435\u0440\u0430 " +
            b.ga + "?") && new p("compete/staff/setuserresult", [a, b.id, c], {
            G: this.Da,
            onComplete: this.load.bind(this)
        })
    },
    Xm: function(a) {
        O("\u0423\u0447\u0430\u0441\u0442\u0432\u043e\u0432\u0430\u0442\u044c \u0432 \u0442\u0443\u0440\u0435 #" + a + "?") && new p("compete/joinme", [a], {
            G: this.Da,
            J: function() {
                new L(M, "success", "\u0412\u044b \u0437\u0430\u0440\u0435\u0433\u0438\u0441\u0442\u0440\u0438\u0440\u043e\u0432\u0430\u043b\u0438\u0441\u044c \u0432 \u0442\u0443\u0440\u0435.")
            },
            onComplete: this.load.bind(this)
        })
    },
    Nl: function() {
        O("\u041e\u043f\u043b\u0430\u0442\u0438\u0442\u044c \u0432\u0437\u043d\u043e\u0441 " +
            Aa(this.ni) + " \u0438 \u043f\u0440\u0438\u043d\u044f\u0442\u044c \u0443\u0447\u0430\u0441\u0442\u0438\u0435 \u0432 \u0442\u0443\u0440\u043d\u0438\u0440\u0435?") && new p("compete/contributeme", {
            G: this.Da,
            J: function() {
                new L(M, "success", "\u0412\u044b \u0437\u0430\u0440\u0435\u0433\u0438\u0441\u0442\u0440\u0438\u0440\u043e\u0432\u0430\u043b\u0438\u0441\u044c \u0432 \u0442\u0443\u0440\u043d\u0438\u0440\u0435.")
            },
            onComplete: this.load.bind(this)
        })
    },
    bc: function() {
        return t.bc("compete")
    }
};
Craft = {
    loaded: k,
    show: function() {
        this.loaded = k;
        D.close();
        t.show("{{craft_title}}", {
            name: "craft"
        });
        new p("craft/list", {
            G: t,
            J: function(a) {
                var b = (new Element("div")).addClassName("divCraftList");
                t.C(b);
                if (!a) return b.Ra("{{craft_list_empty}}");
                for (var c = 0; c < a.items.size(); c++) {
                    var d = new ub(a.items[c]);
                    b.ins(d);
                    for (var e = a.items[c][9], m = 0; m < e.size(); m++) {
                        var g = (new vb(a.pokes[e[m].pokemonID], d)).km(e[m]);
                        d.re(g)
                    }
                    d.ii()
                }
                this.loaded = f
            }.bind(this)
        })
    },
    info: function(a) {
        r.visible() || r.show(a, "", k, "white");
        r.title("{{craft_info_title}}").Sd();
        new p("craft/info", [a.za.item.id, a.id], {
            G: r,
            J: function(b) {
                for (var c = (new Element("div")).addClassName("divCraftInfo"), d = (new Element("div")).addClassName("divCraftSources"), e = 0; e < b.sources.size(); e++) {
                    1 == e && d.ins((new Element("span")).addClassName("plus").insert("+"));
                    var m = new Z(b.sources[e], f, f, f),
                        g = (new Element("span")).C(b.have[e]);
                    m.Ve.ins("/").ins(g);
                    m.Ja > b.have[e] && (g.addClassName("rednumber"), m.addClassName("rednumber"));
                    d.ins(m)
                }
                e = (new Element("div")).addClassName("divCraftDuration").C(Ha(60 *
                    b.duration));
                b = (new Element("div")).addClassName("divCraftSkill").C("{{craft_skill:" + b.pk + "}}");
                c.ins(d).ins(b).ins(e).ins(new Z(a.za.item, k, f, f));
                r.content(c).T("{{craft_create_button}}", this.create.bind(this, a.za.item, a))
            }.bind(this)
        })
    },
    collect: function(a) {
        new p("craft/collect", [a.za.item.id, a.id], {
            G: t,
            J: aa().bind(this)
        })
    },
    create: function(a, b) {
        new p("craft/create", [a.id, b.id], {
            G: r,
            J: aa().bind(this)
        })
    }
};
var A = Class.create({
        initialize: function() {
            this.ia = 0;
            this.rg = T((new Element("div")).addClassName("divDexZoomerBg").hide(), this.If.curry(0));
            this.qg = T((new Element("div")).addClassName("divDexZoomer").hide(), this.If.curry(0));
            $("body").ins(this.rg).ins(this.qg.hide())
        },
        R: function(a, b, c) {
            var d = A.fa && A.fa.last ? A.fa.last : "evols";
            A.oa = 0 < b ? "shine" : "norm";
            A.form = c ? c : 0;
            w.open("divPokedex");
            $("divPokedex").C();
            A.Qi = (new Element("div")).addClassName("divUpper");
            A.wl = (new N("", "ctrl nobg prev")).click(A.R.curry("prev"));
            A.ul = (new N("", "ctrl nobg next")).click(A.R.curry("next"));
            A.Gc = new Ya("{{search_char}}", A.R, {
                Nc: ["pokename", A.R]
            });
            A.N = (new Element("div")).addClassName("pokemonBoxDex");
            A.Qi.ins(A.wl).ins(A.Gc).ins(A.ul);
            $("divPokedex").ins(A.Qi).ins(A.N);
            S || A.Gc.focus();
            if (!a) return A.N.Ra("{{dex_title}}");
            b = parseInt(A.ia);
            "next" == a && (a = 807 == b ? 840 : b + 1);
            "prev" == a && (a = 840 == b ? 807 : b - 1);
            new p("dex/poke", [A.form], {
                G: A.N,
                aa: [0, a],
                J: function(a) {
                    if (Object.isArray(a)) A.N.C(), a.each(function(a) {
                            A.N.ins(new lb(a)).ins("<br>")
                        }),
                        A.ia = 0;
                    else {
                        a.sp_id = ya(a.sp_id);
                        A.ia = a.sp_id;
                        A.form = a.form;
                        A.forms = a.forms;
                        A.bh = (new Element("div")).addClassName("powers");
                        for (var b = 1; b <= a.power; b++) A.bh.ins('<img src="//' + CONF_DOMAIN_IMG + '/pub/interface/star.png">');
                        A.bh.U("{{dex_power:" + a.power + "}}");
                        A.Xl = T((new Element("div")).addClassName("divForms").C(A.forms ? a.form_title : "").Ha(A.forms), A.no);
                        A.be = T((new Element("div")).addClassName("imagebox"), A.yo);
                        A.qo = (new Element("div")).addClassName("spWeight").C(a.lenght / 100 + " m<br>" + a.weight / 10 + " kg");
                        A.ph = (new Element("div")).addClassName("spShine").C("shine").Ha("shine" == A.oa);
                        A.be.ins(A.qo).ins(A.ph);
                        A.uh = (new Element("div")).addClassName("typs");
                        A.Ko = (new Element("img", {
                            src: "//" + CONF_DOMAIN_IMG + "/pub/typs/s/" + a.typ[0] + ".png"
                        })).U("{{typ." + a.typ[0] + "}}");
                        A.uh.ins(A.Ko).ins("&nbsp;");
                        a.typ[1] && (A.Lo = (new Element("img", {
                            src: "//" + CONF_DOMAIN_IMG + "/pub/typs/s/" + a.typ[1] + ".png"
                        })).U("{{typ." + a.typ[1] + "}}"), A.uh.ins(A.Lo).ins("&nbsp;"));
                        A.be.ins(A.uh);
                        A.Dl = (new N("", "nobg btnZoom")).click(A.If.curry(1));
                        A.be.ins(A.Dl);
                        A.params = (new Element("div")).addClassName("params");
                        A.title = (new Element("div")).addClassName("title").C("#" + a.sp_id + " " + a.pokename);
                        A.params.ins(A.title);
                        A.vo = (new Element("div")).addClassName("forms txtgray").ins("{{dex_data:" + a.numform + ":" + a.expid + ":" + a.rare + "}}");
                        A.wb = (new Element("div")).addClassName("stats");
                        for (b = 0; 5 >= b; b++) A.wb.ins((new wb(b)).ins(a.stats[b]));
                        A.La = (new Element("div")).addClassName("descr txtgray2").C(a.descr);
                        A.qm = (new Element("div")).addClassName("genders txtgray").C("{{sex." +
                            SEX_MALE + "}}" + (a.genders[0] ? a.genders[0] + "%" : "-") + " {{sex." + SEX_FEMALE + "}}" + (a.genders[1] ? a.genders[1] + "%" : "-"));
                        A.params.ins(A.vo).ins(A.wb).ins(A.La).ins(A.qm);
                        A.fa = new eb("right");
                        A.fa.add("evols", 0, "{{dex_tabs_evols}}");
                        A.cp = [];
                        a.evolves ? (a.evolves = Parser.ea(a.evolves), a.evolves = Parser.eb(a.evolves), a.evolves = Parser.wa(a.evolves)) : a.evolves = Q("empty", "{{dex_evol_empty}}");
                        var c = (new Element("div")).addClassName("divEvols").ins(a.evolves);
                        if (a.abilities) {
                            for (var v = (new Element("div")).addClassName("divAbilities").ins("{{dex_tabs_abilities}}:"),
                                    G = [], b = 0; 3 >= b; b++) G[b] = a.abilities[b].id ? (new Element("div")).addClassName("divAbilitieSocket").C(new fb(a.abilities[b], A.ia)) : "";
                            v.ins(G[1]).ins(G[2]);
                            G[3] && v.ins("<br>{{dex_ability_socket.3}}:").ins(G[3])
                        } else v = "";
                        A.fa.tab("evols").C(c).ins(v).addClassName("evolves");
                        c = [
                            ["lvls", "{{dex_tabs_lvls}}"],
                            ["tmhm", "{{dex_tabs_tmhm}}"],
                            ["tutor", "{{dex_tabs_tutor}}"],
                            ["breed", "{{dex_tabs_breed}}"]
                        ];
                        for (b = 0; b < c.size(); b++) {
                            var v = c[b][0],
                                U = A.fa.add(v, 0, c[b][1]);
                            a.moves[v] && 0 < a.moves[v].size() ? a.moves[v].each(function(a) {
                                    U.ins(new xb(a))
                                }) :
                                U.Ra()
                        }
                        A.fa.add("extinf", 0, "{{dex_tabs_etc}}", function() {
                            new p("dex/extinf", [A.ia], {
                                G: A.fa.tab("extinf"),
                                J: function(a) {
                                    var b = (new Element("div")).addClassName("extinf txtgray2");
                                    b.ins("{{dex_catched_amount:" + a.population + ":" + a.population_shine + "}}<br>");
                                    b.ins("{{dex_wished_amount:" + a.wishamount + "}}<br>");
                                    a.mywish && b.ins("{{dex_in_my_wishes}}<br>");
                                    a.ishave && b.ins("{{dex_have_char}}<br>");
                                    a.ishave_shine && b.ins("{{dex_have_char_shine}}<br>");
                                    var c = (new N(!a.mywish ? "{{dex_i_wish}}" : "{{dex_i_unwish}}",
                                        "gray")).click(function() {
                                        new p("dex/wish/" + (a.mywish ? "del" : "add"), [A.ia], {
                                            G: A.fa.tab("extinf"),
                                            J: function() {
                                                A.fa.open("extinf")
                                            }
                                        })
                                    });
                                    b.ins(c);
                                    A.fa.tab("extinf").C(b)
                                }
                            })
                        });
                        A.fa.open(d);
                        A.N.C().ins(A.bh).ins(A.Xl).ins(A.be).ins(A.params).ins(A.fa);
                        Parser.go();
                        a = $$(".divEvols .sp" + A.ia);
                        a[0] && a[0].addClassName("selected");
                        A.Nj()
                    }
                }
            })
        },
        Nj: function() {
            A.be.style.backgroundImage = 'url("//' + CONF_DOMAIN_IMG + "/pub/mnst/" + A.oa + "/full/" + A.ia + (A.form ? "_" + A.form : "") + '.png")'
        },
        yo: function() {
            "norm" == A.oa ? (A.oa = "shine",
                A.ph.Ha(f)) : (A.oa = "norm", A.ph.Ha(k));
            A.Nj()
        },
        no: function(a) {
            q.show("{{dex_forms}}");
            for (var b = 0; b < A.forms.size(); b++) q.add(A.forms[b].title, A.R.curry(A.ia, A.oa, A.forms[b].form));
            a.stopPropagation()
        },
        If: function(a, b) {
            b && b.stopPropagation && b.stopPropagation();
            if (a) {
                var c = (new Element("span")).C(A.title.innerHTML),
                    d = new Element("img", {
                        src: "//" + CONF_DOMAIN_IMG + "/pub/mnst/" + A.oa + "/hires/" + A.ia + (A.form ? "_" + A.form : "") + ".png"
                    });
                A.rg.show();
                A.qg.C(c).ins(d).show()
            } else A.rg.hide(), A.qg.hide()
        },
        rf: function(a,
            b, c, d) {
            T(a, function() {
                A.R(b, c, d)
            })
        }
    }),
    H = Class.create({
        initialize: function() {
            this.G = k;
            this.fa = new eb("right");
            this.$c = (new Element("div")).addClassName("divFacts");
            Xa(this.$c, this.tj.bind(this, f));
            this.fa.add("facts", 0, "{{diary_tab_news}}", this.tj.bind(this, k)).C(this.$c);
            yb = new yb;
            this.fa.add("quests", 0, "{{diary_tab_quests}}", yb.load.bind(yb)).C(yb.B);
            this.Ki = (new Element("div")).addClassName("divNotes");
            this.fa.add("notes", 0, "{{diary_tab_notes}}", this.dn.bind(this)).C(this.Ki);
            this.me = "";
            this.Ni =
                (new Element("div")).addClassName("divRecords");
            this.Rb = new Element("textarea", {
                maxlength: "20000",
                placeholder: n("{{diary_tab_records_tip}}"),
                autocomplete: "off"
            });
            this.Rb.on("keyup", this.Qd.bind(this));
            this.Rb.on("change", this.Qd.bind(this));
            this.He = (new Element("div")).addClassName("divRecordsControls").hide();
            this.Vh = (new N("{{button_save}}", "gray")).click(this.Sn.bind(this));
            this.Vf = (new N("{{button_undo}}", "gray")).click(this.No.bind(this));
            this.He.ins(this.Vh).ins(this.Vf);
            this.Ni.ins(this.Rb).ins(this.He);
            this.fa.add("records", 0, "{{diary_tab_records}}", this.en.bind(this)).C(this.Ni);
            this.fa.add("forecast", 0, "{{diary_tab_weather}}", this.cn.bind(this));
            D.add("diary", this.show.bind(this)).ins(this.fa)
        },
        show: function() {
            if (globalCallbacks.showquestindiary) return this.fa.open("quests"), this;
            this.fa.open("last");
            return this
        },
        tj: function(a) {
            if (!this.G) {
                if (a) {
                    if (!H.Rg) return;
                    var b = new Element("div");
                    this.$c.ins(b)
                }
                this.G = f;
                new p("diary/facts/load", a ? [this.Rg] : [], {
                    G: a ? b : this.$c,
                    J: function(c) {
                        if (c.tips) {
                            var d =
                                function(a, b) {
                                    m.C(a);
                                    b && m.ins((new N("{{diary_tips_details}}", "nobg link")).click(function() {
                                        window.open(b, "_blank")
                                    }))
                                },
                                e = (new Element("div")).addClassName("divTips txtgray2"),
                                m = new Element("span"),
                                g = (new N("{{diary_tips_did_you_know}}", "nobg link")).click(function() {
                                    new p("diary/tips/load", {
                                        G: m,
                                        J: function(a) {
                                            d(a.content, a.url)
                                        }
                                    })
                                }.bind(this));
                            e.ins(g).ins(m);
                            d(c.tips.content, c.tips.url);
                            H.$c.ins(e)
                        }
                        c = c.facts;
                        a && b.remove();
                        if (!c || !c.size()) H.Rg = 0;
                        else {
                            var v = 0;
                            c.each(function(a) {
                                H.$c.ins(new zb(a,
                                    v == a.userID));
                                H.Rg = a.id;
                                v = a.userID
                            });
                            Parser.go()
                        }
                    },
                    onComplete: function() {
                        H.G = k
                    }
                })
            }
        },
        dn: function() {
            var a = new Ab({
                id: 47,
                stage: Bb
            });
            a.toggle();
            this.Ki.C(a)
        },
        en: function() {
            new p("diary/records/load", {
                J: function(a) {
                    H.me = a ? "" + a : "";
                    H.Rb.value || (H.Rb.value = H.me);
                    H.Qd()
                }
            })
        },
        Qd: function() {
            H.me.replace(/\r/g, "").strip() == $F(H.Rb).replace(/\r/g, "").strip() ? H.He.hide() : H.He.show()
        },
        Sn: function() {
            H.Vf.Ha(k);
            new p("diary/records/save", {
                G: H.Vh,
                parameters: {
                    txt: H.Rb.value
                },
                J: function() {
                    H.me = H.Rb.value;
                    H.Vf.Ha(f);
                    H.Qd()
                }
            })
        },
        No: function() {
            O("{{diary_tab_records_undo_confirm}}") && (H.Rb.value = H.me, H.Qd())
        },
        cn: function() {
            new p("diary/forecast", {
                G: H.fa.tab("forecast"),
                J: function(a) {
                    for (var b = 0; b < a.size(); b++) {
                        var c = (new Element("div")).addClassName("divForecast");
                        c.ins('<span class="region">{{loc_regions.' + a[b].region + "}}</span>");
                        c.ins((new Element("div")).addClassName("iconweather w" + a[b].weather).U("{{weather." + a[b].weather + "}}"));
                        c.ins('<span class="temper txtgray2">' + a[b].temper + "\u00b0C</span>");
                        a[b].forecast && c.ins('<span class="forecast txtgray">{{diary_forecast}} {{weather.' +
                            a[b].forecast + "}}</span>");
                        H.fa.tab("forecast").ins(c).ins(R())
                    }
                }
            })
        }
    }),
    K = {
        Gf: 0,
        xh: 0,
        Km: function(a) {
            new L(1, Parser.ea(a.data.type), Parser.ea(a.data.txt), {
                sa: a.dat,
                from: a.from ? new Y(a.from, {
                    Ac: f
                }) : ""
            });
            Parser.go();
            K.Lc("alert")
        },
        Lm: function(a) {
            a.from && (a = new Y(a.from, {
                Ac: f
            }), new L(2, "info", "{{gym_badge_give_confirm}}", {
                from: a,
                lf: Gym.Lh.curry(a.id, f),
                Wg: Gym.Lh.curry(a.id, k)
            }))
        },
        Mm: function(a) {
            var b = new Element("div"),
                c = new qb(a.data.id);
            b.ins((new Element("span")).C(a.data.title).addClassName("title"));
            b.ins((new Element("span")).C(a.from ? new Y(a.from, {
                Ac: f
            }) : "").ins(a.data.txt).addClassName("txt"));
            (new L(1, "clan", b)).va.ins(c)
        },
        Jm: function(a) {
            var b = new Element("div");
            a.data.id && b.ins(new gb(a.data, f));
            b.ins((a.data.title ? "<b>" + a.data.title + "</b><br>" : "") + a.data.descr);
            new L(1, "achivement", b)
        },
        Qm: function(a) {
            var b = new Element("div"),
                c = new Ab(a.data, f);
            b.ins(c).ins((a.data.title ? "<b>" + a.data.title + "</b><br>" : "") + (a.data.txt ? a.data.txt : ""));
            new L(1, "inform", b, {
                Uf: "{{button_open}}",
                J: function() {
                    globalCallbacks.showquestindiary =
                        c.id;
                    D.open("diary");
                    this.hide()
                }
            })
        },
        Tm: function(a) {
            a.data.js && eval(a.data.js);
            a.data.txt && (a.data.txt = Parser.Qb(a.data.txt), new L(1, "system", "<b>{{console_sys_mess}}</b><br>" + a.data.txt, {
                sa: a.dat,
                from: a.from ? new Y(a.from, {
                    Ac: f
                }) : 0
            }), K.Lc("alert"))
        },
        Rm: function(a) {
            a.data.txt = Parser.Qb(a.data.txt);
            var b = (new N("{{radio_infromer_btn}}", "gray")).click(function() {
                window.open("http://volnorez.com/league17", "_blank")
            });
            new L(b, "radio", "<b>{{radio_infromer_title}}</b><br>" + a.data.txt, {
                sa: a.dat,
                timeout: 0
            });
            K.Lc("alert")
        },
        Sm: function(a) {
            a.data.txt = Parser.Qb(a.data.txt);
            var b = (new N("{{stream_infromer_btn}}", "gray")).click(function() {
                window.open("https://www.twitch.tv/diwadegold", "_blank")
            });
            new L(b, "stream", "<b>{{stream_infromer_title}}</b><br>" + a.data.txt, {
                sa: a.dat,
                timeout: 0
            });
            K.Lc("alert")
        },
        Nm: function(a) {
            a.data.txt = Parser.Qb(a.data.txt);
            new L(M, "event", a.data.txt, {
                sa: a.dat,
                from: a.from ? new Y(a.from, {
                    Ac: f
                }) : 0,
                timeout: 0
            });
            K.Lc("alert")
        },
        Om: function(a) {
            D.Y("profile").Ob(1, f);
            new L(1, "info", "{{gift_smbd_sent_gift}}", {
                sa: a.dat,
                from: a.from ? new Y(a.from, {
                    Ac: f
                }) : "{{gift_anonim}}",
                Uf: "{{button_open}}",
                J: function() {
                    D.open("profile");
                    this.hide()
                }
            });
            K.Lc("alert")
        },
        Pm: function(a) {
            a.from = new Y(a.from, {
                Ac: f
            });
            var b = "{{invite_" + a.data.type + "}}",
                c;
            switch (a.data.type) {
                case "clan":
                    b += " <b>" + a.data.txt + "<b>.";
                    break;
                case "breed":
                    c = Breed.ce;
                    break;
                case "fight":
                    var d = Cb;
                    if (a.data.txt) {
                        var e = a.data.txt.split(":"),
                            d = e[0],
                            e = e[1];
                        d == Db && (e = e.split(","), 6 > e[1] && (b += "<br><b>{{fight_conds.1}}</b> " + e[1]), e[2] < e[1] && (b += "<br><b>{{fight_conds.2}}</b> " +
                            e[2]), 100 > e[3] && (b += "<br><b>{{fight_conds.3}}</b> " + e[3]), 0 < e[4] && (b += "<br><b>{{fight_conds.4}}</b>"));
                        d == Eb && (b += "<br><b>{{fight_gym}}</b>");
                        d == tb && (b += "<br><b>{{fight_compete}}</b>")
                    }
                    c = function(a) {
                        y.start(d, a)
                    }
            }
            new L(2, "info", b, {
                timeout: 20,
                from: a.from,
                bi: "{{button_accept}}",
                Th: "{{button_decline}}",
                lf: function() {
                    c ? c(a.data.id) : new p("inviter/accept", [a.data.id])
                }
            });
            return this
        },
        tm: function(a) {
            var b = {
                plus: new Element("div"),
                minus: new Element("div")
            };
            a.items && a.items.each(function(a) {
                if (75 == a[1] ||
                    144 == a[1] || 501 == a[1]) a[3] = 0;
                var d = 0 <= a[2] ? "plus" : "minus";
                a[2] = Math.abs(a[2]);
                b[d].ins((new Z(a, f, f, f)).size("middle")).ins("<br>")
            });
            a.pokes && a.pokes.each(function(a) {
                b[0 <= a[3] ? "plus" : "minus"].ins(new lb({
                    sp_id: a[0],
                    pokename: a[1],
                    shine: a[2]
                })).ins("<br>")
            });
            b.plus.empty() || new L(M, "plus", b.plus, {
                timeout: 2 * ALERTEN_TIMEOUT
            });
            b.minus.empty() || new L(M, "minus", b.minus, {
                timeout: 2 * ALERTEN_TIMEOUT
            })
        },
        Lc: function(a) {
            if ("chat" == a && S && !$("divChat").visible()) {
                var b = parseInt(Na.B.innerHTML);
                Na.C((b ? b : 0) + 1)
            }
            Game.Fd ||
                (this.Gf++, this.wh(), C.play(a))
        },
        Uo: function(a) {
            Game.Fd || (this.xh = 1, this.wh(), C.play(a))
        },
        To: function() {
            this.xh = this.Gf = 0;
            this.wh()
        },
        wh: function() {
            this.yh || (this.yh = document.title);
            document.title = this.yh;
            Game.Fd || (document.title = this.yh + (this.Gf ? "  (" + this.Gf + ")" : "") + (this.xh ? n(" {{fight_alarm}}") : ""))
        }
    };
Events = {
    Lj: {},
    fwStart: function() {
        this.kb = (new Element("div")).addClassName("divFirework");
        $("body").ins(this.kb);
        for (var a = 0; 10 > a; a++) Events.om.bind(this).delay(3 * a);
        C.play("fw");
        Events.pm.bind(this).delay(30)
    },
    om: function() {
        var a = (new Element("div")).addClassName("fw" + P(1, 4));
        this.kb.ins(a);
        Ca(a, {
            x: P(0, $("body").getWidth()),
            y: P(0, $("body").getHeight())
        });
        a.addClassName.bind(a, "animated fadeOut").delay(2.7)
    },
    pm: function() {
        this.kb.remove()
    },
    fp: function() {
        return 1
    },
    audio: function(a) {
        C.play.curry(a).delay(1)
    },
    Co: function(a) {
        new p("events/throwsnow", [a])
    },
    hitSnow: function(a) {
        var b = (new Element("div")).addClassName("divSnowball").setStyle({
            top: P(0, $("body").getHeight() - 150) + "px",
            left: P(0, $("body").getWidth() - 150) + "px"
        });
        b.U(Onlines.get(a).ga, f);
        T(b, function() {
            var a = +this.getAttribute("data-i") + 2;
            this.setAttribute("data-i", a);
            a = 1 - a / 10;
            this.setStyle({
                opacity: a
            });
            0 >= a && (this.remove(), ma.hide())
        });
        $("body").ins.bind($("body"), b).delay(0.7);
        C.play.curry("snow").delay(0.1)
    },
    Bo: function(a, b) {
        new p("events/throwpaint",
            [a, b])
    },
    hitPaint: function(a, b) {
        var c = (new Element("div")).addClassName("event_divPaint col" + b).setStyle({
            top: P(0, $("body").getHeight() - 300) + "px",
            left: P(0, $("body").getWidth() - 300) + "px"
        });
        c.U(Onlines.get(a).ga, f);
        T(c, function() {
            var a = +this.getAttribute("data-i") + 2;
            this.setAttribute("data-i", a);
            a = 1 - a / 10;
            this.setStyle({
                opacity: a
            });
            0 >= a && (this.remove(), ma.hide())
        });
        $("body").ins.bind($("body"), c).delay(0.7);
        C.play.curry("paint").delay(0.1)
    },
    Kj: function(a) {
        var b = this.Lj["user" + a.id];
        if (b) {
            for (var c = 0,
                    d = ""; c < a.ga.length; c++) d += +b[c] ? '<span class="event_painted' + b[c] + '">' + a.ga[c] + "</span>" : a.ga[c];
            a.Ad.C(d);
            $$(".trainer.id" + a.id + " .label").invoke("update", d)
        }
    },
    paintedTrainer: function(a) {
        Events.Lj["user" + a.id] = a.painted;
        (a = Onlines.get(a.id)) && Events.Kj(a)
    },
    showHalloweenTrick: function() {
        if (!$$(".divHalloweenTrick").length) {
            var a = (new Element("div")).addClassName("divHalloweenTrick"),
                b = (new Element("div")).addClassName("divHalloweenTrickButtons");
            a.ins(b);
            var c = (new N("\u041f\u043e\u0441\u043b\u0430\u0442\u044c \u043d\u0430\u0433\u043b\u0435\u0446\u043e\u0432 \u043a\u0443\u0434\u0430 \u043f\u043e\u0434\u0430\u043b\u044c\u0448\u0435!",
                    "gray")).click(function() {
                    new p("events/halloween/trick", {
                        J: a.remove.bind(a)
                    })
                }),
                d = (new N("\u041e\u0442\u0434\u0430\u0442\u044c " + Aa(1666) + " \u0438 \u0443\u0439\u0442\u0438.", "gray")).click(function() {
                    new p("events/halloween/treat", {
                        J: a.remove.bind(a)
                    })
                });
            b.ins("\u042d\u0439 \u0442\u044b! \u0418\u0434\u0438 \u0441\u044e\u0434\u0430!<br>\u041a\u043e\u0448\u0435\u043b\u0451\u043a \u0438\u043b\u0438 \u0436\u0438\u0437\u043d\u044c?!").ins(c).ins(d);
            $("body").ins(a);
            C.play("hwtrick" + P(1, 5))
        }
    },
    showHalloweenCandy: function(a) {
        var b =
            a[0],
            c = a[1],
            d = a[2];
        a = (new Element("div")).addClassName("divHalloweenCandy grayscale").setStyle({
            top: P(0, $("body").getHeight() - 200) + "px",
            left: P(0, $("body").getWidth() - 200) + "px",
            opacity: 0.07
        });
        var e = new Element("img", {
            src: "//" + CONF_DOMAIN_IMG + "/pub/event/hw2017/" + b + ".png"
        });
        a.remove.bind(a).delay(4);
        T(a.ins(e), function() {
            var a = +this.getAttribute("data-i") + 2;
            this.setAttribute("data-i", a);
            this.setStyle({
                opacity: a / 10
            });
            4 <= a && this.removeClassName("grayscale");
            12 <= a && (c ? (this.remove(), new p("events/halloween17",
                [d]), C.play("chinkwin")) : (e.src = "//" + CONF_DOMAIN_IMG + "/pub/event/hw2017/ghost.png", e.addClassName("animationfloat"), C.play("hwtrick5")))
        });
        $("body").ins(a)
    },
    showExplosion: function() {
        var a = (new Element("div")).addClassName("divExplosion");
        $("body").ins(a);
        C.play("explosion");
        (function() {
            a.hide();
            u.start("\u0412\u044b \u043d\u0438\u0447\u0435\u0433\u043e \u043d\u0435 \u0441\u043b\u044b\u0448\u0438\u0442\u0435 \u0438 \u043d\u0430\u0445\u043e\u0434\u0438\u0442\u0435\u0441\u044c \u043d\u0430 \u0433\u0440\u0430\u043d\u0438 \u043f\u043e\u0442\u0435\u0440\u0438 \u0441\u043e\u0437\u043d\u0430\u043d\u0438\u044f...", {
                duration: 10
            })
        }).delay(0.9)
    },
    showMutagen: function() {
        var a = (new Element("img", {
            src: "//img.league17.ru/pub/mnst/shine/hires/150.png"
        })).addClassName("imgMutagen animationfloat");
        $("body").ins(a)
    },
    hideMutagen: function() {
        var a = $$(".imgMutagen");
        a && (a = a[0]) && a.remove.bind(a).delay(1)
    }
};
Exp = {
    ce: function() {
        window.evalsaver = evalsaver = 0;
        window.Exp = {
            Audio_horn: C.Fm.bind(C),
            Audio_load: C.load.bind(C),
            Audio_play: C.play.bind(C),
            Audio_stop: C.stop.bind(C),
            Ava_haircutDialog: Ava.vm.bind(Ava),
            Ava_tatooDialog: Ava.zo.bind(Ava),
            Game_reload: Game.reload.bind(Game),
            Game_retrainer: Game.Pn.bind(Game),
            Game_throwout: Game.Do.bind(Game),
            Chat_addArea: B.Eh.bind(B),
            Chat_addPost: B.Zk.bind(B),
            Chat_delPost: B.Ul.bind(B),
            Moder_addAbuse: Moder.Xk.bind(Moder),
            Onlines_join: Onlines.join.bind(Onlines),
            Onlines_upd: Onlines.C.bind(Onlines),
            Onlines_leave: Onlines.od.bind(Onlines),
            Auc_load: Auc.load.bind(Auc),
            Trade_upd: z.C.bind(z),
            Fight_upd: y.C.bind(y),
            Loc_load: I.load.bind(I),
            Npc_trade: J.Ek.bind(J),
            Npc_tutor: J.Ff.bind(J),
            Gym_load: Gym.load.bind(Gym),
            Compete_show: Compete.show.bind(Compete),
            Compete_upd: Compete.C.bind(Compete),
            Froup_end: Fb.end.bind(Fb),
            Froup_joined: Fb.Zm.bind(Fb),
            Eventer_gettings: K.tm.bind(K),
            Eventer_informerAlerten: K.Km.bind(K),
            Eventer_informerSys: K.Tm.bind(K),
            Eventer_informerRadio: K.Rm.bind(K),
            Eventer_informerStream: K.Sm.bind(K),
            Eventer_informerGift: K.Om.bind(K),
            Eventer_informerInvite: K.Pm.bind(K),
            Eventer_informerBadge: K.Lm.bind(K),
            Eventer_informerClan: K.Mm.bind(K),
            Eventer_informerAchive: K.Jm.bind(K),
            Eventer_informerQuest: K.Qm.bind(K),
            Eventer_informerEvent: K.Nm.bind(K),
            edu: s.Ke.bind(s),
            Dex_poke: A.R.bind(A),
            Profile_updEmblem: E.Mk.bind(E),
            Profile_updConds: E.Lk.bind(E),
            Dialog_alert: t.alert.bind(t),
            Events: Events,
            Waiter_start: u.start.bind(u)
        }
    }
};
var zb = Class.create(V, {
        initialize: function($super, b, c, d) {
            $super(d);
            d = b.userID;
            var e = Ia(b.dat);
            b = b.txt;
            this.B = (new Element("div")).addClassName("divFact" + (c ? " continue" : ""));
            this.Le = new Gb(d);
            this.Yd = (new Element("div")).addClassName("divTxt");
            this.hg = (new Element("div")).addClassName("divDat txtgray").C(e);
            b = Parser.xa(b);
            b = Parser.Il(b);
            b = Parser.ea(b);
            b = Parser.eb(b);
            b = Parser.wa(b);
            b = Parser.Wk(b);
            this.Yd.C(b);
            this.B.ins(this.Le).ins(this.Yd).ins(this.hg);
            return this
        }
    }),
    Cb = 0,
    Db = 1,
    Eb = 5,
    rb = 6,
    tb = 7,
    y = Class.create({
        initialize: function() {
            this.id =
                0;
            this.Xa = -1;
            this.yk = 0;
            this.bf = h;
            this.wj = new Hb;
            this.ke = new ab(0, FIGHT_TIMEOUT, {
                U: "none"
            });
            this.ed = (new Element("div")).addClassName("timeout");
            this.Al = (new N("{{fight_menu_wait}}", "gray")).click(this.timeout.bind(this, "wait"));
            this.yl = (new N("{{fight_menu_attack}}", "gray")).click(this.timeout.bind(this, "attack"));
            this.zl = (new N("{{fight_menu_leave}}", "gray")).click(this.timeout.bind(this, "run"));
            this.ed.ins(this.Al).ins(this.yl).ins(this.zl);
            $("divFightTimer").ins(this.ke).ins(this.ed);
            this.Wb = $("divFightButtons");
            this.Wb.C();
            this.Od = (new N("{{fight_menu_surrend}}", "red")).click(this.wo.bind(this)).hide();
            this.Oc = (new N("{{fight_menu_help}}")).click(this.$k.bind(this)).hide();
            this.Pc = (new N("{{fight_menu_draw}}")).click(this.gm.bind(this)).hide();
            this.Ld = (new N("{{fight_menu_team}}")).click(Fb.list).hide();
            this.ib = (new N("{{fight_menu_close}}")).click(this.close.bind(this)).hide();
            this.Wb.ins(this.Od).ins(this.Oc).ins(this.Pc).ins(this.Ld).ins(this.ib);
            this.ij = new Element("img");
            this.Bd = new Element("input");
            this.Bd.on("keypress", function(a) {
                13 === a.keyCode && !this.Bd.value.blank() && this.Kk()
            }.bind(this));
            this.Cl = (new N("{{button_unblock}}")).click(this.Kk.bind(this));
            $("divFightCaptcha").ins(this.ij).ins(this.Bd).ins(this.Cl).hide();
            $("divFightOptions").addClassName("txtgray2")
        },
        start: function(a, b) {
            new p("fightstart", [a, b], {
                J: function(a) {
                    y.C(a)
                }
            })
        },
        reload: function() {
            new p("fight/reload", {
                J: function(a) {
                    y.C(a)
                }
            })
        },
        C: function(a) {
            a ? (this.id = a.id, this.options = a.options, this.Xa = a.raund, this.Fk = a.turn, this.Hf =
                a.weather, this.ei = a.captcha, this.nd = a.is_wild, this.bb = a.is_ended, this.Zb = a.froup, this.Eo = a.timeUpdate, this.mp = a.timeStart, this.lp = a.spectloc, this.qk = a.spectators, this.cb = a.side && a.side.I.trainer.id != l.id && a.side.H.trainer.id != l.id, a.side && (this.O = {
                    V: {
                        n: a.side.I.n,
                        xa: new Y(a.side.I.trainer),
                        yf: new cb(a.side.I.slots),
                        R: a.side.I.poke && 0 < a.side.I.poke.hp ? new Ib(a.side.I.poke) : new Jb(this.de() ? F.show.curry("{{char_select}}", "alive", y.Ic.bind(y)) : 0)
                    },
                    H: {
                        n: a.side.H.n,
                        xa: !this.nd ? new Y(a.side.H.trainer) : (new Element("div")).addClassName("trainer").C(a.side.H.poke.wild.npc ? a.side.H.poke.wild.npc.name : "{{char_wild}}"),
                        yf: !this.nd ? new cb(a.side.H.slots) : "",
                        R: a.side.H.poke && 0 < a.side.H.poke.hp ? new Ib(a.side.H.poke) : new Jb
                    }
                })) : (l.Bb = k, this.close());
            y.wj.C(this.id, a.log);
            l.Bb = a && this.id && !this.bb && !this.cb;
            this.cb || (r.Ia(), K.Uo("fight"));
            this.id ? ($("divFightLog").show(), $("divFightCaptcha").hide(), $("divFightTimer").show(), this.cb ? (this.Oc.hide(), this.Od.hide(), this.Pc.hide(), this.O.V.xa.id < this.O.H.xa.id &&
                    (a = this.O.V, this.O.V = this.O.H, this.O.H = a), $("divVisioFight").addClassName("spectate")) : (this.Zb && !this.bb && (l.cc = f), $("divVisioFight").removeClassName("spectate"), this.ei && this.nk()), na.df(), $("divFightI").C(this.O.V.R), this.cb && ($("divFightI").ins(this.O.V.xa).ins(this.O.V.yf), this.O.V.R.zb ? (this.O.V.R.hide(), this.O.V.xa.tf(), this.O.V.xa.Vb.addClassName("flipH"), this.O.V.yf.addClassName("antioverlaybug")) : this.O.V.R.N.ba.select("img")[0].addClassName("flipH")), $("divFightH").C(this.O.H.R).ins(this.O.H.xa).ins(this.O.H.yf),
                this.O.H.R.zb && !this.nd && (this.O.H.R.hide(), this.O.H.xa.tf()), $("divFightWeather").C(), this.Hf && $("divFightWeather").ins((new Element("div")).addClassName("iconweather w" + this.Hf).U("{{weather." + this.Hf + "}}")), $("divFightOptions").C(), this.nd && (this.O.H.R.Tk && this.O.H.R.Tk.nocatch) && $("divFightOptions").ins((new Element("div")).addClassName("nocatch").U("{{fight_opt_nocatch}}")), this.Zb && $("divFightOptions").ins((new Element("div")).addClassName("froup").U("{{fight_opt_froup}}")), this.options.agro &&
                $("divFightOptions").ins((new Element("div")).addClassName("agro").U("{{fight_opt_agro}}")), this.options.dominat && $("divFightOptions").ins((new Element("div")).addClassName("dominat").U("{{fight_opt_dominat}}")), this.options.occupancy && $("divFightOptions").ins((new Element("div")).addClassName("occupancy").U("{{fight_opt_occupancy}}")), this.options.pokescap && $("divFightOptions").ins((new Element("div")).addClassName("pokescap").update(this.options.pokescap).U("{{fight_opt_pokescap}}")), this.options.noitem &&
                $("divFightOptions").ins((new Element("div")).addClassName("noitem").U("{{fight_opt_noitem}}")), this.options.gym && $("divFightOptions").ins((new Element("div")).addClassName("gym").U("{{fight_opt_gym}}")), this.options.honor && $("divFightOptions").ins((new Element("div")).addClassName("honor").U("{{fight_opt_honor:" + (this.O.V.xa.id == this.options.honor ? this.O.V.xa.ga : this.O.H.xa.ga) + "}}")), this.options.compete && $("divFightOptions").ins((new Element("div")).addClassName("compete").U("{{fight_opt_compete:" +
                    this.options.compete + "}}")), this.qk && $("divFightOptions").ins((new Element("div")).addClassName("spectators").U("{{fight_opt_viewers}}").C(this.qk)), this.Oc.disable(this.nd || this.Zb || this.options.gym), this.Zb ? this.Ld.show() : this.Ld.hide(), this.options.gym && l.id == this.options.gym ? this.Pc.show() : this.Pc.hide(), this.O.V.R.N && (!this.de() || this.bb ? this.O.V.R.N.tb.hide() : this.O.V.R.N.tb.show()), $("divFightRaund").removeClassName("ended").removeClassName("win").removeClassName("lose"), $("divFightAction").removeClassName("rednote"),
                this.bb ? ($("divFightRaund").addClassName("ended"), this.Oc.hide(), this.Od.hide(), this.Pc.hide(), 3 == this.bb ? $("divFightAction").C("{{fight_draw}}") : this.cb ? $("divFightAction").C("{{fight_result}}").ins(this.bb == this.O.V.n ? new Y(this.O.V.xa) : new Y(this.O.H.xa)) : ($("divFightRaund").addClassName(this.bb == this.O.V.n ? "win" : "lose"), $("divFightAction").C(this.bb == this.O.V.n ? "{{fight_you_win}}" : "{{fight_you_lose}}"))) : this.cb ? $("divFightAction").C("{{fight_viewing}}") : (this.Oc.show(), this.Od.show(), this.de() ?
                    ($("divFightAction").C("{{fight_your_move}}"), $("divFightTimer").addClassName("attention")) : ($("divFightAction").C("{{fight_foe_move}}"), $("divFightTimer").removeClassName("attention")), this.qi(FIGHT_TIMEOUT - this.Eo)), this.cb || this.bb ? this.ib.show() : this.ib.hide(), this.history && this.history.id == this.id && (!this.O.V.R.zb && (this.O.V.R.id == this.history.V.R.id && this.O.V.R.ia != this.history.V.R.ia) && this.O.V.R.Vi(this.history.V.R), !this.O.H.R.zb && (this.O.H.R.id == this.history.H.R.id && this.O.H.R.ia != this.history.H.R.ia) &&
                    this.O.H.R.Vi(this.history.H.R), !this.O.V.R.zb && this.O.V.R.id != this.history.V.R.id && this.O.V.R.Ic(this.history.V.R && !this.history.V.R.zb ? this.history.V.R : 0), !this.O.H.R.zb && this.O.H.R.id != this.history.H.R.id && this.O.H.R.Ic(this.history.H.R && !this.history.H.R.zb ? this.history.H.R : 0)), this.history = {
                    id: this.id,
                    V: {
                        R: this.O.V.R
                    },
                    H: {
                        R: this.O.H.R
                    }
                }, s.da.Xi && (s.da.Db = this.O.V.R.Db, s.Ke())) : (this.Zb && (l.cc = f, this.Od.hide(), this.Oc.hide(), this.Pc.hide(), this.Ld.show(), this.ib.show(), $("divFightAction").C("{{fight_froup}}"),
                $("divFightTimer").hide(), $("divFightLog").C().show(), $("divFightCaptcha").hide(), $("divFightI").C(), $("divFightH").C()), na.df())
        },
        close: function() {
            this.cb && Vight.od();
            if (l.Bb) return new L(M, "warning", "{{fight_cant_leave}}");
            l.cc ? y.C({
                froup: this.Zb
            }) : na.nf("loc");
            na.df()
        },
        de: function() {
            return this.bb || this.cb ? k : !this.Fk || this.Fk == l.id
        },
        Hn: function() {
            30 >= P(1, 100) && this.O.V.R.Pa();
            30 >= P(1, 100) && this.O.H.R.Pa()
        },
        qi: function(a) {
            this.ke.show();
            this.ed.hide();
            clearTimeout(this.yk);
            this.pi(a)
        },
        pi: function(a) {
            if (!this.bb) {
                this.ke.set(a);
                if (0 >= a) return this.de() ? $("divFightAction").C("{{fight_hurry}}").addClassName("rednote") : (this.ke.hide(), this.ed.show()), k;
                this.Hn();
                this.yk = this.pi.bind(this, a - 1).delay(1);
                return f
            }
        },
        wo: function() {
            O("{{fight_surrend_confirm}}") && this.Oa("fight/surrend")
        },
        $k: function() {
            this.Oa("fight/askhelp")
        },
        gm: function() {
            O("{{fight_cancel_confirm}}") && this.Oa("fight/draw")
        },
        timeout: function(a) {
            switch (a) {
                case "wait":
                    this.ke.show();
                    this.ed.hide();
                    this.qi(FIGHT_TIMEOUT);
                    break;
                case "attack":
                case "run":
                    this.Oa("fight/timeout/" +
                        a)
            }
        },
        Gd: function(a, b) {
            if (a) {
                if (y.O.V.R.status[37]) return new L(1, "warning", "{{fight_switch_needed}}");
                switch (a.id) {
                    case 226:
                    case 270:
                    case 358:
                    case 481:
                    case 482:
                    case 485:
                    case 417:
                    case 511:
                    case 560:
                    case 606:
                        F.show("{{char_select}}", "alive", function(a) {
                            y.Oa("fight/attack", [b, a])
                        });
                        break;
                    default:
                        this.Oa("fight/attack", [b])
                }
            }
        },
        Ic: function(a) {
            D.close();
            this.Oa("fight/switche", [a])
        },
        zh: function(a) {
            this.bf = a;
            D.close();
            if (y.O.V.R.status[37]) return new L(1, "warning", "{{fight_switch_needed}}");
            if (y.O.V.R.status[55] &&
                y.O.V.R.status[55] >= FIGHT_INV_CAP || y.O.V.R.status[103]) return new L(1, "warning", "{{fight_invent_cap}}");
            this.Oa("fight/useitem", [a.id])
        },
        nk: function() {
            this.ij.src = this.ei + "?" + Math.random();
            this.Bd.value = "";
            $("divFightLog").hide();
            $("divFightCaptcha").show();
            B.F.Qa !== document.activeElement && this.Bd.focus()
        },
        Kk: function() {
            new p("fight/uncaptcha", {
                parameters: {
                    captcha: this.Bd.value
                },
                J: function(a) {
                    "good" == a && ($("divFightLog").show(), $("divFightCaptcha").hide());
                    a.id && this.C(a)
                }.bind(this),
                onError: function() {
                    this.nk()
                }.bind(this)
            })
        },
        Oa: function(a, b, c) {
            b || (b = []);
            c || (c = {});
            c.parameters || (c.parameters = {});
            c.parameters.raund = this.Xa;
            this.ed.hide();
            this.Wb.hide();
            this.O.V.R.gj();
            c.J = function(a, b) {
                this.C(b);
                a && a()
            }.bind(this, c.J);
            c.onComplete = function(a) {
                this.Wb.show();
                this.O.V.R.lk();
                a && a()
            }.bind(this, c.onComplete);
            new p(a, b, c)
        }
    }),
    Hb = Class.create({
        initialize: function() {
            this.id = 0;
            this.ec = [];
            this.parser = {};
            this.pc = $("divFightLog")
        },
        C: function(a, b) {
            this.id != a && this.clear();
            this.id = a;
            if (b) {
                var c = Wa(this.pc, 100);
                this.pc.select(".divLogRaund").invoke("removeClassName",
                    "new");
                b.reverse();
                for (var d = 0; d < b.size(); d++) {
                    var e = b[d];
                    e.Xa = parseInt(e[0]);
                    e.la = this.parse(e[1]);
                    this.ec[e.Xa] ? this.ec[e.Xa].Yd.C(e.la) : (this.ec[e.Xa] = {
                        Ai: (new Element("div")).addClassName("divLogCont"),
                        fm: (new Element("div")).addClassName("divLogRaund new").C(e.Xa),
                        Yd: (new Element("div")).addClassName("divLogTxt").C(e.la)
                    }, this.ec[e.Xa].Ai.C(this.ec[e.Xa].fm).ins(this.ec[e.Xa].Yd), this.ins(this.ec[e.Xa].Ai))
                }
                Parser.go();
                c && this.Dc()
            }
        },
        ins: function(a) {
            S && this.pc.firstDescendant() ? this.pc.firstDescendant().ins({
                    before: a
                }) :
                this.pc.ins(a)
        },
        parse: function(a) {
            a && (a = a.replace(/\[HP(-*\d+)\]/ig, function(a, c) {
                return Ba(parseInt(c), " HP")
            }), a = Parser.ea(a, {
                yn: f
            }), a = Parser.eb(a), a = Parser.wa(a), a = a.replace("[U1]", "<b>" + this.parser.names[0] + "</b>"), a = a.replace("[U2]", "<b>" + this.parser.names[1] + "</b>"), a = a.replace(/\[SPLIT\]/g, '<div class = "divider"></div>'));
            return a
        },
        clear: function() {
            this.ec = [];
            this.parser = {};
            this.pc.C();
            y.O && (this.parser.names = [y.O.V.xa.ga, !y.nd ? y.O.H.xa.ga : y.O.H.xa], 2 == y.O.V.n && this.parser.names.reverse())
        },
        Dc: function() {
            S || (this.pc.scrollTop = 65E3)
        }
    }),
    Fb = {
        list: function() {
            r.show(y.Ld, "{{fight_froup_title}}", f, "white");
            if (!y.Zb) return k;
            new p("froup/list", [y.Zb], {
                G: r,
                J: function(a) {
                    var b = (new Element("div")).addClassName("divFroup"),
                        c = [(new Element("div")).addClassName("divFroupListFights"), (new Element("div")).addClassName("divFroupListSide1"), (new Element("div")).addClassName("divFroupListSide2")],
                        d = {},
                        e = a.side[1].users.concat(a.side[2].users),
                        m = 0;
                    if (l.cc)
                        for (var g = 0; g < e.length; g++) e[g].id == l.id && (m =
                            e[g].side);
                    for (g = 0; g < e.length; g++) {
                        var v = c[e[g].side],
                            G = new cb(e[g].slots);
                        if (l.cc && !l.Bb && m != e[g].side && G.Kf) {
                            var U = (new N("", "ctrl nobg attack")).click(Fb.ro.curry(e[g].id));
                            v.ins(U)
                        }
                        v.ins((new Y(e[g])).addClassName(e[g].leader ? "leader" : "").addClassName(G.Kf ? "" : "defeat")).ins(G);
                        d[e[g].id] = e[g]
                    }
                    if (a.fights)
                        for (g = 0; g < a.fights.length; g++) v = Vight.cj(a.fights[g][0], d[a.fights[g][1]], d[a.fights[g][2]]), c[0].ins(v);
                    else c[0].C("{{fight_froup_no_battles}}");
                    if (!l.Ng() && !a.win)
                        for (g = 1; 2 >= g; g++) a.side[g].users.length >=
                            FROUP_SIZE || (U = (new N("{{fight_froup_join}}", "gray")).click(Fb.join.curry(a.side[g].leader)), c[g].ins(U));
                    a.win && (c[a.win].ins({
                        top: (new Element("span")).addClassName("win").C("{{fight_win}}")
                    }), c[1 == a.win ? 2 : 1].ins({
                        top: (new Element("span")).addClassName("lose").C("{{fight_lose}}")
                    }));
                    b.ins(c[1 == m || !m ? 1 : 2]).ins(c[0]).ins(c[1 == m || !m ? 2 : 1]);
                    r.content(b)
                }
            })
        },
        join: function(a) {
            new p("inviter/send/froup", [a])
        },
        ro: function(a) {
            new p("fightstart", [4, a], {
                J: function(a) {
                    y.C(a)
                }
            })
        },
        Zm: function(a) {
            new L(1, "success",
                "{{fight_froup_you_joined}}");
            y.C({
                froup: a
            })
        },
        end: function(a) {
            l.cc = k;
            new L(1, "info", "{{fight_froup_end:" + a + "}}");
            y.ib && y.ib.show()
        }
    };
Gym = {
    load: function(a) {
        J.close();
        t.show("{{gym_title}}");
        new p("gym/" + (a ? "leader/" : "") + "load", {
            G: t,
            J: function(b) {
                var c = k;
                t.title(b.title);
                for (var d = (new Element("div")).addClassName("divGym"), e = 0; e < b.leaders.size(); e++) {
                    var m = (new ga(b.leaders[e].trainer)).Ma("{{gym_leader}}");
                    d.ins(m);
                    var g = (new Element("div")).addClassName("divRule");
                    b.leaders[e].rule ? g.C(Parser.Qb(b.leaders[e].rule)) : g.C("{{gym_rules}}");
                    m.id == l.id && (T(g.addClassName("clickable"), Gym.ak.curry(g, k)), g.setAttribute("data-txt", b.leaders[e].rule));
                    d.ins(g)
                }
                var e = (new Element("div")).addClassName("divRestricts txtgray2"),
                    v;
                for (v in b.restricts) "level" == v && e.ins("<br>{{gym_rule_level:" + b.restricts[v] + "}}"), "slots" == v && e.ins("<br>{{gym_rule_slots:" + b.restricts[v] + "}}"), "norepeatsp" == v && e.ins("<br>{{gym_rule_norepeatsp}}"), "norepeattyp" == v && e.ins("<br>{{gym_rule_norepeattyp}}");
                d.ins(e);
                v = (new Element("div")).addClassName("divOrders tabbox");
                v.ins('<span class="title">{{gym_order_title}}</span>');
                if (b.orders)
                    for (e = 0; e < b.orders.size(); e++) m = new Y(b.orders[e]),
                        a && (g = (new N("", "ctrl nobg attack")).click(m.call.bind(m, Eb)), v.ins(g), g.Ha("wait" == b.orders[e].status), m.T("---"), m.T("{{gym_order_disapply}}", Gym.Vl.curry(m.id)), "wait" !== b.orders[e].status && m.T("{{gym_order_replay}}", Gym.Ln.curry(m.id))), m.id == l.id && (c = b.orders[e].status), m.addClassName(b.orders[e].status), v.ins(m).ins("<br>");
                else v.ins(Q("empty"));
                d.ins(v);
                a ? (d.ins((new N("{{gym_telep}}", "gray")).click(Gym.sh)), d.ins((new N("{{gym_notify}}", "gray")).click(Gym.alert))) : (c || d.ins((new N("{{gym_order_apply}}",
                    "gray")).click(Gym.vd)), "wait" == c && d.ins((new N("{{gym_order_disapply}}", "gray")).click(Gym.vd)));
                t.ins(d)
            }
        })
    },
    vd: function() {
        new p("gym/order", {
            G: t,
            J: function(a) {
                new L(M, "success", a ? "{{gym_order_apply_ok}}" : "{{gym_order_disapply_ok}}")
            },
            onComplete: Gym.load.curry(k)
        })
    },
    ak: function(a, b) {
        if (b) new p("gym/leader/rule", {
            parameters: {
                txt: $F(a)
            },
            G: t,
            onComplete: Gym.load.curry(f)
        });
        else {
            var c = (new Element("textarea")).addClassName("txtRule").C(a.getAttribute("data-txt")),
                d = (new N("{{button_save}}", "gray")).click(Gym.ak.curry(c,
                    f));
            a.replace(c);
            c.ins({
                after: d
            })
        }
    },
    sh: function() {
        new L(0, "info", "{{teleporting}}...");
        new p("gym/leader/telep", {
            J: function() {
                I.load()
            }
        })
    },
    alert: function() {
        new p("gym/leader/alert", {
            J: function() {
                new L(0, "info", "{{gym_notify_ok}}")
            }
        })
    },
    Vl: function(a) {
        new p("gym/leader/delorder", [a], {
            G: t,
            onComplete: Gym.load.curry(f)
        })
    },
    Lh: function(a, b) {
        new p("gym/leader/badge", [a, b ? 1 : 0])
    },
    Ln: function(a) {
        new p("gym/leader/replay", [a], {
            G: t,
            onComplete: Gym.load.curry(f)
        })
    }
};
var s = Class.create({
        Ui: 0,
        da: {},
        initialize: aa(),
        show: function(a, b, c) {
            S || (b = "{{help_" + b + "}}", a.addClassName("onhelp"), r.Sa(a, b), c && T(a, function() {
                s.hide(this);
                c()
            }))
        },
        hide: function(a) {
            a.removeClassName("onhelp");
            r.Jk(a);
            r.hide()
        },
        Ke: function(a) {
            a || (a = s.Ui);
            s.Ui = a;
            switch (a) {
                case -1:
                    789 === l.Lb && s.show($("btnNpc17"), "1");
                    break;
                case 1:
                    789 === l.Lb && s.show($("btnGo6"), "2");
                    6 === l.Lb && s.show($("btnNpc17"), "3");
                    break;
                case 2:
                    6 === l.Lb && s.show($("btnNpc136"), "4");
                    break;
                case 3:
                    s.da.eh != a && (s.da = {
                        eh: a
                    });
                    6 === l.Lb &&
                        (14 == s.da.Db ? s.show($("btnNpc136"), "5") : s.show($("btnGo563"), "6"));
                    563 === l.Lb && (s.da.Xi = f, l.Bb ? (s.hide(B.F.Qc), s.da.Df || B.tk(0), 0 == y.Xa && s.show($("divFightI").select(".divMoveTitle.category3")[0].ancestors()[1], "14"), 1 == y.Xa && s.show($("divFightI").select(".divMoveTitle.category1")[0].ancestors()[1], "15"), 2 == y.Xa && s.show($("divFightI").select(".divMoveTitle.category1")[0].ancestors()[1], "16")) : (y.bb && (s.da.hb || (s.da.hb = 0), s.da.hb++), s.show(B.F.Qc, "7"), s.hide($$(".btnLocHeal")[0]), (2 == s.da.hb || 3 == s.da.hb) &&
                        s.show($$(".btnLocHeal")[0], "8"), (4 == s.da.hb || 5 == s.da.hb) && s.show(D.Y("diary").va, "9", aa()), 7 == s.da.Db && !s.da.Df && (s.da.hc = f, s.show(D.Y("pokes").va, "10"), D.Y("pokes").Va() && (s.hide(D.Y("pokes").va), s.show($$(".panelpokes .pokemonBoxCard .image")[0], "11", function() {
                            s.show($$(".panelpokes .pokemonBoxCard .btnLoadTeach")[0], "12", function() {
                                s.da.hc = k;
                                s.da.Df = f
                            })
                        }))), 14 == s.da.Db && s.show($("btnGo6"), "13")));
                    break;
                case 4:
                case 6:
                    s.show(D.Y("diary").va, "17", aa());
                    break;
                case 5:
                    s.da.eh != a && (s.da = {
                        eh: a
                    });
                    if (563 ===
                        l.Lb)
                        if (s.da.Xi = f, s.hide(D.Y("items").va), l.Bb)(!s.da.hb || 2 >= s.da.hb) && s.show(D.Y("items").va, "21", aa());
                        else if (y.bb && (s.da.hb || (s.da.hb = 0), s.da.hb++), (1 == s.da.hb || 2 == s.da.hb) && !s.da.Df) s.da.hc = f, D.Y("pokes").Va() || s.show(D.Y("pokes").va, "18", aa()), (a = $$(".panelpokes .pokemonBoxCard .ball")[1]) && s.show(a, "19", function() {
                        s.da.Df = f;
                        s.show($$(".btnLocFarm")[0], "20", aa())
                    });
                    break;
                case 7:
                    563 === l.Lb ? s.show(D.Y("items").va, "22") : s.hide(D.Y("items").va)
            }
        }
    }),
    Z = Class.create(V, {
        initialize: function($super, b,
            c, d, e) {
            $super();
            this.jd(b);
            this.B = new Element("div", {
                "class": "item" + (e ? "" : " clickable")
            });
            this.ab = new Element("img", {
                src: this.Pe()
            });
            this.am = new Element("div", {
                "class": "infos"
            });
            this.We = new Element("div", {
                "class": "extra"
            });
            this.Ve = new Element("div", {
                "class": "amount"
            });
            this.B.ins(this.ab).ins(this.am.ins(this.We).ins(this.Ve));
            this.dk(this.Ja);
            this.Bk && this.We.C(this.Bk);
            450 == this.ja && this.We.C("#" + this.memo);
            this.Je && this.Je.Ce <= Math.ceil(this.Je.max / 4) && (this.Wl = new Element("div", {
                "class": "dura " +
                    (0 == this.Je.Ce ? "broken" : " weak")
            }), this.B.ins(this.Wl));
            this.ug && this.We.C('<span class="' + ("r" == this.ug.status ? "greennumber" : "rednumber") + '">' + this.ug.days + "</span>");
            c ? (this.pa = (new Element("div", {
                "class": "title"
            })).C(this.Ab()), this.B.addClassName("titled").ins(this.pa)) : !d && !S && r.Sa(this.B, this.Ab.bind(this));
            e || T(this.B, this.onclick.bind(this));
            return this
        },
        jd: function(a) {
            this.id = +(a.id || a[0]);
            this.ja = +(a.ja || a[1]);
            this.Ja = +(a.Ja || a[2]);
            this.memo = a.memo || a[3];
            this.title = a.title || a[4];
            a[5] && (this.La =
                a[5]);
            a[6] && (this.$e = parseInt(a[6]));
            a[7] && (this.Ug = parseInt(a[7]));
            a[8] && (this.qe = a[8]);
            if (this.title) {
                if (a = this.title.match(Z.An)) this.Je = {
                    Ce: a[1],
                    max: a[2]
                };
                if (a = this.title.match(Z.Cn)) this.Bk = a[1];
                if (a = this.title.match(Z.Bn)) this.ug = {
                    status: a[1],
                    days: a[2]
                }
            }
        },
        toHTML: function() {
            return '<img src = "' + this.Pe() + '" class = "item noclick" onmouseover = "Hint.show(this, \'' + this.Ab() + '\')" onmouseout = "Hint.hide()">'
        },
        size: function(a) {
            this.addClassName("size-" + a);
            return this
        },
        dk: function(a) {
            this.Ja = a;
            1 < this.Ja ? this.Ve.C(za(this.Ja)) : this.Ve.C()
        },
        Im: function(a) {
            this.dk(this.Ja + a);
            0 >= this.Ja && this.remove()
        },
        Ab: function() {
            return this.title + (1 < this.Ja ? " <b>x" + za(this.Ja) + "</b>" : "")
        },
        Pe: function(a) {
            a || (a = "tiny");
            var b = "items/" + a + "/" + this.ja + ".png";
            1 == this.ja && 1E6 <= this.Ja && (b = "items/" + a + "/1_.png");
            10 == this.ja && (b = "vita/pill/" + a + "/" + this.memo + ".png");
            11 == this.ja && (b = "vita/jar/" + a + "/" + this.memo + ".png");
            60 == this.ja && this.memo && (b = "balls/" + a + "/" + this.memo + ".png");
            75 == this.ja && (b = "items/" + a + "/" + (200 > this.memo ?
                "tm" : "hm") + ".png");
            144 == this.ja && this.memo && (b = "eggs/" + a + "/" + ya(this.memo) + ".png");
            200 == this.ja && (b = "badges/" + a + "/" + this.memo + ".png");
            250 == this.ja && (b = "cards/" + a + "/" + this.memo + ".png");
            335 == this.ja && (b = "coord/" + a + "/" + this.memo + ".png");
            345 == this.ja && (b = "paints2/" + a + "/" + this.memo + ".png");
            360 == this.ja && (b = "toys/" + a + "/" + ya(this.memo) + ".png");
            385 == this.ja && (b = "candy/" + a + "/" + this.memo + ".png");
            398 == this.ja && (b = "quests/assets/mirror/" + a + "/" + this.memo + ".png");
            502 == this.ja && this.memo && (b = "gifts/" + a + "/" + this.memo +
                ".png");
            623 == this.ja && (b = "lcbadges/" + a + "/" + this.memo + ".png");
            624 == this.ja && (b = "statuette/" + a + "/" + this.memo + ".png");
            700 == this.ja && (b = "candles/" + a + "/" + this.memo + ".png");
            706 == this.ja && (b = "paints/" + a + "/" + this.memo + ".png");
            713 == this.ja && (b = "fair/" + a + "/" + this.memo + ".png");
            return 501 == this.ja && Object.isArray(this.memo) && ("tiny" == a && (b = "cloth/" + a + "/" + this.memo[0] + this.memo[1] + this.memo[2] + ".png"), "big" == a) ? "//" + CONF_DOMAIN_IMG + "/dyn/dolls/" + this.memo[3] + ".png" : "//" + CONF_DOMAIN_IMG + "/pub/" + b
        },
        onclick: function() {
            r.visible() ||
                r.show(this.ab);
            r.title(this.Ab()).Sd().qa();
            this.Gb = h;
            var a = (new Element("div")).addClassName("divItemInfo"),
                b = (new Element("img", {
                    src: this.Pe("big")
                })).addClassName("imgItemBig" + (501 == this.ja ? " doll" : "")),
                c = (new Element("div")).addClassName("divItemDescr").C(this.La);
            a.ins(b).ins(c);
            r.content(a);
            Object.isFunction(this.click) && this.click()
        },
        wf: function(a, b, c) {
            this.Gb = Va(Ua((new Element("input", {
                value: !b && 1 <= this.Ja ? this.Ja : 1
            })).addClassName("amount"), "unsign int"), c);
            this.ti = (new Element("div")).addClassName("divItemAmountField").C(a).ins(this.Gb);
            r.ins(this.ti);
            this.Gb.select()
        },
        Oe: function() {
            return !this.Gb || !this.Gb.visible() ? 0 : $F(this.Gb)
        }
    });
Z.An = /\[(\d+)\/(\d+)\]/i;
Z.Cn = /((T|H)M \d+)/i;
Z.Bn = /\[\{\{i_egg_(r|s):(\d+)\}\}\]/i;
var ub = Class.create(Z, {
        initialize: function($super, b) {
            $super(b);
            this.N = {
                ba: this.B,
                Ci: (new Element("div")).addClassName("divCraftProcess"),
                Bi: (new Element("div")).addClassName("divCraftPokes")
            };
            this.ea = [];
            this.B = (new Element("div")).addClassName("divCraftItem").ins(this.N.ba).ins(this.N.Bi.ins(this.N.Ci));
            return this
        },
        re: function(a) {
            this.ea.push(a);
            this.N.Bi.ins(a)
        },
        ii: function() {
            for (var a = k, b = 0; b < this.ea.size(); b++) this.ea[b].paused || (a = f);
            this.N.Ci.qa("animated infinite pulsate", a)
        }
    }),
    Kb = Class.create(Z, {
        initialize: function($super, b) {
            $super(b, k, f);
            this.B.U(this.Ab(), f);
            return this
        },
        onclick: function() {
            y.zh(this);
            r.hide()
        }
    }),
    Lb = Class.create(Z, {
        initialize: function($super, b) {
            var c = b.txt ? b.txt : "";
            b.dat && (this.sa = b.dat);
            b.from && (this.from = new Y(b.from));
            b.price && (this.Wa = b.price);
            b.receiver && (this.Vj = b.receiver);
            $super([b.id, 502, 1, b.giftID, b.title, c]);
            return this
        },
        Ab: function($super) {
            return $super() + (this.Wa ? "<br>{{gift_price:" + this.Wa + "}}" : "")
        },
        onclick: function() {
            r.show(this.ab, this.Ab(), k, "white");
            var a =
                (new Element("div")).addClassName("divItemInfo gift"),
                b = (new Element("img", {
                    src: this.Pe("big")
                })).addClassName("imgItemBig"),
                c = (new Element("div")).addClassName("divItemDescr").C(this.La).ins("<br>"),
                d = (new Element("div")).addClassName("divItemFrom");
            this.sa && d.ins(this.sa);
            this.from ? d.ins(" {{gift_from}} ").ins(this.from) : this.id && d.ins(" {{gift_anon}}");
            a.ins(b).ins(c).ins(d);
            this.id && r.T("{{button_del}}", this.del.bind(this)).addClassName("red");
            this.Vj && (b = (new Element("div")).addClassName("divItemGiftPresent"),
                this.F = [], this.F.fi = new Element("input", {
                    type: "checkbox",
                    value: 1
                }), this.F.gi = new Element("input", {
                    type: "checkbox",
                    value: 1
                }), this.F.Ik = new Element("input", {
                    type: "text",
                    placeholder: n("{{gift_opt_comment}}")
                }), b.ins(this.F.Ik).ins("<br>"), b.ins(this.F.fi).ins("{{gift_opt_anon}}").ins("<br>"), b.ins(this.F.gi).ins("{{gift_opt_private}}"), a.ins(b), r.T("{{gift_send}}", this.present.bind(this)));
            r.content(a)
        },
        present: function() {
            new p("gift/present", [this.Vj.id, this.memo, this.F.fi.checked ? 1 : 0, this.F.gi.checked ?
                1 : 0
            ], {
                G: r,
                parameters: {
                    txt: this.F.Ik.value
                },
                J: function() {
                    new L(M, "success", "{{gift_send_ok}}");
                    t.close()
                },
                onComplete: function() {
                    r.Ia()
                }
            })
        },
        del: function() {
            O("{{gift_del_confirm}}") && new p("gift/del", [this.id], {
                G: r,
                J: function() {
                    D.Y("profile").ka()
                },
                onComplete: function() {
                    r.Ia()
                }
            })
        }
    });
Lb.ko = function(a) {
    t.show("{{gift_title:" + a.ga + "}}");
    new p("gift/shop", {
        J: function(b) {
            for (var c = (new Element("div")).addClassName("divGiftShopList"), d = 0; d < b.gifts.size(); d++) b.gifts[d].receiver = a, c.ins(new Lb(b.gifts[d]));
            t.ins(c).ins('<span class="spPearlBalance txtgray">{{pearl_balance:' + b.pearl + "}}</span>")
        }
    })
};
var sa = Class.create({
        initialize: function() {
            this.$e = 0;
            this.nj = k;
            this.ig = (new Element("div")).addClassName("divItemCats");
            this.ha = (new Element("div")).addClassName("divItemList");
            this.Uh = (new N("{{pearljam_title}}", "gray btnPearljam")).click(Mb);
            this.gd = (new Element("div")).addClassName("txtgray divWeight");
            this.Ga = [0, 1, 2, 3, 4, 5, 12, 7, 8, 9, 10, 11, 6, 15];
            for (var a = 0; a < this.Ga.length; a++) 15 != this.Ga[a] && this.ig.ins((new N("", "invcategory nobg cat" + this.Ga[a], {
                ta: f,
                U: "{{invent_groups." + this.Ga[a] + "}}"
            })).click(this.group.bind(this,
                this.Ga[a])));
            D.add("items", this.load.bind(this)).ins(R()).ins(this.ig).ins(R()).ins(this.ha).ins(R()).ins(this.Uh).ins(this.gd)
        },
        load: function() {
            ia.Ua = 0;
            new p("items/load", {
                G: this.ha,
                J: function(a) {
                    a.p || this.Uh.hide();
                    this.gd.C("{{invent_weight:" + a.weight + ":" + a.maxweight + "}}");
                    (this.nj = a.weight > a.maxweight) ? this.gd.addClassName("rednote"): this.gd.removeClassName("rednote");
                    T(this.gd, function() {
                        r.show(this.gd).ins("{{invent_weight_cap_inform}}");
                        r.T("{{invent_weight_cap_extend}}", function() {
                            O("{{invent_weight_cap_extend_confirm:1000:5}}") &&
                                new p("items/invextend", {
                                    J: sa.load.bind(sa)
                                })
                        }.bind(this))
                    }.bind(this));
                    l.eb = [];
                    for (var b = 0; b < a.items.length; b++) {
                        var c = new Nb(a.items[b]);
                        0 < c.Ja && (l.eb.push(c), this.ha.ins(c))
                    }
                    this.group(this.$e)
                }.bind(this)
            })
        },
        group: function(a) {
            this.$e = a;
            this.ig.select(".button").invoke("removeClassName", "pressed")[this.Ga[a]].addClassName("pressed");
            l.eb.each(function(b) {
                0 == a || b.$e == a ? b.B.show() : b.B.hide()
            })
        }
    }),
    ib = Class.create(Z, {
        initialize: function($super, b) {
            $super(b);
            this.N = {
                ba: this.B,
                pa: (new Element("div")).addClassName("name").ins(this.Ab())
            };
            this.B = new Element("div");
            this.B.ins(this.N.ba).ins(this.N.pa);
            return this
        }
    }),
    Nb = Class.create(Z, {
        initialize: function($super) {
            $super.apply(h, $A(arguments).slice(1));
            ia.ri(this, "item");
            ia.dg(this, "item", this.zf.bind(this));
            return this
        },
        click: function() {
            if (S) {
                if (ia.Ua) {
                    ia.Ua.removeClassName("dragging");
                    this.zf(ia.Ua);
                    ia.Ua = 0;
                    r.Ia();
                    return
                }
                r.T("{{item_menu_sorting}}", function() {
                    ia.Ua = this;
                    this.addClassName("dragging")
                }.bind(this))
            }
            J.Mc && r.T("{{item_menu_give_to_npc}} " + J.Tg, J.vk.bind(J, this));
            I.yg() &&
                !this.Ug && r.T("{{item_menu_add_lot}}", Auc.Gh.curry(this, "item"));
            l.dc && !this.Ug && (this.wf("{{trade_in}}: ", 0, z.Fh.curry(this)), r.T("{{button_add}}", z.Fh.curry(this), f));
            l.Bb && 1 == this.qe[1] && r.T("{{item_menu_use_fight}}", y.zh.bind(y, this));
            l.Ng() || (1 == this.qe[0] && r.T("{{item_menu_dress_poke}}", this.hm.bind(this), f), 1 == this.qe[2] && r.T("{{item_menu_use}}", this.Sb.bind(this, 0)), 1 == this.qe[3] && r.T("{{item_menu_give_poke}}", this.Ok.bind(this), f), 1 == this.qe[4] && r.T("{{item_menu_wear}}", Ava.Sk.curry(this.id,
                f)), this.Ug || r.T("{{item_menu_drop}}", this.Si.bind(this), f).addClassName("red"));
            144 == this.ja && r.T("{{item_menu_incubator}}", function() {
                var a = 0;
                for (X = 0; X < l.eb.length; X++) 298 == l.eb[X].ja && (a = l.eb[X]);
                a ? a.Sb(h, this) : new L(M, "error", "{{item_menu_incubator_no}}")
            }.bind(this))
        },
        Si: function() {
            var a = this.Oe();
            if (!a) return this.wf("{{amount}}: ", 0, this.Si.bind(this));
            O("{{item_drop_confirm}}") && new p("items/drop", [this.id, a], {
                G: r,
                J: function() {
                    D.Y("items").ka()
                },
                onComplete: function() {
                    r.Ia()
                }
            })
        },
        hm: function() {
            F.show(this.title +
                " {{item_menu_dress_poke}}", 0,
                function(a) {
                    new p("items/dress", [this.id, a], {
                        J: function() {
                            r.Ia();
                            D.Y("items").ka()
                        }
                    })
                }.bind(this))
        },
        Sb: function(a, b, c, d) {
            248 == this.ja && !c ? (q.show("{{item_choose_move}}"), new p("dex/poke", {
                G: q,
                aa: [0, a.ia],
                J: function(b) {
                    for (var c = 0; c < b.moves.lvls.size(); c++) {
                        var d = b.moves.lvls[c];
                        d.lvl <= a.Db && q.add(d.title, this.Sb.bind(this, a, h, d.id, 1))
                    }
                }.bind(this)
            })) : 228 == this.ja && !c ? (q.show("{{item_timer_set}}"), q.add(Ha(60), this.Sb.bind(this, h, h, 1)), q.add(Ha(300), this.Sb.bind(this,
                h, h, 5)), q.add(Ha(600), this.Sb.bind(this, h, h, 10)), q.add(Ha(1800), this.Sb.bind(this, h, h, 30)), q.add(Ha(3600), this.Sb.bind(this, h, h, 60))) : new p("items/use", [this.id, a ? a.id : 0, b ? b.id : 0, c ? c : 0, d], {
                se: {
                    timeout: 10
                },
                J: function(a) {
                    a.txt && new L(M, a.success ? "success" : "error", a.txt);
                    a.reader && t.show(this.title).C((new Element("div")).addClassName("divReader").C(a.reader));
                    D.Y("items").ka()
                }.bind(this)
            })
        },
        Ok: function() {
            a = 1;
            if (-1 != [10, 11, 23, 24, 39, 59, 41, 269, 270, 309, 385, 436].indexOf(this.ja) && 1 < this.Ja) {
                var a = this.Oe();
                if (!a) return this.wf("{{amount}}: ", 1, this.Ok.bind(this))
            }
            F.show(this.title + " {{item_menu_give_poke}}", 0, function(b) {
                this.Sb(F.ea[b], h, h, a)
            }.bind(this))
        },
        zf: function(a) {
            a.hide();
            var b = (new Element("div")).addClassName("loader");
            this.B.insert({
                before: b
            });
            new p("items/sort", [a.id, this.id], {
                J: function() {
                    this.B.insert({
                        before: a
                    })
                }.bind(this),
                onComplete: function() {
                    a.show();
                    b.remove()
                }.bind(this)
            })
        }
    }),
    Ob = Class.create(Z, {
        initialize: function($super, b, c, d) {
            $super(b);
            this.Wa = b[9];
            this.uf = b[11];
            this.Td = d;
            this.td =
                c;
            this.B.addClassName("npctrade" + (!this.uf ? " notavailable" : ""));
            return this
        },
        Ab: function($super) {
            return $super() + (0 <= this.uf ? " <br>{{item_remains:" + this.uf + "}}" : "") + "<br>{{item_price}}: <b>" + Aa(this.Wa, this.Td[1]) + "</b>"
        },
        click: function() {
            if (0 == this.uf) return k;
            this.Af = (new Element("span")).addClassName("bold");
            this.wf("{{item_menu_buy}}: ", 1, this.Yf.bind(this));
            this.ti.ins(this.Af);
            this.Gb.on("keyup", this.di.bind(this));
            this.di();
            r.T("{{item_menu_buy}}", this.Yf.bind(this))
        },
        di: function() {
            var a = this.Wa *
                this.Gb.value;
            if (!a || a < this.Wa) a = this.Wa;
            this.Af.C(Aa(a, this.Td[1]));
            this.Af.qa("rednumber", a > J.De);
            this.Af.qa("greennumber", a <= J.De)
        },
        Yf: function() {
            new p("npc/buy", [this.td, this.id, $F(this.Gb)], {
                G: $("divNpcPhrase"),
                J: function() {
                    D.Y("items").ka();
                    r.Ia()
                },
                onComplete: function() {
                    J.Ek(this.td)
                }.bind(this)
            })
        }
    }),
    Pb = Class.create(Z, {
        initialize: function($super, b, c) {
            this.Io = b[8];
            this.Wm = c;
            $super(b, f);
            return this
        },
        click: function() {
            this.Wm && r.T("{{trade_item_remove}}", z.Yj.curry(this.Io, 1))
        }
    }),
    ha = Class.create({
        initialize: function(a) {
            this.yc = {};
            for (var b in a) this.fill(b, a[b]);
            return this
        },
        fill: function(a, b) {
            this.yc[a] = b
        },
        get: function(a, b, c) {
            b = +b;
            c || (c = 1);
            return !this.yc[a] ? "" : Object.isArray(this.yc[a][b]) ? this.yc[a][b][c] ? this.yc[a][b][c] : this.yc[a][b][1] : this.yc[a][b]
        }
    }),
    I = Class.create({
        initialize: function() {
            $("divLocGoTitle").C("{{loc_go_title}}");
            $("divLocNpcTitle").C("{{loc_npc_title}}");
            this.load();
            this.mode = "loc";
            if (S) T($("divLocDescr"), function() {
                this.toggleClassName("maximized")
            });
            else $("body").on("mousemove", this.ge.bind(this));
            $("divLocTitle").on("mouseover", function() {
                "loc" == this.mode && $("divLocDescr").show()
            }.bind(this));
            $("divLocTitle").on("mouseout", function() {
                this.Ca.ab && $("divLocDescr").hide()
            }.bind(this));
            T($("divLocTitleText"), this.show.bind(this, 0))
        },
        load: function() {
            new p("loc/load", {
                J: function(a) {
                    I.Pa(a)
                }
            })
        },
        jd: function(a) {
            this.Ca = {
                id: a.loc.id,
                title: a.loc.title,
                La: a.loc.descr,
                vj: a.loc.loc_type,
                region: a.loc.region,
                Sj: a.loc.pvm,
                Tj: a.loc.pvp,
                gp: a.loc.nofight,
                ab: a.loc.img,
                path: a.loc.path
            };
            this.Fj = a.npc;
            this.Rk = a.way;
            this.cg = a.coord;
            this.ud = a.occupancy;
            this.ca = h;
            if (a.conds && !Object.isArray(a.conds)) {
                this.ca = {};
                for (var b in a.conds) this.ca[b] = new Qb(b, a.conds[b].expire)
            }
            l.Lb = this.Ca.id;
            l.region = this.Ca.region
        },
        Pa: function(a) {
            I.jd(a);
            B.Ro(I.Ca.title);
            J.close();
            $("divLocTitleText").C(I.Ca.title);
            I.show("loc");
            $("divLocDescr").C(I.Ca.La).ins((new Element("span")).addClassName("locpath").C(I.Ca.path));
            l.Ed == GR_ADMIN && $("divLocDescr").ins("<br>ID: " + I.Ca.id);
            I.Ca.ab ? $("divLocDescr").addClassName("bg") : $("divLocDescr").removeClassName("bg");
            this.Ca.Sj || this.Ca.Tj ? ($("divLocTitle").addClassName("danger"), this.Ca.Sj && $("divLocDescr").ins("<br><span class=redlabel>{{loc_pvm}}</span>"), this.Ca.Tj && $("divLocDescr").ins("<br><span class=redlabel>{{loc_pvp}}</span>")) : $("divLocTitle").removeClassName("danger");
            $("divLocGo").C();
            I.Rk && I.Rk.each(function(a) {
                var b = a[1].truncate(30),
                    e = "";
                if (b != a[1] || a[2]) e = "<b>" + a[1] + "</b>";
                a[2] && (e += "<br>" + Ha(60 * a[2]));
                a = (new N(b, "", {
                    id: "btnGo" + a[0],
                    U: e
                })).click(I.go.curry(a[0]));
                $("divLocGo").ins(a)
            });
            $("divLocNpc").C();
            I.Fj && I.Fj.each(function(a) {
                if (a) {
                    var b = new N(a[1], "", {
                        id: "btnNpc" + a[0]
                    });
                    b.click(J.Kc.bind(J, a[0]));
                    $("divLocNpc").ins(b)
                }
            });
            $("divLocTitleOccupancy").C().hide();
            I.ud && (I.ud.jn = new qb(I.ud.id, "<b>" + I.ud.title + "</b><br>{{clan_occupied_by:" + I.ud.expire.substr(0, 10) + "}}", f), $("divLocTitleOccupancy").ins(I.ud.jn).show());
            $("divLocTitleConds").C().hide();
            if (I.ca) {
                for (var b in I.ca) $("divLocTitleConds").ins(I.ca[b]);
                $("divLocTitleConds").show()
            }
            1 === I.Ca.vj || 3 === I.Ca.vj ? ($("divLocNpc").ins((new N("", "btnLocHeal")).click(PC.Dm)),
                $("divLocNpc").ins((new N("", "btnLocFarm")).click(PC.Ne.curry(0, 0))), 1170 != I.Ca.id && $("divLocNpc").ins((new N("", "btnLocBack")).click(PC.Mf))) : "pc" == t.name && t.close();
            a.edustep && s.Ke(a.edustep)
        },
        go: function(a) {
            r.hide();
            u.start("{{loc_go}}");
            new p("loc/go", [a], {
                J: function(a) {
                    I.Pa(a)
                },
                onComplete: function() {
                    u.stop()
                }
            })
        },
        sh: function() {
            if (!O("{{telep_confirm}}")) return k;
            u.start("{{teleporting}}");
            new p("loc/telep", {
                J: function(a) {
                    I.Pa(a)
                },
                onComplete: function() {
                    u.stop()
                }
            })
        },
        fc: function() {
            this.qb = {
                top: $("divLocImageBorder").cumulativeOffset().top +
                    50,
                bottom: $("divLocImageBorder").cumulativeOffset().top + $("divLocImageBorder").getHeight(),
                left: $("divLocImageBorder").cumulativeOffset().left,
                right: $("divLocImageBorder").cumulativeOffset().left + $("divLocImageBorder").getWidth()
            }
        },
        ge: function(a) {
            a = Ea(a);
            if (!(a.x < this.qb.left || a.x > this.qb.right || a.y < this.qb.top || a.y > this.qb.bottom)) {
                var b = this.qb.left - a.x;
                a = this.qb.top - a.y;
                "loc" == this.mode ? (b = -(b / 6), 112 < b && (b = 112), -7 > b && (b = -7), a = -(a / 3.1), 112 < a && (a = 112), 0 > a && (a = 0), $("divLocImage").setStyle({
                    backgroundPosition: b +
                        "% " + a + "%"
                })) : (b = -(b / 20), 112 < b && (b = 112), -7 > b && (b = -7), a = -(a / 20), 112 < a && (a = 112), 0 > a && (a = 0), $("divLocImage").setStyle({
                    backgroundPosition: -(this.cg[0] - 342 + b) + "px " + -(this.cg[1] - 195 + a) + "px"
                }), $("divMapMarker").setStyle({
                    left: 312 - b + "px",
                    top: 165 - a + "px"
                }))
            }
        },
        show: function(a) {
            S && (a = "loc");
            a || (a = "loc" == this.mode ? "map" : "loc");
            if ("loc" == a) I.Ca.ab ? ($("divLocImage").setStyle({
                backgroundImage: 'url("//' + CONF_DOMAIN_IMG + "/pub/locs/" + I.Ca.ab + '.jpg")'
            }), $("divLocDescr").hide()) : ($("divLocImage").setStyle({
                    backgroundImage: "none"
                }),
                $("divLocDescr").show()), $("divMapMarker").hide();
            else {
                if (!this.cg) return new L(M, "warning", "{{loc_no_coords}}");
                $("divLocImage").setStyle({
                    backgroundImage: 'url("//' + CONF_DOMAIN_IMG + '/pub/map/full.jpg")'
                });
                $("divMapMarker").show();
                $("divLocDescr").hide()
            }
            this.mode = a;
            ca && this.ge(ca)
        },
        yg: function() {
            return $("btnNpc236") ? 236 : 0
        },
        ag: function() {
            for (var a in this.ca) this.ca[a].B && (this.ca[a].B.visible() && this.ca[a].Wi && 0 >= this.ca[a].Xj()) && (this.ca[a].B.remove(), delete this.ca[a])
        }
    }),
    Qb = Class.create(V, {
        initialize: function($super,
            b, c) {
            $super();
            this.mi = b;
            this.Wi = c ? moment(c) : 0;
            this.B = (new Element("div")).addClassName("cond").addClassName("cond" + this.mi);
            this.B.U(this.Re.bind(this));
            return this
        },
        Xj: function() {
            return this.Wi.diff(x.na, "seconds")
        },
        Re: function() {
            return "{{loc_conds." + this.mi + "}}<br>({{date_remain:" + this.Xj() + "}})"
        }
    });
Moder = {
    Xk: function(a) {
        var b = new Y(a.from),
            c = "{{chat_area_loc}} " + a.post.loc_title;
        a.post.is.privat && (c = "{{chat_abuse_privat}}");
        a.post.is.ad && (c = "{{chat_area_ad}}");
        a.post.is.clanchat && (c = "{{chat_area_clan}}");
        a = new ob(new Y(a.post.userFrom), a.post.userTo ? new Y(a.post.userTo) : 0, a.post);
        var d = $$("#divChat .abuse[data-id=" + a.id + "] .abusers");
        d.length ? d = d[0] : (d = (new Element("div")).addClassName("abusers").C(""), c = (new Element("div")).addClassName("abuse").C("{{chat_abuse}} " + c).ins(a).ins("{{chat_abuse_from}} ").ins(d),
            c.setAttribute("data-id", a.id), B.Ze("moder", c).kj());
        d.ins(b)
    }
};
var xb = Class.create(V, {
        initialize: function($super, b) {
            $super();
            this.jd(b);
            this.B = (new Element("div")).addClassName("divMove");
            this.jj = (new Element("img", {
                src: "//" + CONF_DOMAIN_IMG + "/pub/typs/s/" + this.Jg + ".png"
            })).addClassName("imgMoveTyp");
            this.Ge = (new Element("div")).addClassName("divMoveInfo");
            this.Ji = (new Element("div")).addClassName("divMoveTitle").addClassName("category" + this.Be).C(this.title);
            this.mg = (new Element("div")).addClassName("divMoveParams");
            this.Oj && this.mg.ins(this.dh + "/" + this.Oj);
            this.Ma && this.mg.ins(this.Ma);
            this.Ge.ins(this.Ji).ins(this.mg);
            this.B.ins(this.jj).ins(this.Ge);
            0 >= this.dh && this.B.addClassName("nopp");
            T(this.jj, this.Ub.bind(this));
            this.disabled && this.disable(f)
        },
        Rl: function() {
            ia.ri(this, "move");
            ia.dg(this, "move", this.zf.bind(this))
        },
        jd: function(a) {
            this.id = a.id;
            this.title = a.title;
            this.Go = a.title_en;
            this.La = a.descr;
            this.dh = a.pp;
            this.Oj = a.pp_max;
            this.wd || (this.wd = a.power);
            this.Jf = a.acc;
            this.Rj = a.priority;
            this.target = a.target;
            this.th || (this.th = a.typ);
            this.Be = a.category;
            this.Ma = a.extra;
            this.disabled = a.disabled;
            this.num || (this.num = a.num);
            this.po = a.sound;
            this.Ml = a.contact;
            this.Jg = this.th;
            581 == this.id && (this.Jg = "5_7");
            1 == this.wd && (this.wd = 0)
        },
        click: function(a) {
            if (this.disabled) return k;
            this.Ge.addClassName("clickable");
            T(this.Ge, a)
        },
        Ub: function() {
            r.show(this.B);
            if (this.La) {
                var a = (new Element("div")).addClassName("divMove movedex"),
                    b = (new Element("img", {
                        src: "//" + CONF_DOMAIN_IMG + "/pub/typs/s/" + this.Jg + ".png"
                    })).addClassName("imgMoveTyp"),
                    c = (new Element("div")).addClassName("divMoveInfo"),
                    d = (new Element("div")).addClassName("divMoveTitle category" + this.Be).C(this.title),
                    e = (new Element("div")).addClassName("divMoveParams").C(this.Go + ", " + this.dh + " {{pp}}");
                this.po && e.ins(", {{move_sound}}");
                this.Ml && e.ins(", {{move_contact}}");
                this.Rj && e.ins(", {{move_priority}} " + Ba(this.Rj));
                c.ins(d).ins(e);
                d = "";
                this.wd && (d = (new Element("div")).addClassName("divMovePower"), e = new ab(this.wd, 250, {
                    U: "none"
                }), d.ins("<span>{{move_power}}</span>").ins(e).ins(this.wd));
                e = "";
                if (this.Jf) {
                    var e = (new Element("div")).addClassName("divMoveAcc"),
                        m = new ab(this.Jf, 100, {
                            U: "none"
                        });
                    e.ins("<span>{{move_accuracy}}</span>").ins(m).ins(this.Jf + "%")
                }
                var m = (new Element("div")).addClassName("divMoveDetails"),
                    g = (new Element("span")).addClassName("category" + this.Be).C("{{move_categories." + this.Be + "}}"),
                    v = (new Element("span")).addClassName("target" + this.target).C("{{move_target}}: {{move_targets." + this.target + "}}");
                m.ins(g).ins(v);
                g = (new Element("div")).addClassName("divMoveDescr").C(this.La);
                a.ins(b).ins(c).ins(d).ins(e).ins(m).ins(g);
                r.content(a);
                this.Ff &&
                    r.T("{{move_menu_tutor}}", J.Jo.bind(J, this.Ff, this.id));
                this.hc && r.T("{{move_menu_teach}}", l.ea[this.hc.Ij].Ao.bind(l.ea[this.hc.Ij], this.id, this.hc.num));
                this.qd && (r.T("{{move_menu_replace}}", l.ea[this.qd].Qg.bind(l.ea[this.qd], this.num)), r.T("{{move_menu_del}}", l.ea[this.qd].Jn.bind(l.ea[this.qd], this.id)))
            } else this.Pg()
        },
        Pg: function() {
            new p("dex/move", [this.id], {
                G: r,
                J: function(a) {
                    this.jd(a);
                    this.Ub()
                }.bind(this)
            })
        },
        zf: function(a) {
            var b = (new Element("div")).addClassName("loader"),
                c = (new Element("div")).addClassName("loader");
            this.B.hide().parentNode.insert(b);
            a.B.hide().parentNode.insert(c);
            new p("pokes/team/movesort", [this.qd, a.num, this.num], {
                J: function() {
                    var d = a.num;
                    a.num = this.num;
                    this.num = d;
                    b.parentNode.insert(a);
                    c.parentNode.insert(this)
                }.bind(this),
                onComplete: function() {
                    this.show();
                    a.show();
                    b.remove();
                    c.remove()
                }.bind(this)
            })
        }
    }),
    Rb = Class.create(xb, {
        initialize: function($super, b) {
            $super(b);
            this.B = this.Ji;
            T(this.B, this.Ub.bind(this));
            return this
        }
    }),
    J = Class.create({
        initialize: function() {
            this.id = 0;
            this.Tg = "";
            this.De =
                this.Td = this.Mc = 0;
            this.B = (new Element("div")).addClassName("divNpc").hide();
            this.Xb = (new Element("div")).addClassName("divNpcName");
            this.ib = (new N("", "ctrl nobg close")).click(this.close.bind(this));
            this.Hi = (new Element("div")).addClassName("divNpcImg");
            this.Ea = (new Element("div")).addClassName("divNpcInfo");
            this.Jb = (new Element("div")).addClassName("divNpcPhrase");
            this.nc = (new Element("div")).addClassName("divNpcAnswers");
            this.Ea.ins(this.Jb).ins(this.nc);
            this.B.ins(this.ib).ins(this.Xb).ins(this.Hi).ins(this.Ea);
            $("body").ins(this.B);
            ia.dg(this.B, "item", function(a) {
                this.Mc && this.vk(a)
            }.bind(this))
        },
        Kc: function(a, b, c) {
            a && (this.id = a);
            this.De = this.Td = this.Mc = 0;
            this.nc.C();
            b || (this.B.hide(), Da(this.B, {
                x: -this.B.getWidth() - 20,
                y: -70
            }), this.B.show(), this.B.setStyle({
                backgroundImage: "none"
            }), this.Xb.C(), Ka());
            new p("npc/talk", [this.id, b ? b : 0, c ? c : 0], {
                G: this.Jb,
                J: function(a) {
                    c && (D.Y("items").ka(), D.Y("pokes").ka());
                    this.id = a.npc.id;
                    this.Tg = a.npc.name;
                    this.Xb.C(this.Tg);
                    a.npc.img && this.Hi.setStyle({
                        backgroundImage: 'url("//' +
                            CONF_DOMAIN_IMG + "/pub/npc/" + a.npc.img + '.png")'
                    });
                    a.say = Parser.uo(a.say);
                    a.say = Parser.Fb(a.say);
                    a.say = Parser.ea(a.say);
                    a.say = Parser.eb(a.say);
                    a.say = Parser.wa(a.say);
                    this.Jb.C(a.say);
                    Parser.go();
                    this.nc.C();
                    a.answ.each(function(a) {
                        switch (a.content) {
                            case "[POKECHOOSE]":
                                var b = new N("{{char_select}}", "nobg txtgray2");
                                b.click(F.show.curry("{{char_select}}", 0, function(b) {
                                    this.Kc(this.id, a.id, b)
                                }.bind(this)));
                                break;
                            case "[ITEMDROP]":
                            case "[EGGDROP]":
                                this.Mc = a.id;
                                b = (new N("[ITEMDROP]" == a.content ? "{{npc_take_item}}" :
                                    "{{npc_take_egg}}", "nobg txtgray2 takeitem")).click(function() {
                                    new L(M, "info", "[ITEMDROP]" == a.content ? "{{npc_take_item_info}}" : "{{npc_take_egg_info}}")
                                });
                                break;
                            case "[STATCHOOSE]":
                                b = new N("{{char_stat_select}}", "nobg txtgray2");
                                b.click(function() {
                                    q.show("{{char_stat_select}}");
                                    for (var b = 0; 6 > b; b++) q.add(Lang.stat[b], this.Kc.bind(this, this.id, a.id, b))
                                }.bind(this));
                                break;
                            case "[NATURECHOOSE]":
                                b = new N("{{char_nature_select}}", "nobg txtgray2");
                                b.click(function() {
                                    q.show("{{char_nature_select}}");
                                    for (var b in Lang.nature) q.add(Lang.nature[b],
                                        this.Kc.bind(this, this.id, a.id, b))
                                }.bind(this));
                                break;
                            default:
                                a.content = Parser.Fb(a.content), a.content = Parser.Qb(a.content), b = (new N(a.content, "nobg txtgray2")).click(this.Kc.bind(this, this.id, a.id))
                        }
                        this.nc.ins(b)
                    }.bind(this));
                    if (a.back_ph) {
                        var b = (new N("{{npc_one_more_thing}}", "nobg txtgray2")).click(this.Kc.bind(this, this.id, -1));
                        this.nc.ins(b)
                    }
                    a.js && eval(a.js);
                    a.away && $("btnNpc" + this.id).setStyle({
                        visibility: "hidden"
                    })
                }.bind(this),
                onError: function() {
                    this.close();
                    $("btnNpc" + this.id).hide()
                }.bind(this)
            })
        },
        vk: function(a) {
            this.Mc && this.Kc(0, this.Mc, a.id)
        },
        Ek: function(a) {
            this.nc.C();
            new p("npc/loadtrade", [a], {
                G: this.Jb,
                J: function(a) {
                    this.Td = a.currency;
                    this.De = a.have;
                    this.Jb.C('<span class="tradehave txtgray">' + Aa(a.have, a.currency[1]) + "</span>");
                    a.items.each(function(c) {
                        this.Jb.ins((new Ob(c, a.npc_id, a.currency)).size("upfull"))
                    }.bind(this))
                }.bind(this)
            })
        },
        Ff: function(a) {
            this.nc.C();
            new p("npc/loadtutor", [a], {
                G: this.Jb,
                J: function(a) {
                    this.Jb.C(a.moves ? "{{npc_tutoring_moves}}" : "{{npc_tutoring_moves_no}}");
                    a.moves.each(function(c) {
                        c.extra = Aa(c.price, a.currency[1]);
                        c = new xb(c);
                        c.Ff = a.npc_id;
                        c.Ma = "";
                        this.Jb.ins(c)
                    }.bind(this))
                }.bind(this)
            })
        },
        Jo: function(a, b) {
            F.show("{{npc_tutoring}}", 0, function(c) {
                u.start("{{npc_tutoring}}");
                new p("npc/tutorgo", [a, c, b], {
                    J: function() {
                        u.start("{{npc_tutoring}}", {
                            duration: 5,
                            callback: function() {
                                new L(M, "success", "{{char_tutoring_ok}}")
                            }
                        })
                    },
                    onError: function() {
                        u.stop()
                    }
                })
            })
        },
        close: function() {
            this.Mc = this.id = 0;
            this.B.visible() && this.B.hide();
            Ka()
        }
    });
Onlines = {
    list: {},
    filter: "",
    C: function(a) {
        this.clear();
        a.each(Onlines.join.bind(this))
    },
    get: function(a) {
        return this.list["id" + a]
    },
    join: function(a) {
        a = new Y(a);
        var b = this.get(a.id);
        b ? b.B.replace(a.B) : $("divOnlineUsers").ins(a);
        this.list["id" + a.id] = a;
        Onlines.Zi(a.B);
        this.vh()
    },
    od: function(a) {
        var b = this.get(a);
        b && (b.B.remove(), delete this.list["id" + a]);
        this.vh()
    },
    clear: function() {
        this.list = {};
        $("divOnlineUsers").C();
        this.vh()
    },
    vh: function() {
        var a = $$("#divOnlineUsers .trainer").size();
        $("divOnlineAmount").C("{{loc_trainers_online:" +
            a + "}}")
    },
    search: function() {
        Onlines.filter = B.F.Dd.value.strip();
        Onlines.filter ? B.F.Dd.addClassName("full") : B.F.Dd.removeClassName("full");
        $("divOnlineUsers").select(".trainer").each(Onlines.Zi)
    },
    Zi: function(a) {
        !Onlines.filter || a.select(".label")[0].innerHTML.toLowerCase().include(Onlines.filter.toLowerCase()) ? a.show() : a.hide()
    }
};
PC = {
    kf: h,
    vn: h,
    ah: [],
    Ne: function(a) {
        a || (a = [1, "", 0]);
        var b = !t.bc("pc");
        t.show("{{pc_farm_title}}", {
            name: "pc"
        });
        var c = (new Element("div")).addClassName("divFarm"),
            d = new Element("div", {
                id: "divFarmList"
            });
        c.ins(d);
        t.C(c);
        new p("pc/farm/load", {
            aa: a,
            G: d,
            J: function(a) {
                var m = Object.clone(Lang.nature);
                m[1] = m[0];
                m[0] = "{{not_selected}}";
                m = [
                    ["{{char_peam}}", {
                        0: "{{not_selected}}",
                        1: "{{peam_not_have}}"
                    }],
                    ["{{char_shine}}", ["{{not_selected}}", "{{char_shine_types.0}}", "{{char_shine_types.1}}", "{{char_shine_types.2}}"]],
                    ["{{char_gender}}", ["{{not_selected}}", "{{sex.1}}", "{{sex.2}}", "{{sex.3}}"]],
                    ["{{char_breed}}", "{{not_selected}};{{char_breed_not_available}};{{char_breed_available}};{{char_breed_available}} A;{{char_breed_available}} G;{{char_breed_available}} T".split(";")],
                    ["{{pcard_nature}}", m],
                    ["{{pcard_lvl}}", 0]
                ];
                if (a.peams)
                    for (var g = 0; g < a.peams.size(); g++) m[0][1][a.peams[g].id] = a.peams[g].title;
                PC.kf = new $a(d, a.navi, {
                    total: a.amount,
                    ih: "{{search_char}}",
                    of: Lang.pc_order,
                    filters: m,
                    Fg: b ? f : PC.kf.params.Fg,
                    Bc: PC.Ne
                });
                PC.ah = [];
                a.amount ? a.pokes.each(function(a) {
                    var b = (new Element("div")).addClassName("divFarmPoke");
                    a.extra = new Element("span");
                    a.hasitem && a.extra.ins((new Element("span")).addClassName("hasitem").C("\u2022"));
                    a.extra.ins((new Element("span")).addClassName("ivcode").C(a.ivcode));
                    a = new bb(a, function(a) {
                        kb(a, "farm");
                        $$("#divFarmList .divFarmPoke").invoke("removeClassName", "selected");
                        b.addClassName("selected")
                    }.curry(a.id));
                    PC.ah.push(a.id);
                    var c = (new N("", "ctrl nobg btnBack")).click(PC.wg.curry(a, 1));
                    b.ins(c).ins(a);
                    d.ins(b).ins(R())
                }) : d.Ra("{{pc_farm_info}}");
                c.ins((new Element("span")).addClassName("txtgray spComment").C("{{pc_farm_total:" + a.amount + "}}"));
                a = (new N("\u0417\u0430\u0431\u0440\u0430\u0442\u044c \u0432\u0441\u0435\u0445", "gray small btnBackAll")).Ha(1 <= a.amount && 6 >= a.amount).click(PC.jm);
                c.ins(a)
            },
            onError: function() {
                D.Y("pokes").ka()
            }
        })
    },
    wg: function(a, b) {
        new p("pc/farm/poke", [a.id, b], {
            G: b ? t : a,
            onComplete: function() {
                t.bc("pc") && PC.Ne(PC.kf.Qe());
                D.Y("pokes").ka()
            }
        })
    },
    jm: function() {
        new p("pc/farm/peam",
            PC.ah, {
                G: t,
                onComplete: function() {
                    t.bc("pc") && PC.Ne(PC.kf.Qe());
                    D.Y("pokes").ka()
                }
            })
    },
    Dm: function() {
        q.show("{{pc_heal_title}}");
        new p("pc/heal/load", {
            G: q,
            J: function(a) {
                q.add("<span>{{pc_heal_all:" + a.heal_all + "}}</span>", PC.Eg.curry(0), {
                    ra: "menuHealAll"
                });
                a.pokes.each(function(a) {
                    a = new bb(a);
                    q.add(a, PC.Eg.curry(a))
                })
            }
        })
    },
    Eg: function(a) {
        new p("pc/heal/poke", [a ? a.id : 0], {
            G: F.ha,
            onComplete: function() {
                D.Y("pokes").ka()
            }
        })
    },
    Mf: function(a) {
        a || (a = [1, ""]);
        t.show("{{pc_back_title}}", {
            name: "pc"
        });
        var b = new Element("div", {
            id: "divBackList"
        });
        t.C(b);
        new p("pc/back/load", {
            aa: a,
            G: b,
            J: function(a) {
                PC.vn = new $a(b, a.navi, {
                    Bc: PC.Mf
                });
                a.amount ? a.pokes.each(function(a) {
                    var c = (new Element("div")).addClassName("divFarmPoke");
                    a.extra = new Y(a.user);
                    a = new bb(a, function(a) {
                        kb(a, "back");
                        $$("#divBackList .divFarmPoke").invoke("removeClassName", "selected");
                        c.addClassName("selected")
                    }.curry(a.id));
                    var m = (new N("", "ctrl nobg btnBack")).click(PC.al.curry(a));
                    c.ins(m).ins(a);
                    b.ins(c).ins(R())
                }) : b.Ra();
                t.ins((new Element("span")).addClassName("txtgray spComment").C("{{pc_back_price:" +
                    a.price + "}}"))
            },
            onError: function() {
                D.Y("pokes").ka()
            }
        })
    },
    al: function(a) {
        new p("pc/back/poke", [a.id], {
            G: t,
            onComplete: function() {
                PC.Mf();
                D.Y("pokes").ka()
            }
        })
    }
};
Parser = {
    Jc: new Hash,
    Qb: function(a) {
        a = "" + a;
        a = Parser.url(a);
        nb.each(function(b) {
            a = a.replace(b[2], '<img class="sm" src="//' + CONF_DOMAIN_IMG + "/pub/smiles/" + b[0] + '.gif">')
        });
        return a = a.replace(/#([0-9][0-9][0-9])(s)?/g, function(a, c, d) {
            return lb.Cg(f, c, d)
        })
    },
    Oh: function(a) {
        return a = a.replace(/(\r\n)+/g, "<br />").replace(/(\n)+/g, "<br />")
    },
    url: function(a) {
        function b(a) {
            a = a.stripScripts();
            a = a.sub("http://", "");
            a = a.sub("https://", "");
            a.endsWith("/") && (a = a.substr(0, a.length - 1));
            var b = new Element("a", {
                target: "_blank"
            });
            b.href = "//" + a.replace("&amp;", "&");
            40 < a.length && (a = a.substr(0, 37) + "...");
            b.C(a);
            return b.outerHTML
        }
        a = a.replace(/(https?:\/\/)?(www\.)?(((forum|wiki|img|help)\.)?league17\.ru|joxi\.ru|floomby\.ru|screenshot\.ru|pixs\.ru|cybergame\.tv|(ru\.)?justin\.tv|(ru\.)?twitch\.tv)(\/[0-9a-z_\-\?=\.&#%\(\)\u0430-\u044f\u0451;\/]*)?/ig, b);
        return a = a.replace(/(https?:\/\/)?(www\.)?(volnorez\.com\/league17)/ig, b)
    },
    Fb: function(a, b) {
        b || (b = l.Fb);
        return a.replace(/\[([^\]]*)\|([^\]]*)\]/ig, function(a, d, e) {
            return "m" ==
                b ? d : e
        })
    },
    uo: function(a) {
        return a = a.replace(/\*/, '<span class="subdo">').replace(/\*/, "</span>")
    },
    ea: function(a) {
        return a = a.replace(/\[(P|POKE)(\d+)(:(\d+))?(_(\d+))?\]/ig, function(a, c, d, e, m, g, v) {
            if (!m || 1 > m || 7 < m) m = 1;
            v = v ? +v : 0;
            if ("POKE" == c) return lb.Cg(f, d, v, m);
            if ("P" == c) return lb.Cg(k, d, k, m)
        })
    },
    xa: function(a) {
        return a.replace(/\[TR(\d+)_(\d+)_(m|f)_([^\]]+)\]/ig, function(a, c, d, e, m) {
            a = Math.random();
            Parser.Jc.set(a, new Y({
                id: c,
                uname: m,
                ugroup: d,
                sex: e
            }));
            return '<span id = "parser' + a + '"></span>'
        })
    },
    Il: function(a) {
        return a.replace(/\[CLAN(\d+)_([^\]]+)\]/ig,
            function(a, c, d) {
                a = Math.random();
                Parser.Jc.set(a, new qb(c, d, f, f));
                return '<span id = "parser' + a + '"></span>'
            })
    },
    eb: function(a) {
        return a.replace(/\[ITEM(\d+)_([^\]^_]+)_?(\d*)_?(\d*)\]/ig, function(a, c, d, e, m) {
            a = Math.random();
            Parser.Jc.set(a, (new Z([0, c, e ? e : 1, m ? m : 0, d])).size("intext"));
            return '<span id = "parser' + a + '"></span>'
        })
    },
    Wk: function(a) {
        return a.replace(/\[ACHIVE(\d+)_([^\]^_]+)\]/ig, function(a, c, d) {
            a = Math.random();
            Parser.Jc.set(a, new gb({
                id: c,
                title: d,
                reached: f
            }));
            return '<span id = "parser' + a + '"></span>'
        })
    },
    wa: function(a) {
        return a.replace(/\[MOVE(\d+)_(\d+)_(\d+)_([^\]]+)\]/ig, function(a, c, d, e, m) {
            a = Math.random();
            Parser.Jc.set(a, new Rb({
                id: c,
                typ: d,
                category: parseInt(e),
                title: m
            }));
            return '<span id = "parser' + a + '"></span>'
        })
    },
    ip: aa(),
    go: function() {
        Parser.Jc.each(function(a) {
            var b = $("parser" + a.key);
            b && b.C(a.value)
        });
        Parser.Jc = new Hash
    }
};

function n(a, b) {
    b || (b = 1);
    if ("string" != typeof a && !(a instanceof String)) return a;
    (a = a.replace(/\{{2}([^{]+?)\}{2}/ig, function(a, b) {
        b = b.split(":");
        var e = b[0];
        0 <= e.indexOf(".") ? (e = e.split("."), e = Lang[e[0]] && Lang[e[0]][e[1]]) : e = Lang[e];
        return !e || !Object.isString(e) ? a : e.replace(/\[([lmdrspty])(\d)\]/ig, function(a, c, e) {
            e = b[+e];
            a = "";
            switch (c) {
                case "l":
                    a = "0 I II III IV V VI VII VIII IX X XI".split(" ")[e];
                    break;
                case "d":
                    a = za(e);
                    break;
                case "r":
                    a = Ba(e);
                    break;
                case "m":
                    a = Aa(e);
                    break;
                case "p":
                    a = Aa(e, n("{{currency_pearl_alias}}"));
                    break;
                case "s":
                    a = e;
                    break;
                case "t":
                    a = n(Ha(e));
                    break;
                case "y":
                    a = moment(e).format("ll")
            }
            return a
        })
    })) && (3 > b && a.match(/\{{2}([^{]+?)\}{2}/ig)) && (a = n(a, b + 1));
    return a = Parser.ea(a)
}
Peam = {
    im: k,
    load: function(a) {
        Peam.im = k;
        q.show("{{peam_title}}");
        new p("peam/load", [a], {
            G: q,
            J: function(b) {
                q.ld();
                b.peams && b.peams.each(function(c) {
                    q.add(c.title, Peam.join.curry(a, c.id), {
                        ra: "peam",
                        selected: b.cur_peam == c.id
                    })
                });
                q.ld();
                b.cur_peam && q.add("{{peam_del}}", Peam.od.curry(a));
                q.add("{{peam_edit}}", function() {
                    q.show("{{peam_edit}}").ld();
                    b.peams && b.peams.each(function(a) {
                        q.add(a.title, Peam.Ti.curry(a), {
                            ra: "peam"
                        })
                    });
                    q.ld().add("{{peam_add}}", Peam.Ti.curry(0))
                })
            }
        })
    },
    join: function(a, b) {
        new p("peam/join",
            [a, b])
    },
    od: function(a) {
        new p("peam/leave", [a])
    },
    Ti: function(a) {
        t.show(a ? "{{peam_str}} - " + a.title : "{{peam_new}}");
        var b = (new Element("input", {
                value: a ? a.title : ""
            })).addClassName("string"),
            c = (new N("{{button_save}}", "gray")).click(function() {
                new p("peam/edit", [a ? a.id : 0], {
                    G: t,
                    parameters: {
                        title: b.value
                    },
                    onComplete: t.close.bind(t)
                })
            }),
            d = (new N("{{button_del}}", "gray")).click(function() {
                new p("peam/del", [a.id], {
                    G: t,
                    onComplete: t.close.bind(t)
                })
            });
        t.ins(Q("spinpt", "{{peam_str_title}}:")).ins(b).ins("<br>");
        t.ins(Q("spinpt", " ")).ins(c).ins(a ? d : "")
    }
};
var Sb, Tb, Ub, Vb, Wb;

function Mb() {
    D.close();
    t.show("{{pearljam_title}}");
    new p("pearljam/load", {
        G: t,
        J: function(a) {
            l.bp = a.email;
            Sb = (new Element("div")).addClassName("divJamList");
            Tb = (new Element("div")).addClassName("divJamGet").hide();
            Ub = (new Element("div")).addClassName("divJamForm").hide();
            a.items.each(function(a) {
                Sb.ins(new Xb(a))
            });
            Vb = (new N("{{pearljam_buy}}", "gray btnJamBuy")).click(Yb.curry(1));
            Wb = (new N("{{pearljam_back}}", "gray btnJamBack")).click(Yb.curry(0)).hide();
            t.ins(Sb).ins(Tb).ins(Ub).ins(R());
            t.ins(Vb).ins(Wb).ins('<span class="spPearlBalance txtgray">{{pearl_balance:' +
                a.pearl + "}}</span>")
        }
    })
}

function Yb(a) {
    a ? (Tb.empty() && new p("pearljam/paygate", {
        G: Tb,
        J: function(a) {
            Tb.C();
            for (var c = (new Element("div")).addClassName("divJamPrice").ins("{{pearljam_price:" + PEARL_PRICE + "}}"), d = (new Element("div")).addClassName("divJamCases"), e = (new Element("div")).addClassName("divJamInfo txtgray").ins("{{pearljam_info}}"), m = (new Element("div")).addClassName("divJamDealer").ins("{{pearljam_dealer}}: ").ins(new Y({
                    id: 61,
                    ga: "\u0424\u043e\u0441\u0441\u0430",
                    Fb: "f"
                })), g = 0; g < a.size(); g++) {
                var v = a[g].amount * PEARL_PRICE,
                    G = (new Element("div")).C("{{currency_pearl}} x" + a[g].amount),
                    v = (new Element("a", {
                        href: a[g].url,
                        target: "_blank"
                    })).addClassName("button gray").C("{{pearljam_pay:" + v + "}}");
                d.ins(G.ins(v))
            }
            Tb.ins(c).ins(d).ins(e).ins(m)
        }
    }), Tb.show(), Wb.show(), Sb.hide(), Vb.hide()) : (Tb.hide(), Wb.hide(), Sb.show(), Vb.show())
}
var Xb = Class.create(Ob, {
    initialize: function($super, b) {
        $super(b, 0, [500, "{{currency_pearl}}"]);
        return this
    },
    Yf: function() {
        new p("pearljam/buy", [this.id, $F(this.Gb)], {
            G: t,
            J: aa(),
            onComplete: function() {
                Mb()
            }
        })
    }
});

function kb(a, b) {
    w.open("divPokeCard");
    new p("pokecard/" + b, [a], {
        G: $("divPokeCard"),
        J: function(a) {
            a && $("divPokeCard").C(new Zb(a))
        }
    })
}
var $b = Class.create(V, {
        initialize: function($super, b) {
            $super();
            this.id = b.id;
            this.ia = b.sp_id;
            this.form = b.form;
            this.xg = b.form_title;
            this.Mj = b.pokename;
            this.name = b.name;
            this.tc = b.gender;
            this.th = b.typ;
            this.oa = b.shine;
            this.Cm = b.happy;
            this.Nf = b.binded;
            this.sf = b.rank;
            this.Db = b.lvl;
            this.dp = b.hasitem;
            this.rd = b.nature + "";
            b.ability && b.ability.id && (this.Xo = new fb(b.ability, this.ia));
            b.user && (this.ya = {
                id: b.user.id
            });
            this.Sc = {};
            b.catcher && (this.Sc.xa = b.catcher.id ? new Y(b.catcher) : 0, this.Sc.date = Ia(b.catcher.date),
                this.Sc.ep = b.catcher.how);
            b.master && (this.ef = new Y(b.master));
            this.wb = b.stats;
            this.ma = {
                wb: b.ev,
                ub: b.ev_free,
                Zo: b.ev_bonus,
                gb: [0, 0, 0, 0, 0, 0]
            };
            this.qj = b.ivcode;
            b.item && (this.item = (new Z([0, b.item.id, 1, b.item.memo, b.item.title])).size("mini"));
            b.exp && (this.exp = b.exp.cur ? {
                Qj: b.exp.prev,
                next: b.exp.next,
                Ce: b.exp.cur
            } : b.exp);
            this.Hg = b.hp;
            this.Hm = b.hp_max;
            this.bl = b.ball;
            this.Ph = b.breedable;
            this.Fa = b.param;
            this.wa = [0, 0, 0, 0, 0];
            for (var c = 1; 4 >= c; c++) b.moves && b.moves[c] && (this.wa[c] = new xb(b.moves[c]));
            this.status =
                Object.isArray(b.status) ? {} : b.status;
            this.rh = Object.isArray(b.status2) ? {} : b.status2;
            this.Tk = b.wild;
            this.Ma = b.extra;
            this.zk = b.tip;
            this.ic = ya(this.ia);
            this.name ? this.Dj = f : (this.name = this.Mj, this.Dj = k);
            this.id || (this.id = 0);
            this.sf || (this.sf = "");
            this.title = this.name;
            this.Ak = 15 < this.title.length ? this.title.substr(0, 15) + "..." : this.title;
            this.N = {}
        },
        ne: function() {
            this.Ue || (this.Ue = {});
            if (this.N.ba.empty()) {
                421 == this.ia && (this.form = 2 == y.Hf ? 2 : 1);
                if (585 == this.ia || 586 == this.ia) this.form = [4, 4, 1, 1, 1, 2, 2, 2, 3, 3,
                    3, 4
                ][x.na.month()];
                0 > this.oa && this.N.ba.addClassName("shadow");
                var a = 0 < this.oa && 16 != this.oa && 24 != this.oa ? "shine" : "norm";
                26 == this.oa && (a = "event");
                32 == this.oa && (a = "dark");
                var b = this.tc == SEX_FEMALE && 0 <= [282, 592, 593, 521, 576].indexOf(this.ia) ? "f" : "";
                ("norm" == a || "shine" == a) && E.params.anims && ha.get("pokeanims", this.ia) ? this.Ue.Hh = "//" + CONF_DOMAIN_IMG + "/pub/mnst/" + a + "/anim/" + this.ic + b + (this.form ? "_" + this.form : "") + ".gif" : this.Ue.Hh = "//" + CONF_DOMAIN_IMG + "/pub/mnst/" + a + "/full/" + this.ic + b + (this.form ? "_" + this.form :
                    "") + ".png";
                this.N.vc = new Element("img");
                this.N.ba.C(this.N.vc);
                this.hk(this)
            }
        },
        hk: function() {
            this.N.vc.src = this.Ue.Hh;
            var a = ha.get("pokeanims", this.ia, this.oa ? 2 : 1);
            E.params.anims && a && this.hk.bind(this).delay(P(a + 2, a + 5))
        },
        xc: function() {
            return this.ef && this.ef.id == l.id
        },
        oj: function() {
            return this.ya && this.ya.id == l.id
        }
    }),
    ac = Class.create($b, {
        initialize: function($super, b) {
            $super(b);
            var c = {};
            this.B = new Element("div");
            c.pa = (new Element("div")).addClassName("title");
            c.Xb = (new Element("div")).addClassName("name" +
                (this.oa ? " shine shine" + this.oa : "")).C(this.title);
            c.Zl = (new Element("div")).addClassName("gender sex" + this.tc + (this.Ph ? " breedable" : ""));
            c.pa.ins(c.Xb).ins(c.Zl);
            c.lg = (new Element("div")).addClassName("minicardContainer");
            c.Ii = (new Element("div")).addClassName("minicard");
            c.lg.ins(c.Ii);
            c.vi = (new Element("div")).addClassName("boxleft antioverlaybug");
            c.md = (new Element("img", {
                src: "//" + CONF_DOMAIN_IMG + "/pub/balls/slots/" + this.bl + ".png"
            })).addClassName("ball" + (0 < this.Hg ? "" : " defeat"));
            c.Fe = (new Element("div")).addClassName("lvl").C(this.Db);
            c.vi.ins(c.md).ins(c.Fe);
            c.ba = (new Element("div")).addClassName("image");
            c.em = (new Element("div")).addClassName("rank txtgray2").C(this.sf);
            c.wi = (new Element("div")).addClassName("boxright");
            c.Gi = (new Element("div")).addClassName("icons antioverlaybug");
            c.pg = (new Element("div")).addClassName("tren");
            c.bm = (new Element("div")).addClassName("items antioverlaybug").C(this.item ? this.item : "");
            c.wi.ins(c.Gi).ins(c.pg).ins(c.bm);
            c.ui = new Element("div", {
                "class": "bars"
            });
            c.Mh = new ab(this.Hg, this.Hm, {
                title: "{{hp}}",
                ra: "barHP",
                Ll: f
            });
            c.cl = new ab(this.exp.Ce - this.exp.Qj, this.exp.next - this.exp.Qj, {
                title: "{{char_exp}}",
                ra: "barEXP"
            });
            c.ui.ins(c.Mh).ins(c.cl);
            c.Ii.ins(c.em).ins(c.ba).ins(c.vi).ins(c.wi).ins(c.ui);
            for (var d = 0; 5 >= d; d++) this.Fa && (this.Fa.tr && this.Fa.tr[d]) && c.pg.addClassName("tren" + this.Fa.tr[d]);
            for (var e in this.status)(d = new wb(e, this.status[e], this.rh[e])) && c.Gi.ins(d);
            this.B.ins(c.lg).ins(c.pa);
            this.N = c;
            return this
        }
    }),
    Zb = Class.create(ac, {
        initialize: function($super, b) {
            $super(b);
            var c = this.N;
            this.addClassName("pokemonBoxCard");
            this.ne(f);
            c.Ea = (new Element("div")).addClassName("info");
            c.Ea.ins(new lb({
                sp_id: this.ia,
                pokename: this.Mj,
                shine: this.oa,
                form: this.form,
                form_title: this.xg
            }, {
                xn: f,
                Vo: f
            }));
            c.Ea.ins(R());
            c.Ea.ins("<b>{{pcard_nature}}:</b> {{nature." + this.rd + "}}");
            c.Ea.ins("<br><b>{{pcard_ivcode}}:</b> " + this.qj);
            c.Ea.ins("<br><b>{{pcard_catched}}:</b> ");
            this.Sc.xa && c.Ea.ins(this.Sc.xa).ins(" ");
            c.Ea.ins(this.Sc.date);
            c.Yh = (new N("...", "gray nobg btnStatistics")).click(this.gn.bind(this));
            r.Sa(c.Yh, "{{pcard_statistics}}");
            c.Ea.ins(c.Yh);
            c.Ie = [];
            c.og = [];
            c.qf = [];
            c.Jd = [];
            c.Kd = [];
            c.sc = new Element("div", {
                "class": "stats txtgray2"
            });
            c.Dn = new ab(this.Cm, 255, {
                ra: "barHappy",
                title: "{{pcard_happiness}}"
            });
            c.sc.ins((new Element("div")).addClassName("statHappy").C("{{pcard_happiness}}")).ins(c.Dn).ins(R());
            for (var d = 0; 5 >= d; d++) c.sc.ins((new Element("div", {
                "class": "statTitle"
            })).C("{{stat." + d + "}}")), c.Ie[d] = (new Element("div", {
                "class": "statVal"
            })).C(this.wb[d] ? this.wb[d] : "?"), d && (this.rd && this.rd[0] != this.rd[1]) && (this.rd[0] == d &&
                c.Ie[d].addClassName("greennumber"), this.rd[1] == d && c.Ie[d].addClassName("rednumber")), c.og[d] = new Element("div", {
                "class": "statTren"
            }), this.Fa && (this.Fa.tr && this.Fa.tr[d]) && c.og[d].ins(c.pg.clone().U("{{char_zatoch." + this.Fa.tr[d] + "}}")), c.qf[d] = new ab(this.ma.wb[d], EV_MAX, {
                ra: "barEV",
                title: "{{stat." + d + "}} EV"
            }), c.sc.ins(c.Ie[d]).ins(c.og[d]).ins(c.qf[d]).ins(R());
            c.Ei = (new Element("div", {
                "class": "statVal ev"
            })).C(this.ma.ub);
            c.sc.ins((new Element("div", {
                "class": "statTitle ev"
            })).C("{{pcard_available_ev}}")).ins(c.Ei);
            c.Xd = new Element("div", {
                "class": "tag"
            });
            c.$l = (new Element("div")).addClassName("id").ins("id" + this.id);
            this.xc() || c.Xd.ins("<br><b>{{pcard_master}}:</b> ").ins(this.ef ? this.ef : "{{pcard_unknown}}").ins("<br>");
            this.Nf && c.Xd.ins("<br><b>{{pcard_binded}}</b><br>");
            c.tb = new Element("div", {
                "class": "moves"
            });
            c.mb = [];
            for (d = 1; 4 >= d; d++) c.mb[d] = new Element("div", {
                "class": "moveBox"
            }), c.tb.ins(c.mb[d]), this.wa[d] && (this.wa[d].qd = this.id, c.mb[d].ins(this.wa[d]));
            c.Sh = (new N("\u00b7\u00b7\u00b7", "nobg wide divMovesTeachable")).click(this.Qg.bind(this));
            c.tb.ins(c.Sh);
            for (d = 1; 6 >= d; d++)
                if (this.Fa && this.Fa.trauma && this.Fa.trauma[d]) {
                    var e = Lang.char_trauma[d][0][this.Fa.trauma[d] - 1],
                        m = Lang.char_trauma[d][1];
                    c.Xd.ins((new Element("div")).C(e).U(m));
                    c.pa.addClassName("attention")
                }
            if (this.Fa && this.Fa.cond)
                for (var g in this.Fa.cond) c.Xd.ins((new Element("div")).C("{{char_zatoch_conds." + g + ":" + this.Fa.cond[g] + "}}"));
            c.lg.ins(c.tb);
            this.B.ins(c.Ea).ins(c.sc).ins(c.Xd).ins(c.$l);
            S && c.sc.ins({
                after: c.tb
            });
            return this
        },
        Qg: function(a) {
            q.show("{{pcard_move_teach}} <b>" +
                this.title + "<b>");
            new p("pokes/team/load_teach", [this.id], {
                G: q,
                J: function(b) {
                    b.each(function(b) {
                        b = new xb(b);
                        a && (b.hc = {
                            Ij: this.id,
                            num: a
                        });
                        q.add(b)
                    }, this)
                }.bind(this)
            })
        },
        gn: function() {
            new p("pokes/team/load_statistics", [this.id], {
                G: r,
                J: function(a) {
                    for (var b = "", c = 0; c < a.length; c++) b += "<br>{{pcard_statistics_fields." + c + ":" + a[c] + "}}";
                    b += "<br>&nbsp;";
                    this.Fa.mod84 && (b += "<br>{{pcard_statistics_param_mod84}}");
                    this.Fa.mod86 && (b += "<br>{{pcard_statistics_param_mod86}}");
                    r.content(b)
                }.bind(this)
            })
        }
    }),
    bc = Class.create(Zb, {
        initialize: function($super, b) {
            $super(b);
            var c = this.N;
            this.Bj();
            T(c.ba.addClassName("resizeable"), this.toggle.bind(this));
            T(c.md.addClassName("clickable"), this.Sg.bind(this));
            this.item && (this.item.click = this.qn.bind(this));
            if (this.xc()) {
                for (var d = 0; 5 >= d; d++) c.Jd[d] = new N(" ", "btnEvAdd", {
                    Ej: f
                }), c.Jd[d].click(this.gf.bind(this, d, 1)), c.Jd[d].click(this.gf.bind(this, d, 10), 9), c.Kd[d] = new N(" ", "btnEvSub", {
                    Ej: f
                }), c.Kd[d].click(this.gf.bind(this, d, -1)), c.Kd[d].click(this.gf.bind(this, d, -10), 9), c.qf[d].B.ins({
                    before: c.Kd[d]
                }).ins({
                    after: c.Jd[d]
                });
                c.Tf = (new N("{{button_save}}", "btnEV gray")).click(this.Rn.bind(this));
                c.Sf = (new N("{{button_reset}}", "btnEV gray")).click(this.Nn.bind(this));
                c.sc.ins(c.Tf).ins(c.Sf);
                for (d = 1; 4 >= d; d++)
                    if (this.wa[d]) this.wa[d].Rl();
                    else {
                        var e = (new N("{{empty}}", "gray btnLoadTeach")).click(this.Qg.bind(this, d));
                        c.mb[d].ins(e)
                    }
                c.Sh.hide()
            }
            this.Pa()
        },
        Pa: function() {
            var a = this.N;
            if (this.xc()) {
                for (var b = 0; 5 >= b; b++) a.qf[b].set(this.ma.wb[b] + this.ma.gb[b]), a.Jd[b].Ha(0 < this.ma.ub && this.ma.wb[b] + this.ma.gb[b] < EV_MAX), a.Kd[b].Ha(0 <
                    this.ma.gb[b]);
                this.Vm() ? (a.Tf.show(), a.Sf.show()) : (a.Tf.hide(), a.Sf.hide());
                a.Ei.C(this.ma.ub)
            }
            E.params.starter == this.id ? this.addClassName("starter") : this.removeClassName("starter")
        },
        qn: function() {
            r.T("{{pcard_undress}}", this.Oo.bind(this))
        },
        Sg: function() {
            q.show(this.title);
            q.add("{{pcard_dex}}", A.R.curry(this.ia, this.oa));
            l.dc && !this.Nf && (q.add('<img src="//' + CONF_DOMAIN_IMG + '/pub/interface/trade_temp.png"> {{pcard_trade_temp}}', z.re.curry(this, 0)), this.xc() && q.add('<img src="//' + CONF_DOMAIN_IMG +
                '/pub/interface/trade_master.png"> {{pcard_trade_master}}', z.re.curry(this, 1)));
            I.yg() && (!this.Nf && this.xc()) && q.add('<img src="//' + CONF_DOMAIN_IMG + '/pub/interface/trade_master.png"> {{auc_add_lot}}', Auc.Gh.curry(this, "poke"));
            l.Bb && this.Hg && q.add("{{pcard_to_battle}}", y.Ic.bind(y, this.id));
            q.add("{{pcard_to_chat}}", this.Ho.bind(this));
            q.add("{{pcard_set_first}}", this.so.bind(this));
            q.add("{{pcard_teams}}", Peam.load.curry(this.id));
            l.Ng() || (q.add("{{pcard_deactivate}}", PC.wg.curry(this, 0)), q.add("{{pcard_deactivate_other}}",
                PC.wg.curry(this, -1)), !this.Dj && this.xc() && q.add("{{pcard_set_name}}", this.fo.bind(this)), this.xc() && (q.add("{{pcard_play}}", this.play.bind(this)), q.add("---"), q.add("{{pcard_free}}", this.ub.bind(this))))
        },
        toggle: function() {
            this.hasClassName("minimized") ? this.yj() : this.Bj()
        },
        yj: function() {
            for (var a in l.ea) l.ea[a].hide();
            this.show();
            this.removeClassName("minimized");
            this.N.Xb.C(this.title)
        },
        Bj: function() {
            this.addClassName("minimized");
            for (var a in l.ea) l.ea[a].show();
            this.N.Xb.C(this.Ak)
        },
        gf: function(a,
            b) {
            if (0 < b) {
                if (b > this.ma.ub && (b = this.ma.ub), b + this.ma.wb[a] + this.ma.gb[a] > EV_MAX && (b = EV_MAX - (this.ma.wb[a] + this.ma.gb[a])), 0 >= b) return 0
            } else b.abs() > this.ma.gb[a] && (b = -this.ma.gb[a]);
            this.ma.gb[a] += b;
            this.ma.ub -= b;
            this.Pa()
        },
        Nn: function() {
            for (var a = 0; 5 >= a; a++) this.ma.ub += this.ma.gb[a], this.ma.gb[a] = 0;
            this.Pa()
        },
        so: function() {
            E.params.starter = this.id;
            E.Pb("starter", this.id);
            for (var a in l.ea) l.ea[a].Pa()
        },
        Vm: function() {
            for (var a = 0; 5 >= a; a++)
                if (0 < this.ma.gb[a]) return f;
            return k
        },
        Rn: function() {
            this.Oa("pokes/team/ev_save",
                [this.id], {
                    ev: this.ma.gb.join(",")
                })
        },
        fo: function() {
            t.show("{{pcard_set_name}} " + this.title);
            this.Hk = (new Element("input", {
                maxlength: 25
            })).addClassName("wide");
            t.ins(this.Hk).ins("<br>");
            var a = (new N("{{button_ok}}", "gray")).click(function() {
                t.close();
                this.Oa("pokes/team/rename", [this.id], {
                    name: $F(this.Hk)
                })
            }.bind(this));
            t.ins(a)
        },
        Ao: function(a, b) {
            this.Oa("pokes/team/teach_move", [this.id, a, b])
        },
        Jn: function(a) {
            if (!O("{{pcard_move_del_confirm}}")) return k;
            for (var b = 0, c = 1; !b && 4 >= c; c++) this.wa[c].id ==
                a && (b = c);
            this.Oa("pokes/team/remove_move", [this.id, b])
        },
        Oo: function() {
            this.Oa("pokes/team/undress", [this.id])
        },
        ub: function() {
            O("{{pcard_free_confirm:" + this.title + "}}") && this.Oa("pokes/team/free", [this.id])
        },
        play: function() {
            this.Oa("pokes/team/play", [this.id])
        },
        Ho: function() {
            B.mj("#" + this.ic + (this.oa ? "s" : "") + " " + this.name + " " + this.sf + n(" {{sex." + this.tc + "}} ") + this.Db + " ")
        },
        Oa: function(a, b, c) {
            q.hide();
            c || (c = {});
            new p(a, b, {
                G: F.ha,
                parameters: c,
                J: function(a) {
                    F.fill(a.pokes);
                    l.ea[a.maximize] && l.ea[a.maximize].yj()
                }
            })
        }
    }),
    Jb = Class.create(V, {
        initialize: function($super, b) {
            $super();
            this.B = (new Element("div")).addClassName("pokemonBoxDummy");
            this.status = [];
            this.zb = 1;
            b && Object.isFunction(b) && (T(this.B, b), this.B.addClassName("clickable"));
            return this
        },
        Pa: aa(),
        gj: aa(),
        lk: aa()
    }),
    bb = Class.create($b, {
        initialize: function($super, b, c, d) {
            $super(b);
            d || (d = {});
            this.size = d.size ? d.size : 0;
            this.title = "#" + this.ic + " " + this.title;
            this.B = (new Element("div")).addClassName("pokemonBoxTiny size" + this.size + (d.ra ? " " + d.ra : ""));
            this.paused = k;
            switch (this.size) {
                default: this.N.ba =
                    (new Element("img")).addClassName("image");
                break;
                case 1:
                        this.N.ba = (new Element("div")).addClassName("imagecontainer").ins((new Element("img")).addClassName("image"));
                    break;
                case 2:
                        this.N.ba = (new Element("div")).addClassName("image")
            }
            this.ne();
            this.B.ins(this.N.ba);
            d.Ub && A.rf(this.N.ba, this.ia, this.oa);
            this.N.pa = (new Element("div")).addClassName("name").C(this.title);
            this.oa && this.N.pa.addClassName("shine");
            this.B.ins(this.N.pa);
            this.Db && (this.N.Fe = (new Element("div")).addClassName("shorts").C('<span class="sex' +
                this.tc + (this.Ph ? " breedable" : "") + '">{{sex.' + this.tc + '}}</span> <span class="lvl">' + this.Db + "</span>"), this.B.ins(this.N.Fe));
            this.Ma && (this.N.Zc = (new Element("div")).addClassName("extra").C(this.Ma), this.B.ins(this.N.Zc));
            this.zk && this.B.U(this.zk);
            c && this.click(c)
        },
        click: function(a) {
            Object.isFunction(a) && (a.$b = a, a.Ba = "all");
            var b;
            "all" == a.Ba && (b = this.B);
            "pic" == a.Ba && (b = this.N.ba);
            b.addClassName("clickable");
            T(b, a.$b)
        },
        ne: function($super) {
            var b = "//" + CONF_DOMAIN_IMG + "/pub/mnst/" + (this.oa ? "shine" :
                "norm") + "/minim" + (this.paused ? "p" : "") + "/" + this.ic + ".gif";
            switch (this.size) {
                default: this.N.ba.src = b;
                break;
                case 1:
                        this.N.ba.select("img")[0].src = b;
                    break;
                case 2:
                        $super(f)
            }
        },
        pause: function(a) {
            this.paused = a;
            this.ne()
        }
    }),
    lb = Class.create($b, {
        initialize: function($super, b, c) {
            $super(b);
            c || (c = {});
            c.yn || (this.title = "#" + this.ic + " " + this.title);
            c.Vo && this.xg && (this.title = this.title + " " + this.xg);
            this.B = (new Element("span")).addClassName("pokemonBoxLabel");
            this.oa && this.addClassName("shine");
            c.xn || (this.ab = (new Element("img", {
                src: "//" + CONF_DOMAIN_IMG + "/pub/mnst/" + (this.oa ? "shine" : "norm") + "/minim/" + this.ic + ".gif"
            })).addClassName("image"), this.B.ins(this.ab));
            this.B.ins(this.title);
            "undefined" != typeof this.tc && this.B.ins("{{sex." + this.tc + "}}");
            A.rf(this.B, this.ia, this.oa, this.form)
        }
    });
lb.Cg = function(a, b, c, d) {
    return '<span class="intextpoke' + (c ? " shine" : "") + " sp" + ya(b) + '" onclick="Exp.Dex_poke(\'' + b + "', " + (c ? 1 : 0) + ')">' + (a ? '<img class="pk" src="//' + CONF_DOMAIN_IMG + "/pub/mnst/" + (c ? "shine/" : "norm/") + "minim/" + ya(b) + '.gif"> ' : "") + ha.get("pokenames", +b, d) + "</span>"
};
for (var Ib = Class.create(ac, {
            initialize: function($super, b) {
                $super(b);
                var c = this.N;
                this.addClassName("pokemonBoxCard pokemonBoxFight minimized");
                this.N.Xb.C(this.Ak);
                A.rf(c.ba, this.ia, this.oa, this.form);
                716 == this.ia && (this.form = 1);
                c.tb = new Element("div", {
                    "class": "moves"
                });
                if (this.oj()) {
                    c.mb = [];
                    for (var d = 1; 4 >= d; d++) c.mb[d] = new Element("div", {
                        "class": "moveBox"
                    }), c.tb.ins(c.mb[d]), this.wa[d] ? (this.status[31] && this.rh[31] != this.wa[d].id && this.wa[d].disable(f), this.wa[d].click(y.Gd.bind(y, this.wa[d], d)),
                        c.mb[d].ins(this.wa[d])) : this.wa[d] = 0;
                    this.B.ins(c.tb)
                }
                y.de() && this.oj() && T(c.md.addClassName("clickable"), this.Sg.bind(this));
                b.wild && b.wild.upiv && this.N.Fe.addClassName("rednote").U("x" + b.wild.upiv);
                this.Pa();
                return this
            },
            Pa: function() {
                var a = this.ya.id === l.id ? 0 : 1;
                this.status[53] ? (this.N.ba.update(), this.hi = f) : this.status[50] ? (this.N.ba.update(new Element("img", {
                        src: "//" + CONF_DOMAIN_IMG + "/pub/fight/substitute_" + (a ? "front" : "back") + ".gif"
                    })), this.N.Mh.kk(this.rh[50]).set(this.status[50]), this.hi = f) :
                    (this.ne(a), this.hi = k);
                this.N.ba.qa("statusMinimize", this.status[41]);
                this.N.ba.qa("statusPoison", this.status[13])
            },
            Vi: function(a) {
                if (!a || a.zb || this.zb || this.Fl || a.Fl || !a.N.ba.select("img")[0]) return k;
                var b = this.N.ba.select("img")[0],
                    c = (new Element("div")).addClassName("image prepoke");
                a = new Element("img", {
                    src: a.N.ba.select("img")[0].src
                });
                this.N.ba.insert({
                    after: c.insert(a)
                });
                a.addClassName("evolve1");
                b.addClassName("evolve2");
                (function() {
                    c.remove();
                    b.removeClassName("evolve2")
                }).bind(this).delay(9)
            },
            Ic: function(a) {
                if (a) {
                    this.N.ba.style.visibility = "hidden";
                    var b = (new Element("div")).addClassName("image prepoke");
                    a = new Element("img", {
                        src: a.N.ba.select("img")[0].src
                    });
                    this.N.ba.insert({
                        after: b.insert(a)
                    });
                    b.addClassName("switche_out");
                    (function() {
                        b.remove();
                        this.Ic(0)
                    }).bind(this).delay(0.3)
                } else this.N.md.style.visibility = "hidden", this.N.ba.style.visibility = "visible", this.N.ba.style.backgroundImage = "url(" + this.N.md.src + ")", this.N.ba.addClassName("switche_in"),
                    function() {
                        this.N.md.style.visibility =
                            "visible";
                        this.N.ba.style.backgroundImage = "none";
                        this.N.ba.removeClassName("switche_in")
                    }.bind(this).delay(1)
            },
            gj: function() {
                this.N.mb && (this.N.mb.invoke("hide"), this.N.tb.addClassName("ajxloading"))
            },
            lk: function() {
                this.N.mb && (this.N.mb.invoke("show"), this.N.tb.removeClassName("ajxloading"))
            },
            Sg: function() {
                F.show("{{char_select}}", "alive", y.Ic.bind(y), function() {
                    q.ld();
                    q.add("{{pcard_use_item}}", function() {
                        r.show(this.N.pa, "{{pcard_use_in_battle}}", 0, "white");
                        r.T("{{button_cancel}}", r.close);
                        new p("items/fightlist", {
                            G: r,
                            J: function(a) {
                                if (!a || !a.total) return r.ins("{{empty}}");
                                var b = (new Element("div")).addClassName("divItemFightlist");
                                a.items.each(function(a) {
                                    b.ins((new Kb(a)).size("middle"))
                                });
                                r.ins(b)
                            }
                        })
                    }.bind(this));
                    y.bf && q.add("{{pcard_use_smth}} " + y.bf.title, y.zh.bind(y, y.bf))
                }.bind(this))
            }
        }), vb = Class.create(bb, {
            initialize: function($super, b, c, d) {
                d || (d = {});
                d.size = 1;
                $super(b, 0, d);
                this.ci = b.busy ? f : k;
                this.za = {
                    item: c
                };
                this.N.fg = (new Element("div")).addClassName("craft");
                this.B.ins(this.N.fg);
                this.click(this.onclick.bind(this));
                r.Sa(this, this.rm.bind(this), k, "white");
                return this
            },
            rm: function() {
                return this.title + "<br><b>{{craft_skill:" + this.za.pk + "}}</b>"
            },
            km: function(a) {
                this.za.pk = +a.skill;
                this.za.rb = +a.current;
                this.za.total = +a.total;
                this.za.result = a.result;
                this.Pa();
                return this
            },
            Gl: function() {
                new p("craft/checkproc", [this.za.item.id, this.id], {
                    J: function(a) {
                        a && (this.za.result = a, this.Pa(), this.za.item.ii())
                    }.bind(this)
                })
            },
            onclick: function() {
                this.za.total ? this.za.result && Craft.collect(this) : this.ci || Craft.info(this)
            },
            Pa: function() {
                this.pause(f);
                if (this.za.total)
                    if (this.za.result) {
                        var a = (new Element("div")).addClassName("divCraftResult");
                        "success" == this.za.result ? a.C("{{craft_result_success:" + this.za.item.Ja + "}}").addClassName("greennumber") : (a.C("{{craft_result_fail}}").addClassName("rednumber"), a.U("{{craft_result_fail_tip}}"));
                        this.N.fg.C(a)
                    } else a = new ab(this.za.rb, this.za.total, {
                        U: "percent",
                        oncomplete: this.Gl.bind(this)
                    }), this.N.fg.C(a), a.nh(a.pd), this.pause(k);
                else this.qa("busy", this.ci)
            }
        }), F = Class.create({
            initialize: function() {
                this.ha =
                    (new Element("div")).addClassName("divPokeTeam");
                D.add("pokes", this.load).ins(this.ha)
            },
            load: function() {
                new p("pokes/load/team", {
                    G: F.ha,
                    J: F.fill
                })
            },
            fill: function(a) {
                l.ea = {};
                a ? a.each(function(a) {
                    a = new bc(a);
                    l.ea[a.id] = a;
                    F.ha.ins(a);
                    s.da.hc && s.Ke()
                }) : F.ha.Ra()
            },
            show: function(a, b, c, d) {
                q.show(a);
                new p("pokes/load/list", {
                    G: q,
                    parameters: {
                        type: b
                    },
                    J: function(a) {
                        this.ea = {};
                        a && a.each(function(a) {
                            a = new bb(a);
                            this.ea[a.id] = a;
                            q.add(a, c.curry(a.id))
                        }, this);
                        d && d()
                    }.bind(this)
                })
            }
        }), E = Class.create({
            initialize: function() {
                D.add("profile",
                    this.load).cd;
                this.params = {
                    kp: 0
                };
                this.ca = {};
                this.F = {};
                this.Jh = this.Ih = 0
            },
            load: function() {
                l.Rc(D.Bg("profile"), E.Um)
            },
            Um: function() {
                var a = D.Bg("profile").select("div.buttons")[0],
                    b = (new N("", "ctrl nobg btnVk", {
                        ta: 1
                    })).click(E.Qk);
                a.C().ins(b);
                E.F.mc = D.Bg("profile").select("div.about")[0];
                T(E.F.mc.addClassName("clickable"), E.Qo);
                E.F.Ud = (new Element("div")).addClassName("divAboutChange");
                E.F.mc.ins({
                    after: E.F.Ud
                });
                D.Y("profile").sm() && l.Nb.fa.tab("gifts").control.addClassName("rednote");
                T(l.Vb.addClassName("clickable"),
                    Ava.Kl)
            },
            Qo: function() {
                E.F.mc.hide();
                E.F.Ud.show();
                var a = new Element("textarea", {
                    maxlength: "300"
                });
                a.value = E.F.mc.getAttribute("data-txt").unescapeHTML();
                var b = new N("{{button_ok}}", "gray");
                E.F.Ud.C(a).ins(b);
                a.select();
                b.click(function() {
                    new p("profile/upd/about", {
                        G: this.Ud,
                        parameters: {
                            txt: $F(a)
                        },
                        J: function(a) {
                            a = a ? a : "...";
                            E.F.mc.C(Parser.Oh(Parser.Qb(a))).show();
                            E.F.mc.setAttribute("data-txt", a);
                            E.F.Ud.hide();
                            r.Jk(E.F.mc)
                        }
                    })
                })
            },
            Mk: function(a) {
                if (a) switch (t.fb(), a) {
                    case 1:
                        t.close();
                        new L(0, "success",
                            "{{profile_emblem_load_ok}}");
                        l.Le.load(f);
                        break;
                    case 2:
                        new L(0, "error", "{{profile_emblem_load_error}}")
                } else {
                    t.show("{{profile_emblem_load}}", {
                        position: "center"
                    });
                    a = new Element("iframe", {
                        id: "iframeUpload",
                        name: "iframeUpload"
                    });
                    var b = (new Element("form", {
                            target: "iframeUpload",
                            enctype: "multipart/form-data",
                            method: "POST",
                            action: "do/profile/upd/emblem"
                        })).addClassName("frmUpdateEmblem"),
                        c = new Element("input", {
                            type: "hidden",
                            name: "MAX_FILE_SIZE",
                            value: EMBLEM_MAX_FILESIZE
                        }),
                        d = new Element("input", {
                            type: "hidden",
                            name: "t_key",
                            value: l.key
                        }),
                        e = new Element("input", {
                            type: "file",
                            name: "file"
                        }),
                        m = (new N("{{button_ok}}", "gray")).click(function() {
                            b.submit();
                            t.Cb()
                        });
                    b.ins(Q("spinpt", "{{profile_emblem_file}}:")).ins(c).ins(d).ins(e).ins("<br>");
                    b.ins(Q("spinpt", "")).ins('<span class="txtgray">{{profile_emblem_file_info:' + EMBLEM_MAX_FILESIZE / 1E3 + "}}</span><br>");
                    b.ins(Q("spinpt", "")).ins(m);
                    t.C(b).ins(a)
                }
            },
            Pj: function() {
                t.show("{{profile_title}}", {
                    position: "center"
                });
                new p("profile/prefs", {
                    G: t,
                    J: function(a) {
                        var b = (new Element("div")).addClassName("divPrefs"),
                            c = new Element("form");
                        E.F.Cd = new Element("input", {
                            name: "email",
                            type: "email",
                            value: a.email,
                            placeholder: n("{{profile_email}}")
                        });
                        E.F.jh = [new W(a.soc_birth[0], W.Bh, 0, {
                            name: "soc_birth0",
                            ra: "days"
                        }), new W(a.soc_birth[1], W.Ch, 0, {
                            name: "soc_birth1",
                            ra: "months"
                        }), new W(a.soc_birth[2], W.Dh, 0, {
                            name: "soc_birth2",
                            ra: "years"
                        })];
                        E.F.pe = new Element("input", {
                            name: "soc_country",
                            "class": "half",
                            value: a.soc_country,
                            placeholder: n("{{profile_country}}")
                        });
                        E.F.pe.uk = "country";
                        E.F.oe = new Element("input", {
                            name: "soc_city",
                            "class": "half",
                            value: a.soc_city,
                            placeholder: n("{{profile_city}}")
                        });
                        E.F.oe.uk = "city";
                        E.F.Wh = new N("{{button_save}}", "gray");
                        c.ins(Q("spinpt", "{{profile_email}}:")).ins(E.F.Cd).ins("<br>");
                        c.ins(Q("spinpt", "{{profile_birthday}}:")).ins(E.F.jh[0]).ins(E.F.jh[1]).ins(E.F.jh[2]).ins("<br>");
                        c.ins(Q("spinpt", "{{profile_from}}:")).ins(E.F.pe).ins(E.F.oe).ins("<br>");
                        c.ins(E.F.Wh);
                        b.ins(c).ins(R());
                        a.mailproof ? E.F.Cd.addClassName("greennumber").disable() : (E.F.Cd.addClassName("rednumber").U("{{profile_mail_not_proofed}}"),
                            E.F.kc = (new N("", "ctrl nobg next middle", {
                                ta: 1,
                                U: "{{profile_lets_proof}}"
                            })).click(E.Fn), E.F.Cd.ins({
                                after: E.F.kc
                            }), E.F.Cd.on("keydown", E.F.kc.Ha.bind(E.F.kc, k)), E.F.Cd.on("change", E.F.kc.Ha.bind(E.F.kc, k)));
                        E.F.pe.on("keyup", E.autocomplete.curry(E.F.pe, k));
                        E.F.oe.on("keyup", E.autocomplete.curry(E.F.oe, k));
                        E.F.Wh.click(function() {
                            new p("profile/upd/prefs", {
                                parameters: c.serialize(f),
                                G: t,
                                J: function() {
                                    E.F.kc.Ha(f)
                                }
                            })
                        });
                        E.F.rc = new Element("div");
                        E.F.dm = (new Element("span")).addClassName("divPstrong pstrong" +
                            a.pstrong).C("{{profile_pass_strong." + a.pstrong + "}}");
                        E.F.ol = (new N("{{profile_change_pass}}", "gray")).click(E.So);
                        E.F.rc.ins(Q("spinpt", "{{profile_pass}}:")).ins(E.F.dm).ins("<br>").ins(E.F.ol);
                        b.ins(E.F.rc).ins(R());
                        E.F.Xn = new W(a.email_subs, Lang.select_on_off, function(a) {
                            new p("profile/mailsubs", [a], {
                                onError: this.set.bind(this, 0, f)
                            })
                        }, {
                            name: "param_email_subs"
                        });
                        E.F.Wn = new W(a.params.chat_mode, Lang.select_chat_mode, E.Pb.curry("chat_mode"), {
                            name: "param_chat_mode"
                        });
                        E.F.Un = new W(a.params.chat_ad,
                            Lang.select_chat_ad, E.Pb.curry("chat_ad"), {
                                name: "param_chat_ad"
                            });
                        E.F.Vn = new W(a.params.chat_color, W.Ah, E.Pb.curry("chat_color"), {
                            name: "param_chat_color"
                        });
                        E.F.Zn = new W(a.params.sound, Lang.select_on_off, E.Pb.curry("sound"), {
                            name: "param_sound"
                        });
                        E.F.Yn = new W(a.params.peep, Lang.select_show, E.Pb.curry("peep"), {
                            name: "param_peep"
                        });
                        E.F.bk = new W(a.params.hotkeys, Lang.select_on_off, E.Pb.curry("hotkeys"), {
                            name: "param_hotkeys"
                        });
                        E.F.Tn = new W(a.params.anims, Lang.select_on_off, E.Pb.curry("anims"), {
                            name: "param_anims"
                        });
                        b.ins(Q("spinpt", "{{profile_email_subs}}:")).ins(E.F.Xn).ins("<br>");
                        b.ins(Q("spinpt", "{{profile_chat_mode}}:")).ins(E.F.Wn).ins("<br>");
                        b.ins(Q("spinpt", "{{profile_chat_ad}}:")).ins(E.F.Un).ins("<br>");
                        b.ins(Q("spinpt", "{{profile_chat_color}}:")).ins(E.F.Vn).ins("<br>");
                        b.ins(Q("spinpt", "{{profile_show_team}}:")).ins(E.F.Yn).ins("<br>");
                        b.ins(Q("spinpt", "{{profile_notify_sound}}:")).ins(E.F.Zn).ins("<br>");
                        b.ins(Q("spinpt", "{{profile_hotkeys}}:")).ins(E.F.bk).ins("<br>");
                        b.ins(Q("spinpt", "{{profile_anims}}:")).ins(E.F.Tn).ins("<br>");
                        r.Sa(E.F.bk.B, "{{help_hotkeys}}");
                        E.F.yi = (new Element("div")).addClassName("divCommandButtons");
                        E.F.Pf = (new N("{{profile_clear_talk_history}}", "gray")).click(ta.Sl.bind(ta));
                        E.F.il = (new N("{{profile_blacklist}}", "gray")).click(E.te);
                        E.F.Bl = (new N("{{profile_unarchive}}", "gray")).click(E.Mo);
                        E.F.jl = (new N("{{profile_bridging}}", "gray")).click(E.ve);
                        E.F.yi.ins(E.F.il).ins(E.F.Pf).ins(E.F.Bl).ins(E.F.jl);
                        b.ins(R()).ins(E.F.yi);
                        t.C(b)
                    }
                })
            },
            autocomplete: function(a, b) {
                if (b) {
                    if (!$F(a)) return q.hide();
                    new p("acomplete/kladr/" +
                        a.uk, {
                            parameters: {
                                country: $F(E.F.pe),
                                city: $F(E.F.oe)
                            },
                            J: function(b) {
                                if (b.size()) {
                                    q.show("{{button_select}}", {
                                        mf: a
                                    });
                                    for (var d = 0; d < b.size(); d++) q.add(b[d].name + (b[d].In ? '<br><span class="txtgray">' + b[d].In + "</span>" : ""), a.setValue.bind(a, b[d].name))
                                } else q.hide()
                            }
                        })
                } else $F(a) != E.Jh && (clearTimeout(E.Ih), E.Ih = E.autocomplete.curry(a, f).delay(0.5), E.Jh = $F(a))
            },
            Fn: function() {
                new p("profile/mailproof");
                E.F.kc.hide()
            },
            So: function() {
                var a = new Element("input", {
                        type: "password"
                    }),
                    b = new Element("input", {
                        type: "password"
                    }),
                    c = new Element("input", {
                        type: "password"
                    });
                E.F.rc.C();
                E.F.rc.ins(Q("spinpt", "{{profile_pass_old}}:")).ins(a).ins("<br>");
                E.F.rc.ins(Q("spinpt", "{{profile_pass_new}}:")).ins(b).ins("<br>");
                E.F.rc.ins(Q("spinpt", "{{profile_pass_repeat}}:")).ins(c).ins("<br>");
                E.F.rc.ins((new N("{{button_save}}", "gray")).click(function() {
                    if ($F(b) != $F(c)) return new L(0, "warning", "{{profile_pass_err}}");
                    new p("profile/upd/pass", {
                        G: t,
                        parameters: {
                            txtPassOld: $F(a),
                            txtPassNew: $F(b)
                        },
                        J: function() {
                            E.Pj()
                        }
                    })
                }))
            },
            Nk: function(a) {
                E.params =
                    a;
                E.params.showf ? B.F.Ae.addClassName("pressed") : B.F.Ae.removeClassName("pressed");
                Game.Gm()
            },
            Pb: function(a, b) {
                new p("profile/setparam", {
                    parameters: {
                        param: a,
                        val: b
                    },
                    J: function(a) {
                        E.Nk(a)
                    }
                })
            },
            Lk: function(a) {
                Object.isArray(a) || (a = [a]);
                for (var b = 0; b < a.size(); b++) E.ca["cond" + a[b].id] = {
                    id: a[b].id,
                    Ya: a[b].val,
                    sa: a[b].dat ? moment(a[b].dat) : 0
                };
                a = (new Element("div")).addClassName("divConds");
                for (var c in E.ca)
                    if (E.ca[c].Ya && (!E.ca[c].sa || 0 < E.ca[c].sa.diff(x.na))) b = E.ca[c].id, 16 == E.ca[c].id && (b += "_" + E.ca[c].Ya),
                        E.ca[c].B = new Element("img", {
                            src: "//" + CONF_DOMAIN_IMG + "/pub/conds/" + b + ".png"
                        }), r.Sa(E.ca[c].B, function(a) {
                            return "<b>" + Lang.conds[a.id] + "</b><br>" + Ha(a.sa.diff(x.na, "seconds"))
                        }.curry(E.ca[c])), a.ins(E.ca[c].B);
                l.Ma(a)
            },
            ag: function() {
                for (var a in E.ca)
                    if (E.ca[a].B && E.ca[a].B.visible() && (!E.ca[a].Ya || !E.ca[a].sa || 0 >= E.ca[a].sa.diff(x.na))) 26 == E.ca[a].id && (new L(1, "warning", "{{item_timer_bzzz}}"), C.play("alert")), E.ca[a].B.remove(), delete E.ca[a]
            },
            Qk: function(a) {
                t.show("{{profile_vk_title}}");
                var b =
                    new eb("tabVk"),
                    c = b.add("mypage", 0, "{{profile_vk_page}}", function() {
                        new p("socnet/vk/getid", {
                            G: c,
                            J: function(a) {
                                if (a) {
                                    var d = "http://vk.com/id" + a;
                                    a = (new N(d, "link btnVkUrl")).U("{{profile_vk_linked}}").click(function() {
                                        q.show();
                                        q.add("{{profile_vk_go_page}}", function() {
                                            window.open(d, "_blank")
                                        });
                                        q.add("{{profile_vk_unlink}}", function() {
                                            new p("socnet/vk/unlink", {
                                                G: c,
                                                onComplete: b.open.bind(b, "mypage")
                                            })
                                        })
                                    });
                                    c.ins(a)
                                } else a = (new N("{{profile_vk_set}}", "gray btnVkAdd")).click(function() {
                                    window.open("https://oauth.vk.com/authorize?client_id=4561803&scope=email&redirect_uri=http://" +
                                        CONF_DOMAIN_WWW + "/do/vkauth&response_type=code&v=5.25", "_blank");
                                    t.close()
                                }), c.ins('<span class="text">{{profile_vk_not_linked}}</span>').ins(a)
                            }
                        })
                    }),
                    d = b.add("group", 0, "{{profile_vk_group}}", function() {
                        d.C((new Element("div", {
                            id: "divVkGroup"
                        })).addClassName("divVkGroup"));
                        VK.Widgets.Group("divVkGroup", {
                            mode: 0,
                            width: 300,
                            height: 250,
                            color1: "FFFFFF",
                            color2: "2B587A",
                            color3: "5B7FA6"
                        }, 28207880)
                    }),
                    e = b.add("share", 0, "{{profile_share}}", function() {
                        var a = "http://" + CONF_DOMAIN_WWW + "/?ref" + l.id;
                        e.C('<span class="text">{{profile_share_info}}</span>');
                        var b = (new N("{{profile_share_vk}}", "gray btnVkShare")).click(function() {
                                window.open("http://vk.com/share.php?url=" + a, "_blank")
                            }),
                            c = (new N("{{profile_referals}}", "gray btnVkShare")).click(E.Wj);
                        e.ins((new Element("div")).addClassName("divLinkShare").C(a)).ins(b).ins(c)
                    });
                t.ins(b);
                b.open(a ? a : "mypage")
            },
            Wj: function() {
                t.show("{{profile_referals}}");
                new p("profile/referals/list", {
                    G: t,
                    J: function(a) {
                        var b = (new Element("div")).addClassName("divReferals");
                        a && a.length ? a.each(function(a) {
                            var d = new ga(a);
                            a.paid ?
                                d.Ma("{{profile_referals_paid}}") : (a = 100 > a.extra ? new ab(a.extra, 100, {
                                    title: "{{profile_referals_activity}}"
                                }) : (new N("{{profile_referals_get_bonuses}}")).click(function() {
                                    new p("profile/referals/payme", [d.id], {
                                        G: t,
                                        J: E.Wj
                                    })
                                }), d.Ma(a));
                            b.ins(d)
                        }) : b.Ra("{{profile_referals_none}}");
                        t.ins(b).ins(R()).ins((new Element("span")).addClassName("spReferalsInfo").ins("{{profile_referals_info}}"))
                    }
                })
            },
            Mo: function() {
                t.show("{{profile_unarchive}}");
                new p("profile/unarchive/list", {
                    G: t,
                    J: function(a) {
                        var b = (new Element("div")).addClassName("divUnarchive");
                        b.ins("{{profile_unarchive_info:30}}");
                        a && a.length ? a.each(function(a) {
                            a.extra = '<span class="txtgray2">' + Ia(a.arh_date) + ", id" + a.id + "<br>" + a.arh_loctitle + "</span>";
                            var d = new bb(a, function() {
                                if (!O("{{profile_unarchive_confirm}}")) return k;
                                new p("profile/unarchive/poke", [d.id], {
                                    J: function() {
                                        d.remove()
                                    }
                                })
                            });
                            b.ins(d)
                        }) : b.ins(Q("empty"));
                        t.ins(b)
                    }
                })
            },
            te: function() {
                t.show("{{profile_blacklist}}");
                new p("profile/blacklist/list", {
                    G: t,
                    J: function(a) {
                        var b = (new Element("div")).addClassName("divBlacklist");
                        a &&
                            a.length ? a.each(function(a) {
                                b.ins(new Y(a))
                            }) : b.Ra();
                        t.C(b)
                    }
                })
            },
            ve: function() {
                t.show("{{profile_bridge_title}}");
                t.pa.ins((new N("", "circle btnHelp", {
                    ta: f,
                    title: "{{profile_bridge_manual}}"
                })).click(function() {
                    window.open("http://wiki.league17.ru/\u0418\u043d\u0441\u0442\u0440\u0443\u043a\u0446\u0438\u044f_\u043f\u043e_\u043f\u0435\u0440\u0435\u043d\u043e\u0441\u0443_\u0430\u043a\u043a\u0430\u0443\u043d\u0442\u0430", "_blank")
                }));
                E.F.yb = (new Element("div")).addClassName("divBridge");
                t.ins(E.F.yb);
                new p("profile/bridge/init", {
                    G: E.F.yb,
                    J: function(a) {
                        a ? (E.F.kl = (new N("{{profile_bridge_invent_go}}", "gray")).click(E.hl), E.F.yb.ins('<span class = "spUname">' + a.uname + "</b><br>").ins(R()), a.items ? E.F.yb.ins('<span class = "greenlabel">{{profile_bridge_invent_ok}}</span><br>') : (E.F.yb.ins("{{profile_bridge_invent_info:" + BRIDGE_WEIGHT_CAP + "}}<br>&nbsp;<br>"), E.F.yb.ins(E.F.kl)), E.F.Tc = new Element("div"), E.F.Uc = new Element("div"), E.F.ml = (new N("{{profile_bridge_chars_go}}", "gray")).click(function() {
                                E.Of($F(E.F.Gk, 0))
                            }), E.F.Gk =
                            new Element("input"), E.F.yb.ins(R()).ins("{{profile_bridge_chars_info}}<br>&nbsp;<br>"), E.F.Tc.ins(Q("spinpt", "{{profile_bridge_chars_amount}}:")).ins("<b>" + a.pokes + "</b><br>"), E.F.Tc.ins(Q("spinpt", "{{profile_bridge_chars_enter_id}}:")).ins(E.F.Gk).ins("<br>"), E.F.Tc.ins(E.F.ml), E.F.yb.ins(E.F.Tc).ins(E.F.Uc)) : E.F.yb.C("{{profile_bridge_none}}")
                    }
                })
            },
            hl: function() {
                O("{{profile_bridge_invent_confirm}}") && new p("profile/bridge/items", {
                    G: E.F.yb,
                    onComplete: E.ve
                })
            },
            Of: function(a, b, c) {
                a && (E.F.Tc.hide(),
                    E.F.Uc.show(), new p("profile/bridge/pokes", [a, b ? 1 : 0, c ? 1 : 0], {
                        G: E.F.Uc,
                        J: function(c) {
                            if (b) E.ve();
                            else {
                                E.F.Uc.C('<span class="pokename">#' + ya(c.sp_id) + " " + c.title + "</span>");
                                var e = (new N("{{profile_bridge_chars_binded:" + c.price + "}}", "gray")).click(E.Of.curry(a, f, f)),
                                    m = (new N("{{profile_bridge_chars_unbinded:" + 5 * c.price + "}}", "gray")).click(E.Of.curry(a, f, k)),
                                    g = (new N("{{button_cancel}}", "gray")).click(function() {
                                        E.F.Tc.show();
                                        E.F.Uc.hide()
                                    });
                                E.F.Uc.ins(e).ins(c.price && !c.binded ? m : "").ins(g)
                            }
                        },
                        onError: E.ve
                    }))
            }
        }),
        Bb = 1, yb = Class.create({
            initialize: function() {
                this.B = (new Element("div")).addClassName("divQuests");
                this.$f = 0;
                this.fh = [];
                this.xi = (new Element("div")).addClassName("divQuestsCats");
                this.ha = (new Element("div")).addClassName("divQuestsList");
                this.xb = [(new N("{{diary_quest_all}}", "gray")).click(this.Ga.bind(this, 0)), (new N("{{diary_quest_performed}}", "gray")).click(this.Ga.bind(this, Bb)), (new N("{{diary_quest_completed}}", "gray")).click(this.Ga.bind(this, 2))];
                this.xi.ins(this.xb[0]).ins(this.xb[1]).ins(this.xb[2]);
                this.B.ins(this.ha).ins(R()).ins(this.xi)
            },
            Ga: function(a) {
                this.$f = a;
                this.xb.invoke("removeClassName", "pressed");
                this.xb[a].addClassName("pressed");
                a ? (this.ha.select(".quest").invoke("hide"), this.ha.select(".stage" + a).invoke("show")) : this.ha.select(".quest").invoke("show");
                this.ha.select(".quest").invoke("removeClassName", "maximized")
            },
            load: function() {
                new p("diary/quests/load", {
                    G: this.ha,
                    J: function(a) {
                        this.fh = [];
                        for (var b = 0; b < a.length; b++) {
                            var c = new Ab(a[b]);
                            this.fh[c.id] = c;
                            this.ha.ins(c)
                        }
                        this.Ga(this.$f);
                        globalCallbacks.showquestindiary && (this.fh[globalCallbacks.showquestindiary].toggle(), globalCallbacks.showquestindiary = 0)
                    }.bind(this)
                })
            }
        }), Ab = Class.create({
            initialize: function(a, b) {
                this.id = a.id;
                this.title = a.title;
                this.qh = a.stage;
                this.B = (new Element("div")).addClassName("quest stage" + this.qh);
                this.vc = (new Element("div")).addClassName("imgquest");
                this.vc.setStyle({
                    backgroundPosition: -135 * this.id + "px " + (0 < this.qh || b ? "-95px" : "0px")
                });
                this.qh && T(this.vc, this.toggle.bind(this));
                b || r.Sa(this.vc, this.title);
                this.B.ins(this.vc);
                return this
            },
            toggle: function() {
                r.hide();
                this.B.hasClassName("maximized") ? yb.Ga(yb.$f) : (yb.ha.select(".quest").invoke("hide"), this.B.show().addClassName("maximized"), this.load());
                return this
            },
            load: function() {
                this.Vd || (this.pa = (new Element("div")).addClassName("title").C(this.title), this.Vd = (new Element("div")).addClassName("phases"), this.B.ins(this.pa).ins(this.Vd));
                new p("diary/quests/phases", [this.id], {
                    G: this.Vd,
                    J: function(a) {
                        for (var b = 0; b < a.length; b++) {
                            var c = a[b].descr,
                                c = Parser.Fb(c),
                                c = Parser.ea(c),
                                c = Parser.eb(c),
                                c = (new Element("div")).addClassName("phase stage" + a[b].stage).C(c);
                            a[b].cstyle && c.addClassName("cstyle_" + a[b].cstyle);
                            if (a[b].percent) {
                                var d = new ab(a[b].val, a[b].percent);
                                c.ins(d)
                            } else Object.isString(a[b].val) && (d = moment(a[b].val), d = d.isBefore(x.na) ? "{{date_now}}" : d.from(x.na), c.ins(" ").ins((new Element("span")).addClassName("timer").C(d)).ins("."));
                            a[b].img && (d = (new Element("img", {
                                    src: "//" + CONF_DOMAIN_IMG + "/pub/quests/phases/" + this.id + "_" + a[b].id + ".jpg"
                                })).addClassName("imgphase"),
                                c.ins(d));
                            a[b].stage == Bb && a[b].spoiler && (d = new N("H E L P", "nobg spoiler", {
                                ta: f
                            }), d.click(this.mk.bind(this, d, a[b].id)), c.ins(d));
                            this.Vd.ins(c)
                        }
                        Parser.go();
                        47 != this.id && (this.Vd.scrollTop = 65E3)
                    }.bind(this)
                });
                return this
            },
            toElement: ba("B"),
            mk: function(a, b) {
                r.Sd().show(a, "{{diary_quest_spoiler_title}}");
                new p("diary/quests/spoilershow", [this.id, b], {
                    G: r,
                    J: function(c) {
                        if (c)
                            if ("locked" == c) {
                                var d = (new Element("div")).addClassName("lock");
                                r.content(d);
                                r.T("{{diary_quest_spoiler_unlock}}", function() {
                                    new p("diary/quests/spoilerunlock",
                                        [this.id, b], {
                                            G: r,
                                            J: this.mk.bind(this, a, b)
                                        })
                                }.bind(this), f)
                            } else if (+c == c) {
                            var e = (new Element("div", {
                                id: "tmptimer" + Math.random()
                            })).addClassName("timer");
                            r.content(e);
                            (function g() {
                                if ($(e.id) && r.visible()) {
                                    var a = c - x.$n();
                                    0 < a ? (e.C(Ha(a)), g.delay(1)) : r.Ia()
                                }
                            })()
                        } else c = Parser.Fb(c), c = Parser.ea(c), c = Parser.eb(c), r.content(c), Parser.go()
                    }.bind(this)
                })
            }
        }), na = Class.create({
            initialize: function() {
                this.params = {
                    isLoadWilds: k,
                    isFirstLoad: f
                };
                this.Pk = $H({
                    loc: $("divVisioLoc"),
                    trade: $("divVisioTrade").hide(),
                    fight: $("divVisioFight").hide()
                });
                this.Ef = this.wk = 0
            },
            start: function() {
                this.refresh();
                this.ej()
            },
            ej: function() {
                this.Ef && Math.floor(Date.now() / 1E3) - this.Ef > 6 * REFRESH_TIME && (Game.paused || (this.params.isFirstLoad = f), this.refresh());
                this.ej.bind(this).delay(REFRESH_HEARTBEAT)
            },
            refresh: function() {
                if (Math.floor(Date.now() / 1E3) - this.Ef < REFRESH_TIME) return this.Zj();
                var a = {},
                    b;
                for (b in this.params)
                    if (this.params[b] && ("isLoadWilds" != b || this.Dg("loc").visible())) a[b] = this.params[b];
                new p("refresher", {
                    parameters: a,
                    J: function(a) {
                        this.params.isFirstLoad &&
                            (oa.start(), this.params.isFirstLoad = k, u.stop());
                        if (!a) return k;
                        if (!CONF_DEV && a.ver && a.ver !== VERSION) return Game.reload.curry("updver").delay(P(1, 50) / 10);
                        if (a.libs)
                            for (var b in a.libs) ha.fill(b, a.libs[b]);
                        a.notify && (a.notify.talk && D.Y("talks").Ob(a.notify.talk), a.notify.gift && D.Y("profile").Ob(a.notify.gift));
                        if (a.boosts)
                            for (b = 0; b < a.boosts.size(); b++) "tradeableloc" != a.boosts[b].boost && new L(1, "boost", "{{boosts." + a.boosts[b].boost + ":" + a.boosts[b].val + "}}" + a.boosts[b].datEnd);
                        a.trade && z.C(a.trade);
                        a.fight &&
                            y.C(a.fight);
                        a.conds && E.Lk(a.conds);
                        a.params && E.Nk(a.params);
                        a.blacklist && B.Yi(a.blacklist);
                        a.nohp && new L(M, "warning", "{{team_no_hp}}");
                        a.loc && (a.loc.id && a.loc.id != I.Ca.id) && I.load();
                        l.cb && (a.trade || a.fight) && Vight.od();
                        a.zvote && pa.fill(a.zvote)
                    }.bind(this),
                    onComplete: function() {
                        this.Zj();
                        this.Ef = Math.floor(Date.now() / 1E3)
                    }.bind(this)
                })
            },
            Zj: function() {
                clearTimeout(this.wk);
                this.wk = this.refresh.bind(this).delay(REFRESH_TIME)
            },
            Dg: function(a) {
                return this.Pk.get(a)
            },
            nf: function(a) {
                this.Pk.each(function(b) {
                    a ==
                        b.key ? b.value.show() : b.value.hide()
                })
            },
            df: function() {
                if (l.dc) return J.close(), Ra(), this.nf("trade");
                if ((l.cc || l.Bb || l.cb) && !this.Dg("fight").visible()) J.close(), Ra(), this.nf("fight"), y.wj.Dc();
                else if (!this.Dg("fight").visible()) return this.nf("loc")
            }
        }), nb = [
            ["smile", ":)"],
            ["wink", ";)"],
            ["sad", ":("],
            ["cray", "T_T"],
            ["blum", ":-P"],
            ["shok", "O_O"],
            ["cool", "8-)"],
            ["blush", ":-["],
            ["biggrin", ":-D"],
            ["kissyou", ":-*"],
            ["wacko", "%)"],
            ["bad", ":-!"],
            ["secret", ":-X"],
            ["rose", "@}--"],
            ["diablo", "]:|"],
            ["bomb", "@="],
            ["fool", ":-|"],
            ["kiss", "[KISSING]"],
            ["rofl", "[ROFL]"],
            ["kissed", "[KISSED]"],
            ["hmm", "[HMM]"],
            ["bravo", "[BRAVO]"],
            ["crazy", "[CRAZY]"],
            ["acute", "[ACUTE]"],
            ["mocking", "[JOKINGLY]"],
            ["ireful", "[IREFUL]"],
            ["kisscrazy", "[IN_LOVE]"],
            ["yes", "[YES]"],
            ["nea", "[NO]"],
            ["good", "[THUMBS_UP]"],
            ["ok", "[OK]"],
            ["scratch", "[SCRATCH]"],
            ["drinks", "[DRINK]"],
            ["congrat", "[CONGRAT]"],
            ["dance", "[DANCE]"],
            ["wall", "[WALL]"],
            ["aggress", "[AGR]"],
            ["mad", "[MAD]"],
            ["hi", "[HI]"],
            ["bye", "[BUY]"],
            ["scare", "[BOO]"],
            ["iamso", "[IAMSO]"],
            ["run", "[RUN]"],
            ["hide", "[TANUVAS]"],
            ["zzz", "[ZZZ]"],
            ["tada", "[TADA]"],
            ["stop", "[STOP]"],
            ["yahoo", "[YAHOO]"],
            ["shout", "[SHOUT]"],
            ["unknown", "[DONT_KNOW]"],
            ["timeout", "[TIMEOUT]"],
            ["wacko2", "[WACKO]"],
            ["sorry", "[SORRY]"],
            ["sos", "[SOS]"],
            ["search", "[SEARCH]"],
            ["roll", "[ROLL]"],
            ["haha", "[XEXE]"],
            ["gamer", "[GAMER]"],
            ["flag", "[FLAG]"],
            ["empathy", "[DONTWORRY]"],
            ["boring", "[BORING]"],
            ["heart", "[HEART]"],
            ["pardon", "[PARDON]"],
            ["nyam", "[NYAM]"],
            ["new_russ", "[ROCK]"],
            ["music", "[MUSIC]"],
            ["mamba", "[MAMBA]"],
            ["mail", "[MAIL]"],
            ["lol", "[LOL]"],
            ["angel", "[ANGEL]"],
            ["aggh", "[AGGH]"],
            ["dancee", "[DANCEE]"],
            ["tap", "[TAP]"],
            ["aw", "[AW]"],
            ["here", "[HERE]"],
            ["bully", "[BULLY]"],
            ["ahgm", "[AHGM]"],
            ["facepalm", "[FPALM]"],
            ["pball", "[PBALL]"],
            ["twiddle", "[TWIDDLE]"],
            ["popcorn", "[POPCORN]"],
            ["g_pardon", "[GPARDON]"],
            ["g_flirt", "[GFLIRT]"],
            ["g_haha", "[GHAHA]"],
            ["g_bye", "[GBUY]"],
            ["g_curtsey", "[GCURTSEY]"],
            ["g_cool", "[GCOOL]"],
            ["hugs", "[HUGS]"]
        ], X = 0; X < nb.size(); X++) {
    var cc = nb[X][1],
        cc = cc.replace(/([)(\-\[\]*\|])/g, "\\$1");
    nb[X][2] = RegExp(cc, "g")
}
var wb = Class.create(V, {
        initialize: function($super, b, c, d) {
            $super();
            var e = 0,
                m = 0,
                g = Lang.f_status[b];
            if (!g) return k;
            13 == b && (g = g[1 == c ? 0 : 1] + (1 == c ? "" : d));
            21 == b && (g += " - " + n("{{typ." + c + "}}" + (d ? " {{typ." + d + "}}" : "")));
            26 == b && (g += c);
            36 == b && (g = g[6 == c ? 0 : 1]);
            42 == b && (g += " - " + n("{{typ." + c + "}}"));
            55 == b && (g += " " + c + "/" + FIGHT_INV_CAP);
            59 == b && (g += c);
            this.B = (new Element("div")).addClassName("statuscontainer");
            e = 15 * -b;
            1 <= b && (7 >= b && c) && (m = 0 < c ? -15 : -30);
            13 == b && 2 == c && (m = -15);
            100 <= b && (m = -45, e = 15 * -(b - 100));
            this.ab = (new Element("div")).addClassName("statusimg").setStyle({
                backgroundPosition: e +
                    "px " + m + "px"
            });
            1 <= b && (7 >= b && c) && (this.ab.C('<span class="' + (0 <= c ? 'greennumber">' : 'rednumber">') + Math.abs(c) + "</span>"), g += " " + Ba(c));
            this.B.C(this.ab);
            r.Sa(this.B, g);
            return this
        },
        ins: function(a) {
            this.B.ins(a);
            return this
        }
    }),
    ta = Class.create({
        initialize: function() {
            this.Cf = [];
            this.G = k;
            this.page = 1;
            this.Eb = [];
            this.Fo = 0;
            this.Wc = (new Element("div")).addClassName("divTalkControls");
            this.Nd = new N("{{talks_filter_unread}}", "gray small");
            this.Md = new N("{{talks_filter_favorites}}", "gray small");
            this.Nd.click(this.filter.bind(this,
                this.Nd));
            this.Md.click(this.filter.bind(this, this.Md));
            this.Wc.ins(this.Nd).ins(this.Md);
            this.ha = Xa((new Element("div")).addClassName("divTalkList"), this.load.bind(this, f));
            D.add("talks", this.load.bind(this)).ins(this.ha).ins(this.Wc);
            this.$i()
        },
        load: function(a) {
            if (!this.G) {
                if (a) {
                    if (!this.page) return;
                    var b = new Element("div");
                    this.ha.ins(b)
                } else this.page = 1;
                this.G = f;
                var c = 0;
                this.Nd.hasClassName("pressed") && (c = 1);
                this.Md.hasClassName("pressed") && (c = 2);
                new p("talks/load", {
                    aa: [this.page, 0, c],
                    parameters: {
                        readed: this.Eb.join(",")
                    },
                    G: a ? b : this.ha,
                    J: function(c) {
                        D.Y("talks").Ob(c.unread);
                        this.Cf = [];
                        a && b.remove();
                        !c.talks || !c.talks.size() ? (a || this.ha.Ra(), this.page = 0) : (c.talks.each(function(a) {
                            var b = new ga(a.user);
                            T(b.B, b.openDialog.bind(b));
                            b.T("---");
                            b.T(a.favorite ? "{{talk_favorite_del}}" : "{{talk_favorite_add}}", this.bo.bind(this, b));
                            b.qa("favorite", a.favorite);
                            b.T("{{talk_del_talk}}", this.del.bind(this, b));
                            b.sa = (new Element("div")).addClassName("dat txtgray").C(Ia(a.dat));
                            b.ins(b.sa);
                            a.readed = a.readed || !a.mine && this.Eb.include(b.id);
                            a.txt = n(a.txt);
                            a.txt = ((a.mine ? "<i>{{you}}</i>: " : "") + a.txt).truncate(40);
                            B.Kg(b.id) && (a.txt = '<span class="redtext">{{blacklist_inlist}}</span>');
                            b.Ma(a.txt);
                            b.qa("unreaded", !a.readed).qa("notmine", !a.mine);
                            this.ha.ins(b);
                            this.Cf[b.id] = b
                        }.bind(this)), this.page++)
                    }.bind(this),
                    onComplete: function() {
                        this.G = k
                    }.bind(this)
                })
            }
        },
        filter: function(a) {
            a.hasClassName("pressed") ? a.removeClassName("pressed") : (this.Nd.removeClassName("pressed"), this.Md.removeClassName("pressed"), a.addClassName("pressed"));
            this.load()
        },
        bo: function(a) {
            var b = a.hasClassName("favorite") ? 0 : 1;
            new p("talks/setfavorite", [a.id, b], {
                J: function() {
                    a.qa("favorite", b);
                    for (var c = 0; c < a.pb.length; c++)
                        if (a.pb[c][0] == (b ? "{{talk_favorite_add}}" : "{{talk_favorite_del}}")) a.pb[c][0] = b ? "{{talk_favorite_del}}" : "{{talk_favorite_add}}"
                }.bind(this)
            })
        },
        vf: function(a, b, c) {
            b && !this.Eb.include(a) && this.Eb.push(a);
            !b && this.Eb.include(a) && (this.Eb = this.Eb.without(a));
            D.Y("talks").Va() && this.Cf[a] && (a = this.Cf[a], b && (a.hasClassName("unreaded") && a.hasClassName("notmine")) &&
                (a.removeClassName("unreaded"), D.Y("talks").Ob(-1, f)), !b && !a.hasClassName("unreaded") && (a.addClassName("unreaded notmine"), D.Y("talks").Ob(1, f)), c && (this.ha.ins({
                    top: a
                }), a.sa.C(Ia(x.na)), a.Ma(c)))
        },
        $i: function() {
            this.Eb.length && oa.emit("flushreaded", this.Eb);
            this.Eb = [];
            this.Fo = this.$i.bind(this).delay(3)
        },
        del: function(a) {
            a.hasClassName("unreaded") && a.hasClassName("notmine") && D.Y("talks").Ob(-1, f);
            B.areas["u" + a.id] && B.areas["u" + a.id].close();
            a.remove();
            new p("talks/del", [a.id])
        },
        Sl: function() {
            O("{{talk_clear_confirm}}") &&
                (E.F.Pf && E.F.Pf.hide(), new p("talks/del", [0], {
                    J: function() {
                        new L(M, "success", "{{talk_clear_success}}");
                        D.Y("talks").Ob(0).ka()
                    }
                }))
        }
    }),
    z = Class.create({
        initialize: function() {
            this.Tb = this.Og = this.jf = 0;
            this.Ka = (new N("...", "")).click(this.accept);
            S || r.Sa(this.Ka, "{{trade_warning}}", f, "white danger");
            this.Qh = (new N("{{button_cancel}}", "red")).click(this.cancel);
            $("divTradeButtons").ins(this.Ka).ins("<br>").ins(this.Qh)
        },
        C: function(a) {
            if (a) {
                if (this.Og > a.lastupd) return;
                l.dc = f;
                this.Og = a.lastupd;
                if (a.trainer1.trainer.id ==
                    l.id) {
                    var b = a.trainer1;
                    a = a.trainer2
                } else b = a.trainer2, a = a.trainer1;
                (z.jf = b.accept) ? (z.Ka.C("{{trade_cancel}}"), z.Ka.disable(0)) : (z.Ka.C("."), z.Ka.disable(1), clearTimeout(z.Tb), z.oi());
                z.Ka.C(z.jf ? "{{trade_cancel}}" : "...");
                z.Ka.disable(!z.jf);
                for (var c = 0, d = "I"; 1 >= c; c++, d = "H", b = a) $("divTradeContainer" + d).select(".trainerbig").invoke("remove"), $("divTrade" + d).C().ins({
                    before: new ga(b.trainer)
                }), b.accept ? $("divTrade" + d).addClassName("accepted") : $("divTrade" + d).removeClassName("accepted"), b.items.items.each(function(a) {
                    a =
                        (new Pb(a, "I" == d)).size("upfull");
                    $("divTrade" + d).ins(a)
                }), b.pokes.pokes.each(function(a) {
                    a.master ? a.tip = "<b>{{trade_master}}</b>" + ("H" == d ? "<br>{{trade_master_info}}" : "") : (a.tip = "<b>{{trade_temp}}</b>" + ("H" == d ? "<br>{{trade_temp_info}}" : ""), a.extra = "{{trade_temp_info}}");
                    a = new bb(a, "I" == d ? z.Yj.curry(a.lot_id, 0) : kb.curry(a.id, "trade"), {
                        size: 1
                    });
                    $("divTrade" + d).ins(a)
                })
            } else {
                if (!l.dc) return;
                l.dc = k;
                D.Y("items").ka();
                D.Y("pokes").ka()
            }
            na.df()
        },
        accept: function() {
            new p("trade/accept", [z.jf ? 0 : z.Og], {
                G: z.Ka,
                J: function(a) {
                    z.C(a)
                }
            })
        },
        cancel: function() {
            new p("trade/cancel", {
                G: z.Qh,
                J: function() {
                    z.C(0)
                }
            })
        },
        Fh: function(a) {
            new p("trade/add_item", [a.id, a.Oe()], {
                G: r,
                J: function(b) {
                    z.C(b);
                    a.Im(-a.Oe())
                },
                onComplete: function() {
                    r.Ia()
                }
            })
        },
        re: function(a, b) {
            new p("trade/add_poke", [a.id, b], {
                J: function(b) {
                    z.C(b);
                    a.remove()
                }
            })
        },
        Yj: function(a, b) {
            new p("trade/remove_lot", [a], {
                J: function(a) {
                    z.C(a);
                    b ? D.Y("items").ka() : D.Y("pokes").ka()
                }
            })
        },
        oi: function() {
            l.dc && (z.Ka.disabled && "." == z.Ka.title[0]) && (z.Ka.C(z.Ka.title + "."), 12 <=
                z.Ka.title.length ? (z.Ka.C("{{trade_accept}}"), z.Ka.disable(0)) : z.Tb = z.oi.delay(0.3))
        }
    }),
    Y = Class.create(V, {
        initialize: function($super, b, c) {
            if (!b) return "";
            $super(c);
            this.id = +(b.id || b.id || b[0]);
            this.ga = b.uname || b.ga || b[1] || "";
            this.Ed = +(b.ugroup || b.Ed || b[2] || 0);
            this.Fb = b.sex || b.Fb || b[3] || 0;
            this.ee = +(b.karma || b.ee || b[4] || 0);
            this.Za = +(b.clan_id || b.Za || b[5] || 0);
            this.Me = b.events || b.Me || b[6] || 0;
            this.lc = +(b.clan_is_lead || b.lc || 0);
            this.Xg = +(b.online || b.Xg || 0);
            this.Hd = b.avaf || b.Hd || 0;
            this.Nb = {};
            this.B = (new Element("div")).addClassName("trainer" +
                (this.Xg ? " online" : "") + " id" + this.id);
            this.Nh = (new N("", "btnInfo sex" + this.Fb)).click(this.Vg.bind(this));
            this.B.ins(this.Nh);
            this.Ad = (new Element("div")).C(this.ga);
            S ? T(this.Ad, this.Vg.bind(this)) : T(this.Ad, this.Ck.bind(this));
            this.Ad.on("dblclick", this.xf.bind(this));
            this.Ad.className = "label ugroup" + this.Ed;
            this.B.ins(this.Ad);
            this.Me && "paint" == this.Me.name && Events.paintedTrainer({
                id: this.id,
                painted: this.Me.val
            });
            Events.Kj(this);
            this.Za && (this.fl = new qb(this.Za, 0, f), this.B.ins(this.fl));
            this.ee &&
                (this.gl = new Element("div", {
                    "class": "karma" + this.Ag(),
                    title: n("{{karma." + (this.Ag() + 1) + "}}")
                }), this.B.ins(this.gl));
            this.pb = [];
            return this
        },
        Ag: function() {
            return this.ee >= PK_KARMA.abs() ? 1 : this.ee <= PK_KARMA ? -1 : 0
        },
        openDialog: function() {
            B.Zg(this)
        },
        Ck: function() {
            Ka("chat");
            B.Fc(this.ga)
        },
        Vg: function() {
            if (this.id)
                if (this.params.Ac) this.xf();
                else {
                    q.show(this.ga);
                    q.add("{{user_menu_card}}", this.xf.bind(this));
                    l.id != this.id && (S && q.add("{{user_menu_chat}}", this.Ck.bind(this)), q.add("{{user_menu_private_mess}}",
                        this.openDialog.bind(this)), q.add("{{user_menu_battle}}", function() {
                        q.show("{{user_menu_battle}} " + this.ga);
                        q.add("{{user_menu_battle_call}}", this.call.bind(this, Cb));
                        q.add("{{user_menu_battle_conditions}}", this.call.bind(this, Db));
                        q.add("{{user_menu_battle_hijack}}", this.call.bind(this, 2));
                        l.Za && this.Za && q.add("{{user_menu_battle_domin}}", this.call.bind(this, 3))
                    }.bind(this)), q.add("{{user_menu_trade}}", this.ac.bind(this, "trade", 0)), q.add("{{user_menu_breed}}", function() {
                        F.show("{{user_menu_breed_select}}",
                            "breedable",
                            function(a) {
                                this.ac("breed", {
                                    p_id: a
                                })
                            }.bind(this))
                    }.bind(this)));
                    l.lc && q.add("{{user_menu_clan}}", function() {
                        q.show("{{user_menu_clan}} " + this.ga);
                        new p("trainers/get_clan", [this.id], {
                            G: q,
                            J: function(a) {
                                l.Za === a.clan_id ? (q.add("{{user_menu_clan_rank}}", function() {
                                    q.show("{{user_menu_clan_rank}} " + this.ga);
                                    new p("clan/ranklist", {
                                        G: q,
                                        J: function(a) {
                                            a && a.size() && a.each(function(a) {
                                                q.add("<b>#" + a.id + "</b> " + a.title, Clan.Aj.curry(this, a.id))
                                            }, this);
                                            q.add("<b>{{user_menu_clan_rank_reset}}</b>",
                                                Clan.Aj.curry(this, 0))
                                        }.bind(this)
                                    })
                                }.bind(this)), q.add(a.is_lead ? "{{user_menu_clan_leader_del}}" : "{{user_menu_clan_leader_add}}", Clan.ln.curry(this, a.is_lead ? 0 : 1)), q.add("{{user_menu_clan_drop}}", Clan.nn.curry(this))) : 0 == a.clan_id ? q.add("{{user_menu_clan_invite}}", this.ac.bind(this, "clan", 0)) : q.add("{{user_menu_clan_card}}", Clan.Rc.curry(a.clan_id))
                            }.bind(this)
                        })
                    }.bind(this));
                    l.id != this.id && q.add("{{user_menu_more}}", function() {
                        q.show(this.ga);
                        new p("trainers/relations", [this.id], {
                            G: q,
                            J: function(a) {
                                a.is_friend ?
                                    q.add("{{friend_del}}", this.Tl.bind(this)) : q.add("{{friend_add}}", this.ac.bind(this, "friend", 0));
                                q.add(a.is_blacklist ? "{{blacklist_del}}" : "{{blacklist_add}}", this.ao.bind(this, !a.is_blacklist));
                                a.is_snowable && q.add("{{events_snow_throw}}", Events.Co.bind(Events, this.id));
                                if (a.is_paintable)
                                    for (var c = 0; c < a.is_paintable.size(); c++) q.add("{{events_paint_throw}}", Events.Bo.bind(Events, this.id, a.is_paintable[c]), {
                                        ra: "event_painted" + a.is_paintable[c]
                                    })
                            }.bind(this)
                        })
                    }.bind(this));
                    Compete.bc() && Compete.af &&
                        (q.add("---"), q.add("{{compete_user_menu_lap_add}}", Compete.Ym.bind(Compete, this)));
                    for (var a = 0; a < this.pb.length; a++) q.add(this.pb[a][0], this.pb[a][1], this.pb[a][2])
                }
        },
        T: function(a, b, c) {
            this.pb.push([a, b, c]);
            return this
        },
        tf: function(a) {
            a && (this.Hd = a);
            this.Kh = "//" + CONF_DOMAIN_IMG + "/dyn/usrava/" + Math.floor(this.Hd / 1E3) + "/" + this.id + "_" + this.Hd + ".png";
            this.Hd && (this.Vb || (this.Vb = (new Element("div")).addClassName("ava"), this.B.ins(this.Vb)), this.Vb.style.backgroundImage = "url(" + this.Kh + ")")
        },
        ac: function(a,
            b) {
            b || (b = {});
            new p("inviter/send/" + a, [this.id], {
                parameters: b
            })
        },
        call: function(a) {
            switch (a) {
                case Cb:
                    this.ac("fight");
                    break;
                case Db:
                    a = (new Element("div")).addClassName("divCall");
                    var b = Ua(new Element("input", {
                            type: "text",
                            value: 6
                        }), "unsign int"),
                        c = Ua(new Element("input", {
                            type: "text",
                            value: 6
                        }), "unsign int"),
                        d = Ua(new Element("input", {
                            type: "text",
                            value: 100
                        }), "unsign int"),
                        e = new Element("input", {
                            type: "checkbox",
                            value: 1
                        });
                    t.show("{{user_menu_battle_conditions}} <b>" + this.ga + "</b>");
                    a.ins(Q("spinpt", "{{fight_conds.1}}")).ins(b).ins("<br>");
                    a.ins(Q("spinpt", "{{fight_conds.2}}")).ins(c).ins("<br>");
                    a.ins(Q("spinpt", "{{fight_conds.3}}")).ins(d).ins("<br>");
                    a.ins(Q("spinpt", "{{fight_conds.4}}")).ins(e).ins("<br>");
                    a.ins(Q("spinpt", "")).ins("<br>");
                    var m = (new N("{{button_ok}}", "gray")).click(function() {
                        this.ac("fight", {
                            isConditions: 1,
                            optSlotCap: $F(b),
                            optPokesCap: $F(c),
                            optLevelCap: $F(d),
                            optNoItems: e.checked ? 1 : 0
                        });
                        t.close()
                    }.bind(this));
                    t.ins(a).ins(m).wc();
                    break;
                case 2:
                case 3:
                    y.start(a, this.id);
                    break;
                case Eb:
                    this.ac("fight", {
                        isGym: 1
                    });
                    break;
                case tb:
                    this.ac("fight", {
                        isCompete: 1,
                        lap: this.$m
                    })
            }
        },
        Tl: function() {
            new p("trainers/friend_remove", [this.id], {
                J: function(a) {
                    a && new L(M, "success", "{{friend_deleted}}")
                }
            });
            w.close()
        },
        ao: function(a) {
            O("{{blacklist_add_confirm}}") && new p("profile/blacklist/set", [this.id, a ? 1 : 0], {
                J: function(a) {
                    B.Yi(a);
                    new L(M, "success", "{{blacklist_saved}}");
                    w.close();
                    B.areas["u" + this.id] && B.areas["u" + this.id].close();
                    ta.vf(this.id, 1)
                }.bind(this)
            })
        },
        xf: function() {
            this.id && (w.open("divTrainerCard"), this.Rc($("divTrainerCard")))
        },
        Rc: function(a, b) {
            if (this.id) {
                var c = (new Element("div")).addClassName("trainercard");
                a.C(c);
                new p("trainers/card", [this.id], {
                    G: c,
                    J: function(a) {
                        this.ee = a.karma;
                        a.awards_amount = a.awards.size();
                        c.C();
                        this.Vb = (new Element("div")).addClassName("ava");
                        this.tf(a.avaf);
                        a.clan && (a.clan.title = "<b>" + a.clan.title + "</b>" + (a.clan.is_lead ? ", {{usercard_leader}}" : "") + "<br>", a.clan.rank && (a.clan.title += '<span style="color: gray">' + a.clan.rank + "</span><br>"), a.clan.title += '<span class="' + Ba(a.clan.rate) + "</span>", this.Vb.ins(new qb(a.clan.id,
                            a.clan.title)));
                        var e = (new Element("div")).addClassName("buttons");
                        if (a.soc_vk) {
                            var m = (new N("", "ctrl nobg btnVk", {
                                ta: 1
                            })).click(function() {
                                window.open("//vk.com/id" + a.soc_vk, "_blank")
                            }.bind(this));
                            e.ins(m)
                        }
                        var m = (new Element("div")).addClassName("info"),
                            g = (new Element("div")).addClassName("position").C("<span>" + a.region + "</span>"),
                            v = "";
                        a.online ? (g.addClassName("online"), a.is_friend && (v = a.loc)) : (g.addClassName("offline"), v = moment(a.activity).from(x.na));
                        v && g.U(v);
                        v = new ga(a);
                        v.Ma(a.socstatus, "socstatus ugroup" +
                            a.ugroup);
                        var G = (new Element("div")).addClassName("params txtgray");
                        G.ins('<span class="gametime txtgray2">{{usercard_gametime:' + Ja(a.regdate) + "}}</span>");
                        G.ins('<span class="block dex"><span class="val txtgray2">' + a.dex + "</span>{{usercard_dex}}</span>");
                        G.ins('<span class="block dexs"><span class="val txtgray2">' + a.dexs + "</span>{{usercard_dexs}}</span>");
                        G.ins('<span class="block karma"><span class="val txtgray2 karma' + this.Ag() + '">' + (0 < a.karma ? "+" : "") + a.karma + "</span>{{usercard_karma}}</span>");
                        var U = (new Element("div", {
                            "data-txt": a.about
                        })).addClassName("about");
                        a.is_blacklist ? U.C("{{blacklist_inlist}}").addClassName("inblacklist") : U.C(Parser.Oh(Parser.Qb(a.about)));
                        this.Nb.fa = new eb("right");
                        var ka = this.Nb.fa.add("awards", 1, "{{usercard_awards:" + a.awards_amount + "}}");
                        ka.ins(G);
                        if (a.awards_amount) {
                            var qa = (new Element("div")).addClassName("divBadges");
                            a.awards.each(function(a) {
                                a = new Z(a);
                                200 != a.ja ? ka.ins(a) : qa.ins(a.size("upfull"))
                            });
                            qa.empty() || ka.ins(qa)
                        }
                        var ra = this.Nb.fa.add("friends",
                                2, "{{usercard_friends:" + a.friends_amount + "}}",
                                function() {
                                    new p("trainers/list/friends", [a.id], {
                                        G: ra,
                                        J: function(a) {
                                            var b = (new Element("div")).addClassName("divFriendList");
                                            a && a.length ? a.each(function(a) {
                                                b.ins(new Y(a))
                                            }) : b.Ra();
                                            ra.C(b)
                                        }
                                    })
                                }),
                            fa = this.Nb.fa.add("achives", 3, "{{usercard_achives:" + a.achives_amount + "}}", function() {
                                new p("trainers/achives", [a.id], {
                                    G: fa,
                                    J: function(a) {
                                        a && a.length ? a.each(function(a) {
                                            fa.ins(new gb(a))
                                        }) : fa.Ra()
                                    }.bind(this)
                                })
                            }.bind(this)),
                            la = this.Nb.fa.add("gifts", 4, "{{usercard_gifts:" +
                                a.gifts_amount + "}}",
                                function() {
                                    new p("gift/trainers", [a.id], {
                                        G: la,
                                        J: function(a) {
                                            this.id !== l.id ? la.C((new N("{{gift_present}}", "btnGift gray")).click(Lb.ko.curry(this))) : D.Y("profile").Ob();
                                            a && a.length && a.each(function(a) {
                                                la.ins(new Lb(a))
                                            })
                                        }.bind(this)
                                    })
                                }.bind(this)),
                            jb = this.Nb.fa.add("wishes", 5, "{{usercard_wishes}}", function() {
                                new p("trainers/wishes", [a.id], {
                                    G: jb,
                                    J: function(a) {
                                        if (a && a.length)
                                            for (var b = 0; b < a.size(); b++) jb.ins('<img class="pk" src="//' + CONF_DOMAIN_IMG + "/pub/mnst/norm/minim/" + ya(a[b]) +
                                                '.gif" onclick="Exp.Dex_poke(\'' + a[b] + "', 0)\">");
                                        else jb.Ra()
                                    }
                                })
                            });
                        this.Nb.fa.open("awards");
                        G = new cb(a.slots);
                        m.ins(g).ins(v).ins(U).ins(e).ins(this.Nb.fa).ins(G);
                        c.ins(this.Vb).ins(m);
                        Object.isFunction(b) && b()
                    }.bind(this)
                })
            }
        }
    }),
    ga = Class.create(Y, {
        initialize: function($super, b, c) {
            $super(b, c);
            this.Xe = this.B;
            this.B = (new Element("div")).addClassName("trainerbig");
            this.Le = new Gb(this.id, function(b) {
                this.Vg();
                b.stopPropagation()
            }.bind(this));
            this.B.ins(this.Le).ins(this.Xe);
            this.Nh.B.remove();
            20 <= this.ga.length ?
                this.Xe.addClassName("long2") : 12 <= this.ga.length && this.Xe.addClassName("long1");
            this.Xg && this.B.addClassName("online");
            "undefined" !== typeof l && l.id == this.id && this.lj();
            0 == this.id && this.B.addClassName("l17");
            return this
        },
        lj: function() {
            l.id === this.id && (this.T("---"), l.Ed == GR_GYM && this.T("{{user_menu_gym}}", Gym.load.curry(f)), this.T("{{telep}}", I.sh), this.T("{{user_menu_change_emblem}}", E.Mk), this.T("{{user_menu_invite_referals}}", E.Qk.curry("share")), this.T("{{user_menu_options}}", E.Pj), La && (this.T(S ?
                "{{user_menu_mobile_off}}" : "{{user_menu_mobile_on}}", Qa), this.T("{{dock_help}}", D.fj)), this.T("---"), this.T("{{user_menu_logout}}", Game.xj))
        },
        Ma: function(a, b) {
            this.Zc || (this.Zc = (new Element("div")).addClassName("extra"), b && this.Zc.addClassName(b), this.B.addClassName("withextra"), this.Xe.ins(this.Zc));
            this.Zc.C(a);
            return this
        }
    }),
    Gb = Class.create(V, {
        initialize: function($super, b, c) {
            $super();
            this.id = b;
            this.B = (new Element("div")).addClassName("emblem");
            this.Na = c;
            T(this.B, this.onclick.bind(this));
            this.load()
        },
        load: function(a) {
            this.B.setStyle({
                backgroundImage: "url(//" + CONF_DOMAIN_IMG + "/dyn/emblems/" + this.id % 100 + "/" + this.id + ".png" + (a ? "?" + Math.random() : "") + ")"
            })
        },
        onclick: function(a) {
            Object.isFunction(this.Na) ? this.Na(a) : (new Y({
                id: this.id
            })).xf();
            return this
        }
    }),
    ua = Class.create({
        initialize: function() {
            this.types = "friends toprate topdex topdexs admins police moders tutors gyms".split(" ");
            this.type = "";
            this.page = 1;
            this.G = k;
            this.Gc = new Ya("{{search_user}}", this.load.bind(this, "search", k));
            this.ck = new W(0, Lang.select_search_user_by,
                this.load.bind(this, "search", k));
            this.ha = Xa((new Element("div")).addClassName("divTrainersList"), this.load.bind(this, 0, f));
            this.jg = (new Element("div")).addClassName("divTrainerListTypes");
            this.xb = [];
            this.jg.ins(this.Gc).ins(this.ck);
            for (var a = 0; a < this.types.length; a++) this.xb[a] = (new N("{{userlist_" + this.types[a] + "}}", "gray")).click(this.load.bind(this, this.types[a], k)), this.jg.ins(this.xb[a]);
            D.add("trainers", this.load.bind(this, "friends", k)).ins(this.ha).ins(this.jg)
        },
        load: function(a, b) {
            if (!this.G) {
                if (b) {
                    if (!this.page ||
                        !this.type) return;
                    var c = new Element("div");
                    this.ha.ins(c)
                } else {
                    a || (a = "friends");
                    this.xb.invoke("removeClassName", "pressed");
                    if ("search" == a) {
                        if (a = 0 == this.ck.get() ? "search_by_name" : "search_by_city", !this.Gc.get()) return
                    } else this.xb[this.types.indexOf(a)].addClassName("pressed"), this.Gc.set("");
                    this.type = a;
                    this.page = 1
                }
                this.G = f;
                new p("trainers/list/" + this.type, {
                    aa: [this.page, this.Gc.get()],
                    G: b ? c : this.ha,
                    J: function(a) {
                        b && c.remove();
                        !a || !a.size() ? (b || this.ha.Ra(), this.page = 0) : (a.each(function(a) {
                            trainer =
                                (new ga(a)).Ma(a.extra);
                            trainer.id && this.ha.ins(trainer).ins(R())
                        }.bind(this)), this.page++)
                    }.bind(this),
                    onComplete: function() {
                        this.G = k
                    }.bind(this)
                })
            }
        }
    });
Vight = {
    fd: (new Element("div")).addClassName("divVights"),
    list: function() {
        r.show(B.F.Xf, "{{fight_view_near}}", f, "white").content(Vight.fd);
        new p("vight/list", {
            G: Vight.fd,
            J: function(a) {
                Vight.fd.C();
                a && a.each(function(a) {
                    var c = new Y(a[1]),
                        d = new Y(a[2]);
                    c && d && (a = Vight.cj(a[0], c, d), Vight.fd.ins(a))
                });
                Vight.fd.empty() && Vight.fd.C("{{fight_view_no_near}}")
            }
        })
    },
    join: function(a) {
        new p("vight/join", [a], {
            J: function(a) {
                l.cb = f;
                y.C(a)
            }
        })
    },
    od: function() {
        new p("vight/leave");
        l.cb = k
    },
    cj: function(a, b, c) {
        a = (new N("",
            "ctrl nobg view")).click(Vight.join.curry(a));
        return (new Element("div")).addClassName("divVight").ins(a).ins(new Y(b)).ins(' <span class = "vs">VS</span> ').ins(new Y(c))
    }
};
var pa = Class.create({
    initialize: function() {
        this.list = [];
        this.bg = P(53, 60)
    },
    fill: function(a) {
        this.list = a;
        for (var b in this.list)
            if (3 != this.list[b].got) {
                for (var c in this.list[b].groups)
                    if (!this.list[b].groups[c].choosed && this.list[b].groups[c].variants[0][0]) {
                        a = new N("\u0421\u043c\u043e\u0442\u0440\u0435\u0442\u044c", "gray");
                        var d = new L(a, "info", "<b>\u041d\u0430\u0431\u043e\u0440 #" + b + " \u0413\u0440\u0443\u043f\u043f\u0430 #" + c + "</b><br>\u041c\u044b \u0433\u043e\u0442\u043e\u0432\u044b \u043f\u0440\u0435\u0434\u0441\u0442\u0430\u0432\u0438\u0442\u044c \u0432\u0430\u043c \u043d\u043e\u0432\u044b\u0445 \u043c\u043e\u043d\u0441\u0442\u0440\u043e\u0432 \u041b\u0438\u0433\u0438! \u0412\u044b \u0441\u043c\u043e\u0436\u0435\u0442\u0435 \u043f\u043e\u043b\u0443\u0447\u0438\u0442\u044c \u043e\u0434\u043d\u043e\u0433\u043e.", {
                            timeout: 0
                        });
                        a.click(this.show.bind(this, b)).click(d.hide.bind(d, f), 1)
                    }
                if (1 == this.list[b].status || 2 == this.list[b].status) a = new N("\u0421\u043c\u043e\u0442\u0440\u0435\u0442\u044c", "gray"), d = new L(a, "info", "<b>\u041d\u0430\u0431\u043e\u0440 #" + b + "</b><br>\u0412\u044b \u0432\u044b\u0431\u0440\u0430\u043b\u0438 \u0432\u0441\u0435\u0445 \u043c\u043e\u043d\u0441\u0442\u0440\u043e\u0432 \u0432 \u044d\u0442\u043e\u043c \u043d\u0430\u0431\u043e\u0440\u0435 \u0438 \u0442\u0435\u043f\u0435\u0440\u044c \u043c\u043e\u0436\u0435\u0442\u0435 \u043f\u043e\u043b\u0443\u0447\u0438\u0442\u044c \u043e\u0434\u043d\u043e\u0433\u043e \u0438\u0437 \u0432\u044b\u0431\u0440\u0430\u043d\u043d\u044b\u0445!", {
                    timeout: 0
                }), a.click(this.show.bind(this, b)).click(d.hide.bind(d, f), 1)
            }
    },
    show: function(a) {
        var b = this.list[a];
        if (b) {
            t.show("\u041d\u043e\u0432\u044b\u0435 \u043c\u043e\u043d\u0441\u0442\u0440\u044b \u041b\u0438\u0433\u0438. \u041d\u0410\u0411\u041e\u0420 #" + a, {
                position: "upleft"
            });
            this.ob = (new Element("div")).addClassName("divZvote");
            var c = (new Element("div")).addClassName("divInfo txtgray2").C("\u041c\u044b \u043f\u0440\u0435\u0434\u0441\u0442\u0430\u0432\u043b\u044f\u0435\u043c \u043d\u0430\u0431\u043e\u0440 \u043d\u043e\u0432\u044b\u0445 \u043c\u043e\u043d\u0441\u0442\u0440\u043e\u0432 \u041b\u0438\u0433\u0438. \u0412 \u043a\u0430\u0436\u0434\u043e\u043c \u043d\u0430\u0431\u043e\u0440\u0435 \u0442\u0440\u0438 \u0433\u0440\u0443\u043f\u043f\u044b \u043c\u043e\u043d\u0441\u0442\u0440\u043e\u0432. \u0412\u044b \u043c\u043e\u0436\u0435\u0442\u0435 \u0432\u044b\u0431\u0440\u0430\u0442\u044c \u043d\u0430\u0438\u0431\u043e\u043b\u0435\u0435 \u043f\u043e\u043d\u0440\u0430\u0432\u0438\u0432\u0448\u0435\u0433\u043e\u0441\u044f \u043c\u043e\u043d\u0441\u0442\u0440\u0430 \u0432 \u0433\u0440\u0443\u043f\u043f\u0435. \u041a\u043e\u0433\u0434\u0430 \u0432\u044b \u0432\u044b\u0431\u0435\u0440\u0435\u0442\u0435 \u043f\u043e \u043e\u0434\u043d\u043e\u043c\u0443 \u043c\u043e\u043d\u0441\u0442\u0440\u0443 \u0432 \u043a\u0430\u0436\u0434\u043e\u0439 \u0433\u0440\u0443\u043f\u043f\u0435 - \u0432\u044b \u043f\u043e\u043b\u0443\u0447\u0438\u0442\u0435 <b>\u043e\u0434\u043d\u043e\u0433\u043e</b> \u0438\u0437 \u0432\u044b\u0431\u0440\u0430\u043d\u043d\u044b\u0445 \u043c\u043e\u043d\u0441\u0442\u0440\u043e\u0432. \u041a\u0430\u043a\u043e\u0433\u043e \u0438\u043c\u0435\u043d\u043d\u043e \u2013 \u043e\u043f\u0440\u0435\u0434\u0435\u043b\u0438\u0442 \u0443\u0434\u0430\u0447\u0430!");
            this.ob.ins(c);
            for (var d in b.groups) {
                for (var c = (new Element("div")).addClassName("divGroup"), e = b.groups[d].choosed, m = 0; m < b.groups[d].variants.size(); m++) {
                    var g = b.groups[d].variants[m][0],
                        v = b.groups[d].variants[m][1],
                        G = (new Element("div")).addClassName("divVariant");
                    G.setAttribute("data-sp", g);
                    if (g) {
                        var U = (new Element("img", {
                                src: "//" + CONF_DOMAIN_IMG + "/pub/mnst/norm/full/" + ya(g) + ".png"
                            })).addClassName("image"),
                            ka = (new Element("span")).C("#" + ya(g) + " " + ha.get("pokenames", g));
                        A.rf(G, g);
                        e ? e == g ? G.addClassName("yourchoise").ins((new Element("div")).C("\u0412\u0410\u0428 \u0412\u042b\u0411\u041e\u0420")) :
                            G.addClassName("notchoosed") : v ? T(G, function(b) {
                                q.show();
                                q.add("\u0412\u044b\u0431\u0440\u0430\u0442\u044c \u044d\u0442\u043e\u0433\u043e \u043c\u043e\u043d\u0441\u0442\u0440\u0430!", this.Hl.bind(this, a, b), {
                                    ra: ""
                                })
                            }.bind(this, g)) : G.ins((new Element("div")).C("\u042d\u0442\u043e\u0442 \u043c\u043e\u043d\u0441\u0442\u0440 \u043d\u0435 \u043c\u043e\u0436\u0435\u0442 \u0431\u044b\u0442\u044c \u0432\u044b\u0431\u0440\u0430\u043d.").addClassName("rednumber"))
                    } else U = (new Element("div")).addClassName("image").C("?"),
                        ka = (new Element("span")).C("\u0421\u041a\u041e\u0420\u041e...");
                    G.ins(U).ins(ka);
                    c.ins(G)
                }
                this.ob.ins(c).ins(R())
            }
            b.groups_choosed == b.groups_amount && (b = (new N("\u041f\u043e\u043b\u0443\u0447\u0438\u0442\u044c \u043c\u043e\u043d\u0441\u0442\u0440\u0430", "roll")).click(this.Qn.bind(this, a)), this.ob.ins(b));
            t.ins(this.ob)
        }
    },
    Hl: function(a, b) {
        O("\u0412\u044b \u0443\u0432\u0435\u0440\u0435\u043d\u044b, \u0447\u0442\u043e \u0445\u043e\u0442\u0438\u0442\u0435 \u0432\u044b\u0431\u0440\u0430\u0442\u044c \u044d\u0442\u043e\u0433\u043e \u043c\u043e\u043d\u0441\u0442\u0440\u0430 #" +
            ya(b) + " " + ha.get("pokenames", b) + " \u0432 \u044d\u0442\u043e\u0439 \u0433\u0440\u0443\u043f\u043f\u0435?") && new p("zvote/choose", [b], {
            G: t,
            J: function(b) {
                t.close();
                this.fill(b);
                this.show(a)
            }.bind(this)
        })
    },
    Qn: function(a) {
        for (var b = (new Element("div")).addClassName("divVariants"), c = this.ob.select(".divVariant.yourchoise"), d = 0; d < c.size(); d++) b.ins(c[d]);
        this.ob.select(".divGroup").invoke("hide");
        this.ob.select(".button.roll").invoke("hide");
        this.ob.ins(b);
        C.play("chink");
        new p("zvote/roll", [a], {
            G: t,
            J: function(b) {
                this.$j(a,
                    0, b.anim ? 0 : 1E3, b.sp_id)
            }.bind(this)
        })
    },
    $j: function(a, b, c, d) {
        var e = this.ob.select(".divVariant.yourchoise");
        e.invoke("hide");
        C.play("chink");
        var m = ++c / 100;
        1 < m && (m = 0.1);
        c < this.bg || c >= this.bg && c < this.bg + 10 && e[b].getAttribute("data-sp") != d ? (e[b].show(), ++b > e.size() - 1 && (b = 0), this.$j.bind(this, a, b, c, d).delay(m)) : (this.ob.select(".divVariant.yourchoise[data-sp=" + d + "]")[0].show(), b = (new N("\u0417\u0430\u0431\u0440\u0430\u0442\u044c \u043c\u043e\u043d\u0441\u0442\u0440\u0430", "roll")).click(function() {
                new p("zvote/recieve",
                    [a], {
                        G: t,
                        J: function() {
                            t.close();
                            new L(1, "info", "\u0412\u044b\u0438\u0433\u0440\u0430\u043d\u043d\u044b\u0439 \u043c\u043e\u043d\u0441\u0442\u0440 \u0432 \u0432\u0430\u0448\u0435\u043c \u043f\u0438\u0442\u043e\u043c\u043d\u0438\u043a\u0435! \u041d\u0430 \u0441\u043b\u0435\u0434\u0443\u044e\u0449\u0438\u0439 \u043d\u0435\u0434\u0435\u043b\u0435 \u0436\u0434\u0438\u0442\u0435 \u043d\u043e\u0432\u044b\u0435 \u0433\u0440\u0443\u043f\u043f\u044b \u043c\u043e\u043d\u0441\u0442\u0440\u043e\u0432 \u0434\u043b\u044f \u0440\u043e\u0437\u044b\u0433\u0440\u044b\u0448\u0430!")
                        }
                    })
            }.bind(this)),
            this.ob.ins.bind(this.ob, b).delay(m), C.play.bind(C, "chinkwin").delay(m))
    }
});
