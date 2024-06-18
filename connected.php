<?php //資料庫連接檔案
$servername = "127.0.0.1";
$username = "root";
$password = 'teddy01050021'; 
$dbname = "my_database";  
$port = 3307; 

// 資料庫連接
$conn = new mysqli($servername, $username, $password, $dbname, $port);

// 檢查連接
if ($conn->connect_error) {
    die("連接失敗:" . $conn->connect_error);
}

//echo "....連接成功";

// closed connected
//$conn->close();
?>