<?php
session_start();
require 'db.php';

$isGuest = !isset($_SESSION['user_id']);
$userID = $isGuest ? '' : $_SESSION['user_id'];

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

$sql = "SELECT b.*, 
        (SELECT LoanID FROM LOAN l WHERE l.BookID = b.BookID AND l.UserID = '$userID' AND l.LoanStatus = 'Active') AS UserHasThisBook,
        (SELECT ReservationID FROM RESERVATION r WHERE r.BookID = b.BookID AND r.UserID = '$userID' AND r.QueueStatus = 'Pending') AS UserInWaitlist
        FROM BOOK b 
        WHERE (b.Title LIKE '%$search%' OR b.Author LIKE '%$search%' OR b.ISBN LIKE '%$search%')
        ORDER BY b.Title ASC";

$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Database Error: " . mysqli_error($conn));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Browse Resources | CIT-U Library</title>
    <style>
        body { font-family: sans-serif; margin: 0; background: #f9f9f9; color: #333; }
        .header { background: #800000; color: white; padding: 20px 40px; border-bottom: 5px solid #f4b400; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { margin: 0; font-size: 20px; letter-spacing: 1px; }
        .container { padding: 30px 40px; }
        
        .search-container { margin-bottom: 35px; }
        .search-form { display: flex; box-shadow: 5px 5px 0px #f4b400; }
        .search-input { flex-grow: 1; padding: 15px 20px; border: 2px solid #800000; font-size: 15px; outline: none; background: white; }
        .btn-search { background: #800000; color: white; border: 2px solid #800000; padding: 0 30px; font-weight: bold; cursor: pointer; text-transform: uppercase; transition: 0.2s; }
        .btn-search:hover { background: #a00000; }
        .btn-clear { 
            background: #f4b400; color: black; text-decoration: none; padding: 0 20px; 
            font-weight: bold; font-size: 13px; display: flex; align-items: center; 
            border: 2px solid #800000; border-left: none; text-transform: uppercase; 
        }

        .resource-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 25px; margin-top: 20px; }
        .book-card { background: white; border: 1px solid #ddd; padding: 0; display: flex; flex-direction: column; box-shadow: 4px 4px 0px rgba(0,0,0,0.1); transition: 0.2s; height: 100%; }
        .book-card:hover { transform: translateY(-5px); box-shadow: 6px 6px 0px #f4b400; }
        .book-cover { background: #1a1a1a; color: white; height: 180px; display: flex; align-items: center; justify-content: center; text-align: center; padding: 15px; font-weight: bold; }
        .book-details { padding: 20px; flex-grow: 1; display: flex; flex-direction: column; }
        .book-title { margin: 0; font-size: 16px; color: #800000; text-transform: uppercase; }
        .book-author { font-size: 13px; color: #666; margin: 5px 0 15px 0; }
        .status-badge { display: inline-block; padding: 4px 10px; font-size: 11px; font-weight: bold; text-transform: uppercase; margin-bottom: 15px; width: fit-content; }
        .available { background: #27ae60; color: white; }
        .checked-out { background: #e74c3c; color: white; }
        
        .btn-action { display: block; width: 80%; margin: 10px auto; padding: 12px; text-align: center; text-decoration: none; font-weight: bold; font-size: 13px; text-transform: uppercase; border: none; cursor: pointer; }
        .btn-borrow { background: #800000; color: white; }
        .btn-waitlist { background: #f4b400; color: black; }
        .btn-disabled { background: #ccc; color: #666; cursor: not-allowed; }
        .guest-notice { font-size: 12px; color: #800000; text-align: center; margin-top: 10px; font-weight: bold; }
    </style>
</head>
<body>

<div class="header">
    <h1>CIT-U LIBRARY CATALOG</h1>
    <a href="index.php" style="color: #f4b400; text-decoration: none; font-weight: bold;">← BACK TO DASHBOARD</a>
</div>

<div class="container">
    <div class="search-container">
        <form method="GET" action="browse_books.php" class="search-form">
            <input type="text" name="search" class="search-input" 
                   placeholder="Search by Title, Author, or ISBN..." 
                   value="<?php echo htmlspecialchars($search); ?>">
            
            <button type="submit" class="btn-search">Search Catalog</button>
            
            <?php if($search !== ''): ?>
                <a href="browse_books.php" class="btn-clear">Clear Results</a>
            <?php endif; ?>
        </form>
    </div>

    <h2 style="color: #800000; border-left: 5px solid #800000; padding-left: 15px;">
        <?php echo ($search !== '') ? "SEARCH RESULTS FOR '" . strtoupper(htmlspecialchars($search)) . "'" : "AVAILABLE RESOURCES"; ?>
    </h2>
    
    <div class="resource-grid">
        <?php if(mysqli_num_rows($result) > 0): ?>
            <?php while($book = mysqli_fetch_assoc($result)): ?>
                <div class="book-card">
                    <div class="book-cover"><?php echo htmlspecialchars($book['Title']); ?></div>
                    <div class="book-details">
                        <p class="status-badge <?php echo ($book['AvailabilityStatus'] == 'Available') ? 'available' : 'checked-out'; ?>">
                            <?php echo $book['AvailabilityStatus']; ?> 
                        </p>
                        <h3 class="book-title"><?php echo htmlspecialchars($book['Title']); ?></h3>
                        <p class="book-author">by <?php echo htmlspecialchars($book['Author']); ?></p>
                        
                        <div style="margin-top: auto;">
                            <?php if($isGuest): ?>
                                <div class="btn-action btn-disabled">Login to Borrow</div>
                                <p class="guest-notice">AUTHENTICATION REQUIRED</p>
                            <?php else: ?>
                                <?php if($book['UserHasThisBook']): ?>
                                    <div class="btn-action" style="background: #2c3e50; color: white;">You have this book</div>
                                    <a href="process_return.php?id=<?php echo $book['BookID']; ?>" class="btn-action" style="background: #7f8c8d; color: white; margin-top: 5px;">Return Resource</a>
                                
                                <?php elseif($book['UserInWaitlist']): ?>
                                    <div class="btn-action" style="background: #f39c12; color: white; cursor: default;">In Waitlist Queue</div>
                                
                                <?php elseif($book['AvailabilityStatus'] == 'Available'): ?>
                                    <a href="process_borrow.php?id=<?php echo $book['BookID']; ?>" class="btn-action btn-borrow">Borrow Resource</a>
                                
                                <?php else: ?>
                                    <a href="process_reserve.php?id=<?php echo $book['BookID']; ?>" class="btn-action btn-waitlist">Join Waitlist</a>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p style="grid-column: 1 / -1; text-align: center; color: #666; padding: 50px;">No resources found matching your search.</p>
        <?php endif; ?>
    </div>
</div>

<script>
    window.onload = function() {
        const urlParams = new URLSearchParams(window.location.search);
        
        // Handle Errors
        if (urlParams.has('error')) {
            const errorType = urlParams.get('error');
            let message = "An unknown error occurred.";
            
            if (errorType === 'restricted') {
                message = "ACCESS DENIED\n\nYour account is restricted due to overdue books. Please return late resources to restore borrowing privileges.";
            } else if (errorType === 'limit') {
                message = "LIMIT REACHED\n\nYou have reached your maximum borrowing limit. Return a book to borrow a new one.";
            } else if (errorType === 'unavailable') {
                message = "RESOURCE UNAVAILABLE\n\nThis book was just checked out by another user.";
            }
            
            alert(message);
            window.history.replaceState({}, document.title, window.location.pathname);
        }

        if (urlParams.has('msg')) {
            alert("✅ SUCCESS\n\n" + urlParams.get('msg'));
            window.history.replaceState({}, document.title, window.location.pathname);
        }
    }
</script>
</body>
</html>