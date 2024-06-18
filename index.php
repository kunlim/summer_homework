<?php
include 'connected.php';
session_start();

if (!isset($_SESSION['loggedin'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$edit_id = null;
$edit_message = '';

// Ensure the uploads directory exists
$uploadDir = 'uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Handle new message submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['message']) && !isset($_POST['edit_id'])) {
    $message = $_POST['message'];
    $stmt = $conn->prepare("INSERT INTO messages (user_id, message, created_at) VALUES (?, ?, NOW())");
    $stmt->bind_param("is", $user_id, $message);
    $stmt->execute();
    $message_id = $stmt->insert_id;
    $stmt->close();

    // Handle file uploads
    if (isset($_FILES['files'])) {
        foreach ($_FILES['files']['tmp_name'] as $index => $tmpName) {
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

// Handle message update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['message']) && isset($_POST['edit_id'])) {
    $edit_id = $_POST['edit_id'];
    $message = $_POST['message'];
    $stmt = $conn->prepare("UPDATE messages SET message = ?, updated_at = NOW() WHERE id = ? AND user_id = ?");
    $stmt->bind_param("sii", $message, $edit_id, $user_id);
    $stmt->execute();
    $stmt->close();
    $edit_id = null; // Reset edit_id after updating

    // Handle file uploads for edit
    if (isset($_FILES['files'])) {
        foreach ($_FILES['files']['tmp_name'] as $index => $tmpName) {
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
}

// Handle message deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_id'])) {
    $delete_id = $_POST['delete_id'];
    $stmt = $conn->prepare("DELETE FROM messages WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $delete_id, $user_id);
    $stmt->execute();
    $stmt->close();
}

// Handle edit request
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

// Handle logout request
if (isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>留言板</title>
    <link href="api\css\index.css" rel="stylesheet">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .message-time {
            float: right;
            color: #888;
            font-size: 0.9em;
        }
        .action-buttons {
            position: absolute;
            bottom: 10px;
            right: 10px;
        }
        .card {
            position: relative;
        }
        .title {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .title h1 {
            font-family: 'DFKai-SB', serif;
            font-weight: bold;
            text-align: center;
            flex-grow: 1;
            color: #fff;
            background-color: #007bff;
            padding: 10px;
            margin: 0;
        }
    </style>
</head>
<body>

<div class="title">
    <h1>實驗室聊天室</h1>
    <form action="" method="post" style="margin: 10px;">
        <button type="submit" name="logout" class="btn btn-danger">登出</button>
    </form>
</div>

<div class="container">
    <hr>
    <h3>實驗室聊天</h3>
    <?php while ($row = $messages->fetch_assoc()) { ?>
        <div class="card my-3">
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
                <p class="card-text"><?php echo htmlspecialchars($row['message']); ?>
                <?php if ($row['updated_at']) { ?>
                            (已編輯)
                        <?php } ?></p>
                <?php if ($row['user_id'] == $user_id) { // 仅显示给留言作者的编辑和删除按钮 ?>
                    <div class="action-buttons">
                        <form method="post" action="" style="display: inline;">
                            <input type="hidden" name="delete_id" value="<?php echo $row['id']; ?>">
                            <button type="submit" class="btn btn-danger btn-sm">刪除</button>
                        </form>
                        <a href="?edit_id=<?php echo $row['id']; ?>" class="btn btn-warning btn-sm">编辑</a>
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
