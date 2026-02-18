<?php 
session_start(); 
include_once("connectdb.php"); 

// รับค่าหมวดหมู่และคำค้นหา
$cat = isset($_GET['cat']) ? mysqli_real_escape_string($conn, $_GET['cat']) : '';
$search = isset($_GET['q']) ? mysqli_real_escape_string($conn, $_GET['q']) : '';

// ส่วนนับจำนวนสินค้าในตะกร้า (คงเดิม)
$cart_count = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $qty) { $cart_count += $qty; }
}

$is_logged_in = isset($_SESSION['user_id']);
$is_admin = (isset($_SESSION['user_id']) && isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1);

// Logic ดึงข้อมูลสินค้า
$where_clause = " WHERE 1 ";
if ($search) { $where_clause .= " AND (p_name LIKE '%$search%' OR p_brand LIKE '%$search%' OR p_category LIKE '%$search%') "; }
if ($cat) {
    if (strpos($cat, 'รองเท้า') !== false) {
        $where_clause .= " AND p_category LIKE '%รองเท้า%' ";
        if (strpos($cat, 'ชาย') !== false) { $where_clause .= " AND (p_gender = 'ชาย' OR p_gender = 'ผู้ชาย') "; }
        elseif (strpos($cat, 'หญิง') !== false) { $where_clause .= " AND (p_gender = 'หญิง' OR p_gender = 'ผู้หญิง') "; }
    } elseif (strpos($cat, 'เสื้อผ้า') !== false) {
        $where_clause .= " AND p_category LIKE '%เสื้อผ้า%' ";
        if (strpos($cat, 'ชาย') !== false) { $where_clause .= " AND (p_gender = 'ชาย' OR p_gender = 'ผู้ชาย') "; }
        elseif (strpos($cat, 'หญิง') !== false) { $where_clause .= " AND (p_gender = 'หญิง' OR p_gender = 'ผู้หญิง') "; }
    } else {
        $where_clause .= " AND (p_category LIKE '%$cat%' OR p_gender LIKE '%$cat%') ";
    }
}

