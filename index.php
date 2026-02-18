<?php 
session_start(); 
include_once("connectdb.php"); 

// รับค่าหมวดหมู่และคำค้นหา
$cat = isset($_GET['cat']) ? mysqli_real_escape_string($conn, $_GET['cat']) : '';
$search = isset($_GET['q']) ? mysqli_real_escape_string($conn, $_GET['q']) : '';

// ส่วนนับจำนวนสินค้าในตะกร้า
$cart_count = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $qty) { $cart_count += $qty; }
}

$is_logged_in = isset($_SESSION['user_id']);
$is_admin = (isset($_SESSION['user_id']) && isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1);

// --- 1. Smart SQL Filter ---
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
    <title>SUPER WORLDS | แหล่งรวมอุปกรณ์กีฬา</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root { --ss-red: #e12128; --ss-dark: #111111; --ss-gray: #f8f9fa; }
        body { font-family: 'Kanit', sans-serif; background-color: var(--ss-gray); color: #333; margin: 0; padding: 0; overflow-x: hidden; }
        
        /* --- Navigation & Dropdown Management --- */
        .navbar { background-color: var(--ss-dark) !important; padding: 15px 0; border-bottom: 3px solid var(--ss-red); z-index: 1050; }
        .navbar-brand { font-size: 1.8rem; font-weight: 800; }
        
        .dropdown-menu { 
            min-width: 250px !important; 
            border-radius: 20px !important; 
            padding: 12px !important; 
            box-shadow: 0 20px 50px rgba(0,0,0,0.3) !important;
            margin-top: 15px !important;
            border: none !important;
            z-index: 9999 !important; 
        }
        .dropdown-item { padding: 12px 20px !important; border-radius: 12px !important; font-weight: 500; transition: 0.3s; color: #333 !important; text-decoration: none !important; }
        .dropdown-item:hover { background: var(--ss-gray) !important; color: var(--ss-red) !important; }

        /* --- Search System Fix --- */
        .search-container { max-width: 900px; margin: 0 auto; position: relative; z-index: 1040; }
        .search-input-group { background: white; border-radius: 50px; padding: 8px 8px 8px 25px; display: flex; align-items: center; border: 2px solid #eee; }
        #search_results { width: 100%; display: none; background: white; z-index: 1030; box-shadow: 0 30px 60px rgba(0,0,0,0.2); position: absolute; top: 100%; left: 0; margin-top: 15px; border-radius: 25px; border: 1px solid #eee; padding: 25px; max-height: 500px; overflow-y: auto; }

        /* --- แก้ปัญหา Sidebar สีน้ำเงิน --- */
        .cat-group { display: flex; flex-direction: column; gap: 5px; background: white; padding: 25px; border-radius: 25px; box-shadow: 0 10px 30px rgba(0,0,0,0.02); max-height: 75vh; overflow-y: auto; }
        .filter-btn { display: flex; align-items: center; justify-content: space-between; padding: 12px 15px; border-radius: 12px; color: #555 !important; text-decoration: none !important; font-size: 0.9rem; transition: 0.3s; }
        .filter-btn:hover { background: var(--ss-gray); color: var(--ss-red) !important; }
        .filter-btn.active { background: var(--ss-dark); color: #fff !important; font-weight: 600; }

        /* --- แก้ปัญหาภาพสินค้าล้น/หาย --- */
        .product-card { border: none; border-radius: 30px; transition: 0.4s; background: #fff; border: 1px solid #f0f0f0; position: relative; overflow: hidden; height: 100%; }
        .product-img-wrapper { 
            padding: 20px; 
            height: 320px; /* เพิ่มความสูงเพื่อให้เห็นรูปเต็ม */
            display: flex; 
            align-items: center; 
            justify-content: center; 
            background: #fff;
        }
        .product-img { 
            max-height: 100%; 
            max-width: 100%; 
            object-fit: contain; /* บังคับให้รูปอยู่ในกรอบไม่เบี้ยว */
            transition: 0.5s;
        }
        .product-card:hover .product-img { transform: scale(1.05); }

        .category-tag { position: absolute; top: 15px; left: 15px; padding: 5px 15px; border-radius: 50px; font-size: 0.65rem; font-weight: 700; text-transform: uppercase; z-index: 5; background: #eee; color: #333; }
        .tag-men { background: #111; color: #fff; }
        .tag-women { background: #ff4d94; color: #fff; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark sticky-top shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php">SUPER<span class="text-danger">WORLDS</span></a>
        <div class="d-flex align-items-center gap-3">
            <a href="cart.php" class="text-white position-relative p-2"><i class="fas fa-shopping-bag fa-lg"></i><span id="cart-badge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"><?php echo $cart_count; ?></span></a>
            <div class="dropdown">
                <a href="#" class="text-white text-decoration-none bg-white bg-opacity-10 py-2 px-3 rounded-pill d-flex align-items-center" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-user-circle me-2"></i> 
                    <span class="small fw-bold text-uppercase"><?php echo isset($_SESSION['fullname']) ? explode(' ', $_SESSION['fullname'])[0] : 'LOGIN'; ?></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow-lg">
                    <?php if($is_logged_in): ?>
                        <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user-circle me-2"></i> โปรไฟล์ของฉัน</a></li>
                        <li><a class="dropdown-item" href="order_history.php"><i class="fas fa-history me-2"></i> ประวัติการซื้อ</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger fw-bold" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> ออกจากระบบ</a></li>
                    <?php else: ?>
                        <li><a class="dropdown-item" href="login.php"><i class="fas fa-sign-in-alt me-2"></i> เข้าสู่ระบบ</a></li>
                        <li><a class="dropdown-item" href="register.php"><i class="fas fa-user-plus me-2"></i> สมัครสมาชิก</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</nav>

<div class="bg-white py-5 border-bottom" style="z-index: 1045; position: relative;">
    <div class="container">
        <form action="index.php" method="get" id="searchForm" class="search-container">
            <div class="search-input-group shadow-sm">
                <input type="text" name="q" id="search_input" placeholder="ค้นหาแบรนด์หรือสินค้า..." value="<?php echo htmlspecialchars($search); ?>" autocomplete="off">
                <button type="submit" class="btn border-0"><i class="fas fa-search text-muted"></i></button>
            </div>
            <div id="search_results" class="text-start">
                <div id="ajax_content"><p class="text-muted small py-2 px-3">กำลังค้นหาสินค้าแนะนำ...</p></div>
            </div>
        </form>
    </div>
</div>

<div class="container my-5">
    <div class="row g-4">
        <div class="col-lg-3 d-none d-lg-block">
            <div class="cat-group shadow-sm">
                <div class="fw-bold small text-muted text-uppercase mb-3">หมวดหมู่สินค้า</div>
                <a href="index.php" class="filter-btn <?= ($cat == '') ? 'active' : '' ?>">ทั้งหมด <i class="fas fa-th-large"></i></a>
                <?php
                $groups = ['SHOES' => 'รองเท้า', 'APPAREL' => 'เสื้อผ้า', 'GEAR' => 'อุปกรณ์'];
                foreach ($groups as $key => $label):
                    echo "<div class='fw-bold small text-muted text-uppercase mt-4 mb-2'>$label</div>";
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
                <h4 class="fw-bold m-0"><?php echo ($cat != '') ? htmlspecialchars($cat) : 'สินค้าแนะนำ'; ?></h4>
                <span class="badge bg-white text-dark shadow-sm py-2 px-3 rounded-pill fw-bold">พบ <?php echo $total_items; ?> รายการ</span>
            </div>
            <div class="row g-4">
                <?php if(mysqli_num_rows($result) > 0): while($row = mysqli_fetch_array($result)): 
                        $gen = $row['p_gender'] ?? 'Unisex';
                        $tc = ($gen == 'ชาย' || $gen == 'ผู้ชาย') ? 'tag-men' : (($gen == 'หญิง' || $gen == 'ผู้หญิง') ? 'tag-women' : '');
                ?>
                    <div class="col-6 col-md-4">
                        <div class="card product-card shadow-sm">
                            <span class="category-tag <?php echo $tc; ?>"><?php echo $gen; ?></span>
                            <div class="product-img-wrapper">
                                <a href="product_detail.php?id=<?= $row['p_id'] ?>"><img src="<?= $row['p_image'] ?>" class="product-img" onerror="this.src='https://placehold.co/400x400'"></a>
                            </div>
                            <div class="card-body p-4 pt-0 text-center">
                                <div class="text-muted small fw-bold text-uppercase mb-1"><?= $row['p_brand'] ?></div>
                                <h6 class="fw-bold mb-3" style="height:38px; overflow:hidden;"><?= $row['p_name'] ?></h6>
                                <div class="h5 fw-800 mb-3" style="color: var(--ss-dark);">฿<?= number_format($row['p_price']) ?></div>
                                <button class="btn btn-dark w-100 rounded-pill add-to-cart-btn" data-id="<?= $row['p_id'] ?>">ใส่ตะกร้า</button>
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
            url: "fetch_search.php", method: "POST", data: { query: query },
            success: function(data) { $('#ajax_content').html(data); }
        });
    }
    $('#search_input').on('focus', function(){ $('#search_results').fadeIn(200); performSearch($(this).val()); });
    $('#search_input').on('keyup', function(){ performSearch($(this).val()); });
    $(document).click(function(e) { if (!$(e.target).closest('#searchForm').length) $('#search_results').fadeOut(200); });

    $('.add-to-cart-btn').click(function(e) {
        if (!<?= $is_logged_in ? 'true' : 'false' ?>) {
            Swal.fire({ title: 'กรุณาเข้าสู่ระบบ', icon: 'warning', confirmButtonColor: '#111' }).then(r => { if(r.isConfirmed) window.location.href='login.php'; });
            return;
        }
        const p_id = $(this).data('id');
        $.ajax({
            url: 'cart_action.php', type: 'GET', data: { id: p_id, action: 'add', ajax: 1 },
            success: function(res) {
                Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'เพิ่มแล้ว', showConfirmButton: false, timer: 1500 });
                $('#cart-badge').text(res);
            }
        });
    });
});
</script>
</body>
</html>
