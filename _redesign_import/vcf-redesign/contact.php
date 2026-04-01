<?php
/**
 * VCF Academy Houston — contact.php
 */
$page_active = 'contact';
$page_title  = 'Contact Us';
$base_url    = '/';
include 'includes/header.php';

$sent = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // ── Mantén aquí tu lógica de envío de mail original ──
  $name    = htmlspecialchars(trim($_POST['name']    ?? ''));
  $email   = htmlspecialchars(trim($_POST['email']   ?? ''));
  $subject = htmlspecialchars(trim($_POST['subject'] ?? ''));
  $message = htmlspecialchars(trim($_POST['message'] ?? ''));
  if ($name && $email && $message) {
    // mail('ansoni1008@gmail.com', 'VCF Contact: '.$subject, $message, 'From: '.$email);
    $sent = true;
  } else {
    $error = 'Please fill in all required fields.';
  }
}
?>

<div class="vcf-page-header">
  <div class="vcf-page-header__inner">
    <div class="vcf-page-header__label">VCF Academy Houston</div>
    <h1 class="vcf-page-header__title">Contact <em>Us</em></h1>
  </div>
</div>

<main style="max-width:800px;margin:0 auto;padding:44px var(--page-pad);">

  <?php if ($sent): ?>
  <div style="background:rgba(74,222,128,0.08);border:1px solid rgba(74,222,128,0.25);border-left:3px solid var(--vcf-green);padding:20px 24px;margin-bottom:28px;">
    <div style="font-family:var(--font-display);font-size:16px;font-weight:800;text-transform:uppercase;letter-spacing:0.06em;color:var(--vcf-green);">Message Sent!</div>
    <div style="font-size:13px;color:var(--vcf-gray);margin-top:4px;">We'll get back to you as soon as possible.</div>
  </div>
  <?php elseif ($error): ?>
  <div style="background:rgba(248,113,113,0.08);border:1px solid rgba(248,113,113,0.25);border-left:3px solid var(--vcf-red);padding:20px 24px;margin-bottom:28px;">
    <div style="font-family:var(--font-display);font-size:14px;font-weight:700;color:var(--vcf-red);"><?= $error ?></div>
  </div>
  <?php endif; ?>

  <div style="display:grid;grid-template-columns:1fr 1fr;gap:32px;align-items:start;">

    <!-- Contact form -->
    <div>
      <h2 style="font-family:var(--font-display);font-size:22px;font-weight:900;text-transform:uppercase;letter-spacing:0.04em;color:var(--vcf-white);margin-bottom:20px;">
        Send a <span style="color:var(--vcf-orange)">Message</span>
      </h2>
      <form method="POST" action="" style="display:flex;flex-direction:column;gap:14px;">
        <div>
          <label style="font-family:var(--font-display);font-size:10px;font-weight:700;letter-spacing:0.15em;text-transform:uppercase;color:var(--vcf-gray);display:block;margin-bottom:6px;">Name *</label>
          <input type="text" name="name" required
            style="width:100%;background:var(--vcf-dark2);border:1px solid var(--vcf-border2);color:var(--vcf-white);padding:10px 14px;font-family:var(--font-body);font-size:14px;border-radius:var(--radius-sm);outline:none;transition:border-color var(--transition);"
            onfocus="this.style.borderColor='var(--vcf-orange)'" onblur="this.style.borderColor='var(--vcf-border2)'"
            placeholder="Your name">
        </div>
        <div>
          <label style="font-family:var(--font-display);font-size:10px;font-weight:700;letter-spacing:0.15em;text-transform:uppercase;color:var(--vcf-gray);display:block;margin-bottom:6px;">Email *</label>
          <input type="email" name="email" required
            style="width:100%;background:var(--vcf-dark2);border:1px solid var(--vcf-border2);color:var(--vcf-white);padding:10px 14px;font-family:var(--font-body);font-size:14px;border-radius:var(--radius-sm);outline:none;transition:border-color var(--transition);"
            onfocus="this.style.borderColor='var(--vcf-orange)'" onblur="this.style.borderColor='var(--vcf-border2)'"
            placeholder="your@email.com">
        </div>
        <div>
          <label style="font-family:var(--font-display);font-size:10px;font-weight:700;letter-spacing:0.15em;text-transform:uppercase;color:var(--vcf-gray);display:block;margin-bottom:6px;">Subject</label>
          <input type="text" name="subject"
            style="width:100%;background:var(--vcf-dark2);border:1px solid var(--vcf-border2);color:var(--vcf-white);padding:10px 14px;font-family:var(--font-body);font-size:14px;border-radius:var(--radius-sm);outline:none;transition:border-color var(--transition);"
            onfocus="this.style.borderColor='var(--vcf-orange)'" onblur="this.style.borderColor='var(--vcf-border2)'"
            placeholder="Program inquiry, tryouts, etc.">
        </div>
        <div>
          <label style="font-family:var(--font-display);font-size:10px;font-weight:700;letter-spacing:0.15em;text-transform:uppercase;color:var(--vcf-gray);display:block;margin-bottom:6px;">Message *</label>
          <textarea name="message" required rows="5"
            style="width:100%;background:var(--vcf-dark2);border:1px solid var(--vcf-border2);color:var(--vcf-white);padding:10px 14px;font-family:var(--font-body);font-size:14px;border-radius:var(--radius-sm);outline:none;resize:vertical;transition:border-color var(--transition);"
            onfocus="this.style.borderColor='var(--vcf-orange)'" onblur="this.style.borderColor='var(--vcf-border2)'"
            placeholder="How can we help you?"></textarea>
        </div>
        <button type="submit" class="vcf-btn vcf-btn--primary" style="align-self:flex-start;">
          Send Message &rarr;
        </button>
      </form>
    </div>

    <!-- Contact info -->
    <div>
      <h2 style="font-family:var(--font-display);font-size:22px;font-weight:900;text-transform:uppercase;letter-spacing:0.04em;color:var(--vcf-white);margin-bottom:20px;">
        Direct <span style="color:var(--vcf-orange)">Contact</span>
      </h2>
      <div style="display:flex;flex-direction:column;gap:4px;margin-bottom:28px;">
        <div style="background:var(--vcf-dark2);padding:18px 20px;border-left:3px solid var(--vcf-orange);">
          <div style="font-family:var(--font-display);font-size:10px;font-weight:700;letter-spacing:0.15em;text-transform:uppercase;color:var(--vcf-orange);margin-bottom:4px;">Email</div>
          <a href="mailto:ansoni1008@gmail.com" style="font-size:14px;color:var(--vcf-lightgray);transition:color var(--transition);" onmouseover="this.style.color='var(--vcf-white)'" onmouseout="this.style.color='var(--vcf-lightgray)'">ansoni1008@gmail.com</a>
        </div>
        <div style="background:var(--vcf-dark2);padding:18px 20px;border-left:3px solid var(--vcf-orange);">
          <div style="font-family:var(--font-display);font-size:10px;font-weight:700;letter-spacing:0.15em;text-transform:uppercase;color:var(--vcf-orange);margin-bottom:4px;">Phone / WhatsApp</div>
          <a href="tel:3466298267" style="font-size:14px;color:var(--vcf-lightgray);transition:color var(--transition);" onmouseover="this.style.color='var(--vcf-white)'" onmouseout="this.style.color='var(--vcf-lightgray)'">(346) 629-8267</a>
        </div>
        <div style="background:var(--vcf-dark2);padding:18px 20px;border-left:3px solid var(--vcf-border2);">
          <div style="font-family:var(--font-display);font-size:10px;font-weight:700;letter-spacing:0.15em;text-transform:uppercase;color:var(--vcf-gray);margin-bottom:4px;">Training Ground</div>
          <div style="font-size:13px;color:var(--vcf-gray);line-height:1.6;">2203 N Westgreen Blvd<br>Katy, TX 77449</div>
          <a href="https://maps.app.goo.gl/q27c1FCQw4cvspGX8" target="_blank" rel="noopener" class="vcf-ground__gps" style="margin-top:10px;display:inline-flex;">
            <svg width="12" height="12" viewBox="0 0 20 20" fill="currentColor"><path d="M10 2a6 6 0 0 0-6 6c0 4 6 10 6 10s6-6 6-10a6 6 0 0 0-6-6zm0 8a2 2 0 1 1 0-4 2 2 0 0 1 0 4z"/></svg>
            Open in GPS
          </a>
        </div>
      </div>
      <a href="<?= $base_url ?>index.php" class="vcf-btn vcf-btn--outline">&larr; Back to Home</a>
    </div>
  </div>
</main>

<?php include 'includes/footer.php'; ?>
