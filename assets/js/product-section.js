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

	/** آیا داخل پیش‌نمایش ادیتور المنتور هستیم؟ */
	function isEditor() {
		return !!(
			window.elementorFrontend &&
			typeof window.elementorFrontend.isEditMode === 'function' &&
			window.elementorFrontend.isEditMode()
		);
	}

	function buildOptions(cfg, root) {
		var editing = isEditor();

		var options = {
			speed: cfg.speed || 600,
			slidesPerView: cfg.slidesPerView || 1,
			spaceBetween: cfg.spaceBetween || 0,
			breakpoints: cfg.breakpoints || {},
			rewind: !!cfg.rewind,
			rtl: !!cfg.rtl,
			// ویجت اغلب وقتی ساخته می‌شود که هنوز پهنای واقعی ندارد (پنل باز/
			// بسته می‌شود، تب عوض می‌شود، ویجت جابه‌جا می‌شود). resizeObserver
			// مکانیزم درست برای «کادر اندازه‌اش عوض شد» است و برخلاف رصد جهش‌های
			// DOM هزینه‌ای روی هر تغییر بی‌ربط ندارد.
			resizeObserver: true,
			observer: true,
			// در ادیتور عمداً خاموش: المنتور مدام به والدها کلاس و اورلی و
			// دستگیره اضافه/کم می‌کند و هر کدام یک update() شلیک می‌کند —
			// همین رگبارِ به‌روزرسانی پیش‌نمایش را ناپایدار می‌کرد.
			observeParents: !editing,
			observeSlideChildren: !editing
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
		if (root.__amwPsSwiper && !root.__amwPsSwiper.destroyed) {
			root.__amwPsSwiper.destroy(true, true);
		}
		root.__amwPsSwiper = createSwiper(root, cfg);
		remember(root, root.__amwPsSwiper);
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

	/*
	 * دفتر نمونه‌های زنده.
	 *
	 * ادیتور المنتور با هر تغییرِ کنترلِ محتوا کل ویجت را دوباره رندر می‌کند،
	 * یعنی نودِ .amw-ps قبلی از سند جدا می‌شود و یکی تازه جایش می‌آید. بدون
	 * این دفتر، سوایپرِ نودِ قدیمی هیچ‌وقت destroy نمی‌شد و با observer و
	 * resizeObserver خودش زنده می‌ماند؛ بعد از چند ویرایش ده‌ها نمونهٔ زامبی
	 * هم‌زمان واکنش نشان می‌دادند و پیش‌نمایش را ناپایدار می‌کردند.
	 */
	var instances = [];

	function reap() {
		instances = instances.filter(function (entry) {
			if (document.contains(entry.root)) {
				return true;
			}
			if (entry.swiper && !entry.swiper.destroyed) {
				// نود از سند جدا شده، پس پاک‌کردن استایل‌ها بی‌معناست؛ فقط
				// شنونده‌ها و observerها باید رها شوند
				try { entry.swiper.destroy(true, false); } catch (e) {}
			}
			return false;
		});
	}

	function remember(root, swiper) {
		if (!swiper) {
			return;
		}
		// یک ریشه همیشه فقط یک ردیف دارد؛ بازسازی (مثلاً بعد از فیلتر دسته)
		// نباید ردیف‌های مرده روی هم انباشته کند
		instances = instances.filter(function (entry) { return entry.root !== root; });
		instances.push({ root: root, swiper: swiper });
	}

	/*
	 * صفِ انتظارِ کتابخانه.
	 *
	 * اسکریپت ویجت ممکن است پیش از خودِ Swiper اجرا شود. نسخهٔ قبلی برای هر
	 * ریشه یک زنجیرهٔ requestAnimationFrame می‌ساخت و بعد از ۶۰ فریم — یعنی
	 * حدوداً یک ثانیه — برای همیشه تسلیم می‌شد. در فرانت صفحه سبک است و
	 * کتابخانه سر یکی دو فریم می‌رسد، ولی در آی‌فریمِ پیش‌نمایش ادیتور که
	 * حجم زیادی JS لود می‌شود این مهلت راحت تمام می‌شد و اسلایدر هیچ‌وقت
	 * بالا نمی‌آمد: کارت‌ها بدون عرض و بدون فاصله به هم می‌چسبیدند.
	 *
	 * حالا یک صف مشترک با بازهٔ زمانی واقعی (نه تعداد فریم) منتظر می‌ماند.
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

		var waitedMs = 0;
		poller = window.setInterval(function () {
			waitedMs += 100;

			if (window.Swiper) {
				window.clearInterval(poller);
				poller = null;
				var queue = pending;
				pending = [];
				queue.forEach(setup);
				return;
			}

			// ۲۰ ثانیه بیش از هر بارگذاری معقولی است؛ اگر تا اینجا نیامده
			// یعنی اسکریپت واقعاً لود نشده و ادامهٔ نظرزدن بی‌فایده است
			if (waitedMs >= 20000) {
				window.clearInterval(poller);
				poller = null;
				pending = [];
			}
		}, 100);
	}

	function setup(root) {
		if (root.__amwPs) {
			return;
		}
		if (!root.querySelector('.amw-ps__slider')) {
			return;
		}

		// عمداً علامت «مقداردهی شد» زده نمی‌شود تا وقتی کتابخانه رسید دوباره
		// تلاش شود
		if (!window.Swiper) {
			waitForSwiper(root);
			return;
		}

		root.__amwPs = true;

		try {
			var cfg = parseCfg(root);
			root.__amwPsSwiper = createSwiper(root, cfg);
			remember(root, root.__amwPsSwiper);

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
		reap();
		(scope || document).querySelectorAll('.amw-ps').forEach(setup);
	}

	function bindElementor() {
		if (!window.elementorFrontend || !window.elementorFrontend.hooks) {
			return false;
		}
		window.elementorFrontend.hooks.addAction('frontend/element_ready/almasara-product-section.default', function ($el) {
			initAll($el && $el[0] ? $el[0] : document);
		});
		return true;
	}

	// در پیش‌نمایش ادیتور، اسکریپت ما معمولاً پیش از elementorFrontend اجرا
	// می‌شود؛ آن‌وقت هوک هیچ‌وقت ثبت نمی‌شد و همه‌چیز به شبکهٔ ایمنی می‌افتاد.
	if (!bindElementor()) {
		window.addEventListener('elementor/frontend/init', bindElementor);
	}

	if (document.readyState !== 'loading') {
		initAll(document);
	} else {
		document.addEventListener('DOMContentLoaded', function () { initAll(document); });
	}

	/*
	 * شبکه ایمنی برای فرانت‌اند: اگر افزونهٔ دیگری با خطای JS حلقهٔ
	 * element_ready المنتور را نصفه بگذارد، ویجت باز هم بالا می‌آید.
	 *
	 * در ادیتور عمداً روشن نمی‌شود: آنجا element_ready قابل‌اتکاست و در عوض
	 * المنتور آن‌قدر DOM را دستکاری می‌کند که رصد کل body — حتی throttle‌شده —
	 * به یک پیمایش دائمی صفحه تبدیل می‌شد.
	 */
	if (window.MutationObserver && document.body) {
		var queued = false;
		var scan = function () {
			// isEditor() عمداً اینجا — نه موقع نصب — سنجیده می‌شود:
			// هنگام اجرای این فایل، elementorFrontend معمولاً هنوز تعریف
			// نشده، پس تصمیم‌گیری در آن لحظه با اطلاعات غلط انجام می‌شد.
			if (queued || isEditor()) {
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
