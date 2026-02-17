<?php
session_start();
include_once("connectdb.php");

$id = isset($_GET['id']) ? $_GET['id'] : '';
$action = isset($_GET['action']) ? $_GET['action'] : '';

// ตรวจสอบว่ามีตะกร้าหรือยัง
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = array();
}

// --- ส่วนจัดการ Action ต่างๆ ---

if ($id != "") {
    if ($action == 'add' || $action == 'add_more') {
        // เพิ่มจำนวนสินค้า
        if (isset($_SESSION['cart'][$id])) {
            $_SESSION['cart'][$id]++;
        } else {
            $_SESSION['cart'][$id] = 1;
        }
    } 
    elseif ($action == 'reduce') {
        // ลดจำนวนสินค้า (แต่ไม่ให้ต่ำกว่า 1)
        if (isset($_SESSION['cart'][$id]) && $_SESSION['cart'][$id] > 1) {
            $_SESSION['cart'][$id]--;
        } else {
            unset($_SESSION['cart'][$id]); // ถ้าลดจนเหลือ 0 ให้ลบออก
        }
    } 
    elseif ($action == 'remove') {
        // ลบสินค้าชิ้นนั้นออก
        unset($_SESSION['cart'][$id]);
    }
}

if ($action == 'clear') {
    // ล้างตะกร้าทั้งหมด
    unset($_SESSION['cart']);
}

// --- ส่วนการตอบกลับ (Response) ---

// 1. ถ้าส่งมาแบบ AJAX (ใช้ร่วมกับปุ่มหยิบใส่ตะกร้าหน้าแรก)
if (isset($_GET['ajax'])) {
    $count = 0;
    if (isset($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $qty) {
            $count += $qty;
        }
    }
    echo $count; // ส่งจำนวนสินค้าที่นับได้กลับไปที่หน้า index.php
    exit;
}

// 2. ถ้าเป็นการกดปุ่มจากหน้า cart.php (เพิ่ม/ลด/ลบ) ให้กลับไปหน้าเดิมทันที
header("Location: cart.php");
exit;
?>