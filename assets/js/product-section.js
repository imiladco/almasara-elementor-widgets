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

	/*
	 * نگهبان پیکربندی.
	 *
	 * سمت سرور هم اصلاح شده، ولی این لایه لازم است: HTML رندرشده — با همین
	 * cfg داخلش — کش می‌شود؛ هم ترنزینت خودمان، هم کش صفحه، هم CDN. پس
	 * صفحه‌ای که با پیکربندی خرابِ قبلی ساخته شده تا انقضای آن کش‌ها همان را
	 * به مرورگر می‌دهد. اینجا جلویش گرفته می‌شود.
	 *
	 * بدترین مقدار، slidesPerView صفر است: سوایپر هیچ خطایی نمی‌دهد، فقط
	 * تقسیم بر صفر می‌کند و کل هندسه‌اش NaN می‌شود — اسلایدر حرکت نمی‌کند و
	 * چیدمان از همان لحظهٔ init به هم می‌ریزد، در حالی که پیش از init (که
	 * چیدمان از CSS و بر حسب درصد می‌آمد) درست بود.
	 */
	function positive(value, fallback) {
		var n = parseFloat(value);

		return (Number.isFinite(n) && n > 0) ? n : fallback;
	}

	/** slidesPerView معتبر: عدد مثبت یا 'auto' برای حالت عرض دستی کارت */
	function perView(value, fallback) {
		return 'auto' === value ? 'auto' : positive(value, fallback);
	}

	/** فاصله: صفر انتخاب کاملاً معتبری است، فقط عددِ بی‌معنا رد می‌شود */
	function spacing(value) {
		var n = parseFloat(value);

		return (Number.isFinite(n) && n >= 0) ? n : 0;
	}

	function sanitizeBreakpoints(raw, fallbackPerView) {
		var out = {};

		Object.keys(raw || {}).forEach(function (key) {
			var bp = raw[key] || {};

			out[key] = {
				speed: positive(bp.speed, 600),
				slidesPerView: perView(bp.slidesPerView, fallbackPerView),
				spaceBetween: spacing(bp.spaceBetween)
			};
		});

		return out;
	}

	function buildOptions(cfg, root) {
		var editing = isEditor();

		/*
		 * پشتیبانِ تعداد کارت از خودِ CSS خوانده می‌شود، نه از یک عدد ثابت.
		 * ‎--amw-ps-spv‎ همان چیزی است که کاربر برای همین دستگاه تنظیم کرده و
		 * همان است که چیدمانِ پیش از init با آن ساخته می‌شود. پس اگر
		 * پیکربندی خراب باشد، سوایپر دقیقاً همان چیدمانی را ادامه می‌دهد که
		 * کاربر تا آن لحظه می‌دیده — نه یک عدد دلبخواه.
		 */
		var fallbackPerView = positive(
			window.getComputedStyle(root).getPropertyValue('--amw-ps-spv'),
			1
		);

		var options = {
			speed: positive(cfg.speed, 600),
			slidesPerView: perView(cfg.slidesPerView, fallbackPerView),
			spaceBetween: spacing(cfg.spaceBetween),
			breakpoints: sanitizeBreakpoints(cfg.breakpoints, fallbackPerView),
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

	/** عرض محتوای عنصر — دقیقاً همان چیزی که سوایپر برای خودش حساب می‌کند */
	function contentWidth(el) {
		var cs = window.getComputedStyle(el);

		return el.clientWidth
			- (parseFloat(cs.paddingLeft) || 0)
			- (parseFloat(cs.paddingRight) || 0);
	}

	/*
	 * دیده‌بان اندازه.
	 *
	 * سوایپر عرض را یک‌بار می‌سنجد و به‌صورت پیکسلی روی هر اسلاید می‌نشاند.
	 * تا پیش از init، چیدمان از CSS و بر حسب درصد می‌آید و همیشه با عرض
	 * واقعی جور است؛ از آن لحظه به بعد، همان عددِ ثابت حاکم است. پس اگر آن
	 * یک اندازه‌گیری سرِ لحظهٔ نامناسبی افتاده باشد، خرابی دائمی می‌شود:
	 * کارت‌ها باریک‌تر (یا پهن‌تر) از جای واقعی‌شان می‌مانند و نوار اسلایدر
	 * با عرض بخش نمی‌خواند — یعنی دقیقاً «تا لود کامل درست بود، بعدش به هم
	 * ریخت».
	 *
	 * روی سایت واقعی خیلی راحت پیش می‌آید: فونتی که دیر می‌رسد، اسکرول‌باری
	 * که با کامل‌شدن صفحه ظاهر می‌شود، والدی که هنوز چیدمانش تمام نشده، یا
	 * افزونهٔ بهینه‌سازی که اجرای همهٔ اسکریپت‌ها را تا اولین تعامل کاربر عقب
	 * می‌اندازد و آن‌ها را وسط یک بازچینش سنگین اجرا می‌کند.
	 *
	 * observer و resizeObserver خودِ سوایپر اینجا کمکی نمی‌کنند: آن‌ها وقتی
	 * شلیک می‌شوند که اندازه «عوض شود». اگر از همان اول غلط خوانده شده باشد
	 * و بعد چیزی تغییر نکند، هیچ‌کدام بیدار نمی‌شوند.
	 *
	 * پس خودمان مقایسه می‌کنیم: عرض واقعی عنصر با عددی که سوایپر در ذهن
	 * دارد. اختلاف بیش از یک پیکسل یعنی اندازه‌گیری کهنه است و update()
	 * تازه‌اش می‌کند. بررسی‌ها محدود و ارزان‌اند.
	 */
	function watchSize(swiper) {
		if (!swiper) {
			return;
		}

		var timers = [];

		var check = function () {
			if (swiper.destroyed || !document.contains(swiper.el)) {
				timers.forEach(window.clearTimeout);
				return;
			}

			var real = contentWidth(swiper.el);

			// عرض صفر یعنی عنصر هنوز نمایش داده نمی‌شود (تب بسته، والدِ
			// display:none)؛ آنجا update() هم چیزی برای سنجیدن ندارد
			if (real > 0 && Math.abs(real - swiper.size) > 1) {
				swiper.update();
			}
		};

		[0, 250, 750, 2000, 5000].forEach(function (delay) {
			timers.push(window.setTimeout(check, delay));
		});

		// دو نقطهٔ قطعی که چیدمان در آن‌ها نهایی می‌شود
		window.addEventListener('load', check, { once: true });

		if (document.fonts && document.fonts.ready && document.fonts.ready.then) {
			document.fonts.ready.then(check).catch(function () { /* بی‌اهمیت */ });
		}
	}

	function createSwiper(root, cfg) {
		var swiperEl = root.querySelector('.amw-ps__slider');
		if (!swiperEl || !window.Swiper) {
			return null;
		}

		var swiper = new window.Swiper(swiperEl, buildOptions(cfg, root));
		watchSize(swiper);

		return swiper;
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

		// اسکلت عمداً از همان کلاس‌های کارت واقعی ساخته می‌شود (amw-card،
		// ‎__media، ‎__body، ‎__title، ‎__price). یعنی هر استایلی که کاربر روی
		// کارت گذاشته — پدینگ، رادیوس، پس‌زمینه، حاشیه، چیدمان ردیف قیمت —
		// بدون هیچ تنظیم جداگانه‌ای روی اسکلت هم می‌افتد و شبح، دقیقاً قالبِ
		// همان کارت را نشان می‌دهد. المان تصویر کلاس amw-card__img را هم
		// می‌گیرد تا عرض و ارتفاع و نسبت ابعادِ تنظیم‌شده را عیناً بگیرد.
		var slide = '<div class="swiper-slide"><div class="amw-ps__card">'
			+ '<div class="amw-card amw-card--skeleton" aria-hidden="true">'
			+ '<div class="amw-card__media">'
			+ '<span class="amw-card__img amw-skeleton amw-skeleton--media"></span>'
			+ '</div>'
			+ '<div class="amw-card__body">'
			+ '<div class="amw-card__title">'
			+ '<span class="amw-skeleton amw-skeleton--line"></span>'
			+ '<span class="amw-skeleton amw-skeleton--line amw-skeleton--short"></span>'
			+ '</div>'
			+ '<div class="amw-card__price">'
			+ '<span class="amw-skeleton amw-skeleton--slogan"></span>'
			+ '<span class="amw-skeleton amw-skeleton--amount"></span>'
			+ '</div>'
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

		// فقط «کدام ویجت» و «کدام دسته». قالب کارت، تعداد، مرتب‌سازی، فیلترها
		// و مدت کش را سرور از تنظیمات ذخیره‌شدهٔ همان ویجت می‌خواند — وگرنه
		// هرکسی می‌توانست رندرِ ۴۸ کارت بدون کش را از بیرون درخواست کند.
		var params = new URLSearchParams({
			post_id: cfg.postId,
			element_id: cfg.elementId,
			category: termId
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
			// سوایپر ممکن است پیش از خطا کلاس swiper-initialized را گذاشته
			// باشد. اگر بماند، قواعد فاصلهٔ ما کنار می‌روند در حالی که هیچ
			// اندازه‌گذاری اینلاینی هم انجام نشده — یعنی چیدمان بدتر از
			// حالتی می‌شود که اصلاً اسلایدری در کار نبود. پس پاکش می‌کنیم تا
			// CSS دوباره کنترل را دست بگیرد.
			var el = root.querySelector('.amw-ps__slider');
			if (el) {
				el.classList.remove('swiper-initialized');
			}

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
		// فقط وقتی گره‌ای واقعاً اضافه شده باشد؛ حذفِ تنها نمی‌تواند ویجت
		// تازه‌ای بیاورد، پس اسکنش بی‌فایده است
		new MutationObserver(function (records) {
			for (var i = 0; i < records.length; i++) {
				if (records[i].addedNodes.length) {
					scan();
					return;
				}
			}
		}).observe(document.body, { childList: true, subtree: true });
	}
})();
