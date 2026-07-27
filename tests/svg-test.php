<?php
/**
 * پاک‌سازی SVG.
 *
 * هر مورد اینجا یک بردار واقعی است که نسخهٔ مبتنی بر Regex از آن عبور
 * می‌کرد، به‌علاوهٔ مواردی که در بازبینی امنیتی مطرح شد.
 */

require_once __DIR__ . '/bootstrap.php';
require_once dirname(__DIR__) . '/includes/svg.php';

use Almasara_Widgets\Svg;

Tests::group('SVG › اسکریپت و رویداد');

Tests::blocks(
    'رویداد بدون کوتیشن',
    Svg::sanitize('<svg xmlns="http://www.w3.org/2000/svg"><rect onload=alert(1) width="10"/></svg>'),
    ['onload', 'alert']
);

Tests::blocks(
    'رویداد با کوتیشن',
    Svg::sanitize('<svg xmlns="http://www.w3.org/2000/svg"><rect onload="alert(1)"/></svg>'),
    ['onload', 'alert']
);

Tests::blocks(
    'رویداد با کوتیشن تکی و فاصلهٔ اضافه',
    Svg::sanitize("<svg xmlns='http://www.w3.org/2000/svg'><rect  onmouseover = 'alert(1)' /></svg>"),
    ['onmouseover', 'alert']
);

Tests::blocks(
    'تگ script',
    Svg::sanitize('<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script><path d="M0 0"/></svg>'),
    ['<script', 'alert']
);

Tests::blocks(
    'animate با onbegin',
    Svg::sanitize('<svg xmlns="http://www.w3.org/2000/svg"><animate onbegin="alert(1)" attributeName="x"/></svg>'),
    ['onbegin', '<animate']
);

Tests::blocks(
    'set با رویداد',
    Svg::sanitize('<svg xmlns="http://www.w3.org/2000/svg"><set attributeName="x" onbegin="alert(1)"/></svg>'),
    ['onbegin', '<set']
);

Tests::blocks(
    'foreignObject با HTML داخلش',
    Svg::sanitize('<svg xmlns="http://www.w3.org/2000/svg"><foreignObject><img src=x onerror=alert(1)></foreignObject></svg>'),
    ['foreignObject', 'onerror']
);

Tests::group('SVG › URI و CSS');

Tests::blocks(
    'use با href از نوع data:',
    Svg::sanitize('<svg xmlns="http://www.w3.org/2000/svg"><use href="data:image/svg+xml;base64,PHN2Zz4="/></svg>'),
    ['data:']
);

Tests::blocks(
    'لینک javascript:',
    Svg::sanitize('<svg xmlns="http://www.w3.org/2000/svg"><a href="javascript:alert(1)"><rect/></a></svg>'),
    ['javascript', '<a ']
);

Tests::blocks(
    'javascript: با کاراکتر کنترلی پنهان',
    Svg::sanitize('<svg xmlns="http://www.w3.org/2000/svg"><use xlink:href="java&#9;script:alert(1)"/></svg>'),
    ['javascript', 'alert']
);

Tests::blocks(
    'style با @import',
    Svg::sanitize('<svg xmlns="http://www.w3.org/2000/svg"><rect style="@import url(//evil)"/></svg>'),
    ['@import', 'evil']
);

Tests::blocks(
    'style با expression',
    Svg::sanitize('<svg xmlns="http://www.w3.org/2000/svg"><rect style="width:expression(alert(1))"/></svg>'),
    ['expression', 'alert']
);

Tests::group('SVG › موجودیت‌ها');

// LIBXML_NOENT موجودیت را باز می‌کند، نه غیرفعال. سند دارای DOCTYPE اصلاً
// پارس نمی‌شود تا این مسیر بسته بماند.
Tests::same(
    'XXE با موجودیت خارجی کاملاً رد می‌شود',
    Svg::sanitize('<!DOCTYPE svg [<!ENTITY xxe SYSTEM "file:///etc/passwd">]><svg xmlns="http://www.w3.org/2000/svg"><text>&xxe;</text></svg>'),
    ''
);

Tests::same(
    'موجودیت داخلی هم باز نمی‌شود (billion laughs)',
    Svg::sanitize(
        '<!DOCTYPE svg [<!ENTITY a "aaaaaaaaaa"><!ENTITY b "&a;&a;&a;&a;&a;&a;&a;&a;&a;&a;">]>'
        . '<svg xmlns="http://www.w3.org/2000/svg"><text>&b;</text></svg>'
    ),
    ''
);

Tests::same(
    'DOCTYPE ساده هم پذیرفته نمی‌شود',
    Svg::sanitize('<!DOCTYPE svg><svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>'),
    ''
);

Tests::group('SVG › محتوای سالم');

$icon = Svg::sanitize(
    '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">'
    . '<path d="m15 6-6 6 6 6" fill="none" stroke="currentColor" stroke-width="2"/></svg>'
);

Tests::ok('مسیر آیکون حفظ می‌شود', false !== strpos($icon, 'm15 6-6 6 6 6'));
Tests::ok('stroke حفظ می‌شود', false !== strpos($icon, 'stroke="currentColor"'));
Tests::ok('viewBox حفظ می‌شود', false !== strpos($icon, 'viewBox'));
Tests::ok('fill="none" دست‌نخورده می‌ماند', false !== strpos($icon, 'fill="none"'));

$multi = Svg::sanitize(
    '<svg xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="g">'
    . '<stop offset="0" stop-color="#f00"/></linearGradient></defs>'
    . '<circle cx="5" cy="5" r="4" fill="url(#g)"/><use href="#g"/></svg>'
);

Tests::ok('گرادیان و stop حفظ می‌شوند', false !== strpos($multi, 'linearGradient'));
Tests::ok('use با ارجاع داخلی مجاز است', false !== strpos($multi, 'href="#g"'));

Tests::same('ورودی غیر SVG رد می‌شود', Svg::sanitize('<html><body>hi</body></html>'), '');
Tests::same('ورودی خالی رد می‌شود', Svg::sanitize(''), '');
