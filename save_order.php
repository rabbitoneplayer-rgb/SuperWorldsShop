<?php
session_start();
include_once("connectdb.php");

// ตรวจสอบว่ามีการส่งข้อมูลมาและล็อกอินอยู่หรือไม่
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    
    // 1. รับค่ารหัสผู้ใช้จาก Session (สำคัญมากเพื่อให้ดึงประวัติได้)
    $u_id = $_SESSION['user_id']; 
    
    // 2. รับค่าจากฟอร์มและป้องกัน SQL Injection
    $fullname = mysqli_real_escape_string($conn, $_POST['fullname']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $total_amount = mysqli_real_escape_string($conn, $_POST['total_amount']);
    
    // 3. ดึงสินค้าในตะกร้ามาทำเป็นข้อความ
    $product_list = [];
    if (isset($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $id => $qty) {
            $res_p = mysqli_query($conn, "SELECT p_name FROM products WHERE p_id = '$id'");
            $row_p = mysqli_fetch_array($res_p);
            $product_list[] = $row_p['p_name'] . " (x$qty)";
        }
    }
    $o_product = mysqli_real_escape_string($conn, implode(", ", $product_list));

    // เตรียมส่วนหัว HTML สำหรับ SweetAlert
    echo "<!DOCTYPE html>
    <html lang='th'>
    <head>
        <meta charset='UTF-8'>
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
        <link href='https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600&display=swap' rel='stylesheet'>
        <style>body { font-family: 'Kanit', sans-serif; }</style>
    </head>
    <body>";

    // 4. บันทึกข้อมูลลงตาราง orders (เพิ่มคอลัมน์ u_id เข้าไป)
    $sql_order = "INSERT INTO orders (u_id, o_name, o_phone, o_address, o_total, o_status, o_date, o_product) 
                  VALUES ('$u_id', '$fullname', '$phone', '$address', '$total_amount', 'รอดำเนินการ', NOW(), '$o_product')";
    
    if (mysqli_query($conn, $sql_order)) {
        // 5. ล้างตะกร้าสินค้าหลังจากบันทึกสำเร็จ
        unset($_SESSION['cart']);

        echo "<script>
            Swal.fire({
                title: 'สั่งซื้อสำเร็จ!',
                text: 'เราได้รับคำสั่งซื้อของคุณเรียบร้อยแล้ว',
                icon: 'success',
                confirmButtonColor: '#e12128',
                confirmButtonText: 'ตกลง'
            }).then(() => {
                window.location.href = 'order_history.php';
            });
        </script>";
    } else {
        $error_msg = mysqli_error($conn);
        echo "<script>
            Swal.fire({
                title: 'เกิดข้อผิดพลาด',
                text: 'ไม่สามารถบันทึกข้อมูลได้: $error_msg',
                icon: 'error'
            }).then(() => {
                window.history.back();
            });
        </script>";
    }
    echo "</body></html>";
} else {
    // ถ้าไม่ได้ล็อกอิน หรือไม่ได้ส่ง POST มา ให้เด้งกลับ
    header("Location: login.php");
    exit();
}
?>
