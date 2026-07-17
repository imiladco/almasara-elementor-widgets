/**
 * آکاردئون سوالات متداول الماسارا
 */
(function () {
	'use strict';

	function slide(answer, open) {
		if (open) {
			answer.hidden = false;
			answer.style.maxHeight = '0px';
			answer.style.overflow = 'hidden';
			requestAnimationFrame(function () {
				answer.style.transition = 'max-height 0.3s ease';
				answer.style.maxHeight = answer.scrollHeight + 'px';
			});
			answer.addEventListener('transitionend', function done() {
				answer.style.maxHeight = '';
				answer.style.overflow = '';
				answer.style.transition = '';
				answer.removeEventListener('transitionend', done);
			});
		} else {
			answer.style.maxHeight = answer.scrollHeight + 'px';
			answer.style.overflow = 'hidden';
			requestAnimationFrame(function () {
				answer.style.transition = 'max-height 0.3s ease';
				answer.style.maxHeight = '0px';
			});
			answer.addEventListener('transitionend', function done() {
				answer.hidden = true;
				answer.style.maxHeight = '';
				answer.style.overflow = '';
				answer.style.transition = '';
				answer.removeEventListener('transitionend', done);
			});
		}
	}

	function setup(root) {
		if (root.__amwFaq) {
			return;
		}
		root.__amwFaq = true;

		var accordion = root.dataset.accordion === '1';

		root.addEventListener('click', function (e) {
			var button = e.target.closest('.amw-faq__q');
			if (!button || !root.contains(button)) {
				return;
			}

			var item = button.closest('.amw-faq__item');
			var answer = item.querySelector('.amw-faq__a');
			var willOpen = !item.classList.contains('is-open');

			if (accordion && willOpen) {
				root.querySelectorAll('.amw-faq__item.is-open').forEach(function (other) {
					if (other !== item) {
						other.classList.remove('is-open');
						other.querySelector('.amw-faq__q').setAttribute('aria-expanded', 'false');
						slide(other.querySelector('.amw-faq__a'), false);
					}
				});
			}

			item.classList.toggle('is-open', willOpen);
			button.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
			slide(answer, willOpen);
		});
	}

	function initAll(scope) {
		(scope || document).querySelectorAll('.amw-faq').forEach(setup);
	}

	if (window.elementorFrontend && window.elementorFrontend.hooks) {
		window.elementorFrontend.hooks.addAction('frontend/element_ready/almasara-product-faq.default', function ($el) {
			initAll($el && $el[0] ? $el[0] : document);
		});
	}

	if (document.readyState !== 'loading') {
		initAll(document);
	} else {
		document.addEventListener('DOMContentLoaded', function () {
			initAll(document);
		});
	}
})();
