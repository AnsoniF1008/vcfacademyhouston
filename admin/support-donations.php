<?php
declare(strict_types=1);

require __DIR__ . '/includes/auth.php';
require_permission('support_donations');
require __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/breadcrumb.php';

$configPath = __DIR__ . '/../config/site.php';
$examplePath = __DIR__ . '/../config/site.example.php';

$example = require $examplePath;
$local = [];
if (file_exists($configPath)) {
    $loaded = require $configPath;
    if (is_array($loaded)) {
        $local = $loaded;
    }
}
$merged = array_merge(is_array($example) ? $example : [], $local);
$rawContributions = trim((string) ($merged['donation_parent_contributions'] ?? ''));
$message = '';
$error = '';
$rows = [];

$parseRawContributions = static function (string $raw): array {
    $out = [];
    $lines = preg_split('/\r\n|\r|\n/', $raw) ?: [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        $parts = array_map('trim', explode('|', $line));
        if (count($parts) >= 3) {
            if ($parts[0] === '' || $parts[1] === '' || $parts[2] === '') {
                continue;
            }
            $out[] = ['date' => $parts[0], 'name' => $parts[1], 'amount' => $parts[2]];
            continue;
        }
        if (count($parts) === 2 && $parts[0] !== '' && $parts[1] !== '') {
            $out[] = ['date' => '', 'name' => $parts[0], 'amount' => $parts[1]];
        }
    }
    return $out;
};

$rows = $parseRawContributions($rawContributions);
if ($rows === []) {
    $rows[] = ['date' => '', 'name' => '', 'amount' => ''];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $error = 'Invalid request. Please refresh and try again.';
    } else {
        $normalized = [];
        $postedDates = $_POST['donation_date'] ?? [];
        $postedNames = $_POST['donation_name'] ?? [];
        $postedAmounts = $_POST['donation_amount'] ?? [];

        if (is_array($postedDates) && is_array($postedNames) && is_array($postedAmounts)) {
            $total = max(count($postedDates), count($postedNames), count($postedAmounts));
            for ($i = 0; $i < $total; $i++) {
                $date = trim((string) ($postedDates[$i] ?? ''));
                $name = trim((string) ($postedNames[$i] ?? ''));
                $amount = trim((string) ($postedAmounts[$i] ?? ''));
                if ($name === '' && $amount === '' && $date === '') {
                    continue;
                }
                if ($name === '' || $amount === '') {
                    continue;
                }
                if ($date !== '') {
                    $normalized[] = $date . '|' . $name . '|' . $amount;
                } else {
                    $normalized[] = $name . '|' . $amount;
                }
            }
        } else {
            $postedRaw = trim((string) ($_POST['donation_parent_contributions'] ?? ''));
            $rowsFallback = $parseRawContributions($postedRaw);
            foreach ($rowsFallback as $row) {
                if ($row['date'] !== '') {
                    $normalized[] = $row['date'] . '|' . $row['name'] . '|' . $row['amount'];
                } else {
                    $normalized[] = $row['name'] . '|' . $row['amount'];
                }
            }
        }

        $finalValue = implode("\n", $normalized);
        $local['donation_parent_contributions'] = $finalValue;

        $php = "<?php\n";
        $php .= "declare(strict_types=1);\n\n";
        $php .= 'return ' . var_export($local, true) . ";\n";

        $written = @file_put_contents($configPath, $php, LOCK_EX);
        if ($written === false) {
            $error = 'Could not save file config/site.php. Check file permissions.';
        } else {
            $rawContributions = $finalValue;
            $rows = $parseRawContributions($rawContributions);
            if ($rows === []) {
                $rows[] = ['date' => '', 'name' => '', 'amount' => ''];
            }
            $message = 'Donations updated successfully.';
            admin_log('support.donations.update', 'Updated parent contributions list');
        }
    }
}

