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

// Retrieve user information from session
$user_id = $_SESSION["user_id"];
$first_name = $_SESSION["first_name"];
$last_name = $_SESSION["last_name"];
$email = $_SESSION["email"];
$role = $_SESSION["role"]; // 'customer', 'company_official', 'administrator'

// Include database connection
require_once 'config/database.php';

$dashboard_data = [];
$most_recent_policy = null;
$error_message = "";

// --- Conditional Data Fetching based on User Role ---
if ($role === 'customer') {
    // 1. Fetch count of pending applications and active policies
    $sql_customer_stats = "SELECT
                               (SELECT COUNT(*) FROM applications WHERE user_id = ? AND status_id = 1) AS pending_applications,
                               (SELECT COUNT(*) FROM policies WHERE user_id = ? AND policy_status = 'Active') AS active_policies";

    if ($stmt = mysqli_prepare($link, $sql_customer_stats)) {
        mysqli_stmt_bind_param($stmt, "ii", $user_id, $user_id);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            $dashboard_data = mysqli_fetch_assoc($result);
            mysqli_free_result($result);
        } else {
            $error_message = "Error fetching customer stats: " . mysqli_error($link);
        }
        mysqli_stmt_close($stmt);
    }

    // 2. Fetch the most recent active policy details
    $sql_recent_policy = "SELECT
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
                          ORDER BY p.issue_date DESC
                          LIMIT 1";
    if ($stmt = mysqli_prepare($link, $sql_recent_policy)) {
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            if (mysqli_num_rows($result) > 0) {
                $most_recent_policy = mysqli_fetch_assoc($result);
            }
            mysqli_free_result($result);
        } else {
            $error_message .= " Error fetching most recent policy: " . mysqli_error($link);
        }
        mysqli_stmt_close($stmt);
    }

} elseif ($role === 'company_official' || $role === 'administrator') {
    // For Admins/Officials: Fetch total pending applications, total customers, total policies
    $sql_admin_stats = "SELECT
                           (SELECT COUNT(*) FROM applications WHERE status_id = 1) AS total_pending_applications,
                           (SELECT COUNT(*) FROM users WHERE role = 'customer') AS total_customers,
                           (SELECT COUNT(*) FROM policies) AS total_policies";

    if ($result = mysqli_query($link, $sql_admin_stats)) {
        $dashboard_data = mysqli_fetch_assoc($result);
        mysqli_free_result($result);
    } else {
        $error_message = "Error fetching admin dashboard data: " . mysqli_error($link);
    }
}

// Close database connection
mysqli_close($link);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Online Insurance System</title>
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
        .welcome-message {
            text-align: center;
            margin-bottom: 30px;
            font-size: 1.2em;
            color: #333;
        }
        .role-specific-content {
            margin-top: 20px;
            border-top: 1px solid #eee;
            padding-top: 20px;
        }
        .section-title {
            color: #007bff;
            margin-bottom: 15px;
            border-bottom: 2px solid #007bff;
            padding-bottom: 5px;
        }
        .info-card {
            background-color: #f8f9fa;
            border: 1px solid #e2e6ea;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 15px;
        }
        .info-card strong {
            color: #333;
        }
        .dashboard-cards {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: center;
            margin-bottom: 30px;
        }
        .card {
            background-color: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            width: 250px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s ease;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .card-title {
            margin: 0 0 10px 0;
            font-size: 1em;
            color: #555;
        }
        .card-number {
            font-size: 2.5em;
            font-weight: bold;
            color: #007bff;
            margin: 0;
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
            <?php if ($role === 'customer'): ?>
                <a href="apply_policy.php">Apply for Policy</a>
                <a href="my_policies.php">My Policies</a>
                <a href="make_payment.php">Make Payment</a>
            <?php elseif ($role === 'company_official'): ?>
                <a href="view_applications.php">View Applications</a>
                <a href="manage_policies.php">Manage Policies</a>
                <a href="admin_policy_types.php">New Policy Type</a>
            <?php elseif ($role === 'administrator'): ?>
                <a href="view_applications.php">Manage Applications</a>
                <a href="admin_users.php">Manage Users</a>
                <a href="admin_policy_types.php">Manage Policy Types</a>
            <?php endif; ?>
            <a href="logout.php" class="logout-btn">Logout</a>
        </nav>
    </div>

    <div class="container">
        <div class="welcome-message">
            <h2>Welcome, <?php echo htmlspecialchars($first_name); ?> <?php echo htmlspecialchars($last_name); ?>!</h2>
            <p>You are logged in as a <strong><?php echo htmlspecialchars(ucfirst($role)); ?></strong>.</p>
        </div>

        <?php if (!empty($error_message)): ?>
            <div class="message-box error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <?php if ($role === 'customer'): ?>
            <div class="dashboard-cards">
                <div class="card">
                    <p class="card-title">Pending Applications</p>
                    <p class="card-number"><?php echo htmlspecialchars($dashboard_data['pending_applications'] ?? 0); ?></p>
                </div>
                <div class="card">
                    <p class="card-title">Active Policies</p>
                    <p class="card-number"><?php echo htmlspecialchars($dashboard_data['active_policies'] ?? 0); ?></p>
                </div>
            </div>

            <?php if ($most_recent_policy): ?>
            <div class="user-info">
                <h3 class="section-title">Your Most Recent Active Policy</h3>
                <div class="info-card">
                    <p><strong>Policy Number:</strong> <?php echo htmlspecialchars($most_recent_policy['policy_number']); ?></p>
                    <p><strong>Policy Type:</strong> <?php echo htmlspecialchars($most_recent_policy['policy_type_name']); ?></p>
                    <p><strong>Premium:</strong> KES <?php echo htmlspecialchars(number_format($most_recent_policy['premium_amount'], 2)); ?></p>
                    <p><strong>Expires On:</strong> <?php echo htmlspecialchars($most_recent_policy['end_date']); ?></p>
                </div>
            </div>
            <?php endif; ?>

        <?php elseif ($role === 'company_official' || $role === 'administrator'): ?>
            <div class="dashboard-cards">
                <div class="card">
                    <p class="card-title">Pending Applications</p>
                    <p class="card-number"><?php echo htmlspecialchars($dashboard_data['total_pending_applications'] ?? 0); ?></p>
                </div>
                <div class="card">
                    <p class="card-title">Total Customers</p>
                    <p class="card-number"><?php echo htmlspecialchars($dashboard_data['total_customers'] ?? 0); ?></p>
                </div>
                <div class="card">
                    <p class="card-title">Total Policies</p>
                    <p class="card-number"><?php echo htmlspecialchars($dashboard_data['total_policies'] ?? 0); ?></p>
                </div>
            </div>
        <?php endif; ?>

        <div class="role-specific-content">
            <h3 class="section-title">Quick Links</h3>
            <?php if ($role === 'customer'): ?>
                <ul>
                    <li><a href="apply_policy.php">Submit a new insurance application.</a></li>
                    <li><a href="my_policies.php">View your existing policies and their status.</a></li>
                    <li><a href="make_payment.php">Find instructions on how to make premium payments.</a></li>
                </ul>
            <?php elseif ($role === 'company_official' || $role === 'administrator'): ?>
                <ul>
                    <li><a href="view_applications.php">Review new and pending insurance applications.</a></li>
                    <li><a href="admin_policy_types.php">Add/Edit available policy types.</a></li>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>