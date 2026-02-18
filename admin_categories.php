<?php
session_start();
include_once("connectdb.php");

// 1. เช็คสิทธิ์แอดมิน
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) { 
    header("Location: login.php"); 
    exit; 
}

// 2. Logic การลบ Tag
if(isset($_GET['del_id'])) {
    $id = mysqli_real_escape_string($conn, $_GET['del_id']);
    mysqli_query($conn, "DELETE FROM categories WHERE cat_id = '$id'");
    header("Location: admin_categories.php");
    exit();
}

// 3. Logic การเพิ่ม Tag
if(isset($_POST['add_cat'])) {
    $name = mysqli_real_escape_string($conn, $_POST['cat_name']);
    $group = mysqli_real_escape_string($conn, $_POST['cat_group']);
    if(!empty($name)) {
        mysqli_query($conn, "INSERT INTO categories (cat_name, cat_group) VALUES ('$name', '$group')");
    }
    header("Location: admin_categories.php");
    exit();
}

// 4. ดึงข้อมูลรายการ Tag ทั้งหมดมาแสดงในตาราง
$res = mysqli_query($conn, "SELECT * FROM categories ORDER BY cat_group DESC, cat_name ASC");
?>
<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>จัดการหมวดหมู่ | Admin SUPERWORLDS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { font-family: 'Kanit', sans-serif; background: #f0f2f5; color: #333; }
        .main-card { border: none; border-radius: 25px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); background: #fff; }
        .table thead { background-color: #111; color: white; }
        .table thead th { padding: 18px; font-weight: 600; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 1px; border: none; }
        .table tbody td { padding: 15px 18px; border-bottom: 1px solid #f1f1f1; vertical-align: middle; }
        .badge-group { padding: 6px 12px; font-weight: 700; font-size: 0.65rem; border-radius: 50px; text-transform: uppercase; }
        .group-SHOES { background: #e7f1ff; color: #0d6efd; }
        .group-APPAREL { background: #fff5f5; color: #e12128; }
        .group-GEAR { background: #f3e5f5; color: #7b1fa2; }
        .form-label-custom { font-weight: 700; font-size: 0.75rem; text-transform: uppercase; color: #888; margin-bottom: 8px; display: block; }
        .form-control-custom { border-radius: 15px; padding: 12px 18px; border: 2px solid #eee; transition: 0.3s; }
        .form-control-custom:focus { border-color: #e12128; box-shadow: none; }
    </style>
</head>
<body>

<div class="container my-5">
    <div class="row align-items-center mb-5">
        <div class="col-md-6">
            <h6 class="text-danger fw-bold text-uppercase mb-1" style="letter-spacing: 2px;">Menu Manager</h6>
            <h2 class="fw-bold m-0">🛠 จัดการ Tag หมวดหมู่</h2>
        </div>
        <div class="col-md-6 text-md-end mt-3 mt-md-0">
            <a href="admin_products.php" class="btn btn-dark rounded-pill px-4 fw-bold shadow-sm">
                <i class="fas fa-boxes me-2"></i>กลับหน้าจัดการสต็อก
            </a>
        </div>
    </div>

    <div class="card main-card p-4 mb-4">
        <form method="POST" class="row g-3 align-items-end">
            <div class="col-md-5">
                <label class="form-label-custom">ชื่อ Tag (เช่น "แบดมินตัน", "เด็ก", "Limited")</label>
                <input type="text" name="cat_name" class="form-control form-control-custom" placeholder="กรอกชื่อหมวดหมู่ย่อย..." required>
            </div>
            <div class="col-md-4">
                <label class="form-label-custom">แสดงภายใต้กลุ่มหลัก</label>
                <select name="cat_group" class="form-select form-control-custom">
                    <option value="SHOES">รองเท้า (SHOES)</option>
                    <option value="APPAREL">เสื้อผ้า (APPAREL)</option>
                    <option value="GEAR">อุปกรณ์ (GEAR)</option>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" name="add_cat" class="btn btn-danger w-100 rounded-pill py-3 fw-bold shadow-sm">
                    <i class="fas fa-plus-circle me-2"></i>เพิ่ม Tag ใหม่
                </button>
            </div>
        </form>
    </div>

    <div class="card main-card overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">กลุ่มหลัก</th>
                        <th>ชื่อ Tag (หมวดหมู่ย่อย)</th>
                        <th class="text-end pe-4">ดำเนินการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(mysqli_num_rows($res) > 0): ?>
                        <?php while($row = mysqli_fetch_array($res)): ?>
                        <tr>
                            <td class="ps-4">
                                <span class="badge-group group-<?= $row['cat_group'] ?>">
                                    <?= $row['cat_group'] ?>
                                </span>
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?= $row['cat_name'] ?></div>
                                <div class="text-muted small">Path: <?= $row['cat_group'] ?> > <?= $row['cat_name'] ?></div>
                            </td>
                            <td class="text-end pe-4">
                                <button onclick="confirmDelete(<?= $row['cat_id'] ?>, '<?= $row['cat_name'] ?>')" class="btn btn-outline-danger btn-sm rounded-pill px-3 fw-bold">
                                    <i class="fas fa-trash-alt me-1"></i> ลบทิ้ง
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" class="text-center py-5 text-muted">
                                <i class="fas fa-tag fa-3x mb-3 opacity-20"></i>
                                <p>ยังไม่มีการเพิ่ม Tag หมวดหมู่สินค้า</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function confirmDelete(id, name) {
    Swal.fire({
        title: 'ยืนยันการลบ?',
        text: "คุณกำลังจะลบ Tag: " + name,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e12128',
        cancelButtonColor: '#111',
        confirmButtonText: 'ใช่, ลบทิ้ง!',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'admin_categories.php?del_id=' + id;
        }
    });
}
</script>

</body>
</html>
