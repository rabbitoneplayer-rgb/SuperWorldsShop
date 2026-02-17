<?php
session_start();
include_once("connectdb.php");

// แนะนำให้ดึงเฉพาะของ User ที่ Login อยู่
$u_id = $_SESSION['user_id'] ?? 0;

// ดึงข้อมูลออเดอร์ (ดึงทั้งหมดตามโครงสร้างที่คุณใช้อยู่)
$sql = "SELECT * FROM orders ORDER BY o_id DESC"; 
$result = mysqli_query($conn, $sql);
?>
<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ประวัติการสั่งซื้อ | SUPERWORLDS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root { --ss-red: #e12128; --ss-dark: #111111; --ss-gray: #f4f7f6; }
        body { background-color: var(--ss-gray); font-family: 'Segoe UI', sans-serif; color: #333; }
        
        /* Header */
        .history-header { background: linear-gradient(135deg, var(--ss-dark) 0%, #333 100%); color: white; padding: 60px 0; border-radius: 0 0 50px 50px; margin-bottom: -40px; }
        
        /* Order Cards */
        .order-card { border: none; border-radius: 25px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); background: #fff; transition: 0.3s; margin-bottom: 25px; overflow: hidden; }
        .order-card:hover { transform: translateY(-5px); box-shadow: 0 15px 40px rgba(0,0,0,0.12); }
        
        .product-thumbnail-wrapper { width: 100px; height: 100px; background: #f8f9fa; border-radius: 20px; display: flex; align-items: center; justify-content: center; overflow: hidden; border: 1px solid #eee; }
        .product-thumbnail { max-width: 80%; max-height: 80%; object-fit: contain; }
        
        .status-pill { border-radius: 50px; padding: 6px 16px; font-weight: 700; font-size: 0.75rem; text-transform: uppercase; }
        .st-pending { background: #fff8e1; color: #f57c00; }
        .st-paid { background: #e3f2fd; color: #1976d2; }
        .st-shipped { background: #e8f5e9; color: #2e7d32; }
        .st-cancel { background: #f8d7da; color: #721c24; }
        
        .order-id { font-family: 'Courier New', Courier, monospace; font-weight: 800; color: var(--ss-red); letter-spacing: 1px; }
        .price-total { font-size: 1.4rem; font-weight: 800; color: var(--ss-dark); }
        .btn-detail { border-radius: 50px; padding: 8px 20px; font-weight: 600; border: 2px solid #eee; transition: 0.3s; background: white; }
        .btn-detail:hover { background: var(--ss-dark); color: white; border-color: var(--ss-dark); }

        /* Modal Detail */
        .modal-content { border-radius: 30px; border: none; }
        .modal-header { border-bottom: 1px solid #f1f1f1; padding: 25px 30px; }
        .detail-box { background: #f8f9fa; border-radius: 20px; padding: 20px; margin-bottom: 15px; }
        .detail-label { font-size: 0.7rem; font-weight: 800; text-transform: uppercase; color: #aaa; letter-spacing: 1px; margin-bottom: 5px; }
    </style>
</head>
<body>

<div class="history-header text-center">
    <div class="container">
        <h6 class="text-danger fw-bold text-uppercase mb-2" style="letter-spacing: 3px;">Order Journey</h6>
        <h1 class="fw-bold mb-0">รายการสั่งซื้อของฉัน</h1>
        <div class="mt-3">
            <a href="index.php" class="text-white-50 text-decoration-none small"><i class="fas fa-home me-1"></i> กลับหน้าหลัก</a>
        </div>
    </div>
</div>

<div class="container" style="margin-top: 20px; max-width: 900px;">
    <?php if (mysqli_num_rows($result) > 0): ?>
        <?php while($row = mysqli_fetch_array($result)): 
            // ระบบดึงรูปภาพ
            $items = explode(',', $row['o_product']);
            $first_item_name = preg_replace('/\s\(x\d+\)$/', '', trim($items[0]));
            $res_img = mysqli_query($conn, "SELECT p_image FROM products WHERE p_name = '".mysqli_real_escape_string($conn, $first_item_name)."' LIMIT 1");
            $img_row = mysqli_fetch_array($res_img);
            $p_image = (!empty($img_row['p_image'])) ? $img_row['p_image'] : 'https://placehold.co/400x400?text=Order';
            
            $status = $row['o_status'] ?? 'รอดำเนินการ';
            $st_class = "st-pending";
            if($status == 'ชำระเงินแล้ว') $st_class = "st-paid";
            if($status == 'ส่งแล้ว') $st_class = "st-shipped";
            if($status == 'ยกเลิกแล้ว') $st_class = "st-cancel";
        ?>
        
        <div class="card order-card">
            <div class="card-body p-4">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <div class="product-thumbnail-wrapper">
                            <img src="<?php echo $p_image; ?>" class="product-thumbnail" onerror="this.src='https://placehold.co/100x100?text=Box'">
                        </div>
                    </div>
                    
                    <div class="col ms-md-3">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <span class="order-id">ORDER #<?php echo str_pad($row['o_id'], 5, "0", STR_PAD_LEFT); ?></span>
                                <div class="text-muted small mt-1"><i class="far fa-calendar-alt me-1"></i> <?php echo date('d M Y | H:i', strtotime($row['o_date'])); ?></div>
                            </div>
                            <span class="status-pill <?php echo $st_class; ?>"><?php echo $status; ?></span>
                        </div>
                        
                        <div class="fw-bold text-dark text-truncate mb-1" style="max-width: 400px;">
                            <?php echo $row['o_product']; ?>
                        </div>
                        <div class="small text-muted">ผู้รับ: <?php echo $row['o_name']; ?></div>
                    </div>
                    
                    <div class="col-md-3 text-md-end mt-3 mt-md-0 border-start ps-4">
                        <div class="text-muted small mb-1">ยอดสุทธิ</div>
                        <div class="price-total mb-2">฿<?php echo number_format($row['o_total']); ?></div>
                        <button class="btn btn-detail w-100 btn-sm shadow-sm" 
                                onclick="showOrderDetail('<?php echo str_pad($row['o_id'], 5, '0', STR_PAD_LEFT); ?>', '<?php echo $status; ?>', '<?php echo $row['o_name']; ?>', '<?php echo $row['o_phone']; ?>', '<?php echo addslashes($row['o_address']); ?>', '<?php echo addslashes($row['o_product']); ?>', '<?php echo number_format($row['o_total']); ?>', '<?php echo $st_class; ?>')">
                            ดูรายละเอียด
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="text-center py-5">
            <i class="fas fa-shopping-basket fa-5x mb-4 opacity-10"></i>
            <h4 class="fw-bold text-muted">ยังไม่มีประวัติการสั่งซื้อ</h4>
            <a href="index.php" class="btn btn-danger rounded-pill px-5 mt-3 shadow">เริ่มช้อปปิ้งตอนนี้</a>
        </div>
    <?php endif; ?>
</div>

<div class="modal fade" id="orderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg">
            <div class="modal-header border-0">
                <h5 class="fw-bold mb-0">รายละเอียดคำสั่งซื้อ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 pt-0">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <span class="order-id fs-5" id="md-order-id"></span>
                    <span class="status-pill" id="md-status"></span>
                </div>

                <div class="detail-box">
                    <div class="detail-label">รายการสินค้า</div>
                    <div class="fw-bold text-dark" id="md-products"></div>
                </div>

                <div class="row g-2">
                    <div class="col-6">
                        <div class="detail-box">
                            <div class="detail-label">ชื่อผู้รับ</div>
                            <div class="fw-bold" id="md-name"></div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="detail-box">
                            <div class="detail-label">เบอร์โทรศัพท์</div>
                            <div class="fw-bold" id="md-phone"></div>
                        </div>
                    </div>
                </div>

                <div class="detail-box">
                    <div class="detail-label">ที่อยู่จัดส่ง</div>
                    <div class="small" id="md-address"></div>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-4 p-3 border-top">
                    <span class="fw-bold fs-5">ยอดชำระสุทธิ</span>
                    <span class="price-total text-danger" id="md-total"></span>
                </div>
            </div>
            <div class="modal-footer border-0 p-4 pt-0">
                <button type="button" class="btn btn-dark w-100 rounded-pill py-2 fw-bold" data-bs-dismiss="modal">ปิดหน้าต่าง</button>
            </div>
        </div>
    </div>
</div>

<script>
function showOrderDetail(id, status, name, phone, address, products, total, stClass) {
    document.getElementById('md-order-id').innerText = 'ORDER #' + id;
    document.getElementById('md-status').innerText = status;
    document.getElementById('md-status').className = 'status-pill ' + stClass;
    document.getElementById('md-name').innerText = name;
    document.getElementById('md-phone').innerText = phone;
    document.getElementById('md-address').innerText = address;
    document.getElementById('md-products').innerText = products;
    document.getElementById('md-total').innerText = '฿' + total;
    
    var myModal = new bootstrap.Modal(document.getElementById('orderModal'));
    myModal.show();
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>