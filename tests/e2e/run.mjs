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

import { spawn } from 'node:child_process';
import { existsSync } from 'node:fs';

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
 * سرور
 * ---------------------------------------------------------------------- */

const server = spawn('php', ['-S', `127.0.0.1:${PORT}`, '-t', WP_ROOT], { stdio: 'ignore' });
const stop = () => { try { server.kill(); } catch { /* already gone */ } };
process.on('exit', stop);

await new Promise((r) => setTimeout(r, 2500));

const browser = await chromium.launch({
	executablePath: process.env.CHROMIUM_PATH || undefined,
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
