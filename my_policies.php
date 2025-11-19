<?php
// --------------------------------------
// ENABLE ERROR REPORTING (Remove in production)
// --------------------------------------
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// --------------------------------------
// SESSION + ACCESS CONTROL
// --------------------------------------
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

if ($_SESSION["role"] !== 'customer') {
    header("location: dashboard.php");
    exit;
}

// --------------------------------------
// DB CONNECTION
// --------------------------------------
require_once 'config/database.php';

$user_id = $_SESSION["user_id"];
$customer_applications = [];
$customer_policies = [];
$error_message = "";

// --------------------------------------
// FETCH APPLICATIONS
// --------------------------------------
$sql_applications = "SELECT
                        a.application_id,
                        pt.name AS policy_type_name,
                        a.desired_duration_months,
                        a.application_date,
                        asl.status_name
                     FROM
                        applications a
                     JOIN policy_types pt ON a.policy_type_id = pt.policy_type_id
                     JOIN application_status_lookup asl ON a.status_id = asl.status_id
                     WHERE a.user_id = ?
                     ORDER BY a.application_date DESC";

if ($stmt = mysqli_prepare($link, $sql_applications)) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($result)) {
            $customer_applications[] = $row;
        }
    }
    mysqli_stmt_close($stmt);
}

// --------------------------------------
// FETCH APPROVED POLICIES
// --------------------------------------
$sql_policies = "SELECT
                    p.policy_id,
                    p.policy_number,
                    pt.name AS policy_type_name,
                    p.start_date,
                    p.end_date,
                    p.premium_amount,
                    p.policy_status
                 FROM policies p
                 JOIN policy_types pt ON p.policy_type_id = pt.policy_type_id
                 WHERE p.user_id = ?
                 ORDER BY p.start_date DESC";

if ($stmt = mysqli_prepare($link, $sql_policies)) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($result)) {
            $customer_policies[] = $row;
        }
    }
    mysqli_stmt_close($stmt);
}

mysqli_close($link);
?>

<?php include 'header.php'; ?>

<!-- ====================== PAGE CONTENT ====================== -->

<style>
    body {
        background: #f5f7fb;
    }

    .page-container {
        max-width: 1100px;
        margin: 35px auto;
        padding: 25px;
        background: #fff;
        border-radius: 14px;
        box-shadow: 0 3px 15px rgba(0,0,0,0.08);
    }

    h2 {
        text-align: center;
        margin-bottom: 10px;
        font-size: 26px;
        color: #333;
        font-weight: 600;
    }

    .subtitle {
        text-align: center;
        color: #666;
        margin-bottom: 25px;
        font-size: 15px;
    }

    h3 {
        color: #2563eb;
        font-size: 20px;
        margin-top: 35px;
        border-left: 4px solid #2563eb;
        padding-left: 10px;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 15px;
        border-radius: 8px;
        overflow: hidden;
    }

    th {
        background: #eef2ff;
        font-weight: 600;
        padding: 12px;
        border-bottom: 1px solid #ddd;
        color: #333;
    }

    td {
        padding: 12px;
        border-bottom: 1px solid #eee;
        color: #444;
    }

    tr:nth-child(even) {
        background: #fafbff;
    }

    .no-records {
        text-align: center;
        background: #f8f9fc;
        padding: 20px;
        border-radius: 10px;
        color: #555;
        margin-top: 10px;
        border: 1px dashed #ccd4e0;
    }

    .error-message-display {
        background: #fee2e2;
        padding: 12px;
        color: #b91c1c;
        border-left: 4px solid #b91c1c;
        margin-bottom: 20px;
        border-radius: 8px;
    }
</style>

<div class="page-container">

    <h2>My Applications & Policies</h2>
    <p class="subtitle">Review your submitted applications and active insurance policies.</p>

    <?php if (!empty($error_message)): ?>
        <div class="error-message-display">
            <?php echo $error_message; ?>
        </div>
    <?php endif; ?>

    <!-- ================= APPLICATIONS ================= -->
    <h3>Applications</h3>

    <?php if (!empty($customer_applications)): ?>
        <table>
            <thead>
            <tr>
                <th>ID</th>
                <th>Policy Type</th>
                <th>Duration</th>
                <th>Date</th>
                <th>Status</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($customer_applications as $app): ?>
                <tr>
                    <td><?= htmlspecialchars($app['application_id']); ?></td>
                    <td><?= htmlspecialchars($app['policy_type_name']); ?></td>
                    <td><?= htmlspecialchars($app['desired_duration_months']); ?> months</td>
                    <td><?= htmlspecialchars($app['application_date']); ?></td>
                    <td><?= htmlspecialchars($app['status_name']); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="no-records">
            You have no applications yet. <a href="apply_policy.php">Apply for a policy</a>.
        </div>
    <?php endif; ?>


    <!-- ================= POLICIES ================= -->
    <h3>Approved Policies</h3>

    <?php if (!empty($customer_policies)): ?>
        <table>
            <thead>
            <tr>
                <th>ID</th>
                <th>Policy No.</th>
                <th>Type</th>
                <th>Start</th>
                <th>End</th>
                <th>Premium</th>
                <th>Status</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($customer_policies as $policy): ?>
                <tr>
                    <td><?= htmlspecialchars($policy['policy_id']); ?></td>
                    <td><?= htmlspecialchars($policy['policy_number']); ?></td>
                    <td><?= htmlspecialchars($policy['policy_type_name']); ?></td>
                    <td><?= htmlspecialchars($policy['start_date']); ?></td>
                    <td><?= htmlspecialchars($policy['end_date']); ?></td>
                    <td><?= htmlspecialchars(number_format($policy['premium_amount'], 2)); ?></td>
                    <td><?= htmlspecialchars($policy['policy_status']); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="no-records">
            You have no approved policies at the moment.
        </div>
    <?php endif; ?>

</div>

<?php include 'footer.php'; ?>
