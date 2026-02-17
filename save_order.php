<?php
session_start();
include_once("connectdb.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. รับค่าและป้องกัน SQL Injection
    $fullname = mysqli_real_escape_string($conn, $_POST['fullname']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $total_amount = mysqli_real_escape_string($conn, $_POST['total_amount']);
    $payment_method = mysqli_real_escape_string($conn, $_POST['payment_method'] ?? 'โอนเงิน / QR');
    
    // ดึงสินค้าในตะกร้ามาทำเป็นข้อความ เพื่อเก็บลงคอลัมน์ o_product (ตามโครงสร้างฐานข้อมูลเดิมของคุณ)
    $product_list = [];
    foreach ($_SESSION['cart'] as $id => $qty) {
        $res_p = mysqli_query($conn, "SELECT p_name FROM products WHERE p_id = '$id'");
        $row_p = mysqli_fetch_array($res_p);
        $product_list[] = $row_p['p_name'] . " (x$qty)";
    }
    $o_product = mysqli_real_escape_string($conn, implode(", ", $product_list));

    // เตรียมส่วนหัว HTML
    echo "<!DOCTYPE html>
    <html lang='th'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Processing Order | SUPERWORLDS</title>
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
        <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css'>
        <link href='https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600&display=swap' rel='stylesheet'>
        <style>
            body { 
                font-family: 'Kanit', sans-serif; 
                background: #f4f7f6;
                height: 100vh; display: flex; align-items: center; justify-content: center; margin: 0; 
            }
            .swal2-popup { border-radius: 30px !important; padding: 2rem !important; box-shadow: 0 20px 60px rgba(0,0,0,0.1) !important; }
            .swal2-title { font-weight: 600 !important; color: #111 !important; }
            .swal2-html-container { color: #666 !important; }
            .swal2-confirm { border-radius: 50px !important; padding: 12px 35px !important; font-weight: 600 !important; font-size: 0.9rem !important; }
            .swal2-cancel { border-radius: 50px !important; padding: 12px 35px !important; font-weight: 600 !important; font-size: 0.9rem !important; }
        </style>
    </head>
    <body>";

    // 2. บันทึกข้อมูลลงตาราง orders
    // เพิ่มการบันทึกรายการสินค้าลงคอลัมน์ o_product เพื่อให้ระบบสมบูรณ์
    $sql_order = "INSERT INTO orders (o_name, o_phone, o_address, o_total, o_status, o_date, o_product) 
                  VALUES ('$fullname', '$phone', '$address', '$total_amount', 'รอดำเนินการ', NOW(), '$o_product')";
    
    if (mysqli_query($conn, $sql_order)) {
        // 3. ล้างตะกร้าสินค้า
        unset($_SESSION['cart']);

        // 4. แสดงการแจ้งเตือนสำเร็จ (Modern Design)
        echo "<script>
            Swal.fire({
                title: 'สั่งซื้อสำเร็จ!',
                html: 'ออเดอร์ของคุณได้รับเรียบร้อยแล้ว<br><small class=\"text-muted\">เราจะดำเนินการจัดส่งให้เร็วที่สุด</small>',
                icon: 'success',
                iconColor: '#e12128',
                showCancelButton: true,
                confirmButtonColor: '#e12128',
                cancelButtonColor: '#111',
                confirmButtonText: '<i class=\"fas fa-shopping-bag me-2\"></i> ช้อปปิ้งต่อ',
                cancelButtonText: '<i class=\"fas fa-history me-2\"></i> ประวัติสั่งซื้อ',
                allowOutsideClick: false,
                backdrop: `rgba(225, 33, 40, 0.05)`
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'index.php';
                } else {
                    window.location.href = 'order_history.php';
                }
            });
        </script>";
    } else {
        // กรณี Error
        $error_msg = mysqli_error($conn);
        echo "<script>
            Swal.fire({
                title: 'เกิดข้อผิดพลาด',
                text: 'ไม่สามารถบันทึกคำสั่งซื้อได้: $error_msg',
                icon: 'error',
                confirmButtonColor: '#111',
                confirmButtonText: 'กลับไปแก้ไข'
            }).then(() => {
                window.history.back();
            });
        </script>";
    }
    echo "</body></html>";
} else {
    header("Location: index.php");
    exit();
}
?>