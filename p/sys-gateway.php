<?php
session_start();

// UBAH PASSWORD DI SINI BRO
$admin_password = 'flood-netika-osint';

if (isset($_POST['login'])) {
    if ($_POST['password'] === $admin_password) {
        $_SESSION['admin_logged_in'] = true;
    } else {
        $error = "ACCESS DENIED.";
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: sys-gateway.php");
    exit;
}

if (!isset($_SESSION['admin_logged_in'])) {
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>SYSTEM GATEWAY</title>
        <style>
            body { background: #020617; color: #33ff33; font-family: monospace; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
            .login-box { background: #0f172a; padding: 40px; border-radius: 8px; border: 1px solid #1e293b; text-align: center; box-shadow: 0 0 20px rgba(51, 255, 51, 0.1); width: 300px; }
            input { padding: 12px; width: 100%; box-sizing: border-box; margin-bottom: 20px; border-radius: 4px; border: 1px solid #334155; background: #020617; color: #fff; outline: none; font-family: monospace; text-align: center; }
            input:focus { border-color: #2563eb; }
            button { padding: 12px; width: 100%; background: #2563eb; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; font-family: monospace; letter-spacing: 2px; }
            button:hover { background: #1d4ed8; }
            .glitch { font-size: 1.5rem; margin-bottom: 20px; letter-spacing: 3px; color: #fff; }
        </style>
    </head>
    <body>
        <div class="login-box">
            <div class="glitch">AUTHORIZATION</div>
            <?php if(isset($error)) echo "<p style='color:#ef4444; margin-bottom: 15px;'>$error</p>"; ?>
            <form method="POST">
                <input type="password" name="password" placeholder="ENTER PASSPHRASE" required autocomplete="off">
                <button type="submit" name="login">AUTHENTICATE</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// LOGIKA ADMIN PANEL TETAP SAMA, HANYA TAMPILAN DIROMBAK
$codes_file = __DIR__ . '/data/codes.json';
if (!file_exists(__DIR__ . '/data')) mkdir(__DIR__ . '/data', 0777, true);
if (!file_exists($codes_file)) file_put_contents($codes_file, '{}');

$codes = json_decode(file_get_contents($codes_file), true);

if (isset($_POST['add_code'])) {
    $new_code = trim($_POST['code']);
    $limit = (int)$_POST['limit'];
    if (!empty($new_code) && $limit > 0) {
        $codes[$new_code] = ['limit' => $limit, 'used' => 0, 'status' => 'active'];
        file_put_contents($codes_file, json_encode($codes));
        header("Location: sys-gateway.php?msg=success_add");
        exit;
    }
}
if (isset($_GET['delete'])) {
    $del = $_GET['delete'];
    if (isset($codes[$del])) {
        unset($codes[$del]);
        file_put_contents($codes_file, json_encode($codes));
        header("Location: sys-gateway.php?msg=success_del");
        exit;
    }
}
if (isset($_POST['edit_code'])) {
    $edit_target = $_POST['target_code'];
    $new_limit = (int)$_POST['new_limit'];
    $new_status = $_POST['new_status'];
    if (isset($codes[$edit_target])) {
        $codes[$edit_target]['limit'] = $new_limit;
        $codes[$edit_target]['status'] = $new_status;
        file_put_contents($codes_file, json_encode($codes));
        header("Location: sys-gateway.php?msg=success_edit");
        exit;
    }
}

$visitorFile = __DIR__ . '/data/visitors.txt';
$visitors = file_exists($visitorFile) ? file_get_contents($visitorFile) : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OVERSEER DASHBOARD</title>
    <style>
        body { background: #020617; color: #f1f5f9; font-family: 'Inter', sans-serif; padding: 20px; margin: 0; }
        .container { max-width: 1000px; margin: 0 auto; background: #0f172a; padding: 30px; border-radius: 12px; border: 1px solid #1e293b; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
        h2 { margin: 0; color: #3b82f6; font-family: monospace; letter-spacing: 1px; }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #1e293b; padding-bottom: 20px; margin-bottom: 30px; }
        .stat-box { background: #1e293b; padding: 10px 20px; border-radius: 8px; font-family: monospace; font-size: 0.9rem; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 0.9rem; }
        th, td { border-bottom: 1px solid #1e293b; padding: 15px; text-align: left; }
        th { color: #94a3b8; font-weight: normal; text-transform: uppercase; font-size: 0.8rem; }
        .badge-active { background: rgba(16, 185, 129, 0.2); color: #10b981; padding: 4px 10px; border-radius: 6px; font-size: 0.75rem; border: 1px solid #10b981; }
        .badge-inactive { background: rgba(239, 68, 68, 0.2); color: #ef4444; padding: 4px 10px; border-radius: 6px; font-size: 0.75rem; border: 1px solid #ef4444; }
        input, select { padding: 10px; border: 1px solid #334155; border-radius: 6px; background: #020617; color: #fff; font-family: monospace; outline: none; }
        input:focus { border-color: #3b82f6; }
        button { padding: 10px 20px; background: #2563eb; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; }
        button:hover { background: #1d4ed8; }
        .btn-danger { background: #ef4444; text-decoration: none; padding: 8px 12px; color: white; border-radius: 6px; font-size: 0.8rem; font-weight: bold;}
        .btn-danger:hover { background: #dc2626; }
        .form-group { display: flex; gap: 10px; align-items: center; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>SYSTEM OVERSEER</h2>
            <div style="display: flex; gap: 15px; align-items: center;">
                <div class="stat-box">TRAFFIC: <span style="color:#10b981;"><?php echo number_format($visitors); ?></span></div>
                <a href="?logout=true" style="color: #ef4444; text-decoration: none; font-weight: bold; font-family: monospace;">[ LOGOUT ]</a>
            </div>
        </div>

        <?php if(isset($_GET['msg'])) echo "<div style='background: rgba(16,185,129,0.1); color: #10b981; padding: 15px; border-radius: 8px; border: 1px solid #10b981; margin-bottom: 20px;'>✔️ DATABASE UPDATED SUCCESSFULLY.</div>"; ?>

        <div style="background: #1e293b; padding: 20px; border-radius: 10px; margin-bottom: 30px; border: 1px solid #334155;">
            <h3 style="margin-top:0; color: #fff; font-size: 1rem;">GENERATE NEW ACCESS KEY</h3>
            <form method="POST" class="form-group">
                <input type="text" name="code" placeholder="ENTER SECRET CODE (e.g. VIP-X99)" required style="flex: 1;">
                <input type="number" name="limit" placeholder="USAGE LIMIT" required style="width: 150px;">
                <button type="submit" name="add_code">DEPLOY KEY</button>
            </form>
        </div>

        <h3 style="color: #fff; font-size: 1rem; margin-bottom: 15px;">ACTIVE & ARCHIVED KEYS</h3>
        <div style="overflow-x: auto;">
            <table>
                <tr>
                    <th>Access Key</th>
                    <th>Usage</th>
                    <th>Limit</th>
                    <th>Status</th>
                    <th>Modify</th>
                    <th>Revoke</th>
                </tr>
                <?php foreach($codes as $code => $data): ?>
                <tr>
                    <td style="font-family: monospace; font-size: 1.1rem; color: #3b82f6;"><strong><?php echo $code; ?></strong></td>
                    <td style="font-family: monospace;"><?php echo $data['used']; ?>x</td>
                    <td>
                        <form method="POST" style="display:flex; gap: 10px; align-items: center;">
                            <input type="hidden" name="target_code" value="<?php echo $code; ?>">
                            <input type="number" name="new_limit" value="<?php echo $data['limit']; ?>" style="width: 70px; padding: 6px;">
                    </td>
                    <td>
                            <select name="new_status" style="padding: 6px;">
                                <option value="active" <?php echo ($data['status']=='active')?'selected':''; ?>>ACTIVE</option>
                                <option value="inactive" <?php echo ($data['status']=='inactive')?'selected':''; ?>>INACTIVE</option>
                            </select>
                    </td>
                    <td>
                            <button type="submit" name="edit_code" style="padding: 6px 12px; font-size: 0.8rem; background: #3b82f6;">UPDATE</button>
                        </form>
                    </td>
                    <td>
                        <a href="?delete=<?php echo $code; ?>" class="btn-danger" onclick="return confirm('PERINGATAN: Yakin mencabut akses ini secara permanen?')">REVOKE</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($codes)) echo "<tr><td colspan='6' style='text-align:center; padding: 30px; color: #64748b;'>NO ACTIVE KEYS FOUND.</td></tr>"; ?>
            </table>
        </div>
    </div>
</body>
</html>
