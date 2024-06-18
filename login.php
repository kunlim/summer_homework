<?php
include 'connected.php'; //連結資料庫
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, password FROM users WHERE username = ?");
    if ($stmt) {
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();
        $stmt->bind_result($id, $hashed_password);
        $stmt->fetch();

        if ($stmt->num_rows > 0) {
            if (password_verify($password, $hashed_password)) {
                $_SESSION['user_id'] = $id;
                $_SESSION['username'] = $username;
                $_SESSION['loggedin'] = true;
                header('Location: index.php');
                exit();
            } else {
                $error = "帳號或密碼錯誤";
                //echo $hashed_password; //正確
                //echo $password;
            }
        } else {
            $error = "帳號或密碼錯誤";
        }

        $stmt->close();
    } else {
        die("準備語句失敗: " . $conn->error);
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>暑假作業</title>
    <link rel="stylesheet" href="api\css\login.css">   
</head>

<body>


<div class="Ltitle">
    實驗室成員登入
</div>

<div>
    

    <form class="manager-log-in-box" method="post">
        <div class="form-group">
        <img src="img\user.png" width="13px"><label for="username">帳號:</label>
            <input type="text" placeholder="請輸入帳號" class="form-control" id="username" name="username" required>
        </div>
        <div class="form-group">
        <img src="img\key.png" width="13px"><label for="password">密碼:</label>
            <input type="password" placeholder="請輸入密碼" class="form-control" id="password" name="password" required>
        </div>
        <button type="submit" class="btn btn-primary" style="font-family:DFKai-sb;font-size: 15px;">登入</button>
    </form>
    <?php if (isset($error)) { echo "<p color:red;>$error</p>"; } ?>
</div>
</body>
</html>
