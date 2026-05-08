<?php
require 'db.php';
$id = $_GET['id'];
$sql = "DELETE FROM USER WHERE UserID = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $id);
mysqli_stmt_execute($stmt);
header("Location: index.php");
?>