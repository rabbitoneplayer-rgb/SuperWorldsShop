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
$pp_id = "0812345678"; // <--- เปลี่ยนเป็นเบอร์พร้อมเพย์ของคุณ
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
        
        .payment-option { border: 2px solid #eee; border-radius: 20px; padding: 20px; cursor: pointer; transition: 0.3s; position: relative; margin-bottom: 15px; }
        .payment-option:hover { border-color: #ddd; }
        .payment-option.active { border-color: var(--ss-red); background-color: #fff5f5; }
        .payment-option input[type="radio"] { position: absolute; opacity: 0; }
        
        /* QR & Slip Styling */
        #qr_container { display: block; text-align: center; background: white; padding: 25px; border-radius: 20px; border: 1px solid #eee; margin-top: 15px; animation: fadeIn 0.5s; }
        .qr-img { width: 200px; height: 200px; border: 5px solid #fff; box-shadow: 0 5px 20px rgba(0,0,0,0.1); border-radius: 15px; }
        
        .slip-upload-box { background: #f8f9fa; border: 2px dashed #ddd; border-radius: 20px; padding: 20px; text-align: center; margin-top: 20px; transition: 0.3s; }
        .slip-upload-box:hover { border-color: var(--ss-red); background: #fff5f5; }
        
        .btn-confirm { background: var(--ss-dark); color: white; border-radius: 50px; padding: 20px; font-weight: 800; border: none; transition: 0.4s; letter-spacing: 1px; font-size: 1.1rem; width: 100%; }
        .btn-confirm:hover { background: var(--ss-red); transform: translateY(-5px); box-shadow: 0 15px 30px rgba(225,33,40,0.3); color: white; }
        
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        .error-hint { font-size: 0.8rem; color: var(--ss-red); display: none; margin-top: 6px; font-weight: 600; }
        #slip_preview { max-width: 150px; border-radius: 10px; display: none; margin: 15px auto 0; }
    </style>
</head>
<body class="py-5">

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-5 px-3">
        <a href="cart.php" class="text-decoration-none text-muted fw-bold small">
            <i class="fas fa-arrow-left me-2"></i> กลับไปยังตะกร้า
        </a>
        <h2 class="fw-bold m-0">ชำระเงิน</h2>
        <div style="width: 100px;"></div> 
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card checkout-card p-4 p-md-5">
                <form action="save_order.php" method="POST" id="checkoutForm" enctype="multipart/form-data">
                    <div class="d-flex align-items-center mb-4">
                        <div class="bg-dark text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 35px; height: 35px; font-weight: 800;">1</div>
                        <h4 class="fw-bold m-0">ที่อยู่จัดส่ง</h4>
                    </div>

                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label">ชื่อผู้รับ</label>
                            <input type="text" id="fullname" name="fullname" class="form-control" value="<?php echo htmlspecialchars($user['fullname'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">เบอร์โทรศัพท์</label>
                            <input type="text" id="phone" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" maxlength="10" required>
                            <div id="phoneError" class="error-hint">กรุณากรอกเบอร์โทร 10 หลัก</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">ที่อยู่โดยละเอียด</label>
                            <textarea name="address" class="form-control" rows="3" required><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <div class="d-flex align-items-center mt-5 mb-4">
                        <div class="bg-dark text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 35px; height: 35px; font-weight: 800;">2</div>
                        <h4 class="fw-bold m-0">ช่องทางการชำระเงิน</h4>
                    </div>

                    <label class="payment-option active w-100" for="pay_transfer">
                        <input type="radio" name="payment_method" id="pay_transfer" value="โอนเงิน / QR" checked>
                        <div class="d-flex align-items-center mb-3">
                            <div class="me-4 text-primary"><i class="fas fa-qrcode fa-3x"></i></div>
                            <div class="flex-grow-1">
                                <h6 class="fw-bold mb-1">โอนเงิน / สแกน QR Code</h6>
                                <small class="text-muted">ชำระเงินตอนนี้และแนบสลิปเพื่อยืนยัน</small>
                            </div>
                            <i class="fas fa-check-circle text-danger fs-4 check-icon"></i>
                        </div>

                        <div id="qr_container">
                            <div class="mb-3">
                                <img src="https://upload.wikimedia.org/wikipedia/commons/c/c5/PromptPay-logo.png" height="45" class="mb-2" alt="PromptPay Logo">
                                <p class="small text-muted mb-3">สแกนเพื่อชำระเงิน ฿<?php echo number_format($grand_total, 2); ?></p>
                                <img src="<?php echo $qr_url; ?>" class="qr-img border shadow-sm">
                            </div>

                            <div class="slip-upload-box">
                                <label class="fw-bold small d-block mb-2 text-dark"><i class="fas fa-file-invoice-dollar me-1"></i> อัปโหลดสลิปโอนเงิน</label>
                                <input type="file" name="payment_slip" id="payment_slip" class="form-control form-control-sm" accept="image/*">
                                <img id="slip_preview" src="#" alt="Slip Preview">
                                <div id="slipError" class="error-hint">กรุณาแนบสลิปโอนเงินก่อนยืนยัน</div>
                            </div>
                        </div>
                    </label>

                    <label class="payment-option w-100" for="pay_cod">
                        <input type="radio" name="payment_method" id="pay_cod" value="เก็บเงินปลายทาง">
                        <div class="d-flex align-items-center">
                            <div class="me-4 text-success"><i class="fas fa-shipping-fast fa-3x"></i></div>
                            <div class="flex-grow-1">
                                <h6 class="fw-bold mb-1">เก็บเงินปลายทาง (COD)</h6>
                                <small class="text-muted">ชำระเงินสดเมื่อได้รับสินค้า</small>
                            </div>
                            <i class="fas fa-check-circle text-muted fs-4 check-icon"></i>
                        </div>
                    </label>

                    <input type="hidden" name="total_amount" value="<?php echo $grand_total; ?>">
                    <button class="btn btn-confirm shadow-sm mt-4" type="submit" id="submitBtn">
                        ยืนยันการสั่งซื้อ ฿<?php echo number_format($grand_total); ?>
                    </button>
                </form>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card summary-card shadow-sm p-4 border-0">
                <h5 class="fw-bold mb-4">สรุปออเดอร์</h5>
                <div class="order-items mb-4" style="max-height: 350px; overflow-y: auto;">
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
                            <h6 class="fw-bold mb-0 small"><?php echo $row['p_name']; ?></h6>
                            <small class="text-muted">x<?php echo $qty; ?></small>
                        </div>
                        <div class="text-end">
                            <span class="fw-bold small">฿<?php echo number_format($item_sum); ?></span>
                        </div>
                    </div>
                    <?php } ?>
                </div>
                <div class="d-flex justify-content-between align-items-center p-3 rounded-4" style="background: #f8f9fa; border: 1px solid #eee;">
                    <span class="fw-bold">ยอดชำระสุทธิ</span>
                    <span class="fw-bold h4 text-danger mb-0">฿<?php echo number_format($grand_total); ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const options = document.querySelectorAll('.payment-option');
    const qrContainer = document.getElementById('qr_container');
    const slipInput = document.getElementById('payment_slip');
    const slipPreview = document.getElementById('slip_preview');

    // สลับการแสดงผล QR และ สลิป
    options.forEach(opt => {
        opt.addEventListener('click', function() {
            options.forEach(o => {
                o.classList.remove('active');
                o.querySelector('.check-icon').classList.replace('text-danger', 'text-muted');
            });
            this.classList.add('active');
            this.querySelector('.check-icon').classList.replace('text-muted', 'text-danger');
            qrContainer.style.display = (this.getAttribute('for') === 'pay_transfer') ? 'block' : 'none';
        });
    });

    // Preview สลิปเมื่อเลือกไฟล์
    slipInput.onchange = evt => {
        const [file] = slipInput.files;
        if (file) {
            slipPreview.src = URL.createObjectURL(file);
            slipPreview.style.display = 'block';
            document.getElementById('slipError').style.display = 'none';
        }
    }

    // ตรวจสอบก่อนส่งฟอร์ม
    document.getElementById('checkoutForm').addEventListener('submit', function(e) {
        let isValid = true;
        
        // เช็คเบอร์โทร
        if (document.getElementById('phone').value.length !== 10) {
            document.getElementById('phoneError').style.display = 'block';
            isValid = false;
        }

        // เช็คสลิป (เฉพาะถ้าเลือกโอนเงิน)
        const isTransfer = document.getElementById('pay_transfer').checked;
        if (isTransfer && slipInput.files.length === 0) {
            document.getElementById('slipError').style.display = 'block';
            isValid = false;
        }

        if (!isValid) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'ข้อมูลไม่ครบถ้วน',
                text: 'กรุณาตรวจสอบเบอร์โทรศัพท์ หรือแนบสลิปโอนเงินให้เรียบร้อย',
                confirmButtonColor: '#111'
            });
        } else {
            document.getElementById('submitBtn').disabled = true;
            document.getElementById('submitBtn').innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> กำลังบันทึกข้อมูล...';
        }
    });
</script>
</body>
</html>
