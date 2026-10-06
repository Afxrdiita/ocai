<?php
const THURUK_BASE_URL = "https://monitoring-dr.options-it.com/thruk";
const MONITORED_SERVER = "yinnag01e";
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

function formatDuration($seconds) {
    if ($seconds < 0) $seconds = 0;
    $days = floor($seconds / 86400);
    $hours = floor(($seconds % 86400) / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    if ($days > 0) return $days . "d " . $hours . "h";
    if ($hours > 0) return $hours . "h " . $minutes . "m";
    return $minutes . "m";
}

function hostStateLabel($state) {
    switch ((int)$state) {
        case 0: return ["UP", "ok"];
        case 1: return ["DOWN", "crit"];
        case 2: return ["UNREACHABLE", "crit"];
        default: return ["UNKNOWN", "unknown"];
    }
}

function serviceStateLabel($state) {
    switch ((int)$state) {
        case 0: return ["OK", "ok"];
        case 1: return ["WARNING", "warn"];
        case 2: return ["CRITICAL", "crit"];
        default: return ["UNKNOWN", "unknown"];
    }
}

function plainServiceName($service) {
    $s = strtolower($service);
    $map = [
        "cpu" => "How hard the computer is working",
        "load" => "How busy the computer is",
        "memory" => "Available short-term memory",
        "ram" => "Available short-term memory",
        "swap" => "Backup memory usage",
        "disk" => "Storage space",
        "disk /" => "Main storage space",
        "space" => "Storage space",
        "inode" => "Storage bookkeeping space",
        "ping" => "Network reachability",
        "http" => "Website availability",
        "https" => "Secure website availability",
        "zombie" => "Stuck programs",
        "process" => "Running programs",
        "service" => "A background program",
        "ntp" => "Clock accuracy",
        "time" => "Clock accuracy",
        "user" => "Signed-in people",
        "ssh" => "Remote access",
        "smtp" => "Email sending",
        "mail" => "Email",
        "ldap" => "Directory sign-in",
        "dns" => "Name lookup",
        "log" => "Event records",
        "backup" => "Backup copies",
        "temp" => "Temperature",
        "fan" => "Cooling fans",
        "power" => "Power supply",
        "raid" => "Redundant storage",
        "mysql" => "Database",
        "sql" => "Database",
        "agent" => "Monitoring agent",
    ];
    foreach ($map as $needle => $plain) {
        if (strpos($s, $needle) !== false) {
            return $plain;
        }
    }
    return $service;
}

function plainEnglish($service, $output, $state) {
    $parts = [];
    $name = plainServiceName($service);

    switch ((int)$state) {
        case 0: $parts[] = "Everything looks fine with the " . strtolower($name) . "."; break;
        case 1: $parts[] = "The " . strtolower($name) . " needs attention — it's close to a limit."; break;
        case 2: $parts[] = "There is a problem with the " . strtolower($name) . " that needs fixing."; break;
        default: $parts[] = "We could not check the " . strtolower($name) . " right now."; break;
    }

    $o = strtolower($output);
    if (preg_match_all("/(\d+(?:\.\d+)?)\s*%/", $output, $m)) {
        $pcts = array_map("floatval", $m[1]);
        $max = max($pcts);
        if (strpos($o, "packet loss") !== false && $max == 0) {
            $parts[] = "The connection is perfect with no dropped signals.";
        } elseif ($max >= 90) {
            $parts[] = "It is " . round($max) . "% of the way used, which is dangerously full.";
        } elseif ($max >= 75) {
            $parts[] = "It is " . round($max) . "% of the way used, which is getting close to full.";
        } else {
            $parts[] = "It is " . round($max) . "% of the way used.";
        }
    }
    if (strpos($o, "timeout") !== false || strpos($o, "timed out") !== false) {
        $parts[] = "The check took too long to get an answer.";
    }
    if (strpos($o, "refused") !== false) {
        $parts[] = "The connection was turned away.";
    }
    if (strpos($o, "unreachable") !== false || strpos($o, "down") !== false) {
        $parts[] = "The server could not be reached.";
    }
    if (strpos($o, "zombie") !== false) {
        $parts[] = "Some programs have stopped responding and are stuck.";
    }
    if (preg_match("/(\d+(?:\.\d+)?)\s*(?:days?|d)\b/i", $o)) {
        $parts[] = "This has been going on for a while.";
    }

    if (count($parts) === 1) {
        $parts[] = "No further explanation is needed.";
    }
    return implode(" ", $parts);
}

$error = null;
$hostDetails = null;
$services = [];
$counts = ["ok" => 0, "warn" => 0, "crit" => 0, "unknown" => 0];
$now = time();

try {
    $details = thrukGet("hosts?columns=name,address,state,plugin_output,last_state_change&name=" . urlencode(MONITORED_SERVER));
    if (count($details) > 0) {
        $hostDetails = $details[0];
    }
    $services = thrukGet("services?columns=description,state,last_state_change,plugin_output&host_name=" . urlencode(MONITORED_SERVER) . "&sort=-last_state_change");
    foreach ($services as $s) {
        $counts[serviceStateLabel(isset($s["state"]) ? $s["state"] : -1)[1]]++;
    }
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="30">
    <title><?php echo htmlspecialchars(MONITORED_SERVER); ?> Monitoring</title>
    <style>
        body {
            margin: 0;
            background: #0a0a12;
            font-family: Arial, sans-serif;
            color: #eee;
            padding: 40px 20px;
        }

        .container { max-width: 1100px; margin: 0 auto; }

        h1 {
            text-align: center;
            font-size: 32px;
            font-weight: 900;
            letter-spacing: 3px;
            text-transform: uppercase;
            color: #fff;
            text-shadow: 0 0 10px #00f0ff, 0 0 30px #00f0ff;
        }

        .host-card {
            border: 2px solid #00f0ff;
            border-radius: 12px;
            padding: 25px 30px;
            background: rgba(0, 240, 255, 0.05);
            box-shadow: 0 0 15px rgba(0, 240, 255, 0.4);
            margin: 35px 0 30px 0;
        }

        .host-card h2 {
            margin: 0 0 15px 0;
            color: #fff;
            font-size: 26px;
            letter-spacing: 2px;
        }

        .host-info div {
            margin: 6px 0;
            font-size: 15px;
        }

        .host-info .label { color: #888; display: inline-block; min-width: 130px; }
        .host-info .value { color: #eee; }

        .summary {
            display: flex;
            gap: 20px;
            margin-bottom: 25px;
        }

        .summary-item {
            border-radius: 10px;
            padding: 12px 28px;
            text-align: center;
            border: 2px solid #555;
            background: rgba(255, 255, 255, 0.03);
        }

        .summary-item .count { font-size: 26px; font-weight: 900; }
        .summary-item .label { font-size: 12px; letter-spacing: 2px; text-transform: uppercase; color: #aaa; }
        .summary-item.ok .count { color: #5aff8a; }
        .summary-item.warn .count { color: #ffd75a; }
        .summary-item.crit .count { color: #ff5a5a; }
        .summary-item.unknown .count { color: #aaa; }

        table {
            width: 100%;
            border-collapse: collapse;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 12px;
            overflow: hidden;
        }

        th {
            background: rgba(0, 240, 255, 0.08);
            color: #00f0ff;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-size: 13px;
            padding: 14px 16px;
            text-align: left;
        }

        td {
            padding: 12px 16px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            font-size: 15px;
            vertical-align: top;
        }

        tr.crit td { background: rgba(255, 0, 0, 0.08); }
        tr.warn td { background: rgba(255, 200, 0, 0.05); }

        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
            letter-spacing: 1px;
        }

        .badge.ok { background: rgba(90, 255, 138, 0.15); color: #5aff8a; border: 1px solid #5aff8a; }
        .badge.warn { background: rgba(255, 215, 90, 0.15); color: #ffd75a; border: 1px solid #ffd75a; }
        .badge.crit { background: rgba(255, 90, 90, 0.15); color: #ff5a5a; border: 1px solid #ff5a5a; }
        .badge.unknown { background: rgba(170, 170, 170, 0.15); color: #aaa; border: 1px solid #aaa; }

        .duration { font-weight: bold; color: #fff; white-space: nowrap; }
        tr.crit .duration { color: #ff5a5a; }
        tr.warn .duration { color: #ffd75a; }

        .svc { color: #00f0ff; font-weight: bold; }
        .output { color: #bbb; font-size: 13px; }
        .output .plain { display: none; color: #ccc; }
        .output.plain-mode .tech { display: none; }
        .output.plain-mode .plain { display: inline; }

        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }

        .tab {
            padding: 10px 25px;
            border: 2px solid #555;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.03);
            color: #aaa;
            font-size: 14px;
            font-weight: bold;
            letter-spacing: 1px;
            cursor: pointer;
            transition: border-color 0.2s, color 0.2s, box-shadow 0.2s;
        }

        .tab.active {
            border-color: #00f0ff;
            color: #fff;
            box-shadow: 0 0 10px rgba(0, 240, 255, 0.4);
        }

        .error-box {
            border: 2px solid #ff5a5a;
            border-radius: 10px;
            padding: 20px 30px;
            text-align: center;
            color: #ff5a5a;
            font-size: 17px;
            box-shadow: 0 0 15px rgba(255, 90, 90, 0.4);
            margin: 40px auto;
            max-width: 600px;
        }

        .updated { text-align: center; color: #666; font-size: 12px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h1><?php echo htmlspecialchars(MONITORED_SERVER); ?> Dashboard</h1>

        <?php if ($error): ?>
            <div class="error-box"><?php echo htmlspecialchars($error); ?></div>
        <?php elseif (!$hostDetails): ?>
            <div class="error-box">No monitoring data found for '<?php echo htmlspecialchars(MONITORED_SERVER); ?>'.</div>
        <?php else:
            list($hostLabel, $hostClass) = hostStateLabel(isset($hostDetails["state"]) ? $hostDetails["state"] : -1);
            $hostDuration = $now - (isset($hostDetails["last_state_change"]) ? $hostDetails["last_state_change"] : $now);
        ?>
            <div class="host-card">
                <h2><?php echo htmlspecialchars($hostDetails["name"]); ?></h2>
                <div class="host-info">
                    <div><span class="label">Status</span><span class="badge <?php echo $hostClass; ?>"><?php echo $hostLabel; ?></span></div>
                    <div><span class="label">Address</span><span class="value"><?php echo htmlspecialchars(isset($hostDetails["address"]) ? $hostDetails["address"] : "-"); ?></span></div>
                    <div><span class="label">In state since</span><span class="value"><?php echo formatDuration($hostDuration); ?></span></div>
                    <div><span class="label">Host check</span><span class="value"><?php echo htmlspecialchars(isset($hostDetails["plugin_output"]) ? $hostDetails["plugin_output"] : "-"); ?></span></div>
                </div>
            </div>

            <div class="summary">
                <div class="summary-item ok"><div class="count"><?php echo $counts["ok"]; ?></div><div class="label">OK</div></div>
                <div class="summary-item warn"><div class="count"><?php echo $counts["warn"]; ?></div><div class="label">Warning</div></div>
                <div class="summary-item crit"><div class="count"><?php echo $counts["crit"]; ?></div><div class="label">Critical</div></div>
                <div class="summary-item unknown"><div class="count"><?php echo $counts["unknown"]; ?></div><div class="label">Unknown</div></div>
            </div>

            <div class="tabs">
                <button class="tab active" id="tab-details">Details</button>
                <button class="tab" id="tab-plain">Plain English</button>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Urgency</th>
                        <th>Service</th>
                        <th>Status</th>
                        <th>Duration</th>
                        <th id="details-header">Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($services as $s):
                        list($label, $class) = serviceStateLabel(isset($s["state"]) ? $s["state"] : -1);
                        $duration = $now - (isset($s["last_state_change"]) ? $s["last_state_change"] : $now);
                        $svcName = isset($s["description"]) ? $s["description"] : "?";
                        $svcOutput = isset($s["plugin_output"]) ? $s["plugin_output"] : "";
                        $plain = plainEnglish($svcName, $svcOutput, isset($s["state"]) ? $s["state"] : -1);
                    ?>
                    <tr class="<?php echo $class; ?>">
                        <td class="duration"><?php echo formatDuration($duration); ?></td>
                        <td class="svc"><?php echo htmlspecialchars($svcName); ?></td>
                        <td><span class="badge <?php echo $class; ?>"><?php echo $label; ?></span></td>
                        <td class="duration"><?php echo formatDuration($duration); ?></td>
                        <td class="output">
                            <span class="tech"><?php echo htmlspecialchars($svcOutput); ?></span>
                            <span class="plain"><?php echo htmlspecialchars($plain); ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <div class="updated">Auto-refreshes every 30 seconds &middot; Last updated <?php echo date("H:i:s"); ?></div>
    </div>

    <script>
        const tabDetails = document.getElementById("tab-details");
        const tabPlain = document.getElementById("tab-plain");
        const detailsHeader = document.getElementById("details-header");
        const outputs = document.querySelectorAll(".output");

        function setMode(plain) {
            outputs.forEach(el => el.classList.toggle("plain-mode", plain));
            tabDetails.classList.toggle("active", !plain);
            tabPlain.classList.toggle("active", plain);
            if (detailsHeader) {
                detailsHeader.textContent = plain ? "Plain English" : "Details";
            }
        }

        tabDetails.addEventListener("click", () => setMode(false));
        tabPlain.addEventListener("click", () => setMode(true));
    </script>
</body>
</html>
