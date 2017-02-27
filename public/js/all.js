//--- Add active class to current page
$(document).ready(function(event) {
    var pathArray = window.location.pathname.split('/');
    var indexUrl = (window.location.protocol + "//" + window.location.hostname + "/") + pathArray[1];
    $('header .nav .nav-link[href="' + indexUrl + '"]').addClass('active');
});

openPanel = function(content, d) {
    var delay = d || 150;

    $('.panel-container, .panel-invisible').remove();
    $('body').prepend('<div class="panel-container">' + content + '</div>');
    $('.panel-container').animate({"right": "0"}, delay, "easeInOutCubic");
    $('body').prepend('<div class="panel-invisible"></div>');
    $('.panel-invisible').fadeIn(delay, "easeInOutCubic");

    $('.panel-invisible').bind("click", function(event) {
        $('.panel-invisible').fadeOut(delay, "easeInOutCubic", function() {
            $('.panel-invisible').remove();
        });

        $('.panel-container, .panel-child-container').animate({"right": "-1000px"}, delay, "easeInOutCubic", function() {
            $('.panel-container, .panel-child-container').remove();
        });
    });
}

openBigWindow = function(content, delay, callback) {
    delay = delay || 500;

    $('.large-panel-container, .large-panel-exit').remove();

    $('body').prepend(
        '<div class="large-panel-exit"></div>'+
        '<div class="large-panel-container">'+
        '<div class="large-panel-inner">' + content + '</div>'+
        '</div>'
    );

    $('.large-panel-container').waitForImages(function() {
        $('.large-panel-exit').fadeIn(delay);
        $('.operations-mission-browser').animate({"bottom": "0"}, delay, "easeInOutCirc");
        $('.large-panel-container, .large-panel-sidebar').animate(
            {"top": "0"},
            delay,
            "easeInOutCirc",
            function() {
                if (typeof callback == "function") callback();
            }
        );

        $('.large-panel-exit').bind('click', function(event) {
            $('.mission-nav').css({
                'position': 'absolute',
                'top': 'calc(100vh / 1.618)',
                'left': 0,
                'right': 0
            });

            $('.large-panel-exit').fadeOut(delay, function() {
                $('.large-panel-exit').remove();
            });

            $('.operations-mission-browser').animate({"bottom": "-1000px"}, delay, "easeInOutCirc");

            $('.large-panel-container, .large-panel-sidebar').animate({"top": "100%"}, delay, "easeInOutCirc", function() {
                $('.large-panel-container').remove();
            });
        });
    });
}

setUrl = function(url, title) {
    var url = (window.location.protocol + "//" + window.location.hostname + "/") + url;
    
    window.history.replaceState({
        archubPush: true
    }, document.title, url);

    if (title) document.title = title;
}

;(function($) {
    $.fn.missionSpinner = function(show) {
        var caller = $(this);

        caller
            .removeClass('spinner')
            .find('.mission-spinner')
            .remove();

        if (show) {
            caller
                .addClass('spinner')
                .find('.mission-item-inner')
                .prepend('\
                    <div class="mission-spinner">\
                        <i class="fa fa-spin fa-circle-o-notch"></i>\
                    </div>\
                ');
        }
    }
})(jQuery);

//# sourceMappingURL=all.js.map
