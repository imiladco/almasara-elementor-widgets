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
		var itemsEl = nav.querySelector('.amw-nav__items');

		// مشخصات sticky یک‌بار خوانده و کش می‌شود (نه در هر فریم اسکرول)
		var isSticky = false;
		var stickyTop = 0;

		function readStickyMeta() {
			var cs = getComputedStyle(nav);
			isSticky = cs.position === 'sticky';
			stickyTop = parseInt(cs.top, 10) || 0;
		}
		readStickyMeta();
		window.addEventListener('resize', readStickyMeta, { passive: true });

		// عنوان داینامیک: متن تب فعال با انیمیشن رول ۳۶۰ درجه
		var titleIn = nav.querySelector('.amw-nav__title-in');
		var dynTitle = nav.dataset.dyntitle === '1' && titleIn;
		var defaultTitle = titleIn ? titleIn.textContent : '';
		var rolling = false;
		var pendingRoll = null;

		function rollTitle(text, direction) {
			if (rolling) {
				pendingRoll = { text: text, direction: direction };
				return;
			}
			if (titleIn.textContent === text) {
				return;
			}
			rolling = true;
			titleIn.classList.add(direction === 'down' ? 'amw-roll-down' : 'amw-roll-up');
			setTimeout(function () {
				titleIn.textContent = text;
			}, 250);
			setTimeout(function () {
				titleIn.classList.remove('amw-roll-down', 'amw-roll-up');
				rolling = false;
				if (pendingRoll) {
					var next = pendingRoll;
					pendingRoll = null;
					rollTitle(next.text, next.direction);
				}
			}, 520);
		}

		// فقط «نوار تب‌ها» را افقی اسکرول می‌کند — نه صفحه را
		function centerActiveTab(link) {
			if (!itemsEl || itemsEl.scrollWidth <= itemsEl.clientWidth + 2) {
				return;
			}
			var stripRect = itemsEl.getBoundingClientRect();
			var linkRect = link.getBoundingClientRect();
			var delta = (linkRect.left + linkRect.width / 2) - (stripRect.left + stripRect.width / 2);
			if (itemsEl.scrollBy) {
				itemsEl.scrollBy({ left: delta, behavior: 'smooth' });
			} else {
				itemsEl.scrollLeft += delta;
			}
		}

		function spy() {
			ticking = false;
			var active = null;
			var previousIndex = activeIndex;
			activeIndex = -1;
			pairs.forEach(function (pair, i) {
				if (pair.target.getBoundingClientRect().top - offset - 10 <= 0) {
					active = pair;
					activeIndex = i;
				}
			});

			if (activeIndex !== previousIndex) {
				pairs.forEach(function (pair) {
					pair.link.classList.toggle('is-active', pair === active);
				});
				if (active) {
					centerActiveTab(active.link);
				}
				if (dynTitle) {
					rollTitle(
						active ? active.link.textContent.trim() : defaultTitle,
						activeIndex > previousIndex ? 'down' : 'up'
					);
				}
			}

			// کلاس is-stuck هنگام چسبیدن (برای استایل حالت چسبیده)
			if (isSticky) {
				nav.classList.toggle('is-stuck', Math.round(nav.getBoundingClientRect().top) <= stickyTop + 1 && window.scrollY > 10);
			}
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
