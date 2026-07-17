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

		function spy() {
			ticking = false;
			var active = null;
			pairs.forEach(function (pair) {
				if (pair.target.getBoundingClientRect().top - offset - 10 <= 0) {
					active = pair;
				}
			});
			pairs.forEach(function (pair) {
				pair.link.classList.toggle('is-active', pair === active);
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
