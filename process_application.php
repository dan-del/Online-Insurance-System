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
    header("location: dashboard.php");
    exit;
}

// Include the database connection file
require_once 'config/database.php';

$application_id = null;
$application = null;
$message = "";
$message_type = ""; // 'success' or 'error'

// Check if an application ID is provided in the URL
if (isset($_GET["id"]) && !empty(trim($_GET["id"]))) {
    $application_id = trim($_GET["id"]);

    // Fetch the application details from the database
    $sql = "SELECT
                a.application_id,
                a.user_id,
                u.first_name,
                u.last_name,
                u.email,
                u.phone_number,
                u.address,
                pt.policy_type_id,
                pt.name AS policy_type_name,
                pt.base_premium,
                a.desired_duration_months,
                a.application_date,
                asl.status_name,
                a.additional_notes,
                a.admin_notes
            FROM
                applications a
            JOIN
                users u ON a.user_id = u.user_id
            JOIN
                policy_types pt ON a.policy_type_id = pt.policy_type_id
            JOIN
                application_status_lookup asl ON a.status_id = asl.status_id
            WHERE
                a.application_id = ?";

    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $application_id);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            if (mysqli_num_rows($result) == 1) {
                $application = mysqli_fetch_assoc($result);
            } else {
                $message = "No application found with that ID.";
                $message_type = "error";
            }
        } else {
            $message = "Error fetching application details: " . mysqli_error($link);
            $message_type = "error";
        }
        mysqli_stmt_close($stmt);
    } else {
        $message = "Database preparation error: " . mysqli_error($link);
        $message_type = "error";
    }

} else {
    // If no ID is provided, redirect back to the view applications page
    header("location: view_applications.php");
    exit;
}

// --- Logic for handling approval/rejection POST request ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $action = $_POST['action'];
    $admin_notes = trim($_POST['admin_notes']);

    if ($action === 'approve') {
        // --- 1. Update application status to 'Approved' ---
        // Get the status ID for 'Approved' (we know it's 2 from our initial INSERT)
        // A more robust way would be to query for it, but for simplicity, we'll use 2.
        $status_id_approved = 2;
        $sql_update_app = "UPDATE applications SET status_id = ?, admin_notes = ?, processed_by_user_id = ?, processed_at = NOW() WHERE application_id = ?";
        if ($stmt_update = mysqli_prepare($link, $sql_update_app)) {
            $processed_by_user_id = $_SESSION['user_id'];
            mysqli_stmt_bind_param($stmt_update, "isii", $status_id_approved, $admin_notes, $processed_by_user_id, $application_id);
            mysqli_stmt_execute($stmt_update);
            mysqli_stmt_close($stmt_update);

            // --- 2. Create a new policy record in the 'policies' table ---
            // Calculate premium amount (example: base premium * duration)
            $premium_amount = $application['base_premium'] * $application['desired_duration_months'];
            // Generate a simple policy number (e.g., APP-ID-YEAR)
            $policy_number = "APP-" . $application['application_id'] . "-" . date('Y');
            // Calculate start and end dates
            $start_date = date('Y-m-d');
            $end_date = date('Y-m-d', strtotime("+$application[desired_duration_months] months"));

            $sql_insert_policy = "INSERT INTO policies (application_id, user_id, policy_type_id, policy_number, start_date, end_date, premium_amount) VALUES (?, ?, ?, ?, ?, ?, ?)";
            if ($stmt_policy = mysqli_prepare($link, $sql_insert_policy)) {
                mysqli_stmt_bind_param($stmt_policy, "iiisssd", $application['application_id'], $application['user_id'], $application['policy_type_id'], $policy_number, $start_date, $end_date, $premium_amount);
                if (mysqli_stmt_execute($stmt_policy)) {
                    $message = "Application successfully APPROVED and new policy created!";
                    $message_type = "success";
                    // Reload the page to show updated status
                    header("location: process_application.php?id=" . $application_id . "&msg_type=success&msg=" . urlencode($message));
                    exit;
                } else {
                    $message = "Application approved but failed to create a new policy: " . mysqli_error($link);
                    $message_type = "error";
                }
                mysqli_stmt_close($stmt_policy);
            }
        }
    } elseif ($action === 'reject') {
        // --- Update application status to 'Rejected' ---
        // Get the status ID for 'Rejected' (we know it's 3 from our initial INSERT)
        $status_id_rejected = 3;
        $sql_update_app = "UPDATE applications SET status_id = ?, admin_notes = ?, processed_by_user_id = ?, processed_at = NOW() WHERE application_id = ?";
        if ($stmt_update = mysqli_prepare($link, $sql_update_app)) {
            $processed_by_user_id = $_SESSION['user_id'];
            mysqli_stmt_bind_param($stmt_update, "isii", $status_id_rejected, $admin_notes, $processed_by_user_id, $application_id);
            if (mysqli_stmt_execute($stmt_update)) {
                $message = "Application successfully REJECTED.";
                $message_type = "success";
                // Reload the page to show updated status
                header("location: process_application.php?id=" . $application_id . "&msg_type=success&msg=" . urlencode($message));
                exit;
            } else {
                $message = "Failed to reject application: " . mysqli_error($link);
                $message_type = "error";
            }
            mysqli_stmt_close($stmt_update);
        }
    }
}

