/**
 * تست مرورگری ویجت بخش محصولات.
 *
 * چیزی که هیچ تست PHP‌ای نمی‌گیرد: اینکه اسلایدر در مرورگر واقعی درست
 * اندازه بگیرد و واقعاً حرکت کند. این لایه بعد از یک باگ اضافه شد که در آن
 * کارت‌ها تمام‌عرض می‌شدند و اسلایدر از کار می‌افتاد — چیزی که فقط با
 * اجرای واقعی CSS و JS در کنار هم دیده می‌شود.
 *
 *   bash tests/integration/setup.sh /tmp/wpint
 *   bash tests/e2e/setup.sh /tmp/wpint
 *   WP_ROOT=/tmp/wpint/wp node tests/e2e/run.mjs
 */

import { spawn, spawnSync } from 'node:child_process';
import { cpSync, existsSync, readdirSync, rmSync } from 'node:fs';
import { join } from 'node:path';

const WP_ROOT = process.env.WP_ROOT;
const PORT = process.env.PORT || 8899;
const URL = `http://127.0.0.1:${PORT}/amw-e2e/`;

if (!WP_ROOT || !existsSync(`${WP_ROOT}/wp-load.php`)) {
	console.error(`
	تست مرورگری رد شد: نصب وردپرس پیدا نشد.

	    bash tests/integration/setup.sh /tmp/wpint
	    bash tests/e2e/setup.sh /tmp/wpint
	    WP_ROOT=/tmp/wpint/wp node tests/e2e/run.mjs
	`);
	process.exit(0);
}

let chromium;
try {
	({ chromium } = await import('playwright'));
} catch {
	console.error('تست مرورگری رد شد: playwright نصب نیست (npm i -D playwright).');
	process.exit(0);
}

/* -------------------------------------------------------------------------
 * assert
 * ---------------------------------------------------------------------- */

let passed = 0;
const failures = [];

function ok(label, condition, detail = '') {
	if (condition) {
		passed++;
		console.log(`  \x1b[32m✓\x1b[0m ${label}`);
		return;
	}
	failures.push(label + (detail ? ` — ${detail}` : ''));
	console.log(`  \x1b[31m✗ ${label}\x1b[0m${detail ? ` — ${detail}` : ''}`);
}

function group(name) {
	console.log(`\n\x1b[1m${name}\x1b[0m`);
}

/* -------------------------------------------------------------------------
 * همگام‌سازی افزونه با پوشهٔ کاری
 * ---------------------------------------------------------------------- */

/*
 * صفحه، افزونه را از wp-content/plugins می‌خواند نه از پوشهٔ کاری. آن کپی را
 * setup.sh یک‌بار می‌سازد، پس هر ویرایشی بعد از آن برای مرورگر نامرئی است و
 * تست بی‌صدا نسخهٔ کهنه را سبز اعلام می‌کند — همان چیزی که یک‌بار سرِ
 * تست‌های یکپارچه اتفاق افتاد.
 *
 * پس هر اجرا اول کپی را تازه می‌کند. کش المنتور هم پاک می‌شود، وگرنه HTML
 * ساخته‌شدهٔ قبلی سرو می‌شود.
 */
const PLUGIN_SRC = join(import.meta.dirname, '..', '..');
const PLUGIN_DEST = join(WP_ROOT, 'wp-content', 'plugins', 'almasara-elementor-widgets');

if (existsSync(PLUGIN_DEST)) {
	for (const dir of ['assets', 'includes']) {
		rmSync(join(PLUGIN_DEST, dir), { recursive: true, force: true });
		cpSync(join(PLUGIN_SRC, dir), join(PLUGIN_DEST, dir), { recursive: true });
	}
	for (const file of readdirSync(PLUGIN_SRC).filter((f) => f.endsWith('.php'))) {
		cpSync(join(PLUGIN_SRC, file), join(PLUGIN_DEST, file));
	}

	spawnSync('php', ['-r', `require '${WP_ROOT}/wp-load.php';`
		+ ' \\Elementor\\Plugin::$instance->files_manager->clear_cache();'], { stdio: 'ignore' });
}

/* -------------------------------------------------------------------------
 * سرور
 * ---------------------------------------------------------------------- */

