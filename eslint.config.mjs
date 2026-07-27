/**
 * پیکربندی ESLint.
 *
 * اسکریپت‌های این افزونه عمداً ES5 و بدون بیلد استپ‌اند تا مستقیم در
 * وردپرس صف شوند؛ پس قواعد حول همان تنظیم شده است.
 */
export default [
	{
		files: ['assets/js/**/*.js'],
		languageOptions: {
			ecmaVersion: 2018,
			sourceType: 'script',
			globals: {
				window: 'readonly',
				document: 'readonly',
				console: 'readonly',
				fetch: 'readonly',
				URLSearchParams: 'readonly',
				MutationObserver: 'readonly',
				IntersectionObserver: 'readonly',
				ResizeObserver: 'readonly',
				requestAnimationFrame: 'readonly',
				setTimeout: 'readonly',
				setInterval: 'readonly',
				clearInterval: 'readonly',
				clearTimeout: 'readonly',
				Image: 'readonly',
				Promise: 'readonly',
				history: 'readonly',
				location: 'readonly',
				elementorFrontend: 'readonly',
				Swiper: 'readonly'
			}
		},
		linterOptions: { reportUnusedDisableDirectives: true },
		rules: {
			'no-undef': 'error',
			// متغیر catch در سبک ES5 اغلب لازم است ولی استفاده نمی‌شود
			'no-unused-vars': ['error', { args: 'none', caughtErrors: 'none' }],
			'no-redeclare': 'error',
			'no-implicit-globals': 'error',
			eqeqeq: ['error', 'smart'],
			'no-eval': 'error',
			'no-implied-eval': 'error'
		}
	}
];
