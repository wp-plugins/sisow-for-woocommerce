jQuery(document).ready(function() {
    jQuery("input[name=payment_method]").on("change", function() {
        jQuery('body').trigger('update_checkout');
    });
});

