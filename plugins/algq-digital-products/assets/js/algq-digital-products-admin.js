(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var wooField = document.getElementById('algq-woo-id');
        var priceField = document.getElementById('algq-price-label');

        if (!wooField || !priceField) {
            return;
        }

        wooField.addEventListener('change', function () {
            if (parseInt(wooField.value, 10) > 0) {
                priceField.setAttribute('aria-label', 'Fallback price used only when WooCommerce pricing is unavailable');
            }
        });
    });
}());
