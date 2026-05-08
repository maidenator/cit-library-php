<?php
require 'db.php';

if (isset($_POST['submit'])) {
    $userID = $_POST['userID'];
    $fname = $_POST['firstName'];
    $lname = $_POST['lastName'];
    $email = $_POST['email'];
    $role = $_POST['userRole'];
    $pass = password_hash($_POST['password'], PASSWORD_DEFAULT); // Secure hashing for BR5 

    // Apply Business Rule 3: Loan Limits 
    $limit = 0;
    if ($role == 'Student') $limit = 5;
    elseif ($role == 'Faculty') $limit = 10;

    $sql = "INSERT INTO USER (UserID, FirstName, LastName, Email, Password, UserRole, MaxLoanLimit) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ssssssi", $userID, $fname, $lname, $email, $pass, $role, $limit);

    if (mysqli_stmt_execute($stmt)) {
        header("Location: index.php");
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>

<form method="POST">
    <input type="text" name="userID" placeholder="University ID (e.g., 2024-0001)" required><br>
    <input type="text" name="firstName" placeholder="First Name" required><br>
    <input type="text" name="lastName" placeholder="Last Name" required><br>
    <input type="email" name="email" placeholder="Email Address" required><br>
    <input type="password" name="password" placeholder="Password" required><br>
    <select name="userRole">
        <option value="Student">Student</option>
        <option value="Faculty">Faculty</option>
        <option value="Admin">Admin</option>
    </select><br>
    <button type="submit" name="submit">Register User</button>
</form>