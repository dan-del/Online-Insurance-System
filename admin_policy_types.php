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

$policy_types = [];
$error_message = "";
$success_message = "";

// Initialize variables for the Add Policy form
$name = $description = $min_duration = $max_duration = $base_premium = "";
$name_err = $min_duration_err = $max_duration_err = $base_premium_err = "";

// --- Processing form data when a new policy type is added ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_policy_type'])) {
    // 1. Validate form inputs
    if (empty(trim($_POST["name"]))) {
        $name_err = "Please enter a policy name.";
    } else {
        $name = trim($_POST["name"]);
    }

    if (empty(trim($_POST["min_duration_months"]))) {
        $min_duration_err = "Please enter a minimum duration.";
    } else {
        $min_duration = (int)trim($_POST["min_duration_months"]);
        if ($min_duration < 1) {
            $min_duration_err = "Minimum duration must be at least 1 month.";
        }
    }

    if (empty(trim($_POST["max_duration_months"]))) {
        $max_duration_err = "Please enter a maximum duration.";
    } else {
        $max_duration = (int)trim($_POST["max_duration_months"]);
        if ($max_duration < $min_duration) {
            $max_duration_err = "Maximum duration must be greater than or equal to minimum duration.";
        }
    }
    
    if (empty(trim($_POST["base_premium"]))) {
        $base_premium_err = "Please enter a base premium.";
    } else {
        $base_premium = (float)trim($_POST["base_premium"]);
        if ($base_premium < 0) {
            $base_premium_err = "Base premium cannot be negative.";
        }
    }
    
    $description = trim($_POST["description"]);

    // Check input errors before inserting into database
    if (empty($name_err) && empty($min_duration_err) && empty($max_duration_err) && empty($base_premium_err)) {
        $sql_insert = "INSERT INTO policy_types (name, description, min_duration_months, max_duration_months, base_premium) VALUES (?, ?, ?, ?, ?)";
        
        if ($stmt = mysqli_prepare($link, $sql_insert)) {
            mysqli_stmt_bind_param($stmt, "ssiid", $name, $description, $min_duration, $max_duration, $base_premium);
            
            if (mysqli_stmt_execute($stmt)) {
                $success_message = "New policy type added successfully!";
                // Clear form fields after success
                $name = $description = $min_duration = $max_duration = $base_premium = "";
            } else {
                // Check if the error is due to a duplicate unique name
                if (mysqli_errno($link) == 1062) {
                    $error_message = "A policy type with this name already exists.";
                } else {
                    $error_message = "Error adding policy type: " . mysqli_error($link);
                }
            }
            mysqli_stmt_close($stmt);
        } else {
            $error_message = "Database preparation error: " . mysqli_error($link);
        }
    } else {
        $error_message = "Please correct the form errors.";
    }
}

// --- Fetch all policy types for display ---
$sql_policy_types = "SELECT * FROM policy_types ORDER BY name ASC";
if ($result = mysqli_query($link, $sql_policy_types)) {
    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $policy_types[] = $row;
        }
        mysqli_free_result($result);
    }
} else {
    $error_message .= " Error fetching existing policy types: " . mysqli_error($link);
}

// Close database connection
mysqli_close($link);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Policy Types - Online Insurance System</title>
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
        .form-container {
            padding: 20px;
            background-color: #f8f9fa;
            border: 1px solid #e2e6ea;
            border-radius: 6px;
            margin-bottom: 30px;
        }
        .form-container .form-group {
            margin-bottom: 15px;
        }
        .form-container label {
            display: block;
            margin-bottom: 5px;
            color: #555;
        }
        .form-container input[type="text"],
        .form-container input[type="number"],
        .form-container textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 16px;
        }
        .form-container .error-message {
            color: #d9534f;
            font-size: 0.9em;
            margin-top: 5px;
            display: block;
        }
        .form-container .btn-primary {
            width: auto;
            padding: 10px 20px;
            background-color: #007bff;
            color: #fff;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.2s ease;
        }
        .form-container .btn-primary:hover {
            background-color: #0056b3;
        }
        .table-responsive {
            overflow-x: auto;
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
        <h2>Manage Insurance Policy Types</h2>

        <?php if (!empty($success_message)): ?>
            <div class="message-box success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        <?php if (!empty($error_message)): ?>
            <div class="message-box error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <div class="form-container">
            <h3>Add New Policy Type</h3>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <input type="hidden" name="add_policy_type" value="1">
                <div class="form-group">
                    <label for="name">Policy Name:</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
                    <span class="error-message"><?php echo $name_err; ?></span>
                </div>
                <div class="form-group">
                    <label for="description">Description:</label>
                    <textarea id="description" name="description" rows="3"><?php echo htmlspecialchars($description); ?></textarea>
                </div>
                <div class="form-group">
                    <label for="min_duration_months">Minimum Duration (in Months):</label>
                    <input type="number" id="min_duration_months" name="min_duration_months" value="<?php echo htmlspecialchars($min_duration); ?>" min="1" required>
                    <span class="error-message"><?php echo $min_duration_err; ?></span>
                </div>
                <div class="form-group">
                    <label for="max_duration_months">Maximum Duration (in Months):</label>
                    <input type="number" id="max_duration_months" name="max_duration_months" value="<?php echo htmlspecialchars($max_duration); ?>" min="1" required>
                    <span class="error-message"><?php echo $max_duration_err; ?></span>
                </div>
                <div class="form-group">
                    <label for="base_premium">Base Premium (KES):</label>
                    <input type="number" id="base_premium" name="base_premium" step="0.01" value="<?php echo htmlspecialchars($base_premium); ?>" min="0" required>
                    <span class="error-message"><?php echo $base_premium_err; ?></span>
                </div>
                <div class="form-group">
                    <input type="submit" class="btn-primary" value="Add Policy Type">
                </div>
            </form>
        </div>

        <h3>Existing Policy Types</h3>
        <div class="table-responsive">
            <?php if (!empty($policy_types)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Duration (Min-Max)</th>
                            <th>Base Premium (KES)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($policy_types as $type): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($type['policy_type_id']); ?></td>
                                <td><?php echo htmlspecialchars($type['name']); ?></td>
                                <td><?php echo htmlspecialchars($type['description']); ?></td>
                                <td><?php echo htmlspecialchars($type['min_duration_months'] . ' - ' . $type['max_duration_months']); ?> months</td>
                                <td><?php echo htmlspecialchars(number_format($type['base_premium'], 2)); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-records">No policy types found. Please add a new one.</div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>