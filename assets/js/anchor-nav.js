/**
 * نوار تب چسبان الماسارا — اسکرول نرم + scrollspy
 */
(function () {
	'use strict';

	function setup(nav) {
		if (nav.__amwNav) {
			return;
		}
		nav.__amwNav = true;

		var offset = parseInt(nav.dataset.offset, 10) || 0;
		var links = Array.prototype.slice.call(nav.querySelectorAll('.amw-nav__item'));

		var pairs = links
			.map(function (link) {
				var id = (link.getAttribute('href') || '').replace('#', '');
				var target = id ? document.getElementById(id) : null;
				if (target) {
					target.style.scrollMarginTop = offset + 'px';
				}
				return target ? { link: link, target: target } : null;
			})
			.filter(Boolean);

		links.forEach(function (link) {
			link.addEventListener('click', function (e) {
				var id = (link.getAttribute('href') || '').replace('#', '');
				var target = id ? document.getElementById(id) : null;
				if (!target) {
					return;
				}
				e.preventDefault();
				target.scrollIntoView({ behavior: 'smooth', block: 'start' });
				if (history.replaceState) {
					history.replaceState(null, '', '#' + id);
				}
			});
		});

		if (!pairs.length) {
			return;
		}

		var ticking = false;
		var activeIndex = -1;

		function spy() {
			ticking = false;
			var active = null;
			activeIndex = -1;
			pairs.forEach(function (pair, i) {
				if (pair.target.getBoundingClientRect().top - offset - 10 <= 0) {
					active = pair;
					activeIndex = i;
				}
			});
			pairs.forEach(function (pair) {
				pair.link.classList.toggle('is-active', pair === active);
			});
		}

		// فلش‌های پیمایش: پرش به بخش قبلی/بعدی
		function goTo(index) {
			if (index < 0 || index >= pairs.length) {
				return;
			}
			pairs[index].target.scrollIntoView({ behavior: 'smooth', block: 'start' });
		}

		var stepUp = nav.querySelector('.amw-nav__step--up');
		var stepDown = nav.querySelector('.amw-nav__step--down');
		if (stepUp) {
			stepUp.addEventListener('click', function () {
				goTo(activeIndex <= 0 ? 0 : activeIndex - 1);
			});
		}
		if (stepDown) {
			stepDown.addEventListener('click', function () {
				goTo(activeIndex >= pairs.length - 1 ? pairs.length - 1 : activeIndex + 1);
			});
		}

		window.addEventListener('scroll', function () {
			if (!ticking) {
				ticking = true;
				requestAnimationFrame(spy);
			}
		}, { passive: true });

		spy();
	}

	function initAll(scope) {
		(scope || document).querySelectorAll('.amw-nav').forEach(setup);
	}

	if (window.elementorFrontend && window.elementorFrontend.hooks) {
		window.elementorFrontend.hooks.addAction('frontend/element_ready/almasara-anchor-nav.default', function ($el) {
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
