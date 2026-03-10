<?php
session_start();
include_once("connectdb.php");

// 1. รับค่า ID สินค้า
$p_id = isset($_GET['id']) ? mysqli_real_escape_string($conn, $_GET['id']) : '';
if ($p_id == "") { header("Location: index.php"); exit; }

// 2. ดึงข้อมูลสินค้า
$sql = "SELECT * FROM products WHERE p_id = '$p_id'";
$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_array($result);

if (!$row) { echo "ไม่พบสินค้า"; exit; }

$is_logged_in = isset($_SESSION['user_id']);
$is_admin = (isset($_SESSION['user_id']) && isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1);

// 3. เตรียมข้อมูลหมวดหมู่และตัวเลือก
$category = trim($row['p_category']); 
$label = (strpos($category, 'รองเท้า') !== false) ? "ไซส์ (US)" : ((strpos($category, 'เสื้อ') !== false) ? "เลือกขนาด" : "ตัวเลือกสินค้า");

if (!empty($row['p_size'])) {
    $options = explode(',', $row['p_size']);
} else {
    $options = (strpos($category, 'รองเท้า') !== false) ? ["7", "8", "9", "10"] : ["S", "M", "L", "XL"];
}

// 4. รวบรวมรูปภาพทั้งหมดที่มีข้อมูล (กรองค่าว่างออก)
$images = array_filter([$row['p_image'], $row['p_img2'] ?? '', $row['p_img3'] ?? '', $row['p_img4'] ?? '', $row['p_img5'] ?? '']);
?>
<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $row['p_name']; ?> | SUPERWORLDS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root { --ss-red: #e12128; --ss-dark: #111111; --ss-gray: #f8f9fa; --ss-border: #eee; }
        body { font-family: 'Kanit', sans-serif; background-color: #fff; color: #333; }
        
        .back-btn { display: inline-flex; align-items: center; gap: 10px; color: #888; text-decoration: none; font-weight: 600; font-size: 0.9rem; transition: 0.3s; margin-bottom: 25px; }
        .back-btn:hover { color: var(--ss-dark); transform: translateX(-5px); }

        /* Gallery System */
        .gallery-container { position: sticky; top: 120px; }
        .img-main-display { background-color: var(--ss-gray); border-radius: 40px; padding: 50px; text-align: center; display: flex; align-items: center; justify-content: center; height: 550px; overflow: hidden; position: relative; border: 1px solid #f0f0f0; }
        .product-main-img { max-width: 100%; max-height: 100%; object-fit: contain; transition: 0.5s ease; }
        
        .thumb-container { display: flex; gap: 12px; margin-top: 20px; justify-content: center; flex-wrap: wrap; }
        .thumb-box { width: 75px; height: 75px; border-radius: 15px; border: 2px solid var(--ss-border); cursor: pointer; overflow: hidden; padding: 5px; transition: 0.3s; background: #fff; position: relative; }
        .thumb-box img { width: 100%; height: 100%; object-fit: contain; }
        .thumb-box.active { border-color: var(--ss-red); box-shadow: 0 5px 15px rgba(225,33,40,0.1); }
        
        /* Admin Badge Delete */
        .admin-badge-del { position: absolute; top: -8px; right: -8px; background: var(--ss-red) !important; color: white !important; border-radius: 50%; width: 22px; height: 22px; font-size: 10px; display: flex; align-items: center; justify-content: center; border: 2px solid white; z-index: 99; cursor: pointer; box-shadow: 0 2px 5px rgba(0,0,0,0.2); }

        .brand-badge { color: var(--ss-red); font-weight: 800; text-transform: uppercase; letter-spacing: 4px; font-size: 0.9rem; margin-bottom: 5px; display: block; }
        .product-title { font-size: 2.8rem; font-weight: 800; line-height: 1.1; margin-bottom: 20px; color: var(--ss-dark); }
        
        .opt-btn { position: relative; min-width: 70px; height: 50px; display: flex; align-items: center; justify-content: center; border: 2px solid var(--ss-border); border-radius: 12px; cursor: pointer; font-weight: 700; transition: 0.3s; background: #fff; }
        .opt-btn.active { background: var(--ss-dark); color: white; border-color: var(--ss-dark); }

        .btn-buy-now { background: var(--ss-dark); color: #fff; border: none; border-radius: 20px; padding: 18px; font-weight: 800; width: 100%; font-size: 1.1rem; transition: 0.4s; display: flex; align-items: center; justify-content: center; gap: 10px; }
        .btn-buy-now:hover { background: var(--ss-red); transform: translateY(-3px); box-shadow: 0 10px 20px rgba(225,33,40,0.2); }

        .admin-bar { background: #000; color: #fff; padding: 12px 0; border-bottom: 4px solid var(--ss-red); position: sticky; top: 0; z-index: 1000; }
        .img-edit-preview { width: 80px; height: 80px; object-fit: contain; border-radius: 10px; border: 1px solid #ddd; background: #fff; }
    </style>
</head>
<body>

<?php if($is_admin): ?>
<div class="admin-bar no-print">
    <div class="container d-flex justify-content-between align-items-center">
        <span class="fw-bold small text-uppercase"><i class="fas fa-user-shield me-2 text-danger"></i> Admin Product Manager</span>
        <div class="d-flex gap-2">
            <button class="btn btn-sm btn-warning rounded-pill px-3 fw-bold" onclick="manageGallery()"><i class="fas fa-images me-1"></i> จัดการรูปภาพ</button>
            <a href="admin_products.php" class="btn btn-sm btn-danger rounded-pill px-3 fw-bold">คลังสินค้า</a>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="container mt-4 mb-5 pt-3">
    <a href="index.php" class="back-btn"><i class="fas fa-arrow-left"></i> ย้อนกลับไปเลือกสินค้า</a>

    <div class="row g-lg-5">
        <div class="col-lg-6">
            <div class="gallery-container">
                <div class="img-main-display shadow-sm">
                    <img src="<?php echo reset($images); ?>" id="mainImg" class="product-main-img" onerror="this.src='https://placehold.co/800x800?text=SUPERWORLDS'">
                </div>
                <div class="thumb-container">
                    <?php foreach($images as $index => $img): if(!empty($img)): ?>
                        <div class="thumb-box <?php echo $index === 0 ? 'active' : ''; ?>" onclick="changeGalleryImage('<?php echo $img; ?>', this)">
                            <img src="<?php echo $img; ?>">
                        </div>
                    <?php endif; endforeach; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="ps-lg-4">
                <span class="brand-badge"><?php echo $row['p_brand']; ?></span>
                <h1 class="product-title"><?php echo $row['p_name']; ?></h1>
                <h3 class="fw-bold mb-4" style="color: var(--ss-red);">฿<?php echo number_format($row['p_price']); ?></h3>

                <div class="mb-5">
                    <label class="fw-bold text-muted small text-uppercase mb-3 d-block"><?php echo $label; ?></label>
                    <div class="d-flex flex-wrap gap-3" id="option-list">
                        <?php foreach($options as $opt): ?>
                            <div class="opt-btn" data-value="<?php echo trim($opt); ?>">
                                <?php echo trim($opt); ?>
                                <?php if($is_admin): ?>
                                    <div class="admin-badge-del" onclick="removeOption('<?php echo trim($opt); ?>', event)"><i class="fas fa-times"></i></div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                        <?php if($is_admin): ?>
                            <div class="opt-btn text-danger border-danger" style="border-style: dashed !important;" onclick="addOption()"><i class="fas fa-plus-circle"></i></div>
                        <?php endif; ?>
                    </div>
                    <input type="hidden" id="selected_option" value="">
                </div>

                <button type="button" class="btn btn-buy-now add-to-cart-btn mb-5" data-id="<?php echo $row['p_id']; ?>">
                    <i class="fas fa-shopping-bag"></i> เพิ่มลงตะกร้าสินค้า
                </button>

                <div class="pt-4 border-top">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="fw-bold text-uppercase small text-muted">รายละเอียดสินค้า</span>
                        <?php if($is_admin): ?>
                            <button class="btn btn-sm btn-dark rounded-pill px-3" onclick="editDescription()"><i class="fas fa-edit me-1"></i>แก้ไข</button>
                        <?php endif; ?>
                    </div>
                    <div id="desc-text" style="line-height: 1.8; color: #666; white-space: pre-line; font-size: 1.05rem;">
                        <?php echo !empty($row['p_detail']) ? $row['p_detail'] : 'ไม่มีรายละเอียดสินค้าในขณะนี้'; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content shadow-lg" style="border-radius: 30px; border: none;">
            <div class="modal-header border-0 px-4 pt-4">
                <h5 class="fw-bold m-0">จัดการรูปภาพสินค้า</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 pt-0">
                <p class="text-muted small mb-4">* เลือกไฟล์เพื่อเปลี่ยนรูป หรือกดกากบาทสีแดงเพื่อลบรูปภาพนั้นๆ ออก</p>
                <form id="imgForm" enctype="multipart/form-data">
                    <input type="hidden" name="p_id" value="<?php echo $p_id; ?>">
                    <div class="row g-3">
                        <?php 
                        $fields = ['p_image'=>'รูปหลัก', 'p_img2'=>'รูปที่ 2', 'p_img3'=>'รูปที่ 3', 'p_img4'=>'รูปที่ 4', 'p_img5'=>'รูปที่ 5'];
                        foreach($fields as $field => $title): ?>
                        <div class="col-md-6">
                            <div class="p-3 border rounded-4 bg-light position-relative">
                                <label class="fw-bold small mb-2 d-block"><?php echo $title; ?></label>
                                
                                <?php if(!empty($row[$field])): ?>
                                    <div class="admin-badge-del" onclick="deleteSingleImage('<?php echo $field; ?>')">
                                        <i class="fas fa-times"></i>
                                    </div>
                                <?php endif; ?>

                                <div class="d-flex align-items-center gap-3">
                                    <img src="<?php echo (!empty($row[$field])) ? $row[$field] : 'https://placehold.co/100x100?text=No+Img'; ?>" class="img-edit-preview" id="prev-<?php echo $field; ?>">
                                    <input type="file" name="<?php echo $field; ?>" class="form-control form-control-sm" onchange="previewImage(this, 'prev-<?php echo $field; ?>')">
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 p-4">
                <button type="button" class="btn btn-dark w-100 rounded-pill py-3 fw-bold shadow" onclick="uploadImages()">บันทึกรูปภาพที่เลือกใหม่</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
function changeGalleryImage(src, element) {
    const mainImg = document.getElementById('mainImg');
    mainImg.style.opacity = '0.5';
    setTimeout(() => {
        mainImg.src = src;
        mainImg.style.opacity = '1';
    }, 150);
    document.querySelectorAll('.thumb-box').forEach(box => box.classList.remove('active'));
    element.classList.add('active');
}

$(document).ready(function(){
    $(document).on('click', '.opt-btn:not(.text-danger)', function(){
        $('.opt-btn').removeClass('active');
        $(this).addClass('active');
        $('#selected_option').val($(this).data('value'));
    });

    $('.add-to-cart-btn').click(function() {
        const opt = $('#selected_option').val();
        if(!opt) { Swal.fire({ icon: 'warning', title: 'กรุณาเลือกไซส์/ขนาด' }); return; }
        $.get('cart_action.php', { id: '<?php echo $p_id; ?>', action: 'add', qty: 1, option: opt, ajax: 1 }, (res) => {
            Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'เพิ่มสินค้าลงตะกร้าแล้ว', showConfirmButton: false, timer: 1500 });
            if(parent.$('#cart-badge').length) parent.$('#cart-badge').text(res);
        });
    });
});

// Admin Functions
function manageGallery() { new bootstrap.Modal(document.getElementById('imageModal')).show(); }

function previewImage(input, prevId) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) { $('#' + prevId).attr('src', e.target.result); }
        reader.readAsDataURL(input.files[0]);
    }
}

function uploadImages() {
    let formData = new FormData(document.getElementById('imgForm'));
    Swal.fire({ title: 'กำลังบันทึกรูปภาพ...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });

    $.ajax({
        url: 'admin_action.php?act=update_images',
        type: 'POST',
        data: formData,
        contentType: false,
        processData: false,
        success: function(res) {
            if(res.includes('success')) {
                Swal.fire({ icon: 'success', title: 'บันทึกสำเร็จ', timer: 1000 }).then(() => location.reload());
            } else {
                Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: res });
            }
        }
    });
}

// ฟังก์ชันลบรูปภาพทีละใบ (AJAX) 
function deleteSingleImage(field) {
    Swal.fire({
        title: 'ยืนยันการลบรูปภาพ?',
        text: "รูปภาพนี้จะถูกลบออกจาก Server ถาวร",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e12128',
        cancelButtonColor: '#666',
        confirmButtonText: 'ใช่, ลบเลย!',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('admin_action.php?act=delete_image', { p_id: '<?php echo $p_id; ?>', field: field }, (res) => {
                if(res.includes('success')) {
                    Swal.fire({ icon: 'success', title: 'ลบรูปภาพสำเร็จ', showConfirmButton: false, timer: 1000 }).then(() => location.reload());
                } else {
                    Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: res });
                }
            });
        }
    });
}

function addOption() {
    Swal.fire({ title: 'เพิ่มตัวเลือกใหม่', input: 'text', showCancelButton: true }).then(r => {
        if(r.value) updateAdminOptions('add', r.value);
    });
}
function removeOption(v, e) {
    e.stopPropagation();
    Swal.fire({ title: 'ลบตัวเลือกนี้?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#e12128' }).then(r => {
        if(r.isConfirmed) updateAdminOptions('remove', v);
    });
}
function updateAdminOptions(action, value) {
    $.post('admin_action.php?act=update_variants', { p_id: '<?php echo $p_id; ?>', action: action, value: value }, (res) => {
        if(res.includes('success')) location.reload();
    });
}
function editDescription() {
    Swal.fire({ title: 'แก้ไขรายละเอียดสินค้า', input: 'textarea', inputValue: $('#desc-text').text().trim(), showCancelButton: true }).then(r => {
        if(r.value) $.post('admin_action.php?act=update_detail', { p_id: '<?php echo $p_id; ?>', p_detail: r.value }, (res) => {
            if(res.includes('success')) location.reload();
        });
    });
}
</script>
</body>
</html>