const server = spawn('php', ['-S', `127.0.0.1:${PORT}`, '-t', WP_ROOT], { stdio: 'ignore' });
const stop = () => { try { server.kill(); } catch { /* already gone */ } };
process.on('exit', stop);

await new Promise((r) => setTimeout(r, 2500));

/**
 * مسیر کروم.
 *
 * روی CI که `playwright install` اجرا شده، خودِ playwright مرورگرش را پیدا
 * می‌کند و این تابع چیزی برنمی‌گرداند. ولی در محیط‌هایی که مرورگر از پیش
 * نصب شده و نسخه‌اش با نسخهٔ playwright یکی نیست، جست‌وجوی پیش‌فرض شکست
 * می‌خورد؛ آن‌وقت هر کرومی که در PLAYWRIGHT_BROWSERS_PATH باشد کار را
 * راه می‌اندازد.
 */
function findChromium() {
	if (process.env.CHROMIUM_PATH) {
		return process.env.CHROMIUM_PATH;
	}

	const root = process.env.PLAYWRIGHT_BROWSERS_PATH;

	if (!root || !existsSync(root)) {
		return undefined;
	}

	const dirs = readdirSync(root).filter((d) => d.startsWith('chromium-')).sort().reverse();

	for (const dir of dirs) {
		const bin = join(root, dir, 'chrome-linux', 'chrome');

		if (existsSync(bin)) {
			return bin;
		}
	}

	return undefined;
}

const browser = await chromium.launch({
	executablePath: findChromium(),
	args: ['--no-sandbox'],
});

const pageErrors = [];

