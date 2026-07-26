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
			rtl: !!cfg.rtl,
			// در ادیتور المنتور، ویجت اغلب وقتی ساخته می‌شود که هنوز پهنای
			// واقعی ندارد (پنل باز/بسته می‌شود، تب عوض می‌شود، ویجت جابه‌جا
			// می‌شود). بدون این‌ها سوایپر یک‌بار با عرض غلط اندازه می‌گیرد و
			// همان‌طور خراب می‌ماند — علت «گاهی پیش‌نمایش به هم می‌ریزد».
			observer: true,
			observeParents: true,
			observeSlideChildren: true
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
		// aria-busy تنها راهی است که صفحه‌خوان بفهمد محتوا در حال تعویض است؛
		// خودِ اسکلت‌ها aria-hidden هستند و خوانده نمی‌شوند.
		if (on) {
			root.setAttribute('aria-busy', 'true');
		} else {
			root.removeAttribute('aria-busy');
		}
	}

	/**
	 * اسکلت بارگذاری: به‌جای خالی‌کردن یا محوکردن اسلایدر، همان تعداد کارتِ
	 * خاکستری با ابعاد واقعی گذاشته می‌شود تا ارتفاع بخش ثابت بماند و چیدمان
	 * صفحه نپرد (CLS). مارکاپ اسکلت با کارت واقعی یکی است تا اندازه‌ها بخوانند.
	 */
	function showSkeletons(root, cfg) {
		var wrapper = root.querySelector('.amw-ps__slider .swiper-wrapper');
		if (!wrapper) {
			return;
		}

		// به‌اندازهٔ کارت‌هایی که واقعاً دیده می‌شوند، نه کل تعداد کوئری
		var visible = Math.ceil(parseFloat(cfg.slidesPerView) || 0) || wrapper.children.length || 4;
		var count = Math.max(1, Math.min(visible + 1, parseInt(cfg.count, 10) || visible));

		var slide = '<div class="swiper-slide"><div class="amw-ps__card">'
			+ '<div class="amw-card amw-card--skeleton" aria-hidden="true">'
			+ '<div class="amw-card__media"><span class="amw-skeleton amw-skeleton--media"></span></div>'
			+ '<div class="amw-card__body">'
			+ '<span class="amw-skeleton amw-skeleton--line"></span>'
			+ '<span class="amw-skeleton amw-skeleton--line amw-skeleton--short"></span>'
			+ '<span class="amw-skeleton amw-skeleton--line amw-skeleton--price"></span>'
			+ '</div></div></div></div>';

		wrapper.innerHTML = new Array(count + 1).join(slide);

		// اسلایدهای تازه هنوز برای سوایپر ناشناخته‌اند و عرض نگرفته‌اند؛ بدون
		// این، اسکلت‌ها تا رسیدن پاسخ بدقواره می‌مانند. observer هم همین کار را
		// می‌کند ولی async است، این صریح و قطعی است.
		if (root.__amwPsSwiper && !root.__amwPsSwiper.destroyed) {
			root.__amwPsSwiper.update();
		}
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
			cache: cfg.cache || 0,
			has_price: cfg.hasPrice ? 1 : 0,
			in_stock: cfg.inStock ? 1 : 0,
			has_image: cfg.hasImage ? 1 : 0,
			min_price: cfg.minPrice || 0,
			source: cfg.source || 'jetengine',
			card: cfg.card || ''
		});

		var wrapper = root.querySelector('.amw-ps__slider .swiper-wrapper');
		// چون اسکلت جای محتوای فعلی را می‌گیرد، نسخهٔ قبلی نگه داشته می‌شود تا
		// اگر درخواست شکست خورد، کاربر با اسکلتِ گیرکرده تنها نماند
		var previous = wrapper ? wrapper.innerHTML : '';
		var replaced = false;

		setLoading(root, true);
		showSkeletons(root, cfg);

		fetch(cfg.restUrl + '?' + params.toString(), { credentials: 'same-origin' })
			.then(function (r) { return r.json(); })
			.then(function (data) {
				if (wrapper && data && typeof data.html === 'string') {
					wrapper.innerHTML = data.html;
					replaced = true;
				}
			})
			.catch(function () { /* شبکه‌ای که موقتاً قطع است نباید UI را بشکند */ })
			.then(function () {
				if (!replaced && wrapper) {
					wrapper.innerHTML = previous;
				}
				rebuildSwiper(root, cfg);
				setLoading(root, false);
			});
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
	//
	// حتماً throttle می‌شود: بدون آن، هر جهش DOM یک پیمایش کامل صفحه راه
	// می‌انداخت — در ادیتور المنتور که مدام DOM را بازمی‌سازد، همین باعث
	// کندی و به‌هم‌ریختن پیش‌نمایش می‌شد.
	if (window.MutationObserver && document.body) {
		var queued = false;
		var scan = function () {
			if (queued) {
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
