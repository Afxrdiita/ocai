<?php
const AI_URL = "https://api.privatemind.com/v1/chat/completions";
const AI_MODEL = "reasoning";
const AI_TOKEN_ENV = "AI_API_KEY";
const NOC_CACHE_TTL = 300;

const THURUK_BASE_URL = "https://monitoring-dr.options-it.com/thruk";
const THURUK_API_KEY_ENV = "THURUK_API_KEY";
const SERVER_LIST = [
    "yinnag01e",
    "cignag01n",
    "wtsnag01nye",
    "stcnag01-uk",
    "symnag01-uk",
    "smknag01-us",
];

function envValue($name, $fallback = "") {
    $value = getenv($name);
    return $value ? $value : $fallback;
}

function cacheDir() {
    $dir = sys_get_temp_dir() . "/jira_ai_cache";
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    return $dir;
}

function thrukGet($path) {
    $ch = curl_init(THURUK_BASE_URL . "/r/" . $path);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_HTTPHEADER => ["X-Thruk-Auth-Key: " . envValue(THURUK_API_KEY_ENV)],
    ]);
    $body = curl_exec($ch);
    if ($body === false) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new Exception("Thruk API request failed: " . $error);
    }
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $redirectUrl = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
    curl_close($ch);
    if ($status === 301 || $status === 302) {
        throw new Exception("Thruk rejected the API key and redirected to: " .
            ($redirectUrl ? $redirectUrl : "(unknown)") .
            ". Check that the THURUK_API_KEY is valid (regenerate it from the Thruk user profile if needed).");
    }
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

function aiRequest($prompt, $systemPrompt) {
    $payload = [
        "model" => AI_MODEL,
        "messages" => [
            ["role" => "system", "content" => $systemPrompt],
            ["role" => "user", "content" => $prompt],
        ],
        "temperature" => 0.15,
        "max_tokens" => 3000,
        "top_k" => 20,
        "top_p" => 0.8,
        "chat_template_kwargs" => ["enable_thinking" => false],
        "repetition_penalty" => 1.00,
        "stream" => false,
    ];
    $ch = curl_init(AI_URL);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_TIMEOUT => 120,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            "Accept: application/json",
            "Content-Type: application/json",
            "Authorization: Bearer " . envValue(AI_TOKEN_ENV),
        ],
    ]);
    $body = curl_exec($ch);
    if ($body === false) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new Exception("AI request failed: " . $error);
    }
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($status !== 200) {
        throw new Exception("AI returned HTTP " . $status);
    }
    $data = json_decode($body, true);
    if (!isset($data["choices"][0]["message"]["content"])) {
        throw new Exception("AI returned an unexpected response");
    }
    return $data["choices"][0]["message"]["content"];
}

function nocSummary($dataText) {
    $cacheFile = cacheDir() . "/noc_summary_" . md5($dataText) . ".html";
    if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < NOC_CACHE_TTL) {
        return file_get_contents($cacheFile);
    }
    $systemPrompt = "You are a NOC duty analyst briefing a non-technical client on server monitoring. " .
        "Summarise the overall health of the servers based on the monitoring data provided, in plain English. " .
        "Clearly call out anything that needs URGENT attention or CLOSE monitoring, with a short reason for each. " .
        "If everything is healthy, say so reassuringly. " .
        "Important instructions: 1. Limit your response to a few short paragraphs. " .
        "2. Format your output in HTML complete with URLs. " .
        "3. Use language like you are a technical support person talking to a client.";
    $summary = aiRequest("Summarise the current monitoring state of these servers:\n\n" . $dataText, $systemPrompt);
    file_put_contents($cacheFile, $summary);
    return $summary;
}

