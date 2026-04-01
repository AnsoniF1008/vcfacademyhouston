<?php
/**
 * Sitemap XML as PHP so servers that parse .xml as PHP do not choke on <?xml.
 */
header('Content-Type: application/xml; charset=UTF-8');
$base = 'https://vcfacademyhouston.com';
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ([
    ['/', 'weekly', '1.0'],
    ['/contact.php', 'monthly', '0.9'],
    ['/recaudaciones.php', 'monthly', '0.7'],
    ['/calendar.php', 'weekly', '0.85'],
    ['/privacy.php', 'yearly', '0.3'],
] as $row) {
    echo "  <url>\n    <loc>{$base}{$row[0]}</loc>\n    <changefreq>{$row[1]}</changefreq>\n    <priority>{$row[2]}</priority>\n  </url>\n";
}
echo '</urlset>';
