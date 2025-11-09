<?php
session_start();
$connection = require_once '../config/connect.php';
function set_user_session($user)
{
    if (strtolower($user['ROLE']) === 'admin') {
        $_SESSION['admin_id'] = $user['USER_ID'];
        $_SESSION['admin_name'] = $user['NAME'];
        $_SESSION['admin_role'] = $user['ROLE'];
        $_SESSION['admin_last_activity'] = time();
    } else {
        $_SESSION['user_id'] = $user['USER_ID'];
        $_SESSION['name'] = $user['NAME'];
        $_SESSION['role'] = $user['ROLE'];
        $_SESSION['last_activity'] = time();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $sql = "SELECT user_id, name, email, password, role FROM users WHERE email = :email";
    $stmt = oci_parse($connection, $sql);
    oci_bind_by_name($stmt, ':email', $email);
    oci_execute($stmt);
    $user = oci_fetch_array($stmt, OCI_ASSOC);

    if ($user && password_verify($password, $user['PASSWORD'])) {
        set_user_session($user);

        oci_free_statement($stmt);
        oci_close($connection);

        if (strtolower($user['ROLE']) === 'admin') {
            header("Location: ../admin/dashboard/dashboard.php");
        } else {
            header("Location: ../user/index.php");
        }
        exit;
    } else {
        $error = "Invalid email or password.";
    }

    oci_free_statement($stmt);
}

oci_close($connection);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login | RoyalNest</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <style>
        * {
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: #fff;
            color: #111;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            overflow: hidden;
            animation: gradientShift 8s ease infinite alternate;
        }

        @keyframes gradientShift {
            0% {
                background-position: 0% 50%;
            }

            100% {
                background-position: 100% 50%;
            }
        }

        .login-container {
            background: #fff;
            color: #111;
            padding: 2rem 2.5rem;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            animation: fadeSlideUp 0.8s ease both;
            text-align: center;
        }

        @keyframes fadeSlideUp {
            0% {
                opacity: 0;
                transform: translateY(30px);
            }

            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-container img {
            border-radius: 50%;
        }

        .logo {
            width: 80px;
            height: 80px;
            margin-bottom: 1rem;
            object-fit: contain;
        }

        h2 {
            margin-bottom: 1.5rem;
            color: #111;
            animation: fadeIn 1s ease forwards;
        }

        form {
            display: flex;
            flex-direction: column;
            text-align: left;
        }

        label {
            font-weight: bold;
            margin-bottom: 0.3rem;
            color: #111;
            opacity: 0;
            animation: fadeInUp 0.8s ease forwards;
        }

        input {
            padding: 0.75rem;
            margin-bottom: 1rem;
            border: 1px solid #ccc;
            border-radius: 8px;
            transition: border 0.3s, box-shadow 0.3s;
            color: #111;
            background: #fff;
            opacity: 0;
            animation: fadeInUp 0.8s ease forwards;
        }

        input:focus {
            border-color: #2575fc;
            box-shadow: 0 0 0 4px rgba(37, 117, 252, 0.1);
            outline: none;
        }

        form label:nth-of-type(1),
        form input:nth-of-type(1) {
            animation-delay: 0.4s;
        }

        form label:nth-of-type(2),
        form input:nth-of-type(2) {
            animation-delay: 0.6s;
        }

        button {
            padding: 0.75rem;
            background: #111;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
            transition: background 0.3s, transform 0.2s ease;
            opacity: 0;
            animation: fadeInUp 0.8s ease forwards;
            animation-delay: 0.8s;
        }

        button:hover {
            background: #333;
            transform: scale(1.02);
        }

        .error {
            color: red;
            text-align: center;
            margin-bottom: 1rem;
            opacity: 0;
            animation: fadeIn 0.8s ease forwards;
            animation-delay: 0.3s;
        }

        .footer-text {
            margin-top: 1rem;
            text-align: center;
            font-size: 0.9rem;
            opacity: 0;
            animation: fadeIn 0.8s ease forwards;
            animation-delay: 1s;
        }

        .footer-text a {
            color: #111;
            text-decoration: none;
        }

        .footer-text a:hover {
            text-decoration: underline;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 500px) {
            .login-container {
                margin: 1rem;
                padding: 1.5rem;
                max-width: 100%;
                min-width: 0;
            }

            .logo {
                width: 60px;
                height: 60px;
            }

            h2 {
                font-size: 1.2rem;
            }

            input,
            button {
                font-size: 1rem;
            }
        }

        @media (max-width: 350px) {
            .login-container {
                padding: 1rem 0.5rem;
            }

            .logo {
                width: 40px;
                height: 40px;
            }
        }
    </style>
</head>

<body>

    <div class="login-container">
        <!-- ✅ Add your RoyalNest logo here -->
        <img src="../assets/Logo/RoyalNest.png" alt="RoyalNest Logo" class="logo">

        <h2>Welcome to RoyalNest</h2>

        <?php if (!empty($error)) : ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <form method="POST" action="">
            <label for="email">Email</label>
            <input type="email" name="email" id="email" required>

            <label for="password">Password</label>
            <input type="password" name="password" id="password" required>

            <button type="submit">Login</button>
        </form>

        <div class="footer-text">
            Don't have an account? <a href="register.php">Register here</a>
        </div>
    </div>

</body>

</html>