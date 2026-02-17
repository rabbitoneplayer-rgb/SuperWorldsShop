<?php include_once("connectdb.php"); ?>
<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <title>ค้นหาสินค้า - SUPERWORLDS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .product-img-search {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 5px;
        }
        .search-results-box {
            position: absolute;
            width: 100%;
            z-index: 1000;
            max-height: 400px;
            overflow-y: auto;
        }
        /* ปรับแต่งเพื่อให้ดูเข้ากับธีม */
        .list-group-item-action:hover {
            background-color: #f8f9fa;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8 text-center position-relative">
                <h2 class="mb-4 fw-bold">ค้นหาสินค้า <span class="text-danger">SUPER</span>WORLDS</h2>
                
                <form action="index.php" method="GET" class="d-flex shadow p-3 bg-white rounded-pill">
                    <input type="text" id="live_search" name="q" class="form-control border-0 ms-2" 
                           placeholder="กรอกชื่อสินค้าหรือแบรนด์ เช่น Nike, Air..." autocomplete="off" autofocus required>
                    <button type="submit" class="btn btn-danger rounded-pill px-4">
                        <i class="fas fa-search"></i> ค้นหา
                    </button>
                </form>

                <div id="search_results" class="search-results-box mt-2 text-start shadow rounded"></div>

                <div class="mt-4"><a href="index.php" class="text-muted text-decoration-none">← กลับหน้าหลัก</a></div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
        $(document).ready(function() {
            $("#live_search").keyup(function() {
                var input = $(this).val();
                
                if (input != "") {
                    $.ajax({
                        url: "fetch_search.php",
                        method: "POST",
                        data: { query: input },
                        success: function(data) {
                            $("#search_results").html(data);
                            $("#search_results").css("display", "block");
                        }
                    });
                } else {
                    $("#search_results").css("display", "none");
                }
            });
        });
    </script>
</body>
</html>