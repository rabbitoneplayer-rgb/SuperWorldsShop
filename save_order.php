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

    // --- ส่วนแสดงผลหน้าจอขณะประมวลผล (UI ใหม่) ---
    echo "<!DOCTYPE html>
    <html lang='th'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1'>
        <title>บันทึกออเดอร์ | SUPERWORLDS</title>
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
        <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css'>
        <link href='https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600&display=swap' rel='stylesheet'>
        <style>
            body { font-family: 'Kanit', sans-serif; background-color: #f4f7f6; height: 100vh; display: flex; align-items: center; justify-content: center; margin: 0; }
            #processing-box { text-align: center; background: white; padding: 60px; border-radius: 40px; box-shadow: 0 25px 60px rgba(0,0,0,0.06); max-width: 450px; width: 90%; }
            .loader { color: #e12128; font-size: 4rem; margin-bottom: 30px; animation: spin 1.5s linear infinite; }
            @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
            h4 { font-weight: 700; color: #111; margin-bottom: 10px; letter-spacing: 0.5px; }
            p { color: #888; font-size: 0.95rem; }
            .swal2-popup { border-radius: 35px !important; padding: 2em !important; }
            .swal2-title { font-weight: 700 !important; color: #111 !important; }
            .swal2-confirm { border-radius: 50px !important; padding: 12px 30px !important; font-weight: 600 !important; }
            .swal2-cancel { border-radius: 50px !important; padding: 12px 30px !important; font-weight: 600 !important; }
        </style>
    </head>
    <body>
        <div id='processing-box'>
            <i class='fas fa-circle-notch loader'></i>
            <h4>กำลังบันทึกคำสั่งซื้อ</h4>
            <p>กรุณารอสักครู่ ระบบกำลังจัดเตรียมข้อมูลให้คุณ...</p>
        </div>";

    // 3. บันทึกข้อมูล
    $sql_order = "INSERT INTO orders (u_id, o_name, o_phone, o_address, o_total, o_status, o_date, o_product, o_slip) 
                  VALUES ('$u_id', '$fullname', '$phone', '$address', '$total_amount', 'รอดำเนินการ', NOW(), '$o_product', '$slip_name')";
    
    if (mysqli_query($conn, $sql_order)) {
        unset($_SESSION['cart']);

        echo "<script>
            setTimeout(function() {
                document.getElementById('processing-box').style.display = 'none';
                Swal.fire({
                    title: 'สั่งซื้อสำเร็จเรียบร้อย!',
                    text: 'ขอบคุณที่เลือกช้อปกับ SUPERWORLDS ออเดอร์ของคุณได้รับเรียบร้อยแล้ว',
                    icon: 'success',
                    iconColor: '#e12128',
                    showCancelButton: true,
                    confirmButtonColor: '#e12128',
                    cancelButtonColor: '#111',
                    confirmButtonText: '<i class=\"fas fa-shopping-bag me-2\"></i> ช้อปปิ้งต่อ',
                    cancelButtonText: '<i class=\"fas fa-history me-2\"></i> ดูประวัติสั่งซื้อ',
                    allowOutsideClick: false,
                    backdrop: `rgba(225,33,40,0.05)`
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = 'index.php';
                    } else {
                        window.location.href = 'order_history.php';
                    }
                });
            }, 1800);
        </script>";
    } else {
        $error_msg = mysqli_error($conn);
        echo "<script>
            document.getElementById('processing-box').style.display = 'none';
            Swal.fire({ 
                icon: 'error', 
                title: 'เกิดข้อผิดพลาด', 
                text: '$error_msg', 
                confirmButtonColor: '#111' 
            }).then(() => { window.history.back(); });
        </script>";
    }
    echo "</body></html>";
} else {
    header("Location: login.php");
    exit();
}
?>
