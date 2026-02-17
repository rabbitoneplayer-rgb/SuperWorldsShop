<?php
session_start();
include_once("connectdb.php");

// 1. เช็คสิทธิ์แอดมิน
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: index.php");
    exit();
}

// 2. Logic การอัปเดตสถานะ (ย้ายไป admin_action.php หรือทำในนี้ก็ได้)
if (isset($_GET['action']) && isset($_GET['o_id'])) {
    $o_id = mysqli_real_escape_string($conn, $_GET['o_id']);
    $new_status = "";
    
    if ($_GET['action'] == 'approve') $new_status = "ชำระเงินแล้ว";
    if ($_GET['action'] == 'ship') $new_status = "ส่งแล้ว";
    if ($_GET['action'] == 'cancel') $new_status = "ยกเลิกแล้ว";
    if ($_GET['action'] == 'refund') $new_status = "คืนเงินแล้ว";
    
    if ($new_status != "") {
        $update_sql = "UPDATE orders SET o_status = '$new_status' WHERE o_id = '$o_id'";
        mysqli_query($conn, $update_sql);
        header("Location: admin_orders.php"); 
        exit();
    }
}

// 3. ดึงข้อมูลออเดอร์ทั้งหมด
$sql = "SELECT * FROM orders ORDER BY o_id DESC"; 
$result = mysqli_query($conn, $sql);
?>
<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>จัดการออเดอร์ (Admin) | SUPERWORLDS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root { --ss-red: #e12128; --ss-dark: #111111; --ss-gray: #f8f9fa; }
        body { background-color: var(--ss-gray); font-family: 'Segoe UI', Roboto, sans-serif; color: #333; }
        
        .admin-container { max-width: 1200px; margin-top: 50px; }
        .admin-card { border: none; border-radius: 24px; box-shadow: 0 15px 45px rgba(0,0,0,0.05); background: #fff; overflow: hidden; }
        
        /* Table Style */
        .table thead { background-color: var(--ss-dark); color: white; }
        .table thead th { font-weight: 700; text-transform: uppercase; font-size: 0.8rem; letter-spacing: 1px; padding: 20px; border: none; }
        .table tbody td { padding: 20px; border-bottom: 1px solid #f0f0f0; vertical-align: middle; }

        /* Status Badges */
        .status-badge { border-radius: 50px; padding: 6px 16px; font-weight: 700; font-size: 0.75rem; display: inline-block; }
        .st-pending { background: #fff3cd; color: #856404; }
        .st-paid { background: #d1ecf1; color: #0c5460; }
        .st-shipped { background: #d4edda; color: #155724; }
        .st-cancelled { background: #f8d7da; color: #721c24; }
        .st-refunded { background: #e2e3e5; color: #383d41; }

        /* Action Buttons */
        .btn-action { width: 35px; height: 35px; border-radius: 10px; display: inline-flex; align-items: center; justify-content: center; transition: 0.3s; margin: 0 2px; border: none; text-decoration: none; }
        .btn-view { background: #f8f9fa; color: #333; border: 1px solid #ddd; }
        .btn-paid { background: #17a2b8; color: white; }
        .btn-ship { background: #28a745; color: white; }
        .btn-cancel { background: #dc3545; color: white; }
        .btn-action:hover { transform: translateY(-3px); box-shadow: 0 5px 10px rgba(0,0,0,0.1); color: #fff; }
        .btn-view:hover { color: var(--ss-red); border-color: var(--ss-red); }

        .order-id-label { font-family: 'Courier New', monospace; font-weight: 800; color: var(--ss-red); }
        .address-box { font-size: 0.8rem; color: #666; max-width: 250px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    </style>
</head>
<body>

<div class="container admin-container mb-5">
    <div class="d-flex justify-content-between align-items-end mb-4">
        <div>
            <h6 class="text-danger fw-bold text-uppercase mb-1" style="letter-spacing: 2px;">Administrator Control</h6>
            <h2 class="fw-bold m-0"><i class="fas fa-box-open me-2"></i>จัดการรายการสั่งซื้อ</h2>
        </div>
        <div class="d-flex gap-2">
            <a href="admin_products.php" class="btn btn-outline-dark rounded-pill px-4">จัดการสินค้า</a>
            <a href="index.php" class="btn btn-dark rounded-pill px-4 shadow-sm"><i class="fas fa-home me-2"></i>หน้าแรก</a>
        </div>
    </div>

    <div class="card admin-card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">ออเดอร์</th>
                        <th>ลูกค้า / ที่อยู่จัดส่ง</th>
                        <th class="text-center">ยอดชำระ</th>
                        <th class="text-center">สถานะ</th>
                        <th class="text-end pe-4">การจัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(mysqli_num_rows($result) > 0): ?>
                        <?php while($row = mysqli_fetch_array($result)): ?>
                        <tr>
                            <td class="ps-4">
                                <span class="order-id-label">#<?php echo str_pad($row['o_id'], 5, "0", STR_PAD_LEFT); ?></span>
                                <div class="text-muted small"><?php echo date('d/m/y H:i', strtotime($row['o_date'])); ?></div>
                            </td>
                            <td>
                                <div class="fw-bold"><?php echo $row['o_name']; ?></div>
                                <div class="text-muted small mb-1"><i class="fas fa-phone-alt me-1 text-danger"></i> <?php echo $row['o_phone']; ?></div>
                                <div class="address-box" title="<?php echo $row['o_address']; ?>">
                                    <i class="fas fa-map-marker-alt me-1"></i> <?php echo $row['o_address']; ?>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="fw-bold text-dark">฿<?php echo number_format($row['o_total']); ?></span>
                            </td>
                            <td class="text-center">
                                <?php 
                                    $status = $row['o_status'] ?? 'รอดำเนินการ';
                                    $class = "st-pending";
                                    if($status == 'ชำระเงินแล้ว') $class = "st-paid";
                                    if($status == 'ส่งแล้ว') $class = "st-shipped";
                                    if($status == 'ยกเลิกแล้ว') $class = "st-cancelled";
                                    if($status == 'คืนเงินแล้ว') $class = "st-refunded";
                                ?>
                                <span class="status-badge <?php echo $class; ?> shadow-sm"><?php echo $status; ?></span>
                            </td>
                            <td class="text-end pe-4">
                                <button onclick="viewOrderDetails(<?php echo $row['o_id']; ?>)" class="btn-action btn-view" title="ดูรายละเอียด"><i class="fas fa-eye"></i></button>
                                
                                <div class="d-inline-block border-start ms-2 ps-2">
                                    <button onclick="confirmStatus(<?php echo $row['o_id']; ?>, 'approve', 'ยืนยันการชำระเงิน?')" class="btn-action btn-paid" title="ยืนยันการจ่ายเงิน"><i class="fas fa-check"></i></button>
                                    <button onclick="confirmStatus(<?php echo $row['o_id']; ?>, 'ship', 'สินค้าถูกจัดส่งแล้ว?')" class="btn-action btn-ship" title="แจ้งส่งของ"><i class="fas fa-truck"></i></button>
                                    <button onclick="confirmStatus(<?php echo $row['o_id']; ?>, 'cancel', 'ยกเลิกออเดอร์นี้?')" class="btn-action btn-cancel" title="ยกเลิก"><i class="fas fa-times"></i></button>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center py-5 text-muted">ยังไม่มีรายการสั่งซื้อในขณะนี้</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="orderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 25px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold"><i class="fas fa-file-invoice me-2 text-danger"></i>รายละเอียดออเดอร์ <span id="modal-oid"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" id="order-details-body">
                <div class="text-center py-5"><i class="fas fa-spinner fa-spin fa-2x text-muted"></i></div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">ปิดหน้าต่าง</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
    // ฟังก์ชันดูรายละเอียดออเดอร์
    function viewOrderDetails(oid) {
        $('#modal-oid').text('#' + oid.toString().padStart(5, '0'));
        $('#orderModal').modal('show');
        $('#order-details-body').html('<div class="text-center py-5"><i class="fas fa-spinner fa-spin fa-2x text-muted"></i></div>');

        // เรียกไฟล์ AJAX เพื่อดึงข้อมูลสินค้าในออเดอร์
        $.ajax({
            url: 'get_order_details.php',
            type: 'GET',
            data: { o_id: oid },
            success: function(html) {
                $('#order-details-body').html(html);
            },
            error: function() {
                $('#order-details-body').html('<div class="alert alert-danger">ไม่สามารถโหลดข้อมูลได้</div>');
            }
        });
    }

    function confirmStatus(id, action, message) {
        Swal.fire({
            title: 'ยืนยันการดำเนินการ?',
            text: message,
            icon: action === 'cancel' ? 'warning' : 'question',
            showCancelButton: true,
            confirmButtonColor: action === 'cancel' ? '#dc3545' : '#111',
            confirmButtonText: 'ยืนยัน',
            cancelButtonText: 'ยกเลิก',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'admin_orders.php?o_id=' + id + '&action=' + action;
            }
        })
    }
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>