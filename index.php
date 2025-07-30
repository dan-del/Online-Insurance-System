<?php
// Include the database connection file
require_once 'config/database.php';

// (Optional) Test query to ensure data can be fetched
// $sql = "SELECT COUNT(*) AS total_users FROM users";
// $result = mysqli_query($link, $sql);
// if ($result) {
//     $row = mysqli_fetch_assoc($result);
//     echo "<p>Total users in database: " . $row['total_users'] . "</p>";
// } else {
//     echo "<p>Error fetching user count: " . mysqli_error($link) . "</p>";
// }

// Your existing HTML code starts here:
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Insurance System</title>
    <style>
        /* ... your existing CSS ... */
    </style>
</head>
<body>
    <div class="container">
        <h1>Welcome to the Online Insurance System!</h1>
        <p>Your development environment is set up and working.</p>
        <?php
            echo "<p>This is a PHP test: Current date is " . date("Y-m-d") . "</p>";
        ?>
    </div>
</body>
</html>