// Check for messages from the URL after processing
if (isset($_GET['msg']) && isset($_GET['msg_type'])) {
    $message = htmlspecialchars($_GET['msg']);
    $message_type = htmlspecialchars($_GET['msg_type']);
}

// Re-fetch application details if a POST request was just processed
if ($application && ($application['status_name'] === 'Pending' || $application['status_name'] === 'Under Review')) {
    // Re-fetch logic can be here or by a page reload, which is what we are doing
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Process Application - Online Insurance System</title>
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
            max-width: 900px;
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
        .detail-group {
            margin-bottom: 20px;
            border-bottom: 1px solid #eee;
            padding-bottom: 20px;
        }
        .detail-group:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }
        .detail-row {
            display: flex;
            margin-bottom: 10px;
            flex-wrap: wrap;
        }
        .detail-row .label {
            font-weight: bold;
            color: #555;
            flex-basis: 30%;
        }
        .detail-row .value {
            flex-basis: 70%;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 5px;
            font-weight: bold;
            color: #fff;
        }
        .status-pending { background-color: #ffc107; color: #333; }
        .status-approved { background-color: #28a745; }
        .status-rejected { background-color: #dc3545; }
        .status-under_review { background-color: #17a2b8; }

        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #555;
        }
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 16px;
        }
        .btn-group {
            display: flex;
            gap: 10px;
            justify-content: flex-start;
        }
        .btn {
            padding: 12px 20px;
            font-size: 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.2s ease;
        }
        .btn-success {
            background-color: #28a745;
            color: #fff;
        }
        .btn-success:hover {
            background-color: #218838;
        }
        .btn-danger {
            background-color: #dc3545;
            color: #fff;
        }
        .btn-danger:hover {
            background-color: #c82333;
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
        <?php if (!empty($message)): ?>
            <div class="message-box <?php echo $message_type; ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <h2>Process Application #<?php echo htmlspecialchars($application['application_id'] ?? 'N/A'); ?></h2>

        <?php if ($application): ?>
            <div class="detail-group">
                <h3>Customer Details</h3>
                <div class="detail-row"><div class="label">Name:</div><div class="value"><?php echo htmlspecialchars($application['first_name'] . ' ' . $application['last_name']); ?></div></div>
                <div class="detail-row"><div class="label">Email:</div><div class="value"><?php echo htmlspecialchars($application['email']); ?></div></div>
                <div class="detail-row"><div class="label">Phone:</div><div class="value"><?php echo htmlspecialchars($application['phone_number'] ?? 'N/A'); ?></div></div>
                <div class="detail-row"><div class="label">Address:</div><div class="value"><?php echo htmlspecialchars($application['address'] ?? 'N/A'); ?></div></div>
            </div>

            <div class="detail-group">
                <h3>Application Details</h3>
                <div class="detail-row"><div class="label">Policy Type:</div><div class="value"><?php echo htmlspecialchars($application['policy_type_name']); ?></div></div>
                <div class="detail-row"><div class="label">Base Premium:</div><div class="value">KES <?php echo htmlspecialchars(number_format($application['base_premium'], 2)); ?></div></div>
                <div class="detail-row"><div class="label">Desired Duration:</div><div class="value"><?php echo htmlspecialchars($application['desired_duration_months']); ?> months</div></div>
                <div class="detail-row"><div class="label">Application Date:</div><div class="value"><?php echo htmlspecialchars($application['application_date']); ?></div></div>
                <div class="detail-row"><div class="label">Status:</div><div class="value">
                    <span class="status-badge status-<?php echo strtolower(str_replace(' ', '_', htmlspecialchars($application['status_name']))); ?>">
                        <?php echo htmlspecialchars($application['status_name']); ?>
                    </span>
                </div></div>
                <div class="detail-row"><div class="label">Customer Notes:</div><div class="value"><?php echo htmlspecialchars($application['additional_notes'] ?? 'N/A'); ?></div></div>
            </div>

            <?php if ($application['status_name'] === 'Pending' || $application['status_name'] === 'Under Review'): ?>
                <div class="processing-form">
                    <h3>Process Application</h3>
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?id=' . htmlspecialchars($application_id); ?>" method="post">
                        <div class="form-group">
                            <label for="admin_notes">Admin/Official Notes:</label>
                            <textarea id="admin_notes" name="admin_notes" rows="4"></textarea>
                        </div>
                        <div class="btn-group">
                            <button type="submit" name="action" value="approve" class="btn btn-success">Approve</button>
                            <button type="submit" name="action" value="reject" class="btn btn-danger">Reject</button>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <div class="detail-group">
                    <h3>Processing History</h3>
                    <div class="detail-row"><div class="label">Official/Admin Notes:</div><div class="value"><?php echo htmlspecialchars($application['admin_notes'] ?? 'N/A'); ?></div></div>
                    <div class="detail-row"><div class="label">Processed By:</div><div class="value">#<?php echo htmlspecialchars($application['processed_by_user_id'] ?? 'N/A'); ?></div></div>
                    <div class="detail-row"><div class="label">Processed On:</div><div class="value"><?php echo htmlspecialchars($application['processed_at'] ?? 'N/A'); ?></div></div>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <p style="text-align:center;">Application details not found.</p>
        <?php endif; ?>

    </div>
</body>
</html>