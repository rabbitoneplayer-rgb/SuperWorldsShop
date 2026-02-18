<?php
session_start();
include_once("connectdb.php");

// 1. รับค่า ID สินค้า
$p_id = isset($_GET['id']) ? $_GET['id'] : '';
if ($p_id == "") { header("Location: index.php"); exit; }

// 2. ดึงข้อมูลสินค้า
$sql = "SELECT * FROM products WHERE p_id = '$p_id'";
$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_array($result);

if (!$row) { echo "ไม่พบสินค้า"; exit; }

// เช็คสถานะการล็อกอินและสิทธิ์แอดมิน
$is_logged_in = isset($_SESSION['user_id']);
$is_admin = (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1);

// 3. เตรียมข้อมูลหมวดหมู่และตัวเลือก
$category = trim($row['p_category']); 
$label = (strpos($category, 'รองเท้า') !== false) ? "ไซส์ (US)" : ((strpos($category, 'เสื้อ') !== false) ? "เลือกขนาด" : "ตัวเลือกสินค้า");

if (!empty($row['p_size'])) {
    $options = explode(',', $row['p_size']);
} else {
    $options = (strpos($category, 'รองเท้า') !== false) ? ["7", "8", "9", "10"] : ["S", "M", "L", "XL"];
}

// 4. รวบรวมรูปภาพทั้งหมด (รูปหลัก + รูปเพิ่มเติม 2-5)
$images = array_filter([$row['p_image'], $row['p_img2'] ?? null, $row['p_img3'] ?? null, $row['p_img4'] ?? null, $row['p_img5'] ?? null]);

// 5. Logic สำหรับ Tag เพศและหมวดหมู่ (เพิ่มเติม)
$p_name = $row['p_name'];
$tag_class = "tag-default";
$gender_label = "Unisex";

