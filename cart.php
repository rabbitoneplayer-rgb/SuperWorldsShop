<?php
session_start();
include_once("connectdb.php");
?>
<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>รถเข็นของฉัน | SUPERWORLDS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root { --ss-red: #e12128; --ss-dark: #111111; --ss-gray: #f8f9fa; --ss-border: #eee; }
        body { background-color: var(--ss-gray); font-family: 'Kanit', sans-serif; color: #333; }
        
        /* Layout */
        .cart-container { max-width: 1100px; margin-top: 60px; padding-bottom: 100px; }
        
        /* Card & Table */
        .cart-card { border: none; border-radius: 30px; box-shadow: 0 20px 60px rgba(0,0,0,0.05); background: #fff; overflow: hidden; }
        .table thead { background-color: #fff; border-bottom: 2px solid #f8f8f8; }
        .table thead th { font-weight: 700; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 2px; color: #bbb; border: none; padding: 25px 20px; }
        .table tbody td { padding: 30px 20px; border-bottom: 1px solid #f8f8f8; vertical-align: middle; }
        
        /* Product UI */
        .product-img-wrapper { width: 90px; height: 90px; background: var(--ss-gray); border-radius: 20px; padding: 10px; display: flex; align-items: center; justify-content: center; overflow: hidden; }
        .product-img { max-width: 100%; max-height: 100%; object-fit: contain; transition: 0.3s; }
        .product-name { font-size: 1.1rem; font-weight: 700; color: var(--ss-dark); text-decoration: none; display: block; line-height: 1.2; margin-bottom: 5px; }
        .product-name:hover { color: var(--ss-red); }
        
        /* Qty Controls */
        .qty-control { display: flex; align-items: center; background: var(--ss-gray); border-radius: 50px; padding: 6px; width: fit-content; border: 1px solid #f0f0f0; }
        .qty-btn { width: 35px; height: 35px; border-radius: 50%; border: none; background: #fff; color: var(--ss-dark); font-weight: 800; display: flex; align-items: center; justify-content: center; text-decoration: none; transition: 0.3s; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .qty-btn:hover { background: var(--ss-dark); color: #fff; transform: scale(1.1); }
        .qty-number { width: 45px; text-align: center; font-weight: 800; font-size: 1rem; }

        /* Summary Section */
        .summary-box { background: #fff; border-radius: 30px; padding: 40px; box-shadow: 0 20px 60px rgba(0,0,0,0.05); }
        .total-price { font-size: 2.5rem; font-weight: 800; color: var(--ss-dark); letter-spacing: -1px; }
        
        /* Buttons */
        .btn-checkout { background: var(--ss-dark); color: #fff; border-radius: 20px; padding: 20px 40px; font-weight: 800; border: none; transition: 0.4s; font-size: 1.1rem; text-transform: uppercase; letter-spacing: 1px; width: 100%; display: flex; align-items: center; justify-content: center; gap: 15px; }
        .btn-checkout:hover { background: var(--ss-red); transform: translateY(-5px); box-shadow: 0 15px 30px rgba(225, 33, 40, 0.25); color: #fff; }
        
        .btn-remove-item { color: #ddd; transition: 0.3s; border: none; background: none; font-size: 1.3rem; }
        .btn-remove-item:hover { color: var(--ss-red); transform: rotate(90deg); }

        /* Empty State */
        .empty-cart { padding: 100px 20px; text-align: center; }
        .empty-icon { font-size: 6rem; background: linear-gradient(135deg, #eee 0%, #f9f9f9 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin-bottom: 20px; }
    </style>
</head>
<body>

<div class="container cart-container">
    <div class="row align-items-end mb-5 px-3 px-md-0">
        <div class="col-8">
            <h6 class="text-danger fw-bold text-uppercase mb-2" style="letter-spacing: 4px;">Shopping Bag</h6>
            <h1 class="fw-bold m-0" style="font-size: 2.5rem;">รถเข็นของฉัน</h1>
        </div>
        <div class="col-4 text-end">
            <a href="index.php" class="text-decoration-none text-muted fw-bold small transition-all hover-red">
                <i class="fas fa-arrow-left me-2"></i> ช้อปต่อ
            </a>
        </div>
    </div>

    <?php if (!empty($_SESSION['cart'])): ?>
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card cart-card shadow-sm">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>รายการสินค้า</th>
                                <th class="text-center">ราคา</th>
                                <th class="text-center">จำนวน</th>
                                <th class="text-center">รวม</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $total_price = 0;
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
                                        <div class="product-img-wrapper me-4">
                                            <img src="<?php echo $row['p_image']; ?>" class="product-img" onerror="this.src='https://placehold.co/100x100?text=Order'">
                                        </div>
                                        <div>
                                            <a href="product_detail.php?id=<?php echo $id; ?>" class="product-name"><?php echo $row['p_name']; ?></a>
                                            <span class="badge bg-light text-dark border fw-normal"><?php echo $row['p_brand']; ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center fw-bold">฿<?php echo number_format($row['p_price']); ?></td>
                                <td class="text-center">
                                    <div class="qty-control mx-auto">
                                        <a href="cart_action.php?id=<?php echo $id; ?>&action=reduce" class="qty-btn text-decoration-none">-</a>
                                        <span class="qty-number"><?php echo $qty; ?></span>
                                        <a href="cart_action.php?id=<?php echo $id; ?>&action=add_more" class="qty-btn text-decoration-none">+</a>
                                    </div>
                                </td>
                                <td class="text-center fw-bold text-dark">฿<?php echo number_format($sum); ?></td>
                                <td class="text-end">
                                    <button type="button" class="btn-remove-item" onclick="confirmDelete('<?php echo $id; ?>')">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="p-4 border-top text-start">
                    <button type="button" class="btn btn-link text-muted fw-bold text-decoration-none small p-0" onclick="confirmClear()">
                        <i class="far fa-trash-alt me-2"></i> ล้างรถเข็นทั้งหมด
                    </button>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="summary-box sticky-top" style="top: 100px;">
                <h5 class="fw-bold mb-4">สรุปคำสั่งซื้อ</h5>
                <div class="d-flex justify-content-between mb-3 text-muted">
                    <span>ราคารวมสินค้า</span>
                    <span>฿<?php echo number_format($total_price); ?></span>
                </div>
                <div class="d-flex justify-content-between mb-4 text-muted">
                    <span>ค่าจัดส่ง</span>
                    <span class="text-success">ฟรีค่าจัดส่ง</span>
                </div>
                <hr class="mb-4" style="opacity: 0.05;">
                <div class="d-flex justify-content-between align-items-center mb-5">
                    <span class="fw-bold h5 mb-0">ยอดชำระสุทธิ</span>
                    <span class="total-price">฿<?php echo number_format($total_price); ?></span>
                </div>
                
                <a href="checkout.php" class="btn btn-checkout shadow-lg">
                    สั่งซื้อสินค้า <i class="fas fa-arrow-right"></i>
                </a>
                
                <div class="mt-4 text-center">
                    <img src="https://cdn-icons-png.flaticon.com/512/349/349221.png" width="30" class="mx-1 opacity-50 grayscale" style="filter: grayscale(1);">
                    <img src="https://cdn-icons-png.flaticon.com/512/174/174861.png" width="30" class="mx-1 opacity-50">
                    <img src="https://cdn-icons-png.flaticon.com/512/5968/5968299.png" width="30" class="mx-1 opacity-50">
                    <p class="small text-muted mt-3 mb-0">ชำระเงินได้รวดเร็วและปลอดภัย</p>
                </div>
            </div>
        </div>
    </div>

    <?php else: ?>
    <div class="card cart-card border-0 shadow-sm empty-cart">
        <div class="empty-icon"><i class="fas fa-shopping-bag"></i></div>
        <h2 class="fw-bold mb-3">รถเข็นของคุณยังว่างอยู่</h2>
        <p class="text-muted mb-5">เลือกซื้อสินค้าที่น่าสนใจจาก SUPERWORLDS และเริ่มต้นประสบการณ์ใหม่ของคุณวันนี้</p>
        <a href="index.php" class="btn btn-dark rounded-pill px-5 py-3 fw-bold shadow">
            เริ่มช้อปปิ้งกันเลย
        </a>
    </div>
    <?php endif; ?>
</div>

<script>
    function confirmDelete(productId) {
        Swal.fire({
            title: 'นำสินค้าออก?',
            text: "คุณต้องการนำรายการนี้ออกจากรถเข็นใช่หรือไม่",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#111',
            cancelButtonColor: '#f8f9fa',
            confirmButtonText: 'ใช่, นำออกเลย',
            cancelButtonText: 'ยกเลิก',
            reverseButtons: true,
            customClass: { confirmButton: 'rounded-pill px-4 py-2', cancelButton: 'rounded-pill px-4 py-2 text-dark' }
        }).then((result) => {
            if (result.isConfirmed) window.location.href = 'cart_action.php?id=' + productId + '&action=remove';
        })
    }

    function confirmClear() {
        Swal.fire({
            title: 'ล้างตะกร้าสินค้า?',
            text: "ข้อมูลสินค้าทั้งหมดในตะกร้าจะถูกลบออกถาวร",
            icon: 'error',
            showCancelButton: true,
            confirmButtonColor: '#e12128',
            cancelButtonColor: '#f8f9fa',
            confirmButtonText: 'ล้างข้อมูลทั้งหมด',
            cancelButtonText: 'ยกเลิก',
            customClass: { confirmButton: 'rounded-pill px-4 py-2', cancelButton: 'rounded-pill px-4 py-2 text-dark' }
        }).then((result) => {
            if (result.isConfirmed) window.location.href = 'cart_action.php?action=clear';
        })
    }
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
