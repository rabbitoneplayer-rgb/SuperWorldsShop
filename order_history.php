<?php
session_start();
include_once("connectdb.php");

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }
$u_id = $_SESSION['user_id'];
$sql = "SELECT * FROM orders WHERE u_id = '$u_id' ORDER BY o_id DESC"; 
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
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <style>
        :root { --ss-red: #e12128; --ss-dark: #111111; --ss-gray: #f8f9fa; }
        body { background-color: var(--ss-gray); font-family: 'Kanit', sans-serif; color: #333; }
        .history-header { background: linear-gradient(135deg, var(--ss-dark) 0%, #333 100%); color: white; padding: 60px 0; border-radius: 0 0 50px 50px; margin-bottom: 20px; }
        .order-card { border: none; border-radius: 25px; background: #fff; transition: 0.3s; margin-bottom: 20px; border: 1px solid #eee; }
        .product-thumbnail-wrapper { width: 80px; height: 80px; background: #fff; border-radius: 15px; display: flex; align-items: center; justify-content: center; border: 1px solid #f0f0f0; }
        .product-thumbnail { max-width: 90%; max-height: 90%; object-fit: contain; }
        .status-pill { border-radius: 50px; padding: 5px 15px; font-weight: 700; font-size: 0.7rem; text-transform: uppercase; }
        .st-pending { background: #fff8e1; color: #f57c00; }
        .st-paid { background: #e3f2fd; color: #1976d2; }
        .st-shipped { background: #e8f5e9; color: #2e7d32; }
        .st-cancel { background: #f8d7da; color: #721c24; }
        .order-id { font-weight: 800; color: var(--ss-red); letter-spacing: 1px; }
        .price-total { font-size: 1.3rem; font-weight: 800; color: var(--ss-dark); }
        .modal-content { border-radius: 30px; border: none; overflow: hidden; }
        .detail-box { background: #f8f9fa; border-radius: 15px; padding: 15px; margin-bottom: 10px; border: 1px solid #eee; }
        .detail-label { font-size: 0.65rem; font-weight: 800; text-transform: uppercase; color: #aaa; letter-spacing: 1px; margin-bottom: 3px; }
    </style>
</head>
<body>

<div class="history-header text-center">
    <div class="container">
        <h6 class="text-danger fw-bold text-uppercase mb-2" style="letter-spacing: 3px;">USER DASHBOARD</h6>
        <h1 class="fw-bold mb-0">ประวัติการสั่งซื้อของฉัน</h1>
        <div class="mt-3">
            <a href="index.php" class="text-white-50 text-decoration-none small"><i class="fas fa-home me-1"></i> กลับหน้าหลัก</a>
        </div>
    </div>
</div>

<div class="container pb-5" style="max-width: 900px;">
    <?php if (mysqli_num_rows($result) > 0): ?>
        <?php while($row = mysqli_fetch_array($result)): 
            $items = explode(',', $row['o_product']);
            $clean_name = trim(preg_replace('/\s\(x\d+\)$/', '', $items[0]));
            $img_query = mysqli_query($conn, "SELECT p_image FROM products WHERE p_name = '".mysqli_real_escape_string($conn, $clean_name)."' LIMIT 1");
            $img_data = mysqli_fetch_array($img_query);
            $p_image = (!empty($img_data['p_image'])) ? $img_data['p_image'] : 'https://placehold.co/100x100?text=Product';

            $status = $row['o_status'] ?? 'รอดำเนินการ';
            $st_class = ($status == 'ชำระเงินแล้ว') ? "st-paid" : (($status == 'ส่งแล้ว') ? "st-shipped" : (($status == 'ยกเลิกแล้ว') ? "st-cancel" : "st-pending"));
        ?>
        
        <div class="card order-card shadow-sm">
            <div class="card-body p-4">
                <div class="row align-items-center">
                    <div class="col-auto"><div class="product-thumbnail-wrapper"><img src="<?php echo $p_image; ?>" class="product-thumbnail"></div></div>
                    <div class="col ms-md-2">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="order-id">#<?php echo str_pad($row['o_id'], 5, "0", STR_PAD_LEFT); ?></span>
                            <span class="status-pill <?php echo $st_class; ?>"><?php echo $status; ?></span>
                        </div>
                        <div class="fw-bold text-dark text-truncate mb-1" style="max-width: 350px;"><?php echo $row['o_product']; ?></div>
                        <div class="small text-muted"><?php echo date('d/m/Y H:i', strtotime($row['o_date'])); ?></div>
                    </div>
                    <div class="col-md-3 text-md-end mt-3 mt-md-0 ps-md-4 border-start">
                        <div class="price-total mb-2">฿<?php echo number_format($row['o_total']); ?></div>
                        <button class="btn btn-dark btn-sm rounded-pill px-4 w-100 fw-bold py-2" 
                                onclick='showOrderData(<?php echo json_encode([
                                    "id" => str_pad($row["o_id"], 5, "0", STR_PAD_LEFT),
                                    "status" => $status,
                                    "name" => $row["o_name"],
                                    "phone" => $row["o_phone"],
                                    "address" => $row["o_address"],
                                    "product" => $row["o_product"],
                                    "total" => number_format($row["o_total"]),
                                    "stClass" => $st_class
                                ], JSON_UNESCAPED_UNICODE); ?>)'>
                            รายละเอียด
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="text-center py-5 opacity-50"><h4>ยังไม่มีประวัติการสั่งซื้อ</h4></div>
    <?php endif; ?>
</div>

<div class="modal fade" id="orderInfoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg">
            <div class="modal-header border-0 p-4 pb-0">
                <h5 class="fw-bold">รายละเอียดการสั่งซื้อ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <span class="order-id fs-5" id="v-id"></span>
                    <span id="v-status" class="status-pill"></span>
                </div>
                <div class="detail-box">
                    <div class="detail-label">รายการสินค้า</div>
                    <div class="fw-bold small" id="v-items"></div>
                </div>
                <div class="row g-2">
                    <div class="col-6"><div class="detail-box"><div class="detail-label">ชื่อผู้รับ</div><div class="small fw-bold" id="v-name"></div></div></div>
                    <div class="col-6"><div class="detail-box"><div class="detail-label">เบอร์โทรศัพท์</div><div class="small fw-bold" id="v-phone"></div></div></div>
                </div>
                <div class="detail-box">
                    <div class="detail-label">ที่อยู่จัดส่ง</div>
                    <div class="small" id="v-address"></div>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                    <span class="fw-bold fs-5">ยอดชำระทั้งสิ้น</span>
                    <span class="price-total text-danger" id="v-total"></span>
                </div>
                <button type="button" class="btn btn-dark w-100 rounded-pill py-2 mt-4 fw-bold" data-bs-dismiss="modal">ปิดหน้าต่าง</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ฟังก์ชันใหม่ที่รองรับการส่งค่าแบบ Object เพื่อความเสถียร
function showOrderData(data) {
    document.getElementById('v-id').innerText = 'ORDER #' + data.id;
    document.getElementById('v-status').innerText = data.status;
    document.getElementById('v-status').className = 'status-pill ' + data.stClass;
    document.getElementById('v-name').innerText = data.name;
    document.getElementById('v-phone').innerText = data.phone;
    document.getElementById('v-address').innerText = data.address;
    document.getElementById('v-items').innerText = data.product;
    document.getElementById('v-total').innerText = '฿' + data.total;
    
    var orderModal = new bootstrap.Modal(document.getElementById('orderInfoModal'));
    orderModal.show();
}
</script>
</body>
</html>
