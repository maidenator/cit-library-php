<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'Faculty' && $_SESSION['role'] !== 'Admin')) {
    header("Location: index.php");
    exit();
}

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

$sql = "SELECT * FROM BOOK 
        WHERE (Title LIKE '%$search%' 
        OR Author LIKE '%$search%' 
        OR BookID LIKE '%$search%' 
        OR ISBN LIKE '%$search%')
        ORDER BY Title ASC";

$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Catalog | CIT-U Library</title>
    <style>
        body { font-family: sans-serif; margin: 0; background: #f9f9f9; }
        .header { background: #800000; color: white; padding: 20px 40px; border-bottom: 5px solid #f4b400; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { margin: 0; font-size: 20px; letter-spacing: 1px; }
        .container { padding: 30px 40px; }
        
        .manage-card { background: white; border: 1px solid #ddd; padding: 25px; box-shadow: 5px 5px 0px #f4b400; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background: #800000; color: white; text-align: left; padding: 12px; font-size: 13px; text-transform: uppercase; }
        td { padding: 12px; border-bottom: 1px solid #eee; font-size: 14px; }
        
        .status { padding: 4px 8px; font-size: 11px; font-weight: bold; border-radius: 3px; text-transform: uppercase; }
        .available { background: #27ae60; color: white; }
        .checked-out { background: #e74c3c; color: white; }

        .btn-add { background: #27ae60; color: white; padding: 10px 15px; text-decoration: none; font-weight: bold; font-size: 13px; border-radius: 3px; }
        .action-link { color: #800000; text-decoration: none; font-weight: bold; margin-right: 10px; }
        .action-link:hover { text-decoration: underline; }

        .search-container { margin-bottom: 25px; display: flex; gap: 10px; }
        .search-input { flex-grow: 1; padding: 12px; border: 2px solid #800000; font-size: 14px; outline: none; }
        .btn-search { background: #800000; color: white; border: none; padding: 0 25px; font-weight: bold; cursor: pointer; text-transform: uppercase; }
        .btn-clear { background: #f4b400; color: black; text-decoration: none; padding: 12px 20px; font-weight: bold; font-size: 13px; display: flex; align-items: center; }
    </style>
</head>
<body>

<div class="header">
    <h1>CATALOG MANAGEMENT</h1>
    <a href="index.php" style="color: #f4b400; text-decoration: none; font-weight: bold;">← DASHBOARD</a>
</div>

<div class="container">
    <div class="manage-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2 style="color: #800000; margin: 0;">Library Bookshelf</h2>
            <a href="add_book.php" class="btn-add">+ ADD NEW RESOURCE</a>
        </div>

        <form method="GET" action="manage_books.php" class="search-container">
            <input type="text" name="search" class="search-input" 
                   placeholder="Search by ID, Title, or Author..." 
                   value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="btn-search">Search</button>
            <?php if($search !== ''): ?>
                <a href="manage_books.php" class="btn-clear">Clear</a>
            <?php endif; ?>
        </form>

        <table>
            <thead>
                <tr>
                    <th>Book ID</th>
                    <th>Title</th>
                    <th>Author</th>
                    <th>ISBN</th>
                    <th>Year</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if(mysqli_num_rows($result) > 0): ?>
                    <?php while($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><strong><?php echo $row['BookID']; ?></strong></td>
                        <td><?php echo htmlspecialchars($row['Title']); ?></td>
                        <td><?php echo htmlspecialchars($row['Author']); ?></td>
                        <td><?php echo $row['ISBN']; ?></td>
                        <td><?php echo $row['PublishedYear']; ?></td>
                        <td>
                            <span class="status <?php echo ($row['AvailabilityStatus'] == 'Available') ? 'available' : 'checked-out'; ?>">
                                <?php echo $row['AvailabilityStatus']; ?>
                            </span>
                        </td>
                        <td>
                            <a href="edit_book.php?id=<?php echo $row['BookID']; ?>" class="action-link">Edit</a>
                            <a href="delete_book.php?id=<?php echo $row['BookID']; ?>" class="action-link" style="color: #e74c3c;" onclick="return confirm('Delete this book from catalog?')">Delete</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 40px; color: #666;">
                            No matching resources found for "<?php echo htmlspecialchars($search); ?>".
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>