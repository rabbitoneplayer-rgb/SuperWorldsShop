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
        :root { --ss-red: #e12128; --ss-dark: #111111; }
        body { background-color: #f0f2f5; font-family: 'Kanit', sans-serif; color: #333; }
        .admin-card { border: none; border-radius: 25px; box-shadow: 0 15px 35px rgba(0,0,0,0.05); background: #fff; }
        .table thead { background-color: var(--ss-dark); color: white; }
        .table thead th { padding: 20px; font-weight: 600; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 1px; border: none; }
        .table tbody td { padding: 15px 20px; border-bottom: 1px solid #f1f1f1; vertical-align: middle; }
        .product-img-admin { width: 65px; height: 65px; object-fit: contain; background: #fff; border-radius: 12px; padding: 5px; border: 1px solid #eee; }
        
        /* สไตล์ Badge แยกสี */
        .badge-cat { padding: 6px 12px; font-weight: 600; font-size: 0.65rem; border-radius: 50px; }
        .tag-men { background: #111; color: #fff; }
        .tag-women { background: #ff4d94; color: #fff; }
        .tag-unisex { background: #eee; color: #666; }

        .form-label-custom { font-weight: 700; font-size: 0.75rem; text-transform: uppercase; color: #999; margin-bottom: 8px; display: block; }
        .form-control-custom { border-radius: 15px; padding: 12px 18px; border: 2px solid #f0f0f0; transition: 0.3s; }
        .form-control-custom:focus { border-color: var(--ss-red); box-shadow: none; }
        .modal-content { border-radius: 30px; border: none; }
        .btn-action { width: 38px; height: 38px; border-radius: 12px; display: inline-flex; align-items: center; justify-content: center; transition: 0.3s; border: none; }
        .btn-edit { background: #f0f7ff; color: #007bff; }
        .btn-del { background: #fff5f5; color: #dc3545; }
    </style>
</head>
<body>

<div class="container my-5">
    <div class="row align-items-center mb-5">
        <div class="col-lg-6">
            <h2 class="fw-bold m-0 text-dark">ระบบจัดการสต็อกสินค้า</h2>
        </div>
        <div class="col-lg-6 text-lg-end mt-3 mt-lg-0">
            <button class="btn btn-danger rounded-pill px-4 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#addProductModal">
                <i class="fas fa-plus me-2"></i>เพิ่มสินค้าใหม่
            </button>
        </div>
    </div>

    <div class="card admin-card overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">สินค้า</th>
                        <th>รายละเอียด</th>
                        <th class="text-center">หมวดหมู่</th>
                        <th class="text-center">สำหรับ</th>
                        <th class="text-end pe-4">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = mysqli_fetch_array($result)): 
                        $gender = $row['p_gender'] ?? 'Unisex'; 
                        $tag_class = ($gender == 'ชาย') ? 'tag-men' : (($gender == 'หญิง') ? 'tag-women' : 'tag-unisex');
                    ?>
                    <tr>
                        <td class="ps-4"><img src="<?php echo $row['p_image']; ?>" class="product-img-admin"></td>
                        <td>
                            <div class="fw-bold"><?php echo $row['p_name']; ?></div>
                            <div class="text-muted small"><?php echo $row['p_brand']; ?> | ฿<?php echo number_format($row['p_price']); ?></div>
                        </td>
                        <td class="text-center"><span class="badge bg-light text-dark"><?php echo $row['p_category']; ?></span></td>
                        <td class="text-center"><span class="badge badge-cat <?php echo $tag_class; ?>"><?php echo $gender; ?></span></td>
                        <td class="text-end pe-4">
                            <button class="btn-action btn-edit me-1" onclick="editProduct(<?php echo $row['p_id']; ?>, '<?php echo addslashes($row['p_name']); ?>', '<?php echo $row['p_brand']; ?>', <?php echo $row['p_price']; ?>, '<?php echo $row['p_category']; ?>', '<?php echo $gender; ?>')">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn-action btn-del" onclick="deleteProduct(<?php echo $row['p_id']; ?>)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="addProductModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="admin_action.php?act=add" method="POST" enctype="multipart/form-data" class="modal-content">
            <div class="modal-header border-0 p-4 pb-0">
                <h4 class="fw-bold">เพิ่มสินค้าลงสต็อก</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label-custom">ชื่อสินค้า</label>
                    <input type="text" name="p_name" class="form-control form-control-custom" required>
                </div>
                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="form-label-custom">หมวดหมู่</label>
                        <select name="p_category" class="form-select form-control-custom">
                            <option value="รองเท้า">รองเท้า</option>
                            <option value="เสื้อผ้า">เสื้อผ้า</option>
                            <option value="แร็คเกต">แร็คเกต</option>
                            <option value="กระเป๋า">กระเป๋า</option>
                        </select>
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label-custom">สินค้าสำหรับ</label>
                        <select name="p_gender" class="form-select form-control-custom">
                            <option value="ชาย">ผู้ชาย</option>
                            <option value="หญิง">ผู้หญิง</option>
                            <option value="Unisex">ทั่วไป (Unisex)</option>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label-custom">แบรนด์</label>
                    <input type="text" name="p_brand" class="form-control form-control-custom" required>
                </div>
                <div class="mb-3">
                    <label class="form-label-custom">ราคา</label>
                    <input type="number" name="p_price" class="form-control form-control-custom" required>
                </div>
                <div class="mb-1">
                    <label class="form-label-custom">รูปภาพ</label>
                    <input type="file" name="p_image" class="form-control form-control-custom" required>
                </div>
            </div>
            <div class="modal-footer border-0 p-4 pt-0">
                <button type="submit" class="btn btn-danger w-100 rounded-pill py-3 fw-bold">บันทึกข้อมูล</button>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function editProduct(id, name, brand, price, category, gender) {
    Swal.fire({
        title: 'แก้ไขข้อมูลสินค้า',
        html: `
            <div class="text-start px-2">
                <label class="form-label-custom mt-3">ชื่อสินค้า</label>
                <input id="swal-name" class="form-control form-control-custom mb-3" value="${name}">
                <div class="row">
                    <div class="col-6">
                        <label class="form-label-custom">หมวดหมู่</label>
                        <select id="swal-category" class="form-select form-control-custom mb-3">
                            <option value="รองเท้า" ${category === "รองเท้า" ? "selected" : ""}>รองเท้า</option>
                            <option value="เสื้อผ้า" ${category === "เสื้อผ้า" ? "selected" : ""}>เสื้อผ้า</option>
                            <option value="แร็คเกต" ${category === "แร็คเกต" ? "selected" : ""}>แร็คเกต</option>
                            <option value="กระเป๋า" ${category === "กระเป๋า" ? "selected" : ""}>กระเป๋า</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label-custom">สินค้าสำหรับ</label>
                        <select id="swal-gender" class="form-select form-control-custom mb-3">
                            <option value="ชาย" ${gender === "ชาย" ? "selected" : ""}>ผู้ชาย</option>
                            <option value="หญิง" ${gender === "หญิง" ? "selected" : ""}>ผู้หญิง</option>
                            <option value="Unisex" ${gender === "Unisex" ? "selected" : ""}>Unisex</option>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-6">
                        <label class="form-label-custom">แบรนด์</label>
                        <input id="swal-brand" class="form-control form-control-custom" value="${brand}">
                    </div>
                    <div class="col-6">
                        <label class="form-label-custom">ราคา</label>
                        <input id="swal-price" type="number" class="form-control form-control-custom" value="${price}">
                    </div>
                </div>
            </div>`,
        showCancelButton: true,
        confirmButtonText: 'บันทึกการเปลี่ยนแปลง',
        confirmButtonColor: '#111',
        preConfirm: () => {
            return {
                id,
                name: document.getElementById('swal-name').value,
                category: document.getElementById('swal-category').value,
                gender: document.getElementById('swal-gender').value,
                brand: document.getElementById('swal-brand').value,
                price: document.getElementById('swal-price').value
            }
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const d = result.value;
            // ส่วนที่แก้ไข: เพิ่ม &gender เข้าไปใน URL เพื่อส่งไปบันทึก
            window.location.href = `admin_action.php?act=edit_full&id=${d.id}&name=${encodeURIComponent(d.name)}&brand=${encodeURIComponent(d.brand)}&price=${d.price}&category=${encodeURIComponent(d.category)}&gender=${encodeURIComponent(d.gender)}`;
        }
    });
}

function deleteProduct(id) {
    Swal.fire({
        title: 'ยืนยันการลบ?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'ลบทิ้ง'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `admin_action.php?act=delete&id=${id}`;
        }
    });
}
</script>
</body>
</html>
