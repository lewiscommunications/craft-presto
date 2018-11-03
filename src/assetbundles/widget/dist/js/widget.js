(function($) {
    $('.js-presto-widget-purge-btn').click(function(e) {
        var answer = window.confirm('This will purge the entire static cache and the craft template cache');
        var target = $(e.target).data('action');

        if (answer && target) {
            window.location = target;
        }
    });
})(window.jQuery);
