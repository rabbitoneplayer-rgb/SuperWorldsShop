<?php
session_start();
include_once("connectdb.php");

// เช็คสิทธิ์แอดมิน
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: login.php");
    exit();
}

// ดึงข้อมูลทั้งหมด
$sql = "SELECT * FROM users"; 
$result = mysqli_query($conn, $sql);
?>
<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Customer Manager | SUPERWORLDS Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root { --ss-red: #e12128; --ss-dark: #111111; --ss-blue: #0d6efd; }
        body { background-color: #f0f2f5; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #333; }
        
        /* Card & Table Style */
        .admin-card { border: none; border-radius: 24px; box-shadow: 0 15px 35px rgba(0,0,0,0.05); background: #fff; overflow: hidden; }
        .table thead { background-color: var(--ss-dark); color: white; }
        .table thead th { padding: 20px; font-size: 0.75rem; letter-spacing: 1px; text-transform: uppercase; border: none; }
        .table tbody td { padding: 18px 20px; vertical-align: middle; border-bottom: 1px solid #f1f1f1; }
        
        /* Action Buttons */
        .btn-action { width: 38px; height: 38px; border-radius: 12px; display: inline-flex; align-items: center; justify-content: center; transition: 0.3s; border: none; margin: 0 2px; }
        .btn-edit { background: #e7f1ff; color: var(--ss-blue); }
        .btn-edit:hover { background: var(--ss-blue); color: #fff; transform: translateY(-3px); }
        .btn-del { background: #fff5f5; color: var(--ss-red); }
        .btn-del:hover { background: var(--ss-red); color: #fff; transform: translateY(-3px); }

        /* SweetAlert Form Customization */
        .swal-form-label { font-weight: 700; font-size: 0.85rem; color: #666; margin-bottom: 5px; display: block; text-align: left; }
        .swal-form-control { border-radius: 12px !important; padding: 12px 15px !important; border: 2px solid #eee !important; transition: 0.3s !important; }
        .swal-form-control:focus { border-color: var(--ss-dark) !important; box-shadow: none !important; }
    </style>
</head>
<body>

<div class="container mt-5 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h6 class="text-primary fw-bold text-uppercase mb-1" style="letter-spacing: 2px;">Database Management</h6>
            <h2 class="fw-bold m-0"><i class="fas fa-users-cog me-2"></i>จัดการข้อมูลลูกค้า</h2>
        </div>
        <a href="index.php" class="btn btn-dark rounded-pill px-4 shadow-sm">
            <i class="fas fa-home me-2"></i>หน้าแรก
        </a>
    </div>

    <div class="card admin-card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">ชื่อ-นามสกุล</th>
                        <th>อีเมล</th>
                        <th>เบอร์โทร</th>
                        <th class="text-center">ระดับ</th>
                        <th class="text-end pe-4">ดำเนินการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = mysqli_fetch_array($result)): 
                        // ตรวจสอบชื่อคอลัมน์ ID (ปรับให้ตรงกับ database จริงของคุณ)
                        $current_id = isset($row['u_id']) ? $row['u_id'] : (isset($row['id']) ? $row['id'] : 0);
                        $is_self = (isset($_SESSION['user_id']) && $current_id == $_SESSION['user_id']);
                    ?>
                    <tr>
                        <td class="ps-4">
                            <div class="fw-bold text-dark"><?php echo $row['fullname']; ?></div>
                            <?php if($is_self): ?><span class="badge bg-primary" style="font-size: 0.6rem;">บัญชีของคุณ</span><?php endif; ?>
                        </td>
                        <td class="text-muted"><?php echo $row['email']; ?></td>
                        <td><?php echo $row['phone']; ?></td>
                        <td class="text-center">
                            <?php if($row['is_admin'] == 1): ?>
                                <span class="badge rounded-pill bg-danger px-3">Admin</span>
                            <?php else: ?>
                                <span class="badge rounded-pill bg-secondary px-3">Customer</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end pe-4">
                            <button class="btn-action btn-edit" title="แก้ไข" 
                                    onclick="editCustomer('<?php echo $current_id; ?>', '<?php echo addslashes($row['fullname']); ?>', '<?php echo $row['email']; ?>', '<?php echo $row['phone']; ?>')">
                                <i class="fas fa-user-edit"></i>
                            </button>
                            
                            <?php if(!$is_self): ?>
                            <button class="btn-action btn-del" title="ลบ" onclick="deleteCustomer('<?php echo $current_id; ?>')">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                            <?php else: ?>
                            <button class="btn-action bg-light text-muted" title="ไม่สามารถลบตัวเองได้" disabled>
                                <i class="fas fa-ban"></i>
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function editCustomer(id, name, email, phone) {
    Swal.fire({
        title: '<div class="fw-bold mt-2">แก้ไขข้อมูลลูกค้า</div>',
        html: `
            <div class="p-2">
                <div class="mb-3">
                    <label class="swal-form-label"><i class="fas fa-user me-2"></i>ชื่อ-นามสกุล</label>
                    <input id="swal-name" class="form-control swal-form-control" value="${name}" placeholder="Full Name">
                </div>
                <div class="mb-3">
                    <label class="swal-form-label"><i class="fas fa-envelope me-2"></i>อีเมล</label>
                    <input id="swal-email" type="email" class="form-control swal-form-control" value="${email}" placeholder="Email Address">
                </div>
                <div class="mb-1">
                    <label class="swal-form-label"><i class="fas fa-phone-alt me-2"></i>เบอร์โทรศัพท์</label>
                    <input id="swal-phone" type="tel" class="form-control swal-form-control" value="${phone}" placeholder="Phone Number">
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'บันทึกการแก้ไข',
        cancelButtonText: 'ยกเลิก',
        confirmButtonColor: '#111',
        cancelButtonColor: '#eee',
        reverseButtons: true,
        customClass: {
            popup: 'rounded-4 shadow-lg',
            confirmButton: 'btn btn-dark rounded-pill px-4 py-2 ms-2',
            cancelButton: 'btn btn-light rounded-pill px-4 py-2 text-dark'
        },
        buttonsStyling: false,
        preConfirm: () => {
            const n = document.getElementById('swal-name').value;
            const e = document.getElementById('swal-email').value;
            const p = document.getElementById('swal-phone').value;
            if (!n || !e || !p) {
                Swal.showValidationMessage('กรุณากรอกข้อมูลให้ครบถ้วน');
                return false;
            }
            return { id, name: n, email: e, phone: p }
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const d = result.value;
            // ส่งค่าไปที่ admin_action.php
            window.location.href = `admin_action.php?act=edit_user&id=${d.id}&name=${encodeURIComponent(d.name)}&email=${encodeURIComponent(d.email)}&phone=${encodeURIComponent(d.phone)}`;
        }
    });
}

function deleteCustomer(id) {
    Swal.fire({
        title: 'ยืนยันการลบลูกค้า?',
        text: "ข้อมูลลูกค้าจะถูกลบออกจากระบบอย่างถาวร!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'ใช่, ลบทิ้ง',
        cancelButtonText: 'ยกเลิก',
        confirmButtonColor: '#e12128',
        cancelButtonColor: '#eee',
        reverseButtons: true,
        customClass: {
            popup: 'rounded-4',
            confirmButton: 'btn btn-danger rounded-pill px-4 py-2 ms-2',
            cancelButton: 'btn btn-light rounded-pill px-4 py-2 text-dark'
        },
        buttonsStyling: false
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `admin_action.php?act=delete_user&id=${id}`;
        }
    });
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>