try {
	const page = await browser.newPage({ viewport: { width: 1400, height: 900 } });

	page.on('pageerror', (e) => pageErrors.push(e.message));

	// فقط منابعِ خودِ سایت مهم‌اند. شکست بارگذاری منابع بیرونی (مثلاً فونت
	// گوگل در محیطی بدون اینترنت) ربطی به افزونه ندارد و نباید تست را
	// قرمز کند.
	page.on('requestfailed', (r) => {
		if (r.url().startsWith(`http://127.0.0.1:${PORT}`)) {
			pageErrors.push(`منبع لود نشد: ${r.url()}`);
		}
	});

	page.on('console', (m) => {
		// «Failed to load resource» را requestfailed بالا پوشش می‌دهد
		if (m.type() === 'error' && !/Failed to load resource/i.test(m.text())) {
			pageErrors.push(m.text());
		}
	});

	await page.goto(URL, { waitUntil: 'networkidle' });
	await page.waitForTimeout(2500);

	group('چیدمان');

	const widgets = await page.$$('.amw-ps');
	ok('ویجت‌ها رندر شده‌اند', widgets.length === 2, `تعداد: ${widgets.length}`);

	const layout = await page.evaluate(() => {
		const root = document.querySelector('.amw-ps');
		const slider = root.querySelector('.amw-ps__slider');
		const slides = [...slider.querySelectorAll('.swiper-slide')];
		const sliderWidth = slider.getBoundingClientRect().width;
		const widths = slides.map((s) => s.getBoundingClientRect().width);

		return {
			initialized: slider.classList.contains('swiper-initialized'),
			slideCount: slides.length,
			sliderWidth,
			// عرض هر اسلاید باید کسری از عرض اسلایدر باشد، نه تمام آن
			widestRatio: Math.max(...widths) / sliderWidth,
			allEqual: widths.every((w) => Math.abs(w - widths[0]) < 2),
			spv: getComputedStyle(root).getPropertyValue('--amw-ps-spv').trim(),
		};
	});

	ok('سوایپر مقداردهی شده', layout.initialized);
	ok('هر ۸ کارت رندر شده', layout.slideCount === 8, `تعداد: ${layout.slideCount}`);
	ok('همهٔ کارت‌ها هم‌عرض‌اند', layout.allEqual);
	ok(
		'هیچ کارتی تمام‌عرض ویجت نیست',
		layout.widestRatio < 0.5,
		`نسبت پهن‌ترین کارت به اسلایدر: ${layout.widestRatio.toFixed(2)}`
	);

	/*
	 * سنجشِ همان چیزی که کاربر گزارش کرد: «تا لود کامل چیدمان درست بود، بعدش
	 * به هم ریخت».
	 *
	 * پیش از init، عرض کارت‌ها از CSS و بر حسب درصد می‌آید و همیشه با عرض
	 * واقعی جور است. بعد از init، عددِ پیکسلیِ سوایپر حاکم می‌شود. اگر آن یک
	 * اندازه‌گیری غلط افتاده باشد، این دو از هم جدا می‌افتند و خرابی دائمی
	 * می‌شود. پس هر دو ثابت را می‌سنجیم: اندازهٔ سوایپر با عرض واقعیِ عنصر
	 * بخواند، و نوارِ کارت‌های دیده‌شده هم‌عرضِ خودِ اسلایدر باشد.
	 */
	group('اندازه‌گیری سوایپر با عرض واقعی می‌خواند');

	// هر دو ویجت سنجیده می‌شوند: دومی عمداً با مقدارِ خالیِ دسکتاپ ذخیره شده
	// (همان پیکربندی‌ای که روی سایت واقعی اسلایدر را از کار انداخته بود)
	const sizing = await page.evaluate(() => [...document.querySelectorAll('.amw-ps')].map((root) => {
		const slider = root.querySelector('.amw-ps__slider');
		const cs = getComputedStyle(slider);
		const real = slider.clientWidth - parseFloat(cs.paddingLeft) - parseFloat(cs.paddingRight);
		const swiper = root.__amwPsSwiper;
		const slides = [...slider.querySelectorAll('.swiper-slide')];
		const spv = parseFloat(getComputedStyle(root).getPropertyValue('--amw-ps-spv')) || 4;
		const gap = parseFloat(getComputedStyle(root).getPropertyValue('--amw-ps-gap')) || 20;

		return {
			real,
			measured: swiper ? swiper.size : null,
			slideWidth: slides[0].getBoundingClientRect().width,
			// همان فرمولی که CSS پیش از init به کار می‌برد
			expected: (real - (spv - 1) * gap) / spv,
			paddingTop: cs.paddingTop,
			marginTop: cs.marginTop,
			cssPerView: spv,
			swiperPerView: swiper ? swiper.params.slidesPerView : null,
		};
	}));

	sizing.forEach((s, i) => {
		const w = `ویجت ${i + 1}`;

		/*
		 * همان چیزی که روی سایت واقعی خراب بود: CSS می‌گفت ۴ کارت و سوایپر
		 * صفر. صفر خطا نمی‌دهد، فقط تقسیم بر صفر می‌کند و هندسه NaN می‌شود —
		 * اسلایدر حرکت نمی‌کند و چیدمان به هم می‌ریزد.
		 */
		ok(
			`${w}: تعداد کارتِ سوایپر با CSS یکی است`,
			s.swiperPerView === s.cssPerView,
			`سوایپر ${s.swiperPerView} ≠ CSS ${s.cssPerView}`
		);
		ok(
			`${w}: اندازهٔ سوایپر با عرض واقعی عنصر یکی است`,
			Math.abs(s.real - s.measured) <= 1,
			`واقعی ${s.real} ≠ سوایپر ${s.measured}`
		);
		ok(
			`${w}: عرض کارت بعد از init همان است که CSS پیش از init می‌داد`,
			Math.abs(s.slideWidth - s.expected) <= 1,
			`بعد ${s.slideWidth.toFixed(2)} ≠ قبل ${s.expected.toFixed(2)}`
		);
		// پدینگِ محافظِ سایه نباید قربانیِ ترتیب بارگذاری شیت‌ها شود؛ اگر
		// «.swiper { padding: 0 }» برنده شود، مارجین منفیِ نامتقارن می‌ماند و
		// اسلایدر ۲۴ پیکسل بالا کشیده می‌شود
		ok(
			`${w}: فضای محافظ سایه سرِ جایش است`,
			parseFloat(s.paddingTop) === -parseFloat(s.marginTop),
			`padding ${s.paddingTop} / margin ${s.marginTop}`
		);
	});

	group('حرکت اسلایدر');

	for (const [i, root] of widgets.entries()) {
		const before = await root.evaluate((r) => getComputedStyle(r.querySelector('.swiper-wrapper')).transform);
		await root.$eval('.amw-ps__btn--next', (b) => b.click());
		await page.waitForTimeout(900);
		const after = await root.evaluate((r) => getComputedStyle(r.querySelector('.swiper-wrapper')).transform);

		ok(`ویجت ${i + 1}: دکمهٔ بعدی اسلایدر را حرکت می‌دهد`, before !== after, `${before} → ${after}`);
	}

	group('بدون جاوااسکریپت');

	// شبیه‌سازی افزونه‌های بهینه‌سازی که اجرای JS را تا تعامل کاربر عقب
	// می‌اندازند: چیدمان باید همچنان درست باشد، فقط بدون حرکت
	const noJs = await browser.newPage({ viewport: { width: 1400, height: 900 }, javaScriptEnabled: false });
	await noJs.goto(URL, { waitUntil: 'domcontentloaded' });
	await noJs.waitForTimeout(600);

	const staticLayout = await noJs.evaluate(() => {
		const slider = document.querySelector('.amw-ps__slider');
		const slides = [...slider.querySelectorAll('.swiper-slide')];
		const widths = slides.map((s) => s.getBoundingClientRect().width);

		return {
			widestRatio: Math.max(...widths) / slider.getBoundingClientRect().width,
			allEqual: widths.every((w) => Math.abs(w - widths[0]) < 2),
		};
	});

	ok(
		'بدون JS هم هیچ کارتی تمام‌عرض نمی‌شود',
		staticLayout.widestRatio < 0.5,
		`نسبت: ${staticLayout.widestRatio.toFixed(2)}`
	);
	ok('بدون JS هم کارت‌ها هم‌عرض‌اند', staticLayout.allEqual);

	await noJs.close();

	group('وقتی سوایپر نصفه‌کاره می‌ماند');

	/*
	 * بدترین حالت واقعی: سوایپر کلاس swiper-initialized را می‌گذارد ولی
	 * اندازه‌گذاری را تمام نمی‌کند — خطای وسط init، یا اجرای نصفه در صفحه‌ای
	 * که افزونهٔ بهینه‌سازی JS را عقب انداخته.
	 *
	 * قبلاً قاعدهٔ عرضِ ما به «:not(.swiper-initialized)» بسته بود، پس در این
	 * حالت کنار می‌رفت و «width:100%» خودِ سوایپر حاکم می‌شد: یک کارت
	 * تمام‌عرض و اسلایدر مرده. این سنجه همان را می‌گیرد.
	 */
	const broken = await browser.newPage({ viewport: { width: 1400, height: 900 } });

	// کتابخانه اصلاً نمی‌رسد، پس هیچ اندازه‌گذاری اینلاینی انجام نمی‌شود
	await broken.route('**/swiper-bundle.min.js*', (r) => r.abort());
	await broken.goto(URL, { waitUntil: 'domcontentloaded' });
	await broken.waitForTimeout(800);

	const brokenLayout = await broken.evaluate(() => {
		const slider = document.querySelector('.amw-ps__slider');
		slider.classList.add('swiper-initialized');

		const slides = [...slider.querySelectorAll('.swiper-slide')];
		const widths = slides.map((s) => s.getBoundingClientRect().width);

		return {
			anyInlineWidth: slides.some((s) => (s.getAttribute('style') || '').includes('width')),
			widestRatio: Math.max(...widths) / slider.getBoundingClientRect().width,
		};
	});

	ok('سوایپر واقعاً اندازه‌گذاری نکرده', !brokenLayout.anyInlineWidth);
	ok(
		'چیدمان به کلاس داخلی سوایپر وابسته نیست',
		brokenLayout.widestRatio < 0.5,
		`نسبت پهن‌ترین کارت: ${brokenLayout.widestRatio.toFixed(2)}`
	);

	await broken.close();

	group('خطاهای صفحه');
	ok('هیچ خطای جاوااسکریپتی نیست', pageErrors.length === 0, pageErrors.slice(0, 3).join(' | '));
} finally {
	await browser.close();
	stop();
}

console.log('\n' + '─'.repeat(60));

if (failures.length) {
	console.log(`\x1b[31m${failures.length} failed\x1b[0m, ${passed} passed\n`);
	failures.forEach((f) => console.log(`  • ${f}`));
	process.exit(1);
}

console.log(`\x1b[32mall ${passed} assertions passed\x1b[0m`);
process.exit(0);
