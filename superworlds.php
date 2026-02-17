<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Focus Sport Store | อุปกรณ์กีฬาระดับมืออาชีพ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --ss-red: #e12128;
            --ss-dark: #1a1a1a;
        }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f4f4; }
        
        /* Navbar Custom */
        .navbar { background-color: var(--ss-dark) !important; }
        .navbar-brand { font-weight: 800; letter-spacing: 1px; color: #fff !important; }
        .navbar-brand span { color: var(--ss-red); }

        /* Hero Banner */
        .hero-section {
            background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('https://images.unsplash.com/photo-1534438327276-14e5300c3a48?q=80&w=2070&auto=format&fit=crop');
            background-size: cover;
            background-position: center;
            height: 400px;
            display: flex;
            align-items: center;
            color: white;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
        }

        /* Product Card */
        .product-card {
            border: none;
            transition: transform 0.3s, shadow 0.3s;
            border-radius: 10px;
            overflow: hidden;
            background: #fff;
        }
        .product-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .product-img {
            height: 200px;
            object-fit: cover;
            background: #f8f8f8;
        }
        .price-tag {
            color: var(--ss-red);
            font-size: 1.25rem;
            font-weight: bold;
        }
        .badge-sale {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: var(--ss-red);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
        }
        .category-btn {
            border-radius: 50px;
            padding: 10px 25px;
            margin: 5px;
            font-weight: 600;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark sticky-top">
    <div class="container">
        <a class="navbar-brand" href="#"><i class="fas fa-football-ball"></i> SUPER<span>WORLDS</span></a>
        <div class="d-flex">
            <a href="#" class="text-white me-3"><i class="fas fa-search"></i></a>
            <a href="#" class="text-white me-3"><i class="fas fa-shopping-cart"></i></a>
            <a href="#" class="text-white"><i class="fas fa-user"></i></a>
        </div>
    </div>
</nav>

<header class="hero-section">
    <div class="container text-center">
        <h1 class="display-3 fw-bold">NEW ARRIVALS 2026</h1>
        <p class="lead">ยกระดับการเล่นของคุณด้วยอุปกรณ์ระดับโปร</p>
        <a href="#shop" class="btn btn-danger btn-lg px-5 mt-3">ช้อปเลย</a>
    </div>
</header>

<div class="container my-5" id="shop">
    <div class="text-center mb-5">
        <button class="btn btn-dark category-btn">ทั้งหมด</button>
        <button class="btn btn-outline-dark category-btn">ฟุตบอล</button>
        <button class="btn btn-outline-dark category-btn">บาสเกตบอล</button>
        <button class="btn btn-outline-dark category-btn">วิ่ง</button>
        <button class="btn btn-outline-dark category-btn">เทนนิส</button>
    </div>

    <div class="row g-4">
        <div class="col-6 col-md-4 col-lg-3">
            <div class="card product-card h-100 shadow-sm">
                <span class="badge-sale">SALE</span>
                <img src="https://images.unsplash.com/photo-1542291026-7eec264c27ff?q=80&w=2070&auto=format&fit=crop" class="product-img card-img-top" alt="Sneaker">
                <div class="card-body">
                    <p class="text-muted mb-1 small">NIKE</p>
                    <h5 class="card-title h6 fw-bold">Air Max Speed Pro</h5>
                    <p class="price-tag mb-0">฿3,500 <del class="text-muted fs-6">฿4,200</del></p>
                    <button class="btn btn-dark btn-sm w-100 mt-3" data-bs-toggle="modal" data-bs-target="#orderModal">สั่งซื้อ</button>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-4 col-lg-3">
            <div class="card product-card h-100 shadow-sm">
                <img src="https://images.unsplash.com/photo-1514907283155-ea5f4094c70c?q=80&w=2070&auto=format&fit=crop" class="product-img card-img-top" alt="Basketball">
                <div class="card-body">
                    <p class="text-muted mb-1 small">WILSON</p>
                    <h5 class="card-title h6 fw-bold">Official Game Ball</h5>
                    <p class="price-tag mb-0">฿1,890</p>
                    <button class="btn btn-dark btn-sm w-100 mt-3" data-bs-toggle="modal" data-bs-target="#orderModal">สั่งซื้อ</button>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-4 col-lg-3">
            <div class="card product-card h-100 shadow-sm">
                <img src="https://images.unsplash.com/photo-1511886929837-399aa992ce00?q=80&w=1964&auto=format&fit=crop" class="product-img card-img-top" alt="Football">
                <div class="card-body">
                    <p class="text-muted mb-1 small">ADIDAS</p>
                    <h5 class="card-title h6 fw-bold">World Cup Match Ball</h5>
                    <p class="price-tag mb-0">฿4,500</p>
                    <button class="btn btn-dark btn-sm w-100 mt-3" data-bs-toggle="modal" data-bs-target="#orderModal">สั่งซื้อ</button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="orderModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-dark text-white">
        <h5 class="modal-title">ยืนยันรายการสั่งซื้อ</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form method="post" action="">
            <div class="mb-3">
                <label class="form-label">ชื่อผู้สั่งซื้อ</label>
                <input type="text" name="fullname" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">เบอร์โทรศัพท์</label>
                <input type="text" name="phone" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">เลือกสี/ไซส์</label>
                <input type="color" name="color" class="form-control form-control-color w-100" value="#e12128">
            </div>
            <button type="submit" name="Submit" class="btn btn-danger w-100">ยืนยันการสั่งซื้อ</button>
        </form>
      </div>
    </div>
  </div>
</div>

<footer class="bg-dark text-white text-center py-4 mt-5">
    <p class="mb-0">© 2026 Focus Sport Store. All Rights Reserved.</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>