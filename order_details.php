<?php
session_start();
include_once("connectdb.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fullname = mysqli_real_escape_string($conn, $_POST['fullname']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $total_amount = mysqli_real_escape_string($conn, $_POST['total_amount']);
    
    echo "<!DOCTYPE html>
    <html lang='th'>
    <head>
        <meta charset='UTF-8'>
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
        <style>body { font-family: 'Segoe UI', sans-serif; }</style>
    </head>
    <body>";

    // เริ่ม Transaction เพื่อความปลอดภัยของข้อมูล
    mysqli_begin_transaction($conn);

    try {
        // 1. บันทึกข้อมูลลงตาราง orders
        $sql_order = "INSERT INTO orders (o_name, o_phone, o_address, o_total, o_status, o_date) 
                      VALUES ('$fullname', '$phone', '$address', '$total_amount', 'รอดำเนินการ', NOW())";
        
        if (!mysqli_query($conn, $sql_order)) {
            throw new Exception("ไม่สามารถบันทึกข้อมูลคำสั่งซื้อได้");
        }

        $order_id = mysqli_insert_id($conn); // ดึง ID ของออเดอร์ที่เพิ่งบันทึก

        // 2. วนลูปบันทึกรายละเอียดสินค้า (ถ้าคุณมีตาราง order_details)
        if (isset($_SESSION['cart'])) {
            foreach ($_SESSION['cart'] as $p_id => $qty) {
                // ดึงราคาสินค้าปัจจุบันมาบันทึก (ป้องกันราคาเปลี่ยนภายหลัง)
                $res_p = mysqli_query($conn, "SELECT p_price FROM products WHERE p_id = '$p_id'");
                $row_p = mysqli_fetch_array($res_p);
                $current_price = $row_p['p_price'];

                $sql_detail = "INSERT INTO order_details (o_id, p_id, d_qty, d_price) 
                               VALUES ('$order_id', '$p_id', '$qty', '$current_price')";
                
                if (!mysqli_query($conn, $sql_detail)) {
                    throw new Exception("ไม่สามารถบันทึกรายละเอียดสินค้าได้");
                }
                
                // 3. (ทางเลือก) ตัดสต็อกสินค้าตรงนี้
                // mysqli_query($conn, "UPDATE products SET p_stock = p_stock - $qty WHERE p_id = '$p_id'");
            }
        }

        // ถ้าทุกอย่างผ่าน ให้ยืนยันการบันทึก
        mysqli_commit($conn);
        unset($_SESSION['cart']);

        echo "<script>
            Swal.fire({
                title: 'สั่งซื้อสินค้าสำเร็จ!',
                text: 'เลขที่คำสั่งซื้อของคุณคือ #" . str_pad($order_id, 5, '0', STR_PAD_LEFT) . "',
                icon: 'success',
                showCancelButton: true,
                confirmButtonColor: '#e12128',
                cancelButtonColor: '#1a1a1a',
                confirmButtonText: 'ช้อปปิ้งต่อ',
                cancelButtonText: 'ดูประวัติการสั่งซื้อ',
                allowOutsideClick: false
            }).then((result) => {
                window.location.href = result.isConfirmed ? 'index.php' : 'order_history.php';
            });
        </script>";

    } catch (Exception $e) {
        // หากเกิดข้อผิดพลาด ให้ยกเลิกการบันทึกทั้งหมด (Rollback)
        mysqli_rollback($conn);
        $msg = $e->getMessage();
        echo "<script>
            Swal.fire({
                title: 'เกิดข้อผิดพลาด',
                text: '$msg',
                icon: 'error',
                confirmButtonText: 'ลองใหม่อีกครั้ง'
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