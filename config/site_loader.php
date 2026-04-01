<?php
/**
 * Loads config/site.example.php merged with optional config/site.php.
 */
declare(strict_types=1);

$vcf_site = require __DIR__ . '/site.example.php';
if (!is_array($vcf_site)) {
    $vcf_site = [];
}
if (file_exists(__DIR__ . '/site.php')) {
    $local = require __DIR__ . '/site.php';
    if (is_array($local)) {
        $vcf_site = array_merge($vcf_site, $local);
    }
}
