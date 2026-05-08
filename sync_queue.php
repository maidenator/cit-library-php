<?php
require 'db.php';

echo "<h2>Manually Processing Stuck Queues...</h2>";

// 1. Find all books that are 'Available' but have 'Pending' reservations
$stuckQuery = "SELECT DISTINCT b.BookID, b.Title 
               FROM BOOK b 
               JOIN RESERVATION r ON b.BookID = r.BookID 
               WHERE b.AvailabilityStatus = 'Available' 
               AND r.QueueStatus = 'Pending'";

$result = mysqli_query($conn, $stuckQuery);

if (mysqli_num_rows($result) == 0) {
    die("No stuck queues found! Everything is in sync.");
}

while ($book = mysqli_fetch_assoc($result)) {
    $bookID = $book['BookID'];
    
    // 2. Find the #1 person in line for this specific book
    $nextUserQuery = "SELECT * FROM RESERVATION WHERE BookID = '$bookID' AND QueueStatus = 'Pending' 
                      ORDER BY ReservationID ASC LIMIT 1";
    $nextInLine = mysqli_fetch_assoc(mysqli_query($conn, $nextUserQuery));

    if ($nextInLine) {
        $nextUserID = $nextInLine['UserID'];
        $resID = $nextInLine['ReservationID'];
        $today = date('Y-m-d');
        $dueDate = date('Y-m-d', strtotime("+14 days"));

        mysqli_begin_transaction($conn);
        try {
            // A. Create the Loan
            $assignLoan = "INSERT INTO LOAN (UserID, BookID, BorrowDate, DueDate, LoanStatus) 
                           VALUES ('$nextUserID', '$bookID', '$today', '$dueDate', 'Active')";
            mysqli_query($conn, $assignLoan);

            // B. Fulfill the Reservation
            mysqli_query($conn, "UPDATE RESERVATION SET QueueStatus = 'Fulfilled' WHERE ReservationID = $resID");

            // C. Mark Book as Checked Out
            mysqli_query($conn, "UPDATE BOOK SET AvailabilityStatus = 'Checked Out' WHERE BookID = '$bookID'");

            mysqli_commit($conn);
            echo "✅ Successfully passed '{$book['Title']}' to User ID: $nextUserID<br>";
        } catch (Exception $e) {
            mysqli_rollback($conn);
            echo "❌ Failed to pass '{$book['Title']}': " . $e->getMessage() . "<br>";
        }
    }
}

echo "<h3>Sync Complete!</h3>";
?>