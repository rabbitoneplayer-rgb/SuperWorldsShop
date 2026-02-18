<?php
session_start();
include_once("connectdb.php");

if (empty($_SESSION['cart'])) {
    header("Location: index.php");
    exit();
}

$uid = $_SESSION['user_id'] ?? 0;
$res_user = mysqli_query($conn, "SELECT * FROM users WHERE id = '$uid'");
$user = mysqli_fetch_array($res_user);

$grand_total = 0;
foreach ($_SESSION['cart'] as $id => $qty) {
    $res = mysqli_query($conn, "SELECT p_price FROM products WHERE p_id = '$id'");
    $row = mysqli_fetch_array($res);
    $grand_total += ($row['p_price'] * $qty);
}

// ตั้งค่าเบอร์ PromptPay ของร้านคุณที่นี่
$pp_id = "0812345678"; // <--- เปลี่ยนเป็นเบอร์พร้อมเพย์หรือเลขบัตรประชาชนของคุณ
$qr_url = "https://promptpay.io/$pp_id/$grand_total.png";
?>
<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ชำระเงิน - SUPERWORLDS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root { --ss-red: #e12128; --ss-dark: #111111; }
        body { background-color: #f4f7f6; font-family: 'Kanit', sans-serif; color: #333; }
        
        .checkout-card { border: none; border-radius: 30px; box-shadow: 0 10px 40px rgba(0,0,0,0.05); background: #fff; }
        .summary-card { border: none; border-radius: 30px; background: #fff; position: sticky; top: 100px; }
        
        .form-label { font-weight: 700; font-size: 0.85rem; color: #888; text-transform: uppercase; letter-spacing: 1px; }
        .form-control { border: 1.5px solid #eee; padding: 14px 18px; border-radius: 15px; transition: 0.3s; background-color: #fcfcfc; }
        .form-control:focus { border-color: var(--ss-red); box-shadow: none; background-color: #fff; }
        
        /* Payment Method Option */
        .payment-option { border: 2px solid #eee; border-radius: 20px; padding: 20px; cursor: pointer; transition: 0.3s; position: relative; margin-bottom: 15px; }
        .payment-option:hover { border-color: #ddd; }
        .payment-option.active { border-color: var(--ss-red); background-color: #fff5f5; }
        .payment-option input[type="radio"] { position: absolute; opacity: 0; }
        
        /* QR Code Styling */
        #qr_container { display: block; text-align: center; background: white; padding: 25px; border-radius: 20px; border: 1px solid #eee; margin-top: 15px; animation: fadeIn 0.5s; }
        .qr-img { width: 220px; height: 220px; border: 5px solid #fff; box-shadow: 0 5px 20px rgba(0,0,0,0.1); border-radius: 15px; }
        
        .btn-confirm { background: var(--ss-dark); color: white; border-radius: 50px; padding: 20px; font-weight: 800; border: none; transition: 0.4s; letter-spacing: 1px; font-size: 1.1rem; }
        .btn-confirm:hover { background: var(--ss-red); transform: translateY(-5px); box-shadow: 0 15px 30px rgba(225,33,40,0.3); color: white; }
        
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        .error-hint { font-size: 0.8rem; color: var(--ss-red); display: none; margin-top: 6px; font-weight: 600; }
    </style>
</head>
<body class="py-5">

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-5">
        <a href="cart.php" class="text-decoration-none text-muted fw-bold small">
            <i class="fas fa-arrow-left me-2"></i> กลับไปยังตะกร้า
        </a>
        <h2 class="fw-bold m-0">ยืนยันการสั่งซื้อ</h2>
        <div style="width: 100px;"></div> 
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card checkout-card p-4 p-md-5">
                <form action="save_order.php" method="POST" id="checkoutForm">
                    <div class="d-flex align-items-center mb-4">
                        <div class="bg-dark text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 35px; height: 35px; font-weight: 800;">1</div>
                        <h4 class="fw-bold m-0">ข้อมูลการจัดส่ง</h4>
                    </div>

                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label">ชื่อ-นามสกุล ผู้รับ</label>
                            <input type="text" id="fullname" name="fullname" class="form-control" 
                                   value="<?php echo htmlspecialchars($user['fullname'] ?? ''); ?>" 
                                   placeholder="ภาษาไทย หรือ อังกฤษ" required>
                            <div id="nameError" class="error-hint">กรุณากรอกชื่อให้ถูกต้อง</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">เบอร์โทรศัพท์</label>
                            <input type="text" id="phone" name="phone" class="form-control" 
                                   value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" 
                                   placeholder="08XXXXXXXX" maxlength="10" required>
                            <div id="phoneError" class="error-hint">เบอร์โทรศัพท์ต้องครบ 10 หลัก</div>
                        </div>

                        <div class="col-12">
                            <label class="form-label">ที่อยู่จัดส่งโดยละเอียด</label>
                            <textarea name="address" class="form-control" rows="3" 
                                      placeholder="บ้านเลขที่, หมู่บ้าน/อาคาร, ถนน, แขวง/ตำบล..." required><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <div class="d-flex align-items-center mt-5 mb-4">
                        <div class="bg-dark text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 35px; height: 35px; font-weight: 800;">2</div>
                        <h4 class="fw-bold m-0">วิธีการชำระเงิน</h4>
                    </div>

                    <label class="payment-option active w-100" for="pay_transfer">
                        <input type="radio" name="payment_method" id="pay_transfer" value="โอนเงิน / QR" checked>
                        <div class="d-flex align-items-center">
                            <div class="me-4 text-primary"><i class="fas fa-qrcode fa-3x"></i></div>
                            <div class="flex-grow-1">
                                <h6 class="fw-bold mb-1">สแกนจ่ายผ่าน QR PromptPay</h6>
                                <small class="text-muted">ระบบจะแสดง QR Code ให้คุณสแกนทันที</small>
                            </div>
                            <i class="fas fa-check-circle text-danger fs-4 check-icon"></i>
                        </div>
                        
                        <div id="qr_container">
                            <div class="mb-3">
                                <img src="https://upload.wikimedia.org/wikipedia/th/thumb/c/c5/PromptPay-logo.png/640px-PromptPay-logo.png" height="30" class="mb-2">
                                <p class="small text-muted mb-3">สแกนด้วย Mobile Banking ทุกธนาคาร</p>
                                <img src="<?php echo $qr_url; ?>" class="qr-img">
                            </div>
                            <div class="alert alert-warning py-2 small mb-0">
                                <i class="fas fa-info-circle me-1"></i> เมื่อสแกนจ่ายสำเร็จ กรุณากดยืนยันด้านล่าง
                            </div>
                        </div>
                    </label>

                    <label class="payment-option w-100" for="pay_cod">
                        <input type="radio" name="payment_method" id="pay_cod" value="เก็บเงินปลายทาง">
                        <div class="d-flex align-items-center">
                            <div class="me-4 text-success"><i class="fas fa-shipping-fast fa-3x"></i></div>
                            <div class="flex-grow-1">
                                <h6 class="fw-bold mb-1">เก็บเงินปลายทาง (COD)</h6>
                                <small class="text-muted">ชำระเงินกับพนักงานขนส่งเมื่อได้รับสินค้า</small>
                            </div>
                            <i class="fas fa-check-circle text-muted fs-4 check-icon"></i>
                        </div>
                    </label>

                    <input type="hidden" name="total_amount" value="<?php echo $grand_total; ?>">
                    <button class="w-100 btn btn-confirm shadow-sm mt-4" type="submit">
                        ยืนยันและส่งคำสั่งซื้อ ฿<?php echo number_format($grand_total); ?>
                    </button>
                </form>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card summary-card shadow-sm p-4 border-0">
                <h5 class="fw-bold mb-4">สรุปออเดอร์</h5>
                <div class="order-items mb-4" style="max-height: 400px; overflow-y: auto;">
                    <?php 
                    foreach ($_SESSION['cart'] as $id => $qty) {
                        $res = mysqli_query($conn, "SELECT p_name, p_price, p_image FROM products WHERE p_id = '$id'");
                        $row = mysqli_fetch_array($res);
                        $item_sum = $row['p_price'] * $qty;
                    ?>
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-light rounded-3 p-1 me-3" style="width: 50px; height: 50px;">
                            <img src="<?php echo $row['p_image']; ?>" class="w-100 h-100 object-fit-contain" onerror="this.src='https://placehold.co/100x100'">
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="fw-bold mb-0 small" style="display: -webkit-box; -webkit-line-clamp: 1; -webkit-box-orient: vertical; overflow: hidden;"><?php echo $row['p_name']; ?></h6>
                            <small class="text-muted">x<?php echo $qty; ?></small>
                        </div>
                        <div class="text-end">
                            <span class="fw-bold small">฿<?php echo number_format($item_sum); ?></span>
                        </div>
                    </div>
                    <?php } ?>
                </div>
                
                <hr class="my-4" style="opacity: 0.05;">
                
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted small">รวมราคาสินค้า</span>
                    <span class="fw-bold small">฿<?php echo number_format($grand_total); ?></span>
                </div>
                <div class="d-flex justify-content-between mb-4">
                    <span class="text-muted small">ค่าจัดส่ง</span>
                    <span class="text-success fw-bold small">ฟรี</span>
                </div>
                <div class="d-flex justify-content-between align-items-center p-3 rounded-4" style="background: var(--ss-gray);">
                    <span class="fw-bold">ยอดสุทธิ</span>
                    <span class="fw-bold h4 text-danger mb-0">฿<?php echo number_format($grand_total); ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // การเปลี่ยนวิธีการชำระเงินและแสดง QR Code
    const options = document.querySelectorAll('.payment-option');
    const qrContainer = document.getElementById('qr_container');

    options.forEach(opt => {
        opt.addEventListener('click', function() {
            options.forEach(o => {
                o.classList.remove('active');
                o.querySelector('.check-icon').classList.replace('text-danger', 'text-muted');
            });
            this.classList.add('active');
            this.querySelector('.check-icon').classList.replace('text-muted', 'text-danger');

            // ซ่อน/แสดง QR Code ตามการเลือก
            if(this.getAttribute('for') === 'pay_transfer') {
                qrContainer.style.display = 'block';
            } else {
                qrContainer.style.display = 'none';
            }
        });
    });

    // Validation เบอร์โทร
    document.getElementById('phone').addEventListener('input', function(e) {
        this.value = this.value.replace(/[^0-9]/g, '');
    });

    document.getElementById('checkoutForm').addEventListener('submit', function(e) {
        const phoneVal = document.getElementById('phone').value;
        if (phoneVal.length !== 10) {
            e.preventDefault();
            document.getElementById('phoneError').style.display = 'block';
            document.getElementById('phone').scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    });
</script>
</body>
</html>
