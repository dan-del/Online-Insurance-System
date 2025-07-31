<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Insurance System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .main-content {
            padding-top: 20px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">Online Insurance System</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="dashboard.php">Dashboard</a>
                    </li>
                    <?php if ($_SESSION["role"] === 'customer'): ?>
                        <li class="nav-item"><a class="nav-link" href="apply_policy.php">Apply for Policy</a></li>
                        <li class="nav-item"><a class="nav-link" href="my_policies.php">My Policies</a></li>
                        <li class="nav-item"><a class="nav-link" href="make_payment.php">Make Payment</a></li>
                    <?php elseif ($_SESSION["role"] === 'company_official'): ?>
                        <li class="nav-item"><a class="nav-link" href="view_applications.php">View Applications</a></li>
                        <li class="nav-item"><a class="nav-link" href="admin_policy_types.php">Manage Policy Types</a></li>
                    <?php elseif ($_SESSION["role"] === 'administrator'): ?>
                        <li class="nav-item"><a class="nav-link" href="view_applications.php">Manage Applications</a></li>
                        <li class="nav-item"><a class="nav-link" href="admin_users.php">Manage Users</a></li>
                        <li class="nav-item"><a class="nav-link" href="admin_policy_types.php">Manage Policy Types</a></li>
                    <?php endif; ?>
                </ul>
                <a href="logout.php" class="btn btn-danger">Logout</a>
            </div>
        </div>
    </nav>
    <div class="container main-content">