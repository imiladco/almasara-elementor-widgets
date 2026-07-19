/**
 * ویجت افزودن به سبد الماسارا
 *
 * افزودنِ واقعی را wc-add-to-cart (بومی ووکامرس) و بجِ آنی را افزونه
 * almasara-fast-cart انجام می‌دهد. این اسکریپت فقط UI را می‌چرخاند:
 * استپر تعداد (که مقدارش را به data-quantity دکمه sync می‌کند) و حالت
 * بصری «افزوده‌شد» روی دکمه.
 */
(function () {
	'use strict';

	function clampQty(input, val) {
		var min = parseInt(input.getAttribute('min'), 10) || 1;
		var max = parseInt(input.getAttribute('max'), 10) || 0;
		val = parseInt(val, 10) || min;
		if (val < min) { val = min; }
		if (max > 0 && val > max) { val = max; }
		return val;
	}

	function setup(root) {
		if (root.__amwAtc) {
			return;
		}
		root.__amwAtc = true;

		var input = root.querySelector('.amw-atc__qty-input');
		var button = root.querySelector('.amw-atc__btn');

		// همگام‌سازی مقدار تعداد با data-quantity دکمه (ووکامرس از این می‌خواند)
		function sync() {
			if (input && button) {
				input.value = clampQty(input, input.value);
				button.setAttribute('data-quantity', input.value);
			}
		}

		if (input) {
			input.addEventListener('change', sync);
			input.addEventListener('input', sync);
		}

		var minus = root.querySelector('.amw-atc__step--minus');
		var plus = root.querySelector('.amw-atc__step--plus');
		if (minus && input) {
			minus.addEventListener('click', function () {
				input.value = clampQty(input, (parseInt(input.value, 10) || 1) - 1);
				sync();
			});
		}
		if (plus && input) {
			plus.addEventListener('click', function () {
				input.value = clampQty(input, (parseInt(input.value, 10) || 1) + 1);
				sync();
			});
		}

		sync();
	}

	// حالت بصری «افزوده‌شد» روی دکمه — با رویداد بومی ووکامرس (jQuery)
	function bindAddedState() {
		if (!window.jQuery) {
			return;
		}
		window.jQuery(document.body).on('added_to_cart', function (e, fragments, hash, $button) {
			if (!$button || !$button.length) {
				return;
			}
			var btn = $button[0];
			if (!btn.classList.contains('amw-atc__btn')) {
				return;
			}
			var label = btn.querySelector('.amw-atc__text');
			var addedText = btn.getAttribute('data-added-text');
			btn.classList.remove('loading');
			btn.classList.add('amw-added');
			if (label && addedText) {
				if (!btn.__amwOrig) {
					btn.__amwOrig = label.textContent;
				}
				label.textContent = addedText;
			}
			clearTimeout(btn.__amwTimer);
			btn.__amwTimer = setTimeout(function () {
				btn.classList.remove('amw-added');
				if (label && btn.__amwOrig) {
					label.textContent = btn.__amwOrig;
				}
			}, 2000);
		});
	}

	function initAll(scope) {
		(scope || document).querySelectorAll('.amw-atc').forEach(setup);
	}

	if (window.elementorFrontend && window.elementorFrontend.hooks) {
		window.elementorFrontend.hooks.addAction('frontend/element_ready/almasara-add-to-cart.default', function ($el) {
			initAll($el && $el[0] ? $el[0] : document);
		});
	}

	if (document.readyState !== 'loading') {
		initAll(document);
		bindAddedState();
	} else {
		document.addEventListener('DOMContentLoaded', function () {
			initAll(document);
			bindAddedState();
		});
	}
})();
