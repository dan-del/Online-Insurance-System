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

// Check if the logged-in user is a customer, otherwise redirect (or show error)
if ($_SESSION["role"] !== 'customer') {
    header("location: dashboard.php");
    exit;
}

// Include the database connection file
require_once 'config/database.php';

$user_id = $_SESSION["user_id"];

$customer_applications = [];
$customer_policies = [];
$error_message = "";

// --- Fetch Customer Applications ---
// Select applications along with policy type name and application status name
$sql_applications = "SELECT
                        a.application_id,
                        pt.name AS policy_type_name,
                        a.desired_duration_months,
                        a.application_date,
                        asl.status_name
                     FROM
                        applications a
                     JOIN
                        policy_types pt ON a.policy_type_id = pt.policy_type_id
                     JOIN
                        application_status_lookup asl ON a.status_id = asl.status_id
                     WHERE
                        a.user_id = ?
                     ORDER BY a.application_date DESC";

if ($stmt = mysqli_prepare($link, $sql_applications)) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($result)) {
            $customer_applications[] = $row;
        }
    } else {
        $error_message .= "Error fetching applications: " . mysqli_error($link) . "<br>";
    }
    mysqli_stmt_close($stmt);
} else {
    $error_message .= "Database preparation error for applications: " . mysqli_error($link) . "<br>";
}

// --- Fetch Customer Approved Policies ---
// Select policies along with policy type name
$sql_policies = "SELECT
                    p.policy_id,
                    p.policy_number,
                    pt.name AS policy_type_name,
                    p.start_date,
                    p.end_date,
                    p.premium_amount,
                    p.policy_status
                 FROM
                    policies p
                 JOIN
                    policy_types pt ON p.policy_type_id = pt.policy_type_id
                 WHERE
                    p.user_id = ?
                 ORDER BY p.start_date DESC";

if ($stmt = mysqli_prepare($link, $sql_policies)) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($result)) {
            $customer_policies[] = $row;
        }
    } else {
        $error_message .= "Error fetching policies: " . mysqli_error($link) . "<br>";
    }
    mysqli_stmt_close($stmt);
} else {
    $error_message .= "Database preparation error for policies: " . mysqli_error($link) . "<br>";
}

// Close database connection
mysqli_close($link);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Policies - Online Insurance System</title>
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
            max-width: 1200px;
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
        .error-message-display {
            color: #d9534f;
            font-weight: bold;
            text-align: center;
            margin-bottom: 20px;
        }
        .logout-btn {
            background-color: #dc3545;
            color: #fff;
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none; /* For the <a> tag */
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
        <h2>My Insurance Applications & Policies</h2>

        <?php if (!empty($error_message)): ?>
            <div class="error-message-display"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <h3>My Applications</h3>
        <div class="table-responsive">
            <?php if (!empty($customer_applications)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Application ID</th>
                            <th>Policy Type</th>
                            <th>Desired Duration (Months)</th>
                            <th>Application Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($customer_applications as $app): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($app['application_id']); ?></td>
                                <td><?php echo htmlspecialchars($app['policy_type_name']); ?></td>
                                <td><?php echo htmlspecialchars($app['desired_duration_months']); ?></td>
                                <td><?php echo htmlspecialchars($app['application_date']); ?></td>
                                <td><?php echo htmlspecialchars($app['status_name']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-records">You have no pending or past applications. <a href="apply_policy.php">Apply for a new policy now!</a></div>
            <?php endif; ?>
        </div>

        <h3>My Approved Policies</h3>
        <div class="table-responsive">
            <?php if (!empty($customer_policies)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Policy ID</th>
                            <th>Policy Number</th>
                            <th>Policy Type</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Premium Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($customer_policies as $policy): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($policy['policy_id']); ?></td>
                                <td><?php echo htmlspecialchars($policy['policy_number']); ?></td>
                                <td><?php echo htmlspecialchars($policy['policy_type_name']); ?></td>
                                <td><?php echo htmlspecialchars($policy['start_date']); ?></td>
                                <td><?php echo htmlspecialchars($policy['end_date']); ?></td>
                                <td><?php echo htmlspecialchars(number_format($policy['premium_amount'], 2)); ?></td>
                                <td><?php echo htmlspecialchars($policy['policy_status']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-records">You do not have any active insurance policies.</div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>