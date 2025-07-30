<?php
// Include the database connection file
require_once 'config/database.php';

// Start a session to use session variables (e.g., for success messages, or later for user login)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Initialize variables for form data and error messages
$first_name = $last_name = $email = $password = $confirm_password = $phone_number = $address = "";
$first_name_err = $last_name_err = $email_err = $password_err = $confirm_password_err = "";
$registration_success = $registration_error = "";

// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. Validate First Name
    if (empty(trim($_POST["first_name"]))) {
        $first_name_err = "Please enter your first name.";
    } else {
        $first_name = trim($_POST["first_name"]);
    }

    // 2. Validate Last Name
    if (empty(trim($_POST["last_name"]))) {
        $last_name_err = "Please enter your last name.";
    } else {
        $last_name = trim($_POST["last_name"]);
    }

    // 3. Validate Email
    if (empty(trim($_POST["email"]))) {
        $email_err = "Please enter an email address.";
    } elseif (!filter_var(trim($_POST["email"]), FILTER_VALIDATE_EMAIL)) {
        $email_err = "Please enter a valid email format.";
    } else {
        $email = trim($_POST["email"]);

        // Check if email already exists in the database
        $sql = "SELECT user_id FROM users WHERE email = ?";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $param_email);
            $param_email = $email;

            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);
                if (mysqli_stmt_num_rows($stmt) == 1) {
                    $email_err = "This email is already registered.";
                }
            } else {
                $registration_error = "Oops! Something went wrong. Please try again later.";
            }
            mysqli_stmt_close($stmt);
        }
    }

    // 4. Validate Password
    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter a password.";
    } elseif (strlen(trim($_POST["password"])) < 6) {
        $password_err = "Password must have at least 6 characters.";
    } else {
        $password = trim($_POST["password"]);
    }

    // 5. Validate Confirm Password
    if (empty(trim($_POST["confirm_password"]))) {
        $confirm_password_err = "Please confirm password.";
    } else {
        $confirm_password = trim($_POST["confirm_password"]);
        if (empty($password_err) && ($password != $confirm_password)) {
            $confirm_password_err = "Password did not match.";
        }
    }

    // 6. Optional fields
    $phone_number = trim($_POST["phone_number"]);
    $address = trim($_POST["address"]);

    // Check input errors before inserting into database
    if (empty($first_name_err) && empty($last_name_err) && empty($email_err) && empty($password_err) && empty($confirm_password_err)) {

        // Prepare an insert statement
        $sql = "INSERT INTO users (first_name, last_name, email, password_hash, phone_number, address, role) VALUES (?, ?, ?, ?, ?, ?, ?)";

        if ($stmt = mysqli_prepare($link, $sql)) {
            // Bind variables to the prepared statement as parameters
            $param_password = password_hash($password, PASSWORD_DEFAULT); // Hash the password
            $param_role = 'customer'; // Default role for new registrations

            mysqli_stmt_bind_param($stmt, "sssssss", $first_name, $last_name, $email, $param_password, $phone_number, $address, $param_role);

            // Attempt to execute the prepared statement
            if (mysqli_stmt_execute($stmt)) {
                $registration_success = "Account created successfully! You can now <a href='login.php'>login</a>.";
                // Clear form fields after successful submission
                $first_name = $last_name = $email = $phone_number = $address = "";
            } else {
                $registration_error = "Something went wrong. Please try again later.";
            }

            // Close statement
            mysqli_stmt_close($stmt);
        }
    } else {
        $registration_error = "Please correct the errors in the form.";
    }

    // Close connection
    mysqli_close($link);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Online Insurance System</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }
        .register-container {
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 500px;
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
        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="password"],
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box; /* Ensures padding doesn't increase width */
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
        .login-link {
            text-align: center;
            margin-top: 20px;
            color: #666;
        }
        .login-link a {
            color: #007bff;
            text-decoration: none;
        }
        .login-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <h2>Customer Registration</h2>
        <p>Please fill this form to create an account.</p>

        <?php
        if (!empty($registration_success)) {
            echo '<div class="form-group success-message">' . $registration_success . '</div>';
        }
        if (!empty($registration_error)) {
            echo '<div class="form-group error-message">' . $registration_error . '</div>';
        }
        ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label for="first_name">First Name:</label>
                <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($first_name); ?>" required>
                <span class="error-message"><?php echo $first_name_err; ?></span>
            </div>
            <div class="form-group">
                <label for="last_name">Last Name:</label>
                <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($last_name); ?>" required>
                <span class="error-message"><?php echo $last_name_err; ?></span>
            </div>
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                <span class="error-message"><?php echo $email_err; ?></span>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
                <span class="error-message"><?php echo $password_err; ?></span>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm Password:</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
                <span class="error-message"><?php echo $confirm_password_err; ?></span>
            </div>
            <div class="form-group">
                <label for="phone_number">Phone Number (Optional):</label>
                <input type="text" id="phone_number" name="phone_number" value="<?php echo htmlspecialchars($phone_number); ?>">
            </div>
            <div class="form-group">
                <label for="address">Address (Optional):</label>
                <textarea id="address" name="address" rows="3"><?php echo htmlspecialchars($address); ?></textarea>
            </div>
            <div class="form-group">
                <input type="submit" class="btn-primary" value="Register">
            </div>
        </form>
        <div class="login-link">
            Already have an account? <a href="login.php">Login here</a>.
        </div>
    </div>
</body>
</html>