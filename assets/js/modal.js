/**
 * کنترلر مشترک مودال‌های الماسارا.
 *
 * پیش از این هر مودال بخشی از کار را خودش و ناقص انجام می‌داد: گالری فقط
 * فوکوس اولیه را می‌گذاشت و مودال دیدگاه حتی همان را هم نداشت؛ هیچ‌کدام
 * فوکوس را داخل مودال نگه نمی‌داشتند، بعد از بستن به عنصر بازکننده
 * برنمی‌گرداندند و پس‌زمینه را از دسترس صفحه‌خوان خارج نمی‌کردند. هر
 * نمونه هم شنوندهٔ سراسری خودش را می‌ساخت.
 *
 * این ماژول همهٔ آن رفتار را یک‌جا و طبق انتظار WCAG پیاده می‌کند و روی
 * window.AlmasaraModal در دسترس است.
 */
(function () {
	'use strict';

	var FOCUSABLE = [
		'a[href]', 'button:not([disabled])', 'input:not([disabled]):not([type="hidden"])',
		'select:not([disabled])', 'textarea:not([disabled])',
		'[tabindex]:not([tabindex="-1"])'
	].join(',');

	// پشتهٔ مودال‌های باز — فقط بالاترین به Escape و Tab پاسخ می‌دهد
	var stack = [];

	function focusable(modal) {
		return Array.prototype.filter.call(
			modal.querySelectorAll(FOCUSABLE),
			function (el) {
				// عناصر پنهان نباید فوکوس بگیرند
				return el.offsetWidth > 0 || el.offsetHeight > 0 || el === document.activeElement;
			}
		);
	}

	function top() {
		return stack.length ? stack[stack.length - 1] : null;
	}

	/** بقیهٔ صفحه از دسترس صفحه‌خوان و Tab خارج می‌شود */
	function setBackgroundInert(modal, on) {
		var parent = modal.parentNode;
		while (parent && parent !== document.body) {
			modal = parent;
			parent = parent.parentNode;
		}
		if (!parent) {
			return;
		}

		Array.prototype.forEach.call(document.body.children, function (child) {
			if (child === modal) {
				return;
			}
			if (on) {
				if (child.hasAttribute('aria-hidden')) {
					child.setAttribute('data-amw-had-hidden', '1');
				}
				child.setAttribute('aria-hidden', 'true');
				child.inert = true;
			} else {
				if (child.getAttribute('data-amw-had-hidden')) {
					child.removeAttribute('data-amw-had-hidden');
				} else {
					child.removeAttribute('aria-hidden');
				}
				child.inert = false;
			}
		});
	}

	function onKeydown(e) {
		var entry = top();
		if (!entry) {
			return;
		}

		if (e.key === 'Escape') {
			e.stopPropagation();
			close(entry.modal);
			return;
		}

		if (e.key !== 'Tab') {
			return;
		}

		// حبس فوکوس: از آخرین عنصر به اولی و برعکس
		var items = focusable(entry.modal);
		if (!items.length) {
			e.preventDefault();
			return;
		}

		var first = items[0];
		var last = items[items.length - 1];
		var active = document.activeElement;

		if (e.shiftKey && (active === first || !entry.modal.contains(active))) {
			e.preventDefault();
			last.focus();
		} else if (!e.shiftKey && active === last) {
			e.preventDefault();
			first.focus();
		}
	}

	/**
	 * @param {Element} modal
	 * @param {Object}  options  initialFocus: Element، onClose: Function
	 */
	function open(modal, options) {
		if (!modal || stack.some(function (e) { return e.modal === modal; })) {
			return;
		}

		options = options || {};

		var entry = {
			modal: modal,
			// عنصری که مودال را باز کرد، تا بعد از بستن فوکوس به آن برگردد
			returnTo: document.activeElement,
			onClose: options.onClose
		};

		stack.push(entry);

		if (stack.length === 1) {
			document.addEventListener('keydown', onKeydown, true);
		}

		modal.classList.add('is-open');
		modal.removeAttribute('aria-hidden');
		document.body.classList.add('amw-pg-noscroll');
		setBackgroundInert(modal, true);

		var target = options.initialFocus || focusable(modal)[0] || modal;
		if (target && target.focus) {
			target.focus({ preventScroll: true });
		}
	}

	function close(modal) {
		var index = -1;
		for (var i = 0; i < stack.length; i++) {
			if (stack[i].modal === modal) {
				index = i;
				break;
			}
		}
		if (index === -1) {
			return;
		}

		var entry = stack.splice(index, 1)[0];

		modal.classList.remove('is-open');
		setBackgroundInert(modal, false);

		if (!stack.length) {
			document.removeEventListener('keydown', onKeydown, true);
			document.body.classList.remove('amw-pg-noscroll');
		}

		if (entry.returnTo && entry.returnTo.focus && document.contains(entry.returnTo)) {
			entry.returnTo.focus({ preventScroll: true });
		}

		if (typeof entry.onClose === 'function') {
			entry.onClose();
		}
	}

	window.AlmasaraModal = { open: open, close: close };
})();
