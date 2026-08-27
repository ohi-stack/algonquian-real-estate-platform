(function () {
    'use strict';

    function animateNumber(el) {
        var raw = (el.textContent || '').trim();
        if (!/^\d+$/.test(raw)) {
            return;
        }

        var target = parseInt(raw, 10);
        if (!target || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            return;
        }

        var start = performance.now();
        var duration = 520;
        el.textContent = '0';

        function frame(now) {
            var progress = Math.min(1, (now - start) / duration);
            var eased = 1 - Math.pow(1 - progress, 3);
            el.textContent = String(Math.round(target * eased));
            if (progress < 1) {
                window.requestAnimationFrame(frame);
            }
        }

        window.requestAnimationFrame(frame);
    }

    document.addEventListener('DOMContentLoaded', function () {
        var wooField = document.getElementById('algq-woo-id');
        var priceField = document.getElementById('algq-price-label');

        if (wooField && priceField) {
            wooField.addEventListener('change', function () {
                if (parseInt(wooField.value, 10) > 0) {
                    priceField.setAttribute('aria-label', 'Fallback price used only when WooCommerce pricing is unavailable');
                }
            });
        }

        document.querySelectorAll('.algq-dp-admin-cards strong').forEach(animateNumber);
    });
}());
