<?php
/**
 * Public site settings (contact, social, donations, partners).
 * Copy to config/site.php and fill in. config/site.php is gitignored.
 *
 * Content not managed here (use admin or DB):
 * - Player photos and jersey numbers: admin/roster.php
 * - Match names, venues, rival typos: admin/juegos.php (or SQL on juegos / sedes)
 *
 * @return array<string, mixed>
 */
declare(strict_types=1);

return [
    'public_email' => '',
    'phone' => '',
    'instagram_url' => '',
    'facebook_url' => '',
    'youtube_url' => '',
    'x_url' => '', // formerly Twitter

    /** PayPal donation or pool link (https://...) */
    'donation_paypal_url' => '',
    /** Venmo profile or link */
    'donation_venmo_url' => '',
    /** Ko-fi or similar */
    'donation_kofi_url' => '',
    /** Optional Zelle email — shows copy-to-clipboard card on Support page */
    'donation_zelle_email' => '',
    /** Plain text for Zelle / bank transfer (shown as paragraph) */
    'donation_other_note' => '',

    /**
     * Footer "Partners" logos. Empty array = section hidden.
     * Each item: src (path under site root or absolute URL), alt, optional href.
     * @var list<array{src: string, alt: string, href?: string}>
     */
    'partner_logos' => [],
];
