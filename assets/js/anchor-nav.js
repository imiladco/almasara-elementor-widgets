/**
 * نوار تب چسبان الماسارا — نسخه بازنویسی‌شده
 *
 * چسبیدن: موتور جاوااسکریپتی fixed + جای‌نگهدار (placeholder).
 * position:sticky روی عنصر داخلی ویجت المنتور کار نمی‌کند چون والدش
 * هم‌قد خود نوار است؛ این موتور مستقل از ساختار کانتینرها همیشه کار می‌کند.
 *
 * تشخیص بخش فعال: بر اساس ارتفاع واقعی نوار + آفست چسبیدن هر دستگاه،
 * نه عدد دستی — به‌همراه حالت انتهای صفحه (آخرین بخش فعال می‌شود).
 */
(function () {
	'use strict';

	var isEditor = !!(window.elementorFrontend && window.elementorFrontend.isEditMode && window.elementorFrontend.isEditMode());

	function breakpoint() {
		var w = window.innerWidth;
		return w <= 767 ? 'm' : (w <= 1024 ? 't' : 'd');
	}

	function setup(nav) {
		if (nav.__amwNav) {
			return;
		}
		nav.__amwNav = true;

		var itemsEl = nav.querySelector('.amw-nav__items');
		var links = Array.prototype.slice.call(nav.querySelectorAll('.amw-nav__item'));

		var pairs = links
			.map(function (link) {
				var id = (link.getAttribute('href') || '').replace('#', '');
				var target = id ? document.getElementById(id) : null;
				return target ? { link: link, target: target } : null;
			})
			.filter(Boolean);

		/* ---------------- تنظیمات دستگاه جاری ---------------- */

		function cfg() {
			var bp = breakpoint();
			return {
				sticky: nav.getAttribute('data-sticky-' + bp) === '1' && !isEditor,
				top: parseInt(nav.getAttribute('data-top-' + bp), 10) || 0,
				extra: parseInt(nav.getAttribute('data-extra-' + bp), 10) || 0
			};
		}

		/* ---------------- موتور چسبیدن (fixed + placeholder) ---------------- */

		var placeholder = document.createElement('div');
		placeholder.style.display = 'none';
		nav.parentNode.insertBefore(placeholder, nav);

		var stuck = false;
		var navH = nav.offsetHeight;

		function stick(c) {
			navH = nav.offsetHeight;
			var rect = nav.getBoundingClientRect();
			placeholder.style.height = navH + 'px';
			placeholder.style.display = 'block';
			nav.style.position = 'fixed';
			nav.style.top = c.top + 'px';
			nav.style.left = rect.left + 'px';
			nav.style.width = rect.width + 'px';
			nav.classList.add('is-stuck');
			stuck = true;
		}

		function unstick() {
			if (!stuck) {
				return;
			}
			placeholder.style.display = 'none';
			nav.style.position = '';
			nav.style.top = '';
			nav.style.left = '';
			nav.style.width = '';
			nav.classList.remove('is-stuck');
			stuck = false;
		}

		function updateSticky(c) {
			if (!c.sticky) {
				unstick();
				return;
			}
			if (!stuck) {
				if (nav.getBoundingClientRect().top <= c.top) {
					stick(c);
				}
			} else if (placeholder.getBoundingClientRect().top > c.top) {
				unstick();
			}
		}

		/* ---------------- عنوان داینامیک با رول ۳۶۰ درجه ---------------- */

		var titleIn = nav.querySelector('.amw-nav__title-in');
		var dynTitle = nav.getAttribute('data-dyntitle') === '1' && titleIn;
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

		/* ---------------- تشخیص بخش فعال ---------------- */

		var activeIndex = -1;

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

		function spy(c) {
			if (!pairs.length) {
				return;
			}

			// خط تشخیص: زیر لبه پایین نوار (ارتفاع واقعی + آفست چسبیدن + فاصله اضافه)
			var line = c.top + navH + c.extra + 4;
			var previousIndex = activeIndex;
			var current = -1;

			for (var i = 0; i < pairs.length; i++) {
				if (pairs[i].target.getBoundingClientRect().top <= line) {
					current = i;
				}
			}

			// انتهای صفحه: آخرین بخش فعال شود حتی اگر کوتاه باشد
			if (window.innerHeight + window.scrollY >= document.documentElement.scrollHeight - 2) {
				current = pairs.length - 1;
			}

			activeIndex = current;

			if (activeIndex !== previousIndex) {
				pairs.forEach(function (pair, idx) {
					pair.link.classList.toggle('is-active', idx === activeIndex);
				});
				var active = activeIndex >= 0 ? pairs[activeIndex] : null;
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
		}

		/* ---------------- اسکرول نرم به بخش‌ها ---------------- */

		function scrollToTarget(target) {
			var c = cfg();
			// اگر نوار هنوز نچسبیده، بعد از اسکرول می‌چسبد؛ ارتفاعش را همیشه لحاظ کن
			var y = target.getBoundingClientRect().top + window.scrollY - c.top - navH - c.extra;
			window.scrollTo({ top: Math.max(0, y), behavior: 'smooth' });
		}

		links.forEach(function (link) {
			link.addEventListener('click', function (e) {
				var id = (link.getAttribute('href') || '').replace('#', '');
				var target = id ? document.getElementById(id) : null;
				if (!target) {
					return;
				}
				e.preventDefault();
				scrollToTarget(target);
				if (history.replaceState) {
					history.replaceState(null, '', '#' + id);
				}
			});
		});

		var stepUp = nav.querySelector('.amw-nav__step--up');
		var stepDown = nav.querySelector('.amw-nav__step--down');
		if (stepUp) {
			stepUp.addEventListener('click', function () {
				var i = activeIndex <= 0 ? 0 : activeIndex - 1;
				if (pairs[i]) {
					scrollToTarget(pairs[i].target);
				}
			});
		}
		if (stepDown) {
			stepDown.addEventListener('click', function () {
				var i = activeIndex >= pairs.length - 1 ? pairs.length - 1 : activeIndex + 1;
				if (pairs[i]) {
					scrollToTarget(pairs[i].target);
				}
			});
		}

		/* ---------------- حلقه اسکرول/ریسایز ---------------- */

		var ticking = false;

		function onFrame() {
			ticking = false;
			var c = cfg();
			updateSticky(c);
			spy(c);
		}

		function requestFrame() {
			if (!ticking) {
				ticking = true;
				requestAnimationFrame(onFrame);
			}
		}

		window.addEventListener('scroll', requestFrame, { passive: true });

		window.addEventListener('resize', function () {
			// هندسه عوض شده: جدا کن، دوباره اندازه بگیر و از نو ارزیابی کن
			unstick();
			navH = nav.offsetHeight;
			requestFrame();
		}, { passive: true });

		// بعد از لود کامل (تصاویر ارتفاع صفحه را عوض می‌کنند) دوباره ارزیابی کن
		window.addEventListener('load', function () {
			navH = nav.offsetHeight;
			requestFrame();
		});

		onFrame();
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
