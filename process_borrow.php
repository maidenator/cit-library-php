<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userID = $_SESSION['user_id'];
$userRole = $_SESSION['role'];
$bookID = $_GET['id'];

// 1. Fetch User Data (Limits & Status)
$userQuery = "SELECT AccountStatus, MaxLoanLimit FROM USER WHERE UserID = ?";
$uStmt = mysqli_prepare($conn, $userQuery);
mysqli_stmt_bind_param($uStmt, "s", $userID);
mysqli_stmt_execute($uStmt);
$userData = mysqli_fetch_assoc(mysqli_stmt_get_result($uStmt));

// 2. Fetch Book Data (Current Status)
$bookQuery = "SELECT AvailabilityStatus FROM BOOK WHERE BookID = ?";
$bStmt = mysqli_prepare($conn, $bookQuery);
mysqli_stmt_bind_param($bStmt, "s", $bookID);
mysqli_stmt_execute($bStmt);
$bookData = mysqli_fetch_assoc(mysqli_stmt_get_result($bStmt));

// 3. Count Active Loans
$countQuery = "SELECT COUNT(*) as current_loans FROM LOAN WHERE UserID = ? AND LoanStatus = 'Active'";
$cStmt = mysqli_prepare($conn, $countQuery);
mysqli_stmt_bind_param($cStmt, "s", $userID);
mysqli_stmt_execute($cStmt);
$loanCount = mysqli_fetch_assoc(mysqli_stmt_get_result($cStmt))['current_loans'];

// --- VALIDATION ---

if ($userData['AccountStatus'] === 'Restricted') {
    header("Location: browse_books.php?error=restricted");
    exit();
}

if ($loanCount >= $userData['MaxLoanLimit']) {
    header("Location: browse_books.php?error=limit");
    exit();
}

if ($bookData['AvailabilityStatus'] !== 'Available') {
    header("Location: browse_books.php?error=unavailable");
    exit();
}

// --- EXECUTION ---
$borrowDate = date('Y-m-d');
$daysAllowed = ($userRole === 'Faculty') ? 30 : 14;
$dueDate = date('Y-m-d', strtotime("+$daysAllowed days"));

mysqli_begin_transaction($conn);

try {
    // A. Record the Loan
    $insertLoan = "INSERT INTO LOAN (UserID, BookID, BorrowDate, DueDate, LoanStatus) VALUES (?, ?, ?, ?, 'Active')";
    $lStmt = mysqli_prepare($conn, $insertLoan);
    mysqli_stmt_bind_param($lStmt, "ssss", $userID, $bookID, $borrowDate, $dueDate);
    mysqli_stmt_execute($lStmt);

    // B. Mark Book as Checked Out
    $updateBook = "UPDATE BOOK SET AvailabilityStatus = 'Checked Out' WHERE BookID = ?";
    $upStmt = mysqli_prepare($conn, $updateBook);
    mysqli_stmt_bind_param($upStmt, "s", $bookID);
    mysqli_stmt_execute($upStmt);

    // C. THE FIX: Increment the BorrowCount
    $updateCount = "UPDATE BOOK SET BorrowCount = BorrowCount + 1 WHERE BookID = ?";
    $countStmt = mysqli_prepare($conn, $updateCount);
    mysqli_stmt_bind_param($countStmt, "s", $bookID);
    mysqli_stmt_execute($countStmt);

    mysqli_commit($conn);
    header("Location: index.php?msg=Borrowed successfully!");
} catch (Exception $e) {
    mysqli_rollback($conn);
    echo "Error: " . $e->getMessage();
}
?>