(function () {
	'use strict';

	document.addEventListener('DOMContentLoaded', function () {
		document.querySelectorAll('.algq-form-grid, .algq-inline-update').forEach(function (form) {
			form.addEventListener('submit', function () {
				var button = form.querySelector('button[type="submit"]');
				if (button) {
					button.disabled = true;
					button.setAttribute('aria-busy', 'true');
				}
			});
		});
	});
}());
