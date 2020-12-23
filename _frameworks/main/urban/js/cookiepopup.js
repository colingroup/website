(function ($) {
    "use strict";

    var cookiePopupHtml = '<div id="cookie-popup-container">' +
        '<div class="cookie-popup" style="display: none;">' +
            '<div class="cookie-popup-inner">' +
                '<div class="cookie-popup-left">' +
                    '<div class="cookie-popup-headline">This website uses cookies</div>' +
                    '<div class="cookie-popup-sub-headline">By using this site, you agree to our use of cookies.</div>' +
                '</div>' +

                '<div class="cookie-popup-right">' +
                    '<input type="button" class="cookie-popup-accept-cookies-btn btn blue" value="Accept">' +
                    '<a href="#" class="cookie-popup-learn-more">Learn More</a>' +
                '</div>' +
            '</div>' +
            '<div class="cookie-popup-lower" style="display: none;">' +
            '</div>' +
        '</div>' +
    '</div>';

    var onAccept;

    $.extend({
        acceptCookies : function(options) {
            var cookiesAccepted = getCookie("cookiesAccepted");

            if (!cookiesAccepted) {
                var cookiePopup = $(cookiePopupHtml);

                var fnWidthPopup=function(){
                    var $w=$('.main'), w=$w.width(), d=w-$w[0].clientWidth;
                    w=w-d;
                    $('.cookie-popup').width(w);
                    return w;
                }
                var w=fnWidthPopup();
                $(window).on('resize',fnWidthPopup);

                var position = "bottom";

                if(options != undefined) {
                    position = options.position != undefined ? options.position : "bottom";

                    if(options.title != undefined)
                        cookiePopup.find('.cookie-popup-headline').text(options.title);
                    if(options.text != undefined)
                        cookiePopup.find('.cookie-popup-sub-headline').html(options.text);
                    if(options.acceptButtonText != undefined)
                        cookiePopup.find(".cookie-popup-accept-cookies-btn").val(options.acceptButtonText);
                    if(options.learnMoreButtonText != undefined)
                        cookiePopup.find(".cookie-popup-learn-more").text(options.learnMoreButtonText);
                    if(options.learnMoreInfoText != undefined)
                        cookiePopup.find(".cookie-popup-lower").text(options.learnMoreInfoText);
                    if(options.theme != undefined)
                        cookiePopup.addClass("theme-" + options.theme);
                    if(options.onAccept != undefined)
                        onAccept = options.onAccept;
                    if(options.learnMore != undefined) {
                        if(options.learnMore == false)
                            cookiePopup.find(".cookie-popup-learn-more").remove();
                    }
                    if(options.learnMoreText != undefined) {
                        cookiePopup.find(".cookie-popup-lower").html(options.learnMoreText);
                    }
                }

                cookiePopup.find('.cookie-popup').addClass("position-" + position);
                cookiePopup.find('.more_link').click(function(){
                    OpenWindow(this.href,'650','400');
                    return false;
                })
                $('.main').append(cookiePopup);
                $('.cookie-popup').width(w).delay(100).slideToggle();

            }
        }
    });

    $(document).on('click', '.cookie-popup-accept-cookies-btn', function(e) {
        e.preventDefault();
        saveCookie();
        $('.cookie-popup').slideToggle();
        if (typeof onAccept === "function")
            onAccept();
    }).on('click', '.cookie-popup-learn-more', function(e) {
        e.preventDefault();
        $('.cookie-popup-lower').slideToggle();
    });

    function getCookie(cname) {
        var name = cname + "=";
        var decodedCookie = decodeURIComponent(document.cookie);
        var ca = decodedCookie.split(';');
        for (var i = 0; i < ca.length; i++) {
            var c = ca[i];
            while (c.charAt(0) == ' ') {
                c = c.substring(1);
            }
            if (c.indexOf(name) == 0) {
                return c.substring(name.length, c.length);
            }
        }
        return "";
    }

    function saveCookie() {
        var expires = "expires=Tue, 01 Jan 2038 00:00:01 GMT";
        document.cookie = "cookiesAccepted=true; " + expires + "; path=/";
    }
}(jQuery));