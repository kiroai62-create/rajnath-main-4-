<?php

return [
    // Trust a CDN's visitor-country header (Cloudflare CF-IPCountry, CloudFront …). Leave off when
    // visitors reach the server directly: anyone could send the header. The local DB-IP list is used otherwise.
    'trust_cdn_country' => (bool) env('ANALYTICS_TRUST_CDN_COUNTRY', false),
];
