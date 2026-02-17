<?php
session_start();
include_once("connectdb.php");

// ตรวจสอบถ้าไม่มีสินค้าในตะกร้าให้เด้งกลับหน้าแรก
if (empty($_SESSION['cart'])) {
    header("Location: index.php");
    exit();
}

// ดึงข้อมูลผู้ใช้ (ถ้า Login อยู่)
$uid = $_SESSION['user_id'] ?? 0;
$res_user = mysqli_query($conn, "SELECT * FROM users WHERE id = '$uid'");
$user = mysqli_fetch_array($res_user);

// คำนวณยอดรวมก่อนเพื่อใช้ใน Hidden Input
$grand_total = 0;
foreach ($_SESSION['cart'] as $id => $qty) {
    $res = mysqli_query($conn, "SELECT p_price FROM products WHERE p_id = '$id'");
    $row = mysqli_fetch_array($res);
    $grand_total += ($row['p_price'] * $qty);
}
?>
<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ชำระเงิน - SUPERWORLDS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root { --ss-red: #e12128; --ss-dark: #111111; }
        body { background-color: #f4f7f6; font-family: 'Segoe UI', Tahoma, sans-serif; color: #333; }
        
        .checkout-card { border: none; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); background: #fff; }
        .summary-card { border: none; border-radius: 20px; background: #fff; position: sticky; top: 100px; }
        
        .form-label { font-weight: 700; font-size: 0.9rem; color: #666; text-transform: uppercase; letter-spacing: 0.5px; }
        .form-control { border: 1px solid #eee; padding: 12px 15px; border-radius: 12px; transition: 0.3s; background-color: #fdfdfd; }
        .form-control:focus { border-color: var(--ss-red); box-shadow: none; background-color: #fff; }
        
        /* Payment Method Option */
        .payment-option { border: 2px solid #eee; border-radius: 15px; padding: 15px; cursor: pointer; transition: 0.3s; position: relative; margin-bottom: 12px; }
        .payment-option:hover { border-color: #ddd; }
        .payment-option.active { border-color: var(--ss-red); background-color: #fef2f2; }
        .payment-option input[type="radio"] { position: absolute; opacity: 0; }
        
        .btn-confirm { background: var(--ss-dark); color: white; border-radius: 50px; padding: 16px; font-weight: 800; border: none; transition: 0.3s; letter-spacing: 1px; }
        .btn-confirm:hover { background: var(--ss-red); transform: translateY(-3px); box-shadow: 0 10px 20px rgba(225,33,40,0.2); color: white; }
        
        .error-hint { font-size: 0.8rem; color: var(--ss-red); display: none; margin-top: 6px; font-weight: 600; }
        .is-invalid { border-color: var(--ss-red) !important; }
    </style>
</head>
<body class="py-5">

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-5">
        <a href="cart.php" class="text-decoration-none text-muted fw-bold">
            <i class="fas fa-chevron-left me-2"></i> ย้อนกลับ
        </a>
        <h2 class="fw-bold m-0">CHECKOUT</h2>
        <div style="width: 80px;"></div> 
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card checkout-card p-4 p-md-5">
                <form action="save_order.php" method="POST" id="checkoutForm">
                    <div class="d-flex align-items-center mb-4">
                        <div class="bg-dark text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 35px; height: 35px;">1</div>
                        <h4 class="fw-bold m-0">ที่อยู่ในการจัดส่ง</h4>
                    </div>

                    <div class="row g-4">
                        <div class="col-12">
                            <label class="form-label">ชื่อ-นามสกุล ผู้รับ</label>
                            <div class="input-group">
                                <span class="input-group-text bg-transparent border-end-0" style="border-radius: 12px 0 0 12px;"><i class="far fa-user text-muted"></i></span>
                                <input type="text" id="fullname" name="fullname" class="form-control border-start-0" 
                                       value="<?php echo htmlspecialchars($user['fullname'] ?? ''); ?>" 
                                       placeholder="ภาษาไทย หรือ อังกฤษ (ห้ามปนกัน)" required style="border-radius: 0 12px 12px 0;">
                            </div>
                            <div id="nameError" class="error-hint"><i class="fas fa-exclamation-circle me-1"></i> กรุณากรอกภาษาไทยล้วน หรือ อังกฤษล้วนเท่านั้น</div>
                        </div>

                        <div class="col-12">
                            <label class="form-label">เบอร์โทรศัพท์</label>
                            <div class="input-group">
                                <span class="input-group-text bg-transparent border-end-0" style="border-radius: 12px 0 0 12px;"><i class="fas fa-mobile-alt text-muted"></i></span>
                                <input type="text" id="phone" name="phone" class="form-control border-start-0" 
                                       value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" 
                                       placeholder="08XXXXXXXX" maxlength="10" required style="border-radius: 0 12px 12px 0;">
                            </div>
                            <div id="phoneError" class="error-hint"><i class="fas fa-exclamation-circle me-1"></i> กรุณากรอกเบอร์โทรศัพท์ให้ครบ 10 หลัก</div>
                        </div>

                        <div class="col-12">
                            <label class="form-label">รายละเอียดที่อยู่</label>
                            <textarea name="address" class="form-control" rows="3" 
                                      placeholder="บ้านเลขที่, แขวง, เขต, จังหวัด, รหัสไปรษณีย์" required><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <div class="d-flex align-items-center mt-5 mb-4">
                        <div class="bg-dark text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 35px; height: 35px;">2</div>
                        <h4 class="fw-bold m-0">วิธีการชำระเงิน</h4>
                    </div>

                    <label class="payment-option active w-100" for="pay_transfer">
                        <input type="radio" name="payment_method" id="pay_transfer" value="โอนเงิน / QR" checked>
                        <div class="d-flex align-items-center">
                            <div class="me-3 text-primary"><i class="fas fa-qrcode fa-2x"></i></div>
                            <div class="flex-grow-1">
                                <h6 class="fw-bold mb-0">โอนเงิน / QR PromptPay</h6>
                                <small class="text-muted">ชำระผ่าน Mobile Banking ทุกธนาคาร</small>
                            </div>
                            <i class="fas fa-check-circle text-danger check-icon"></i>
                        </div>
                    </label>

                    <label class="payment-option w-100" for="pay_cod">
                        <input type="radio" name="payment_method" id="pay_cod" value="เก็บเงินปลายทาง">
                        <div class="d-flex align-items-center">
                            <div class="me-3 text-success"><i class="fas fa-hand-holding-usd fa-2x"></i></div>
                            <div class="flex-grow-1">
                                <h6 class="fw-bold mb-0">เก็บเงินปลายทาง (COD)</h6>
                                <small class="text-muted">ชำระเงินเมื่อได้รับสินค้าหน้าบ้าน</small>
                            </div>
                            <i class="fas fa-check-circle text-muted check-icon"></i>
                        </div>
                    </label>

                    <input type="hidden" name="total_amount" value="<?php echo $grand_total; ?>">
                    <button class="w-100 btn btn-confirm shadow-sm mt-4" type="submit">
                        ยืนยันการสั่งซื้อ ฿<?php echo number_format($grand_total); ?> <i class="fas fa-arrow-right ms-2"></i>
                    </button>
                </form>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card summary-card shadow-sm p-4 border-0">
                <h4 class="fw-bold mb-4">สรุปคำสั่งซื้อ</h4>
                <div class="order-items mb-4" style="max-height: 350px; overflow-y: auto;">
                    <?php 
                    foreach ($_SESSION['cart'] as $id => $qty) {
                        $res = mysqli_query($conn, "SELECT p_name, p_price, p_image FROM products WHERE p_id = '$id'");
                        $row = mysqli_fetch_array($res);
                        $item_sum = $row['p_price'] * $qty;
                    ?>
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-light rounded-3 p-2 me-3" style="width: 60px; height: 60px;">
                            <img src="<?php echo $row['p_image']; ?>" class="w-100 h-100 object-fit-contain" onerror="this.src='https://placehold.co/100x100'">
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="fw-bold mb-0 small"><?php echo $row['p_name']; ?></h6>
                            <small class="text-muted">จำนวน <?php echo $qty; ?> ชิ้น</small>
                        </div>
                        <div class="text-end">
                            <span class="fw-bold small">฿<?php echo number_format($item_sum); ?></span>
                        </div>
                    </div>
                    <?php } ?>
                </div>
                
                <hr class="my-4">
                
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">ยอดรวม</span>
                    <span class="fw-bold">฿<?php echo number_format($grand_total); ?></span>
                </div>
                <div class="d-flex justify-content-between mb-3">
                    <span class="text-muted">ค่าจัดส่ง</span>
                    <span class="text-success fw-bold">FREE</span>
                </div>
                <div class="d-flex justify-content-between align-items-center bg-light p-3 rounded-4">
                    <span class="fw-bold">ยอดชำระสุทธิ</span>
                    <span class="fw-bold fs-4 text-danger">฿<?php echo number_format($grand_total); ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // จัดการการเลือก Payment Method (UI)
    const options = document.querySelectorAll('.payment-option');
    options.forEach(opt => {
        opt.addEventListener('click', function() {
            // ลบ class active และ reset icon
            options.forEach(o => {
                o.classList.remove('active');
                o.querySelector('.check-icon').classList.replace('text-danger', 'text-muted');
            });
            // เพิ่ม class active ให้ตัวที่เลือก
            this.classList.add('active');
            this.querySelector('.check-icon').classList.replace('text-muted', 'text-danger');
        });
    });

    // Validation
    const checkoutForm = document.getElementById('checkoutForm');
    const fullnameInput = document.getElementById('fullname');
    const phoneInput = document.getElementById('phone');

    phoneInput.addEventListener('input', function(e) {
        this.value = this.value.replace(/[^0-9]/g, '');
    });

    checkoutForm.addEventListener('submit', function(e) {
        let isValid = true;
        const nameVal = fullnameInput.value.trim();
        const phoneVal = phoneInput.value.trim();

        const thaiPattern = /^[ก-๙\s]+$/;
        const engPattern = /^[a-zA-Z\s]+$/;

        if (!thaiPattern.test(nameVal) && !engPattern.test(nameVal)) {
            document.getElementById('nameError').style.display = 'block';
            fullnameInput.classList.add('is-invalid');
            isValid = false;
        } else {
            document.getElementById('nameError').style.display = 'none';
            fullnameInput.classList.remove('is-invalid');
        }

        if (phoneVal.length !== 10) {
            document.getElementById('phoneError').style.display = 'block';
            phoneInput.classList.add('is-invalid');
            isValid = false;
        } else {
            document.getElementById('phoneError').style.display = 'none';
            phoneInput.classList.remove('is-invalid');
        }

        if (!isValid) {
            e.preventDefault();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    });
</script>
</body>
</html>