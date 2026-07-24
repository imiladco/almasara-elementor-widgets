/**
 * ویجت «بخش محصولات» — اسلایدر Swiper + فیلتر AJAX زنده پیل‌های دسته‌بندی.
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

	function buildOptions(cfg, root) {
		var options = {
			speed: cfg.speed || 600,
			slidesPerView: cfg.slidesPerView || 1,
			spaceBetween: cfg.spaceBetween || 0,
			breakpoints: cfg.breakpoints || {},
			rewind: !!cfg.rewind,
			rtl: !!cfg.rtl
		};

		if (cfg.autoplay) {
			options.autoplay = { delay: cfg.delay || 3500, disableOnInteraction: !!cfg.disableOnInteraction };
		}
		if (cfg.navigation) {
			var prev = root.querySelector('.amw-ps__btn--prev');
			var next = root.querySelector('.amw-ps__btn--next');
			if (prev && next) {
				options.navigation = { prevEl: prev, nextEl: next };
			}
		}
		if (cfg.pagination) {
			var pager = root.querySelector('.amw-ps__pagination');
			if (pager) {
				options.pagination = { el: pager, type: 'bullets', clickable: !!cfg.paginationClickable };
			}
		}

		return options;
	}

	function createSwiper(root, cfg) {
		var swiperEl = root.querySelector('.amw-ps__slider');
		if (!swiperEl || !window.Swiper) {
			return null;
		}
		return new window.Swiper(swiperEl, buildOptions(cfg, root));
	}

	/** بعد از تعویض HTML اسلایدها، Swiper باید کامل بازسازی شود (تعداد اسلاید عوض شده) */
	function rebuildSwiper(root, cfg) {
		if (root.__amwPsSwiper) {
			root.__amwPsSwiper.destroy(true, true);
		}
		root.__amwPsSwiper = createSwiper(root, cfg);
	}

	function setLoading(root, on) {
		root.classList.toggle('is-loading', on);
	}

	function filterByCategory(root, cfg, pill) {
		if (root.classList.contains('is-loading')) {
			return;
		}

		var termId = parseInt(pill.dataset.term, 10) || 0;
		var link = pill.dataset.link || '';

		root.querySelectorAll('.amw-ps__pill').forEach(function (p) {
			var active = p === pill;
			p.classList.toggle('is-active', active);
			p.setAttribute('aria-selected', active ? 'true' : 'false');
		});

		var viewAll = root.querySelector('.amw-ps__viewall');
		if (viewAll && link) {
			viewAll.href = link;
		}

		var params = new URLSearchParams({
			listing_id: cfg.listingId,
			category: termId,
			count: cfg.count,
			orderby: cfg.orderby,
			order: cfg.order,
			cache: cfg.cache || 0
		});

		setLoading(root, true);
		fetch(cfg.restUrl + '?' + params.toString(), { credentials: 'same-origin' })
			.then(function (r) { return r.json(); })
			.then(function (data) {
				var wrapper = root.querySelector('.amw-ps__slider .swiper-wrapper');
				if (wrapper && data && typeof data.html === 'string') {
					wrapper.innerHTML = data.html;
					rebuildSwiper(root, cfg);
				}
			})
			.catch(function () { /* شبکه‌ای که موقتاً قطع است نباید UI را بشکند */ })
			.then(function () { setLoading(root, false); });
	}

	function setup(root) {
		if (root.__amwPs || !window.Swiper) {
			return;
		}
		if (!root.querySelector('.amw-ps__slider')) {
			return;
		}
		root.__amwPs = true;

		try {
			var cfg = parseCfg(root);
			root.__amwPsSwiper = createSwiper(root, cfg);

			root.querySelectorAll('.amw-ps__pill').forEach(function (pill) {
				pill.addEventListener('click', function () {
					filterByCategory(root, cfg, pill);
				});
			});
		} catch (e) {
			if (window.console && console.error) {
				console.error('[almasara-product-section] init failed:', e);
			}
		}
	}

	function initAll(scope) {
		(scope || document).querySelectorAll('.amw-ps').forEach(setup);
	}

	if (window.elementorFrontend && window.elementorFrontend.hooks) {
		window.elementorFrontend.hooks.addAction('frontend/element_ready/almasara-product-section.default', function ($el) {
			initAll($el && $el[0] ? $el[0] : document);
		});
	}

	if (document.readyState !== 'loading') {
		initAll(document);
	} else {
		document.addEventListener('DOMContentLoaded', function () { initAll(document); });
	}

	// شبکه ایمنی: مثل ویجت اسلایدر هیرو، در برابر خطای افزونه‌های دیگر که
	// ممکن است حلقه element_ready المنتور را متوقف کنند مقاوم می‌کند.
	if (window.MutationObserver && document.body) {
		new MutationObserver(function () { initAll(document); })
			.observe(document.body, { childList: true, subtree: true });
	}
})();
