<?php
const JIRA_BASE_URL = "https://options-it.atlassian.net";
const JIRA_JQL = 'project = "DISRUPT" ORDER BY created DESC';
const MAX_TICKETS = 50;
const JIRA_EMAIL_ENV = "JIRA_EMAIL";
const JIRA_TOKEN_ENV = "JIRA_API_TOKEN";
const AI_URL = "https://api.privatemind.com/v1/chat/completions";
const AI_MODEL = "reasoning";
const AI_TOKEN_ENV = "AI_API_KEY";
const CACHE_TTL = 21600;
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

function jiraRequest($path, $method = "GET", $payload = null) {
    $auth = base64(envValue(JIRA_EMAIL_ENV) . ":" . envValue(JIRA_TOKEN_ENV));
    $ch = curl_init(JIRA_BASE_URL . $path);
    $options = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_HTTPHEADER => [
            "Accept: application/json",
            "Content-Type: application/json",
            "Authorization: Basic " . $auth,
        ],
    ];
    if ($method === "POST") {
        $options[CURLOPT_POST] = true;
        $options[CURLOPT_POSTFIELDS] = json_encode($payload);
    }
    curl_setopt_array($ch, $options);
    $body = curl_exec($ch);
    if ($body === false) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new Exception("JIRA request failed: " . $error);
    }
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($status !== 200) {
        throw new Exception("JIRA returned HTTP " . $status);
    }
    $data = json_decode($body, true);
    if (!is_array($data)) {
        throw new Exception("JIRA returned invalid data");
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

function summariseTicket($key) {
    $commentsData = jiraRequest("/rest/api/2/issue/" . urlencode($key) . "/comment?maxResults=100&orderBy=created");
    $comments = isset($commentsData["comments"]) ? $commentsData["comments"] : [];
    $latest = 0;
    $lines = [];
    foreach ($comments as $c) {
        $created = isset($c["created"]) ? strtotime($c["created"]) : 0;
        if ($created > $latest) {
            $latest = $created;
        }
        $author = isset($c["author"]["displayName"]) ? $c["author"]["displayName"] : "Unknown";
        $lines[] = $author . " (" . (isset($c["created"]) ? $c["created"] : "?") . "): " .
            (isset($c["body"]) ? $c["body"] : "");
    }

    if (count($lines) === 0) {
        return "<p>There are no comments on this ticket yet.</p>";
    }

    $cacheFile = cacheDir() . "/summary_" . md5($key) . "_" . md5($latest) . ".html";
    if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < CACHE_TTL) {
        return file_get_contents($cacheFile);
    }

    $systemPrompt = "You are a technical support person talking to a client. " .
        "Summarise the JIRA ticket comments provided in plain English, avoiding technical jargon. " .
        "Then add a clearly labelled 'Recommended steps' section with practical next actions. " .
        "Important instructions: 1. Limit your response to a few short paragraphs. " .
        "2. Format your output in HTML complete with URLs. " .
        "3. Use language like you are a technical support person talking to a client.";
    $prompt = "Summarise the comments on JIRA ticket " . $key . " (Disruption Tracker).\n\nComments:\n" . implode("\n\n", $lines);
    $summary = aiRequest($prompt, $systemPrompt);
    file_put_contents($cacheFile, $summary);
    return $summary;
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

if (isset($_GET["action"]) && $_GET["action"] === "summary" && isset($_GET["key"])) {
    header("Content-Type: application/json");
    try {
        $key = $_GET["key"];
        if (!preg_match('/^[A-Z][A-Z0-9]+-\d+$/', $key)) {
            throw new Exception("Invalid ticket key");
        }
        echo json_encode(["success" => true, "summary" => summariseTicket($key)]);
    } catch (Exception $e) {
        echo json_encode(["success" => false, "error" => $e->getMessage()]);
    }
    exit;
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

function hostStateText($state) {
    switch ((int)$state) {
        case 0: return "UP";
        case 1: return "DOWN";
        case 2: return "UNREACHABLE";
        default: return "UNKNOWN";
    }
}

function serviceStateText($state) {
    switch ((int)$state) {
        case 0: return "OK";
        case 1: return "WARNING";
        case 2: return "CRITICAL";
        default: return "UNKNOWN";
    }
}

$error = null;
$tickets = [];
$nocError = null;
$hostStatus = [];
$hostCounts = [];
$nocDataText = "";
$now = time();

try {
    $result = jiraRequest("/rest/api/2/search", "POST", [
        "jql" => JIRA_JQL,
        "maxResults" => MAX_TICKETS,
        "fields" => ["summary", "description", "status", "created"],
    ]);
    $tickets = isset($result["issues"]) ? $result["issues"] : [];
} catch (Exception $e) {
    $error = $e->getMessage();
}

try {
    $nameRegex = "^(" . implode("|", array_map("preg_quote", SERVER_LIST)) . ")$";

    $allHosts = thrukGet("hosts?columns=name,address,state,plugin_output,last_state_change&name[regex]=" . urlencode($nameRegex));
    $hostInfo = [];
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
    $servicesByHost = [];
    foreach ($allServices as $s) {
        $h = isset($s["host_name"]) ? $s["host_name"] : "";
        if (!in_array($h, SERVER_LIST, true)) {
            continue;
        }
        $state = (int)(isset($s["state"]) ? $s["state"] : -1);
        $servicesByHost[$h][] = [
            "description" => isset($s["description"]) ? $s["description"] : "?",
            "state" => $state,
            "output" => isset($s["plugin_output"]) ? $s["plugin_output"] : "",
            "duration" => $now - (isset($s["last_state_change"]) ? $s["last_state_change"] : $now),
        ];
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
    }

    foreach (SERVER_LIST as $name) {
        $info = isset($hostInfo[$name]) ? $hostInfo[$name] : null;
        $svcs = isset($servicesByHost[$name]) ? $servicesByHost[$name] : [];
        if (!$info && count($svcs) === 0) {
            continue;
        }
        $nocDataText .= "HOST " . $name;
        if ($info) {
            $nocDataText .= ": " . hostStateText($info["state"]) .
                " for " . formatDurationText($info["duration"]) .
                " (address " . $info["address"] . ", check: " . substr($info["output"], 0, 120) . ")";
        } else {
            $nocDataText .= ": no host check data";
        }
        $nocDataText .= "\n";
        foreach ($svcs as $svc) {
            $nocDataText .= "  - " . $svc["description"] . ": " . serviceStateText($svc["state"]) .
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

function statusClass($status) {
    $s = strtolower($status);
    if (strpos($s, "done") !== false || strpos($s, "resolved") !== false || strpos($s, "closed") !== false || strpos($s, "complete") !== false) return "ok";
    if (strpos($s, "block") !== false || strpos($s, "critical") !== false || strpos($s, "reject") !== false) return "crit";
    if (strpos($s, "progress") !== false || strpos($s, "review") !== false || strpos($s, "pending") !== false || strpos($s, "hold") !== false) return "warn";
    return "unknown";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Disruption Tracker &amp; NOC Overview</title>
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

        h2.section {
            font-size: 20px;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: #00f0ff;
            text-shadow: 0 0 10px rgba(0, 240, 255, 0.5);
            margin: 40px 0 20px 0;
        }

        .subtitle { text-align: center; color: #888; font-size: 14px; margin-bottom: 35px; }

        .noc-card {
            border: 2px solid #00f0ff;
            border-radius: 12px;
            padding: 25px 30px;
            background: rgba(0, 240, 255, 0.05);
            box-shadow: 0 0 15px rgba(0, 240, 255, 0.4);
        }

        .noc-card h2 { margin: 0 0 15px 0; color: #fff; font-size: 22px; letter-spacing: 2px; }

        .ai-summary p { margin: 8px 0; line-height: 1.6; font-size: 15px; color: #ddd; }
        .ai-summary a { color: #00f0ff; }

        .ticket {
            border: 2px solid #333;
            border-radius: 12px;
            padding: 20px 25px;
            margin: 15px 0;
            background: rgba(255, 255, 255, 0.03);
        }

        .ticket-top { display: flex; align-items: center; gap: 15px; flex-wrap: wrap; }
        .ticket-key { color: #00f0ff; font-weight: 900; font-size: 16px; }
        .ticket-summary { color: #fff; font-weight: bold; font-size: 17px; flex: 1; }
        .ticket-created { color: #666; font-size: 12px; }

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

        .ticket-desc { color: #bbb; font-size: 14px; margin: 12px 0 0 0; line-height: 1.5; }

        .summary-btn {
            margin-top: 15px;
            padding: 8px 20px;
            background: rgba(0, 240, 255, 0.07);
            border: 2px solid #00f0ff;
            border-radius: 8px;
            color: #fff;
            font-size: 13px;
            font-weight: bold;
            cursor: pointer;
            box-shadow: 0 0 10px rgba(0, 240, 255, 0.3);
        }

        .summary-btn:disabled { opacity: 0.5; cursor: wait; }
        .summary-btn:hover:not(:disabled) { box-shadow: 0 0 20px rgba(0, 240, 255, 0.6); }

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
            <a href="jira.php" class="sidebar-jira active">&raquo; Disruption Tracker</a>

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
                <h1>NOC &amp; Disruption Tracker</h1>
                <div class="subtitle">Server monitoring overview and the 50 newest DISRUPT tickets</div>

                <div class="noc-card">
                    <h2>Monitoring Summary</h2>
                    <div class="ai-summary" id="noc-summary">
                        <?php if ($nocError): ?>
                            <p>NOC data unavailable: <?php echo htmlspecialchars($nocError); ?></p>
                        <?php else: ?>
                            <p>Asking the AI to read the current monitoring data, this may take a moment...</p>
                        <?php endif; ?>
                    </div>
                </div>

                <h2 class="section">Newest DISRUPT Tickets</h2>

                <?php if ($error): ?>
                    <div class="error-box"><?php echo htmlspecialchars($error); ?></div>
                <?php else: ?>
                    <?php foreach ($tickets as $t):
                        $key = isset($t["key"]) ? $t["key"] : "?";
                        $fields = isset($t["fields"]) ? $t["fields"] : [];
                        $summary = isset($fields["summary"]) ? $fields["summary"] : "";
                        $description = isset($fields["description"]) ? $fields["description"] : "";
                        $statusName = isset($fields["status"]["name"]) ? $fields["status"]["name"] : "UNKNOWN";
                        $created = isset($fields["created"]) ? date("d M Y H:i", strtotime($fields["created"])) : "?";
                        $class = statusClass($statusName);
                    ?>
                        <div class="ticket" id="ticket-<?php echo htmlspecialchars($key); ?>">
                            <div class="ticket-top">
                                <span class="ticket-key"><?php echo htmlspecialchars($key); ?></span>
                                <span class="ticket-summary"><?php echo htmlspecialchars($summary); ?></span>
                                <span class="badge <?php echo $class; ?>"><?php echo htmlspecialchars($statusName); ?></span>
                                <span class="ticket-created"><?php echo htmlspecialchars($created); ?></span>
                            </div>
                            <?php if ($description !== ""): ?>
                                <div class="ticket-desc"><?php echo nl2br(htmlspecialchars($description)); ?></div>
                            <?php endif; ?>
                            <button class="summary-btn" data-key="<?php echo htmlspecialchars($key); ?>">AI Summary &amp; Recommended Steps</button>
                            <div class="ai-summary" style="display:none;"></div>
                        </div>
                    <?php endforeach; ?>
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

        document.querySelectorAll(".summary-btn").forEach(btn => {
            btn.addEventListener("click", () => {
                const key = btn.getAttribute("data-key");
                const box = document.getElementById("ticket-" + key).querySelector(".ai-summary");
                btn.disabled = true;
                btn.textContent = "Generating summary...";
                box.style.display = "block";
                box.textContent = "Asking the AI to read the ticket comments, this may take a moment...";

                fetch("jira.php?action=summary&key=" + encodeURIComponent(key))
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            box.innerHTML = data.summary;
                            btn.textContent = "Refresh summary";
                        } else {
                            box.style.display = "none";
                            btn.textContent = "Retry";
                            box.insertAdjacentHTML("afterend", '<div class="ai-error">' + data.error + '</div>');
                        }
                    })
                    .catch(() => {
                        box.style.display = "none";
                        btn.textContent = "Retry";
                    })
                    .finally(() => {
                        btn.disabled = false;
                    });
            });
        });
    </script>
</body>
</html>
