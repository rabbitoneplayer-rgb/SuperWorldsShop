<?php
include_once("connectdb.php");

// 1. รับค่าจาก AJAX
$query = isset($_POST["query"]) ? mysqli_real_escape_string($conn, $_POST["query"]) : "";
$min = isset($_POST["min_price"]) && $_POST["min_price"] != "" ? (int)$_POST["min_price"] : 0;
$max = isset($_POST["max_price"]) && $_POST["max_price"] != "" ? (int)$_POST["max_price"] : 999999;

$price_condition = " AND (p_price BETWEEN $min AND $max)";

// 2. เริ่มโครงสร้างแบบ Flex เพื่อแก้ปัญหาเบี้ยว
echo '<div class="search-flex-container" style="display: flex; gap: 30px;">';

    // --- ฝั่งซ้าย: Sidebar (ตัวกรองและเทรนด์) ---
    echo '<div class="search-sidebar-box" style="width: 280px; flex-shrink: 0; border-right: 1px solid #eee; padding-right: 25px;">';
        echo '<div class="mb-4">';
            echo '<h6 class="fw-bold small text-muted text-uppercase mb-3"><i class="fas fa-fire text-danger me-2"></i>คำค้นหายอดนิยม</h6>';
            echo '<div class="d-flex flex-wrap gap-2">';
                $trending = ["รองเท้าวิ่ง", "Nike", "Adidas", "อุปกรณ์กีฬา"];
                foreach ($trending as $t) {
                    echo '<a href="index.php?q='.urlencode($t).'" class="pop-tag">'.$t.'</a>';
                }
            echo '</div>';
        echo '</div>';

        echo '<div class="mb-2">';
            echo '<h6 class="fw-bold small text-muted text-uppercase mb-3"><i class="fas fa-filter me-2"></i>กรองราคา (บาท)</h6>';
            echo '<div class="d-flex align-items-center gap-2 mb-3">';
                echo '<input type="number" id="min_p" class="price-input" placeholder="ต่ำสุด" value="'.($min > 0 ? $min : '').'">';
                echo '<span class="text-muted">-</span>';
                echo '<input type="number" id="max_p" class="price-input" placeholder="สูงสุด" value="'.($max < 999999 ? $max : '').'">';
            echo '</div>';
            echo '<button type="button" onclick="applyPriceFilter()" class="btn-filter-go">กรองราคา</button>';
        echo '</div>';
    echo '</div>';

    // --- ฝั่งขวา: รายการสินค้าแนะนำ/ผลการค้นหา ---
    echo '<div class="search-result-box" style="flex-grow: 1;">';
        if ($query != "") {
            echo '<h6 class="fw-bold mb-3">ผลการค้นหาสำหรับ: <span class="text-danger">"'.htmlspecialchars($query).'"</span></h6>';
            $sql = "SELECT * FROM products WHERE (p_name LIKE '%$query%' OR p_brand LIKE '%$query%') $price_condition LIMIT 5";
        } else {
            echo '<h6 class="fw-bold mb-3"><i class="fas fa-star text-warning me-2"></i>สินค้าแนะนำสำหรับคุณ</h6>';
            $sql = "SELECT * FROM products WHERE 1 $price_condition ORDER BY RAND() LIMIT 5";
        }

        $res = mysqli_query($conn, $sql);
        if (mysqli_num_rows($res) > 0) {
            while ($row = mysqli_fetch_array($res)) {
                echo '
                <a href="product_detail.php?id='.$row['p_id'].'" class="search-item-row">
                    <div class="search-item-img">
                        <img src="'.$row['p_image'].'" onerror="this.src=\'https://placehold.co/50x50?text=None\'">
                    </div>
                    <div class="search-item-info">
                        <div class="item-name-text">'.$row['p_name'].'</div>
                        <div class="item-brand-text">'.$row['p_brand'].'</div>
                        <div class="item-price-text">฿'.number_format($row['p_price']).'</div>
                    </div>
                    <div class="search-item-arrow"><i class="fas fa-chevron-right"></i></div>
                </a>';
            }
        } else {
            echo '<div class="text-center py-5 text-muted small"><i class="fas fa-search-minus fa-2x mb-2 opacity-50"></i><br>ไม่พบสินค้าที่ต้องการ</div>';
        }
    echo '</div>';

echo '</div>';
?>

<style>
/* จัดการสไตล์ให้หายเบี้ยวและสวยงาม */
.pop-tag { padding: 5px 12px; background: #f8f9fa; border-radius: 50px; color: #666; text-decoration: none; font-size: 0.75rem; border: 1px solid #eee; transition: 0.3s; }
.pop-tag:hover { background: #111; color: #fff; border-color: #111; }

.price-input { width: 100%; border: 1px solid #ddd; border-radius: 10px; padding: 6px 10px; font-size: 0.8rem; outline: none; }
.price-input:focus { border-color: #e12128; }

.btn-filter-go { width: 100%; border: none; background: #111; color: #fff; padding: 8px; border-radius: 10px; font-weight: 600; font-size: 0.85rem; transition: 0.3s; }
.btn-filter-go:hover { background: #e12128; }

.search-item-row { display: flex; align-items: center; gap: 15px; padding: 12px; border-radius: 15px; text-decoration: none; color: inherit; transition: 0.2s; border-bottom: 1px solid #fbfbfb; }
.search-item-row:hover { background: #fff5f5; }

.search-item-img { width: 55px; height: 55px; flex-shrink: 0; background: #fff; border-radius: 10px; padding: 5px; border: 1px solid #eee; }
.search-item-img img { width: 100%; height: 100%; object-fit: contain; }

.search-item-info { flex-grow: 1; min-width: 0; }
.item-name-text { font-weight: 600; font-size: 0.9rem; color: #111; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.item-brand-text { font-size: 0.7rem; color: #999; text-transform: uppercase; }
.item-price-text { font-weight: 800; color: #e12128; font-size: 0.95rem; }

.search-item-arrow { color: #eee; font-size: 0.8rem; }
.search-item-row:hover .search-item-arrow { color: #e12128; transform: translateX(3px); transition: 0.3s; }
</style>
