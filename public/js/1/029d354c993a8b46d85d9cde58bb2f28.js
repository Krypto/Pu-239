(function($) {
    $.fn.simpleCaptcha = function(o) {
        var n = this;
        if (n.length < 1) {
            return n;
        }
        o = o ? o : {};
        o = auditOptions($.extend({}, $.fn.simpleCaptcha.defaults, o));
        var inputId = "simpleCaptcha_" + $.fn.simpleCaptcha.uid++;
        n.addClass("simpleCaptcha").html("").append("<div class='" + o.introClass + "'>" + o.introText + "</div>" + "<div class='" + o.imageBoxClass + " " + o.imageBoxClassExtra + "'></div>" + "<input class='simpleCaptchaInput' id='" + inputId + "' name='" + o.inputName + "' type='hidden' value='' />");
        $.ajax({
            url: o.scriptPath,
            data: {
                numImages: o.numImages
            },
            method: "post",
            dataType: "json",
            success: function(data, status) {
                if (typeof data.error == "string") {
                    handleError(n, data.error);
                    return;
                } else {
                    n.find("." + o.textClass).html(data.text);
                    var imgBox = n.find("." + o.imageBoxClass);
                    $.each(data.images, function() {
                        imgBox.append("<img class='" + o.imageClass + "' src='" + this.file + "' alt='' data-title='" + this.hash + "' />");
                    });
                    imgBox.find("img." + o.imageClass).click(function(e) {
                        n.find("img." + o.imageClass).removeClass("simpleCaptchaSelected");
                        var hash = $(this).addClass("simpleCaptchaSelected").attr("data-title");
                        $("#" + inputId).val(hash);
                        n.trigger("select.simpleCaptcha", [ hash ]);
                        return false;
                    }).keyup(function(e) {
                        if (e.keyCode == 13 || e.which == 13) {
                            $(this).click();
                        }
                    });
                    n.trigger("loaded.simpleCaptcha", [ data ]);
                }
            },
            error: function(xhr, status) {
                handleError(n, "There was a serious problem: " + xhr.status);
            }
        });
        return n;
    };
    var handleError = function(n, msg) {
        n.trigger("error.simpleCaptcha", [ msg ]);
    };
    var auditOptions = function(o) {
        if (typeof o.numImages != "number" || o.numImages < 1) {
            o.numImages = $.fn.simpleCaptcha.defaults.numImages;
        }
        if (typeof o.introText != "string" || o.introText.length < 1) {
            o.introText = $.fn.simpleCaptcha.defaults.introText;
        }
        if (typeof o.inputName != "string") {
            o.inputName = $.fn.simpleCaptcha.defaults.inputName;
        }
        if (typeof o.scriptPath != "string") {
            o.scriptPath = $.fn.simpleCaptcha.defaults.scriptPath;
        }
        if (typeof o.introClass != "string") {
            o.introClass = $.fn.simpleCaptcha.defaults.introClass;
        }
        if (typeof o.textClass != "string") {
            o.textClass = $.fn.simpleCaptcha.defaults.textClass;
        }
        if (typeof o.imageBoxClass != "string") {
            o.imageBoxClass = $.fn.simpleCaptcha.defaults.imageBoxClass;
        }
        if (typeof o.imageClass != "string") {
            o.imageClass = $.fn.simpleCaptcha.defaults.imageClass;
        }
        return o;
    };
    $.fn.simpleCaptcha.uid = 0;
    $.fn.simpleCaptcha.defaults = {
        numImages: 6,
        introText: "<p align='center'>To make sure you are a human, we need you to click on the <span class='captchaText'></span>.</p>",
        inputName: "captchaSelection",
        scriptPath: "simpleCaptcha.php",
        introClass: "captchaIntro bottom10",
        textClass: "captchaText",
        imageBoxClass: "tabs",
        imageBoxClassExtra: "is-marginless",
        imageClass: "captchaImage"
    };
})(jQuery);

$(document).ready(function() {
    if ($("#captcha_show").length) {
        $("#captcha_show").simpleCaptcha();
    }
});

function checkit() {
    wantusername = document.getElementById("wantusername").value;
    var url = "../namecheck.php?wantusername=" + escape(wantusername);
    try {
        request = new ActiveXObject("Msxml2.XMLHTTP");
    } catch (e) {
        try {
            request = new ActiveXObject("Microsoft.XMLHTTP");
        } catch (e2) {
            request = false;
        }
    }
    if (!request && typeof XMLHttpRequest != "undefined") {
        request = new XMLHttpRequest();
    }
    request.open("GET", url, true);
    global_content = wantusername;
    request.onreadystatechange = check;
    request.send(null);
}

function check() {
    if (request.readyState == 4) {
        if (request.status == 200) {
            var response = request.responseText;
            document.getElementById("namecheck").innerHTML = response;
            if (response.substring(0, 20) == "<font color='#cc0000'>") document.reform.submitt.disabled = true; else if (response.substring(0, 20) == "<font color='#33cc33'>") document.reform.submitt.disabled = false;
        }
    }
}

