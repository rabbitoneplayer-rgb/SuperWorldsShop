<?php
session_start();
include_once("connectdb.php");

// เปิดการแจ้งเตือน Error เพื่อตรวจสอบสาเหตุ (ลบออกเมื่อใช้งานจริง)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// ตรวจสอบการส่งข้อมูลและล็อกอิน
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    
    $u_id = $_SESSION['user_id']; 
    $fullname = mysqli_real_escape_string($conn, $_POST['fullname']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $total_amount = mysqli_real_escape_string($conn, $_POST['total_amount']);
    
    // 1. จัดการไฟล์สลิป (ตรวจสอบสิทธิ์ Permission)
    $slip_name = ""; 
    $target_dir = "img/slips/"; 

    if (!empty($_FILES['payment_slip']['name'])) {
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $ext = pathinfo($_FILES['payment_slip']['name'], PATHINFO_EXTENSION);
        $new_name = "slip_" . $u_id . "_" . time() . "." . $ext;
        $target_file = $target_dir . $new_name;

        if (move_uploaded_file($_FILES['payment_slip']['tmp_name'], $target_file)) {
            $slip_name = $new_name;
            chmod($target_file, 0644);
        } else {
            // กรณีอัปโหลดไม่สำเร็จ (Permission Error)
            echo "<!DOCTYPE html><html lang='th'><head><meta charset='UTF-8'><script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script></head><body>
                  <script>Swal.fire({icon:'error', title:'อัปโหลดสลิปไม่สำเร็จ', text:'กรุณาแจ้งแอดมินให้ตรวจสอบ Permission โฟลเดอร์ slips', confirmButtonColor:'#111'}).then(()=>window.history.back());</script></body></html>";
            exit();
        }
    }

    // 2. รวมรายการสินค้าจากตะกร้า
    $product_list = [];
    if (isset($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $id => $qty) {
            $res_p = mysqli_query($conn, "SELECT p_name FROM products WHERE p_id = '$id'");
            $row_p = mysqli_fetch_array($res_p);
            $product_list[] = $row_p['p_name'] . " (x$qty)";
        }
    }
    $o_product = mysqli_real_escape_string($conn, implode(", ", $product_list));

    // --- ส่วนแสดงผลหน้าจอขณะประมวลผล (UI ตกแต่งใหม่) ---
    echo "<!DOCTYPE html>
    <html lang='th'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1'>
        <title>บันทึกคำสั่งซื้อ | SUPERWORLDS</title>
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
        <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css'>
        <link href='https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600&display=swap' rel='stylesheet'>
        <style>
            body { font-family: 'Kanit', sans-serif; background-color: #f4f7f6; height: 100vh; display: flex; align-items: center; justify-content: center; margin: 0; }
            .processing-box { text-align: center; background: white; padding: 60px; border-radius: 40px; box-shadow: 0 25px 60px rgba(0,0,0,0.08); max-width: 400px; width: 90%; }
            .loader { color: #e12128; font-size: 4rem; margin-bottom: 30px; animation: spin 1.5s linear infinite; }
            @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
            h4 { margin-bottom: 10px; font-weight: 700; color: #111; }
            p { color: #888; font-size: 0.9rem; }
            .swal2-popup { border-radius: 30px !important; }
        </style>
    </head>
    <body>
        <div class='processing-box'>
            <i class='fas fa-circle-notch loader'></i>
            <h4>กำลังสร้างออเดอร์</h4>
            <p>กรุณารอสักครู่ ระบบกำลังบันทึกข้อมูลของคุณ...</p>
        </div>";

    // 3. บันทึกข้อมูลลงฐานข้อมูล (INSERT)
    $sql_order = "INSERT INTO orders (u_id, o_name, o_phone, o_address, o_total, o_status, o_date, o_product, o_slip) 
                  VALUES ('$u_id', '$fullname', '$phone', '$address', '$total_amount', 'รอดำเนินการ', NOW(), '$o_product', '$slip_name')";
    
    if (mysqli_query($conn, $sql_order)) {
        unset($_SESSION['cart']); // ล้างตะกร้า

        echo "<script>
            setTimeout(function() {
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
            }, 1200);
        </script>";
    } else {
        $db_error = mysqli_real_escape_string($conn, mysqli_error($conn));
        echo "<script>
            Swal.fire({
                icon: 'error',
                title: 'Database Error',
                text: 'ไม่สามารถบันทึกข้อมูลได้: $db_error',
                confirmButtonColor: '#e12128'
            }).then(() => window.history.back());
        </script>";
    }
    echo "</body></html>";
} else {
    header("Location: login.php");
    exit();
}
?>
