<?php
$file = 'F:\xampp82\htdocs\restaurant_pos\purchases.php';
$content = file_get_contents($file);

// Find the position of footer.php
$pos = strpos($content, "<?php require_once 'footer.php'; ?>");
if ($pos !== false) {
    // Keep everything up to the footer line
    $content = substr($content, 0, $pos);
    
    // Append the clean JS block and footer
    $content .= "<?php require_once 'footer.php'; ?>\n";
    $content .= "<?php if (isset(\$_GET['print_id'])): ?>\n";
    $content .= "<script>window.open('purchase_invoice.php?id=<?= (int)\$_GET['print_id'] ?>&print=1', '_blank', 'width=1000,height=800');</script>\n";
    $content .= "<?php endif; ?>\n";
    
    file_put_contents($file, $content);
    echo "Fixed purchases.php";
}
