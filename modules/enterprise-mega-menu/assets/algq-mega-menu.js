(function () {
	'use strict';

	function closeAll(menu, except) {
		menu.querySelectorAll('.algq-mega-menu__trigger').forEach(function (trigger) {
			if (trigger === except) {
				return;
			}
			trigger.setAttribute('aria-expanded', 'false');
			var panelId = trigger.getAttribute('aria-controls');
			var panel = panelId ? document.getElementById(panelId) : null;
			if (panel) {
				panel.hidden = true;
			}
		});
	}

	document.addEventListener('DOMContentLoaded', function () {
		document.querySelectorAll('.algq-mega-menu').forEach(function (menu) {
			var mobileToggle = menu.querySelector('.algq-mega-menu__mobile-toggle');
			var primary = menu.querySelector('.algq-mega-menu__primary');
			var triggers = menu.querySelectorAll('.algq-mega-menu__trigger');

			if (mobileToggle && primary) {
				mobileToggle.addEventListener('click', function () {
					var open = mobileToggle.getAttribute('aria-expanded') === 'true';
					mobileToggle.setAttribute('aria-expanded', String(!open));
					primary.classList.toggle('is-open', !open);
					if (open) {
						closeAll(menu, null);
					}
				});
			}

			triggers.forEach(function (trigger) {
				trigger.addEventListener('click', function () {
					var expanded = trigger.getAttribute('aria-expanded') === 'true';
					var panel = document.getElementById(trigger.getAttribute('aria-controls'));
					closeAll(menu, trigger);
					trigger.setAttribute('aria-expanded', String(!expanded));
					if (panel) {
						panel.hidden = expanded;
					}
				});

				trigger.addEventListener('keydown', function (event) {
					if (event.key === 'Escape') {
						closeAll(menu, null);
						trigger.focus();
					}
				});
			});

			document.addEventListener('click', function (event) {
				if (!menu.contains(event.target)) {
					closeAll(menu, null);
				}
			});
		});
	});
})();