(function(A) {
    A.extend(A.fn, {
        pstrength: function(B) {
            var B = A.extend({
                verdects: [ "Very weak", "Weak", "Medium", "Strong", "Very strong" ],
                colors: [ "#f00", "#c06", "#f60", "#3c0", "#3f0" ],
                scores: [ 10, 15, 30, 40 ],
                common: [ "password", "sex", "god", "123456", "123", "liverpool", "letmein", "qwerty", "monkey" ],
                minchar: 6
            }, B);
            return this.each(function() {
                var C = A(this).attr("id");
                A(this).after('<div class="pstrength-minchar alt_bordered bottom10" id="' + C + '_minchar">Minimum number of characters is ' + B.minchar + "</div>");
                A(this).after('<div class="pstrength-info" id="' + C + '_text"></div>');
                A(this).after('<div class="pstrength-bar" id="' + C + '_bar" style="border: 1px solid white; font-size: 1px; height: 5px; width: 0px;"></div>');
                A(this).keyup(function() {
                    A.fn.runPassword(A(this).val(), C, B);
                });
            });
        },
        runPassword: function(D, F, C) {
            nPerc = A.fn.checkPassword(D, C);
            var B = "#" + F + "_bar";
            var E = "#" + F + "_text";
            if (nPerc == -200) {
                strColor = "#f00";
                strText = "Unsafe password word!";
                A(B).css({
                    width: "0%"
                });
            } else {
                if (nPerc < 0 && nPerc > -199) {
                    strColor = "#ccc";
                    strText = "Too short";
                    A(B).css({
                        width: "5%"
                    });
                } else {
                    if (nPerc <= C.scores[0]) {
                        strColor = C.colors[0];
                        strText = C.verdects[0];
                        A(B).css({
                            width: "10%"
                        });
                    } else {
                        if (nPerc > C.scores[0] && nPerc <= C.scores[1]) {
                            strColor = C.colors[1];
                            strText = C.verdects[1];
                            A(B).css({
                                width: "25%"
                            });
                        } else {
                            if (nPerc > C.scores[1] && nPerc <= C.scores[2]) {
                                strColor = C.colors[2];
                                strText = C.verdects[2];
                                A(B).css({
                                    width: "50%"
                                });
                            } else {
                                if (nPerc > C.scores[2] && nPerc <= C.scores[3]) {
                                    strColor = C.colors[3];
                                    strText = C.verdects[3];
                                    A(B).css({
                                        width: "75%"
                                    });
                                } else {
                                    strColor = C.colors[4];
                                    strText = C.verdects[4];
                                    A(B).css({
                                        width: "92%"
                                    });
                                }
                            }
                        }
                    }
                }
            }
            A(B).css({
                backgroundColor: strColor
            });
            A(E).html("<span style='color: " + strColor + ";'>" + strText + "</span>");
        },
        checkPassword: function(C, B) {
            var F = 0;
            var E = B.verdects[0];
            if (C.length < B.minchar) {
                F = F - 100;
            } else {
                if (C.length >= B.minchar && C.length <= B.minchar + 2) {
                    F = F + 6;
                } else {
                    if (C.length >= B.minchar + 3 && C.length <= B.minchar + 4) {
                        F = F + 12;
                    } else {
                        if (C.length >= B.minchar + 5) {
                            F = F + 18;
                        }
                    }
                }
            }
            if (C.match(/[a-z]/)) {
                F = F + 1;
            }
            if (C.match(/[A-Z]/)) {
                F = F + 5;
            }
            if (C.match(/\d+/)) {
                F = F + 5;
            }
            if (C.match(/(.*[0-9].*[0-9].*[0-9])/)) {
                F = F + 7;
            }
            if (C.match(/.[!,@,#,$,%,^,&,*,?,_,~]/)) {
                F = F + 5;
            }
            if (C.match(/(.*[!,@,#,$,%,^,&,*,?,_,~].*[!,@,#,$,%,^,&,*,?,_,~])/)) {
                F = F + 7;
            }
            if (C.match(/([a-z].*[A-Z])|([A-Z].*[a-z])/)) {
                F = F + 2;
            }
            if (C.match(/([a-zA-Z])/) && C.match(/([0-9])/)) {
                F = F + 3;
            }
            if (C.match(/([a-zA-Z0-9].*[!,@,#,$,%,^,&,*,?,_,~])|([!,@,#,$,%,^,&,*,?,_,~].*[a-zA-Z0-9])/)) {
                F = F + 3;
            }
            for (var D = 0; D < B.common.length; D++) {
                if (C.toLowerCase() == B.common[D]) {
                    F = -200;
                }
            }
            return F;
        }
    });
})(jQuery);