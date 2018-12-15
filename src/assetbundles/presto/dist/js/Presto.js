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
        return {
            $form: $('.js-presto-purge-selected'),
            $checkboxes: $('.js-presto-cache-key-checkbox'),
            $checkAllCheckbox: $('.js-presto-cache-key-checkbox-all'),
            $submitButton: $('.js-presto-purge-submit'),
        }
    })();

    Presto = P;
})(Presto, window.jQuery);

console.log(Presto);