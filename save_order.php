<?php
session_start();
include_once("connectdb.php");

// ตรวจสอบว่ามีการส่งข้อมูลมาและล็อกอินอยู่หรือไม่
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    
    // 1. รับค่ารหัสผู้ใช้จาก Session
    $u_id = $_SESSION['user_id']; 
    
    // 2. รับค่าจากฟอร์มและป้องกัน SQL Injection
    $fullname = mysqli_real_escape_string($conn, $_POST['fullname']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $total_amount = mysqli_real_escape_string($conn, $_POST['total_amount']);
    
    // 3. จัดการไฟล์สลิปโอนเงิน (เพิ่มระบบตรวจสอบสิทธิ์ Permission)
    $slip_name = ""; 
    $target_dir = "img/slips/"; 

    if (!empty($_FILES['payment_slip']['name'])) {
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
            chmod($target_dir, 0777); 
        }

        $ext = pathinfo($_FILES['payment_slip']['name'], PATHINFO_EXTENSION);
        $new_name = "slip_" . $u_id . "_" . time() . "." . $ext;
        $target_file = $target_dir . $new_name;

        if (move_uploaded_file($_FILES['payment_slip']['tmp_name'], $target_file)) {
            $slip_name = $new_name;
            chmod($target_file, 0644);
        } else {
            echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                  <script>
                    window.onload = function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'อัปโหลดสลิปไม่สำเร็จ',
                            text: 'กรุณาตรวจสอบสิทธิ์โฟลเดอร์ img/slips/',
                            confirmButtonColor: '#111'
                        }).then(() => window.history.back());
                    };
                  </script>";
            exit();
        }
    }

    // 4. ดึงสินค้าในตะกร้ามาทำเป็นข้อความ
    $product_list = [];
    if (isset($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $id => $qty) {
            $res_p = mysqli_query($conn, "SELECT p_name FROM products WHERE p_id = '$id'");
            $row_p = mysqli_fetch_array($res_p);
            $product_list[] = $row_p['p_name'] . " (x$qty)";
        }
    }
    $o_product = mysqli_real_escape_string($conn, implode(", ", $product_list));

    // --- ส่วนตกแต่งหน้าจอขณะประมวลผล ---
    echo "<!DOCTYPE html>
    <html lang='th'>
    <head>
        <meta charset='UTF-8'>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>กำลังประมวลผล | SUPERWORLDS</title>
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
        <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css'>
        <link href='https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600&display=swap' rel='stylesheet'>
        <style>
            body { 
                font-family: 'Kanit', sans-serif; 
                background-color: #f4f7f6; 
                height: 100vh; display: flex; align-items: center; justify-content: center; margin: 0;
                color: #333;
            }
            .processing-box { text-align: center; background: white; padding: 50px; border-radius: 30px; box-shadow: 0 20px 50px rgba(0,0,0,0.05); }
            .loader { color: #e12128; font-size: 3rem; margin-bottom: 20px; animation: spin 2s linear infinite; }
            @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
            h4 { margin: 0; font-weight: 600; letter-spacing: 1px; }
            .swal2-popup { border-radius: 25px !important; font-family: 'Kanit', sans-serif !important; }
        </style>
    </head>
    <body>
        <div class='processing-box'>
            <i class='fas fa-circle-notch loader'></i>
            <h4>กำลังบันทึกคำสั่งซื้อของคุณ</h4>
            <p class='text-muted small'>กรุณาอย่าปิดหน้าต่างนี้...</p>
        </div>";

    // 5. บันทึกข้อมูลลงตาราง orders
    $sql_order = "INSERT INTO orders (u_id, o_name, o_phone, o_address, o_total, o_status, o_date, o_product, o_slip) 
                  VALUES ('$u_id', '$fullname', '$phone', '$address', '$total_amount', 'รอดำเนินการ', NOW(), '$o_product', '$slip_name')";
    
    if (mysqli_query($conn, $sql_order)) {
        unset($_SESSION['cart']);

        echo "<script>
            setTimeout(function() {
                Swal.fire({
                    title: 'สั่งซื้อสำเร็จ!',
                    text: 'เราได้รับคำสั่งซื้อและหลักฐานของคุณแล้ว',
                    icon: 'success',
                    confirmButtonColor: '#111',
                    confirmButtonText: 'ดูประวัติการสั่งซื้อ',
                    backdrop: `rgba(225,33,40,0.1)`,
                    allowOutsideClick: false
                }).then(() => {
                    window.location.href = 'order_history.php';
                });
            }, 1000);
        </script>";
    } else {
        $error_msg = mysqli_error($conn);
        echo "<script>
            Swal.fire({
                title: 'เกิดข้อผิดพลาด',
                text: '$error_msg',
                icon: 'error',
                confirmButtonColor: '#e12128'
            }).then(() => {
                window.history.back();
            });
        </script>";
    }
    echo "</body></html>";
} else {
    header("Location: login.php");
    exit();
}
?>