$count_res = mysqli_query($conn, "SELECT COUNT(*) as total FROM products " . $where_clause);
$total_items = mysqli_fetch_array($count_res)['total'];
$sql = "SELECT * FROM products " . $where_clause . " ORDER BY p_id DESC";
$result = mysqli_query($conn, $sql);
?>
<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SUPER WORLDS | ค้นหาสินค้ากีฬา</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root { --ss-red: #e12128; --ss-dark: #111111; --ss-gray: #f8f9fa; }
        body { font-family: 'Kanit', sans-serif; background-color: var(--ss-gray); color: #333; margin: 0; padding: 0; overflow-x: hidden; }
        
        /* --- Navigation --- */
        .navbar { background-color: var(--ss-dark) !important; padding: 15px 0; border-bottom: 3px solid var(--ss-red); z-index: 2000; }
        .navbar-brand { font-size: 1.8rem; font-weight: 800; }

        /* --- Sidebar --- */
        .cat-group { display: flex; flex-direction: column; gap: 5px; background: white; padding: 20px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.02); max-height: 70vh; overflow-y: auto; }
        .filter-btn { display: flex; align-items: center; justify-content: space-between; padding: 10px 15px; border-radius: 12px; color: #555; text-decoration: none; font-size: 0.9rem; transition: 0.3s; }
        .filter-btn.active { background: var(--ss-dark); color: #fff; font-weight: 600; }

        /* --- 🛠 แก้ไขช่อง Search ไม่ให้ล้นและแสดงรูป --- */
        .search-container { max-width: 800px; margin: 0 auto; position: relative; z-index: 1500; }
        .search-input-group { background: white; border-radius: 50px; padding: 8px 8px 8px 25px; display: flex; align-items: center; border: 2px solid #eee; }
        
        #search_results { 
            width: 100%; display: none; background: white; z-index: 1600; 
            box-shadow: 0 20px 50px rgba(0,0,0,0.15); position: absolute; 
            top: 100%; left: 0; margin-top: 10px; border-radius: 20px; 
            border: 1px solid #eee; padding: 20px; max-height: 400px; overflow-y: auto;
        }

        /* การแสดงรูปสินค้าในผลลัพธ์การค้นหา  */
        .search-ajax-item { display: flex; align-items: center; gap: 15px; padding: 10px; border-radius: 12px; text-decoration: none; color: #333; border-bottom: 1px solid #f8f9fa; }
        .search-ajax-item:hover { background: #fdf2f2; }
        .search-ajax-img { width: 45px; height: 45px; object-fit: contain; background: #fff; border-radius: 8px; border: 1px solid #eee; }

        /* --- Product Cards (กัน Tag ล้น) --- */
        .product-card { border: none; border-radius: 25px; transition: 0.4s; background: #fff; border: 1px solid #f0f0f0; position: relative; overflow: hidden; height: 100%; }
        .product-img-wrapper { padding: 30px; height: 250px; display: flex; align-items: center; justify-content: center; }
        .product-img { max-height: 100%; max-width: 100%; object-fit: contain; }
        .category-tag { position: absolute; top: 12px; left: 12px; padding: 4px 12px; border-radius: 50px; font-size: 0.6rem; font-weight: 700; text-transform: uppercase; z-index: 5; max-width: 80%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark sticky-top shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php">SUPER<span class="text-danger">WORLDS</span></a>
        <div class="d-flex align-items-center gap-4">
            <a href="cart.php" class="text-white position-relative p-2"><i class="fas fa-shopping-bag fa-lg"></i><span id="cart-badge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"><?php echo $cart_count; ?></span></a>
            <div class="dropdown">
                <a href="#" class="text-white text-decoration-none bg-white bg-opacity-10 py-2 px-3 rounded-pill d-flex align-items-center" data-bs-toggle="dropdown">
                    <i class="fas fa-user-circle me-2"></i> <span class="small fw-bold"><?php echo isset($_SESSION['fullname']) ? explode(' ', $_SESSION['fullname'])[0] : 'LOGIN'; ?></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-4">
                    <?php if($is_logged_in): ?>
                        <li><a class="dropdown-item" href="order_history.php">ประวัติการซื้อ</a></li>
                        <li><a class="dropdown-item text-danger" href="logout.php">ออกจากระบบ</a></li>
                    <?php else: ?>
                        <li><a class="dropdown-item" href="login.php">เข้าสู่ระบบ</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</nav>

<div class="bg-white py-5 border-bottom">
    <div class="container">
        <form action="index.php" method="get" id="searchForm" class="search-container">
            <div class="search-input-group">
                <input type="text" name="q" id="search_input" placeholder="ค้นหาสินค้าที่นี่..." value="<?php echo htmlspecialchars($search); ?>" autocomplete="off">
                <button type="submit" class="btn border-0"><i class="fas fa-search text-muted"></i></button>
            </div>
            <div id="search_results">
                <div id="ajax_content">
                    <p class="text-muted small py-2 px-3">กำลังค้นหาสินค้าแนะนำ...</p>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="container my-5">
    <div class="row g-4">
        <div class="col-lg-3 d-none d-lg-block">
            <div class="cat-group">
                <div class="fw-bold small text-muted text-uppercase mb-2">หมวดหมู่สินค้า</div>
                <a href="index.php" class="filter-btn <?= ($cat == '') ? 'active' : '' ?>">ทั้งหมด <i class="fas fa-th-large"></i></a>
                <?php
                $groups = ['SHOES' => 'รองเท้า', 'APPAREL' => 'เสื้อผ้า', 'GEAR' => 'อุปกรณ์'];
                foreach ($groups as $key => $label):
                    echo "<div class='cat-header small fw-bold text-muted text-uppercase mt-3'>$label</div>";
                    $cq = mysqli_query($conn, "SELECT * FROM categories WHERE cat_group = '$key'");
                    while ($c = mysqli_fetch_array($cq)):
                        $fcat = ($key == 'GEAR') ? $c['cat_name'] : $label . $c['cat_name'];
                ?>
                    <a href="index.php?cat=<?= urlencode($fcat) ?>" class="filter-btn <?= ($cat == $fcat) ? 'active' : '' ?>"><?= $c['cat_name'] ?></a>
                <?php endwhile; endforeach; ?>
            </div>
        </div>

        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-4 px-2">
                <h4 class="fw-bold m-0"><?php echo ($cat != '') ? htmlspecialchars($cat) : 'สินค้าทั้งหมด'; ?></h4>
                <span class="badge bg-white text-dark shadow-sm py-2 px-3 rounded-pill fw-bold">พบ <?php echo $total_items; ?> รายการ</span>
            </div>
            <div class="row g-4">
                <?php if(mysqli_num_rows($result) > 0): while($row = mysqli_fetch_array($result)): 
                        $gen = $row['p_gender'] ?? 'Unisex';
                        $tc = ($gen == 'ชาย' || $gen == 'ผู้ชาย') ? 'tag-men' : (($gen == 'หญิง' || $gen == 'ผู้หญิง') ? 'tag-women' : 'tag-default');
                ?>
                    <div class="col-6 col-md-4">
                        <div class="card product-card">
                            <span class="category-tag <?php echo $tc; ?>"><?php echo $gen; ?></span>
                            <div class="product-img-wrapper"><a href="product_detail.php?id=<?= $row['p_id'] ?>"><img src="<?= $row['p_image'] ?>" class="product-img" onerror="this.src='https://placehold.co/400x400'"></a></div>
                            <div class="card-body p-4 pt-0">
                                <div class="text-muted small fw-bold text-uppercase mb-1"><?= $row['p_brand'] ?></div>
                                <h6 class="fw-bold mb-3" style="height:38px; overflow:hidden;"><?= $row['p_name'] ?></h6>
                                <div class="h5 fw-bold mb-3">฿<?= number_format($row['p_price']) ?></div>
                                <button type="button" class="btn btn-dark w-100 add-to-cart-btn" data-id="<?= $row['p_id'] ?>">ใส่ตะกร้า</button>
                            </div>
                        </div>
                    </div>
                <?php endwhile; else: ?>
                    <div class="col-12 text-center py-5 opacity-50"><h4>ไม่พบสินค้า</h4></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<footer class="py-5 text-center text-white" style="background:#111;"><div class="container"><p class="small opacity-50">© 2026 SUPERWORLDS STORE.</p></div></footer>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function(){
    function performSearch(query) {
        $.ajax({
            url: "fetch_search.php",
            method: "POST",
            data: { query: query },
            success: function(data) {
                $('#ajax_content').html(data);
            }
        });
    }

    // คลิกแล้วเด้งแสดงผลทันที (Focus)
    $('#search_input').on('focus', function(){ 
        $('#search_results').fadeIn(200); 
        performSearch($(this).val()); 
    });

    $('#search_input').on('keyup', function(){ performSearch($(this).val()); });
    $(document).click(function(e) { if (!$(e.target).closest('#searchForm').length) $('#search_results').fadeOut(200); });
});
</script>
</body>
</html>
