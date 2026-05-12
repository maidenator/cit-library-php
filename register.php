<?php
require 'db.php';

if (isset($_POST['register'])) {
    $userID = $_POST['userID'];
    $fname = $_POST['firstName'];
    $lname = $_POST['lastName'];
    $email = $_POST['email'];
    $role = $_POST['role'];
    $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $limit = ($role == 'Faculty') ? 10 : 5;

    $sql = "INSERT INTO USER (UserID, FirstName, LastName, Email, Password, UserRole, MaxLoanLimit) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ssssssi", $userID, $fname, $lname, $email, $pass, $role, $limit);

    if (mysqli_stmt_execute($stmt)) {
        header("Location: login.php?msg=Registration Successful");
        exit();
    } else {
        $error = "Error: " . mysqli_error($conn);
    }
}
?>

<div style="font-family: sans-serif; max-width: 400px; margin: 50px auto; border: 2px solid #800000; padding: 20px; box-shadow: 5px 5px 0px #f4b400;">
    <h2 style="color: #800000; border-bottom: 2px solid #f4b400; padding-bottom: 10px;">Register Account</h2>
    
    <?php if(isset($error)): ?>
        <p style="color: red; font-size: 14px;"><?php echo $error; ?></p>
    <?php endif; ?>

    <form method="POST">
        <label style="font-size: 11px; font-weight: bold; text-transform: uppercase;">University ID</label>
        <input type="text" name="userID" placeholder="e.g. 2024-0001" required style="width:100%; padding: 10px; margin: 5px 0 15px 0; border: 1px solid #ccc; box-sizing: border-box;">

        <div style="display: flex; gap: 10px;">
            <div style="flex: 1;">
                <label style="font-size: 11px; font-weight: bold; text-transform: uppercase;">First Name</label>
                <input type="text" name="firstName" placeholder="Juan" required style="width:100%; padding: 10px; margin: 5px 0 15px 0; border: 1px solid #ccc; box-sizing: border-box;">
            </div>
            <div style="flex: 1;">
                <label style="font-size: 11px; font-weight: bold; text-transform: uppercase;">Last Name</label>
                <input type="text" name="lastName" placeholder="Dela Cruz" required style="width:100%; padding: 10px; margin: 5px 0 15px 0; border: 1px solid #ccc; box-sizing: border-box;">
            </div>
        </div>

        <label style="font-size: 11px; font-weight: bold; text-transform: uppercase;">Email Address</label>
        <input type="email" name="email" placeholder="teknoy@cit.edu" required style="width:100%; padding: 10px; margin: 5px 0 15px 0; border: 1px solid #ccc; box-sizing: border-box;">

        <label style="font-size: 11px; font-weight: bold; text-transform: uppercase;">Password</label>
        <input type="password" name="password" placeholder="••••••••" required style="width:100%; padding: 10px; margin: 5px 0 15px 0; border: 1px solid #ccc; box-sizing: border-box;">

        <label style="font-size: 11px; font-weight: bold; text-transform: uppercase;">User Role</label>
        <select name="role" required style="width:100%; padding: 10px; margin: 5px 0 20px 0; border: 1px solid #ccc; box-sizing: border-box;">
            <option value="Student">Student</option>
            <option value="Faculty">Faculty</option>
        </select>

        <button type="submit" name="register" style="background:#800000; color:white; width:100%; padding:12px; border:none; cursor:pointer; font-weight:bold; text-transform: uppercase;">Create Account</button>
    </form>
    
    <p style="text-align: center; font-size: 14px; margin-top: 20px;">
        Already have an account? <br>
        <a href="login.php" style="color: #800000; font-weight: bold; text-decoration: none;">Return to Login</a>
    </p>
</div>