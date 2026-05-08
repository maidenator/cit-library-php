<?php
session_start();
require 'db.php';

// Security Check: Only Faculty and Admins can edit the catalog
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'Faculty' && $_SESSION['role'] !== 'Admin')) {
    header("Location: index.php");
    exit();
}

$id = $_GET['id'];
$msg = "";

// 1. Fetch the current book details from the database
$sql = "SELECT * FROM BOOK WHERE BookID = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$book = mysqli_fetch_assoc($result);

if (!$book) {
    die("Error: Resource not found in the catalog.");
}

// 2. Handle the Update Request
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['title'];
    $author = $_POST['author'];
    $isbn = $_POST['isbn'];
    $year = $_POST['year'];
    $status = $_POST['status'];

    $update_sql = "UPDATE BOOK SET Title=?, Author=?, ISBN=?, PublishedYear=?, AvailabilityStatus=? WHERE BookID=?";
    $update_stmt = mysqli_prepare($conn, $update_sql);
    mysqli_stmt_bind_param($update_stmt, "ssssss", $title, $author, $isbn, $year, $status, $id);

    if (mysqli_stmt_execute($update_stmt)) {
        header("Location: manage_books.php?msg=Resource updated successfully");
        exit();
    } else {
        $msg = "Update failed: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Resource | CIT-U Library</title>
    <style>
        body { font-family: sans-serif; margin: 0; background: #f9f9f9; color: #333; }
        .header { background: #800000; color: white; padding: 20px 40px; border-bottom: 5px solid #f4b400; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { margin: 0; font-size: 20px; letter-spacing: 1px; }
        .container { padding: 50px 40px; display: flex; justify-content: center; }
        
        /* Thematic Card similar to add_book.php */
        .form-card { background: white; border: 2px solid #800000; padding: 40px; width: 100%; max-width: 500px; box-shadow: 8px 8px 0px #f4b400; }
        .form-card h2 { color: #800000; margin-top: 0; margin-bottom: 25px; text-transform: uppercase; letter-spacing: 1px; border-left: 5px solid #800000; padding-left: 15px; }
        
        .form-group { margin-bottom: 20px; }
        label { display: block; font-weight: bold; font-size: 11px; margin-bottom: 8px; color: #666; text-transform: uppercase; }
        input, select { width: 100%; padding: 12px; border: 1px solid #ddd; box-sizing: border-box; font-size: 14px; }
        input:focus { border-color: #800000; outline: none; }
        
        .btn-update { width: 100%; background: #800000; color: white; border: none; padding: 15px; font-weight: bold; cursor: pointer; text-transform: uppercase; letter-spacing: 1px; margin-top: 10px; }
        .btn-update:hover { opacity: 0.9; }
        .cancel-link { display: block; text-align: center; margin-top: 15px; color: #666; text-decoration: none; font-size: 13px; }
    </style>
</head>
<body>

<div class="header">
    <h1>CATALOG MANAGEMENT</h1>
    <a href="manage_books.php" style="color: #f4b400; text-decoration: none; font-weight: bold;">← DISCARD CHANGES</a>
</div>

<div class="container">
    <div class="form-card">
        <h2>Modify Resource</h2>
        
        <?php if($msg): ?>
            <p style="color: #e74c3c; font-weight: bold; font-size: 13px; margin-bottom: 20px;"><?php echo $msg; ?></p>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Resource ID (Primary Key)</label>
                <input type="text" value="<?php echo $book['BookID']; ?>" disabled style="background: #f0f0f0; color: #888;">
            </div>

            <div class="form-group">
                <label>Full Title</label>
                <input type="text" name="title" value="<?php echo htmlspecialchars($book['Title']); ?>" required>
            </div>

            <div class="form-group">
                <label>Author / Publisher</label>
                <input type="text" name="author" value="<?php echo htmlspecialchars($book['Author']); ?>" required>
            </div>

            <div class="form-group">
                <label>Standard ISBN</label>
                <input type="text" name="isbn" value="<?php echo $book['ISBN']; ?>" required>
            </div>

            <div class="form-group">
                <label>Year of Publication</label>
                <input type="number" name="year" value="<?php echo $book['PublishedYear']; ?>" required>
            </div>

            <div class="form-group">
                <label>Availability Status</label>
                <select name="status">
                    <option value="Available" <?php echo ($book['AvailabilityStatus'] == 'Available') ? 'selected' : ''; ?>>Available</option>
                    <option value="Checked Out" <?php echo ($book['AvailabilityStatus'] == 'Checked Out') ? 'selected' : ''; ?>>Checked Out</option>
                </select>
            </div>

            <button type="submit" class="btn-update">Save Changes</button>
            <a href="manage_books.php" class="cancel-link">Cancel and return to catalog</a>
        </form>
    </div>
</div>

</body>
</html>