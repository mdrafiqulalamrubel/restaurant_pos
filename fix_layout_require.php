<?php
$dir = 'f:/xampp82/htdocs/restaurant_pos/';
$files = glob($dir . 'acc_*.php');

foreach ($files as $file) {
    if (basename($file) === 'acc_core.php') continue;
    $content = file_get_contents($file);
    
    // Replace all variations of layout.php include/require with footer.php
    $content = preg_replace("/\\\$content\s*=\s*ob_get_clean\(\);\s*(require|include)(_once)?\s*__DIR__\s*\.\s*'\/(\.\.\/)*templates\/layout\.php';/", "require_once 'footer.php';", $content);
    
    // Just in case ob_start() was left somewhere, we can safely remove it
    $content = str_replace("ob_start();", "", $content);
    
    file_put_contents($file, $content);
    echo "Fixed layout require in " . basename($file) . "\n";
}
