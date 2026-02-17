<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สมัครสมาชิก | SUPERWORLDS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root { --ss-red: #e12128; --ss-dark: #111; --ss-gray: #f8f9fa; }
        
        body { 
            background: linear-gradient(135deg, #111 0%, #222 100%);
            display: flex; align-items: center; justify-content: center; 
            min-height: 100vh; margin: 0; font-family: 'Segoe UI', sans-serif;
        }

        /* Register Card */
        .register-card { 
            background: #fff; 
            padding: 45px 40px; 
            border-radius: 30px; 
            width: 100%; max-width: 450px; 
            box-shadow: 0 25px 50px rgba(0,0,0,0.4);
            position: relative;
        }

        .brand-logo { font-weight: 800; font-size: 2.2rem; letter-spacing: -2px; text-align: center; margin-bottom: 5px; color: var(--ss-dark); }
        .brand-logo span { color: var(--ss-red); }
        
        .sub-title { color: #888; font-size: 0.9rem; text-align: center; margin-bottom: 35px; }

        /* Input Styling */
        .input-group-custom { position: relative; margin-bottom: 20px; }
        .input-group-custom i { position: absolute; left: 15px; top: 16px; color: #bbb; transition: 0.3s; z-index: 10; }
        
        .form-control { 
            border-radius: 15px; padding: 14px 15px 14px 45px; 
            border: 2px solid #f0f0f0; background: #fafafa;
            transition: all 0.3s; font-size: 0.95rem; color: #333;
        }
        
        .form-control:focus { 
            border-color: var(--ss-red); background: #fff; box-shadow: none; 
        }
        
        .form-control:focus + i { color: var(--ss-red); }

        .form-label-small { font-weight: 700; font-size: 0.75rem; text-transform: uppercase; color: #999; margin-left: 5px; margin-bottom: 8px; display: block; }

        /* Register Button */
        .btn-register { 
            background: var(--ss-dark); color: white; width: 100%; 
            padding: 16px; border-radius: 15px; border: none; 
            font-weight: 700; transition: all 0.4s; font-size: 1rem;
            margin-top: 15px; letter-spacing: 1px;
        }
        
        .btn-register:hover { 
            background: var(--ss-red); transform: translateY(-3px); 
            box-shadow: 0 10px 20px rgba(225, 33, 40, 0.3); 
        }

        .footer-link { border-top: 1px solid #eee; margin-top: 30px; padding-top: 20px; text-align: center; }
        .footer-link a { color: var(--ss-red); font-weight: 700; text-decoration: none; transition: 0.3s; }
        .footer-link a:hover { color: #b01a1f; text-decoration: underline; }

        /* Badge Decor */
        .decoration-dot { position: absolute; top: 20px; right: 20px; width: 40px; height: 40px; background: var(--ss-gray); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #ddd; font-size: 1.2rem; }
    </style>
</head>
<body>

<div class="container d-flex justify-content-center">
    <div class="card register-card">
        <div class="decoration-dot">
            <i class="fas fa-user-plus"></i>
        </div>
        
        <h2 class="brand-logo">SUPER<span>WORLDS</span></h2>
        <p class="sub-title">สร้างบัญชีใหม่เพื่อเริ่มช้อปอุปกรณ์กีฬาระดับโลก</p>
        
        <form action="auth_action.php?act=register" method="POST">
            
            <div class="mb-3">
                <span class="form-label-small">ชื่อ-นามสกุล</span>
                <div class="input-group-custom">
                    <input type="text" name="fullname" class="form-control" placeholder="เช่น สมชาย สายลุย" required>
                    <i class="fas fa-user"></i>
                </div>
            </div>

            <div class="mb-3">
                <span class="form-label-small">อีเมลติดต่อ</span>
                <div class="input-group-custom">
                    <input type="email" name="email" class="form-control" placeholder="example@email.com" required>
                    <i class="fas fa-envelope"></i>
                </div>
            </div>

            <div class="mb-3">
                <span class="form-label-small">กำหนดรหัสผ่าน</span>
                <div class="input-group-custom">
                    <input type="password" name="password" class="form-control" placeholder="รหัสผ่านอย่างน้อย 6 ตัว" required>
                    <i class="fas fa-lock"></i>
                </div>
            </div>

            <button type="submit" class="btn btn-register shadow-sm">
                สร้างบัญชีผู้ใช้งาน
            </button>

            <div class="footer-link">
                <span class="small text-muted">เป็นสมาชิกอยู่แล้วใช่ไหม? </span>
                <a href="login.php" class="small">เข้าสู่ระบบที่นี่</a>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>