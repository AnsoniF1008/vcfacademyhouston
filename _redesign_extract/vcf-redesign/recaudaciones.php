<?php
/**
 * VCF Academy Houston — recaudaciones.php (Support the site)
 */
$page_active = 'support';
$page_title  = 'Support the Site';
$base_url    = '/';
include 'includes/header.php';
?>

<div class="vcf-page-header">
  <div class="vcf-page-header__inner">
    <div class="vcf-page-header__label">VCF Academy Houston</div>
    <h1 class="vcf-page-header__title">Support <em>the Site</em></h1>
  </div>
</div>

<main style="max-width:760px;margin:0 auto;padding:44px var(--page-pad);">

  <p style="font-size:14px;color:var(--vcf-gray);line-height:1.8;margin-bottom:32px;max-width:560px;">
    This site is built and maintained voluntarily for players and families.
    Your contribution helps cover hosting, domain, and ongoing development.
    100% goes back into keeping this platform running.
  </p>

  <!-- What your support covers -->
  <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:2px;margin-bottom:36px;">
    <div style="background:var(--vcf-dark2);padding:20px;text-align:center;border-top:3px solid var(--vcf-orange);">
      <div style="font-size:28px;margin-bottom:8px;">&#127760;</div>
      <div style="font-family:var(--font-display);font-size:14px;font-weight:800;text-transform:uppercase;letter-spacing:0.06em;color:var(--vcf-white);">Hosting</div>
      <div style="font-size:12px;color:var(--vcf-gray);margin-top:4px;">Keep the server online 24/7</div>
    </div>
    <div style="background:var(--vcf-dark2);padding:20px;text-align:center;border-top:3px solid var(--vcf-orange);">
      <div style="font-size:28px;margin-bottom:8px;">&#128279;</div>
      <div style="font-family:var(--font-display);font-size:14px;font-weight:800;text-transform:uppercase;letter-spacing:0.06em;color:var(--vcf-white);">Domain</div>
      <div style="font-size:12px;color:var(--vcf-gray);margin-top:4px;">vcfacademyhouston.com</div>
    </div>
    <div style="background:var(--vcf-dark2);padding:20px;text-align:center;border-top:3px solid var(--vcf-orange);">
      <div style="font-size:28px;margin-bottom:8px;">&#9881;</div>
      <div style="font-family:var(--font-display);font-size:14px;font-weight:800;text-transform:uppercase;letter-spacing:0.06em;color:var(--vcf-white);">Updates</div>
      <div style="font-size:12px;color:var(--vcf-gray);margin-top:4px;">New features for families</div>
    </div>
  </div>

  <!-- Payment options -->
  <h2 style="font-family:var(--font-display);font-size:22px;font-weight:900;text-transform:uppercase;letter-spacing:0.04em;color:var(--vcf-white);margin-bottom:18px;">
    How to <em style="font-style:normal;color:var(--vcf-orange)">Contribute</em>
  </h2>

  <div style="display:flex;flex-direction:column;gap:4px;margin-bottom:32px;">
    <div style="background:var(--vcf-dark2);padding:20px 24px;display:flex;align-items:center;gap:20px;border-left:3px solid var(--vcf-orange);">
      <div style="font-family:var(--font-display);font-size:22px;font-weight:900;color:var(--vcf-orange);min-width:70px;">Zelle</div>
      <div>
        <div style="font-size:13px;color:var(--vcf-gray);margin-bottom:2px;">Send to</div>
        <div style="font-family:var(--font-display);font-size:16px;font-weight:800;color:var(--vcf-white);">ansoni1008@gmail.com</div>
        <div style="font-size:12px;color:var(--vcf-gray);margin-top:2px;">Any amount is appreciated</div>
      </div>
    </div>
    <div style="background:var(--vcf-dark2);padding:20px 24px;display:flex;align-items:center;gap:20px;border-left:3px solid var(--vcf-border2);">
      <div style="font-family:var(--font-display);font-size:18px;font-weight:900;color:var(--vcf-gray);min-width:70px;">Other</div>
      <div>
        <div style="font-size:13px;color:var(--vcf-gray);line-height:1.6;">
          Questions or other payment methods:<br>
          <a href="tel:3466298267" style="color:var(--vcf-lightgray);transition:color var(--transition);">(346) 629-8267</a>
        </div>
      </div>
    </div>
  </div>

  <a href="<?= $base_url ?>index.php" class="vcf-btn vcf-btn--outline">&larr; Back to Home</a>

</main>

<?php include 'includes/footer.php'; ?>
