<?php
session_start();

// 🔐 User Account
$USERS = [
    "admin" => "admin",
    "User" => "User1",
    "User" => "User12",
    "User" => "User123"
];  

// ⚙️ Servers
$servers = [
    "Survival" => ["ip" => "127.0.0.1", "port" => 8080, "pass" => "password1"],
    "Creative" => ["ip" => "127.0.0.1", "port" => 8081, "pass" => "password2"]
];

// Login
if (isset($_POST['login'])) {
    $user = $_POST['user'] ?? '';
    $pass = $_POST['pass'] ?? '';
    if (isset($USERS[$user]) && $USERS[$user] === $pass) {
        $_SESSION['loggedin'] = true;
        $_SESSION['username'] = $user;
    } else {
        $error = "Check username and password!";
    }
}

// Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: ?");
    exit;
}

// AMP API Communication 
function amp_api($ip, $port, $password, $endpoint, $args = []) {
    $data = json_encode([
        "password" => $password,
        "request" => $endpoint,
        "arguments" => $args
    ]);
    $c = curl_init("http://$ip:$port/API");
    curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($c, CURLOPT_POSTFIELDS, $data);
    curl_setopt($c, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    return json_decode(curl_exec($c), true);
}

// Chat
$chatFile = "chat.txt";
if (isset($_POST['sendchat']) && isset($_SESSION['loggedin'])) {
    $name = $_SESSION['username'] ?? 'Unknown';
    $msg = trim($_POST['message'] ?? '');
    if ($msg !== '') {
        $entry = date("H:i") . " <strong>" . htmlspecialchars($name) . ":</strong> " . htmlspecialchars($msg) . "<br>\n";
        file_put_contents($chatFile, $entry, FILE_APPEND);
    }
    exit;
}

if (isset($_GET['loadchat']) && isset($_SESSION['loggedin'])) {
    if (file_exists($chatFile)) {
        echo file_get_contents($chatFile);
    }
    exit;
}

$page = $_GET['page'] ?? 'amp';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>AMP Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-dark text-light p-4">
<?php if (!isset($_SESSION['loggedin'])): ?>
<h2>Login</h2>
<?php if (!empty($error)): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
<form method="post">
    <input class="form-control my-2" name="user" placeholder="Username" required>
    <input class="form-control my-2" type="password" name="pass" placeholder="Password" required>
    <button class="btn btn-primary" name="login">Login</button>
</form>
<?php else: ?>
<a href="?logout=1" class="btn btn-danger mb-3">Logout (<?= htmlspecialchars($_SESSION['username']) ?>)</a>
<?php if ($page === 'amp'): ?>
<h2>AMP Dashboard</h2>
<p>AMP section ready, active only when the AMP server is running.</p>
<?php elseif ($page === 'chat'): ?>
<h2>User Chat</h2>
<div id="chatbox" style="background:#222; padding:10px; height:300px; overflow-y:scroll; border:1px solid #555;" class="mb-2"></div>
<form onsubmit="sendChat(event)" class="d-flex gap-2">
    <input type="text" id="chatmsg" class="form-control" placeholder="Type a message..." required>
    <button type="submit" class="btn btn-success">Send</button>
</form>
<?php endif; ?>
<?php endif; ?>

<script>
function sendChat(e) {
    e.preventDefault();
    const msg = document.getElementById("chatmsg").value;
    fetch("", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({
            sendchat: "1",
            message: msg
        })
    }).then(() => {
        document.getElementById("chatmsg").value = "";
    });
}
if (window.location.search.includes("page=chat")) {
    const chatbox = document.getElementById("chatbox");
    function loadChat() {
        fetch("?loadchat=1")
            .then(res => res.text())
            .then(data => {
                chatbox.innerHTML = data;
                chatbox.scrollTop = chatbox.scrollHeight;
            });
    }
    setInterval(loadChat, 2000);
    loadChat();
}
</script>
</body>
</html>