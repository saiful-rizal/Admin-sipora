<?php
require_once "config.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $targetDir = "uploads/";

    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true); 
    }

    $fileName = basename($_FILES["fileUpload"]["name"]);
    $targetFile = $targetDir . $fileName;

    $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

    if ($fileType != "pdf") {
        echo "❌ Hanya file PDF yang diperbolehkan.";
        exit;
    }

    if (move_uploaded_file($_FILES["fileUpload"]["tmp_name"], $targetFile)) {

        $judul = $_POST["judul"];
        $sql = "INSERT INTO skripsi (judul, mahasiswa_id, pembimbing, abstrak, tahun pengajuan, file_path, jurusan_id, created_at) VALUES ('$judul', '$targetFile')";
        if (mysqli_query($conn, $sql)) {
            echo "✅ File berhasil diupload dan data tersimpan.<br>";
            echo "<a href='list.php'>Lihat Daftar Skripsi</a>";
        } else {
            echo "❌ Database error: " . mysqli_error($conn);
        }
    } else {
        echo "❌ Upload gagal.";
    }
}
?>

<form action="upload.php" method="post" enctype="multipart/form-data">
    Judul Skripsi: <input type="text" name="judul" required><br><br>
    Pilih File PDF: <input type="file" name="fileUpload" required><br><br>
    <input type="submit" value="Upload">
</form>