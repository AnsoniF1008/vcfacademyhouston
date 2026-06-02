# CSP nonce migration plan

Goal: remove `'unsafe-inline'` from the Content-Security-Policy in `.htaccess`
so injected inline scripts/styles can no longer execute (the main residual XSS
risk). This is a **dedicated, separately-tested effort**, not a header tweak,
because of a hard CSP rule:

> As soon as a `nonce` (or hash) appears in `script-src`, CSP3 browsers
> **ignore `'unsafe-inline'`**. There is no "both" — the moment you add the
> nonce, every inline script must carry it and every inline event handler
> (`onclick`/`onload`/`onerror`/`onsubmit`) stops working, because nonces do
> **not** authorise `on*=` attributes. Inline `style=""` attributes are likewise
> never covered by nonces (only `<style>` elements are).

## Current inline surface (audit)

| Kind | Count | Notes |
|------|------:|-------|
| Inline `<script>` blocks | ~13 | Get a `nonce` attribute. |
| Inline event handlers `on*=` | ~31 | `onsubmit` ×18 (admin confirms), `onload` ×7 (font/FA async + media swap), `onerror` ×5 (image fallback), `onclick` ×1. Must be refactored away. |
| Inline `style=""` attributes | ~194 across 39 files | Blocks dropping `unsafe-inline` from `style-src`. |

## Done already (safe, no refactor)

- `object-src 'none'` and `upgrade-insecure-requests` added to the CSP.
- CSP stays in `.htaccess` (global coverage incl. static files & error pages).

## Stage 1 — scripts-only nonce (high value, medium effort)

Removes `unsafe-inline` from `script-src` while leaving it on `style-src`
(style injection is far lower risk). Steps:

1. Generate a per-request nonce in PHP (`bin2hex(random_bytes(16))`), exposed
   via a helper e.g. `vcf_csp_nonce()`. Move the CSP header emission to PHP so
   it can interpolate the nonce, OR keep `.htaccess` for everything except
   `script-src` and set `script-src` from PHP. (Per-request header ⇒ PHP.)
2. Add `nonce="<?= vcf_csp_nonce() ?>"` to all ~13 inline `<script>` blocks
   (header.php, footer.php, admin/index.php, the JSON-LD block, etc.).
3. Eliminate the ~31 inline event handlers — recommended low-risk patterns:
   - **`onsubmit="return confirm('…')"` (×18):** replace with
     `data-confirm="…"` and one delegated nonce'd listener:
     `document.addEventListener('submit', e => { const m = e.target.getAttribute('data-confirm'); if (m && !confirm(m)) e.preventDefault(); });`
   - **`onerror="this.style.display='none'"` on images (×5):** add a class and a
     captured listener: `document.addEventListener('error', e => { if (e.target.matches('img.vcf-hide-on-error')) e.target.style.display='none'; }, true);`
   - **`onload` CSS async swap (×7):** keep the `<link rel="preload">` hint and
     move the `media`/`rel` flip into a small nonce'd inline script at end of
     `<head>` that targets the links by a class.
4. Switch `script-src` to `'self' 'nonce-…' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com`
   (consider `'strict-dynamic'` so the CDN allowlist can eventually drop).
5. Test every page (public + all admin CRUD) with DevTools console open for CSP
   violations before shipping.

## Stage 2 — strict styles (large effort, do last)

Remove `unsafe-inline` from `style-src` by migrating all ~194 `style=""`
attributes to CSS classes (or nonce'd `<style>` blocks where dynamic). Highest
regression risk (visual); tackle file-by-file.

## Related finding — blog embeds vs frame-src

`news.php` sanitises blog HTML to allow `<iframe>` embeds from youtube / vimeo /
instagram / twitter / x.com. The current CSP has **no `frame-src`**, so frames
fall back to `default-src 'self'` and those embeds are blocked. If embeds are
intended to work, add e.g.:

```
frame-src 'self' https://www.youtube.com https://www.youtube-nocookie.com https://player.vimeo.com https://www.instagram.com https://twitter.com https://platform.twitter.com;
```

Keep this in sync with the sanitiser allowlist.
