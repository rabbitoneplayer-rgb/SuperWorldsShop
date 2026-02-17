<?php
include_once("connectdb.php");

// 1. รับค่า ID ออเดอร์
$o_id = isset($_GET['o_id']) ? mysqli_real_escape_string($conn, $_GET['o_id']) : '';

if ($o_id == '') {
    echo "<div class='alert alert-danger rounded-4'><i class='fas fa-exclamation-circle me-2'></i>ไม่พบรหัสใบสั่งซื้อ</div>";
    exit;
}

// 2. ดึงข้อมูลออเดอร์ (ดึงจากตารางเดียวตามโครงสร้างจริงของคุณ)
$sql_order = "SELECT * FROM orders WHERE o_id = '$o_id'";
$result = mysqli_query($conn, $sql_order);
$order = mysqli_fetch_array($result);

if (!$order) {
    echo "<div class='alert alert-warning rounded-4'><i class='fas fa-search me-2'></i>ไม่พบข้อมูลออเดอร์ในระบบ</div>";
    exit;
}
?>

<style>
    .info-label { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color: #999; font-weight: 800; }
    .order-item-card { background: #fff; border: 1px solid #eee; border-radius: 15px; padding: 20px; transition: 0.3s; }
    .bg-invoice { background-color: #fcfcfc; border: 1px dashed #ddd; border-radius: 15px; }
    .price-large { font-family: 'Segoe UI', sans-serif; font-weight: 800; color: #e12128; font-size: 1.5rem; }
    @media print { .no-print { display: none; } .modal-content { border: none; } }
</style>

<div class="d-flex justify-content-between align-items-center mb-4 px-2">
    <div>
        <span class="info-label">วันที่ทำรายการ</span>
        <div class="fw-bold"><i class="far fa-calendar-alt me-1 text-muted"></i> <?php echo date('d/m/Y H:i', strtotime($order['o_date'])); ?></div>
    </div>
    <div class="text-end">
        <span class="info-label">สถานะออเดอร์</span>
        <div>
            <span class="badge bg-dark rounded-pill px-3 py-2">
                <i class="fas fa-circle text-success me-1" style="font-size: 8px;"></i> <?php echo $order['o_status']; ?>
            </span>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="p-3 h-100 bg-invoice">
            <h6 class="info-label mb-3 text-danger"><i class="fas fa-user-circle me-1"></i> ข้อมูลผู้รับ</h6>
            <div class="fw-bold fs-5 mb-1"><?php echo $order['o_name']; ?></div>
            <div class="text-dark"><i class="fas fa-phone me-2 text-muted"></i><?php echo $order['o_phone']; ?></div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="p-3 h-100 bg-invoice">
            <h6 class="info-label mb-3 text-danger"><i class="fas fa-map-marker-alt me-1"></i> สถานที่จัดส่ง</h6>
            <div class="text-dark small" style="line-height: 1.6;">
                <?php echo !empty($order['o_address']) ? $order['o_address'] : '<span class="text-muted italic">ไม่ได้ระบุที่อยู่จัดส่ง</span>'; ?>
            </div>
        </div>
    </div>
</div>

<h6 class="info-label mb-3 px-2">รายละเอียดสินค้า</h6>
<div class="order-item-card shadow-sm mb-4">
    <div class="d-flex align-items-center">
        <div class="bg-danger bg-opacity-10 p-3 rounded-circle me-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
            <i class="fas fa-box-open text-danger fa-lg"></i>
        </div>
        
        <div class="flex-grow-1">
            <div class="fw-bold text-dark fs-5"><?php echo $order['o_product']; ?></div>
            <div class="d-flex gap-2 mt-1">
                <?php if(!empty($order['o_size'])): ?>
                    <span class="badge border text-dark fw-normal rounded-pill bg-white">ไซส์: <?php echo $order['o_size']; ?></span>
                <?php endif; ?>
                <?php if(!empty($order['o_color'])): ?>
                    <span class="badge border text-dark fw-normal rounded-pill bg-white">สี: <?php echo $order['o_color']; ?></span>
                <?php endif; ?>
            </div>
            <small class="text-muted d-block mt-1">รหัสสั่งซื้อ: #<?php echo str_pad($order['o_id'], 5, "0", STR_PAD_LEFT); ?></small>
        </div>
        
        <div class="text-end">
            <span class="info-label d-block">ราคาสุทธิ</span>
            <div class="price-large">฿<?php echo number_format($order['o_total']); ?></div>
        </div>
    </div>
</div>

<div class="rounded-4 overflow-hidden border">
    <div class="p-3 bg-dark text-white d-flex justify-content-between align-items-center">
        <span class="fw-bold">ยอดเงินที่ชำระแล้ว (Net Total)</span>
        <span class="fs-3 fw-bold">฿<?php echo number_format($order['o_total']); ?></span>
    </div>
</div>

<div class="mt-4 text-center no-print">
    <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-4" onclick="window.print()">
        <i class="fas fa-print me-2"></i>พิมพ์ใบจัดส่งสินค้า
    </button>
</div>