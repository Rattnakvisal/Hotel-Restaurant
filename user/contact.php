<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}
require_once '../config/connect.php';
?>
<?php require_once("../include/Header.php"); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Contact Us | RoyalNest</title>
    <link rel="stylesheet" href="../assets/Css/styles.css">
    <link rel="stylesheet" href="../assets/Styles/contact.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <div class="contact-container">
        <div class="contact-title">
            <i class="fas fa-envelope"></i> Contact Us
        </div>
        <div class="contact-info">
            <div class="info-block">
                <i class="fas fa-map-marker-alt"></i>
                <h4>Address</h4>
                <div>123 RoyalNest Avenue, Luxury City, Country</div>
            </div>
            <div class="info-block">
                <i class="fas fa-phone"></i>
                <h4>Phone</h4>
                <div>+1 (555) 123-4567</div>
            </div>
            <div class="info-block">
                <i class="fas fa-envelope"></i>
                <h4>Email</h4>
                <div>contact@royalnest.com</div>
            </div>
        </div>
        <?php
        $msg = '';
        $name = $email = $phone = $message = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contact_submit'])) {
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $message = trim($_POST['message'] ?? '');
            if (!$name || !$email || !$message) {
                $msg = '<div class="msg-error"><i class="fas fa-exclamation-circle"></i> Please fill in all required fields.</div>';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $msg = '<div class="msg-error"><i class="fas fa-exclamation-circle"></i> Please enter a valid email address.</div>';
            } else {
                $sql = "INSERT INTO contact (id, name, email, phone, message, created_at) VALUES (contact_seq.NEXTVAL, :name, :email, :phone, :message, SYSDATE)";
                $stmt = oci_parse($connection, $sql);
                oci_bind_by_name($stmt, ":name", $name);
                oci_bind_by_name($stmt, ":email", $email);
                oci_bind_by_name($stmt, ":phone", $phone);
                oci_bind_by_name($stmt, ":message", $message);
                $result = oci_execute($stmt);
                if ($result) {
                    $msg = '<div class="msg-success"><i class="fas fa-check-circle"></i> Thank you for contacting us! We will get back to you soon.</div>';
                    $name = $email = $phone = $message = '';
                } else {
                    $msg = '<div class="msg-error"><i class="fas fa-exclamation-circle"></i> Failed to submit your message. Please try again later.</div>';
                }
                oci_free_statement($stmt);
            }
        }
        echo $msg;
        ?>
        <form method="post" class="contact-form" autocomplete="off">
            <div class="form-group" style="position:relative;">
                <label class="form-label" for="name">Your Name <span style="color:#d93025">*</span></label>
                <input type="text" name="name" id="name" class="form-input" placeholder="Enter your full name" value="<?php echo htmlspecialchars($name); ?>" required>
                <i class="fas fa-user input-icon" style="position:absolute;right:16px;top:40px;color:#aaa;"></i>
            </div>
            <div class="form-group" style="position:relative;">
                <label class="form-label" for="email">Your Email <span style="color:#d93025">*</span></label>
                <input type="email" name="email" id="email" class="form-input" placeholder="Enter your email address" value="<?php echo htmlspecialchars($email); ?>" required>
                <i class="fas fa-envelope input-icon" style="position:absolute;right:16px;top:40px;color:#aaa;"></i>
            </div>
            <div class="form-group" style="position:relative;">
                <label class="form-label" for="phone">Your Phone</label>
                <input type="text" name="phone" id="phone" class="form-input" placeholder="Enter your phone number" value="<?php echo htmlspecialchars($phone); ?>">
                <i class="fas fa-phone input-icon" style="position:absolute;right:16px;top:40px;color:#aaa;"></i>
            </div>
            <div class="form-group">
                <label class="form-label" for="message">Message <span style="color:#d93025">*</span></label>
                <textarea name="message" id="message" class="form-textarea" placeholder="How can we assist you?" required><?php echo htmlspecialchars($message); ?></textarea>
            </div>
            <button type="submit" name="contact_submit" class="submit-btn">
                <i class="fas fa-paper-plane"></i> Send Message
            </button>
        </form>
    </div>
    <?php require_once("../include/Footer.php"); ?>
</body>

</html>