if (strpos($category, 'ชาย') !== false || strpos($p_name, 'Men') !== false || strpos($p_name, 'ชาย') !== false) {
    $tag_class = "tag-men";
    $gender_label = "Men's Collection";
} elseif (strpos($category, 'หญิง') !== false || strpos($p_name, 'Women') !== false || strpos($p_name, 'หญิง') !== false) {
    $tag_class = "tag-women";
    $gender_label = "Women's Collection";
}
?>
<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $row['p_name']; ?> | SUPERWORLDS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root { --ss-red: #e12128; --ss-dark: #111111; --ss-gray: #f8f9fa; --ss-border: #eee; }
        body { font-family: 'Kanit', sans-serif; background-color: #fff; color: #333; }
        
        /* Tag Styling */
        .detail-tags-wrapper { display: flex; gap: 10px; margin-bottom: 15px; }
        .gender-tag { padding: 4px 15px; border-radius: 50px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; }
        .tag-men { background: var(--ss-dark); color: #fff; }
        .tag-women { background: #ff4d94; color: #fff; }
        .tag-default { background: #eee; color: #333; }
        .category-outline-tag { padding: 4px 15px; border-radius: 50px; font-size: 0.7rem; font-weight: 600; border: 1.5px solid var(--ss-border); color: #888; }

        /* Gallery System */
        .gallery-container { position: sticky; top: 100px; }
        .img-main-display { background-color: var(--ss-gray); border-radius: 40px; padding: 50px; text-align: center; display: flex; align-items: center; justify-content: center; height: 580px; overflow: hidden; position: relative; border: 1px solid #f0f0f0; }
        .product-main-img { max-width: 100%; max-height: 100%; object-fit: contain; transition: 0.8s cubic-bezier(0.165, 0.84, 0.44, 1); }
        .img-main-display:hover .product-main-img { transform: scale(1.1) rotate(-2deg); }
        
        .thumb-container { display: flex; gap: 15px; margin-top: 25px; justify-content: center; flex-wrap: wrap; }
        .thumb-box { width: 85px; height: 85px; border-radius: 18px; border: 2px solid var(--ss-border); cursor: pointer; overflow: hidden; padding: 8px; transition: 0.3s; background: #fff; }
        .thumb-box img { width: 100%; height: 100%; object-fit: contain; }
        .thumb-box.active { border-color: var(--ss-red); box-shadow: 0 8px 20px rgba(225,33,40,0.15); }
        
        /* Info Styling */
        .brand-badge { color: var(--ss-red); font-weight: 800; text-transform: uppercase; letter-spacing: 4px; font-size: 0.9rem; margin-bottom: 5px; display: block; }
        .product-title { font-size: 3rem; font-weight: 800; line-height: 1.1; letter-spacing: -1px; margin-bottom: 20px; color: var(--ss-dark); }
        .price-label { font-size: 2.4rem; font-weight: 800; color: var(--ss-dark); margin-bottom: 35px; display: block; }
        
        .opt-btn { min-width: 75px; height: 55px; display: flex; align-items: center; justify-content: center; border: 2px solid var(--ss-border); border-radius: 16px; cursor: pointer; font-weight: 700; transition: 0.3s; background: #fff; font-size: 1.1rem; }
        .opt-btn.active { background: var(--ss-dark); color: white; border-color: var(--ss-dark); box-shadow: 0 10px 25px rgba(0,0,0,0.15); }
        
        .qty-wrapper { display: flex; align-items: center; background: var(--ss-gray); border-radius: 20px; padding: 8px; width: fit-content; border: 1px solid #eee; }
        .qty-btn { width: 45px; height: 45px; border-radius: 15px; border: none; background: white; font-weight: 800; transition: 0.3s; }
        
        .btn-buy-now { background: var(--ss-dark); color: #fff; border: none; border-radius: 25px; padding: 20px; font-weight: 800; width: 100%; font-size: 1.2rem; text-transform: uppercase; transition: 0.4s; display: flex; align-items: center; justify-content: center; gap: 15px; }
        .btn-buy-now:hover { background: var(--ss-red); transform: translateY(-5px); box-shadow: 0 20px 40px rgba(225, 33, 40, 0.25); }

        .admin-bar { background: #000; color: #fff; padding: 15px 0; border-bottom: 4px solid var(--ss-red); position: sticky; top: 0; z-index: 1000; }
        .admin-badge-del { position: absolute; top: -10px; right: -10px; background: var(--ss-red); color: white; border-radius: 50%; width: 24px; height: 24px; font-size: 11px; display: flex; align-items: center; justify-content: center; border: 2px solid white; }
    </style>
</head>
<body>

<?php if($is_admin): ?>
<div class="admin-bar no-print">
    <div class="container d-flex justify-content-between align-items-center">
        <span class="fw-bold small"><i class="fas fa-user-shield me-2 text-danger"></i> ADMIN EDIT MODE: <?php echo $row['p_name']; ?></span>
        <button class="btn btn-sm btn-outline-light rounded-pill px-3" onclick="location.reload()"><i class="fas fa-sync"></i></button>
    </div>
</div>
<?php endif; ?>

<div class="container mt-5 mb-5 pt-3">
    <div class="row g-lg-5">
        <div class="col-lg-6">
            <div class="gallery-container">
                <div class="img-main-display shadow-sm">
                    <img src="<?php echo $images[0]; ?>" id="mainImg" class="product-main-img" onerror="this.src='https://placehold.co/800x800?text=SUPERWORLDS'">
                </div>
                <div class="thumb-container">
                    <?php foreach($images as $index => $img): ?>
                        <div class="thumb-box <?php echo $index === 0 ? 'active' : ''; ?>" onclick="changeGalleryImage('<?php echo $img; ?>', this)">
                            <img src="<?php echo $img; ?>">
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="ps-lg-4">
                <div class="detail-tags-wrapper">
                    <span class="gender-tag <?php echo $tag_class; ?>"><?php echo $gender_label; ?></span>
                    <span class="category-outline-tag"><?php echo $category; ?></span>
                </div>

                <span class="brand-badge"><?php echo $row['p_brand']; ?></span>
                <h1 class="product-title"><?php echo $row['p_name']; ?></h1>
                <span class="price-label">฿<?php echo number_format($row['p_price']); ?></span>

                <div class="mb-5">
                    <label class="option-label" style="font-weight: 700; text-transform: uppercase; font-size: 0.8rem; color: #999; letter-spacing: 2px; margin-bottom: 18px; display: block;"><?php echo $label; ?></label>
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

                <div class="mb-5">
                    <label class="option-label" style="font-weight: 700; text-transform: uppercase; font-size: 0.8rem; color: #999; letter-spacing: 2px; margin-bottom: 18px; display: block;">จำนวนสินค้า</label>
                    <div class="qty-wrapper">
                        <button class="qty-btn" onclick="changeQty(-1)"><i class="fas fa-minus"></i></button>
                        <input type="text" id="p_qty" class="border-0 bg-transparent text-center fw-bold" value="1" readonly style="width: 70px; font-size: 1.4rem;">
                        <button class="qty-btn" onclick="changeQty(1)"><i class="fas fa-plus"></i></button>
                    </div>
                </div>

                <button type="button" class="btn btn-buy-now add-to-cart-btn mb-5" data-id="<?php echo $row['p_id']; ?>">
                    <i class="fas fa-shopping-bag"></i> เพิ่มลงตะกร้าสินค้า
                </button>

                <div style="margin-top: 60px; padding-top: 40px; border-top: 1px solid #f0f0f0;">
                    <div style="font-weight: 800; font-size: 1.2rem; color: var(--ss-dark); margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center;">
                        <span>รายละเอียดสินค้า</span>
                        <?php if($is_admin): ?>
                            <button class="btn btn-sm btn-dark rounded-pill px-4 fw-bold" onclick="editDescription()"><i class="fas fa-edit me-2"></i>แก้ไข</button>
                        <?php endif; ?>
                    </div>
                    <div class="detail-text" id="desc-text" style="font-size: 1.1rem; line-height: 1.9; color: #666; white-space: pre-line;">
                        <?php echo !empty($row['p_detail']) ? $row['p_detail'] : 'กำลังอัปเดตรายละเอียดสินค้า...'; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
function changeGalleryImage(src, element) {
    const mainImg = document.getElementById('mainImg');
    mainImg.style.opacity = '0';
    setTimeout(() => { mainImg.src = src; mainImg.style.opacity = '1'; }, 200);
    document.querySelectorAll('.thumb-box').forEach(box => box.classList.remove('active'));
    element.classList.add('active');
}

$(document).ready(function(){
    const isLoggedIn = <?php echo $is_logged_in ? 'true' : 'false'; ?>;
    $(document).on('click', '.opt-btn:not(.text-danger)', function(){
        $('.opt-btn').removeClass('active');
        $(this).addClass('active');
        $('#selected_option').val($(this).data('value'));
    });

    $('.add-to-cart-btn').click(function() {
        if (!isLoggedIn) {
            Swal.fire({ title: 'กรุณาเข้าสู่ระบบ', icon: 'info', showCancelButton: true, confirmButtonColor: '#111', confirmButtonText: 'Login Now' }).then((result) => { if (result.isConfirmed) window.location.href = 'login.php'; });
            return;
        }
        const opt = $('#selected_option').val();
        if(!opt) {
            Swal.fire({ icon: 'warning', title: 'โปรดเลือกตัวเลือก', text: 'กรุณาเลือกไซส์ก่อนหยิบใส่ตะกร้า', confirmButtonColor: '#111' });
            return;
        }
        $.ajax({
            url: 'cart_action.php', type: 'GET',
            data: { id: '<?php echo $p_id; ?>', action: 'add', qty: $('#p_qty').val(), option: opt, ajax: 1 },
            success: function(res) {
                Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'เพิ่มลงตะกร้าเรียบร้อย', showConfirmButton: false, timer: 2000 });
                if(parent.$('#cart-badge').length) parent.$('#cart-badge').text(res);
            }
        });
    });
});

function changeQty(v) {
    let q = parseInt($('#p_qty').val()) + v;
    if(q >= 1) $('#p_qty').val(q);
}

// Admin Scripts (Original preserved)
function addOption() {
    Swal.fire({ title: 'เพิ่มไซส์/ตัวเลือก', input: 'text', showCancelButton: true, confirmButtonColor: '#111' }).then(r => {
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
    Swal.fire({ title: 'แก้ไขรายละเอียดสินค้า', input: 'textarea', inputValue: $('#desc-text').text(), showCancelButton: true, confirmButtonColor: '#111' }).then(r => {
        if(r.value) $.post('admin_action.php?act=update_detail', { p_id: '<?php echo $p_id; ?>', p_detail: r.value }, (res) => {
            if(res.includes('success')) location.reload();
        });
    });
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
