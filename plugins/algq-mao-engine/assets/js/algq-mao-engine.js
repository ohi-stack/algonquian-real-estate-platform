(function () {
	'use strict';

	function money(value) {
		return new Intl.NumberFormat('en-US', {
			style: 'currency',
			currency: (window.algqMao && window.algqMao.currency) || 'USD',
			maximumFractionDigits: 0
		}).format(Number(value || 0));
	}

	function percent(value) {
		return (Number(value || 0) * 100).toFixed(2) + '%';
	}

	function payload(form) {
		var output = {};
		new FormData(form).forEach(function (value, key) {
			if (['action', 'algq_mao_nonce', '_wp_http_referer', 'scenario_name', 'deal_id'].indexOf(key) === -1) {
				output[key] = value;
			}
		});
		return output;
	}

	function clear(node) {
		while (node.firstChild) {
			node.removeChild(node.firstChild);
		}
	}

	function row(label, value) {
		var item = document.createElement('div');
		var strong = document.createElement('strong');
		var span = document.createElement('span');
		strong.textContent = label;
		span.textContent = value;
		item.appendChild(strong);
		item.appendChild(span);
		return item;
	}

	function render(form, result) {
		var target = form.querySelector('.algq-mao-result');
		clear(target);

		var summary = document.createElement('div');
		summary.className = 'algq-mao-result-grid';
		summary.appendChild(row('Maximum Allowable Offer', money(result.mao)));
		summary.appendChild(row('Projected Profit', money(result.projected_profit)));
		summary.appendChild(row('Estimated Spread', money(result.estimated_spread)));
		summary.appendChild(row('Risk', result.risk_flag || 'Review'));

		if (result.noi > 0) {
			summary.appendChild(row('NOI', money(result.noi)));
			summary.appendChild(row('Cap Rate', percent(result.cap_rate)));
			summary.appendChild(row('DSCR', Number(result.dscr || 0).toFixed(2)));
		}
		target.appendChild(summary);

		var formula = document.createElement('p');
		formula.className = 'algq-mao-formula';
		formula.textContent = 'Formula: ' + (result.formula || '—');
		target.appendChild(formula);

		if (Array.isArray(result.risk_reasons) && result.risk_reasons.length) {
			var reasons = document.createElement('p');
			reasons.className = 'algq-mao-risk-reasons';
			reasons.textContent = 'Review: ' + result.risk_reasons.join(', ').replace(/_/g, ' ');
			target.appendChild(reasons);
		}

		if (result.sensitivity) {
			var table = document.createElement('table');
			table.className = 'algq-mao-sensitivity';
			var caption = document.createElement('caption');
			caption.textContent = 'Sensitivity Analysis';
			table.appendChild(caption);
			var head = document.createElement('tr');
			['Case', 'ARV', 'Repairs', 'MAO', 'Profit'].forEach(function (title) {
				var th = document.createElement('th');
				th.textContent = title;
				head.appendChild(th);
			});
			table.appendChild(head);
			Object.keys(result.sensitivity).forEach(function (name) {
				var data = result.sensitivity[name];
				var tr = document.createElement('tr');
				[name, money(data.arv), money(data.repairs), money(data.mao), money(data.projected_profit)].forEach(function (value) {
					var td = document.createElement('td');
					td.textContent = value;
					tr.appendChild(td);
				});
				table.appendChild(tr);
			});
			target.appendChild(table);
		}
	}

	function renderError(form, message) {
		var target = form.querySelector('.algq-mao-result');
		clear(target);
		var notice = document.createElement('p');
		notice.className = 'algq-mao-error';
		notice.textContent = message;
		target.appendChild(notice);
	}

	function toggleFields(form) {
		var strategy = form.querySelector('[name="strategy"]').value;
		form.querySelectorAll('[data-strategy-field]').forEach(function (field) {
			var visible = field.getAttribute('data-strategy-field').split(',').indexOf(strategy) !== -1;
			field.hidden = !visible;
		});
	}

	function bind(form) {
		var button = form.querySelector('.algq-mao-calculate');
		var strategy = form.querySelector('[name="strategy"]');
		if (!button || !window.algqMao) {
			return;
		}

		strategy.addEventListener('change', function () {
			toggleFields(form);
		});
		toggleFields(form);

		button.addEventListener('click', function () {
			button.disabled = true;
			button.textContent = window.algqMao.labels.calculating;

			fetch(window.algqMao.endpoint, {
				method: 'POST',
				headers: Object.assign({
					'Content-Type': 'application/json'
				}, window.algqMao.nonce ? {'X-WP-Nonce': window.algqMao.nonce} : {}),
				body: JSON.stringify(payload(form)),
				credentials: 'same-origin'
			}).then(function (response) {
				return response.json().then(function (data) {
					if (!response.ok) {
						throw new Error(data.message || window.algqMao.labels.error);
					}
					return data;
				});
			}).then(function (data) {
				render(form, data);
			}).catch(function (error) {
				renderError(form, error.message || window.algqMao.labels.error);
			}).finally(function () {
				button.disabled = false;
				button.textContent = 'Calculate Scenario';
			});
		});
	}

	document.addEventListener('DOMContentLoaded', function () {
		document.querySelectorAll('.algq-mao-calculator').forEach(bind);
	});
}());
