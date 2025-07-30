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
    // For now, redirect unauthorized roles back to dashboard or an access denied page
    header("location: dashboard.php");
    exit;
}

// Include the database connection file
require_once 'config/database.php';

// Initialize variables for form feedback
$policy_type_id = $desired_duration_months = $additional_notes = "";
$policy_type_err = $desired_duration_err = $application_err = $application_success = "";

// Fetch available policy types from the database
$policy_types = [];
$sql_policy_types = "SELECT policy_type_id, name, description, min_duration_months, max_duration_months FROM policy_types ORDER BY name ASC";
if ($result = mysqli_query($link, $sql_policy_types)) {
    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $policy_types[] = $row;
        }
        mysqli_free_result($result);
    } else {
        $application_err = "No insurance policy types available. Please contact support.";
    }
} else {
    $application_err = "Error fetching policy types: " . mysqli_error($link);
}


// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION["user_id"]; // Get current user's ID from session

    // 1. Validate Policy Type
    if (empty(trim($_POST["policy_type_id"]))) {
        $policy_type_err = "Please select an insurance type.";
    } else {
        $policy_type_id = (int)trim($_POST["policy_type_id"]);
        // Optional: Verify if policy_type_id exists in fetched policy_types
        $found_policy_type = false;
        foreach ($policy_types as $pt) {
            if ($pt['policy_type_id'] == $policy_type_id) {
                $found_policy_type = true;
                break;
            }
        }
        if (!$found_policy_type) {
            $policy_type_err = "Invalid insurance type selected.";
        }
    }

    // 2. Validate Desired Duration
    if (empty(trim($_POST["desired_duration_months"]))) {
        $desired_duration_err = "Please enter the desired duration.";
    } elseif (!filter_var(trim($_POST["desired_duration_months"]), FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]])) {
        $desired_duration_err = "Duration must be a positive whole number.";
    } else {
        $desired_duration_months = (int)trim($_POST["desired_duration_months"]);
        // Optional: Check if duration is within min/max allowed for selected policy type
        if (!empty($policy_type_id) && empty($policy_type_err)) {
            $selected_policy_meta = null;
            foreach ($policy_types as $pt) {
                if ($pt['policy_type_id'] === $policy_type_id) {
                    $selected_policy_meta = $pt;
                    break;
                }
            }
            if ($selected_policy_meta) {
                if ($desired_duration_months < $selected_policy_meta['min_duration_months'] || $desired_duration_months > $selected_policy_meta['max_duration_months']) {
                    $desired_duration_err = "Duration must be between " . $selected_policy_meta['min_duration_months'] . " and " . $selected_policy_meta['max_duration_months'] . " months.";
                }
            }
        }
    }

    // 3. Optional: Additional Notes
    $additional_notes = trim($_POST["additional_notes"]);

    // Check input errors before inserting into database
    if (empty($policy_type_err) && empty($desired_duration_err)) {
        // Prepare an insert statement for applications
        $sql_insert_app = "INSERT INTO applications (user_id, policy_type_id, desired_duration_months, additional_notes) VALUES (?, ?, ?, ?)";

        if ($stmt = mysqli_prepare($link, $sql_insert_app)) {
            mysqli_stmt_bind_param($stmt, "iiis", $user_id, $policy_type_id, $desired_duration_months, $additional_notes);

            // Attempt to execute the prepared statement
            if (mysqli_stmt_execute($stmt)) {
                $application_success = "Your insurance application has been submitted successfully! We will review it shortly.";
                // Clear form fields after successful submission
                $policy_type_id = $desired_duration_months = $additional_notes = "";
            } else {
                $application_error = "Something went wrong submitting your application. Please try again later. " . mysqli_error($link);
            }

            // Close statement
            mysqli_stmt_close($stmt);
        } else {
            $application_error = "Database preparation error: " . mysqli_error($link);
        }
    } else {
        $application_error = "Please correct the errors in the form.";
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
    <title>Apply for Policy - Online Insurance System</title>
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
            max-width: 800px;
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
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #555;
        }
        .form-group select,
        .form-group input[type="number"],
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 16px;
        }
        .form-group .error-message {
            color: #d9534f;
            font-size: 0.9em;
            margin-top: 5px;
            display: block;
        }
        .form-group .success-message {
            color: #5cb85c;
            font-size: 0.9em;
            margin-top: 5px;
            display: block;
        }
        .btn-primary {
            width: 100%;
            padding: 12px;
            background-color: #007bff;
            color: #fff;
            border: none;
            border-radius: 4px;
            font-size: 18px;
            cursor: pointer;
            transition: background-color 0.2s ease;
        }
        .btn-primary:hover {
            background-color: #0056b3;
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
        <h2>Apply for New Insurance Policy</h2>
        <p>Choose an insurance type and desired duration to submit your application.</p>

        <?php
        if (!empty($application_success)) {
            echo '<div class="form-group success-message">' . $application_success . '</div>';
        }
        if (!empty($application_err)) { // Use general application_err for overall form errors/no policy types
            echo '<div class="form-group error-message">' . $application_err . '</div>';
        }
        ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label for="policy_type_id">Insurance Type:</label>
                <select id="policy_type_id" name="policy_type_id" required>
                    <option value="">-- Select Policy Type --</option>
                    <?php if (!empty($policy_types)): ?>
                        <?php foreach ($policy_types as $type): ?>
                            <option value="<?php echo htmlspecialchars($type['policy_type_id']); ?>"
                                <?php echo ($policy_type_id == $type['policy_type_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($type['name']); ?> (Min: <?php echo htmlspecialchars($type['min_duration_months']); ?> months, Max: <?php echo htmlspecialchars($type['max_duration_months']); ?> months)
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
                <span class="error-message"><?php echo $policy_type_err; ?></span>
            </div>
            <div class="form-group">
                <label for="desired_duration_months">Desired Duration (in Months):</label>
                <input type="number" id="desired_duration_months" name="desired_duration_months" min="1" value="<?php echo htmlspecialchars($desired_duration_months); ?>" required>
                <span class="error-message"><?php echo $desired_duration_err; ?></span>
            </div>
            <div class="form-group">
                <label for="additional_notes">Additional Notes (Optional):</label>
                <textarea id="additional_notes" name="additional_notes" rows="4"><?php echo htmlspecialchars($additional_notes); ?></textarea>
            </div>
            <div class="form-group">
                <input type="submit" class="btn-primary" value="Submit Application">
            </div>
        </form>
    </div>
</body>
</html>