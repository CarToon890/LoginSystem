<?php
// reports.php — Reports & Export (CSV) for Sales / Users
// ใช้ร่วมกับ config_mysqli.php และระบบ session เดิม
require __DIR__ . '/config_mysqli.php';
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// อ่านพารามิเตอร์
$export = $_GET['export'] ?? null;   // 'sales' | 'users' | null
$from   = $_GET['from']   ?? $_POST['from']   ?? date('Y-m-01');
$to     = $_GET['to']     ?? $_POST['to']     ?? date('Y-m-d');

// helper: ตรวจสอบวันที่ (Y-m-d) แบบง่าย
function is_ymd($s)
{
    return (bool)preg_match('/^\d{4}-\d{2}-\d{2}$/', $s);
}

// ถ้าเป็นโหมด export → ส่งไฟล์ CSV ออกเลย
if ($export && is_ymd($from) && is_ymd($to)) {
    // ปรับขอบเขตให้ from <= to
    if ($from > $to) {
        [$from, $to] = [$to, $from];
    }

    if ($export === 'sales') {
        // ตัวอย่างรายงานสรุปรายวัน (safe กับ schema ส่วนใหญ่)
        // ถ้ามีคอลัมน์ชื่ออื่น ปรับ SELECT ให้ตรง fact_sales ของคุณ
        $stmt = $mysqli->prepare("
      SELECT date_key AS date,
             SUM(net_amount) AS total_sales,
             SUM(quantity)   AS total_qty,
             COUNT(DISTINCT customer_id) AS unique_buyers
      FROM fact_sales
      WHERE date_key BETWEEN ? AND ?
      GROUP BY date_key
      ORDER BY date_key
    ");
        $stmt->bind_param('ss', $from, $to);
        $stmt->execute();
        $res = $stmt->get_result();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=sales_' . $from . '_' . $to . '.csv');

        $out = fopen('php://output', 'w');
        // หัวตาราง
        fputcsv($out, ['date', 'total_sales', 'total_qty', 'unique_buyers']);
        while ($row = $res->fetch_assoc()) {
            fputcsv($out, [
                $row['date'],
                number_format((float)$row['total_sales'], 2, '.', ''),
                (int)$row['total_qty'],
                (int)$row['unique_buyers']
            ]);
        }
        fclose($out);
        exit;
    }

    if ($export === 'users') {
        // รายงานผู้ใช้ที่สร้างภายในช่วงวันที่ + last_login
        $stmt = $mysqli->prepare("
      SELECT id, username, email, display_name, full_name, created_at, last_login
      FROM users
      WHERE DATE(created_at) BETWEEN ? AND ?
      ORDER BY created_at
    ");
        $stmt->bind_param('ss', $from, $to);
        $stmt->execute();
        $res = $stmt->get_result();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=users_' . $from . '_' . $to . '.csv');

        $out = fopen('php://output', 'w');
        fputcsv($out, ['id', 'username', 'email', 'display_name', 'full_name', 'created_at', 'last_login']);
        while ($row = $res->fetch_assoc()) {
            fputcsv($out, [
                (int)$row['id'],
                $row['username'],
                $row['email'],
                $row['display_name'],
                $row['full_name'],
                $row['created_at'],
                $row['last_login']
            ]);
        }
        fclose($out);
        exit;
    }
}

// โหมดปกติ: หน้า UI เลือกช่วงวันที่ + ปุ่มดาวน์โหลด
?>
<!doctype html>
<html lang="th">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reports & Export</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f8fafc;
        }
    </style>
</head>

<body>
    <div class="container py-4" style="max-width:960px">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="mb-0">Reports & Export</h3>
            <div class="d-flex gap-2">
                <a class="btn btn-outline-secondary btn-sm" href="dashboard.php">กลับ Dashboard</a>
                <a class="btn btn-outline-danger btn-sm" href="logout.php">Logout</a>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                <form class="row g-3" method="get" action="reports.php">
                    <div class="col-md-4">
                        <label class="form-label">วันที่เริ่ม (YYYY-MM-DD)</label>
                        <input type="date" class="form-control" name="from" value="<?= htmlspecialchars($from) ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">วันที่สิ้นสุด (YYYY-MM-DD)</label>
                        <input type="date" class="form-control" name="to" value="<?= htmlspecialchars($to) ?>" required>
                    </div>
                    <div class="col-md-4 d-flex align-items-end gap-2 flex-wrap">
                        <button name="export" value="sales" type="submit" class="btn btn-primary">ดาวน์โหลด CSV — Sales</button>
                        <button name="export" value="users" type="submit" class="btn btn-outline-primary">ดาวน์โหลด CSV — Users</button>
                    </div>
                </form>

                <div class="form-text mt-3">
                    * Sales CSV: สรุปรายวัน (SUM net_amount, SUM quantity, COUNT DISTINCT customer_id) จาก <code>fact_sales</code><br>
                    * Users CSV: ผู้ใช้ที่สมัครในช่วงวันที่ จาก <code>users.created_at</code>, แสดง <code>last_login</code> ประกอบ
                </div>
            </div>
        </div>
    </div>
</body>

</html>