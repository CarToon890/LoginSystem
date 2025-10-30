<?php
require __DIR__ . '/csrf.php';
if (empty($_SESSION['user_id'])) {
  header('Location: login.php');
  exit;
}
// dashboard.php — Minimal Bootstrap + Chart.js

// เปิด error ตอนพัฒนา (เอาออกในโปรดักชัน)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// DB
require __DIR__ . '/config_mysqli.php';

$mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($mysqli->connect_errno) {
  http_response_code(500);
  die('Database connection failed: ' . $mysqli->connect_error);
}
$mysqli->set_charset('utf8mb4');

function fetch_all($mysqli, $sql)
{
  $res = $mysqli->query($sql);
  $rows = [];
  while ($row = $res->fetch_assoc()) $rows[] = $row;
  $res->free();
  return $rows;
}
function nf2($n)
{
  return number_format((float)$n, 2);
}

// DATA
$monthly      = fetch_all($mysqli, "SELECT ym, net_sales FROM v_monthly_sales") ?: [];
$category     = fetch_all($mysqli, "SELECT category, net_sales FROM v_sales_by_category") ?: [];
$region       = fetch_all($mysqli, "SELECT region, net_sales FROM v_sales_by_region") ?: [];
$topProducts  = fetch_all($mysqli, "SELECT product_name, qty_sold, net_sales FROM v_top_products") ?: [];
$payment      = fetch_all($mysqli, "SELECT payment_method, net_sales FROM v_payment_share") ?: [];
$hourly       = fetch_all($mysqli, "SELECT hour_of_day, net_sales FROM v_hourly_sales") ?: [];
$newReturning = fetch_all($mysqli, "SELECT date_key, new_customer_sales, returning_sales FROM v_new_vs_returning ORDER BY date_key") ?: [];

$kpis = fetch_all($mysqli, "
  SELECT
    (SELECT IFNULL(SUM(net_amount),0) FROM fact_sales WHERE date_key >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)) AS sales_30d,
    (SELECT IFNULL(SUM(quantity),0)   FROM fact_sales WHERE date_key >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)) AS qty_30d,
    (SELECT IFNULL(COUNT(DISTINCT customer_id),0) FROM fact_sales WHERE date_key >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)) AS buyers_30d
");
$kpi = $kpis ? $kpis[0] : ['sales_30d' => 0, 'qty_30d' => 0, 'buyers_30d' => 0];
?>
<!doctype html>
<html lang="th">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Retail DW Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
  <style>
    :root {
      --bg: #f8fafc;
      --text: #0f172a;
      --muted: #64748b;
      --card: #ffffff;
      --border: #e2e8f0;
    }

    body {
      background: var(--bg);
      color: var(--text);
    }

    .container-xl {
      max-width: 1280px;
    }

    .card {
      border: 1px solid var(--border);
      background: var(--card);
    }

    .sub {
      color: var(--muted);
      font-size: .95rem;
    }

    .chart-wrap {
      height: 320px;
    }

    /* ความสูงกราฟคงที่ */
    .chart-wrap>canvas {
      height: 100% !important;
    }

    .wrapword {
      overflow-wrap: anywhere;
      word-break: break-word;
    }

    .kpi-val {
      font-weight: 800;
      font-size: 1.6rem;
    }

    .kpi-label {
      color: var(--muted);
    }

    /* ป้องกันหัวการ์ดยาวเกิน */
    .card-title {
      text-overflow: ellipsis;
      white-space: nowrap;
      overflow: hidden;
    }
  </style>
</head>

