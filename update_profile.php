<?php
session_start();
include_once("connectdb.php");

// ตรวจสอบว่ามีการล็อกอินและส่งข้อมูลแบบ POST หรือไม่
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    
    // 1. รับค่าและป้องกัน SQL Injection
    $user_id = mysqli_real_escape_string($conn, $_POST['user_id']);
    $fullname = mysqli_real_escape_string($conn, $_POST['fullname']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);

    // 2. จัดการเรื่องรูปภาพโปรไฟล์
    $img_sql = "";
    if (!empty($_FILES['u_img']['name'])) {
        $filename = $_FILES['u_img']['name'];
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        
        // สุ่มชื่อไฟล์ใหม่เพื่อป้องกันชื่อซ้ำ
        $new_name = "user_" . $user_id . "_" . time() . "." . $ext;
        $target = "img/profiles/" . $new_name;
        
        // สร้างโฟลเดอร์ถ้ายังไม่มี
        if (!is_dir('img/profiles')) { 
            mkdir('img/profiles', 0777, true); 
        }

        // ย้ายไฟล์ไปยังตำแหน่งที่ต้องการ
        if (move_uploaded_file($_FILES['u_img']['tmp_name'], $target)) {
            // เตรียมคำสั่ง SQL สำหรับอัปเดตรูปภาพ
            $img_sql = ", u_img = '$target'";
            
            // ลบรูปภาพเก่าออก (ถ้ามี) เพื่อไม่ให้เปลืองพื้นที่ server
            $res_old = mysqli_query($conn, "SELECT u_img FROM users WHERE id = '$user_id'");
            $old_data = mysqli_fetch_assoc($res_old);
            if (!empty($old_data['u_img']) && file_exists($old_data['u_img'])) {
                unlink($old_data['u_img']);
            }

            // อัปเดตข้อมูลรูปใน Session ทันที
            $_SESSION['u_img'] = $target;
        }
    }

    // 3. อัปเดตข้อมูลลงฐานข้อมูล
    $sql = "UPDATE users SET 
            fullname = '$fullname', 
            email = '$email', 
            phone = '$phone', 
            address = '$address' 
            $img_sql
            WHERE id = '$user_id'";

    if (mysqli_query($conn, $sql)) {
        // อัปเดตชื่อใน Session เพื่อให้หน้าเว็บเปลี่ยนตามทันที
        $_SESSION['fullname'] = $fullname; 
        echo "success";
    } else {
        echo "เกิดข้อผิดพลาดในการบันทึก: " . mysqli_error($conn);
    }

} else {
    // ป้องกันการเข้าถึงไฟล์โดยตรง
    echo "Access Denied";
}
?>