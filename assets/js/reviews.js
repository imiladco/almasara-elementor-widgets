/**
 * ویجت دیدگاه‌های الماسارا — مودال ثبت دیدگاه با ارسال ایجکسی
 */
(function () {
	'use strict';

	function setup(root) {
		if (root.__amwRv) {
			return;
		}
		root.__amwRv = true;

		var modal = root.querySelector('.amw-rv-modal');
		var openBtn = root.querySelector('.amw-rv__open');
		if (!modal || !openBtn) {
			return;
		}

		var closeBtn = modal.querySelector('.amw-rv-modal__close');
		var form = modal.querySelector('.amw-rv-form');

		function open() {
			// این مودال حتی فوکوس اولیه هم نداشت؛ حالا مثل بقیه از کنترلر
			// مشترک می‌گذرد: فوکوس داخلش حبس می‌شود، پس‌زمینه از دسترس خارج
			// و بعد از بستن فوکوس به دکمهٔ بازکننده برمی‌گردد.
			if (window.AlmasaraModal) {
				window.AlmasaraModal.open(modal);
				return;
			}

			modal.classList.add('is-open');
			document.body.classList.add('amw-pg-noscroll');
		}

		function close() {
			if (window.AlmasaraModal) {
				window.AlmasaraModal.close(modal);
				return;
			}

			modal.classList.remove('is-open');
			document.body.classList.remove('amw-pg-noscroll');
		}

		openBtn.addEventListener('click', open);
		closeBtn.addEventListener('click', close);
		modal.addEventListener('click', function (e) {
			if (e.target === modal) {
				close();
			}
		});

		// Escape را کنترلر مشترک می‌گیرد؛ این فقط برای حالتی است که به هر
		// دلیل لود نشده باشد
		if (!window.AlmasaraModal) {
			document.addEventListener('keydown', function (e) {
				if (e.key === 'Escape' && modal.classList.contains('is-open')) {
					close();
				}
			});
		}

		if (!form) {
			return;
		}

		// افزودن/حذف نکات مثبت و منفی
		form.querySelectorAll('.amw-rv-form__points').forEach(function (box) {
			var input = box.querySelector('input');
			var list = box.querySelector('.amw-rv-form__pointlist');

			function addPoint() {
				var value = input.value.trim();
				if (!value) {
					return;
				}
				var li = document.createElement('li');
				var text = document.createElement('span');
				text.textContent = value;
				var remove = document.createElement('button');
				remove.type = 'button';
				remove.className = 'amw-rv-form__remove';
				remove.textContent = 'حذف';
				remove.addEventListener('click', function () {
					li.remove();
				});
				li.appendChild(text);
				li.appendChild(remove);
				list.appendChild(li);
				input.value = '';
				input.focus();
			}

			box.querySelector('.amw-rv-form__add').addEventListener('click', addPoint);
			input.addEventListener('keydown', function (e) {
				if (e.key === 'Enter') {
					e.preventDefault();
					addPoint();
				}
			});
		});

		function collectPoints(kind) {
			var box = form.querySelector('.amw-rv-form__points[data-kind="' + kind + '"]');
			if (!box) {
				return [];
			}
			return Array.prototype.map.call(box.querySelectorAll('.amw-rv-form__pointlist li span'), function (el) {
				return el.textContent;
			});
		}

		form.addEventListener('submit', function (e) {
			e.preventDefault();

			var message = form.querySelector('.amw-rv-form__message');
			var submit = form.querySelector('.amw-rv-form__submit');
			var rating = form.querySelector('input[name="amw_rating"]:checked');
			var comment = form.querySelector('textarea[name="amw_comment"]').value.trim();
			var recommend = form.querySelector('input[name="amw_recommend"]:checked');
			var anonymous = form.querySelector('input[name="amw_anonymous"]');
			var author = form.querySelector('input[name="amw_author"]');
			var email = form.querySelector('input[name="amw_email"]');
			var trap = form.querySelector('input[name="amw_website"]');

			message.textContent = '';
			message.classList.remove('is-error', 'is-success');

			if (!comment) {
				message.textContent = 'متن دیدگاه را بنویسید.';
				message.classList.add('is-error');
				return;
			}

			submit.disabled = true;

			fetch(root.dataset.endpoint, {
				method: 'POST',
				credentials: 'same-origin',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': root.dataset.nonce
				},
				body: JSON.stringify({
					product_id: parseInt(modal.dataset.product, 10),
					rating: rating ? parseInt(rating.value, 10) : 5,
					comment: comment,
					pros: collectPoints('pros'),
					cons: collectPoints('cons'),
					recommend: recommend ? recommend.value : '',
					anonymous: !!(anonymous && anonymous.checked),
					author: author ? author.value : '',
					email: email ? email.value : '',
					// تلهٔ ربات — کاربر واقعی همیشه خالی می‌فرستدش
					website: trap ? trap.value : ''
				})
			})
				.then(function (res) {
					return res.json().then(function (data) {
						return { ok: res.ok, data: data };
					});
				})
				.then(function (result) {
					submit.disabled = false;
					if (result.ok && result.data && result.data.success) {
						message.textContent = result.data.message;
						message.classList.add('is-success');
						form.reset();
						form.querySelectorAll('.amw-rv-form__pointlist').forEach(function (list) {
							list.innerHTML = '';
						});
						setTimeout(close, 2500);
						if (result.data.approved) {
							setTimeout(function () {
								window.location.reload();
							}, 2600);
						}
					} else {
						message.textContent = (result.data && result.data.message) || 'خطایی رخ داد؛ دوباره تلاش کنید.';
						message.classList.add('is-error');
					}
				})
				.catch(function () {
					submit.disabled = false;
					message.textContent = 'ارتباط با سرور برقرار نشد؛ دوباره تلاش کنید.';
					message.classList.add('is-error');
				});
		});
	}

	function initAll(scope) {
		(scope || document).querySelectorAll('.amw-rv').forEach(setup);
	}

	if (window.elementorFrontend && window.elementorFrontend.hooks) {
		window.elementorFrontend.hooks.addAction('frontend/element_ready/almasara-product-reviews.default', function ($el) {
			initAll($el && $el[0] ? $el[0] : document);
		});
	}

	if (document.readyState !== 'loading') {
		initAll(document);
	} else {
		document.addEventListener('DOMContentLoaded', function () {
			initAll(document);
		});
	}
})();
