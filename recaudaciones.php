<?php
require __DIR__ . '/config/database.php';

$page_active = 'support';
$page_title = 'Support the site - VCF Academy Houston';
$page_description = 'Help keep the VCF Academy Houston website online through voluntary contributions for hosting, domain, and maintenance.';
require __DIR__ . '/includes/header.php';

$paypal = trim((string) ($vcf_site['donation_paypal_url'] ?? ''));
$venmo = trim((string) ($vcf_site['donation_venmo_url'] ?? ''));
$kofi = trim((string) ($vcf_site['donation_kofi_url'] ?? ''));
$otherNote = trim((string) ($vcf_site['donation_other_note'] ?? ''));
$zelleEmail = trim((string) ($vcf_site['donation_zelle_email'] ?? ''));
$hostingDueRaw = trim((string) ($vcf_site['donation_hosting_due_date'] ?? ''));
$hostingPaidAmount = trim((string) ($vcf_site['donation_hosting_paid_amount'] ?? ''));
$domainDueRaw = trim((string) ($vcf_site['donation_domain_due_date'] ?? ''));
$domainPaidAmount = trim((string) ($vcf_site['donation_domain_paid_amount'] ?? ''));
$phone = trim((string) ($vcf_site['phone'] ?? ''));
$telHref = $phone !== '' ? preg_replace('/[^\d+]/', '', $phone) : '';
$formatDate = static function (string $raw): string {
    if ($raw === '') {
        return '';
    }
    try {
        return (new DateTimeImmutable($raw))->format('F j, Y');
    } catch (Exception $e) {
        return $raw;
    }
};
$hostingDueDisplay = $formatDate($hostingDueRaw);
$domainDueDisplay = $formatDate($domainDueRaw);
$showHostingMeta = $hostingDueDisplay !== '' || $hostingPaidAmount !== '';
$showDomainMeta = $domainDueDisplay !== '' || $domainPaidAmount !== '';

$hasAnyLink = $paypal !== '' || $venmo !== '' || $kofi !== '';
$hasMethods = $hasAnyLink || $zelleEmail !== '' || $phone !== '' || $otherNote !== '';

$suggestedAmounts = [
    ['amt' => '$5', 'label' => 'Monthly hosting help'],
    ['amt' => '$10', 'label' => 'Domain contribution'],
    ['amt' => '$25', 'label' => 'Feature supporter'],
    ['amt' => 'Any', 'label' => 'Every bit counts'],
];
?>

