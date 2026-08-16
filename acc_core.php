<?php
require_once 'config.php';
require_once 'accounting.php';

if (!function_exists('db')) {
    function db() {
        global $pdo;
        return $pdo;
    }
}

if (!function_exists('h')) {
    function h($str) {
        return htmlspecialchars((string)($str ?? ''), ENT_QUOTES);
    }
}

if (!function_exists('redirect')) {
    function redirect($url) {
        header("Location: $url");
        exit;
    }
}

if (!function_exists('flash')) {
    function flash($type, $message) {
        $_SESSION['flash_' . $type] = $message;
    }
}

if (!function_exists('display_flash')) {
    function display_flash() {
        foreach (['success', 'error', 'warning', 'info'] as $type) {
            if (isset($_SESSION['flash_' . $type])) {
                $bootstrap_class = $type === 'error' ? 'danger' : $type;
                echo '<div class="alert alert-' . $bootstrap_class . ' alert-dismissible fade show" style="margin:20px;">' . h($_SESSION['flash_' . $type]) . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
                unset($_SESSION['flash_' . $type]);
            }
        }
    }
}



// Inject legacy CSS for the accounting pages
echo '<style>
:root {
    --c-primary: #667eea;
    --c-muted: #6c757d;
    --c-bg: #f8f9fa;
}
.card {
    background: white;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    margin-bottom: 20px;
    border: none;
}
.card-title {
    font-size: 1.4rem;
    font-weight: bold;
    color: #333;
    margin-bottom: 20px;
    border-bottom: 2px solid #f0f0f0;
    padding-bottom: 10px;
}
.form-group {
    margin-bottom: 15px;
}
.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
    color: #555;
}
.form-group input, .form-group select {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-family: inherit;
}
.form-group input:focus, .form-group select:focus {
    border-color: var(--c-primary);
    outline: none;
    box-shadow: 0 0 0 2px rgba(102,126,234,0.2);
}
table th {
    background: var(--c-bg) !important;
    font-weight: 600;
    color: #444;
}
table td {
    vertical-align: middle;
}
</style>';

if (!function_exists('paginate')) {
    function paginate($total, $limit, $page, $url = '') {
        if (empty($url)) $url = basename($_SERVER['PHP_SELF']);
        $pages = ceil($total / $limit);
        $offset = ($page - 1) * $limit;
        
        $html = '';
        if ($pages > 1) {
            $html .= '<nav><ul class="pagination pagination-sm justify-content-center mt-3">';
            for ($i = 1; $i <= $pages; $i++) {
                $active = $i == $page ? 'active' : '';
                $u = strpos($url, '?') !== false ? "$url&page=$i" : "$url?page=$i";
                $html .= "<li class='page-item $active'><a class='page-link' href='$u'>$i</a></li>";
            }
            $html .= '</ul></nav>';
        }
        
        return [
            'html' => $html,
            'per_page' => $limit,
            'offset' => $offset
        ];
    }
}

if (!function_exists('fmt_date')) {
    function fmt_date($date) {
        if (!$date) return '';
        return date('d M Y', strtotime($date));
    }
}


if (!function_exists('acc_branch_sql')) {
    function acc_branch_sql($alias = 'j') {
        $branch_id = $_SESSION['branch_id'] ?? null;
        if ($branch_id && $branch_id !== 'all') {
            $prefix = $alias ? "{$alias}." : "";
            return " AND {$prefix}branch_id = " . (int)$branch_id . " ";
        }
        return " ";
    }
}
?>
