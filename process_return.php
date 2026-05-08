<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userID = $_SESSION['user_id'];
$bookID = $_GET['id'];
$today = date('Y-m-d');

// Start transaction to ensure the "hand-off" is atomic
mysqli_begin_transaction($conn);

try {
    // 1. Close the current user's loan
    $updateLoanSql = "UPDATE LOAN SET LoanStatus = 'Returned', ReturnDate = NOW() 
                      WHERE BookID = ? AND UserID = ? AND LoanStatus = 'Active'";
    $stmt1 = mysqli_prepare($conn, $updateLoanSql);
    mysqli_stmt_bind_param($stmt1, "ss", $bookID, $userID);
    mysqli_stmt_execute($stmt1);

        // Check if the user has any REMAINING overdue books
    $still_late_query = "SELECT COUNT(*) as remaining_overdue FROM LOAN 
                        WHERE UserID = '$userID' 
                        AND LoanStatus = 'Active' 
                        AND DueDate < CURDATE()";
    $still_late = mysqli_fetch_assoc(mysqli_query($conn, $still_late_query))['remaining_overdue'];

    // If no more overdue books, restore their account status
    if ($still_late == 0) {
        mysqli_query($conn, "UPDATE USER SET AccountStatus = 'Active' WHERE UserID = '$userID'");
    }

    // 2. Check for the person at #1 in the waitlist (FIFO - oldest ReservationID)
    $nextUserQuery = "SELECT * FROM RESERVATION WHERE BookID = ? AND QueueStatus = 'Pending' 
                      ORDER BY ReservationID ASC LIMIT 1";
    $stmt2 = mysqli_prepare($conn, $nextUserQuery);
    mysqli_stmt_bind_param($stmt2, "s", $bookID);
    mysqli_stmt_execute($stmt2);
    $waitlistResult = mysqli_stmt_get_result($stmt2);
    $nextInLine = mysqli_fetch_assoc($waitlistResult);

    if ($nextInLine) {
        // --- AUTO-TRANSFER LOGIC ---
        $nextUserID = $nextInLine['UserID'];
        $resID = $nextInLine['ReservationID'];
        
        // Calculate due date (Standard 14 days for auto-loan)
        $dueDate = date('Y-m-d', strtotime("+14 days"));

        // A. Create a new loan for the next person in line
        $assignLoan = "INSERT INTO LOAN (UserID, BookID, BorrowDate, DueDate, LoanStatus) VALUES (?, ?, ?, ?, 'Active')";
        $stmt3 = mysqli_prepare($conn, $assignLoan);
        mysqli_stmt_bind_param($stmt3, "ssss", $nextUserID, $bookID, $today, $dueDate);
        mysqli_stmt_execute($stmt3);

        // B. Mark their reservation as 'Fulfilled'
        $fulfillRes = "UPDATE RESERVATION SET QueueStatus = 'Fulfilled' WHERE ReservationID = ?";
        $stmt4 = mysqli_prepare($conn, $fulfillRes);
        mysqli_stmt_bind_param($stmt4, "i", $resID);
        mysqli_stmt_execute($stmt4);

        // C. Increment BorrowCount for the new loan (Popularity tracking)
        $updateCount = "UPDATE BOOK SET BorrowCount = BorrowCount + 1 WHERE BookID = ?";
        $stmt5 = mysqli_prepare($conn, $updateCount);
        mysqli_stmt_bind_param($stmt5, "s", $bookID);
        mysqli_stmt_execute($stmt5);

        // The book remains 'Checked Out' because it was immediately passed to a new user
        $msg = "Book returned and automatically assigned to the next user in the queue!";
    } else {
        // --- NO ONE WAITING ---
        // Set book back to 'Available' for the general catalog
        $updateBookSql = "UPDATE BOOK SET AvailabilityStatus = 'Available' WHERE BookID = ?";
        $stmt6 = mysqli_prepare($conn, $updateBookSql);
        mysqli_stmt_bind_param($stmt6, "s", $bookID);
        mysqli_stmt_execute($stmt6);
        $msg = "Book returned successfully!";
    }

    mysqli_commit($conn);
    header("Location: index.php?msg=" . urlencode($msg));
    exit();

} catch (Exception $e) {
    mysqli_rollback($conn);
    echo "Error processing resource hand-off: " . $e->getMessage();
}
?>