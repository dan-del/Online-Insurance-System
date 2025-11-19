<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

$user_id = $_SESSION["user_id"];
$first_name = $_SESSION["first_name"];
$last_name = $_SESSION["last_name"];
$email = $_SESSION["email"];
$role = $_SESSION["role"];

require_once 'config/database.php';

$dashboard_data = [];
$most_recent_policy = null;
$error_message = "";

if ($role === 'customer') {

    $sql_customer_stats = "SELECT
                            (SELECT COUNT(*) FROM applications WHERE user_id = ? AND status_id = 1) AS pending_applications,
                            (SELECT COUNT(*) FROM policies WHERE user_id = ? AND policy_status = 'Active') AS active_policies";

    if ($stmt = mysqli_prepare($link, $sql_customer_stats)) {
        mysqli_stmt_bind_param($stmt, "ii", $user_id, $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $dashboard_data = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
    }

    $sql_recent_policy = "SELECT
                            p.policy_number,
                            pt.name AS policy_type_name,
                            p.premium_amount,
                            p.end_date
                          FROM policies p
                          JOIN policy_types pt ON p.policy_type_id = pt.policy_type_id
                          WHERE p.user_id = ? AND p.policy_status = 'Active'
                          ORDER BY p.issue_date DESC
                          LIMIT 1";

    if ($stmt = mysqli_prepare($link, $sql_recent_policy)) {
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if (mysqli_num_rows($result) > 0) {
            $most_recent_policy = mysqli_fetch_assoc($result);
        }
        mysqli_stmt_close($stmt);
    }
}

elseif ($role === 'company_official' || $role === 'administrator') {

    $sql_admin_stats = "SELECT
                           (SELECT COUNT(*) FROM applications WHERE status_id = 1) AS total_pending_applications,
                           (SELECT COUNT(*) FROM users WHERE role = 'customer') AS total_customers,
                           (SELECT COUNT(*) FROM policies) AS total_policies";

    $result = mysqli_query($link, $sql_admin_stats);
    $dashboard_data = mysqli_fetch_assoc($result);
}

mysqli_close($link);
?>

<?php include 'header.php'; ?>

<style>
    body {
        background-color: #eef1f5;
        margin: 0;
        padding: 0;
        font-family: "Segoe UI", Arial, sans-serif;
    }

    .container {
        max-width: 1100px;
        margin: 40px auto;
        background: #ffffff;
        padding: 40px 50px;
        border-radius: 18px;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
        animation: fadeIn 0.4s ease-in-out;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .welcome-message h2 {
        font-size: 32px;
        color: #222;
        font-weight: 700;
    }

    .welcome-message p {
        font-size: 18px;
        margin-top: -5px;
        color: #444;
    }

    .dashboard-cards {
        display: flex;
        justify-content: center;
        gap: 25px;
        margin: 40px 0;
        flex-wrap: wrap;
    }

    .card {
        width: 260px;
        padding: 25px;
        border-radius: 16px;
        background: #ffffff;
        border: 1px solid #e6e6e6;
        text-align: center;
        transition: 0.25s ease-in-out;
        box-shadow: 0px 3px 8px rgba(0,0,0,0.05);
    }

    .card:hover {
        transform: translateY(-6px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.12);
        border-color: #007bff;
    }

    .card-title {
        font-size: 16px;
        color: #555;
        margin-bottom: 8px;
        font-weight: 600;
    }

    .card-number {
        font-size: 45px;
        font-weight: 700;
        color: #007bff;
    }

    .section-title {
        font-size: 22px;
        font-weight: 700;
        color: #007bff;
        border-bottom: 3px solid #007bff;
        display: inline-block;
        margin-bottom: 15px;
        padding-bottom: 4px;
    }

    .info-card {
        background: #f9fbff;
        padding: 20px;
        border-radius: 14px;
        border: 1px solid #d6e4ff;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }

    .info-card p {
        font-size: 16px;
        margin: 6px 0;
        color: #222;
    }

    ul {
        font-size: 16px;
    }

    ul li a {
        color: #007bff;
        font-weight: 600;
    }

    ul li a:hover {
        text-decoration: underline;
    }
</style>


<div class="container">
    <div class="welcome-message" style="text-align:center;">
        <h2>Welcome, <?php echo htmlspecialchars($first_name); ?> <?php echo htmlspecialchars($last_name); ?>!</h2>
        <p>You are logged in as a <strong><?php echo ucfirst($role); ?></strong>.</p>
    </div>

    <?php if ($role === 'customer'): ?>
        <div class="dashboard-cards">
            <div class="card">
                <p class="card-title">Pending Applications</p>
                <p class="card-number"><?php echo $dashboard_data['pending_applications'] ?? 0; ?></p>
            </div>
            <div class="card">
                <p class="card-title">Active Policies</p>
                <p class="card-number"><?php echo $dashboard_data['active_policies'] ?? 0; ?></p>
            </div>
        </div>

        <?php if ($most_recent_policy): ?>
            <h3 class="section-title">Your Most Recent Active Policy</h3>
            <div class="info-card">
                <p><strong>Policy Number:</strong> <?= $most_recent_policy['policy_number']; ?></p>
                <p><strong>Policy Type:</strong> <?= $most_recent_policy['policy_type_name']; ?></p>
                <p><strong>Premium:</strong> KES <?= number_format($most_recent_policy['premium_amount'], 2); ?></p>
                <p><strong>Expires On:</strong> <?= $most_recent_policy['end_date']; ?></p>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <div class="dashboard-cards">
            <div class="card">
                <p class="card-title">Pending Applications</p>
                <p class="card-number"><?= $dashboard_data['total_pending_applications']; ?></p>
            </div>
            <div class="card">
                <p class="card-title">Total Customers</p>
                <p class="card-number"><?= $dashboard_data['total_customers']; ?></p>
            </div>
            <div class="card">
                <p class="card-title">Total Policies</p>
                <p class="card-number"><?= $dashboard_data['total_policies']; ?></p>
            </div>
        </div>
    <?php endif; ?>

    <h3 class="section-title">Quick Links</h3>
    <ul>
        <?php if ($role === 'customer'): ?>
            <li><a href="apply_policy.php">Submit a new insurance application.</a></li>
            <li><a href="my_policies.php">View your existing policies and their status.</a></li>
            <li><a href="make_payment.php">Find instructions on how to make premium payments.</a></li>
        <?php else: ?>
            <li><a href="view_applications.php">Review pending applications.</a></li>
            <li><a href="admin_policy_types.php">Manage policy types.</a></li>
        <?php endif; ?>
    </ul>
</div>

<?php include 'footer.php'; ?>
