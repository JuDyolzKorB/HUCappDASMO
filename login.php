<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require "HUCappDB.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare("SELECT UserID, Role, Password FROM `user` WHERE Username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if ($password === $user['Password']) {

            session_start();
            $_SESSION['user_id'] = $user['UserID'];
            $_SESSION['role'] = $user['Role'];

            if ($user['Role'] === "health_center") {
                echo "LOGIN_SUCCESS_HEALTH_CENTER";
            } elseif ($user['Role'] === "warehouse") {
                echo "LOGIN_SUCCESS_WAREHOUSE";
            } elseif ($user['Role'] === "pharmacy") {
                echo "LOGIN_SUCCESS_PHARMACY";
            } else {
                echo "UNKNOWN_ROLE";
            }

            exit;
        } else {
            $error = "Invalid password";
        }
    } else {
        $error = "User not found";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
</head>
<body>

<h2>Login</h2>

<?php if (!empty($error)) echo "<p style='color:red;'>$error</p>"; ?>

<form method="POST" action="">
    <label>Username:</label><br>
    <input type="text" name="username" required><br><br>

    <label>Password:</label><br>
    <input type="password" name="password" required><br><br>

    <button type="submit">Login</button>
</form>

</body>
</html>
