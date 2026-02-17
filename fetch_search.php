<?php
include_once("connectdb.php");

// 1. รับค่าค้นหาและช่วงราคา
$query = isset($_POST["query"]) ? mysqli_real_escape_string($conn, $_POST["query"]) : "";
$min_price = isset($_POST["min_price"]) && $_POST["min_price"] != "" ? (int)$_POST["min_price"] : 0;
$max_price = isset($_POST["max_price"]) && $_POST["max_price"] != "" ? (int)$_POST["max_price"] : 999999;

// --- ฝั่งซ้าย: แถบ Sidebar (Trending + Price Filter) ---
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
                echo '<input type="number" id="min_price" class="p-input" placeholder="ต่ำสุด" value="'.($min_price > 0 ? $min_price : '').'">';
                echo '<span class="sep">-</span>';
                echo '<input type="number" id="max_price" class="p-input" placeholder="สูงสุด" value="'.($max_price < 999999 ? $max_price : '').'">';
            echo '</div>';
            echo '<button type="button" id="btn_apply_price" class="btn-apply shadow-sm">ประยุกต์ใช้</button>';
        echo '</div>';
    echo '</div>';
echo '</div>';

// --- ฝั่งขวา: ผลการค้นหา ---
echo '<div class="search-content">';

$price_condition = " AND (p_price BETWEEN $min_price AND $max_price)";

if ($query != "") {
    echo '<div class="content-header">ผลการค้นหาสำหรับ: <span class="text-danger">"'.htmlspecialchars($query).'"</span></div>';
    $sql = "SELECT * FROM products WHERE (p_name LIKE '%$query%' OR p_brand LIKE '%$query%') $price_condition LIMIT 6";
} else {
    echo '<div class="content-header">สินค้าที่คุณอาจสนใจ</div>';
    $sql = "SELECT * FROM products WHERE 1 $price_condition ORDER BY RAND() LIMIT 6";
}

$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) > 0) {
    echo '<div class="search-grid">';
    while ($row = mysqli_fetch_array($result)) {
        ?>
        <a href="product_detail.php?id=<?php echo $row['p_id']; ?>" class="search-item-card">
            <div class="item-img">
                <img src="<?php echo $row['p_image']; ?>" onerror="this.src='https://placehold.co/100x100?text=No+Img'">
            </div>
            <div class="item-info">
                <span class="item-brand"><?php echo htmlspecialchars($row['p_brand']); ?></span>
                <span class="item-name"><?php echo htmlspecialchars($row['p_name']); ?></span>
                <span class="item-price">฿<?php echo number_format($row['p_price']); ?></span>
            </div>
        </a>
        <?php
    }
    echo '</div>';
} else {
    echo '<div class="search-empty text-center">';
        echo '<div class="empty-icon"><i class="fas fa-search-minus fa-3x mb-3 opacity-25"></i></div>';
        echo '<p class="text-muted">ไม่พบสินค้าในช่วงราคานี้</p>';
    echo '</div>';
}
echo '</div>';
?>