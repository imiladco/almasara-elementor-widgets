/**
 * مودال گالری محصول الماسارا
 *
 * - تصاویر کامل فقط بعد از باز شدن مودال و به‌صورت ایجکسی (REST) لود می‌شوند
 * - هر تصویر فقط لحظه نمایش دانلود می‌شود و تصاویر مجاور از قبل preload می‌شوند
 * - ناوبری با کلیک، کیبورد (Esc / فلش‌ها) و سوایپ لمسی
 */
(function () {
	'use strict';

	function setup(root) {
		if (root.__amwPg) {
			return;
		}
		root.__amwPg = true;

		var modal = root.querySelector('.amw-pg-modal');
		var endpoint = root.dataset.endpoint;
		if (!modal || !endpoint) {
			return;
		}

		var imgEl = modal.querySelector('.amw-pg-modal__img');
		var spinner = modal.querySelector('.amw-pg-modal__spinner');
		var strip = modal.querySelector('.amw-pg-modal__strip');
		var closeBtn = modal.querySelector('.amw-pg-modal__close');
		var prevBtn = modal.querySelector('.amw-pg-modal__nav--prev');
		var nextBtn = modal.querySelector('.amw-pg-modal__nav--next');

		var images = null;
		var fetching = null;
		var current = 0;
		var isOpen = false;
		var isRtl = document.documentElement.dir === 'rtl';

		function fetchImages() {
			if (images) {
				return Promise.resolve(images);
			}
			if (!fetching) {
				fetching = fetch(endpoint)
					.then(function (res) {
						if (!res.ok) {
							throw new Error('HTTP ' + res.status);
						}
						return res.json();
					})
					.then(function (data) {
						images = Array.isArray(data) ? data : [];
						buildStrip();
						return images;
					})
					.catch(function () {
						fetching = null;
						images = null;
						return [];
					});
			}
			return fetching;
		}

		function buildStrip() {
			strip.innerHTML = '';
			images.forEach(function (item, i) {
				var btn = document.createElement('button');
				btn.type = 'button';
				btn.setAttribute('aria-label', String(i + 1) + ' / ' + String(images.length));
				var thumb = document.createElement('img');
				thumb.src = item.thumb;
				thumb.alt = item.alt || '';
				thumb.loading = 'lazy';
				btn.appendChild(thumb);
				btn.addEventListener('click', function () {
					show(i);
				});
				strip.appendChild(btn);
			});
		}

		function preload(index) {
			if (!images || !images.length) {
				return;
			}
			var item = images[(index + images.length) % images.length];
			if (item && !item.__preloaded) {
				item.__preloaded = true;
				var im = new Image();
				im.src = item.full;
			}
		}

		function show(index) {
			if (!images || !images.length) {
				return;
			}
			current = (index + images.length) % images.length;
			var item = images[current];

			spinner.hidden = false;
			imgEl.classList.add('is-loading');

			var loader = new Image();
			loader.onload = function () {
				imgEl.src = item.full;
				imgEl.alt = item.alt || '';
				spinner.hidden = true;
				imgEl.classList.remove('is-loading');
			};
			loader.onerror = function () {
				spinner.hidden = true;
				imgEl.classList.remove('is-loading');
			};
			loader.src = item.full;

			// تامبنیل فعال + اسکرول به دید
			Array.prototype.forEach.call(strip.children, function (btn, i) {
				btn.classList.toggle('is-active', i === current);
			});
			var activeBtn = strip.children[current];
			if (activeBtn && activeBtn.scrollIntoView) {
				activeBtn.scrollIntoView({ block: 'nearest', inline: 'center', behavior: 'smooth' });
			}

			// preload تصاویر مجاور
			preload(current + 1);
			preload(current - 1);
		}

		function open(index) {
			isOpen = true;
			modal.setAttribute('aria-hidden', 'false');
			document.body.classList.add('amw-pg-noscroll');

			// یک reflow کوچک تا transition درست بازی کند بعد افزودن کلاس
			// eslint-disable-next-line no-unused-expressions
			modal.offsetHeight;
			modal.classList.add('is-open');

			fetchImages().then(function (list) {
				if (list.length) {
					show(index);
				}
			});
			closeBtn.focus({ preventScroll: true });
		}

		function close() {
			isOpen = false;
			modal.classList.remove('is-open');
			modal.setAttribute('aria-hidden', 'true');
			document.body.classList.remove('amw-pg-noscroll');
		}

		function next() {
			show(current + 1);
		}

		function prev() {
			show(current - 1);
		}

		// تریگرها: تصویر شاخص و تامبنیل‌ها
		root.querySelectorAll('.amw-pg__main[data-index], .amw-pg__thumb[data-index]').forEach(function (el) {
			el.addEventListener('click', function () {
				open(parseInt(el.dataset.index, 10) || 0);
			});
		});

		closeBtn.addEventListener('click', close);
		nextBtn.addEventListener('click', next);
		prevBtn.addEventListener('click', prev);

		// کلیک روی فضای خالی مودال = بستن
		modal.addEventListener('click', function (e) {
			if (e.target === modal) {
				close();
			}
		});

		// کیبورد
		document.addEventListener('keydown', function (e) {
			if (!isOpen) {
				return;
			}
			if (e.key === 'Escape') {
				close();
			} else if (e.key === 'ArrowLeft') {
				isRtl ? next() : prev();
			} else if (e.key === 'ArrowRight') {
				isRtl ? prev() : next();
			}
		});

		// سوایپ لمسی
		var touchX = null;
		modal.addEventListener('touchstart', function (e) {
			touchX = e.changedTouches[0].clientX;
		}, { passive: true });
		modal.addEventListener('touchend', function (e) {
			if (touchX === null) {
				return;
			}
			var delta = e.changedTouches[0].clientX - touchX;
			touchX = null;
			if (Math.abs(delta) < 40) {
				return;
			}
			// سوایپ به چپ در RTL یعنی تصویر بعدی
			if (delta < 0) {
				isRtl ? next() : prev();
			} else {
				isRtl ? prev() : next();
			}
		}, { passive: true });
	}

	function initAll(scope) {
		(scope || document).querySelectorAll('.amw-pg').forEach(setup);
	}

	if (window.elementorFrontend && window.elementorFrontend.hooks) {
		window.elementorFrontend.hooks.addAction(
			'frontend/element_ready/almasara-product-gallery.default',
			function ($el) {
				initAll($el && $el[0] ? $el[0] : document);
			}
		);
	}

	if (document.readyState !== 'loading') {
		initAll(document);
	} else {
		document.addEventListener('DOMContentLoaded', function () {
			initAll(document);
		});
	}
})();
