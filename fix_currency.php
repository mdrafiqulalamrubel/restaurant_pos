<?php
$dir = __DIR__;
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));

$patterns = [
    // Currency followed by number_format
    '/[\$€]\s*<\?=\s*number_format\(([^,]+?),\s*2\)\s*\?\>/' => '<?= money($1) ?>',
    
    // Currency variable followed by number_format
    '/<\?=\s*\$[a-zA-Z_]+(\[\'[^\']+\'\])?(\s*\?\?\s*[\'"][^\'"]+[\'"])?\s*\?\>\s*<\?=\s*number_format\(([^,]+?),\s*2\)\s*\?\>/' => '<?= money($3) ?>',
    
    // Currency variable concatenated with number_format
    '/<\?=\s*\$[a-zA-Z_]+(\[\'[^\']+\'\])?(\s*\?\?\s*[\'"][^\'"]+[\'"])?\s*\.\s*number_format\(([^,]+?),\s*2\)\s*\?\>/' => '<?= money($3) ?>',

    // Currency followed by echo number_format
    '/[\$€]\s*<\?php\s+echo\s+number_format\(([^,]+?),\s*2\);\s*\?\>/' => '<?= money($1) ?>',
];

$count = 0;
foreach ($files as $file) {
    if ($file->isFile() && $file->getExtension() === 'php' && $file->getFilename() !== 'config.php' && $file->getFilename() !== 'fix_currency.php' && $file->getFilename() !== 'acc_core.php') {
        $content = file_get_contents($file->getPathname());
        $original = $content;
        
        foreach ($patterns as $pattern => $replacement) {
            $content = preg_replace($pattern, $replacement, $content);
        }
        
        // Remove currency symbols before variables just echoed out
        $content = preg_replace_callback('/[\$€]\s*<\?=\s*([^\?]+?)\s*\?\>/', function($matches) {
            if (strpos($matches[1], 'money(') === false && strpos($matches[1], 'number_format(') === false) {
                return '<?= money(' . $matches[1] . ') ?>';
            }
            return $matches[0];
        }, $content);
        
        if ($content !== $original) {
            file_put_contents($file->getPathname(), $content);
            echo "Updated " . $file->getFilename() . "\n";
            $count++;
        }
    }
}
echo "Updated $count files.\n";
