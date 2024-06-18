<?php
include 'connected.php';

$new_username = "kunlinko";
$new_password = password_hash("cute1234", PASSWORD_DEFAULT); 

$sql = "INSERT INTO users (username, password) VALUES (?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $new_username, $new_password);

if ($stmt->execute()) {
    echo "新用戶建立成功";
} else {
    echo "錯誤: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>