(function () {
	'use strict';

	function money(value) {
		var number = Number(value || 0);
		return number.toLocaleString('en-US', {
			style: 'currency',
			currency: 'USD',
			maximumFractionDigits: 0
		});
	}

	function getNumber(form, name) {
		var field = form.querySelector('[name="' + name + '"]');
		if (!field) {
			return 0;
		}
		return parseFloat(field.value || '0') || 0;
	}

	function getString(form, name, fallback) {
		var field = form.querySelector('[name="' + name + '"]');
		if (!field) {
			return fallback;
		}
		return field.value || fallback;
	}

	function riskClass(risk) {
		return 'algq-risk-' + String(risk || 'review').toLowerCase().replace(/\s+/g, '-');
	}

	function calculate(form) {
		var arv = getNumber(form, 'arv');
		var repairs = getNumber(form, 'repairs');
		var holdingCosts = getNumber(form, 'holding_costs');
		var desiredProfit = getNumber(form, 'desired_profit');
		var assignmentFee = getNumber(form, 'assignment_fee');
		var strategy = getString(form, 'strategy', 'wholesale');
		var multiplier = 0.70;
		var closingRate = 0.03;
		var closingCosts = arv * closingRate;
		var mao = (arv * multiplier) - repairs - holdingCosts - closingCosts - desiredProfit;

		if (strategy === 'wholesale') {
			mao -= assignmentFee;
		}

		var spread = Math.max(0, arv - repairs - mao);
		var risk = 'Acceptable';

		if (mao <= 0 || repairs > (arv * 0.35)) {
			risk = 'High Risk';
		} else if (spread < desiredProfit) {
			risk = 'Review';
		}

		return {
			arv: arv,
			repairs: repairs,
			holdingCosts: holdingCosts,
			closingCosts: closingCosts,
			desiredProfit: desiredProfit,
			assignmentFee: assignmentFee,
			strategy: strategy,
			mao: mao,
			spread: spread,
			risk: risk
		};
	}

	function renderResult(form, result) {
		var target = form.querySelector('.algq-mao-result');
		if (!target) {
			return;
		}

		target.innerHTML = '' +
			'<div><strong>Maximum Allowable Offer:</strong> ' + money(result.mao) + '</div>' +
			'<div><strong>Estimated Spread:</strong> ' + money(result.spread) + '</div>' +
			'<div><strong>Closing Costs:</strong> ' + money(result.closingCosts) + '</div>' +
			'<div><strong>Strategy:</strong> ' + String(result.strategy).replace(/^./, function (c) { return c.toUpperCase(); }) + '</div>' +
			'<div><strong>Risk Flag:</strong> <span class="' + riskClass(result.risk) + '">' + result.risk + '</span></div>';
	}

	function bindCalculator(form) {
		var button = form.querySelector('.algq-mao-calc-button');
		var fields = form.querySelectorAll('.algq-mao-input');

		function update() {
			renderResult(form, calculate(form));
		}

		if (button) {
			button.addEventListener('click', update);
		}

		fields.forEach(function (field) {
			field.addEventListener('input', update);
			field.addEventListener('change', update);
		});

		if (fields.length) {
			update();
		}
	}

	document.addEventListener('DOMContentLoaded', function () {
		document.querySelectorAll('.algq-mao-card').forEach(bindCalculator);
	});
}());