$page_title = 'Support Donations - Admin';
require __DIR__ . '/../includes/header.php';
?>
<div class="container py-5">
    <?= admin_breadcrumb([
        ['label' => 'Dashboard', 'href' => 'dashboard.php'],
        ['label' => 'Support Donations'],
    ]) ?>
    <h1 class="mb-3 admin-page-title">Support Donations</h1>
    <p class="text-muted mb-4">Manage the list shown on the public <strong>Support the Site</strong> page.</p>

    <?php if ($message !== ''): ?>
    <div class="alert alert-success py-2"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
    <div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="card bg-dark border border-secondary border-2 rounded-3">
        <div class="card-body">
            <form method="post" action="">
                <?= csrf_field() ?>
                <label class="form-label text-uppercase small text-warning fw-bold">Parent contributions</label>
                <div class="table-responsive">
                    <table class="table table-dark table-bordered align-middle mb-2">
                        <thead>
                            <tr>
                                <th style="min-width: 140px;">Date</th>
                                <th style="min-width: 280px;">Parent / Family</th>
                                <th style="min-width: 120px;">Amount</th>
                                <th style="width: 90px;"></th>
                            </tr>
                        </thead>
                        <tbody id="donationsRows">
                            <?php foreach ($rows as $row): ?>
                            <tr>
                                <td><input type="text" class="form-control form-control-sm bg-black text-white border-secondary" name="donation_date[]" value="<?= htmlspecialchars($row['date']) ?>" placeholder="04/03/2026"></td>
                                <td><input type="text" class="form-control form-control-sm bg-black text-white border-secondary" name="donation_name[]" value="<?= htmlspecialchars($row['name']) ?>" placeholder="Parent name"></td>
                                <td><input type="text" class="form-control form-control-sm bg-black text-white border-secondary" name="donation_amount[]" value="<?= htmlspecialchars($row['amount']) ?>" placeholder="$20.00"></td>
                                <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger js-remove-row">Delete</button></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <textarea
                    class="form-control bg-black text-white border-secondary d-none"
                    id="donation_parent_contributions"
                    name="donation_parent_contributions"
                    rows="2"
                    spellcheck="false"><?= htmlspecialchars($rawContributions) ?></textarea>
                <div class="form-text text-muted mt-2">
                    Tip: you can leave Date empty. Name and Amount are required for each saved row.
                </div>
                <div class="mt-3 d-flex flex-wrap gap-2">
                    <button type="button" id="addDonationRow" class="btn btn-outline-light">+ Add row</button>
                    <button type="submit" class="btn btn-warning">Save donations</button>
                    <a href="dashboard.php" class="btn btn-outline-secondary">Back to dashboard</a>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
(function () {
  var tbody = document.getElementById('donationsRows');
  var addBtn = document.getElementById('addDonationRow');
  if (!tbody || !addBtn) return;

  function bindDelete(btn) {
    btn.addEventListener('click', function () {
      var rows = tbody.querySelectorAll('tr');
      if (rows.length <= 1) {
        rows[0].querySelectorAll('input').forEach(function (input) { input.value = ''; });
        return;
      }
      btn.closest('tr').remove();
    });
  }

  tbody.querySelectorAll('.js-remove-row').forEach(bindDelete);

  addBtn.addEventListener('click', function () {
    var tr = document.createElement('tr');
    tr.innerHTML =
      '<td><input type="text" class="form-control form-control-sm bg-black text-white border-secondary" name="donation_date[]" placeholder="04/03/2026"></td>' +
      '<td><input type="text" class="form-control form-control-sm bg-black text-white border-secondary" name="donation_name[]" placeholder="Parent name"></td>' +
      '<td><input type="text" class="form-control form-control-sm bg-black text-white border-secondary" name="donation_amount[]" placeholder="$20.00"></td>' +
      '<td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger js-remove-row">Delete</button></td>';
    tbody.appendChild(tr);
    var del = tr.querySelector('.js-remove-row');
    if (del) bindDelete(del);
  });
})();
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
