<?php
session_start();
include_once("connectdb.php");
?>
<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ตะกร้าสินค้า - SUPERWORLDS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root { --ss-red: #e12128; --ss-dark: #111111; --ss-gray: #f8f9fa; }
        body { background-color: var(--ss-gray); font-family: 'Segoe UI', Tahoma, sans-serif; color: #333; }
        
        .cart-container { max-width: 1000px; margin-top: 50px; }
        .cart-card { border: none; border-radius: 24px; box-shadow: 0 15px 40px rgba(0,0,0,0.04); background: #fff; overflow: hidden; }
        
        .table thead { background-color: #fff; border-bottom: 2px solid #f1f1f1; }
        .table thead th { font-weight: 700; text-transform: uppercase; font-size: 0.85rem; letter-spacing: 1px; color: #888; border: none; padding: 20px; }
        .table tbody td { padding: 25px 20px; border-bottom: 1px solid #f1f1f1; vertical-align: middle; }
        
        .product-img { width: 80px; height: 80px; object-fit: contain; border-radius: 12px; background: #fdfdfd; padding: 5px; }
        .product-name { font-size: 1.05rem; font-weight: 700; color: var(--ss-dark); text-decoration: none; display: block; }
        
        .qty-control { display: flex; align-items: center; background: #f4f4f4; border-radius: 50px; padding: 5px; width: fit-content; margin: 0 auto; }
        .qty-btn { width: 32px; height: 32px; border-radius: 50%; border: none; background: #fff; color: var(--ss-dark); font-weight: bold; display: flex; align-items: center; justify-content: center; text-decoration: none; transition: 0.3s; }
        .qty-btn:hover { background: var(--ss-dark); color: #fff; }
        .qty-number { width: 40px; text-align: center; font-weight: 700; }

        .total-row { background-color: #fcfcfc; padding: 30px; border-top: 2px solid #f1f1f1; }
        .btn-checkout { background: var(--ss-dark); color: #fff; border-radius: 50px; padding: 16px 40px; font-weight: 700; border: none; transition: 0.4s; }
        .btn-checkout:hover { background: #000; transform: translateY(-3px); color: #fff; }
        
        /* สไตล์ปุ่มลบสินค้าแบบไอคอน */
        .btn-remove-item { color: #ccc; transition: 0.3s; border: none; background: none; font-size: 1.2rem; }
        .btn-remove-item:hover { color: var(--ss-red); }
    </style>
</head>
<body>

<div class="container cart-container mb-5">
    <div class="d-flex justify-content-between align-items-end mb-4">
        <div>
            <h6 class="text-danger fw-bold text-uppercase mb-1" style="letter-spacing: 2px;">Your Selection</h6>
            <h2 class="fw-bold m-0">ตะกร้าสินค้า</h2>
        </div>
        <a href="index.php" class="text-decoration-none text-muted fw-bold small">
            <i class="fas fa-chevron-left me-1"></i> เลือกซื้อสินค้าต่อ
        </a>
    </div>

    <div class="card cart-card">
        <?php
        $total_price = 0;
        if (!empty($_SESSION['cart'])): 
        ?>
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>สินค้า</th>
                        <th class="text-center">ราคา</th>
                        <th class="text-center">จำนวน</th>
                        <th class="text-center">รวม</th>
                        <th class="text-end"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    foreach ($_SESSION['cart'] as $id => $qty) {
                        $sql = "SELECT * FROM products WHERE p_id = '$id'";
                        $res = mysqli_query($conn, $sql);
                        $row = mysqli_fetch_array($res);
                        $sum = $row['p_price'] * $qty;
                        $total_price += $sum;
                    ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <img src="<?php echo $row['p_image']; ?>" class="product-img me-3" onerror="this.src='https://placehold.co/100x100?text=No+Image'">
                                <div>
                                    <a href="product_detail.php?id=<?php echo $id; ?>" class="product-name"><?php echo $row['p_name']; ?></a>
                                    <small class="text-muted text-uppercase fw-bold" style="font-size: 10px;"><?php echo $row['p_brand']; ?></small>
                                </div>
                            </div>
                        </td>
                        <td class="text-center">
                            <span class="fw-bold">฿<?php echo number_format($row['p_price']); ?></span>
                        </td>
                        <td class="text-center">
                            <div class="qty-control">
                                <a href="cart_action.php?id=<?php echo $id; ?>&action=reduce" class="qty-btn text-decoration-none">-</a>
                                <span class="qty-number"><?php echo $qty; ?></span>
                                <a href="cart_action.php?id=<?php echo $id; ?>&action=add_more" class="qty-btn text-decoration-none">+</a>
                            </div>
                        </td>
                        <td class="text-center">
                            <span class="fw-bold text-danger">฿<?php echo number_format($sum); ?></span>
                        </td>
                        <td class="text-end">
                            <button type="button" class="btn-remove-item" onclick="confirmDelete('<?php echo $id; ?>')">
                                <i class="far fa-trash-can"></i>
                            </button>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>

        <div class="total-row">
            <div class="row align-items-center">
                <div class="col-md-6 mb-4 mb-md-0">
                    <button type="button" class="btn btn-link text-muted small text-decoration-none fw-bold p-0 border-0" onclick="confirmClear()">
                        <i class="fas fa-trash-alt me-2"></i> ล้างตะกร้าทั้งหมด
                    </button>
                </div>
                <div class="col-md-6 text-md-end">
                    <div class="mb-4">
                        <span class="text-muted fw-bold me-3">ยอดรวมทั้งสิ้น</span>
                        <span class="display-6 fw-bold text-dark">฿<?php echo number_format($total_price); ?></span>
                    </div>
                    <a href="checkout.php" class="btn btn-checkout shadow-lg w-100 w-md-auto">
                        ไปหน้าชำระเงิน <i class="fas fa-arrow-right ms-2"></i>
                    </a>
                </div>
            </div>
        </div>

        <?php else: ?>
        <div class="empty-cart-section text-center py-5">
            <div class="empty-icon-wrapper mb-3" style="font-size: 5rem; color: #eee;">
                <i class="fas fa-shopping-bag"></i>
            </div>
            <h3 class="fw-bold mb-2">ตะกร้าของคุณว่างเปล่า</h3>
            <p class="text-muted mb-5">ลองไปเลือกชมสินค้าใหม่ล่าสุดของเราดูไหม?</p>
            <a href="index.php" class="btn btn-dark btn-lg rounded-pill px-5 shadow">เริ่มเลือกซื้อสินค้า</a>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
    // ฟังก์ชันยืนยันการลบสินค้าทีละชิ้น
    function confirmDelete(productId) {
        Swal.fire({
            title: 'ยืนยันการลบสินค้า?',
            text: "คุณต้องการนำสินค้านี้ออกจากตะกร้าใช่หรือไม่?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#111',
            cancelButtonColor: '#eee',
            confirmButtonText: 'ใช่, ลบเลย',
            cancelButtonText: 'ยกเลิก',
            reverseButtons: true, // สลับตำแหน่งปุ่มให้ดูทันสมัย
            customClass: {
                confirmButton: 'rounded-pill px-4',
                cancelButton: 'rounded-pill px-4 text-dark'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'cart_action.php?id=' + productId + '&action=remove';
            }
        })
    }

    // ฟังก์ชันยืนยันการล้างตะกร้าทั้งหมด
    function confirmClear() {
        Swal.fire({
            title: 'ล้างตะกร้าสินค้าทั้งหมด?',
            text: "ข้อมูลสินค้าทั้งหมดในตะกร้าจะถูกลบออก",
            icon: 'error',
            showCancelButton: true,
            confirmButtonColor: '#e12128', // สีแดงเพื่อให้ดูเป็นการแจ้งเตือนสำคัญ
            cancelButtonColor: '#eee',
            confirmButtonText: 'ยืนยันการล้างตะกร้า',
            cancelButtonText: 'ยกเลิก',
            reverseButtons: true,
            customClass: {
                confirmButton: 'rounded-pill px-4',
                cancelButton: 'rounded-pill px-4 text-dark'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'cart_action.php?action=clear';
            }
        })
    }
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>