function formatDurationText($seconds) {
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

$nocError = null;
$hostStatus = [];
$hostInfo = [];
$hostCounts = [];
$nocDataText = "";
$now = time();
set_time_limit(90);

try {
    $nameRegex = "^(" . implode("|", array_map("preg_quote", SERVER_LIST)) . ")$";

    $allHosts = thrukGet("hosts?columns=name,address,state,plugin_output,last_state_change&name[regex]=" . urlencode($nameRegex));
    foreach ($allHosts as $h) {
        $name = isset($h["name"]) ? $h["name"] : "";
        if (!in_array($name, SERVER_LIST, true)) {
            continue;
        }
        $state = (int)(isset($h["state"]) ? $h["state"] : -1);
        $hostInfo[$name] = [
            "state" => $state,
            "address" => isset($h["address"]) ? $h["address"] : "",
            "output" => isset($h["plugin_output"]) ? $h["plugin_output"] : "",
            "duration" => $now - (isset($h["last_state_change"]) ? $h["last_state_change"] : $now),
        ];
        $hostStatus[$name] = "green";
        $hostCounts[$name] = ["ok" => 0, "warn" => 0, "crit" => 0, "unknown" => 0];
        if ($state === 1 || $state === 2) {
            $hostStatus[$name] = "red";
        }
    }

    $allServices = thrukGet("services?columns=host_name,description,state,plugin_output,last_state_change&host_name[regex]=" . urlencode($nameRegex));
    foreach ($allServices as $s) {
        $h = isset($s["host_name"]) ? $s["host_name"] : "";
        if (!in_array($h, SERVER_LIST, true)) {
            continue;
        }
        $state = (int)(isset($s["state"]) ? $s["state"] : -1);
        if (!isset($hostStatus[$h])) {
            $hostStatus[$h] = "green";
            $hostCounts[$h] = ["ok" => 0, "warn" => 0, "crit" => 0, "unknown" => 0];
        }
        if ($state === 2) {
            $hostStatus[$h] = "red";
            $hostCounts[$h]["crit"]++;
        } elseif ($state === 1) {
            if ($hostStatus[$h] !== "red") {
                $hostStatus[$h] = "yellow";
            }
            $hostCounts[$h]["warn"]++;
        } elseif ($state === 3) {
            if ($hostStatus[$h] !== "red") {
                $hostStatus[$h] = "yellow";
            }
            $hostCounts[$h]["unknown"]++;
        } else {
            $hostCounts[$h]["ok"]++;
        }
        $hostInfo[$h]["services"][] = [
            "description" => isset($s["description"]) ? $s["description"] : "?",
            "state" => $state,
            "output" => isset($s["plugin_output"]) ? $s["plugin_output"] : "",
            "duration" => $now - (isset($s["last_state_change"]) ? $s["last_state_change"] : $now),
        ];
    }

    foreach (SERVER_LIST as $name) {
        $info = isset($hostInfo[$name]) ? $hostInfo[$name] : null;
        $svcs = (isset($info["services"]) && is_array($info["services"])) ? $info["services"] : [];
        if (!$info && count($svcs) === 0) {
            continue;
        }
        $nocDataText .= "HOST " . $name;
        if ($info) {
            $nocDataText .= ": " . hostStateLabel($info["state"])[0] .
                " for " . formatDurationText($info["duration"]) .
                " (address " . $info["address"] . ", check: " . substr($info["output"], 0, 120) . ")";
        } else {
            $nocDataText .= ": no host check data";
        }
        $nocDataText .= "\n";
        foreach ($svcs as $svc) {
            $stateText = "OK";
            if ($svc["state"] === 1) $stateText = "WARNING";
            if ($svc["state"] === 2) $stateText = "CRITICAL";
            if ($svc["state"] === 3) $stateText = "UNKNOWN";
            $nocDataText .= "  - " . $svc["description"] . ": " . $stateText .
                " for " . formatDurationText($svc["duration"]) .
                " (" . substr($svc["output"], 0, 120) . ")\n";
        }
        $nocDataText .= "\n";
    }
} catch (Exception $e) {
    $nocError = $e->getMessage();
}

if (isset($_GET["action"]) && $_GET["action"] === "noc_summary") {
    header("Content-Type: application/json");
    try {
        if ($nocError !== null || $nocDataText === "") {
            throw new Exception("NOC data is not available");
        }
        echo json_encode(["success" => true, "summary" => nocSummary($nocDataText)]);
    } catch (Exception $e) {
        echo json_encode(["success" => false, "error" => $e->getMessage()]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NOC Summary</title>
    <style>
        body {
            margin: 0;
            background: #0a0a12;
            font-family: Arial, sans-serif;
            color: #eee;
        }

        .layout { display: flex; min-height: 100vh; }

        .sidebar {
            width: 230px;
            flex-shrink: 0;
            background: rgba(255, 255, 255, 0.03);
            border-right: 2px solid rgba(0, 240, 255, 0.2);
            padding: 30px 0;
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            overflow-y: auto;
        }

        .sidebar h3 {
            margin: 0 20px 20px 20px;
            font-size: 14px;
            letter-spacing: 3px;
            text-transform: uppercase;
            color: #00f0ff;
            text-shadow: 0 0 10px rgba(0, 240, 255, 0.6);
        }

        .sidebar a {
            display: block;
            padding: 10px 20px;
            color: #bbb;
            text-decoration: none;
            font-size: 14px;
            border-left: 3px solid transparent;
            transition: background 0.2s, color 0.2s, border-color 0.2s;
        }

        .sidebar a:hover { background: rgba(0, 240, 255, 0.08); color: #fff; }

        .sidebar a.status-red { color: #ff5a5a; }
        .sidebar a.status-yellow { color: #ffd75a; }
        .sidebar a.status-green { color: #5aff8a; }

        .sidebar-divider {
            margin: 20px;
            border-top: 1px solid rgba(0, 240, 255, 0.25);
        }

        .sidebar a.sidebar-jira { color: #ff00e6; font-weight: bold; }
        .sidebar a.sidebar-jira:hover { color: #ff5af0; }
        .sidebar a.sidebar-jira.active {
            background: rgba(255, 0, 230, 0.12);
            border-left: 3px solid #ff00e6;
            box-shadow: inset 0 0 15px rgba(255, 0, 230, 0.15);
        }

        .main {
            flex: 1;
            margin-left: 232px;
            padding: 40px 30px;
        }

        .container { max-width: 1000px; margin: 0 auto; }

        h1 {
            text-align: center;
            font-size: 30px;
            font-weight: 900;
            letter-spacing: 3px;
            text-transform: uppercase;
            color: #fff;
            text-shadow: 0 0 10px #00f0ff, 0 0 30px #00f0ff;
        }

        .subtitle { text-align: center; color: #888; font-size: 14px; margin-bottom: 35px; }

        .noc-card {
            border: 2px solid #00f0ff;
            border-radius: 12px;
            padding: 25px 30px;
            background: rgba(0, 240, 255, 0.05);
            box-shadow: 0 0 15px rgba(0, 240, 255, 0.4);
            margin-bottom: 35px;
        }

        .noc-card h2 { margin: 0 0 15px 0; color: #fff; font-size: 22px; letter-spacing: 2px; }

        .ai-summary p { margin: 8px 0; line-height: 1.6; font-size: 15px; color: #ddd; }
        .ai-summary a { color: #00f0ff; }

        .host-overview {
            width: 100%;
            border-collapse: collapse;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 35px;
        }

        .host-overview th {
            background: rgba(0, 240, 255, 0.08);
            color: #00f0ff;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-size: 13px;
            padding: 14px 16px;
            text-align: left;
        }

        .host-overview td {
            padding: 12px 16px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            font-size: 15px;
        }

        .host-overview tr.crit td { background: rgba(255, 0, 0, 0.08); }
        .host-overview tr.warn td { background: rgba(255, 200, 0, 0.05); }

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

        .host-name { font-weight: bold; color: #fff; }
        .count-ok { color: #5aff8a; }
        .count-warn { color: #ffd75a; }
        .count-crit { color: #ff5a5a; }
        .count-unknown { color: #aaa; }

        .error-box {
            border: 2px solid #ff5a5a;
            border-radius: 10px;
            padding: 20px 30px;
            text-align: center;
            color: #ff5a5a;
            font-size: 17px;
            box-shadow: 0 0 15px rgba(255, 90, 90, 0.4);
            margin: 20px 0;
        }

        .updated { text-align: center; color: #666; font-size: 12px; margin-top: 25px; }
    </style>
</head>
<body>
    <div class="layout">
        <div class="sidebar">
            <h3>Summary</h3>
            <a href="jira.php" class="sidebar-jira active">&raquo; NOC Summary</a>

            <div class="sidebar-divider"></div>
            <h3>Servers</h3>
            <?php foreach (SERVER_LIST as $name):
                $statusClassSidebar = isset($hostStatus[$name]) ? " status-" . $hostStatus[$name] : "";
            ?>
                <a href="<?php echo htmlspecialchars($name); ?>.php" class="<?php echo $statusClassSidebar; ?>">
                    <?php echo htmlspecialchars($name); ?>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="main">
            <div class="container">
                <h1>NOC Summary</h1>
                <div class="subtitle">AI overview of all monitored servers</div>

                <?php if ($nocError): ?>
                    <div class="error-box"><?php echo htmlspecialchars($nocError); ?></div>
                <?php else: ?>
                    <div class="noc-card">
                        <h2>Monitoring Summary</h2>
                        <div class="ai-summary" id="noc-summary">
                            <p>Asking the AI to read the current monitoring data, this may take a moment...</p>
                        </div>
                    </div>

                    <table class="host-overview">
                        <thead>
                            <tr>
                                <th>Host</th>
                                <th>Status</th>
                                <th>OK</th>
                                <th>Warning</th>
                                <th>Critical</th>
                                <th>Unknown</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (SERVER_LIST as $name):
                                $info = isset($hostInfo[$name]) ? $hostInfo[$name] : null;
                                $counts = isset($hostCounts[$name]) ? $hostCounts[$name] : ["ok" => 0, "warn" => 0, "crit" => 0, "unknown" => 0];
                                if (!$info) continue;
                                list($label, $class) = hostStateLabel($info["state"]);
                                $rowClass = $hostStatus[$name] === "red" ? "crit" : ($hostStatus[$name] === "yellow" ? "warn" : "");
                            ?>
                            <tr class="<?php echo $rowClass; ?>">
                                <td class="host-name"><?php echo htmlspecialchars($name); ?></td>
                                <td><span class="badge <?php echo $class; ?>"><?php echo $label; ?></span></td>
                                <td class="count-ok"><?php echo $counts["ok"]; ?></td>
                                <td class="count-warn"><?php echo $counts["warn"]; ?></td>
                                <td class="count-crit"><?php echo $counts["crit"]; ?></td>
                                <td class="count-unknown"><?php echo $counts["unknown"]; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>

                <div class="updated">Last updated <?php echo date("H:i:s"); ?></div>
            </div>
        </div>
    </div>

    <script>
        const nocSummaryBox = document.getElementById("noc-summary");

        <?php if (!$nocError && $nocDataText !== ""): ?>
        fetch("jira.php?action=noc_summary")
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    nocSummaryBox.innerHTML = data.summary;
                } else {
                    nocSummaryBox.innerHTML = "<p>Could not generate the monitoring summary: " + data.error + "</p>";
                }
            })
            .catch(() => {
                nocSummaryBox.innerHTML = "<p>Could not generate the monitoring summary.</p>";
            });
        <?php endif; ?>
    </script>
</body>
</html>
