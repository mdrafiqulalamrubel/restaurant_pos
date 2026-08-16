<?php
$dir = 'f:/xampp82/htdocs/restaurant_pos/';

function fix_file($file, $search, $replace) {
    global $dir;
    $path = $dir . $file;
    if (file_exists($path)) {
        $content = file_get_contents($path);
        $content = str_replace($search, $replace, $content);
        file_put_contents($path, $content);
        echo "Fixed $file\n";
    }
}

// acc_balance_sheet.php
fix_file('acc_balance_sheet.php', [
    'execute([$tid, $date])'
], [
    'execute([$date])'
]);

// acc_pl.php
fix_file('acc_pl.php', [
    'execute([$tid, \'Revenue\', $start_date, $end_date])',
    'execute([$tid, $start_date, $end_date])'
], [
    'execute([\'Revenue\', $start_date, $end_date])',
    'execute([$start_date, $end_date])'
]);

// acc_trading.php
fix_file('acc_trading.php', [
    'execute([$tid, \'4000\', $start_date, $end_date])',
    'execute([$tid, $start_date, $end_date])'
], [
    'execute([\'4000\', $start_date, $end_date])',
    'execute([$start_date, $end_date])'
]);

// acc_trial_balance.php
fix_file('acc_trial_balance.php', [
    'execute([$tid])'
], [
    'execute([])'
]);

// Check if acc_core.php needs paginate()
$acc_core = $dir . 'acc_core.php';
$core_content = file_get_contents($acc_core);
if (strpos($core_content, 'function paginate') === false) {
    $paginate = <<<'EOD'

if (!function_exists('paginate')) {
    function paginate($total, $limit, $page, $url) {
        $pages = ceil($total / $limit);
        if ($pages <= 1) return '';
        $html = '<nav><ul class="pagination pagination-sm justify-content-center">';
        for ($i = 1; $i <= $pages; $i++) {
            $active = $i == $page ? 'active' : '';
            $u = strpos($url, '?') !== false ? "$url&page=$i" : "$url?page=$i";
            $html .= "<li class='page-item $active'><a class='page-link' href='$u'>$i</a></li>";
        }
        $html .= '</ul></nav>';
        return $html;
    }
}
EOD;
    file_put_contents($acc_core, str_replace('?>', $paginate . "\n?>", $core_content));
    echo "Added paginate() to acc_core.php\n";
}
