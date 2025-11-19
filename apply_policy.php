<?php
// Start the session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Redirect if not logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// Only customers can access this page
if ($_SESSION["role"] !== 'customer') {
    header("location: dashboard.php");
    exit;
}

// Include the database connection
require_once 'config/database.php';

// Initialize variables
$policy_type_id = $desired_duration_months = $additional_notes = "";
$policy_type_err = $desired_duration_err = $application_err = $application_success = "";

// Fetch policy types
$policy_types = [];
$sql_policy_types = "SELECT policy_type_id, name, description, min_duration_months, max_duration_months FROM policy_types ORDER BY name ASC";

if ($result = mysqli_query($link, $sql_policy_types)) {
    while ($row = mysqli_fetch_assoc($result)) {
        $policy_types[] = $row;
    }
    mysqli_free_result($result);
} else {
    $application_err = "Error fetching policy types.";
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION["user_id"];

    // Validate policy type
    if (empty(trim($_POST["policy_type_id"]))) {
        $policy_type_err = "Please select an insurance type.";
    } else {
        $policy_type_id = (int)trim($_POST["policy_type_id"]);
    }

    // Validate duration
    if (empty(trim($_POST["desired_duration_months"]))) {
        $desired_duration_err = "Please enter the duration.";
    } elseif (!filter_var(trim($_POST["desired_duration_months"]), FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]])) {
        $desired_duration_err = "Enter a valid positive number.";
    } else {
        $desired_duration_months = (int)trim($_POST["desired_duration_months"]);
    }

    // Notes
    $additional_notes = trim($_POST["additional_notes"]);

    // If no errors, insert into DB
    if (empty($policy_type_err) && empty($desired_duration_err)) {
        $sql_insert_app = "INSERT INTO applications (user_id, policy_type_id, desired_duration_months, additional_notes)
                           VALUES (?, ?, ?, ?)";

        if ($stmt = mysqli_prepare($link, $sql_insert_app)) {
            mysqli_stmt_bind_param($stmt, "iiis", $user_id, $policy_type_id, $desired_duration_months, $additional_notes);

            if (mysqli_stmt_execute($stmt)) {
                $application_success = "Your application has been submitted successfully!";
                $policy_type_id = $desired_duration_months = $additional_notes = "";
            } else {
                $application_err = "Something went wrong. Please try again.";
            }

            mysqli_stmt_close($stmt);
        }
    } else {
        $application_err = "Please correct the highlighted errors.";
    }
}

mysqli_close($link);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply for Policy</title>

    <style>
        body {
            background: #f5f7fb;
            margin: 0;
            padding: 0;
            font-family: "Segoe UI", Arial, sans-serif;
        }

        .page-container {
            max-width: 850px;
            margin: 40px auto;
            padding: 25px;
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.08);
        }

        h2 {
            text-align: center;
            margin-bottom: 15px;
            font-size: 26px;
            color: #333;
            font-weight: 600;
        }

        p.subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 25px;
            font-size: 15px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        label {
            font-weight: 500;
            color: #444;
            display: block;
            margin-bottom: 6px;
        }

        select, input, textarea {
            width: 100%;
            padding: 12px;
            border: 1.5px solid #ccc;
            border-radius: 8px;
            font-size: 15px;
            transition: .2s;
        }

        select:focus, input:focus, textarea:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37,99,235,0.15);
            outline: none;
        }

        .error-message {
            color: #d62828;
            font-size: 13px;
            margin-top: 4px;
        }

        .success-box, .error-box {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 18px;
            font-size: 14px;
        }

        .success-box {
            background: #d1f7d1;
            color: #127512;
        }

        .error-box {
            background: #fbe3e4;
            color: #9c1c1c;
        }

        .btn-primary {
            background: #2563eb;
            color: #fff;
            padding: 14px;
            width: 100%;
            font-size: 17px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: .2s;
        }

        .btn-primary:hover {
            background: #1e4fcc;
        }
    </style>
</head>

<body>

<?php include 'header.php'; ?>

<div class="page-container">
    <h2>Apply for New Insurance Policy</h2>
    <p class="subtitle">Choose an insurance type and duration to submit your application.</p>

    <?php if (!empty($application_success)): ?>
        <div class="success-box"><?= $application_success ?></div>
    <?php endif; ?>

    <?php if (!empty($application_err)): ?>
        <div class="error-box"><?= $application_err ?></div>
    <?php endif; ?>

    <form action="" method="POST">

        <div class="form-group">
            <label>Insurance Type:</label>
            <select name="policy_type_id">
                <option value="">-- Select Policy Type --</option>
                <?php foreach ($policy_types as $type): ?>
                    <option value="<?= $type['policy_type_id'] ?>"
                        <?= ($policy_type_id == $type['policy_type_id']) ? "selected" : "" ?>>
                        <?= htmlspecialchars($type['name']) ?> (Min: <?= $type['min_duration_months'] ?>, Max: <?= $type['max_duration_months'] ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <span class="error-message"><?= $policy_type_err ?></span>
        </div>

        <div class="form-group">
            <label>Duration (Months):</label>
            <input type="number" name="desired_duration_months" value="<?= htmlspecialchars($desired_duration_months) ?>" min="1">
            <span class="error-message"><?= $desired_duration_err ?></span>
        </div>

        <div class="form-group">
            <label>Additional Notes (Optional):</label>
            <textarea name="additional_notes" rows="4"><?= htmlspecialchars($additional_notes) ?></textarea>
        </div>

        <button type="submit" class="btn-primary">Submit Application</button>
    </form>
</div>

<?php include 'footer.php'; ?>

</body>
</html>
