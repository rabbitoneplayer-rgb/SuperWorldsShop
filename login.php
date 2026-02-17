<?php
session_start();
include_once("connectdb.php");

// หากล็อกอินอยู่แล้วให้เด้งไปหน้าแรก
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    $login_type = isset($_POST['login_type']) ? $_POST['login_type'] : 'user';

    $sql = "SELECT * FROM users WHERE email = '$email'";
    $result = mysqli_query($conn, $sql);
    
    if ($row = mysqli_fetch_array($result)) {
        if (password_verify($password, $row['password'])) {
            if ($login_type == 'admin' && $row['is_admin'] != 1) {
                $error = "บัญชีนี้ไม่มีสิทธิ์เข้าถึงระบบผู้ดูแลระบบ";
            } else {
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['fullname'] = $row['fullname'];
                $_SESSION['is_admin'] = $row['is_admin'];
                header("Location: index.php");
                exit();
            }
        } else {
            $error = "รหัสผ่านไม่ถูกต้อง";
        }
    } else {
        $error = "ไม่พบอีเมลนี้ในระบบ";
    }
}
?>
<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>เข้าสู่ระบบ | SUPER WORLDS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root { --ss-red: #e12128; --ss-dark: #111; --ss-gray: #f8f9fa; }
        
        body { 
            background: linear-gradient(135deg, #111 0%, #333 100%);
            display: flex; align-items: center; justify-content: center; 
            min-height: 100vh; margin: 0; font-family: 'Segoe UI', sans-serif;
            color: #fff;
        }

        /* Login Card */
        .login-card { 
            background: rgba(255, 255, 255, 1); 
            padding: 50px 40px; 
            border-radius: 30px; 
            width: 100%; max-width: 440px; 
            box-shadow: 0 25px 50px rgba(0,0,0,0.3);
            color: #333;
            position: relative;
            overflow: hidden;
        }

        .brand-text { font-weight: 800; font-size: 2.2rem; letter-spacing: -2px; text-align: center; margin-bottom: 5px; color: var(--ss-dark); }
        .brand-text span { color: var(--ss-red); }
        
        /* Tab Switcher */
        .login-nav { 
            display: flex; background: #f0f0f0; border-radius: 15px; 
            padding: 6px; margin: 30px 0; 
        }
        .nav-item { 
            flex: 1; text-align: center; cursor: pointer; padding: 12px; 
            border-radius: 12px; font-size: 0.9rem; font-weight: 700; 
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); color: #888; 
        }
        .nav-item.active { background: var(--ss-dark); color: white; box-shadow: 0 4px 15px rgba(0,0,0,0.15); }
        .nav-item.admin-active.active { background: var(--ss-red); }

        /* Form Inputs */
        .input-group-custom { position: relative; margin-bottom: 20px; }
        .input-group-custom i { position: absolute; left: 15px; top: 16px; color: #bbb; transition: 0.3s; }
        .form-control { 
            border-radius: 15px; padding: 14px 15px 14px 45px; 
            border: 2px solid #eee; background: #fafafa;
            transition: all 0.3s; font-size: 0.95rem;
        }
        .form-control:focus { 
            border-color: var(--ss-dark); background: #fff; box-shadow: none; 
        }
        .form-control:focus + i { color: var(--ss-dark); }

        /* Login Button */
        .btn-login { 
            background: var(--ss-dark); color: white; width: 100%; 
            padding: 16px; border-radius: 15px; border: none; 
            font-weight: 700; transition: all 0.4s; font-size: 1rem;
            margin-top: 10px;
        }
        .btn-login:hover { background: #000; transform: translateY(-3px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
        .btn-admin { background: var(--ss-red); }
        .btn-admin:hover { background: #b01a1f; box-shadow: 0 10px 20px rgba(225, 33, 40, 0.2); }
        
        .alert { 
            border-radius: 15px; font-size: 0.85rem; border: none; 
            background-color: #fff5f5; color: var(--ss-red); 
            border: 1px solid #ffebeb;
        }

        .register-footer { border-top: 1px solid #eee; margin-top: 30px; padding-top: 20px; }
    </style>
</head>
<body>
    <div class="login-card">
        <h2 class="brand-text">SUPER<span>WORLDS</span></h2>
        <p id="mode-text" class="text-muted small text-center">เข้าสู่ระบบเพื่อจัดการข้อมูลอุปกรณ์กีฬาของคุณ</p>
        
        <?php if($error != ""): ?>
            <div class="alert alert-danger py-3 text-center mt-3 animate__animated animate__shakeX">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="login-nav" id="loginTypeNav">
            <div class="nav-item active" onclick="setMode('user')">
                <i class="fas fa-user me-2"></i>ลูกค้า
            </div>
            <div class="nav-item" onclick="setMode('admin')">
                <i class="fas fa-user-shield me-2"></i>แอดมิน
            </div>
        </div>
        
        <form method="POST" id="loginForm">
            <input type="hidden" name="login_type" id="login_type" value="user">

            <div class="input-group-custom">
                <input type="email" name="email" class="form-control" placeholder="อีเมลของคุณ" required>
                <i class="fas fa-envelope"></i>
            </div>
            
            <div class="input-group-custom">
                <input type="password" name="password" class="form-control" placeholder="รหัสผ่าน" required>
                <i class="fas fa-lock"></i>
            </div>
            
            <button type="submit" id="submitBtn" class="btn btn-login shadow">เข้าสู่ระบบ</button>
            
            <div class="text-center register-footer" id="registerLink">
                <span class="small text-muted">ยังไม่เป็นสมาชิก? </span>
                <a href="register.php" class="small text-danger-custom fw-bold text-decoration-none">สมัครสมาชิกที่นี่</a>
            </div>
        </form>
    </div>

    <script>
        function setMode(mode) {
            const navItems = document.querySelectorAll('.nav-item');
            const submitBtn = document.getElementById('submitBtn');
            const loginTypeInput = document.getElementById('login_type');
            const modeText = document.getElementById('mode-text');
            const registerLink = document.getElementById('registerLink');

            navItems.forEach(item => item.classList.remove('active', 'admin-active'));

            if (mode === 'admin') {
                navItems[1].classList.add('active', 'admin-active');
                submitBtn.classList.add('btn-admin');
                submitBtn.innerHTML = '<i class="fas fa-shield-alt me-2"></i>เข้าสู่ระบบแอดมิน';
                loginTypeInput.value = 'admin';
                modeText.innerHTML = 'โหมดผู้ดูแลระบบ (Admin Control Panel)';
                registerLink.style.display = 'none';
            } else {
                navItems[0].classList.add('active');
                submitBtn.classList.remove('btn-admin');
                submitBtn.innerHTML = 'เข้าสู่ระบบ';
                loginTypeInput.value = 'user';
                modeText.innerHTML = 'เข้าสู่ระบบเพื่อจัดการข้อมูลอุปกรณ์กีฬาของคุณ';
                registerLink.style.display = 'block';
            }
        }
    </script>
</body>
</html>