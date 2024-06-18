<?php
include 'connected.php';

$new_username = "admin1";
$new_password = password_hash("admin1", PASSWORD_DEFAULT); // 使用 password_hash 函數加密密碼

$sql = "INSERT INTO users (username, password) VALUES (?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $new_username, $new_password);

if ($stmt->execute()) {
    echo "新用戶插入成功";
} else {
    echo "錯誤: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>