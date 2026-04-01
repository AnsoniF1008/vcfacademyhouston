# one-off: replace index body with home-vcf include
p = r"c:\xampp\htdocs\valencia\index.php"
with open(p, encoding="utf-8") as f:
    lines = f.readlines()
new = lines[:396] + ["<?php require __DIR__ . '/includes/home-vcf.php'; ?>\n", "\n"] + lines[1376:]
with open(p, "w", encoding="utf-8", newline="") as f:
    f.writelines(new)
print("ok", len(lines), "->", len(new))
