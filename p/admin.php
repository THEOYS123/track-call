<?php
session_start();

// UBAH PASSWORD DI SINI BRO
$admin_password = 'ax0895';

if (isset($_POST['login'])) {
    if ($_POST['password'] === $admin_password) {
        $_SESSION['admin_logged_in'] = true;
    } else {
        $error = "Password Salah!";
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: admin.php");
    exit;
}

if (!isset($_SESSION['admin_logged_in'])) {
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head><title>Admin Panel - Login</title>
    <style>
        body { background: #0f172a; color: #fff; font-family: sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-box { background: #1e293b; padding: 30px; border-radius: 10px; border: 1px solid #334155; text-align: center; }
        input { padding: 10px; width: 80%; margin-bottom: 15px; border-radius: 5px; border: none; }
        button { padding: 10px 20px; background: #2563eb; color: white; border: none; border-radius: 5px; cursor: pointer; }
    </style>
    </head>
    <body>
        <div class="login-box">
            <h2>Admin OSINT</h2>
            <?php if(isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
            <form method="POST">
                <input type="password" name="password" placeholder="Masukkan Password Admin" required><br>
                <button type="submit" name="login">Masuk</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// LOGIKA ADMIN PANEL
$codes_file = __DIR__ . '/data/codes.json';
if (!file_exists(__DIR__ . '/data')) mkdir(__DIR__ . '/data', 0777, true);
if (!file_exists($codes_file)) file_put_contents($codes_file, '{}');

$codes = json_decode(file_get_contents($codes_file), true);

// Tambah Kode
if (isset($_POST['add_code'])) {
    $new_code = trim($_POST['code']);
    $limit = (int)$_POST['limit'];
    if (!empty($new_code) && $limit > 0) {
        $codes[$new_code] = ['limit' => $limit, 'used' => 0, 'status' => 'active'];
        file_put_contents($codes_file, json_encode($codes));
        header("Location: admin.php?msg=success_add");
        exit;
    }
}

// Hapus Kode
if (isset($_GET['delete'])) {
    $del = $_GET['delete'];
    if (isset($codes[$del])) {
        unset($codes[$del]);
        file_put_contents($codes_file, json_encode($codes));
        header("Location: admin.php?msg=success_del");
        exit;
    }
}

// Edit Limit / Status
if (isset($_POST['edit_code'])) {
    $edit_target = $_POST['target_code'];
    $new_limit = (int)$_POST['new_limit'];
    $new_status = $_POST['new_status'];
    if (isset($codes[$edit_target])) {
        $codes[$edit_target]['limit'] = $new_limit;
        $codes[$edit_target]['status'] = $new_status;
        file_put_contents($codes_file, json_encode($codes));
        header("Location: admin.php?msg=success_edit");
        exit;
    }
}

// Hitung total visitor
$visitorFile = __DIR__ . '/data/visitors.txt';
$visitors = file_exists($visitorFile) ? file_get_contents($visitorFile) : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Admin - Secret Codes</title>
    <style>
        body { background: #f8fafc; color: #1e293b; font-family: sans-serif; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #cbd5e1; padding: 10px; text-align: left; }
        th { background: #2563eb; color: white; }
        .badge-active { background: #10b981; color: white; padding: 3px 8px; border-radius: 5px; font-size: 12px; }
        .badge-inactive { background: #ef4444; color: white; padding: 3px 8px; border-radius: 5px; font-size: 12px; }
        input, select { padding: 8px; border: 1px solid #cbd5e1; border-radius: 5px; }
        button { padding: 8px 15px; background: #2563eb; color: white; border: none; border-radius: 5px; cursor: pointer; }
        .btn-danger { background: #ef4444; text-decoration: none; padding: 5px 10px; color: white; border-radius: 5px; font-size: 12px;}
    </style>
</head>
<body>
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px; margin-bottom: 20px;">
            <h2 style="margin:0;">⚙️ Admin Panel - Secret Codes</h2>
            <div>
                <strong>Total Visitor: <?php echo number_format($visitors); ?></strong> |
                <a href="?logout=true" style="color: red; text-decoration: none; margin-left: 10px;">Logout</a>
            </div>
        </div>

        <?php if(isset($_GET['msg'])) echo "<p style='color: green; font-weight: bold;'>Berhasil update data!</p>"; ?>

        <div style="background: #f1f5f9; padding: 15px; border-radius: 10px; margin-bottom: 20px;">
            <h3 style="margin-top:0;">➕ Buat Kode Baru</h3>
            <form method="POST">
                <input type="text" name="code" placeholder="Misal: VIP-DATA-123" required>
                <input type="number" name="limit" placeholder="Batas Penggunaan" required>
                <button type="submit" name="add_code">Buat Kode</button>
            </form>
        </div>

        <h3>Daftar Kode Akses</h3>
        <table>
            <tr>
                <th>Kode Rahasia</th>
                <th>Terpakai</th>
                <th>Limit</th>
                <th>Status</th>
                <th>Aksi Edit</th>
                <th>Hapus</th>
            </tr>
            <?php foreach($codes as $code => $data): ?>
            <tr>
                <td><strong><?php echo $code; ?></strong></td>
                <td><?php echo $data['used']; ?> kali</td>
                <td>
                    <form method="POST" style="display:flex; gap: 5px;">
                        <input type="hidden" name="target_code" value="<?php echo $code; ?>">
                        <input type="number" name="new_limit" value="<?php echo $data['limit']; ?>" style="width: 60px;">
                </td>
                <td>
                        <select name="new_status">
                            <option value="active" <?php echo ($data['status']=='active')?'selected':''; ?>>Active</option>
                            <option value="inactive" <?php echo ($data['status']=='inactive')?'selected':''; ?>>Inactive</option>
                        </select>
                </td>
                <td>
                        <button type="submit" name="edit_code" style="padding: 5px 10px; font-size: 12px; background: #f59e0b;">Update</button>
                    </form>
                </td>
                <td>
                    <a href="?delete=<?php echo $code; ?>" class="btn-danger" onclick="return confirm('Yakin hapus kode ini?')">Hapus</a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($codes)) echo "<tr><td colspan='6' style='text-align:center;'>Belum ada kode rahasia.</td></tr>"; ?>
        </table>
    </div>
</body>
</html>
