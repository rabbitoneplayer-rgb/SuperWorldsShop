<?php
session_start();
include_once("connectdb.php");

// 1. เช็คสิทธิ์แอดมิน
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
    $p_gender = mysqli_real_escape_string($conn, $_POST['p_gender'] ?? 'Unisex');
    $p_detail = mysqli_real_escape_string($conn, $_POST['p_detail'] ?? '');

    if (!empty($_FILES['p_image']['name'])) {
        $filename = $_FILES['p_image']['name'];
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        $new_name = "product_" . time() . "." . $ext; 
        $target = "img/" . $new_name;
        if (!is_dir('img')) { mkdir('img', 0777, true); }

        if (move_uploaded_file($_FILES['p_image']['tmp_name'], $target)) {
            $sql = "INSERT INTO products (p_name, p_brand, p_price, p_category, p_gender, p_image, p_detail) 
                    VALUES ('$p_name', '$p_brand', '$p_price', '$p_category', '$p_gender', '$target', '$p_detail')";
            mysqli_query($conn, $sql);
        }
    }
    header("Location: admin_products.php");
    exit();
}

// --- 2. อัปเดตรูปภาพสินค้าหลายรูป (ฉบับสมบูรณ์ - ไม่ลบของเก่าที่มีอยู่) ---
if ($act == 'update_images') {
    $p_id = mysqli_real_escape_string($conn, $_POST['p_id']);
    $update_parts = [];
    
    // รายชื่อฟิลด์รูปภาพใน Table products
    $img_fields = ['p_image', 'p_img2', 'p_img3', 'p_img4', 'p_img5'];
    
    // สร้างโฟลเดอร์ img ถ้ายังไม่มี
    if (!is_dir('img')) { mkdir('img', 0777, true); }

    foreach ($img_fields as $f) {
        // ตรวจสอบว่ามีการอัปโหลดไฟล์ใหม่ในช่องนั้นๆ หรือไม่
        if (isset($_FILES[$f]) && $_FILES[$f]['error'] == 0) {
            
            // ลบรูปเก่าออกก่อนเพื่อประหยัดพื้นที่ (เลือกทำได้)
            $res = mysqli_query($conn, "SELECT $f FROM products WHERE p_id = '$p_id'");
            $old_row = mysqli_fetch_array($res);
            if (!empty($old_row[$f]) && file_exists($old_row[$f])) {
                unlink($old_row[$f]);
            }

            $ext = strtolower(pathinfo($_FILES[$f]["name"], PATHINFO_EXTENSION));
            $filename = "prod_" . $p_id . "_" . $f . "_" . time() . "." . $ext;
            $target = "img/" . $filename;
            
            if (move_uploaded_file($_FILES[$f]["tmp_name"], $target)) {
                $update_parts[] = "$f = '$target'";
            }
        }
    }
    
    if (!empty($update_parts)) {
        $sql = "UPDATE products SET " . implode(", ", $update_parts) . " WHERE p_id = '$p_id'";
        if (mysqli_query($conn, $sql)) {
            echo "success";
        } else {
            echo "Database Error: " . mysqli_error($conn);
        }
    } else {
        echo "success"; // กรณีไม่ได้เลือกรูปใหม่เลย แต่กดบันทึก
    }
    exit();
}

// --- 3. แก้ไขข้อมูลสินค้าเบื้องต้น ---
if ($act == 'edit_full') {
    $p_id = mysqli_real_escape_string($conn, $_REQUEST['id']);
    $p_name = mysqli_real_escape_string($conn, $_REQUEST['name']);
    $p_brand = mysqli_real_escape_string($conn, $_REQUEST['brand']);
    $p_price = (int)$_REQUEST['price'];
    $p_category = mysqli_real_escape_string($conn, $_REQUEST['category']);
    $p_gender = mysqli_real_escape_string($conn, $_REQUEST['gender'] ?? 'Unisex');

    $sql = "UPDATE products SET p_name = '$p_name', p_brand = '$p_brand', p_price = '$p_price', p_category = '$p_category', p_gender = '$p_gender' WHERE p_id = '$p_id'";
    mysqli_query($conn, $sql);
    header("Location: admin_products.php");
    exit();
}

