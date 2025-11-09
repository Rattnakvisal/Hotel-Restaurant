<?php
require_once("../../config/connect.php");
?>
<style>
    :root {
        --primary: #2c3e50;
        --primary-light: #34495e;
        --primary-dark: #1a252f;
        --secondary: #3498db;
        --accent: #e74c3c;
        --success: #27ae60;
        --light: #ecf0f1;
        --dark: #2c3e50;
        --gray: #7f8c8d;
        --light-gray: #e9ecef;
        --card-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        --transition: all 0.3s ease;
    }

    body {
        font-family: 'Arial', sans-serif;
        background: #f4f7fa;
        color: #333;
        margin: 0;
        padding: 0;
    }

    .content {
        margin-left: 260px;
        padding: 30px;
        transition: all 0.3s ease;
    }

    .contact-table-container {
        background: #fff;
        border-radius: 16px;
        box-shadow: var(--card-shadow);
        overflow-x: auto;
        margin-bottom: 40px;
        transition: box-shadow 0.3s;
    }

    .contact-table-container:hover {
        box-shadow: 0 12px 35px rgba(0, 0, 0, 0.12);
    }

    .contact-title {
        padding: 25px 30px;
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        color: #fff;
        font-size: 1.8rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 15px;
        border-top-left-radius: 16px;
        border-top-right-radius: 16px;
    }

    .contact-title i {
        font-size: 1.5rem;
        background: rgba(255, 255, 255, 0.2);
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .contact-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        min-width: 900px;
    }

    .contact-table th {
        padding: 16px 18px;
        text-align: left;
        font-weight: 600;
        color: var(--primary);
        background: #f4f7fa;
        border-bottom: 2px solid var(--light-gray);
        font-size: 1rem;
        position: sticky;
        top: 0;
        z-index: 1;
    }

    .contact-table td {
        padding: 16px 18px;
        border-bottom: 1px solid var(--light-gray);
        font-size: 0.98rem;
        vertical-align: top;
        background: #fff;
    }

    .contact-table tbody tr {
        transition: background 0.2s;
    }

    .contact-table tbody tr:hover {
        background: #f0f6fa;
    }

    .contact-table tbody tr:nth-child(even) {
        background: #f9fafb;
    }

    .contact-table tbody tr:nth-child(even):hover {
        background: #f0f6fa;
    }

    .message-cell {
        max-width: 350px;
        word-break: break-word;
        line-height: 1.6;
        color: #222;
    }

    .timestamp {
        font-size: 0.92rem;
        color: var(--gray);
        white-space: nowrap;
    }

    .user-info {
        display: flex;
        flex-direction: column;
        gap: 3px;
    }

    .user-name {
        font-weight: 600;
        color: var(--primary);
        font-size: 1.05rem;
    }

    .user-email {
        color: var(--secondary);
        font-size: 0.93rem;
        word-break: break-all;
    }

    .user-phone {
        color: var(--accent);
        font-size: 0.93rem;
        font-weight: 500;
        letter-spacing: 0.5px;
    }

    .no-messages {
        text-align: center;
        padding: 50px 20px;
        color: var(--gray);
        background: #f9fafb;
    }

    .no-messages i {
        font-size: 3rem;
        margin-bottom: 18px;
        color: #d1d5db;
    }

    .no-messages h3 {
        font-weight: 600;
        margin-bottom: 10px;
        color: var(--dark);
    }

    .no-messages p {
        max-width: 500px;
        margin: 0 auto 20px;
        line-height: 1.6;
    }

    .status-badge {
        padding: 6px 15px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
        display: inline-block;
        min-width: 80px;
        text-align: center;
        background: rgba(46, 204, 113, 0.15);
        color: #27ae60;
        border: 1px solid rgba(46, 204, 113, 0.25);
        box-shadow: 0 1px 4px rgba(46, 204, 113, 0.07);
    }

    @media (max-width: 1200px) {
        .content {
            padding: 20px;
        }

        .contact-table th,
        .contact-table td {
            padding: 12px 10px;
        }

        .contact-table {
            min-width: 700px;
        }
    }

    @media (max-width: 992px) {
        .content {
            margin-left: 0;
            padding: 12px;
        }

        .contact-title {
            font-size: 1.3rem;
            padding: 15px 10px;
        }

        .contact-table-container {
            border-radius: 10px;
        }

        .contact-table {
            min-width: 600px;
        }
    }

    @media (max-width: 768px) {
        .content {
            padding: 7px;
        }

        .contact-title {
            font-size: 1.1rem;
            padding: 10px 5px;
        }

        .contact-title i {
            width: 32px;
            height: 32px;
            font-size: 1rem;
        }

        .user-name,
        .user-email,
        .user-phone {
            font-size: 0.9rem;
        }

        .message-cell {
            font-size: 0.92rem;
        }
    }

    @media (max-width: 576px) {
        .contact-title {
            font-size: 1rem;
            flex-direction: column;
            text-align: center;
            gap: 7px;
        }

        .no-messages {
            padding: 20px 5px;
        }

        .no-messages i {
            font-size: 2rem;
        }

        .contact-table {
            min-width: 400px;
        }
    }
</style>
<div class="dashboard-container">
    <?php require_once("../include/Header.php"); ?>
    <div class="content">
        <div class="contact-table-container">
            <div class="contact-title">
                <i class="fas fa-envelope"></i> Contact Messages
            </div>
            <table class="contact-table">
                <thead>
                    <tr>
                        <th>Contact</th>
                        <th>Phone</th>
                        <th>Message</th>
                        <th>Status</th>
                        <th>Timestamp</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql = "SELECT name, email, phone, message, status, TO_CHAR(created_at, 'YYYY-MM-DD HH24:MI:SS') AS created_at FROM contact ORDER BY created_at DESC";
                    $stmt = oci_parse($connection, $sql);
                    oci_execute($stmt);

                    $hasMessages = false;

                    while ($row = oci_fetch_assoc($stmt)) {
                        $hasMessages = true;
                        $messageText = '';
                        if (is_object($row['MESSAGE']) && method_exists($row['MESSAGE'], 'load')) {
                            $messageText = $row['MESSAGE']->load();
                        } else {
                            $messageText = (string)$row['MESSAGE'];
                        }
                        $status = htmlspecialchars($row['STATUS'] ?? 'New');
                        echo "<tr>
                            <td>
                                <div class='user-info'>
                                    <div class='user-name'>" . htmlspecialchars($row['NAME']) . "</div>
                                    <div class='user-email'>" . htmlspecialchars($row['EMAIL']) . "</div>
                                </div>
                            </td>
                            <td><span class='user-phone'>" . htmlspecialchars($row['PHONE']) . "</span></td>
                            <td class='message-cell'>" . nl2br(htmlspecialchars($messageText)) . "</td>
                            <td>
                                <span class='status-badge'>$status</span>
                            </td>
                            <td class='timestamp'>" . $row['CREATED_AT'] . "</td>
                        </tr>";
                    }

                    oci_free_statement($stmt);

                    if (!$hasMessages) {
                        echo '<tr>
                            <td colspan="5">
                                <div class="no-messages">
                                    <i class="fas fa-inbox"></i>
                                    <h3>No Contact Messages</h3>
                                    <p>You haven\'t received any contact messages yet. All new messages will appear here.</p>
                                </div>
                            </td>
                        </tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>