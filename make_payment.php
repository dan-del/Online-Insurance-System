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

// Include header
include 'header.php';
?>

<style>
    .container-custom {
        max-width: 900px;
        margin: 40px auto;
        background: #fff;
        padding: 25px;
        border-radius: 10px;
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
    h2 {
        text-align: center;
        margin-bottom: 25px;
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
    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }
    table th, table td {
        padding: 12px;
        border: 1px solid #ccc;
    }
    table th {
        background: #f4f4f4;
    }
    .payment-btn {
        background: #007bff;
        color: #fff;
        padding: 8px 15px;
        border-radius: 5px;
        text-decoration: none;
    }
    .payment-btn:hover {
        background: #0056b3;
    }
    .payment-instructions {
        background: #f8f9fa;
        border: 1px solid #e2e6ea;
        border-radius: 6px;
        padding: 20px;
        margin-top: 25px;
    }
    .payment-instructions ul {
        padding-left: 20px;
    }
    .no-records {
        text-align: center;
        padding: 20px;
        background: #fafafa;
        border: 1px dashed #ccc;
        border-radius: 6px;
    }
</style>

<div class="container-custom">

    <h2>Make a Payment</h2>

    <?php if (!empty($error_message)): ?>
        <div class="message-box error"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <?php if (!empty($success_message)): ?>
        <div class="message-box success"><?php echo htmlspecialchars($success_message); ?></div>
    <?php endif; ?>

    <h3>Your Active Policies</h3>

    <?php if (!empty($active_policies)): ?>
        <table>
            <thead>
                <tr>
                    <th>Policy Number</th>
                    <th>Policy Type</th>
                    <th>Premium Amount</th>
                    <th>Next Due Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($active_policies as $policy): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($policy['policy_number']); ?></td>
                        <td><?php echo htmlspecialchars($policy['policy_type_name']); ?></td>
                        <td>KES <?php echo number_format($policy['premium_amount'], 2); ?></td>
                        <td><?php echo htmlspecialchars($policy['end_date']); ?></td>
                        <td>
                            <a href="make_payment.php?payment_success=true&policy_id=<?php echo $policy['policy_id']; ?>" class="payment-btn">Pay Now</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="no-records">You do not have any active insurance policies to pay for.</div>
    <?php endif; ?>

    <h3>Manual Payment Instructions</h3>
    <div class="payment-instructions">
        <p>If you prefer to make a manual payment, please follow the instructions below:</p>

        <ul>
            <li><strong>M-Pesa:</strong>
                <ul>
                    <li>Go to Lipa Na M-Pesa > Pay Bill.</li>
                    <li>Business Number: <strong>XXXXXX</strong></li>
                    <li>Account Number: Your Policy Number (<strong><?php echo $active_policies[0]['policy_number'] ?? 'N/A'; ?></strong>)</li>
                    <li>Amount: <strong>KES <?php echo number_format($active_policies[0]['premium_amount'] ?? 0, 2); ?></strong></li>
                </ul>
            </li>

            <li><strong>Bank Transfer:</strong>
                <ul>
                    <li>Bank Name: <strong>Your Insurance Bank</strong></li>
                    <li>Account Name: <strong>Online Insurance Ltd</strong></li>
                    <li>Account Number: <strong>XXXXXXXXXXXX</strong></li>
                    <li>Reference: Your Policy Number</li>
                </ul>
            </li>
        </ul>

    </div>

</div>

<?php include 'footer.php'; ?>
