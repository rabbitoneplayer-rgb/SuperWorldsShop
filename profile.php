<?php
session_start();
include_once("connectdb.php");

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }

$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE id = '$user_id'";
$result = mysqli_query($conn, $sql);
$user = mysqli_fetch_assoc($result);

// ตรวจสอบรูปภาพโปรไฟล์ ถ้าไม่มีให้ใช้ตัวอักษรแรกของชื่อ
$has_image = !empty($user['u_img']) && file_exists($user['u_img']);
$display_initial = strtoupper(mb_substr($user['fullname'], 0, 1));
?>
<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ตั้งค่าโปรไฟล์ | SUPER WORLDS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root { --ss-red: #e12128; --ss-dark: #111111; --ss-gray: #f4f7f6; }
        body { background-color: var(--ss-gray); font-family: 'Kanit', sans-serif; color: #333; }
        
        .navbar { background-color: var(--ss-dark) !important; padding: 18px 0; }
        .profile-header { background: linear-gradient(135deg, var(--ss-dark) 0%, #333 100%); padding: 80px 0 120px; color: white; border-radius: 0 0 50px 50px; margin-bottom: -80px; }
        .profile-card { border: none; border-radius: 30px; box-shadow: 0 20px 50px rgba(0,0,0,0.08); background: #fff; overflow: hidden; }
        
        /* Avatar Upload Style */
        .avatar-upload { position: relative; max-width: 150px; margin: 0 auto 30px; }
        .avatar-edit { position: absolute; right: 10px; z-index: 1; bottom: 10px; }
        .avatar-edit input { display: none; }
        .avatar-edit label { display: flex; align-items: center; justify-content: center; width: 34px; height: 34px; margin-bottom: 0; border-radius: 100%; background: #FFFFFF; border: 1px solid transparent; box-shadow: 0px 2px 4px 0px rgba(0, 0, 0, 0.12); cursor: pointer; font-weight: normal; transition: all 0.2s ease-in-out; }
        .avatar-edit label:hover { background: #f1f1f1; border-color: #d6d6d6; }
        .avatar-preview { width: 150px; height: 150px; position: relative; border-radius: 40px; border: 6px solid #F8F8F8; box-shadow: 0px 2px 10px 0px rgba(0, 0, 0, 0.1); overflow: hidden; background: var(--ss-red); display: flex; align-items: center; justify-content: center; }
        .avatar-preview img { width: 100%; height: 100%; object-fit: cover; }
        .initial-display { font-size: 4rem; font-weight: 800; color: white; }

        .form-label { font-weight: 700; font-size: 0.8rem; color: #aaa; text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 10px; }
        .input-group-custom { background: #f9f9f9; border: 2px solid #f1f1f1; border-radius: 15px; padding: 5px 15px; transition: 0.3s; display: flex; align-items: center; margin-bottom: 20px; }
        .input-group-custom:focus-within { border-color: var(--ss-dark); background: #fff; box-shadow: 0 10px 20px rgba(0,0,0,0.03); }
        .input-group-custom i { color: #ccc; margin-right: 15px; font-size: 1.1rem; }
        .input-group-custom input, .input-group-custom textarea { border: none; background: transparent; padding: 12px 0; width: 100%; outline: none; font-weight: 500; }
        
        .btn-save { background: var(--ss-dark); color: white; border-radius: 50px; padding: 18px 40px; border: none; font-weight: 800; width: 100%; transition: 0.4s; font-size: 1.1rem; display: flex; align-items: center; justify-content: center; gap: 10px; }
        .btn-save:hover { background: var(--ss-red); transform: translateY(-5px); box-shadow: 0 15px 30px rgba(225, 33, 40, 0.3); }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php">SUPER<span class="text-danger">WORLDS</span></a>
        <a href="index.php" class="btn btn-outline-light btn-sm rounded-pill px-4">กลับหน้าหลัก</a>
    </div>
</nav>

<div class="profile-header text-center">
    <div class="container">
        <h6 class="text-danger fw-bold text-uppercase mb-2" style="letter-spacing: 3px;">Account Settings</h6>
        <h1 class="fw-bold mb-0">ข้อมูลโปรไฟล์</h1>
    </div>
</div>

<div class="container mb-5 pb-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card profile-card p-4 p-md-5">
                <form id="profileForm" enctype="multipart/form-data">
                    <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">

                    <div class="avatar-upload">
                        <div class="avatar-edit">
                            <input type='file' id="imageUpload" name="u_img" accept=".png, .jpg, .jpeg" />
                            <label for="imageUpload"><i class="fas fa-camera text-muted"></i></label>
                        </div>
                        <div class="avatar-preview">
                            <?php if($has_image): ?>
                                <img src="<?php echo $user['u_img']; ?>" id="imagePreview">
                            <?php else: ?>
                                <div id="initialPreview" class="initial-display"><?php echo $display_initial; ?></div>
                                <img src="" id="imagePreview" style="display:none;">
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label">ชื่อ-นามสกุล</label>
                            <div class="input-group-custom">
                                <i class="fas fa-user"></i>
                                <input type="text" name="fullname" value="<?php echo htmlspecialchars($user['fullname']); ?>" required>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">อีเมลติดต่อ</label>
                            <div class="input-group-custom">
                                <i class="fas fa-envelope"></i>
                                <input type="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">เบอร์โทรศัพท์</label>
                            <div class="input-group-custom">
                                <i class="fas fa-phone-alt"></i>
                                <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" maxlength="10">
                            </div>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">ที่อยู่สำหรับการจัดส่ง</label>
                            <div class="input-group-custom align-items-start">
                                <i class="fas fa-map-marker-alt mt-3"></i>
                                <textarea name="address" rows="4"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-save shadow-sm" id="submitBtn">
                            <i class="fas fa-check-circle"></i> บันทึกข้อมูลทั้งหมด
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
    // Preview รูปภาพก่อนอัปโหลด
    function readURL(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                $('#imagePreview').attr('src', e.target.result).fadeIn(650);
                $('#imagePreview').show();
                $('#initialPreview').hide();
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
    $("#imageUpload").change(function() {
        readURL(this);
    });

    $(document).ready(function(){
        $('#profileForm').on('submit', function(e){
            e.preventDefault();
            const btn = $('#submitBtn');
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> กำลังประมวลผล...');

            // ใช้ FormData เพื่อส่งไฟล์รูปภาพ
            var formData = new FormData(this);

            $.ajax({
                url: "update_profile.php",
                method: "POST",
                data: formData,
                contentType: false,
                processData: false,
                success: function(response) {
                    if(response.trim() == "success") {
                        Swal.fire({ 
                            icon: 'success', title: 'อัปเดตสำเร็จ!', 
                            text: 'ข้อมูลและรูปโปรไฟล์ของคุณถูกบันทึกแล้ว',
                            showConfirmButton: false, timer: 2000 
                        }).then(() => { window.location.reload(); });
                    } else {
                        Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: response });
                        btn.prop('disabled', false).html('<i class="fas fa-check-circle"></i> บันทึกการเปลี่ยนแปลง');
                    }
                }
            });
        });
    });
</script>
</body>
</html>