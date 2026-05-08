<?php
session_start();
require 'db.php';

if (isset($_POST['login'])) {
    $userID = $_POST['userID'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM USER WHERE UserID = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $userID);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($user = mysqli_fetch_assoc($result)) {
        if (password_verify($password, $user['Password'])) {
            $_SESSION['user_id'] = $user['UserID'];
            $_SESSION['role'] = $user['UserRole'];
            $_SESSION['name'] = $user['FirstName'];

            header("Location: index.php");
            exit();
        } else {
            $error = "Invalid Password.";
        }
    } else {
        $error = "User not found.";
    }
}
?>

<div style="font-family: sans-serif; max-width: 400px; margin: 50px auto; border: 2px solid #800000; padding: 20px; box-shadow: 5px 5px 0px #f4b400;">
    <h2 style="color: #800000; border-bottom: 2px solid #f4b400; padding-bottom: 10px;">CIT-U Library Login</h2>
    
    <?php if(isset($error)): ?>
        <p style="color: red; font-size: 14px;"><?php echo $error; ?></p>
    <?php endif; ?>

    <?php if(isset($_GET['msg'])): ?>
        <p style="color: green; font-size: 14px;"><?php echo htmlspecialchars($_GET['msg']); ?></p>
    <?php endif; ?>

    <form method="POST">
        <label style="font-size: 12px; font-weight: bold;">UNIVERSITY ID</label>
        <input type="text" name="userID" placeholder="e.g. 2022-12345" required style="width:100%; padding: 10px; margin: 5px 0 15px 0; border: 1px solid #ccc; box-sizing: border-box;">
        
        <label style="font-size: 12px; font-weight: bold;">PASSWORD</label>
        <input type="password" name="password" placeholder="••••••••" required style="width:100%; padding: 10px; margin: 5px 0 20px 0; border: 1px solid #ccc; box-sizing: border-box;">
        
        <button type="submit" name="login" style="background:#800000; color:white; width:100%; padding:12px; border:none; cursor:pointer; font-weight:bold; text-transform: uppercase;">Login</button>
    </form>
    
    <p style="text-align: center; font-size: 14px; margin-top: 20px;">
        Don't have an account? <br>
        <a href="register.php" style="color: #800000; font-weight: bold; text-decoration: none;">Register as Student or Faculty</a>
    </p>
</div>