<body class="py-3 py-md-4">
  <div class="container-xl">
    <div class="d-flex align-items-baseline justify-content-between mb-3">
      <h2 class="mb-0">Retail DW — Dashboard</h2>
      <span class="sub">แหล่งข้อมูล: MySQL (mysqli)</span>
    </div>
    <nav class="navbar navbar-light bg-light border-bottom mb-4">
      <div class="container">
        <span class="navbar-brand">Welcome</span>
        <div class="d-flex align-items-center gap-3">
          <span class="text-muted small">Hi, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
          <a class="btn btn-outline-secondary btn-sm" href="logout.php">Logout</a>
        </div>
      </div>
    </nav>
    <!-- KPIs -->
    <div class="row row-cols-1 row-cols-md-3 g-3 mb-3">
      <div class="col">
        <div class="card h-100">
          <div class="card-body d-flex flex-column justify-content-center">
            <div class="kpi-label mb-1">ยอดขาย 30 วัน</div>
            <div class="kpi-val">฿<?= nf2($kpi['sales_30d']) ?></div>
          </div>
        </div>
      </div>
      <div class="col">
        <div class="card h-100">
          <div class="card-body d-flex flex-column justify-content-center">
            <div class="kpi-label mb-1">จำนวนชิ้นขาย 30 วัน</div>
            <div class="kpi-val"><?= number_format((int)$kpi['qty_30d']) ?> ชิ้น</div>
          </div>
        </div>
      </div>
      <div class="col">
        <div class="card h-100">
          <div class="card-body d-flex flex-column justify-content-center">
            <div class="kpi-label mb-1">จำนวนผู้ซื้อ 30 วัน</div>
            <div class="kpi-val"><?= number_format((int)$kpi['buyers_30d']) ?> คน</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Charts (สมมาตรด้วย Bootstrap grid) -->
    <div class="row g-3">
      <div class="col-lg-8">
        <div class="card h-100">
          <div class="card-header">
            <div class="card-title h6 mb-0 text-truncate" title="ยอดขายรายเดือน">ยอดขายรายเดือน</div>
          </div>
          <div class="card-body">
            <div class="chart-wrap"><canvas id="chartMonthly"></canvas></div>
          </div>
        </div>
      </div>

      <div class="col-lg-4">
        <div class="card h-100">
          <div class="card-header">
            <div class="card-title h6 mb-0 text-truncate" title="สัดส่วนยอดขายตามหมวด">สัดส่วนยอดขายตามหมวด</div>
          </div>
          <div class="card-body">
            <div class="chart-wrap"><canvas id="chartCategory"></canvas></div>
          </div>
        </div>
      </div>

      <div class="col-lg-6">
        <div class="card h-100">
          <div class="card-header">
            <div class="card-title h6 mb-0 text-truncate" title="Top 10 สินค้าขายดี">Top 10 สินค้าขายดี</div>
          </div>
          <div class="card-body">
            <div class="chart-wrap"><canvas id="chartTopProducts"></canvas></div>
          </div>
        </div>
      </div>

      <div class="col-lg-6">
        <div class="card h-100">
          <div class="card-header">
            <div class="card-title h6 mb-0 text-truncate" title="ยอดขายตามภูมิภาค">ยอดขายตามภูมิภาค</div>
          </div>
          <div class="card-body">
            <div class="chart-wrap"><canvas id="chartRegion"></canvas></div>
          </div>
        </div>
      </div>

      <div class="col-lg-6">
        <div class="card h-100">
          <div class="card-header">
            <div class="card-title h6 mb-0 text-truncate" title="วิธีการชำระเงิน">วิธีการชำระเงิน</div>
          </div>
          <div class="card-body">
            <div class="chart-wrap"><canvas id="chartPayment"></canvas></div>
          </div>
        </div>
      </div>

      <div class="col-lg-6">
        <div class="card h-100">
          <div class="card-header">
            <div class="card-title h6 mb-0 text-truncate" title="ยอดขายรายชั่วโมง">ยอดขายรายชั่วโมง</div>
          </div>
          <div class="card-body">
            <div class="chart-wrap"><canvas id="chartHourly"></canvas></div>
          </div>
        </div>
      </div>

      <div class="col-12">
        <div class="card h-100">
          <div class="card-header">
            <div class="card-title h6 mb-0 text-truncate" title="ลูกค้าใหม่ vs ลูกค้าเดิม (รายวัน)">ลูกค้าใหม่ vs ลูกค้าเดิม (รายวัน)</div>
          </div>
          <div class="card-body">
            <div class="chart-wrap"><canvas id="chartNewReturning"></canvas></div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
    // Chart.js defaults (อ่านง่าย, สีสุภาพ)
    Chart.defaults.font.family = "'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, Arial";
    Chart.defaults.color = "#0f172a";

    // PHP -> JS
    const monthly = <?= json_encode($monthly, JSON_UNESCAPED_UNICODE) ?>;
    const category = <?= json_encode($category, JSON_UNESCAPED_UNICODE) ?>;
    const region = <?= json_encode($region, JSON_UNESCAPED_UNICODE) ?>;
    const topProducts = <?= json_encode($topProducts, JSON_UNESCAPED_UNICODE) ?>;
    const payment = <?= json_encode($payment, JSON_UNESCAPED_UNICODE) ?>;
    const hourly = <?= json_encode($hourly, JSON_UNESCAPED_UNICODE) ?>;
    const newReturning = <?= json_encode($newReturning, JSON_UNESCAPED_UNICODE) ?>;

    const toXY = (arr, x, y) => ({
      labels: arr.map(o => o[x]),
      values: arr.map(o => +o[y] || 0)
    });

    // Monthly
    (() => {
      const {
        labels,
        values
      } = toXY(monthly, 'ym', 'net_sales');
      new Chart(document.getElementById('chartMonthly'), {
        type: 'line',
        data: {
          labels,
          datasets: [{
            label: 'ยอดขาย (฿)',
            data: values,
            tension: .25,
            fill: true
          }]
        },
        options: {
          maintainAspectRatio: false,
          elements: {
            point: {
              radius: 0
            }
          },
          scales: {
            x: {
              grid: {
                color: '#e2e8f0'
              }
            },
            y: {
              grid: {
                color: '#e2e8f0'
              }
            }
          },
          plugins: {
            legend: {
              display: false
            }
          }
        }
      });
    })();

    // Category
    (() => {
      const {
        labels,
        values
      } = toXY(category, 'category', 'net_sales');
      new Chart(document.getElementById('chartCategory'), {
        type: 'doughnut',
        data: {
          labels,
          datasets: [{
            data: values
          }]
        },
        options: {
          maintainAspectRatio: false,
          plugins: {
            legend: {
              position: 'bottom'
            }
          }
        }
      });
    })();

    // Top products (horizontal bars by qty)
    (() => {
      const labels = topProducts.map(o => o.product_name);
      const qty = topProducts.map(o => parseInt(o.qty_sold) || 0);
      new Chart(document.getElementById('chartTopProducts'), {
        type: 'bar',
        data: {
          labels,
          datasets: [{
            label: 'ชิ้นที่ขาย',
            data: qty
          }]
        },
        options: {
          maintainAspectRatio: false,
          indexAxis: 'y',
          scales: {
            x: {
              grid: {
                color: '#e2e8f0'
              }
            },
            y: {
              grid: {
                color: '#f8fafc'
              }
            }
          },
          plugins: {
            legend: {
              display: false
            }
          }
        }
      });
    })();

    // Region
    (() => {
      const {
        labels,
        values
      } = toXY(region, 'region', 'net_sales');
      new Chart(document.getElementById('chartRegion'), {
        type: 'bar',
        data: {
          labels,
          datasets: [{
            label: 'ยอดขาย (฿)',
            data: values
          }]
        },
        options: {
          maintainAspectRatio: false,
          scales: {
            x: {
              grid: {
                color: '#f8fafc'
              }
            },
            y: {
              grid: {
                color: '#e2e8f0'
              }
            }
          },
          plugins: {
            legend: {
              display: false
            }
          }
        }
      });
    })();

    // Payment
    (() => {
      const {
        labels,
        values
      } = toXY(payment, 'payment_method', 'net_sales');
      new Chart(document.getElementById('chartPayment'), {
        type: 'pie',
        data: {
          labels,
          datasets: [{
            data: values
          }]
        },
        options: {
          maintainAspectRatio: false,
          plugins: {
            legend: {
              position: 'bottom'
            }
          }
        }
      });
    })();

    // Hourly
    (() => {
      const {
        labels,
        values
      } = toXY(hourly, 'hour_of_day', 'net_sales');
      new Chart(document.getElementById('chartHourly'), {
        type: 'bar',
        data: {
          labels,
          datasets: [{
            label: 'ยอดขาย (฿)',
            data: values
          }]
        },
        options: {
          maintainAspectRatio: false,
          scales: {
            x: {
              grid: {
                color: '#f8fafc'
              }
            },
            y: {
              grid: {
                color: '#e2e8f0'
              }
            }
          },
          plugins: {
            legend: {
              display: false
            }
          }
        }
      });
    })();

    // New vs Returning
    (() => {
      const labels = newReturning.map(o => o.date_key);
      const newC = newReturning.map(o => +o.new_customer_sales || 0);
      const retC = newReturning.map(o => +o.returning_sales || 0);
      new Chart(document.getElementById('chartNewReturning'), {
        type: 'line',
        data: {
          labels,
          datasets: [{
              label: 'ลูกค้าใหม่ (฿)',
              data: newC,
              tension: .25,
              fill: false
            },
            {
              label: 'ลูกค้าเดิม (฿)',
              data: retC,
              tension: .25,
              fill: false
            }
          ]
        },
        options: {
          maintainAspectRatio: false,
          elements: {
            point: {
              radius: 0
            }
          },
          scales: {
            x: {
              grid: {
                color: '#e2e8f0'
              }
            },
            y: {
              grid: {
                color: '#e2e8f0'
              }
            }
          }
        }
      });
    })();
  </script>
</body>

</html>
