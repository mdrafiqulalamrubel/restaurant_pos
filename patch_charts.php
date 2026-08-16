<?php
function patch_file($file, $is_index = false) {
    $content = file_get_contents($file);

    // 1. Add total_purchases_today to reports.php (already added to index.php)
    if (!$is_index) {
        $search1 = '$total_items = $pdo->query("SELECT COUNT(*) FROM items WHERE active=1")->fetchColumn();';
        $replace1 = '$total_purchases_today = $pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM purchases WHERE DATE(purchase_date) = CURDATE()")->fetchColumn();' . "\n" . $search1;
        if (strpos($content, '$total_purchases_today') === false) {
            $content = str_replace($search1, $replace1, $content);
        }
        
        $search2 = '<div class="stats-label">Menu Items</div>';
        if (strpos($content, 'Menu Items') !== false) {
            $replace2 = '<div class="stats-label">Today\'s Purchases</div>';
            $content = str_replace($search2, $replace2, $content);
            $content = str_replace('<div class="stats-number"><?= $total_items ?></div>', '<div class="stats-number"><?= money($total_purchases_today) ?></div>', $content);
            $content = str_replace('<small>Active products</small>', '<small>Raw materials & stock</small>', $content);
            $content = str_replace('<i class="fas fa-utensils stats-icon"></i>', '<i class="fas fa-shopping-cart stats-icon"></i>', $content);
        }
    }
    
    // 2. Add monthly_purchases query
    $search3 = '// Get recent sales';
    if (strpos($content, '$monthly_purchases') === false) {
        $replace3 = '// Get monthly purchases
$monthly_purchases_sql = "
    SELECT DATE_FORMAT(purchase_date, \'%Y-%m\') as month, 
           SUM(total_amount) as total 
    FROM purchases 
    WHERE purchase_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH) 
    GROUP BY DATE_FORMAT(purchase_date, \'%Y-%m\')
    ORDER BY month DESC
";
$monthly_purchases_data = $pdo->query($monthly_purchases_sql)->fetchAll(PDO::FETCH_ASSOC);

// Get recent sales';
        $content = str_replace($search3, $replace3, $content);
    }
    
    // 3. Update Chart.js datasets
    $search4 = 'const monthlyData = <?= json_encode(array_reverse($monthly_sales' . ($is_index ? '_data' : '') . ')) ?>;';
    if (strpos($content, 'monthlyPurchasesData') === false) {
        $replace4 = $search4 . "\n" . 'const monthlyPurchasesData = <?= json_encode(array_reverse($monthly_purchases_data)) ?>;';
        $content = str_replace($search4, $replace4, $content);
        
        $search5 = 'datasets: [{';
        $replace5 = 'datasets: [{
            label: \'Purchases (<?= CURRENCY_SYMBOL ?? \'$\' ?>)\',
            data: monthlyData.map(item => {
                let p = monthlyPurchasesData.find(x => x.month === item.month);
                return p ? parseFloat(p.total) : 0;
            }),
            borderColor: \'#ff9a9e\',
            backgroundColor: \'rgba(255,154,158,0.1)\',
            tension: 0.4,
            fill: true
        }, {';
        $content = str_replace($search5, $replace5, $content);
    }
    
    file_put_contents($file, $content);
    echo "Patched $file\n";
}

patch_file('F:\xampp82\htdocs\restaurant_pos\index.php', true);
patch_file('F:\xampp82\htdocs\restaurant_pos\reports.php', false);