// --- 4. จัดการไซส์และตัวเลือก (AJAX) ---
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

// --- 5. แก้ไขรายละเอียดสินค้า (AJAX) ---
if ($act == 'update_detail') {
    $p_id = mysqli_real_escape_string($conn, $_POST['p_id']);
    $p_detail = mysqli_real_escape_string($conn, $_POST['p_detail']);
    if (mysqli_query($conn, "UPDATE products SET p_detail = '$p_detail' WHERE p_id = '$p_id'")) { echo 'success'; } 
    else { echo 'error'; }
    exit();
}

// --- 6. อัปเดตสถานะออเดอร์ ---
if ($act == 'update_status') {
    $o_id = mysqli_real_escape_string($conn, $_GET['id']);
    mysqli_query($conn, "UPDATE orders SET o_status = 'ส่งแล้ว' WHERE o_id = '$o_id'");
    header("Location: admin_orders.php");
    exit();
}

// --- 7. ลบสินค้า ---
if ($act == 'delete') {
    $p_id = mysqli_real_escape_string($conn, $_GET['id']);
    $res = mysqli_query($conn, "SELECT p_image, p_img2, p_img3, p_img4, p_img5 FROM products WHERE p_id = '$p_id'");
    $row = mysqli_fetch_array($res);
    $imgs = ['p_image', 'p_img2', 'p_img3', 'p_img4', 'p_img5'];
    foreach($imgs as $img) {
        if (!empty($row[$img]) && file_exists($row[$img])) { unlink($row[$img]); }
    }
    mysqli_query($conn, "DELETE FROM products WHERE p_id = '$p_id'");
    header("Location: admin_products.php");
    exit();
}

// --- 8. จัดการข้อมูลลูกค้า ---
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

if ($act == 'delete_user') {
    $id = mysqli_real_escape_string($conn, $_GET['id']);
    mysqli_query($conn, "DELETE FROM users WHERE id = '$id' AND is_admin != 1");
    header("Location: admin_customers.php");
    exit();
}
// --- 9. ลบรูปภาพเฉพาะฟิลด์ (AJAX) ---
if ($act == 'delete_image') {
    // รับค่า ID สินค้า และชื่อฟิลด์ที่ต้องการลบ (เช่น p_img2)
    $p_id = mysqli_real_escape_string($conn, $_POST['p_id']);
    $field = mysqli_real_escape_string($conn, $_POST['field']);
    
    // ตรวจสอบชื่อฟิลด์เพื่อความปลอดภัย ป้องกันการส่งค่ามั่วมาลบข้อมูลส่วนอื่น
    $allowed_fields = ['p_image', 'p_img2', 'p_img3', 'p_img4', 'p_img5'];
    if (!in_array($field, $allowed_fields)) { 
        exit("Invalid field name"); 
    }

    // 1. ดึงข้อมูลรูปภาพปัจจุบันจากฐานข้อมูล
    $res = mysqli_query($conn, "SELECT $field FROM products WHERE p_id = '$p_id'");
    $row = mysqli_fetch_array($res);
    
    // 2. ถ้ามีไฟล์จริงอยู่ใน Server ให้สั่งลบทิ้ง (Unlink) เพื่อประหยัดพื้นที่
    if (!empty($row[$field]) && file_exists($row[$field])) {
        unlink($row[$field]);
    }

    // 3. อัปเดตค่าในฐานข้อมูลในฟิลด์นั้นให้เป็นค่าว่าง
    $sql = "UPDATE products SET $field = '' WHERE p_id = '$p_id'";
    if (mysqli_query($conn, $sql)) {
        echo 'success';
    } else {
        echo 'Database Error: ' . mysqli_error($conn);
    }
    exit();
}
?>

