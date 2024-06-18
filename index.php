<?php
include 'connected.php';
session_start();

$session_timeout = 900; // seconds

// 檢查是否有登出請求
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['logout'])) {
    session_unset();
    session_destroy(); 
    header('Location: login.php');
    exit();
}
// 設置一個timeout時間 計算目前時間與活動時間差 ，只要閒置時間大於設置的timeout 系統就會登出
// 檢查是否已經設置最後活動時間
if (isset($_SESSION['last_activity'])) {
    // 計算閒置時間
    $idle_time = time() - $_SESSION['last_activity'];
    
    // 如果閒置時間超過了過期時間，銷毀 session 並重定向到登錄頁面
    if ($idle_time > $session_timeout) {
        session_unset();
        session_destroy();
        header('Location: login.php');
        exit();
    }
}

// 更新最後活動時間
$_SESSION['last_activity'] = time();

// 如果使用者未登錄，重定向到登錄頁面
if (!isset($_SESSION['loggedin'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$edit_id = null;
$edit_message = '';

// 確保上傳目錄存在
$uploadDir = 'uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// 處理新留言提交
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['message']) && !isset($_POST['edit_id'])) {
    $message = $_POST['message'];
    $stmt = $conn->prepare("INSERT INTO messages (user_id, message, created_at) VALUES (?, ?, NOW())");
    $stmt->bind_param("is", $user_id, $message);
    $stmt->execute();
    $message_id = $stmt->insert_id;
    $stmt->close();

    // 處理文件上傳
    if (isset($_FILES['files'])) { 
        foreach ($_FILES['files']['error'] as $index => $error) {
            if ($error != UPLOAD_ERR_OK) {
                //echo "File upload error: " . $error;
                continue;
            }
            
            $tmpName = $_FILES['files']['tmp_name'][$index];
            $originalName = $_FILES['files']['name'][$index];
            $filePath = $uploadDir . basename($originalName);
            $fileType = pathinfo($filePath, PATHINFO_EXTENSION);
            
            if (in_array($fileType, ['pdf', 'doc', 'docx', 'jpg'])) {
                if (move_uploaded_file($tmpName, $filePath)) {
                    $stmt = $conn->prepare("INSERT INTO message_files (message_id, file_path, original_name) VALUES (?, ?, ?)");
                    $stmt->bind_param("iss", $message_id, $filePath, $originalName);
                    $stmt->execute();
                    $stmt->close();
                } else {
                    echo "Failed to move uploaded file.";
                }
            } else {
                echo "Invalid file type.";
            }
        }
    }
}

// 處理留言更新
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['message']) && isset($_POST['edit_id'])) {
    $edit_id = $_POST['edit_id'];
    $message = $_POST['message'];
    $stmt = $conn->prepare("UPDATE messages SET message = ?, updated_at = NOW() WHERE id = ? AND user_id = ?");
    $stmt->bind_param("sii", $message, $edit_id, $user_id);
    $stmt->execute();
    $stmt->close();

    // 處理文件上傳
    if (isset($_FILES['files'])) {
        foreach ($_FILES['files']['error'] as $index => $error) {
            if ($error != UPLOAD_ERR_OK) {
                //echo "File upload error: " . $error;
                continue;
            }
            
            $tmpName = $_FILES['files']['tmp_name'][$index];
            $originalName = $_FILES['files']['name'][$index];
            $filePath = $uploadDir . basename($originalName);
            $fileType = pathinfo($filePath, PATHINFO_EXTENSION);
            
            if (in_array($fileType, ['pdf', 'doc', 'docx', 'jpg'])) {
                if (move_uploaded_file($tmpName, $filePath)) {
                    $stmt = $conn->prepare("INSERT INTO message_files (message_id, file_path, original_name) VALUES (?, ?, ?)");
                    $stmt->bind_param("iss", $edit_id, $filePath, $originalName);
                    $stmt->execute();
                    $stmt->close();
                } else {
                    echo "Failed to move uploaded file.";
                }
            } else {
                echo "Invalid file type.";
            }
        }
    }

    // 更新後重置 edit_id 和 edit_message 並重定向到主頁
    header("Location: ".$_SERVER['PHP_SELF']);
    exit();
}

// 處理留言刪除
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_id'])) {
    $delete_id = $_POST['delete_id'];
    $stmt = $conn->prepare("DELETE FROM messages WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $delete_id, $user_id);
    $stmt->execute();
    $stmt->close();
}

// 處理編輯請求
if (isset($_GET['edit_id'])) {
    $edit_id = $_GET['edit_id'];
    $stmt = $conn->prepare("SELECT message FROM messages WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $edit_id, $user_id);
    $stmt->execute();
    $stmt->bind_result($edit_message);
    $stmt->fetch();
    $stmt->close();
}

$messages = $conn->query("SELECT messages.id, messages.message, users.username, messages.created_at, messages.updated_at, messages.user_id FROM messages JOIN users ON messages.user_id = users.id ORDER BY messages.created_at DESC");

?>

<!DOCTYPE html>
<html>
<head>
    <title>留言板</title>
    <link href="api\css\index.css" rel="stylesheet">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="title">
    <h1>實驗室留言板</h1>
    <form action="" method="post" style="margin: 0;">
        <button type="submit" name="logout" class="btn btn-danger">登出</button>
    </form>
</div>

<div class="container">
    <hr>
    <?php while ($row = $messages->fetch_assoc()) { ?>
        <div class="card my-3" style="border-radius: 15px;">
            <div class="card-body">
                <h5 class="card-title">
                    <?php echo htmlspecialchars($row['username']); ?>
                    <span class="message-time">
                        <?php echo htmlspecialchars($row['created_at']); ?>
                        <?php if ($row['updated_at']) { ?>
                            (已編輯)
                        <?php } ?>
                    </span>
                </h5>
                <p class="card-text"><?php echo htmlspecialchars($row['message']); ?></p>
                <?php if ($row['user_id'] == $user_id) { // 編輯和刪除按鈕: 只有本人才能看 ?>
                    <div class="action-buttons">
                        <a href="?edit_id=<?php echo $row['id']; ?>" class="btn btn-warning btn-sm">編輯</a>
                        <form method="post" action="" style="display: inline;">
                            <input type="hidden" name="delete_id" value="<?php echo $row['id']; ?>">
                            <button type="submit" class="btn btn-danger btn-sm">刪除</button>
                        </form>
                    </div>
                <?php } ?>

                <!-- Fetch and display attached files -->
                <?php
                $message_id = $row['id'];
                $file_result = $conn->query("SELECT * FROM message_files WHERE message_id = $message_id");
                while ($file_row = $file_result->fetch_assoc()) {
                    echo '<a href="' . $file_row['file_path'] . '" download>' . htmlspecialchars($file_row['original_name']) . '</a><br>';
                }
                ?>
            </div>
        </div>
    <?php } ?>
    <form method="post" enctype="multipart/form-data">
        <div class="form-group">
            <textarea class="form-control" name="message" placeholder="輸入您的留言" required><?php echo htmlspecialchars($edit_message); ?></textarea>
            <?php if ($edit_id) { ?>
                <input type="hidden" name="edit_id" value="<?php echo $edit_id; ?>">
            <?php } ?>
        </div>
        <div class="form-group">
            <img src="img\upload.png" style="height:20px;">
            <label for="files">附加檔案</label>
            <input type="file" name="files[]" multiple>
        </div>
        <button type="submit" class="btn btn-primary"><?php echo $edit_id ? '更新留言' : '發佈留言'; ?></button>
    </form>
</div>
</body>
</html>