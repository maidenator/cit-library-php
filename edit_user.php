<?php
require 'db.php';
$id = $_GET['id'];
// 1. Fetch current data
$stmt = mysqli_prepare($conn, "SELECT * FROM USER WHERE UserID = ?");
mysqli_stmt_bind_param($stmt, "s", $id);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (isset($_POST['update'])) {
    $status = $_POST['accountStatus'];
    // 2. Update status (e.g., manually restricting an account) [cite: 21]
    $updateSql = "UPDATE USER SET AccountStatus = ? WHERE UserID = ?";
    $updateStmt = mysqli_prepare($conn, $updateSql);
    mysqli_stmt_bind_param($updateStmt, "ss", $status, $id);
    mysqli_stmt_execute($updateStmt);
    header("Location: index.php");
}
?>

<form method="POST">
    <h3>Editing User: <?php echo $user['UserID']; ?></h3>
    <select name="accountStatus">
        <option value="Active" <?php if($user['AccountStatus'] == 'Active') echo 'selected'; ?>>Active</option>
        <option value="Restricted" <?php if($user['AccountStatus'] == 'Restricted') echo 'selected'; ?>>Restricted</option>
    </select>
    <button type="submit" name="update">Update Status</button>
</form>