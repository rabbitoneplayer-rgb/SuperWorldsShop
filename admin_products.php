<?php
session_start();
include_once("connectdb.php");

// เช็คสิทธิ์แอดมิน
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
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root { --ss-red: #e12128; --ss-dark: #111111; --ss-blue: #0d6efd; }
        body { background-color: #f0f2f5; font-family: 'Kanit', sans-serif; color: #333; }
        .main-content { margin-top: 40px; margin-bottom: 80px; }
        .admin-card { border: none; border-radius: 25px; box-shadow: 0 15px 35px rgba(0,0,0,0.05); background: #fff; }
        .table thead { background-color: var(--ss-dark); color: white; }
        .table thead th { padding: 20px; font-weight: 600; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 1px; border: none; }
        .table tbody td { padding: 15px 20px; border-bottom: 1px solid #f1f1f1; vertical-align: middle; }
        .product-img-admin { width: 65px; height: 65px; object-fit: contain; background: #fff; border-radius: 12px; padding: 5px; border: 1px solid #eee; }
        
        /* สไตล์ Badge แยกสีตามเพศและหมวดหมู่ */
        .badge-cat { padding: 6px 12px; font-weight: 600; font-size: 0.65rem; text-transform: uppercase; border-radius: 50px; }
        .cat-men { background: #e3f2fd; color: #0d6efd; }
        .cat-women { background: #fce4ec; color: #d81b60; }
        .cat-gear { background: #f3e5f5; color: #7b1fa2; }
        .cat-default { background: #f8f9fa; color: #666; }

        .price-text { font-weight: 700; color: var(--ss-dark); }
        .search-box-admin { border-radius: 50px; padding-left: 40px; border: 2px solid #eee; height: 45px; }
        .btn-action { width: 38px; height: 38px; border-radius: 12px; display: inline-flex; align-items: center; justify-content: center; transition: 0.3s; border: none; }
        .btn-edit { background: #f0f7ff; color: #007bff; }
        .btn-edit:hover { background: #007bff; color: white; }
        .btn-del { background: #fff5f5; color: #dc3545; }
        .btn-del:hover { background: #dc3545; color: white; }
        
        .form-label-custom { font-weight: 700; font-size: 0.75rem; text-transform: uppercase; color: #999; margin-bottom: 8px; display: block; }
        .form-control-custom { border-radius: 15px; padding: 12px 18px; border: 2px solid #f0f0f0; transition: 0.3s; }
        .form-control-custom:focus { border-color: var(--ss-red); box-shadow: none; }
        .modal-content { border-radius: 30px; border: none; }
    </style>
</head>
<body>

<div class="container main-content">
    <div class="row align-items-center mb-5">
        <div class="col-lg-6">
            <h6 class="text-danger fw-bold text-uppercase mb-1" style="letter-spacing: 2px;">Inventory System</h6>
            <h2 class="fw-bold m-0 text-dark">จัดการสต็อกและหมวดหมู่</h2>
        </div>
        <div class="col-lg-6 mt-4 mt-lg-0 text-lg-end">
            <div class="d-flex flex-wrap justify-content-lg-end gap-3">
                <form action="" method="get" class="position-relative" style="min-width: 280px;">
                    <i class="fas fa-search position-absolute" style="left: 15px; top: 14px; color: #ccc;"></i>
                    <input type="text" name="admin_q" class="form-control search-box-admin" placeholder="ค้นหาชื่อสินค้าหรือแบรนด์..." value="<?php echo htmlspecialchars($admin_search); ?>">
                </form>
                <button class="btn btn-danger rounded-pill px-4 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#addProductModal">
                    <i class="fas fa-plus me-2"></i>เพิ่มสินค้าใหม่
                </button>
            </div>
        </div>
    </div>

    <div class="card admin-card overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">รูปสินค้า</th>
                        <th>ข้อมูลสินค้า</th>
                        <th class="text-center">ราคา</th>
                        <th class="text-center">หมวดหมู่/Tag</th>
                        <th class="text-end pe-4">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(mysqli_num_rows($result) > 0): ?>
                        <?php while($row = mysqli_fetch_array($result)): 
                            $p_cat = $row['p_category'];
                            // กำหนดสี Badge ตามหมวดหมู่
                            $badge_class = "cat-default";
                            if(strpos($p_cat, 'ชาย') !== false) $badge_class = "cat-men";
                            if(strpos($p_cat, 'หญิง') !== false) $badge_class = "cat-women";
                            if(strpos($p_cat, 'แร็คเกต') !== false || strpos($p_cat, 'กระเป๋า') !== false) $badge_class = "cat-gear";
                        ?>
                        <tr>
                            <td class="ps-4">
                                <img src="<?php echo $row['p_image']; ?>" class="product-img-admin shadow-sm" onerror="this.src='https://placehold.co/100x100?text=Order'">
                            </td>
                            <td>
                                <div class="fw-bold text-dark mb-0"><?php echo $row['p_name']; ?></div>
                                <div class="text-muted small text-uppercase fw-bold" style="font-size: 0.65rem;"><?php echo $row['p_brand']; ?></div>
                            </td>
                            <td class="text-center price-text">฿<?php echo number_format($row['p_price']); ?></td>
                            <td class="text-center">
                                <span class="badge badge-cat <?php echo $badge_class; ?>">
                                    <i class="fas fa-tag me-1"></i> <?php echo $p_cat; ?>
                                </span>
                            </td>
                            <td class="text-end pe-4">
                                <button class="btn-action btn-edit me-1" title="แก้ไขข้อมูล" 
                                        onclick="editProduct(<?php echo $row['p_id']; ?>, '<?php echo addslashes(htmlspecialchars($row['p_name'])); ?>', '<?php echo addslashes(htmlspecialchars($row['p_brand'])); ?>', <?php echo $row['p_price']; ?>, '<?php echo $row['p_category']; ?>')">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn-action btn-del" title="ลบสินค้า" onclick="deleteProduct(<?php echo $row['p_id']; ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center py-5 text-muted">ไม่พบรายการสินค้าที่ค้นหา</td></tr>
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
                <h4 class="fw-bold mb-0">เพิ่มสินค้าใหม่ลงสต็อก</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label-custom">ชื่อสินค้า</label>
                    <input type="text" name="p_name" class="form-control form-control-custom" placeholder="เช่น รองเท้าวิ่ง Men's Air Max" required>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label-custom">แบรนด์</label>
                        <input type="text" name="p_brand" class="form-control form-control-custom" placeholder="NIKE, YONEX..." required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label-custom">ราคา (บาท)</label>
                        <input type="number" name="p_price" class="form-control form-control-custom" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label-custom">เลือกหมวดหมู่ / ประเภทสินค้า</label>
                    <select name="p_category" class="form-select form-control-custom">
                        <optgroup label="รองเท้า (Shoes)">
                            <option value="รองเท้าชาย">รองเท้าผู้ชาย</option>
                            <option value="รองเท้าหญิง">รองเท้าผู้หญิง</option>
                            <option value="รองเท้าวิ่ง">รองเท้าวิ่ง (Unisex)</option>
                        </optgroup>
                        <optgroup label="เสื้อผ้า (Apparel)">
                            <option value="เสื้อผ้าชาย">เสื้อผ้าผู้ชาย</option>
                            <option value="เสื้อผ้าหญิง">เสื้อผ้าผู้หญิง</option>
                            <option value="ชุดวอร์ม">ชุดวอร์ม / แจ็คเก็ต</option>
                        </optgroup>
                        <optgroup label="อุปกรณ์กีฬา (Equipment)">
                            <option value="แร็คเกต">แร็คเกต (Badminton/Tennis)</option>
                            <option value="กระเป๋า">กระเป๋ากีฬา</option>
                            <option value="อุปกรณ์กีฬา">อุปกรณ์อื่นๆ</option>
                        </optgroup>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label-custom">รูปภาพหลัก</label>
                    <input type="file" name="p_image" class="form-control form-control-custom" accept="image/*" required>
                </div>
            </div>
            <div class="modal-footer border-0 p-4 pt-0">
                <button type="submit" class="btn btn-danger w-100 rounded-pill py-3 fw-bold shadow-sm">ยืนยันการเพิ่มสินค้า</button>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ฟังก์ชันแก้ไขสินค้า (ปรับปรุงหมวดหมู่)
function editProduct(id, name, brand, price, category) {
    Swal.fire({
        title: 'แก้ไขข้อมูลสินค้า',
        width: '500px',
        html: `
            <div class="text-start px-2">
                <label class="form-label-custom mt-3">ชื่อสินค้า</label>
                <input id="swal-name" class="form-control form-control-custom mb-3" value="${name}">
                <div class="row">
                    <div class="col-6">
                        <label class="form-label-custom">แบรนด์</label>
                        <input id="swal-brand" class="form-control form-control-custom mb-3" value="${brand}">
                    </div>
                    <div class="col-6">
                        <label class="form-label-custom">ราคา</label>
                        <input id="swal-price" type="number" class="form-control form-control-custom mb-3" value="${price}">
                    </div>
                </div>
                <label class="form-label-custom">หมวดหมู่</label>
                <select id="swal-category" class="form-select form-control-custom">
                    <option value="รองเท้าชาย" ${category === "รองเท้าชาย" ? "selected" : ""}>รองเท้าผู้ชาย</option>
                    <option value="รองเท้าหญิง" ${category === "รองเท้าหญิง" ? "selected" : ""}>รองเท้าผู้หญิง</option>
                    <option value="รองเท้าวิ่ง" ${category === "รองเท้าวิ่ง" ? "selected" : ""}>รองเท้าวิ่ง</option>
                    <option value="เสื้อผ้าชาย" ${category === "เสื้อผ้าชาย" ? "selected" : ""}>เสื้อผ้าผู้ชาย</option>
                    <option value="เสื้อผ้าหญิง" ${category === "เสื้อผ้าหญิง" ? "selected" : ""}>เสื้อผ้าผู้หญิง</option>
                    <option value="แร็คเกต" ${category === "แร็คเกต" ? "selected" : ""}>แร็คเกต</option>
                    <option value="กระเป๋า" ${category === "กระเป๋า" ? "selected" : ""}>กระเป๋า</option>
                    <option value="อุปกรณ์กีฬา" ${category === "อุปกรณ์กีฬา" ? "selected" : ""}>อุปกรณ์กีฬา</option>
                </select>
            </div>`,
        showCancelButton: true,
        confirmButtonText: 'บันทึกการเปลี่ยนแปลง',
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
            window.location.href = `admin_action.php?act=edit_full&id=${d.id}&name=${encodeURIComponent(d.name)}&brand=${encodeURIComponent(d.brand)}&price=${d.price}&category=${encodeURIComponent(d.category)}`;
        }
    });
}

function deleteProduct(id) {
    Swal.fire({
        title: 'ยืนยันการลบสินค้า?',
        text: "ข้อมูลสินค้านี้จะถูกลบออกจากระบบถาวร",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e12128',
        cancelButtonColor: '#111',
        confirmButtonText: 'ใช่, ลบออกตอนนี้',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `admin_action.php?act=delete&id=${id}`;
        }
    });
}
</script>
</body>
</html>
