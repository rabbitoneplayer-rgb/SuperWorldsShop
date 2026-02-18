<?php
include_once("connectdb.php");

// 1. รับค่าค้นหาและช่วงราคาจาก AJAX
$query = isset($_POST["query"]) ? mysqli_real_escape_string($conn, $_POST["query"]) : "";
$min_price = isset($_POST["min_price"]) && $_POST["min_price"] != "" ? (int)$_POST["min_price"] : 0;
$max_price = isset($_POST["max_price"]) && $_POST["max_price"] != "" ? (int)$_POST["max_price"] : 999999;

// --- ส่วนที่ 1: แถบ Sidebar (Trending + Price Filter) ---
echo '<div class="search-sidebar">';
    echo '<div class="sidebar-section">';
        echo '<h6><i class="fas fa-fire me-2 text-danger"></i>คำค้นหายอดนิยม</h6>';
        echo '<div class="trending-wrapper">';
            $trending = ["รองเท้า", "Nike Air", "เสื้อฟุตบอล", "Adidas", "อุปกรณ์กีฬา"];
            foreach ($trending as $item) {
                echo '<a href="index.php?q='.urlencode($item).'" class="trending-item">'.$item.'</a>';
            }
        echo '</div>';
    echo '</div>';

    echo '<hr class="sidebar-divider">';
    
    echo '<div class="sidebar-section">';
        echo '<h6><i class="fas fa-tag me-2 text-muted"></i>ช่วงราคา (บาท)</h6>';
        echo '<div class="price-filter-wrapper">';
            echo '<div class="price-input-row">';
                echo '<input type="number" id="min_price_ajax" class="p-input" placeholder="ต่ำสุด" value="'.($min_price > 0 ? $min_price : '').'">';
                echo '<span class="sep">-</span>';
                echo '<input type="number" id="max_price_ajax" class="p-input" placeholder="สูงสุด" value="'.($max_price < 999999 ? $max_price : '').'">';
            echo '</div>';
            echo '<button type="button" id="btn_apply_price" class="btn-apply shadow-sm">กรองราคา</button>';
        echo '</div>';
    echo '</div>';
echo '</div>';

// --- ส่วนที่ 2: ผลการค้นหาแบบ Ajax พร้อมรูปภาพ ---
echo '<div class="search-content">';

$price_condition = " AND (p_price BETWEEN $min_price AND $max_price)";

if ($query != "") {
    echo '<div class="content-header small fw-bold text-muted mb-3">ผลการค้นหาสำหรับ: <span class="text-danger">"'.htmlspecialchars($query).'"</span></div>';
    $sql = "SELECT * FROM products WHERE (p_name LIKE '%$query%' OR p_brand LIKE '%$search%') $price_condition LIMIT 6";
} else {
    echo '<div class="content-header small fw-bold text-muted mb-3">สินค้าแนะนำสำหรับคุณ</div>';
    $sql = "SELECT * FROM products WHERE 1 $price_condition ORDER BY RAND() LIMIT 6";
}

$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) > 0) {
    echo '<div class="search-ajax-list">'; // ใช้คลาสใหม่เพื่อควบคุม layout
    while ($row = mysqli_fetch_array($result)) {
        ?>
        <a href="product_detail.php?id=<?php echo $row['p_id']; ?>" class="search-ajax-item">
            <div class="search-ajax-img-wrapper">
                <img src="<?php echo $row['p_image']; ?>" class="search-ajax-img" onerror="this.src='https://placehold.co/50x50?text=None'">
            </div>
            <div class="search-ajax-info">
                <div class="fw-bold small text-dark text-truncate" style="max-width: 200px;"><?php echo htmlspecialchars($row['p_name']); ?></div>
                <div class="text-muted" style="font-size: 0.7rem;"><?php echo htmlspecialchars($row['p_brand']); ?></div>
                <div class="text-danger fw-bold small">฿<?php echo number_format($row['p_price']); ?></div>
            </div>
        </a>
        <?php
    }
    echo '</div>';
} else {
    echo '<div class="text-center py-4">';
        echo '<i class="fas fa-box-open fa-2x mb-2 opacity-25"></i>';
        echo '<p class="text-muted small">ไม่พบสินค้าที่ต้องการ</p>';
    echo '</div>';
}
echo '</div>';
?>

<style>
/* CSS ป้องกันการล้นและจัดการ Layout ในช่อง Search */
.search-ajax-list { display: flex; flex-direction: column; gap: 5px; }
.search-ajax-item { 
    display: flex; 
    align-items: center; 
    gap: 12px; 
    padding: 10px; 
    border-radius: 12px; 
    text-decoration: none; 
    transition: 0.2s;
    border-bottom: 1px solid #f8f9fa;
}
.search-ajax-item:hover { background-color: #fdf2f2; }
.search-ajax-img-wrapper { width: 50px; height: 50px; flex-shrink: 0; }
.search-ajax-img { width: 100%; height: 100%; object-fit: contain; border-radius: 8px; background: #fff; }
.search-ajax-info { flex: 1; min-width: 0; }

/* Sidebar Inside Search Results */
.search-sidebar { border-right: 1px solid #eee; padding-right: 15px; margin-right: 15px; width: 220px; flex-shrink: 0; }
.search-content { flex: 1; min-width: 0; }
#search_results { display: flex !important; } /* บังคับให้แสดงผลแบบ Flex */

.trending-wrapper { display: flex; flex-wrap: wrap; gap: 5px; margin-top: 10px; }
.trending-item { font-size: 0.75rem; padding: 4px 12px; background: #f0f2f5; border-radius: 50px; color: #666; text-decoration: none; }
.trending-item:hover { background: #111; color: #fff; }

.price-input-row { display: flex; align-items: center; gap: 5px; margin: 10px 0; }
.p-input { width: 100%; border: 1px solid #eee; border-radius: 8px; padding: 5px 8px; font-size: 0.8rem; }
.btn-apply { width: 100%; border: none; background: #111; color: #fff; border-radius: 8px; padding: 6px; font-size: 0.8rem; font-weight: 600; }
</style>
