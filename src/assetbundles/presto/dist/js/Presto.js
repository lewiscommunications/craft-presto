// (function($) {
//     var $form = $('.js-presto-purge-selected');
//     var $checkboxes = $('.js-presto-cache-key-checkbox');
//     var $checkAllCheckbox = $('.js-presto-cache-key-checkbox-all');
//     var $submitButton = $('.js-presto-purge-submit');
//
//     function validateCheckBoxes() {
//         for (var i = 0; i < $checkboxes.length; i += 1) {
//             if ($checkboxes[i].checked) {
//                 return true;
//             }
//         }
//     }
//
//     function operateAllCheckboxes(checked) {
//         $checkboxes.each(function() {
//             this.checked = checked || false;
//         });
//     }
//
//     $checkAllCheckbox.on('change', function(e) {
//         operateAllCheckboxes(e.target.checked);
//     });
//
//     $form.on('change', function(e) {
//         if (e.target.type === 'checkbox') {
//             var valid = validateCheckBoxes();
//
//             $submitButton.attr('disabled', ! valid)
//                 [valid ? 'removeClass' : 'addClass']('disabled');
//         }
//     });
//
//     $form.submit(function(e) {
//         if (! validateCheckBoxes()) {
//             alert('You must select an item or items to purge');
//             e.preventDefault();
//         }
//     });
// })(window.jQuery);

var Presto;

(function(P, $) {
    P = (function() {
        function parseQuery(queryString) {
            var obj = {};

            if (queryString.length) {
                var pairs = queryString.split('&');
                for (i in pairs) {
                    var split = pairs[i].split('=');
                    obj[decodeURIComponent(split[0])] = decodeURIComponent(split[1]);
                }
            }

            return obj;
        }

        function validateCheckBoxes($checkboxes) {
            for (var i = 0; i < $checkboxes.length; i += 1) {
                if ($checkboxes[i].checked) {
                    return true;
                }
            }
        }

        function operateAllCheckboxes($checkboxes, checked) {
            $checkboxes.each(function() {
                this.checked = checked || false;
            });
        }

        return {
            $form: $('.js-presto-purge-selected'),
            $checkboxes: $('.js-presto-cache-key-checkbox'),
            $checkAllCheckbox: $('.js-presto-cache-key-checkbox-all'),
            $submitButton: $('.js-presto-purge-submit'),
            $searchInput: $('.js-presto-search-input'),
            $searchSubmit: $('.js-presto-search-submit'),
            init: function() {
                this.bindEvents();
            },

            bindEvents: function() {
                var scope = this;

                this.$searchInput.on('keyup', function(e) {
                    if (e.keyCode === 13) {
                        scope.search();
                    }
                });

                this.$form.on('change', function(e) {
                    if (e.target.type === 'checkbox') {
                        var valid = validateCheckBoxes(scope.$checkboxes);

                        scope.$submitButton.attr('disabled', ! valid)
                            [valid ? 'removeClass' : 'addClass']('disabled');
                    }
                });

                this.$checkAllCheckbox.on('change', function(e) {
                    operateAllCheckboxes(scope.$checkboxes, e.target.checked);
                });
            },

            search: function() {
                var query = parseQuery(window.location.search);

                query.query = this.$searchInput.val();

                console.log(query);

                window.location = window.location.origin + window.location.pathname + '?' + $.param(query);
            },
        };
    })();

    Presto = P;
})(Presto, window.jQuery);

Presto.init();