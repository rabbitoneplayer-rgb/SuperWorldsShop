<?php
session_start();
include_once("connectdb.php");

// 1. เช็คสิทธิ์แอดมินเบื้องต้น
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    exit("Access Denied");
}

$act = $_GET['act'] ?? '';

// --- 1. เพิ่มสินค้าใหม่ (รองรับ p_gender) ---
if ($act == 'add') {
    $p_name = mysqli_real_escape_string($conn, $_POST['p_name']);
    $p_brand = mysqli_real_escape_string($conn, $_POST['p_brand']);
    $p_price = $_POST['p_price'];
    $p_category = $_POST['p_category'];
    // เพิ่มการรับค่าเพศจากฟอร์ม
    $p_gender = mysqli_real_escape_string($conn, $_POST['p_gender'] ?? 'Unisex');
    $p_detail = mysqli_real_escape_string($conn, $_POST['p_detail'] ?? '');

    if (!empty($_FILES['p_image']['name'])) {
        $filename = $_FILES['p_image']['name'];
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        $new_name = "product_" . time() . "." . $ext; 
        $target = "img/" . $new_name;
        if (!is_dir('img')) { mkdir('img', 0777, true); }

        if (move_uploaded_file($_FILES['p_image']['tmp_name'], $target)) {
            // เพิ่ม p_gender เข้าไปในคำสั่ง INSERT
            $sql = "INSERT INTO products (p_name, p_brand, p_price, p_category, p_gender, p_image, p_detail) 
                    VALUES ('$p_name', '$p_brand', '$p_price', '$p_category', '$p_gender', '$target', '$p_detail')";
            mysqli_query($conn, $sql);
        }
    }
    header("Location: admin_products.php");
    exit();
}

// --- 2. แก้ไขข้อมูลสินค้า (รองรับ p_gender) ---
if ($act == 'edit_full') {
    $p_id = mysqli_real_escape_string($conn, $_REQUEST['id']);
    $p_name = mysqli_real_escape_string($conn, $_REQUEST['name']);
    $p_brand = mysqli_real_escape_string($conn, $_REQUEST['brand']);
    $p_price = (int)$_REQUEST['price'];
    $p_category = mysqli_real_escape_string($conn, $_REQUEST['category']);
    // เพิ่มการรับค่าเพศที่ส่งมาจาก URL (GET)
    $p_gender = mysqli_real_escape_string($conn, $_REQUEST['gender'] ?? 'Unisex');

    if (!empty($_FILES['p_image']['name'])) {
        $res = mysqli_query($conn, "SELECT p_image FROM products WHERE p_id = '$p_id'");
        $row = mysqli_fetch_array($res);
        if ($row['p_image'] && file_exists($row['p_image'])) { unlink($row['p_image']); }

        $filename = $_FILES['p_image']['name'];
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        $new_name = "product_" . time() . "." . $ext;
        $target = "img/" . $new_name;
        
        if (move_uploaded_file($_FILES['p_image']['tmp_name'], $target)) {
            // อัปเดตพร้อมรูปภาพและเพศ
            $sql = "UPDATE products SET p_name = '$p_name', p_brand = '$p_brand', p_price = '$p_price', p_category = '$p_category', p_gender = '$p_gender', p_image = '$target' WHERE p_id = '$p_id'";
        }
    } else {
        // อัปเดตเฉพาะข้อมูลและเพศ (ไม่เปลี่ยนรูป)
        $sql = "UPDATE products SET p_name = '$p_name', p_brand = '$p_brand', p_price = '$p_price', p_category = '$p_category', p_gender = '$p_gender' WHERE p_id = '$p_id'";
    }
    
    mysqli_query($conn, $sql);
    header("Location: admin_products.php");
    exit();
}

// --- 3. จัดการไซส์และสี (Update Variants ผ่าน AJAX) ---
if ($act == 'update_variants') {
    $p_id = mysqli_real_escape_string($conn, $_POST['p_id']);
    $action = $_POST['action'];
    $value = trim($_POST['value']);
    $sql = "SELECT p_size FROM products WHERE p_id = '$p_id'";
    $res = mysqli_query($conn, $sql);
    $row = mysqli_fetch_array($res);
    $options = !empty($row['p_size']) ? array_filter(array_map('trim', explode(',', $row['p_size']))) : [];

    if ($action == 'add' && !in_array($value, $options) && $value != "") { $options[] = $value; } 
    elseif ($action == 'remove') { $options = array_diff($options, [$value]); }

    $new_size_str = implode(',', $options);
    if (mysqli_query($conn, "UPDATE products SET p_size = '$new_size_str' WHERE p_id = '$p_id'")) { echo 'success'; } 
    else { echo 'error'; }
    exit();
}

