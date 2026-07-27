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

	/*
	 * صفِ انتظارِ کتابخانه.
	 *
	 * قبلاً وقتی window.Swiper نبود، فقط فلگ init ست نمی‌شد و امید بسته بود
	 * به اینکه MutationObserver دوباره صدا بزند. همین باعث شده بود آن
	 * observer روی کل صفحه و بدون throttle بماند. با یک انتظار صریح و
	 * زمان‌دار، observer به شبکهٔ ایمنیِ واقعی تبدیل می‌شود و می‌شود مهارش کرد.
	 */
	var pending = [];
	var poller = null;

	function waitForSwiper(root) {
		if (pending.indexOf(root) === -1) {
			pending.push(root);
		}
		if (poller) {
			return;
		}

		var waited = 0;
		poller = window.setInterval(function () {
			waited += 100;

			if (window.Swiper) {
				window.clearInterval(poller);
				poller = null;
				var queue = pending;
				pending = [];
				queue.forEach(setup);
				return;
			}

			if (waited >= 20000) {
				window.clearInterval(poller);
				poller = null;
				pending = [];
			}
		}, 100);
	}

	function setup(root) {
		if (root.__amwHero) {
			return;
		}

		// فلگ init عمداً ست نمی‌شود تا وقتی کتابخانه رسید دوباره تلاش شود
		if (!window.Swiper) {
			waitForSwiper(root);
			return;
		}

		var swiperEl = root.querySelector('.amw-hero__swiper');
		var skeleton = root.querySelector('.amw-hero__skeleton');
		if (!swiperEl) {
			return;
		}

		root.__amwHero = true;

		try {
			buildSwiper(root, swiperEl, skeleton);
		} catch (e) {
			// اجازه نده خطای خودمان بقیه ویجت‌های صفحه را در همان حلقه بخواباند
			if (window.console && console.error) {
				console.error('[almasara-hero-slider] init failed:', e);
			}
		}
	}

	function buildSwiper(root, swiperEl, skeleton) {
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

	/**
	 * شبکه ایمنی: اگر افزونه دیگری در همان حلقه frontend/element_ready خطای
	 * uncaught بدهد، هوک‌های صف‌شده بعد از آن (از جمله همین ویجت) ممکن است
	 * اصلاً اجرا نشوند.
	 *
	 * حتماً throttle می‌شود: نسخهٔ قبلی به‌ازای هر جهش DOM یک پیمایش کامل
	 * صفحه راه می‌انداخت و در فرانت‌اند هم فعال بود، نه فقط در ویرایشگر.
	 * isEditor هم داخل callback سنجیده می‌شود نه موقع نصب، چون در آن لحظه
	 * elementorFrontend معمولاً هنوز تعریف نشده است.
	 */
	if (window.MutationObserver && document.body) {
		var queued = false;
		var scan = function () {
			var editing = window.elementorFrontend
				&& typeof window.elementorFrontend.isEditMode === 'function'
				&& window.elementorFrontend.isEditMode();

			if (queued || editing) {
				return;
			}
			queued = true;
			window.requestAnimationFrame(function () {
				queued = false;
				initAll(document);
			});
		};
		new MutationObserver(scan).observe(document.body, { childList: true, subtree: true });
	}
})();
