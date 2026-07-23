/**
 * ویجت «اسلایدر هیرو» — راه‌اندازی Swiper.
 *
 * چندنمونه‌ای بودن با instantiate کردن Swiper روی هر عنصر پیدا‌شده حل
 * می‌شود (نه با یک کلاس/ID سراسری)، پس چند ویجت در یک صفحه تداخل ندارند.
 */
(function () {
	'use strict';

	function parseCfg(root) {
		try {
			return JSON.parse(root.dataset.cfg || '{}');
		} catch (e) {
			return {};
		}
	}

	function setup(root) {
		if (root.__amwHero || !window.Swiper) {
			return;
		}
		root.__amwHero = true;

		var swiperEl = root.querySelector('.amw-hero__swiper');
		var skeleton = root.querySelector('.amw-hero__skeleton');
		if (!swiperEl) {
			return;
		}

		var cfg = parseCfg(root);
		var hasNav = !!cfg.navigation && root.querySelector('.amw-hero__btn--prev');
		var hasPagination = !!cfg.pagination && root.querySelector('.amw-hero__pagination');

		var options = {
			speed: cfg.speed || 1000,
			slidesPerView: cfg.slidesPerView || 1,
			spaceBetween: cfg.spaceBetween || 0,
			breakpoints: cfg.breakpoints || {},
			resistanceRatio: cfg.resistanceRatio || 0,
			rewind: !!cfg.rewind,
			rtl: !!cfg.rtl,
			parallax: !!cfg.parallax,
			on: {
				init: function () {
					if (skeleton) {
						skeleton.style.display = 'none';
					}
				}
			}
		};

		if (cfg.autoplay) {
			options.autoplay = { delay: cfg.delay || 3000, disableOnInteraction: !!cfg.disableOnInteraction };
		}
		if (hasNav) {
			options.navigation = {
				nextEl: root.querySelector('.amw-hero__btn--next'),
				prevEl: root.querySelector('.amw-hero__btn--prev')
			};
		}
		if (hasPagination) {
			options.pagination = {
				el: root.querySelector('.amw-hero__pagination'),
				type: 'bullets',
				clickable: !!cfg.paginationClickable
			};
		}

		var swiper = new window.Swiper(swiperEl, options);

		// توقف پخش خودکار وقتی تب پنهان است یا کاربر مدتی غایب است
		document.addEventListener('visibilitychange', function () {
			if (!swiper.autoplay) {
				return;
			}
			if (document.hidden) {
				swiper.autoplay.stop();
			} else if (cfg.autoplay) {
				swiper.autoplay.start();
			}
		});
	}

	function initAll(scope) {
		(scope || document).querySelectorAll('.amw-hero').forEach(setup);
	}

	if (window.elementorFrontend && window.elementorFrontend.hooks) {
		window.elementorFrontend.hooks.addAction('frontend/element_ready/almasara-hero-slider.default', function ($el) {
			initAll($el && $el[0] ? $el[0] : document);
		});
	}

	if (document.readyState !== 'loading') {
		initAll(document);
	} else {
		document.addEventListener('DOMContentLoaded', function () { initAll(document); });
	}
})();
