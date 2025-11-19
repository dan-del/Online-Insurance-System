<?php 
// 1. CRITICAL: Start the session to access $_SESSION["role"]
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
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
        /* 2. CHATBOT CSS - MOVED TO HEAD SECTION */
        #chat-button {
            position: fixed;
            bottom: 20px;
            right: 20px;
            padding: 15px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 30px;
            cursor: pointer;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            z-index: 1000;
        }
        #chat-window {
            position: fixed;
            bottom: 80px;
            right: 20px;
            width: 300px;
            height: 400px;
            background-color: #f9f9f9;
            border: 1px solid #ccc;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            display: flex;
            flex-direction: column;
            z-index: 999;
        }
        #chat-header {
            padding: 10px;
            background-color: #007bff;
            color: white;
            font-weight: bold;
            border-top-left-radius: 8px;
            border-top-right-radius: 8px;
            cursor: pointer;
        }
        #close-chat {
            float: right;
            cursor: pointer;
        }
        #chat-body {
            flex-grow: 1;
            padding: 10px;
            overflow-y: auto;
            border-bottom: 1px solid #eee;
        }
        #chat-input {
            padding: 10px;
            border: none;
            border-top: 1px solid #ccc;
            width: 100%;
            box-sizing: border-box;
        }
        .message {
            padding: 8px;
            margin-bottom: 8px;
            border-radius: 5px;
            max-width: 80%;
        }
        .user {
            background-color: #dcf8c6;
            margin-left: auto;
            text-align: right;
        }
        .bot {
            background-color: #e5e5ea;
            margin-right: auto;
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
                    <?php if (isset($_SESSION["role"]) && $_SESSION["role"] === 'customer'): ?>
                        <li class="nav-item"><a class="nav-link" href="apply_policy.php">Apply for Policy</a></li>
                        <li class="nav-item"><a class="nav-link" href="my_policies.php">My Policies</a></li>
                        <li class="nav-item"><a class="nav-link" href="make_payment.php">Make Payment</a></li>
                    <?php elseif (isset($_SESSION["role"]) && $_SESSION["role"] === 'company_official'): ?>
                        <li class="nav-item"><a class="nav-link" href="view_applications.php">View Applications</a></li>
                        <li class="nav-item"><a class="nav-link" href="admin_policy_types.php">Manage Policy Types</a></li>
                    <?php elseif (isset($_SESSION["role"]) && $_SESSION["role"] === 'administrator'): ?>
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