<?php
// 資料庫連接設定
$servername = "127.0.0.1";  // 或者使用 "localhost"
$username = "root";
$password = 'teddy01050021';  // XAMPP 預設的 root 使用者沒有密碼
$dbname = "my_database";  // 您要連接的資料庫名稱
$port = 3307;

// 創建資料庫連接
$conn = new mysqli($servername, $username, $password, $dbname, $port);

// 檢查連接
if ($conn->connect_error) {
    die("連接失敗:" . $conn->connect_error);
}

//echo "....連接成功";

// 在這裡可以進行後續的資料庫操作

// 關閉連接
//$conn->close();
?>