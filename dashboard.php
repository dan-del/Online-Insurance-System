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

// Include database connection (if needed for direct queries on this page, though often handled by separate functions/classes)
require_once 'config/database.php';
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
            <?php if ($role === 'customer'): ?>
                <a href="apply_policy.php">Apply for Policy</a>
                <a href="my_policies.php">My Policies</a>
                <a href="make_payment.php">Make Payment</a>
            <?php elseif ($role === 'company_official'): ?>
                <a href="view_applications.php">View Applications</a>
                <a href="manage_policies.php">Manage Policies</a>
                <a href="create_policy_type.php">New Policy Type</a>
            <?php elseif ($role === 'administrator'): ?>
                <a href="admin_users.php">Manage Users</a>
                <a href="admin_applications.php">Manage Applications</a>
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

        <div class="user-info">
            <h3 class="section-title">Your Account Details</h3>
            <div class="info-card">
                <p><strong>User ID:</strong> <?php echo htmlspecialchars($user_id); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($email); ?></p>
                <p><strong>Role:</strong> <?php echo htmlspecialchars(ucfirst($role)); ?></p>
                </div>
        </div>

        <div class="role-specific-content">
            <?php if ($role === 'customer'): ?>
                <h3 class="section-title">Customer Dashboard</h3>
                <p>Here you will find information relevant to your insurance policies and applications.</p>
                <ul>
                    <li><a href="apply_policy.php">Submit a new insurance application.</a></li>
                    <li><a href="my_policies.php">View your existing policies and their status.</a></li>
                    <li><a href="make_payment.php">Find instructions on how to make premium payments.</a></li>
                    </ul>
            <?php elseif ($role === 'company_official'): ?>
                <h3 class="section-title">Company Official Dashboard</h3>
                <p>Manage insurance applications and existing policies.</p>
                <ul>
                    <li><a href="view_applications.php">Review new and pending insurance applications.</a></li>
                    <li><a href="manage_policies.php">Manage existing customer policies (renewals, cancellations, etc.).</a></li>
                    <li><a href="create_policy_type.php">Add/Edit policy types and schemes.</a></li>
                </ul>
            <?php elseif ($role === 'administrator'): ?>
                <h3 class="section-title">Administrator Dashboard</h3>
                <p>Full control over users, applications, and system settings.</p>
                <ul>
                    <li><a href="admin_users.php">Manage all user accounts (customers, officials, admins).</a></li>
                    <li><a href="admin_applications.php">Oversee all insurance applications.</a></li>
                    <li><a href="admin_policy_types.php">Manage available policy types and their configurations.</a></li>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>