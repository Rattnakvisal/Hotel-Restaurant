<?php
session_start();
$connection = require_once '../config/connect.php';

function set_user_session($user_id, $name, $role)
{
    if (strtolower($role) === 'admin') {
        $_SESSION['admin_id'] = $user_id;
        $_SESSION['admin_name'] = $name;
        $_SESSION['admin_role'] = $role;
        $_SESSION['admin_last_activity'] = time();
    } else {
        $_SESSION['user_id'] = $user_id;
        $_SESSION['name'] = $name;
        $_SESSION['role'] = $role;
        $_SESSION['last_activity'] = time();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($name) || empty($email) || empty($password)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        $sql_check = "SELECT * FROM users WHERE email = :email";
        $stmt_check = oci_parse($connection, $sql_check);
        oci_bind_by_name($stmt_check, ':email', $email);
        oci_execute($stmt_check);

        $existing_user = oci_fetch_array($stmt_check, OCI_ASSOC);
        oci_free_statement($stmt_check);

        if ($existing_user) {
            $error = "Email already registered.";
        } else {
            $sql = "INSERT INTO users (user_id, name, email, password, role, status, created_at)
              VALUES (users_seq.NEXTVAL, :name, :email, :password, 'user', 'active', SYSDATE)
              RETURNING user_id INTO :new_user_id";

            $stmt = oci_parse($connection, $sql);
            oci_bind_by_name($stmt, ':name', $name);
            oci_bind_by_name($stmt, ':email', $email);
            oci_bind_by_name($stmt, ':password', $hashed_password);
            oci_bind_by_name($stmt, ':new_user_id', $new_user_id, 20);

            $result = oci_execute($stmt);

            if ($result) {
                // Fetch the newly registered user's name from DB to ensure session consistency
                $sql_user = "SELECT name FROM users WHERE user_id = :user_id";
                $stmt_user = oci_parse($connection, $sql_user);
                oci_bind_by_name($stmt_user, ':user_id', $new_user_id);
                oci_execute($stmt_user);
                $user_row = oci_fetch_array($stmt_user, OCI_ASSOC);
                $session_name = $user_row && isset($user_row['NAME']) ? $user_row['NAME'] : $name;
                oci_free_statement($stmt_user);

                set_user_session($new_user_id, $session_name, 'user');

                oci_free_statement($stmt);
                oci_close($connection);

                header("Location: ../user/index.php");
                exit;
            } else {
                $error = "Registration failed. Please try again.";
            }
            oci_free_statement($stmt);
        }
    }
}

oci_close($connection);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" /> <!-- ADD THIS -->
    <title>RoyalNest | Register</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">

    <style>
        * {
            box-sizing: border-box;
            /* ADD THIS */
        }

        body {
            background: #fff;
            color: #111;
            font-family: 'Poppins', sans-serif;
            margin: 0;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 1rem;
            /* Add padding for small screens */
        }

        .auth-container {
            background: #fff;
            color: #111;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            animation: fadeSlideUp 0.8s ease both;
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

        .auth-container .logo {
            display: block;
            margin: 0 auto 20px;
            width: 80px;
            border-radius: 50%;
            height: auto;
        }

        .auth-container h2 {
            text-align: center;
            margin-bottom: 25px;
            color: #111;
            font-size: 1.5rem;
        }

        .auth-container .form-group {
            display: flex;
            flex-direction: column;
            margin-bottom: 1rem;
            text-align: left;
        }

        .auth-container label {
            margin-bottom: 5px;
            color: #111;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .auth-container input {
            padding: 0.75rem;
            border: 1px solid #ccc;
            border-radius: 8px;
            transition: border 0.3s, box-shadow 0.3s;
            font-size: 1rem;
            color: #111;
            background: #fff;
        }

        .auth-container input:focus {
            border-color: #3f51b5;
            box-shadow: 0 0 0 4px rgba(63, 81, 181, 0.1);
            outline: none;
        }

        .auth-container button {
            width: 100%;
            padding: 12px 15px;
            background: #111;
            border: none;
            border-radius: 8px;
            color: #fff;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: background 0.3s ease, transform 0.2s ease;
        }

        .auth-container button:hover {
            background: #333;
            transform: scale(1.02);
        }

        .auth-container .error {
            color: red;
            text-align: center;
            margin-bottom: 15px;
            font-size: 0.9rem;
        }

        .auth-container p {
            text-align: center;
            margin-top: 20px;
            color: #555;
            font-size: 0.85rem;
        }

        .auth-container p a {
            color: #111;
            text-decoration: none;
        }

        .auth-container p a:hover {
            text-decoration: underline;
        }

        @media (max-width: 480px) {
            .auth-container {
                padding: 1.5rem 1rem;
            }

            .auth-container .logo {
                width: 60px;
            }

            .auth-container h2 {
                font-size: 1.25rem;
            }

            .auth-container input {
                font-size: 0.95rem;
            }

            .auth-container button {
                font-size: 0.95rem;
            }
        }
    </style>
</head>

<body>

    <div class="auth-container">
        <img src="../assets/Logo/RoyalNest.png" alt="RoyalNest Logo" class="logo">
        <h2>RoyalNest Register</h2>

        <?php if (!empty($error)) : ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="name" placeholder="Your Full Name" required />
            </div>

            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" placeholder="you@example.com" required />
            </div>

            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="Create a password" required />
            </div>

            <div class="form-group">
                <label>Confirm Password</label>
                <input type="password" name="confirm_password" placeholder="Confirm your password" required />
            </div>

            <button type="submit">Register</button>
        </form>

        <p>Already have an account? <a href="login.php">Login here</a></p>
    </div>

</body>

</html>