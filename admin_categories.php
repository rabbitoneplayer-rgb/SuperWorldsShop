// โค้ดส่วนจัดการ (ตัวอย่างการลบ)
if(isset($_GET['del'])) {
    $id = $_GET['del'];
    mysqli_query($conn, "DELETE FROM categories WHERE cat_id = '$id'");
    header("Location: admin_categories.php");
}

// ตารางแสดงรายการ Tag
$res = mysqli_query($conn, "SELECT * FROM categories ORDER BY cat_group, cat_id");
while($row = mysqli_fetch_array($res)) {
    echo "<tr>
            <td>{$row['cat_group']}</td>
            <td>{$row['cat_name']}</td>
            <td><a href='?del={$row['cat_id']}' class='btn btn-danger btn-sm'>ลบ Tag</a></td>
          </tr>";
}
