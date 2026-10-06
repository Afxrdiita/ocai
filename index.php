<?php
const THURUK_BASE_URL = "https://localhost/thruk";
const THURUK_API_KEY_ENV = "THURUK_API_KEY";

function getApiKey() {
    $key = getenv(THURUK_API_KEY_ENV);
    return $key ? $key : "f965cf88b8f475852d3af6d9b35778f29f4f46622b25ef4f49ee827710406ced_1";
}

function thrukGet($path) {
    $url = THURUK_BASE_URL . "/r/" . $path;
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_HTTPHEADER => ["X-Thruk-Auth-Key: " . getApiKey()],
    ]);
    $body = curl_exec($ch);
    if ($body === false) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new Exception("Thruk API request failed: " . $error);
    }
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($status !== 200) {
        throw new Exception("Thruk API returned HTTP " . $status);
    }
    $data = json_decode($body, true);
    if (!is_array($data)) {
        throw new Exception("Thruk API returned invalid data");
    }
    if (isset($data["data"]) && is_array($data["data"])) {
        return $data["data"];
    }
    return $data;
}

$hostStatus = [];
$nocError = null;
$serverList = [];
set_time_limit(120);

$statusCacheFile = sys_get_temp_dir() . "/noc_status_cache.ser";
$cached = false;
if (file_exists($statusCacheFile) && (time() - filemtime($statusCacheFile)) < 60) {
    $cache = @unserialize((string)file_get_contents($statusCacheFile));
    if (is_array($cache) && isset($cache["serverList"]) && is_array($cache["serverList"]) && isset($cache["hostStatus"]) && is_array($cache["hostStatus"])) {
        $serverList = $cache["serverList"];
        $hostStatus = $cache["hostStatus"];
        $cached = true;
    }
}

if (!$cached) {
    try {
        $allHosts = thrukGet("hosts?columns=name&sort=name");
        foreach ($allHosts as $h) {
            if (isset($h["name"]) && $h["name"] !== "") {
                $serverList[] = $h["name"];
            }
        }
        $serverList = array_values(array_unique($serverList));

        foreach ($serverList as $name) {
            $hostStatus[$name] = "green";
        }

        $allServices = thrukGet("services?columns=host_name,state&state[gte]=1");
        foreach ($allServices as $row) {
            $h = isset($row["host_name"]) ? $row["host_name"] : "";
            if (!in_array($h, $serverList, true)) {
                continue;
            }
            $st = (int)(isset($row["state"]) ? $row["state"] : -1);
            if ($st === 2) {
                $hostStatus[$h] = "red";
            } elseif ($st === 1 || $st === 3) {
                if ($hostStatus[$h] !== "red") {
                    $hostStatus[$h] = "yellow";
                }
            }
        }

        $hostStates = thrukGet("hosts?columns=name,state");
        foreach ($hostStates as $row) {
            $h = isset($row["name"]) ? $row["name"] : "";
            if (!in_array($h, $serverList, true)) {
                continue;
            }
            $st = (int)(isset($row["state"]) ? $row["state"] : -1);
            if ($st === 1 || $st === 2) {
                $hostStatus[$h] = "red";
            }
        }

        @file_put_contents($statusCacheFile, serialize(["serverList" => $serverList, "hostStatus" => $hostStatus]));
    } catch (Exception $e) {
        $nocError = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NOC Index</title>
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            background: #0a0a12;
            font-family: Arial, sans-serif;
            color: #eee;
            padding: 60px 20px;
        }

        .container { max-width: 800px; margin: 0 auto; }

        h1 {
            text-align: center;
            font-size: 36px;
            font-weight: 900;
            letter-spacing: 4px;
            text-transform: uppercase;
            color: #fff;
            text-shadow: 0 0 10px #00f0ff, 0 0 30px #00f0ff;
        }

        .subtitle { text-align: center; color: #888; font-size: 14px; margin-bottom: 50px; }

        h2.section {
            font-size: 16px;
            letter-spacing: 3px;
            text-transform: uppercase;
            color: #00f0ff;
            text-shadow: 0 0 10px rgba(0, 240, 255, 0.5);
            margin: 40px 0 15px 0;
            border-bottom: 1px solid rgba(0, 240, 255, 0.3);
            padding-bottom: 8px;
        }

        .link-list { display: flex; flex-direction: column; gap: 10px; }

        .link-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px 25px;
            border: 2px solid #333;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.03);
            color: #fff;
            text-decoration: none;
            font-size: 16px;
            font-weight: bold;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .link-item:hover {
            border-color: #00f0ff;
            box-shadow: 0 0 15px rgba(0, 240, 255, 0.4);
        }

        .link-item .desc { color: #888; font-size: 13px; font-weight: normal; margin-left: auto; text-align: right; }

        .dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            flex-shrink: 0;
            background: #555;
        }

        .dot.green { background: #5aff8a; box-shadow: 0 0 8px #5aff8a; }
        .dot.yellow { background: #ffd75a; box-shadow: 0 0 8px #ffd75a; }
        .dot.red { background: #ff5a5a; box-shadow: 0 0 8px #ff5a5a; }

        .fun-link {
            border-color: rgba(255, 0, 230, 0.4);
        }

        .fun-link:hover {
            border-color: #ff00e6;
            box-shadow: 0 0 15px rgba(255, 0, 230, 0.4);
        }

        .error-box {
            border: 2px solid #ff5a5a;
            border-radius: 10px;
            padding: 15px 25px;
            text-align: center;
            color: #ff5a5a;
            font-size: 14px;
            box-shadow: 0 0 15px rgba(255, 90, 90, 0.4);
            margin: 0 0 30px 0;
        }

        .updated { text-align: center; color: #666; font-size: 12px; margin-top: 40px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>NOC Index</h1>
        <div class="subtitle">Everything in one place</div>

        <?php if ($nocError): ?>
            <div class="error-box">Live server statuses unavailable: <?php echo htmlspecialchars($nocError); ?></div>
        <?php endif; ?>

        <h2 class="section">Server Dashboards</h2>
        <div class="link-list">
            <?php foreach ($serverList as $name):
                $dotClass = isset($hostStatus[$name]) ? " " . $hostStatus[$name] : "";
            ?>
                <a class="link-item" href="dashboard.php?host=<?php echo urlencode($name); ?>">
                    <span class="dot<?php echo $dotClass; ?>"></span>
                    <?php echo htmlspecialchars($name); ?>
                    <span class="desc">Monitoring dashboard</span>
                </a>
            <?php endforeach; ?>
        </div>

        <h2 class="section">Fun &amp; Games</h2>
        <div class="link-list">
            <a class="link-item fun-link" href="facts.php">
                <span class="dot"></span>
                Fun Facts
                <span class="desc">Neon box &amp; slot machine lever</span>
            </a>
            <a class="link-item fun-link" href="quiz.php">
                <span class="dot"></span>
                The Test
                <span class="desc">10 general knowledge questions</span>
            </a>
        </div>

        <div class="updated">Last updated <?php echo date("H:i:s"); ?></div>
    </div>
</body>
</html>
