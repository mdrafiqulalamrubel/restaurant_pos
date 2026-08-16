<?php
$dir = 'f:/xampp82/htdocs/restaurant_pos/';
$files = glob($dir . 'acc_*.php');

foreach ($files as $file) {
    if (basename($file) === 'acc_core.php') continue;
    $content = file_get_contents($file);
    
    // Replace config.php with acc_core.php
    $content = str_replace("require_once 'config.php';", "require_once 'acc_core.php';", $content);
    
    // Inject display_flash() after header.php
    if (strpos($content, 'display_flash();') === false) {
        $content = str_replace("require_once 'header.php';", "require_once 'header.php';\ndisplay_flash();", $content);
    }
    
    file_put_contents($file, $content);
    echo "Fixed " . basename($file) . "\n";
}