<section class="vcf-support vcf-section--dark vcf-page-sub vcf-redesign-legacy">
    <div class="vcf-support__hero">
        <div class="vcf-support__inner">
            <div class="vcf-support__eyebrow">VCF Academy Houston</div>
            <h1 class="vcf-support__title">Support the <em>Site</em></h1>
            <p class="vcf-support__hero-desc">This site is built and maintained voluntarily for players and families. Every contribution — big or small — goes directly into keeping this platform running and improving.</p>
        </div>
    </div>

    <div class="vcf-support__strip">
        <div class="vcf-support__strip-inner">
            <div class="vcf-support__section-label">What your support covers</div>
            <div class="vcf-support__covers-grid">
                <div class="vcf-support__cover-card">
                    <div class="vcf-support__cover-icon" aria-hidden="true">&#127760;</div>
                    <div class="vcf-support__cover-title">Hosting</div>
                    <p class="vcf-support__cover-desc">Server costs to keep the site online 24/7, fast and secure for all families.</p>
                    <?php if ($showHostingMeta): ?>
                    <?php if ($hostingDueDisplay !== ''): ?>
                    <p class="vcf-support__cover-desc"><strong>Next renewal:</strong> <?= htmlspecialchars($hostingDueDisplay) ?></p>
                    <?php endif; ?>
                    <?php if ($hostingPaidAmount !== ''): ?>
                    <p class="vcf-support__cover-desc"><strong>Amount paid:</strong> <?= htmlspecialchars($hostingPaidAmount) ?></p>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
                <div class="vcf-support__cover-card">
                    <div class="vcf-support__cover-icon" aria-hidden="true">&#128279;</div>
                    <div class="vcf-support__cover-title">Domain</div>
                    <p class="vcf-support__cover-desc">Annual renewal of our official web address so families always find us.</p>
                    <?php if ($showDomainMeta): ?>
                    <?php if ($domainDueDisplay !== ''): ?>
                    <p class="vcf-support__cover-desc"><strong>Next renewal:</strong> <?= htmlspecialchars($domainDueDisplay) ?></p>
                    <?php endif; ?>
                    <?php if ($domainPaidAmount !== ''): ?>
                    <p class="vcf-support__cover-desc"><strong>Amount paid:</strong> <?= htmlspecialchars($domainPaidAmount) ?></p>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
                <div class="vcf-support__cover-card">
                    <div class="vcf-support__cover-icon" aria-hidden="true">&#9881;</div>
                    <div class="vcf-support__cover-title">Updates</div>
                    <p class="vcf-support__cover-desc">New features like galleries, stats, schedules, and a better experience for everyone.</p>
                </div>
                <div class="vcf-support__cover-card vcf-support__cover-card--soft">
                    <div class="vcf-support__cover-icon" aria-hidden="true">&#9917;</div>
                    <div class="vcf-support__cover-title">For the team</div>
                    <p class="vcf-support__cover-desc">Every dollar reinvested in better tools and experiences for players and families.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="vcf-support__strip">
        <div class="vcf-support__strip-inner">
            <div class="vcf-support__section-label">How to contribute</div>

            <?php if ($hasAnyLink): ?>
            <div class="vcf-support__btn-row">
                <?php if ($paypal !== ''): ?>
                <a href="<?= htmlspecialchars($paypal) ?>" class="vcf-btn-cta" target="_blank" rel="noopener noreferrer">PayPal</a>
                <?php endif; ?>
                <?php if ($venmo !== ''): ?>
                <a href="<?= htmlspecialchars($venmo) ?>" class="vcf-btn-pill vcf-btn-pill--outline" target="_blank" rel="noopener noreferrer">Venmo</a>
                <?php endif; ?>
                <?php if ($kofi !== ''): ?>
                <a href="<?= htmlspecialchars($kofi) ?>" class="vcf-btn-pill vcf-btn-pill--outline" target="_blank" rel="noopener noreferrer">Ko-fi</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php if ($zelleEmail !== '' || $phone !== ''): ?>
            <div class="vcf-support__method-grid">
                <?php if ($zelleEmail !== ''): ?>
                <div class="vcf-support__method-card">
                    <div class="vcf-support__method-head">
                        <div class="vcf-support__method-icon" aria-hidden="true">Z</div>
                        <div>
                            <div class="vcf-support__method-name">Zelle</div>
                            <div class="vcf-support__method-tag">Instant &middot; No fees</div>
                        </div>
                    </div>
                    <p class="vcf-support__method-hint">Send to this email in your Zelle app:</p>
                    <div class="vcf-support__email-row">
                        <span class="vcf-support__email-text"><?= htmlspecialchars($zelleEmail) ?></span>
                        <button type="button" class="vcf-support__copy-btn" data-vcf-copy="<?= htmlspecialchars($zelleEmail, ENT_QUOTES) ?>">Copy</button>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($phone !== ''): ?>
                <div class="vcf-support__method-card vcf-support__method-card--muted">
                    <div class="vcf-support__method-head">
                        <div class="vcf-support__method-icon vcf-support__method-icon--dark" aria-hidden="true">&#128222;</div>
                        <div>
                            <div class="vcf-support__method-name">Other</div>
                            <div class="vcf-support__method-tag vcf-support__method-tag--gray">Questions welcome</div>
                        </div>
                    </div>
                    <p class="vcf-support__method-hint">Prefer another method or have questions?</p>
                    <a href="tel:<?= htmlspecialchars($telHref) ?>" class="vcf-support__phone-link">
                        <span class="vcf-support__phone-num"><?= htmlspecialchars($phone) ?></span>
                        <span class="vcf-support__phone-label">Call / WhatsApp</span>
                    </a>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php if ($otherNote !== ''): ?>
            <div class="vcf-support__other-card mt-4">
                <h2 class="vcf-support__other-title">Other options</h2>
                <div class="vcf-support__other-text"><?= nl2br(htmlspecialchars($otherNote)) ?></div>
            </div>
            <?php endif; ?>

            <?php if (!$hasMethods): ?>
            <div class="vcf-support__empty">
                <p class="mb-3">Contribution links will be listed here soon.</p>
                <p class="mb-0">You can still reach us through <a href="<?= htmlspecialchars($base ?? '') ?>/contact.php" class="footer-link">Contact</a>.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="vcf-support__strip">
        <div class="vcf-support__strip-inner">
            <div class="vcf-support__section-label">Suggested amounts</div>
            <div class="vcf-support__amounts-grid">
                <?php foreach ($suggestedAmounts as $a): ?>
                <div class="vcf-support__amount-card">
                    <div class="vcf-support__amount-val"><?= htmlspecialchars($a['amt']) ?></div>
                    <div class="vcf-support__amount-lbl"><?= htmlspecialchars($a['label']) ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            <p class="vcf-support__amount-note">No minimum, no obligation. 100% voluntary. Thank you for supporting the players and families of VCF Academy Houston.</p>
        </div>
    </div>

    <div class="vcf-support__closing">
        <div>
            <div class="vcf-support__thank-title">Thank you &#9825;</div>
            <div class="vcf-support__thank-sub">Your support keeps this going for every player and family.</div>
        </div>
        <a href="<?= htmlspecialchars($base ?? '') ?>/index.php" class="vcf-support__back">&larr; Back to Home</a>
    </div>
</section>

<script>
(function () {
  document.querySelectorAll('[data-vcf-copy]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var t = btn.getAttribute('data-vcf-copy') || '';
      function done() {
        var prev = btn.textContent;
        btn.textContent = 'Copied!';
        setTimeout(function () { btn.textContent = prev; }, 1800);
      }
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(t).then(done).catch(function () { fallback(); });
      } else {
        fallback();
      }
      function fallback() {
        var ta = document.createElement('textarea');
        ta.value = t;
        ta.style.position = 'fixed';
        ta.style.left = '-9999px';
        document.body.appendChild(ta);
        ta.select();
        try { document.execCommand('copy'); done(); } catch (e) {}
        document.body.removeChild(ta);
      }
    });
  });
})();
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
