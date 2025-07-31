<?php
// Start the session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is logged in, if not then redirect to login page
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// Check if the logged-in user is a customer, otherwise redirect
if ($_SESSION["role"] !== 'customer') {
    header("location: dashboard.php");
    exit;
}

// Include the database connection file
require_once 'config/database.php';

$user_id = $_SESSION["user_id"];
$active_policies = [];
$error_message = "";
$success_message = "";

// --- Fetch Active Policies for the Customer ---
$sql_active_policies = "SELECT
                            p.policy_id,
                            p.policy_number,
                            pt.name AS policy_type_name,
                            p.premium_amount,
                            p.end_date
                         FROM
                            policies p
                         JOIN
                            policy_types pt ON p.policy_type_id = pt.policy_type_id
                         WHERE
                            p.user_id = ? AND p.policy_status = 'Active'
                         ORDER BY p.end_date ASC";

if ($stmt = mysqli_prepare($link, $sql_active_policies)) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($result)) {
            $active_policies[] = $row;
        }
    } else {
        $error_message = "Error fetching active policies: " . mysqli_error($link);
    }
    mysqli_stmt_close($stmt);
} else {
    $error_message = "Database preparation error for policies: " . mysqli_error($link);
}

// --- Check for simulated payment success message from URL ---
if (isset($_GET['payment_success']) && $_GET['payment_success'] === 'true') {
    $success_message = "Payment simulated successfully! Thank you for your payment.";
}

// Close database connection
mysqli_close($link);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Make Payment - Online Insurance System</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .header {
            background-color: #007bff;
            color: #fff;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .header nav a {
            color: #fff;
            text-decoration: none;
            margin-left: 20px;
            font-size: 16px;
            padding: 5px 10px;
            border-radius: 4px;
            transition: background-color 0.2s ease;
        }
        .header nav a:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }
        .container {
            flex-grow: 1;
            padding: 30px;
            max-width: 1000px;
            margin: 20px auto;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        h2 {
            text-align: center;
            color: #333;
            margin-bottom: 25px;
        }
        h3 {
            color: #007bff;
            border-bottom: 2px solid #007bff;
            padding-bottom: 5px;
            margin-top: 30px;
            margin-bottom: 20px;
        }
        .message-box {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: center;
        }
        .message-box.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message-box.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .table-responsive {
            overflow-x: auto;
            margin-bottom: 30px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        table th, table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
            vertical-align: top;
        }
        table th {
            background-color: #f2f2f2;
            font-weight: bold;
            color: #333;
        }
        table tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .no-records {
            text-align: center;
            color: #666;
            padding: 20px;
            border: 1px dashed #ddd;
            border-radius: 5px;
        }
        .payment-instructions {
            background-color: #f8f9fa;
            border: 1px solid #e2e6ea;
            border-radius: 6px;
            padding: 20px;
        }
        .payment-instructions ul {
            list-style-type: none;
            padding: 0;
        }
        .payment-instructions li {
            margin-bottom: 10px;
        }
        .payment-instructions strong {
            color: #333;
        }
        .payment-btn {
            background-color: #007bff;
            color: #fff;
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            transition: background-color 0.2s ease;
        }
        .payment-btn:hover {
            background-color: #0056b3;
        }
        .logout-btn {
            background-color: #dc3545;
            color: #fff;
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            transition: background-color 0.2s ease;
        }
        .logout-btn:hover {
            background-color: #c82333;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Online Insurance System</h1>
        <nav>
            <a href="dashboard.php">Dashboard</a>
            <?php if ($_SESSION["role"] === 'customer'): ?>
                <a href="apply_policy.php">Apply for Policy</a>
                <a href="my_policies.php">My Policies</a>
                <a href="make_payment.php">Make Payment</a>
            <?php elseif ($_SESSION["role"] === 'company_official'): ?>
                <a href="view_applications.php">View Applications</a>
                <a href="manage_policies.php">Manage Policies</a>
                <a href="create_policy_type.php">New Policy Type</a>
            <?php elseif ($_SESSION["role"] === 'administrator'): ?>
                <a href="admin_users.php">Manage Users</a>
                <a href="admin_applications.php">Manage Applications</a>
                <a href="admin_policy_types.php">Manage Policy Types</a>
            <?php endif; ?>
            <a href="logout.php" class="logout-btn">Logout</a>
        </nav>
    </div>

    <div class="container">
        <h2>Make a Payment</h2>
        [cite_start]<p>View your active policies and proceed with a payment for the premium[cite: 107].</p>

        <?php if (!empty($error_message)): ?>
            <div class="message-box error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        <?php if (!empty($success_message)): ?>
            <div class="message-box success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>

        <h3>Your Active Policies</h3>
        <div class="table-responsive">
            <?php if (!empty($active_policies)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Policy Number</th>
                            <th>Policy Type</th>
                            <th>Premium Amount</th>
                            <th>Next Due Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($active_policies as $policy): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($policy['policy_number']); ?></td>
                                <td><?php echo htmlspecialchars($policy['policy_type_name']); ?></td>
                                <td>KES <?php echo htmlspecialchars(number_format($policy['premium_amount'], 2)); ?></td>
                                <td><?php echo htmlspecialchars($policy['end_date']); ?></td>
                                <td>
                                    <a href="make_payment.php?payment_success=true&policy_id=<?php echo htmlspecialchars($policy['policy_id']); ?>" class="payment-btn">Pay Now</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-records">You do not have any active insurance policies to pay for.</div>
            <?php endif; ?>
        </div>

        <h3>Manual Payment Instructions</h3>
        <div class="payment-instructions">
            <p>If you prefer to make a manual payment, please follow the instructions below:</p>
            <ul>
                <li><strong>M-Pesa:</strong>
                    <ul>
                        <li>Go to M-Pesa Menu > Lipa Na M-Pesa > Pay Bill.</li>
                        <li>Enter Business Number: <strong>XXXXXX</strong>.</li>
                        <li>Enter Account Number: Your Policy Number (e.g., <strong><?php echo htmlspecialchars($active_policies[0]['policy_number'] ?? 'N/A'); ?></strong>).</li>
                        <li>Enter Amount: The premium amount (e.g., <strong>KES <?php echo htmlspecialchars(number_format($active_policies[0]['premium_amount'] ?? 0, 2)); ?></strong>).</li>
                        <li>Enter your M-Pesa PIN and confirm.</li>
                    </ul>
                </li>
                <li><strong>Bank Transfer:</strong>
                    <ul>
                        <li>Bank Name: <strong>Your Insurance Bank</strong></li>
                        <li>Account Name: <strong>Online Insurance Ltd</strong></li>
                        <li>Account Number: <strong>XXXXXXXXXXXXXX</strong></li>
                        <li>Reference: Your Policy Number.</li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</body>
</html>