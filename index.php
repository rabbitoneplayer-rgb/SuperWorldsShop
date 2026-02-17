<?php 
session_start(); 
include_once("connectdb.php"); 

$cat = isset($_GET['cat']) ? $_GET['cat'] : '';
$search = isset($_GET['q']) ? $_GET['q'] : '';

// ส่วนนับจำนวนสินค้าในตะกร้า
$cart_count = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $qty) {
        $cart_count += $qty;
    }
}

// เช็คสถานะการล็อกอินและแอดมิน
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root { --ss-red: #e12128; --ss-dark: #111111; --ss-gray: #f8f9fa; }
        body { font-family: 'Segoe UI', Roboto, sans-serif; background-color: var(--ss-gray); color: #333; margin: 0; padding: 0; }
        
        /* --- Floating Admin Sidebar --- */
        .admin-sidebar { 
            position: fixed; left: 20px; top: 50%; transform: translateY(-50%); 
            background: rgba(0,0,0,0.9); backdrop-filter: blur(10px);
            padding: 15px 10px; border-radius: 20px; z-index: 9999;
            display: flex; flex-direction: column; gap: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5); border: 1px solid rgba(255,255,255,0.1);
            transition: 0.3s;
        }
        .admin-sidebar:hover { left: 25px; }
        .admin-btn { 
            width: 45px; height: 45px; display: flex; align-items: center; justify-content: center;
            color: #fff; text-decoration: none; border-radius: 12px; transition: 0.3s;
            position: relative;
        }
        .admin-btn:hover { background: var(--ss-red); color: #fff; transform: scale(1.1); }
        .admin-tooltip {
            position: absolute; left: 60px; background: #000; color: #fff; padding: 5px 12px;
            border-radius: 8px; font-size: 0.75rem; white-space: nowrap; visibility: hidden; opacity: 0;
            transition: 0.3s; pointer-events: none;
        }
        .admin-btn:hover .admin-tooltip { visibility: visible; opacity: 1; left: 55px; }

        /* --- Navigation --- */
        .navbar { background-color: var(--ss-dark) !important; padding: 15px 0; }
        .navbar-brand { font-size: 1.8rem; letter-spacing: -1.5px; font-weight: 800; }

        /* --- Search Results Layout --- */
        .search-container { max-width: 650px; margin: 0 auto; position: relative; }
        .search-input-group { background: white; border-radius: 50px; padding: 6px 6px 6px 25px; display: flex; align-items: center; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        .search-input-group input { border: none; outline: none; flex: 1; font-size: 1rem; }
        .search-input-group .btn-search { background: var(--ss-dark); color: white; border-radius: 50px; padding: 10px 25px; border: none; transition: 0.3s; }

        #search_results { 
            width: 850px !important; left: 50% !important; transform: translateX(-50%); 
            display: none; background: white; z-index: 9990; box-shadow: 0 25px 60px rgba(0,0,0,0.4); 
            position: absolute; margin-top: 15px; border-radius: 20px; overflow: hidden;
            flex-direction: row !important;
        }

        .search-sidebar {
            width: 260px !important; min-width: 260px !important;
            padding: 30px !important; background: #f9f9f9; border-right: 1px solid #eee;
            display: flex !important; flex-direction: column !important; gap: 20px;
        }
        
        .trending-wrapper { display: flex !important; flex-direction: column !important; gap: 8px; }
        .trending-item { 
            font-size: 0.85rem !important; color: #444; text-decoration: none; 
            padding: 8px 12px; border-radius: 8px; transition: 0.2s; background: #fff; border: 1px solid #f0f0f0;
        }
        .trending-item:hover { color: var(--ss-red); background: #fff5f5; border-color: #ffcccc; transform: translateX(5px); }

        .price-filter-wrapper { display: flex; flex-direction: column; gap: 10px; }
        .price-input-row { display: flex; align-items: center; gap: 8px; }
        .p-input { width: 100%; border: 1px solid #ddd; border-radius: 10px; padding: 8px; font-size: 0.8rem; outline: none; }
        .btn-apply { 
            width: 100%; background: #111; color: #fff; border: none; padding: 10px; 
            border-radius: 10px; font-weight: 700; font-size: 0.8rem; transition: 0.3s;
        }
        .btn-apply:hover { background: var(--ss-red); }

        .search-content { flex: 1 !important; padding: 30px !important; background: #fff; overflow-y: auto !important; max-height: 550px !important; }
        .search-grid { display: grid !important; grid-template-columns: 1fr 1fr !important; gap: 15px !important; }
        .search-item-card { 
            display: flex !important; align-items: center !important; padding: 12px !important; 
            border-radius: 15px; border: 1px solid #f0f0f0; text-decoration: none !important; 
            color: inherit; transition: 0.2s; height: 100px !important; 
        }
        .search-item-card:hover { background: #fff5f5; border-color: #ffcccc; transform: translateY(-2px); }

        .item-img {
            width: 70px !important; height: 70px !important; min-width: 70px !important;
            margin-right: 15px; background: #fff; display: flex !important;
            align-items: center; justify-content: center; border-radius: 10px; overflow: hidden; border: 1px solid #eee;
        }
        .item-img img { max-width: 90% !important; max-height: 90% !important; object-fit: contain !important; }
        .item-info { overflow: hidden; display: flex; flex-direction: column; }
        .item-brand { font-size: 0.65rem; color: #bbb; text-transform: uppercase; font-weight: 800; letter-spacing: 1px; }
        .item-name { font-size: 0.85rem !important; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .item-price { font-size: 0.9rem !important; color: var(--ss-red); font-weight: 800; }

        .category-filter-wrapper { display: flex; justify-content: center; gap: 12px; margin-top: 20px; flex-wrap: wrap; }
        .filter-btn { border: 1px solid rgba(255,255,255,0.2); background: rgba(255,255,255,0.05); color: rgba(255,255,255,0.8); padding: 8px 24px; border-radius: 50px; font-size: 0.85rem; text-decoration: none; transition: 0.3s; }
        .filter-btn:hover, .filter-btn.active { background: #fff; color: #000; font-weight: 700; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }

        .product-card { border: none; transition: 0.4s; border-radius: 20px; overflow: hidden; background: #fff; height: 100%; }
        .product-card:hover { transform: translateY(-12px); box-shadow: 0 20px 40px rgba(0,0,0,0.08); }
        .product-img-wrapper { background: #fff; padding: 25px; display: flex; align-items: center; justify-content: center; height: 250px; overflow: hidden; }
        .product-img { max-height: 100%; max-width: 100%; object-fit: contain; }
        .price-tag { color: var(--ss-red); font-weight: 800; font-size: 1.3rem; margin: 15px 0; }
        .btn-add-cart { background: var(--ss-dark); color: white; border-radius: 12px; padding: 12px; font-weight: 700; width: 100%; border: none; transition: 0.3s; }
        .btn-add-cart:hover { background: var(--ss-red); box-shadow: 0 8px 20px rgba(225, 33, 40, 0.3); }

        .premium-toast-popup { border-radius: 15px !important; box-shadow: 0 15px 35px rgba(0,0,0,0.1) !important; }
        .premium-toast-title { font-family: 'Segoe UI', sans-serif !important; font-size: 0.95rem !important; font-weight: 600; color: #333; }

        footer { background: var(--ss-dark) !important; color: #888; padding: 60px 0; margin-top: 80px; }
    </style>
</head>
<body>

<?php if($is_admin): ?>
<div class="admin-sidebar shadow">
    <a href="admin_products.php" class="admin-btn">
        <i class="fas fa-boxes-stacked"></i>
        <span class="admin-tooltip">จัดการสต็อกสินค้า</span>
    </a>
    <a href="admin_orders.php" class="admin-btn">
        <i class="fas fa-file-invoice-dollar"></i>
        <span class="admin-tooltip">ดูรายการสั่งซื้อ</span>
    </a>
    <a href="admin_customers.php" class="admin-btn">
        <i class="fas fa-users"></i>
        <span class="admin-tooltip">จัดการข้อมูลลูกค้า</span>
    </a>
    <div style="border-top: 1px solid rgba(255,255,255,0.1); margin: 5px 0;"></div>
    <div class="admin-btn" style="cursor: default; background: rgba(255,255,255,0.05);">
        <i class="fas fa-shield-halved" style="color: #ffc107;"></i>
        <span class="admin-tooltip">โหมดแอดมิน</span>
    </div>
</div>
<?php endif; ?>

<nav class="navbar navbar-expand-lg navbar-dark sticky-top shadow-sm">
    <div class="container">
        <a class="navbar-brand" href="index.php">SUPER<span class="text-danger">WORLDS</span></a>
        
        <div class="flex-grow-1 mx-lg-5">
            <form action="index.php" method="get" id="searchForm" class="search-container">
                <div class="search-input-group">
                    <input type="text" name="q" id="search_input" placeholder="ค้นหาแบรนด์หรือสินค้าที่คุณต้องการ..." value="<?php echo htmlspecialchars($search); ?>" autocomplete="off">
                    <button type="submit" class="btn-search"><i class="fas fa-search"></i></button>
                </div>
                <div class="category-filter-wrapper">
                    <a href="index.php?q=<?php echo $search; ?>" class="filter-btn <?php echo ($cat == '') ? 'active' : ''; ?>">ทั้งหมด</a>
                    <a href="index.php?q=<?php echo $search; ?>&cat=รองเท้า" class="filter-btn <?php echo ($cat == 'รองเท้า') ? 'active' : ''; ?>">รองเท้า</a>
                    <a href="index.php?q=<?php echo $search; ?>&cat=เสื้อผ้า" class="filter-btn <?php echo ($cat == 'เสื้อผ้า') ? 'active' : ''; ?>">เสื้อผ้า</a>
                    <a href="index.php?q=<?php echo $search; ?>&cat=อุปกรณ์กีฬา" class="filter-btn <?php echo ($cat == 'อุปกรณ์กีฬา') ? 'active' : ''; ?>">อุปกรณ์กีฬา</a>
                </div>
                <div id="search_results"></div>
            </form>
        </div>

        <div class="d-flex align-items-center gap-4">
            <a href="cart.php" class="text-white position-relative text-decoration-none p-2">
                <i class="fas fa-shopping-bag fa-lg"></i>
                <span id="cart-badge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"><?php echo $cart_count; ?></span>
            </a>
            <div class="dropdown">
                <a href="#" class="text-white text-decoration-none dropdown-toggle d-flex align-items-center bg-white bg-opacity-10 py-2 px-3 rounded-pill" data-bs-toggle="dropdown">
                    <i class="fas fa-user-circle me-2"></i>
                    <span class="small fw-bold text-uppercase"><?php echo isset($_SESSION['fullname']) ? explode(' ', $_SESSION['fullname'])[0] : 'LOGIN'; ?></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 mt-3 p-2 rounded-4">
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <li class="px-3 py-2 small fw-bold text-muted border-bottom mb-1">สวัสดี, <?php echo $_SESSION['fullname']; ?></li>
                        <li><a class="dropdown-item py-2 rounded-3" href="profile.php"><i class="fas fa-user-edit me-2 opacity-50"></i>ข้อมูลส่วนตัว</a></li>
                        <li><a class="dropdown-item py-2 rounded-3" href="order_history.php"><i class="fas fa-history me-2 opacity-50"></i>ประวัติการซื้อ</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item py-2 rounded-3 text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>ออกจากระบบ</a></li>
                    <?php else: ?>
                        <li><a class="dropdown-item py-2 rounded-3" href="login.php">เข้าสู่ระบบ</a></li>
                        <li><a class="dropdown-item py-2 rounded-3" href="register.php">สมัครสมาชิก</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</nav>

<div class="container my-5 pt-4">
    <div class="mb-4 text-center text-lg-start">
        <h6 class="text-danger fw-bold text-uppercase mb-1" style="letter-spacing: 2px;">Trending Now</h6>
        <h2 class="fw-bold m-0"><?php echo ($cat != '') ? 'หมวดหมู่: ' . $cat : 'สินค้าแนะนำสำหรับคุณ'; ?></h2>
    </div>

    <div class="row g-4">
        <?php
        $sql = "SELECT * FROM products WHERE 1";
        if ($search) { $sql .= " AND (p_name LIKE '%$search%' OR p_brand LIKE '%$search%')"; }
        if ($cat) { $sql .= " AND p_category = '$cat'"; }
        $sql .= " ORDER BY p_id DESC";
        $result = mysqli_query($conn, $sql);
        if(mysqli_num_rows($result) > 0) {
            while($row = mysqli_fetch_array($result)) {
        ?>
            <div class="col-6 col-md-4 col-lg-3">
                <div class="card product-card shadow-sm">
                    <div class="product-img-wrapper">
                        <a href="product_detail.php?id=<?php echo $row['p_id']; ?>">
                            <img src="<?php echo $row['p_image']; ?>" class="product-img" onerror="this.src='https://placehold.co/400x400?text=No+Image'">
                        </a>
                    </div>
                    <div class="card-body p-4 pt-0 text-center text-lg-start">
                        <div class="text-muted small fw-bold text-uppercase" style="letter-spacing:1px;"><?php echo $row['p_brand']; ?></div>
                        <h5 class="product-title" style="font-size:0.9rem; font-weight:700; height:40px; overflow:hidden;"><?php echo $row['p_name']; ?></h5>
                        <div class="price-tag">฿<?php echo number_format($row['p_price']); ?></div>
                        <button type="button" class="btn btn-add-cart add-to-cart-btn" data-id="<?php echo $row['p_id']; ?>">
                            <i class="fas fa-cart-plus me-2"></i> หยิบใส่ตะกร้า
                        </button>
                    </div>
                </div>
            </div>
        <?php 
            }
        } else {
            echo '<div class="col-12 text-center py-5 opacity-50"><h4>ไม่พบสินค้าที่คุณค้นหา</h4></div>';
        }
        ?>
    </div>
</div>

<footer class="text-center">
    <div class="container">
        <h5 class="mb-4 text-white">SUPER<span class="text-danger">WORLDS</span></h5>
        <p class="small opacity-50 m-0">© 2026 Super Worlds Store. All Rights Reserved.</p>
    </div>
</footer>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function(){
    const isLoggedIn = <?php echo $is_logged_in ? 'true' : 'false'; ?>;

    function performSearch(query) {
        var min = $('#min_price').val();
        var max = $('#max_price').val();
        $.ajax({
            url: "fetch_search.php",
            method: "POST",
            data: { query: query, min_price: min, max_price: max },
            success: function(data) {
                $('#search_results').stop(true, true).fadeIn(200).css('display', 'flex').html(data);
            }
        });
    }

    $('#search_input').on('focus keyup', function(){ performSearch($(this).val()); });

    $(document).on('click', '#btn_apply_price', function(e){
        e.stopPropagation();
        performSearch($('#search_input').val());
    });

    $(document).click(function(e) {
        if (!$(e.target).closest('#searchForm').length) {
            $('#search_results').fadeOut(200);
        }
    });

    $('.add-to-cart-btn').click(function(e) {
        e.preventDefault();
        if (!isLoggedIn) {
            Swal.fire({
                title: 'กรุณาเข้าสู่ระบบ',
                text: "คุณต้องล็อกอินก่อนจึงจะสามารถเพิ่มสินค้าลงตะกร้าได้",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#111',
                confirmButtonText: 'ไปหน้าล็อกอิน',
                cancelButtonText: 'ไว้ทีหลัง'
            }).then((result) => { if (result.isConfirmed) window.location.href = 'login.php'; });
            return;
        }

        const btn = $(this);
        const p_id = btn.data('id');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
        
        $.ajax({
            url: 'cart_action.php', type: 'GET', data: { id: p_id, action: 'add', ajax: 1 },
            success: function(res) {
                const Toast = Swal.mixin({
                    toast: true, position: 'top-end', showConfirmButton: false,
                    timer: 2000, timerProgressBar: true, background: '#ffffff',
                    iconColor: '#28a745',
                    customClass: { popup: 'premium-toast-popup', title: 'premium-toast-title' }
                });
                Toast.fire({ icon: 'success', title: 'เพิ่มลงตะกร้าเรียบร้อยแล้ว' });
                $('#cart-badge').text(res).addClass('bg-danger');
                btn.prop('disabled', false).html('<i class="fas fa-cart-plus me-2"></i> หยิบใส่ตะกร้า');
            }
        });
    });
});
</script>
</body>
</html>