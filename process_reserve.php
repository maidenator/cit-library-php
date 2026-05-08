<?php
session_start();
require 'db.php';

// 1. Security Check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userID = $_SESSION['user_id'];
$bookID = $_GET['id'];

// 2. Safety Validations
// Check if user already has this book active
$checkLoan = mysqli_query($conn, "SELECT * FROM LOAN WHERE UserID = '$userID' AND BookID = '$bookID' AND LoanStatus = 'Active'");

// Check if user is already on the waitlist for this book
$checkWaitlist = mysqli_query($conn, "SELECT * FROM RESERVATION WHERE UserID = '$userID' AND BookID = '$bookID' AND QueueStatus = 'Pending'");

if (mysqli_num_rows($checkLoan) > 0) {
    die("Error: You currently have this book. No need to waitlist!");
}

if (mysqli_num_rows($checkWaitlist) > 0) {
    die("Error: You are already in the waitlist for this resource.");
}

// 3. Join the Queue
$requestDate = date('Y-m-d');
$sql = "INSERT INTO RESERVATION (UserID, BookID, RequestDate, QueueStatus) VALUES (?, ?, ?, 'Pending')";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "sss", $userID, $bookID, $requestDate);

if (mysqli_stmt_execute($stmt)) {
    header("Location: index.php?msg=You have been added to the waitlist!");
} else {
    echo "Error: " . mysqli_error($conn);
}
?>