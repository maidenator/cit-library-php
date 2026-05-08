

<?php
session_start();
require 'db.php';

// 1. Security Check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$role = $_SESSION['role'];
$name = $_SESSION['name'];
$user_id = $_SESSION['user_id'];

// Fetch General Dashboard Stats
$stats_query = "SELECT 
    (SELECT COUNT(*) FROM BOOK) as total,
    (SELECT COUNT(*) FROM BOOK WHERE AvailabilityStatus = 'Available') as avail,
    (SELECT COUNT(*) FROM USER WHERE AccountStatus = 'Active') as active_users";
$stats = mysqli_fetch_assoc(mysqli_query($conn, $stats_query));

// Fetch top 4 most borrowed available books
$high_demand_query = "SELECT * FROM BOOK 
                      WHERE AvailabilityStatus = 'Available' 
                      ORDER BY BorrowCount DESC 
                      LIMIT 4";
$high_demand_result = mysqli_query($conn, $high_demand_query);

// Fetch specific load count
$load_query = "SELECT COUNT(*) as current_load FROM LOAN WHERE UserID = '$user_id' AND LoanStatus = 'Active'";
$user_load = mysqli_fetch_assoc(mysqli_query($conn, $load_query))['current_load'];

// Fetch actual book details for cards
$my_books_query = "SELECT b.*, l.DueDate 
                   FROM BOOK b 
                   JOIN LOAN l ON b.BookID = l.BookID 
                   WHERE l.UserID = '$user_id' AND l.LoanStatus = 'Active'";
$my_books_result = mysqli_query($conn, $my_books_query);

// Fetch active reservations with a strict FIFO tie-breaker using ReservationID
$res_query = "SELECT r.*, b.Title, 
             (SELECT COUNT(*) FROM RESERVATION r2 
              WHERE r2.BookID = r.BookID 
              AND r2.ReservationID <= r.ReservationID 
              AND r2.QueueStatus = 'Pending') as position
              FROM RESERVATION r
              JOIN BOOK b ON r.BookID = b.BookID
              WHERE r.UserID = '$user_id' AND r.QueueStatus = 'Pending'";
$res_result = mysqli_query($conn, $res_query);

// --- OVERDUE ENFORCEMENT ---
$today = date('Y-m-d');

// 1. Check if the user has ANY active loan that is past its DueDate
$overdue_check = "SELECT COUNT(*) as overdue_count FROM LOAN 
                  WHERE UserID = '$user_id' 
                  AND LoanStatus = 'Active' 
                  AND DueDate < '$today'";
$is_overdue = mysqli_fetch_assoc(mysqli_query($conn, $overdue_check))['overdue_count'] > 0;

// 2. Automatically Restrict the account if they are late (Business Rule 10)
if ($is_overdue) {
    mysqli_query($conn, "UPDATE USER SET AccountStatus = 'Restricted' WHERE UserID = '$user_id'");
} else {
    // Optional: Un-restrict if they have returned everything (or keep it manual for Faculty)
    // mysqli_query($conn, "UPDATE USER SET AccountStatus = 'Active' WHERE UserID = '$user_id'");
}