// --- 4. แก้ไขรายละเอียดสินค้า (AJAX) ---
if ($act == 'update_detail') {
    $p_id = mysqli_real_escape_string($conn, $_POST['p_id']);
    $p_detail = mysqli_real_escape_string($conn, $_POST['p_detail']);
    if (mysqli_query($conn, "UPDATE products SET p_detail = '$p_detail' WHERE p_id = '$p_id'")) { echo 'success'; } 
    else { echo 'error'; }
    exit();
}

// --- 5. อัปเดตสถานะออเดอร์ ---
if ($act == 'update_status') {
    $o_id = mysqli_real_escape_string($conn, $_GET['id']);
    mysqli_query($conn, "UPDATE orders SET o_status = 'ส่งแล้ว' WHERE o_id = '$o_id'");
    header("Location: admin_orders.php");
    exit();
}

// --- 6. ลบสินค้า ---
if ($act == 'delete') {
    $p_id = mysqli_real_escape_string($conn, $_GET['id']);
    $res = mysqli_query($conn, "SELECT p_image FROM products WHERE p_id = '$p_id'");
    $row = mysqli_fetch_array($res);
    if ($row['p_image'] && file_exists($row['p_image'])) { unlink($row['p_image']); }
    mysqli_query($conn, "DELETE FROM products WHERE p_id = '$p_id'");
    header("Location: admin_products.php");
    exit();
}

// --- 7. แก้ไขข้อมูลลูกค้า ---
if ($act == 'edit_user') {
    $id = mysqli_real_escape_string($conn, $_REQUEST['id']); 
    $fullname = mysqli_real_escape_string($conn, $_REQUEST['name']);
    $email = mysqli_real_escape_string($conn, $_REQUEST['email']);
    $phone = mysqli_real_escape_string($conn, $_REQUEST['phone']);

    $sql = "UPDATE users SET fullname = '$fullname', email = '$email', phone = '$phone' WHERE id = '$id'";
    mysqli_query($conn, $sql);
    header("Location: admin_customers.php");
    exit();
}

// --- 8. ลบลูกค้า ---
if ($act == 'delete_user') {
    $id = mysqli_real_escape_string($conn, $_GET['id']);
    mysqli_query($conn, "DELETE FROM users WHERE id = '$id' AND is_admin != 1");
    header("Location: admin_customers.php");
    exit();
}

// --- 9. อัปโหลดรูปภาพเพิ่มเติม (สูงสุด 5 รูป) ---
if ($act == 'upload_more') {
    $p_id = mysqli_real_escape_string($conn, $_POST['p_id']);
    
    if (!empty($_FILES['p_image']['name'])) {
        $res = mysqli_query($conn, "SELECT p_img2, p_img3, p_img4, p_img5 FROM products WHERE p_id = '$p_id'");
        $row = mysqli_fetch_array($res);
        
        $target_column = '';
        if (empty($row['p_img2'])) $target_column = 'p_img2';
        elseif (empty($row['p_img3'])) $target_column = 'p_img3';
        elseif (empty($row['p_img4'])) $target_column = 'p_img4';
        elseif (empty($row['p_img5'])) $target_column = 'p_img5';
        
        if ($target_column == '') {
            exit("รูปภาพเต็มแล้ว (สูงสุด 5 รูป)");
        }

        $ext = pathinfo($_FILES['p_image']['name'], PATHINFO_EXTENSION);
        $new_name = "prod_" . $p_id . "_" . time() . "." . $ext;
        $target = "img/" . $new_name;

        if (move_uploaded_file($_FILES['p_image']['tmp_name'], $target)) {
            $sql = "UPDATE products SET $target_column = '$target' WHERE p_id = '$p_id'";
            if (mysqli_query($conn, $sql)) {
                echo 'success';
            }
        }
    }
    exit();
}
?>
