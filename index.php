<?php 
session_start(); 
include_once("connectdb.php"); 

$cat = isset($_GET['cat']) ? $_GET['cat'] : '';
$search = isset($_GET['q']) ? $_GET['q'] : '';

// ส่วนนับจำนวนสินค้าในตะกร้า (โครงสร้างเดิม)
$cart_count = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $qty) { $cart_count += $qty; }
}

// เช็คสถานะการล็อกอินและแอดมิน (โครงสร้างเดิม)
$is_logged_in = isset($_SESSION['user_id']);
$is_admin = (isset($_SESSION['user_id']) && isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1);
?>
<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SUPER WORLDS | แหล่งรวมอุปกรณ์กีฬาระดับโลก</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root { --ss-red: #e12128; --ss-dark: #111111; --ss-gray: #f8f9fa; }
        body { font-family: 'Kanit', sans-serif; background-color: var(--ss-gray); color: #333; margin: 0; padding: 0; }
        
        /* --- Floating Admin Sidebar --- */
        .admin-sidebar { 
            position: fixed; left: 20px; top: 50%; transform: translateY(-50%); 
            background: rgba(0,0,0,0.9); backdrop-filter: blur(10px);
            padding: 15px 10px; border-radius: 20px; z-index: 9999;
            display: flex; flex-direction: column; gap: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5); border: 1px solid rgba(255,255,255,0.1);
        }
        .admin-btn { 
            width: 45px; height: 45px; display: flex; align-items: center; justify-content: center;
            color: #fff; text-decoration: none; border-radius: 12px; transition: 0.3s;
        }
        .admin-btn:hover { background: var(--ss-red); transform: scale(1.1); }
        .admin-tooltip {
            position: absolute; left: 60px; background: #000; color: #fff; padding: 5px 12px;
            border-radius: 8px; font-size: 0.75rem; white-space: nowrap; visibility: hidden; opacity: 0;
            transition: 0.3s;
        }
        .admin-btn:hover .admin-tooltip { visibility: visible; opacity: 1; left: 55px; }

        /* --- Navigation --- */
        .navbar { background-color: var(--ss-dark) !important; padding: 15px 0; border-bottom: 3px solid var(--ss-red); }
        .navbar-brand { font-size: 1.8rem; font-weight: 800; letter-spacing: -1px; }

        /* --- Sidebar Category --- */
        .cat-group { background: white; padding: 20px; border-radius: 25px; box-shadow: 0 10px 30px rgba(0,0,0,0.02); }
        .cat-header { font-weight: 800; text-transform: uppercase; font-size: 0.7rem; color: #bbb; letter-spacing: 1.5px; margin: 15px 0 5px; }
        .filter-btn { 
            display: flex; align-items: center; justify-content: space-between;
            padding: 8px 12px; border-radius: 10px; color: #555; text-decoration: none; 
            font-size: 0.85rem; transition: 0.3s;
        }
        .filter-btn:hover { background: #fff5f5; color: var(--ss-red); padding-left: 18px; }
        .filter-btn.active { background: var(--ss-dark); color: #fff; font-weight: 600; }

        /* --- Search & Live Results --- */
        .search-container { max-width: 800px; margin: 0 auto; position: relative; }
        .search-input-group { background: white; border-radius: 50px; padding: 8px 8px 8px 25px; display: flex; align-items: center; border: 2px solid #eee; }
        .search-input-group input { border: none; outline: none; flex: 1; font-size: 1rem; }
        .btn-search { background: var(--ss-dark); color: white; border-radius: 50px; width: 45px; height: 45px; border: none; }
        #search_results { 
            position: absolute; width: 100%; background: white; z-index: 9990; 
            margin-top: 10px; border-radius: 20px; box-shadow: 0 20px 50px rgba(0,0,0,0.15);
            display: none; overflow: hidden; border: 1px solid #eee;
        }

        /* --- Product Card & Tags --- */
        .product-card { border: none; border-radius: 25px; transition: 0.4s; background: #fff; height: 100%; border: 1px solid #f0f0f0; position: relative; }
        .product-card:hover { transform: translateY(-10px); box-shadow: 0 25px 50px rgba(0,0,0,0.06); }
        .product-img-wrapper { padding: 30px; height: 250px; display: flex; align-items: center; justify-content: center; position: relative; }
        .product-img { max-height: 100%; max-width: 100%; object-fit: contain; }
        
        .category-tag {
            position: absolute; top: 15px; left: 15px; padding: 4px 12px; border-radius: 50px;
            font-size: 0.6rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; z-index: 5;
        }
        .tag-men { background: #111; color: #fff; }
        .tag-women { background: #ff4d94; color: #fff; }
        .tag-unisex { background: #eee; color: #333; }

        .price-tag { font-size: 1.3rem; font-weight: 800; color: var(--ss-dark); margin: 10px 0; }
        .btn-add-cart { background: var(--ss-dark); color: white; border-radius: 15px; padding: 10px; font-weight: 600; width: 100%; border: none; transition: 0.3s; }
        .btn-add-cart:hover { background: var(--ss-red); }

        footer { background: var(--ss-dark) !important; color: #888; padding: 60px 0; }
    </style>
</head>
<body>

<?php if($is_admin): ?>
<div class="admin-sidebar shadow-lg">
    <a href="admin_products.php" class="admin-btn"><i class="fas fa-boxes-stacked"></i><span class="admin-tooltip">จัดการสต็อก</span></a>
    <a href="admin_orders.php" class="admin-btn"><i class="fas fa-file-invoice-dollar"></i><span class="admin-tooltip">ออเดอร์</span></a>
</div>
<?php endif; ?>

<nav class="navbar navbar-expand-lg navbar-dark sticky-top">
    <div class="container">
        <a class="navbar-brand" href="index.php">SUPER<span class="text-danger">WORLDS</span></a>
        <div class="d-flex align-items-center gap-4 order-lg-3">
            <a href="cart.php" class="text-white position-relative p-2">
                <i class="fas fa-shopping-bag fa-lg"></i>
                <span id="cart-badge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"><?php echo $cart_count; ?></span>
            </a>
            <div class="dropdown">
                <a href="#" class="text-white text-decoration-none bg-white bg-opacity-10 py-2 px-3 rounded-pill" data-bs-toggle="dropdown">
                    <i class="fas fa-user-circle me-1"></i> <span class="small fw-bold"><?php echo isset($_SESSION['fullname']) ? explode(' ', $_SESSION['fullname'])[0] : 'LOGIN'; ?></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-4 mt-2 p-2">
                    <?php if($is_logged_in): ?>
                        <li><a class="dropdown-item py-2 rounded-3" href="profile.php">โปรไฟล์</a></li>
                        <li><a class="dropdown-item py-2 rounded-3" href="order_history.php">ประวัติการซื้อ</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item py-2 rounded-3 text-danger" href="logout.php">ออกจากระบบ</a></li>
                    <?php else: ?>
                        <li><a class="dropdown-item py-2 rounded-3" href="login.php">เข้าสู่ระบบ</a></li>
                        <li><a class="dropdown-item py-2 rounded-3" href="register.php">สมัครสมาชิก</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</nav>

<div class="bg-white py-5 border-bottom text-center">
    <div class="container">
        <h6 class="text-danger fw-bold text-uppercase mb-3" style="letter-spacing: 3px;">Explore Collection</h6>
        <form action="index.php" method="get" id="searchForm" class="search-container">
            <div class="search-input-group">
                <input type="text" name="q" id="search_input" placeholder="พิมพ์ชื่อสินค้าหรือแบรนด์..." value="<?php echo htmlspecialchars($search); ?>" autocomplete="off">
                <button type="submit" class="btn-search"><i class="fas fa-search"></i></button>
            </div>
            <div id="search_results"></div>
        </form>
    </div>
</div>

<div class="container my-5">
    <div class="row">
        <div class="col-lg-3 d-none d-lg-block">
            <div class="sticky-top" style="top: 110px;">
                <div class="cat-group">
                    <a href="index.php" class="filter-btn <?php echo ($cat == '') ? 'active' : ''; ?>">ทั้งหมด <i class="fas fa-th-large"></i></a>
                    
                    <div class="cat-header">รองเท้า (Shoes)</div>
                    <a href="index.php?cat=รองเท้าชาย" class="filter-btn <?php echo ($cat == 'รองเท้าชาย') ? 'active' : ''; ?>">รองเท้าผู้ชาย</a>
                    <a href="index.php?cat=รองเท้าหญิง" class="filter-btn <?php echo ($cat == 'รองเท้าหญิง') ? 'active' : ''; ?>">รองเท้าผู้หญิง</a>
                    <a href="index.php?cat=รองเท้าวิ่ง" class="filter-btn <?php echo ($cat == 'รองเท้าวิ่ง') ? 'active' : ''; ?>">รองเท้าวิ่ง</a>

                    <div class="cat-header">เสื้อผ้า (Apparel)</div>
                    <a href="index.php?cat=เสื้อผ้าชาย" class="filter-btn <?php echo ($cat == 'เสื้อผ้าชาย') ? 'active' : ''; ?>">เสื้อผ้าผู้ชาย</a>
                    <a href="index.php?cat=เสื้อผ้าหญิง" class="filter-btn <?php echo ($cat == 'เสื้อผ้าหญิง') ? 'active' : ''; ?>">เสื้อผ้าผู้หญิง</a>
                    
                    <div class="cat-header">อื่นๆ</div>
                    <a href="index.php?cat=อุปกรณ์กีฬา" class="filter-btn <?php echo ($cat == 'อุปกรณ์กีฬา') ? 'active' : ''; ?>">อุปกรณ์เสริม</a>
                </div>
            </div>
        </div>

        <div class="col-lg-9">
            <h2 class="fw-bold mb-4"><?php echo ($cat != '') ? $cat : 'สินค้าแนะนำ'; ?></h2>
            <div class="row g-4">
                <?php
                $sql = "SELECT * FROM products WHERE 1";
                if ($search) { $sql .= " AND (p_name LIKE '%$search%' OR p_brand LIKE '%$search%')"; }
                if ($cat) { $sql .= " AND p_category LIKE '%$cat%'"; }
                $sql .= " ORDER BY p_id DESC";
                $result = mysqli_query($conn, $sql);
                
                while($row = mysqli_fetch_array($result)) {
                    // Logic Tag สีตามประเภท ชาย/หญิง
                    $p_cat = $row['p_category'];
                    $tag_color = "tag-unisex";
                    if (strpos($p_cat, 'ชาย') !== false) $tag_color = "tag-men";
                    if (strpos($p_cat, 'หญิง') !== false) $tag_color = "tag-women";
                ?>
                    <div class="col-6 col-md-4">
                        <div class="card product-card">
                            <span class="category-tag <?php echo $tag_color; ?>"><?php echo $p_cat; ?></span>
                            
                            <div class="product-img-wrapper">
                                <a href="product_detail.php?id=<?php echo $row['p_id']; ?>">
                                    <img src="<?php echo $row['p_image']; ?>" class="product-img" onerror="this.src='https://placehold.co/400x400'">
                                </a>
                            </div>
                            <div class="card-body p-4 pt-0">
                                <div class="text-muted small fw-bold text-uppercase"><?php echo $row['p_brand']; ?></div>
                                <h5 class="fw-bold my-1" style="font-size:0.9rem; height:40px; overflow:hidden;"><?php echo $row['p_name']; ?></h5>
                                <div class="price-tag">฿<?php echo number_format($row['p_price']); ?></div>
                                <button class="btn btn-add-cart add-to-cart-btn" data-id="<?php echo $row['p_id']; ?>">+ ใส่ตะกร้า</button>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>
</div>

<footer class="py-5 text-center text-white" style="background:#111;">
    <h4 class="fw-bold">SUPER<span class="text-danger">WORLDS</span></h4>
    <p class="small opacity-50">© 2026 SUPERWORLDS STORE. QUALITY GEAR.</p>
</footer>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function(){
    // คืนค่าระบบ Live Search
    function performSearch(query) {
        if(query.length < 1) { $('#search_results').fadeOut(); return; }
        $.ajax({
            url: "fetch_search.php",
            method: "POST",
            data: { query: query },
            success: function(data) {
                $('#search_results').fadeIn().html(data);
            }
        });
    }
    $('#search_input').on('keyup focus', function(){ performSearch($(this).val()); });
    $(document).click(function(e) { if (!$(e.target).closest('#searchForm').length) $('#search_results').fadeOut(); });

    // AJAX Add to Cart (คงความสามารถเดิม)
    $('.add-to-cart-btn').click(function() {
        const p_id = $(this).data('id');
        $.ajax({
            url: 'cart_action.php', type: 'GET', data: { id: p_id, action: 'add', ajax: 1 },
            success: function(res) {
                Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'เพิ่มลงตะกร้าแล้ว', showConfirmButton: false, timer: 1500 });
                $('#cart-badge').text(res);
            }
        });
    });
});
</script>
</body>
</html>
