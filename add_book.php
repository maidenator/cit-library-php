<?php
session_start();
require 'db.php';

// Security: Only Faculty and Admins can add books
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'Faculty' && $_SESSION['role'] !== 'Admin')) {
    header("Location: index.php");
    exit();
}

if (isset($_POST['add_book'])) {
    $bookID = $_POST['bookID'];
    $title = $_POST['title'];
    $author = $_POST['author'];
    $isbn = $_POST['isbn'];
    $year = $_POST['publishedYear'];
    $status = "Available"; // BR2: Default status for new records [cite: 14]

    $sql = "INSERT INTO BOOK (BookID, Title, Author, ISBN, AvailabilityStatus, PublishedYear) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "sssssi", $bookID, $title, $author, $isbn, $status, $year);

    if (mysqli_stmt_execute($stmt)) {
        $success = "Book added successfully to the catalog!";
    } else {
        $error = "Error: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Add New Resource | CIT-U Library</title>
    <style>
        body { font-family: sans-serif; background: #f9f9f9; margin: 0; padding: 20px; }
        .form-container { 
            max-width: 500px; 
            margin: 40px auto; 
            background: white; 
            border: 2px solid #800000; 
            padding: 30px; 
            box-shadow: 8px 8px 0px #f4b400; 
        }
        h2 { color: #800000; border-bottom: 3px solid #f4b400; padding-bottom: 10px; margin-top: 0; }
        label { display: block; font-size: 11px; font-weight: bold; text-transform: uppercase; margin-top: 15px; }
        input { width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #ccc; box-sizing: border-box; }
        .btn-submit { 
            background: #800000; color: white; border: none; padding: 12px; 
            width: 100%; margin-top: 25px; cursor: pointer; font-weight: bold; text-transform: uppercase; 
        }
        .back-link { display: block; text-align: center; margin-top: 20px; color: #800000; text-decoration: none; font-size: 14px; }
    </style>
</head>
<body>

<div class="form-container">
    <h2>Add New Book</h2>
    
    <?php if(isset($success)): ?>
        <p style="color: green; font-weight: bold;"><?php echo $success; ?></p>
    <?php endif; ?>
    <?php if(isset($error)): ?>
        <p style="color: red; font-weight: bold;"><?php echo $error; ?></p>
    <?php endif; ?>

    <form method="POST">
        <label>Book ID / Accession Number</label>
        <input type="text" name="bookID" placeholder="e.g. LIB-001" required>

        <label>Book Title</label>
        <input type="text" name="title" placeholder="Engineering Mechanics" required>

        <label>Author</label>
        <input type="text" name="author" placeholder="Ferdinand Singer" required>

        <label>ISBN</label>
        <input type="text" name="isbn" placeholder="978-0-0000-0000-0">

        <label>Published Year</label>
        <input type="number" name="publishedYear" placeholder="2024" required>

        <button type="submit" name="add_book" class="btn-submit">Register Resource</button>
    </form>

    <a href="index.php" class="back-link">← Return to Dashboard</a>
</div>

</body>
</html>