// Fetch the latest account status for the UI
$status_query = "SELECT AccountStatus FROM USER WHERE UserID = '$user_id'";
$account_status = mysqli_fetch_assoc(mysqli_query($conn, $status_query))['AccountStatus'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard | CIT-U Library</title>
    <style>
        body { font-family: sans-serif; margin: 0; background: #f9f9f9; color: #333; }
        .header { background: #800000; color: white; padding: 20px 40px; border-bottom: 5px solid #f4b400; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { margin: 0; font-size: 24px; letter-spacing: 2px; }
        .header a { color: #f4b400; text-decoration: none; font-weight: bold; font-size: 14px; }
        .container { padding: 30px 40px; }
        .welcome-banner { margin-bottom: 30px; }
        .welcome-banner h2 { color: #800000; margin: 0; text-transform: uppercase; }
        .role-badge { background: #f4b400; color: black; padding: 2px 8px; font-size: 12px; font-weight: bold; border-radius: 3px; }
        .dashboard-row { display: flex; gap: 25px; margin-bottom: 40px; }
        .stat-card { background: white; border: 2px solid #800000; padding: 20px; min-width: 180px; text-align: center; box-shadow: 5px 5px 0px #f4b400; }
        .stat-card small { font-weight: bold; color: #800000; text-transform: uppercase; font-size: 11px; }
        .stat-card h1 { margin: 10px 0 0 0; font-size: 36px; }
        .section-title { border-left: 5px solid #800000; padding-left: 15px; color: #800000; text-transform: uppercase; font-size: 18px; margin-bottom: 20px; }
        .card { background: white; border: 1px solid #ddd; padding: 25px; margin-bottom: 30px; box-shadow: 3px 3px 10px rgba(0,0,0,0.05); }
        .btn { display: inline-block; padding: 12px 20px; text-decoration: none; font-weight: bold; text-transform: uppercase; font-size: 13px; border: none; cursor: pointer; transition: 0.2s; }
        .btn-maroon { background: #800000; color: white; border: 2px solid #800000; }
        .btn-gold { background: #f4b400; color: black; border: 2px solid #f4b400; margin-left: 10px; }
        .loan-info { font-weight: bold; font-size: 14px; margin-bottom: 10px; }

        /* NEW STYLES FOR BORROWED BOOK LIST */
        .my-books-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px; margin: 20px 0; }
        .mini-book-card { background: white; border: 1px solid #ddd; box-shadow: 3px 3px 0px #f4b400; display: flex; flex-direction: column; }
        .mini-cover { background: #1a1a1a; color: white; padding: 15px; text-align: center; font-weight: bold; font-size: 12px; min-height: 40px; display: flex; align-items: center; justify-content: center; }
        .mini-details { padding: 15px; }
        .due-date { font-size: 11px; color: #e74c3c; font-weight: bold; text-transform: uppercase; margin-bottom: 10px; }
        .btn-return-small { display: block; text-align: center; background: #800000; color: white; text-decoration: none; font-size: 11px; padding: 8px; font-weight: bold; }

        .quick-search-container {
            margin-bottom: 35px;
        }

        .search-form {
            display: flex;
            gap: 0; /* Flush look */
            box-shadow: 5px 5px 0px #f4b400;
        }

        .search-input {
            flex-grow: 1;
            padding: 15px 20px;
            border: 2px solid #800000;
            font-size: 15px;
            outline: none;
        }

        .btn-search-main {
            background: #800000;
            color: white;
            border: 2px solid #800000;
            padding: 0 30px;
            font-weight: bold;
            cursor: pointer;
            text-transform: uppercase;
            transition: 0.2s;
        }

        .btn-search-main:hover {
            background: #a00000;
        }

        /* Overdue Alert Styles */
        .overdue-card {
            border: 2px solid #e74c3c !important; /* Bright Red */
            box-shadow: 5px 5px 0px #800000 !important;
        }

        .overdue-text {
            color: #e74c3c !important;
            font-weight: 900 !important;
            animation: blinker 1.5s linear infinite;
        }

        @keyframes blinker {
            50% { opacity: 0; }
        }

        .status-banner {
            padding: 10px;
            margin-bottom: 20px;
            font-weight: bold;
            text-align: center;
            text-transform: uppercase;
            font-size: 13px;
        }
        .status-active { background: #27ae60; color: white; }
        .status-restricted { background: #e74c3c; color: white; border: 2px solid #800000; }
    
    </style>
</head>
<body>

<div class="header">
    <h1>CIT-U LIB</h1>
    <a href="logout.php">LOGOUT</a>
</div>

<div class="container">
    <div class="quick-search-container">
        <form action="browse_books.php" method="GET" class="search-form">
            <input type="text" name="search" class="search-input" 
                placeholder="Find resources by Title, Author, or ISBN (e.g., Designing Data-Intensive)...">
            <button type="submit" class="btn-search-main">Search Catalog</button>
        </form>
    </div>

    <div class="welcome-banner">
    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
        <div>
            <h2>Welcome back, <?php echo htmlspecialchars($name); ?></h2>
            <span class="role-badge">
                <?php echo ($role === 'Faculty') ? 'Faculty Account' : 'Student Account'; ?>
            </span>
        </div>
        
        <div class="status-banner <?php echo ($account_status == 'Active') ? 'status-active' : 'status-restricted'; ?>">
            Account Status: <?php echo $account_status; ?>
        </div>
    </div>
</div>

    <div class="dashboard-row">
        <div class="stat-card">
            <small>Total Catalog</small>
            <h1><?php echo $stats['total']; ?></h1>
        </div>
        <div class="stat-card" style="background: #f4b400; border-color: black; box-shadow: 5px 5px 0px #800000;">
            <small style="color: black;">Live Availability</small>
            <h1><?php echo $stats['avail']; ?></h1>
        </div>
        <div class="stat-card">
            <small>Active Users</small>
            <h1><?php echo $stats['active_users']; ?></h1>
        </div>
    </div>

    <div class="section-title">My Library Account</div>
    <div class="card">
        <p class="loan-info">
            Current Load: <?php echo $user_load; ?> / <?php echo ($role == 'Faculty') ? '10' : '5'; ?> Resources
        </p>

        <?php if($user_load > 0): ?>
            <div class="my-books-grid">
                <?php while($row = mysqli_fetch_assoc($my_books_result)): 
                    $is_late = (strtotime($row['DueDate']) < strtotime($today));
                ?>
                    <div class="mini-book-card <?php echo $is_late ? 'overdue-card' : ''; ?>">
                        <div class="mini-cover" style="<?php echo $is_late ? 'background: #e74c3c;' : ''; ?>">
                            <?php echo htmlspecialchars($row['Title']); ?>
                        </div>
                        <div class="mini-details">
                            <div class="due-date <?php echo $is_late ? 'overdue-text' : ''; ?>">
                                <?php echo $is_late ? '⚠️ OVERDUE: ' : 'Due: '; ?>
                                <?php echo $row['DueDate']; ?>
                            </div>
                            <a href="process_return.php?id=<?php echo $row['BookID']; ?>" class="btn-return-small">Return Book</a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p style="color: #666; font-style: italic; font-size: 13px;">You have no active loans. Visit the catalog to borrow books.</p>
        <?php endif; ?>
        
        <div style="margin-top: 15px; border-top: 1px solid #eee; padding-top: 15px;">
            <a href="browse_books.php" class="btn btn-maroon">Browse & Borrow</a>
            
            <?php if($role == 'Faculty' || $role == 'Admin'): ?>
                <a href="add_book.php" class="btn btn-gold">+ Add Book</a>
                <a href="manage_books.php" class="btn btn-gold">Manage Catalog</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="section-title">My Reservations</div>
    <div class="card">
        <?php if(mysqli_num_rows($res_result) > 0): ?>
            <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
                <tr style="text-align: left; border-bottom: 2px solid #800000; color: #800000;">
                    <th style="padding: 10px;">Resource Title</th>
                    <th style="padding: 10px;">Date Requested</th>
                    <th style="padding: 10px;">Queue Position</th>
                </tr>
                <?php while($res = mysqli_fetch_assoc($res_result)): ?>
                    <tr style="border-bottom: 1px solid #eee;">
                        <td style="padding: 10px;"><strong><?php echo htmlspecialchars($res['Title']); ?></strong></td>
                        <td style="padding: 10px;"><?php echo $res['RequestDate']; ?></td>
                        <td style="padding: 10px;">
                            <span class="role-badge" style="background: <?php echo ($res['position'] == 1) ? '#27ae60; color:white;' : '#f4b400;'; ?>">
                                #<?php echo $res['position']; ?> in line
                            </span>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </table>
        <?php else: ?>
            <p style="color: #666; font-style: italic; font-size: 13px;">You are not currently waiting for any resources.</p>
        <?php endif; ?>
    </div>


    <div class="section-title">High-Demand Resources</div>
    <div class="card">
        <div class="my-books-grid">
            <?php while($book = mysqli_fetch_assoc($high_demand_result)): ?>
                <div class="mini-book-card">
                    <div class="mini-cover" style="background: #f4b400; color: #800000;">
                        <?php echo htmlspecialchars($book['Title']); ?>
                    </div>
                    <div class="mini-details">
                        <p style="font-size: 11px; margin: 0; font-weight: bold;"><?php echo htmlspecialchars($book['Author']); ?></p>
                        <p style="font-size: 10px; color: #27ae60; font-weight: bold; text-transform: uppercase; margin: 5px 0 10px 0;">
                            Popular Choice (<?php echo $book['BorrowCount']; ?> borrows)
                        </p>
                        <a href="process_borrow.php?id=<?php echo $book['BookID']; ?>" class="btn-return-small" style="background: #800000; color: white;">
                            Quick Borrow
                        </a>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
        
        <div style="text-align: center; margin-top: 20px;">
            <a href="browse_books.php" style="color: #800000; font-weight: bold; text-decoration: none; font-size: 13px;">VIEW FULL CATALOG →</a>
        </div>
    </div>

</body>
</html>