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

// Check if the logged-in user has 'company_official' or 'administrator' role
if ($_SESSION["role"] !== 'company_official' && $_SESSION["role"] !== 'administrator') {
    // Redirect unauthorized roles back to dashboard or an access denied page
    header("location: dashboard.php");
    exit;
}

// Include the database connection file
require_once 'config/database.php';

$applications = [];
$error_message = "";

// Fetch all applications along with customer details, policy type, and status
$sql_applications = "SELECT
                        a.application_id,
                        u.first_name,
                        u.last_name,
                        u.email,
                        pt.name AS policy_type_name,
                        a.desired_duration_months,
                        a.application_date,
                        asl.status_name,
                        a.additional_notes
                     FROM
                        applications a
                     JOIN
                        users u ON a.user_id = u.user_id
                     JOIN
                        policy_types pt ON a.policy_type_id = pt.policy_type_id
                     JOIN
                        application_status_lookup asl ON a.status_id = asl.status_id
                     ORDER BY a.application_date DESC"; // Order by most recent applications

if ($result = mysqli_query($link, $sql_applications)) {
    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $applications[] = $row;
        }
        mysqli_free_result($result);
    } else {
        $error_message = "No insurance applications found.";
    }
} else {
    $error_message = "Error fetching applications: " . mysqli_error($link);
}

// Close database connection
mysqli_close($link);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Applications - Online Insurance System</title>
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
            max-width: 1400px;
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
        .error-message-display {
            color: #d9534f;
            font-weight: bold;
            text-align: center;
            margin-bottom: 20px;
        }
        .action-btn {
            display: inline-block;
            padding: 5px 10px;
            background-color: #28a745;
            color: #fff;
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.2s ease;
            font-size: 0.9em;
        }
        .action-btn:hover {
            background-color: #218838;
        }
        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.8em;
            font-weight: bold;
            color: #fff;
        }
        .status-pending { background-color: #ffc107; color: #333; } /* Warning */
        .status-approved { background-color: #28a745; } /* Success */
        .status-rejected { background-color: #dc3545; } /* Danger */
        .status-under_review { background-color: #17a2b8; } /* Info */

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
        <h2>All Insurance Applications</h2>

        <?php if (!empty($error_message)): ?>
            <div class="error-message-display"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <div class="table-responsive">
            <?php if (!empty($applications)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>App ID</th>
                            <th>Customer Name</th>
                            <th>Customer Email</th>
                            <th>Policy Type</th>
                            <th>Duration (Months)</th>
                            <th>App Date</th>
                            <th>Status</th>
                            <th>Notes</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($applications as $app): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($app['application_id']); ?></td>
                                <td><?php echo htmlspecialchars($app['first_name'] . ' ' . $app['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($app['email']); ?></td>
                                <td><?php echo htmlspecialchars($app['policy_type_name']); ?></td>
                                <td><?php echo htmlspecialchars($app['desired_duration_months']); ?></td>
                                <td><?php echo htmlspecialchars($app['application_date']); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower(str_replace(' ', '_', htmlspecialchars($app['status_name']))); ?>">
                                        <?php echo htmlspecialchars($app['status_name']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($app['additional_notes']); ?></td>
                                <td>
                                    <?php if ($app['status_name'] === 'Pending' || $app['status_name'] === 'Under Review'): ?>
                                        <a href="process_application.php?id=<?php echo htmlspecialchars($app['application_id']); ?>" class="action-btn">Process</a>
                                    <?php else: ?>
                                        <a href="process_application.php?id=<?php echo htmlspecialchars($app['application_id']); ?>" class="action-btn" style="background-color: #6c757d;">View</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-records">No insurance applications found.</div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>