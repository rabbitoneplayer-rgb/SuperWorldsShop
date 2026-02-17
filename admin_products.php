<?php
session_start();
include_once("connectdb.php");

// เช็คสิทธิ์แอดมิน (เพิ่มความปลอดภัย)
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: login.php");
    exit();
}

$admin_search = isset($_GET['admin_q']) ? mysqli_real_escape_string($conn, $_GET['admin_q']) : '';
$sql = "SELECT * FROM products WHERE p_name LIKE '%$admin_search%' OR p_brand LIKE '%$admin_search%' ORDER BY p_id DESC";
$result = mysqli_query($conn, $sql);
?>
<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Stock Manager | SUPERWORLDS Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root { --ss-red: #e12128; --ss-dark: #111111; --ss-blue: #0d6efd; }
        body { background-color: #f0f2f5; font-family: 'Segoe UI', sans-serif; color: #333; }
        .main-content { margin-top: 40px; margin-bottom: 80px; }
        .admin-card { border: none; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.04); background: #fff; }
        .table thead { background-color: var(--ss-dark); color: white; border-radius: 15px 15px 0 0; }
        .table thead th { padding: 20px; font-weight: 600; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 1px; border: none; }
        .table tbody td { padding: 18px 20px; border-bottom: 1px solid #f1f1f1; vertical-align: middle; }
        .product-img-admin { width: 70px; height: 70px; object-fit: contain; background: #fff; border-radius: 12px; padding: 5px; border: 1px solid #f0f0f0; }
        .badge-cat { padding: 6px 14px; font-weight: 700; font-size: 0.7rem; text-transform: uppercase; border-radius: 50px; background: #e9ecef; color: #495057; }
        .price-text { font-weight: 800; color: var(--ss-red); font-size: 1.1rem; }
        .search-box-admin { border-radius: 50px; padding-left: 40px; border: 2px solid #eee; height: 42px; font-size: 0.9rem; }
        .btn-action { width: 36px; height: 36px; border-radius: 10px; display: inline-flex; align-items: center; justify-content: center; transition: 0.3s; margin: 0 2px; border: none; }
        .btn-edit { background: #e7f1ff; color: var(--ss-blue); }
        .btn-del { background: #fff5f5; color: var(--ss-red); }
        .form-label-custom { font-weight: 700; font-size: 0.75rem; text-transform: uppercase; color: #888; margin-bottom: 8px; }
        .form-control-custom { border-radius: 12px; padding: 12px; border: 2px solid #eee; }
    </style>
</head>
<body>

<div class="container main-content">
    <div class="row align-items-center mb-5">
        <div class="col-lg-6">
            <h6 class="text-primary fw-bold text-uppercase mb-1" style="letter-spacing: 2px;">Inventory Manager</h6>
            <h2 class="fw-bold m-0 text-dark">ระบบจัดการสต็อกสินค้า</h2>
        </div>
        <div class="col-lg-6 mt-3 mt-lg-0 text-lg-end">
            <div class="d-flex flex-wrap justify-content-lg-end gap-2">
                <form action="" method="get" class="position-relative" style="max-width: 300px;">
                    <i class="fas fa-search position-absolute" style="left: 15px; top: 12px; color: #aaa;"></i>
                    <input type="text" name="admin_q" class="form-control search-box-admin" placeholder="ค้นหาชื่อหรือแบรนด์..." value="<?php echo htmlspecialchars($admin_search); ?>">
                </form>
                <a href="index.php" class="btn btn-dark rounded-pill px-4 shadow-sm"><i class="fas fa-external-link-alt me-2"></i>หน้าเว็บ</a>
                <button class="btn btn-danger rounded-pill px-4 shadow-sm fw-bold" data-bs-toggle="modal" data-bs-target="#addProductModal">
                    <i class="fas fa-plus-circle me-2"></i>เพิ่มสินค้า
                </button>
            </div>
        </div>
    </div>

    <div class="card admin-card overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">สินค้า</th>
                        <th>ชื่อ & แบรนด์</th>
                        <th class="text-center">ราคา</th>
                        <th class="text-center">หมวดหมู่</th>
                        <th class="text-end pe-4">ดำเนินการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(mysqli_num_rows($result) > 0): ?>
                        <?php while($row = mysqli_fetch_array($result)): ?>
                        <tr>
                            <td class="ps-4">
                                <img src="<?php echo $row['p_image']; ?>" class="product-img-admin shadow-sm" onerror="this.src='https://placehold.co/100x100?text=No+Img'">
                            </td>
                            <td>
                                <div class="fw-bold text-dark mb-0" style="font-size: 0.95rem;"><?php echo $row['p_name']; ?></div>
                                <div class="text-muted small text-uppercase fw-bold" style="font-size: 0.7rem;"><?php echo $row['p_brand']; ?></div>
                            </td>
                            <td class="text-center price-text">฿<?php echo number_format($row['p_price']); ?></td>
                            <td class="text-center">
                                <span class="badge badge-cat"><?php echo $row['p_category']; ?></span>
                            </td>
                            <td class="text-end pe-4">
                                <button class="btn-action btn-edit" title="แก้ไข"
                                        onclick="editProduct(<?php echo $row['p_id']; ?>, '<?php echo addslashes(htmlspecialchars($row['p_name'])); ?>', '<?php echo addslashes(htmlspecialchars($row['p_brand'])); ?>', <?php echo $row['p_price']; ?>, '<?php echo $row['p_category']; ?>')">
                                    <i class="fas fa-pen"></i>
                                </button>
                                <button class="btn-action btn-del" title="ลบ" onclick="deleteProduct(<?php echo $row['p_id']; ?>)">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center py-5 text-muted">ไม่พบรายการสินค้า</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="addProductModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="admin_action.php?act=add" method="POST" enctype="multipart/form-data" class="modal-content">
            <div class="modal-header border-0 p-4 pb-0">
                <h4 class="fw-bold mb-0 text-dark">สร้างรายการใหม่</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label-custom">ชื่อสินค้า</label>
                    <input type="text" name="p_name" class="form-control form-control-custom" required>
                </div>
                <div class="mb-3">
                    <label class="form-label-custom">แบรนด์</label>
                    <input type="text" name="p_brand" class="form-control form-control-custom" required>
                </div>
                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="form-label-custom">ราคา (บาท)</label>
                        <input type="number" name="p_price" class="form-control form-control-custom" required>
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label-custom">หมวดหมู่</label>
                        <select name="p_category" class="form-select form-control-custom">
                            <option value="รองเท้า">รองเท้า</option>
                            <option value="เสื้อผ้า">เสื้อผ้า</option>
                            <option value="อุปกรณ์กีฬา">อุปกรณ์กีฬา</option>
                        </select>
                    </div>
                </div>
                <div class="mb-1">
                    <label class="form-label-custom">อัปโหลดรูปภาพ</label>
                    <input type="file" name="p_image" class="form-control form-control-custom" accept="image/*" required>
                </div>
            </div>
            <div class="modal-footer border-0 p-4 pt-0">
                <button type="submit" class="btn btn-danger w-100 rounded-pill py-3 fw-bold shadow-sm">บันทึกสินค้าลงสต็อก</button>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function editProduct(id, name, brand, price, category) {
    Swal.fire({
        title: 'แก้ไขข้อมูลสินค้า',
        html: `
            <div class="text-start px-3">
                <label class="form-label-custom d-block mt-3">ชื่อสินค้า</label>
                <input id="swal-name" class="form-control form-control-custom w-100" value="${name}">
                <label class="form-label-custom d-block mt-3">แบรนด์</label>
                <input id="swal-brand" class="form-control form-control-custom w-100" value="${brand}">
                <div class="row mt-3">
                    <div class="col-6">
                        <label class="form-label-custom d-block">ราคา</label>
                        <input id="swal-price" type="number" class="form-control form-control-custom w-100" value="${price}">
                    </div>
                    <div class="col-6">
                        <label class="form-label-custom d-block">หมวดหมู่</label>
                        <select id="swal-category" class="form-select form-control-custom w-100">
                            <option value="รองเท้า" ${category === "รองเท้า" ? "selected" : ""}>รองเท้า</option>
                            <option value="เสื้อผ้า" ${category === "เสื้อผ้า" ? "selected" : ""}>เสื้อผ้า</option>
                            <option value="อุปกรณ์กีฬา" ${category === "อุปกรณ์กีฬา" ? "selected" : ""}>อุปกรณ์กีฬา</option>
                        </select>
                    </div>
                </div>
            </div>`,
        showCancelButton: true,
        confirmButtonText: 'บันทึกการแก้ไข',
        confirmButtonColor: '#111',
        cancelButtonText: 'ยกเลิก',
        preConfirm: () => {
            const n = document.getElementById('swal-name').value;
            const b = document.getElementById('swal-brand').value;
            const p = document.getElementById('swal-price').value;
            const c = document.getElementById('swal-category').value;
            if (!n || !b || !p) { Swal.showValidationMessage(`กรุณากรอกข้อมูลให้ครบถ้วน`); return false; }
            return { id, name: n, brand: b, price: p, category: c }
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const d = result.value;
            // ส่งค่าผ่าน URL ไปที่ admin_action.php (GET)
            window.location.href = `admin_action.php?act=edit_full&id=${d.id}&name=${encodeURIComponent(d.name)}&brand=${encodeURIComponent(d.brand)}&price=${d.price}&category=${encodeURIComponent(d.category)}`;
        }
    });
}

function deleteProduct(id) {
    Swal.fire({
        title: 'ยืนยันการลบ?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e12128',
        confirmButtonText: 'ใช่, ลบทิ้ง'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `admin_action.php?act=delete&id=${id}`;
        }
    });
}
</script>
</body>
</html>