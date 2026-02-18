<?php
session_start();
include_once("connectdb.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    
    $u_id = $_SESSION['user_id']; 
    $fullname = mysqli_real_escape_string($conn, $_POST['fullname']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $total_amount = mysqli_real_escape_string($conn, $_POST['total_amount']);
    
    // 1. จัดการไฟล์สลิป
    $slip_name = ""; 
    $target_dir = "img/slips/"; 

    if (!empty($_FILES['payment_slip']['name'])) {
        if (!is_dir($target_dir)) { mkdir($target_dir, 0777, true); }
        $ext = pathinfo($_FILES['payment_slip']['name'], PATHINFO_EXTENSION);
        $new_name = "slip_" . $u_id . "_" . time() . "." . $ext;
        $target_file = $target_dir . $new_name;

        if (move_uploaded_file($_FILES['payment_slip']['tmp_name'], $target_file)) {
            $slip_name = $new_name;
        } else {
            echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                  <script>window.onload = () => { Swal.fire({icon:'error', title:'อัปโหลดไม่สำเร็จ', text:'ตรวจสอบ Permission โฟลเดอร์ img/slips/'}).then(()=>window.history.back()); };</script>";
            exit();
        }
    }

    // 2. ดึงสินค้าในตะกร้า
    $product_list = [];
    if (isset($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $id => $qty) {
            $res_p = mysqli_query($conn, "SELECT p_name FROM products WHERE p_id = '$id'");
            $row_p = mysqli_fetch_array($res_p);
            $product_list[] = $row_p['p_name'] . " (x$qty)";
        }
    }
    $o_product = mysqli_real_escape_string($conn, implode(", ", $product_list));

    // --- ส่วนแก้ไข: ปรับ UI ไม่ให้ซ้อนกัน ---
    echo "<!DOCTYPE html>
    <html lang='th'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1'>
        <title>กำลังบันทึกข้อมูล | SUPERWORLDS</title>
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
        <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css'>
        <link href='https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600&display=swap' rel='stylesheet'>
        <style>
            body { font-family: 'Kanit', sans-serif; background-color: #f4f7f6; height: 100vh; display: flex; align-items: center; justify-content: center; margin: 0; }
            #processing-box { text-align: center; background: white; padding: 50px; border-radius: 30px; box-shadow: 0 20px 50px rgba(0,0,0,0.05); }
            .loader { color: #e12128; font-size: 3.5rem; margin-bottom: 20px; animation: spin 1.5s linear infinite; }
            @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
            .swal2-popup { border-radius: 25px !important; }
        </style>
    </head>
    <body>
        <div id='processing-box'>
            <i class='fas fa-circle-notch loader'></i>
            <h4 style='margin:0;'>กำลังสร้างออเดอร์</h4>
            <p style='color:#888; font-size:0.9rem;'>กรุณารอสักครู่ ระบบกำลังบันทึกข้อมูลของคุณ...</p>
        </div>";

    // 3. บันทึกข้อมูล
    $sql_order = "INSERT INTO orders (u_id, o_name, o_phone, o_address, o_total, o_status, o_date, o_product, o_slip) 
                  VALUES ('$u_id', '$fullname', '$phone', '$address', '$total_amount', 'รอดำเนินการ', NOW(), '$o_product', '$slip_name')";
    
    if (mysqli_query($conn, $sql_order)) {
        unset($_SESSION['cart']);

        echo "<script>
            // เมื่อทำงานเสร็จ ให้สั่งซ่อนตัวโหลดก่อนแสดง SweetAlert
            setTimeout(function() {
                document.getElementById('processing-box').style.display = 'none';
                Swal.fire({
                    title: 'สั่งซื้อเรียบร้อย!',
                    text: 'ขอบคุณที่ใช้บริการ SUPERWORLDS ออเดอร์ของคุณกำลังเตรียมจัดส่ง',
                    icon: 'success',
                    confirmButtonColor: '#111',
                    confirmButtonText: 'ไปหน้าประวัติการสั่งซื้อ',
                    allowOutsideClick: false
                }).then(() => {
                    window.location.href = 'order_history.php';
                });
            }, 1500);
        </script>";
    } else {
        $error_msg = mysqli_error($conn);
        echo "<script>
            document.getElementById('processing-box').style.display = 'none';
            Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: '$error_msg', confirmButtonColor: '#e12128' })
            .then(() => { window.history.back(); });
        </script>";
    }
    echo "</body></html>";
} else {
    header("Location: login.php");
    exit();
}
?>
