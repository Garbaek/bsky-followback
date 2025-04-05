<?php session_start(); ?>
<!DOCTYPE html>
<html>
<head>
  <title>Bluesky Auto Follow-Back</title>
  <meta property="og:title" content="Bluesky Auto Follow-Back Tool" />
  <meta property="og:description" content="Automate your follow-backs on Bluesky! A privacy-focused, browser-based tool built in PHP. No data stored. by @Ga>
  <meta property="og:url" content="https://garbaek.dk/bsky-followback" />
  <meta property="og:type" content="website" />
  <meta property="og:image" content="https://garbaek.dk/bsky-followback/preview.png" />

  <style>
    body { font-family: sans-serif; max-width: 600px; margin: 40px auto; background: #f7f7f7; padding: 20px; }
    input, button { width: 100%; padding: 10px; margin: 6px 0; }
    .log-box { background: #fff; padding: 15px; margin-top: 20px; border-radius: 6px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
    .footer { font-size: 0.9em; margin-top: 40px; color: #888; text-align: center; }
    .countdown { font-weight: bold; margin-top: 10px; text-align: center; font-size: 1.2em; color: #444; }
    .privacy-note { font-size: 0.9em; color: #555; margin-top: 20px; background: #eef; padding: 10px; border-left: 5px solid #88c; }
  </style>
  <?php if (isset($_SESSION['auth'])): ?>
    <meta http-equiv="refresh" content="60">
    <script>
      let seconds = 60;
      function updateCountdown() {
        if (seconds >= 0) {
          document.getElementById("countdown").innerText = `⏳ Next check in ${seconds} second${seconds !== 1 ? 's' : ''}...`;
          seconds--;
          setTimeout(updateCountdown, 1000);
        }
      }
      window.onload = updateCountdown;
    </script>
  <?php endif; ?>
</head>
<body>

  <h2>Bluesky Auto Follow-Back</h2>

  <form action="" method="POST">
    <input type="text" name="handle" placeholder="Handle (e.g. yourname.bsky.social)" required>
    <input type="password" name="app_password" placeholder="App Password" required>
    <input type="text" name="pds" placeholder="Custom PDS (default: https://bsky.social)">
    <button type="submit">Start Session</button>
  </form>

  <?php if (isset($_SESSION['auth'])): ?>
    <div class="countdown" id="countdown">⏳ Starting countdown...</div>
  <?php endif; ?>

  <div class="privacy-note">
    🔐 <strong>Privacy Notice:</strong> No usernames, passwords, or session data is stored on this server.<br>
    All processing happens in memory while this page is open in your browser. Closing the tab ends everything.
  </div>

  <div class="log-box">
    <?php
    function get_base_url() {
        return $_SESSION['pds'] ?? 'https://bsky.social';
    }

    function api_post($endpoint, $data, $auth = null) {
        $url = get_base_url() . "/xrpc/$endpoint";
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge([
            'Content-Type: application/json'
        ], $auth ? ["Authorization: Bearer $auth"] : []));
        $result = curl_exec($ch);
        return json_decode($result, true);
    }
    function api_get($endpoint, $auth) {
        $url = get_base_url() . "/xrpc/$endpoint";
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $auth"]);
        $result = curl_exec($ch);
        return json_decode($result, true);
    }

    // Login logic
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $handle = $_POST['handle'];
        $password = $_POST['app_password'];
        $pds = trim($_POST['pds']);

        if (!empty($pds)) {
            if (!str_starts_with($pds, 'http')) {
                $pds = 'https://' . $pds;
            }
            $_SESSION['pds'] = rtrim($pds, '/');
        } else {
            $_SESSION['pds'] = 'https://bsky.social';
        }

        $login = api_post("com.atproto.server.createSession", [
            'identifier' => $handle,
            'password' => $password
        ]);

        if (isset($login['accessJwt'])) {
            $_SESSION['auth'] = $login['accessJwt'];
            $_SESSION['did'] = $login['did'];
            $_SESSION['handle'] = $handle;
            echo "<p>✅ Logged in as <strong>{$handle}</strong> via <code>{$_SESSION['pds']}</code>.</p>";
        } else {
            echo "<p>❌ Login failed. Check credentials or PDS endpoint.</p>";
            session_destroy();
            exit;
        }
    }

    // Follow-back logic if logged in
    if (isset($_SESSION['auth'])) {
        $followers = api_get("app.bsky.graph.getFollowers?actor=" . urlencode($_SESSION['did']) . "&limit=100", $_SESSION['auth']);
        $following = api_get("app.bsky.graph.getFollows?actor=" . urlencode($_SESSION['did']) . "&limit=100", $_SESSION['auth']);

        $follower_dids = array_column($followers['followers'] ?? [], 'did');
        $following_dids = array_column($following['follows'] ?? [], 'did');

        $to_follow = array_slice(array_diff($follower_dids, $following_dids), 0, 10);

        foreach ($to_follow as $did) {
            $res = api_post("com.atproto.repo.createRecord", [
                'repo' => $_SESSION['did'],
                'collection' => 'app.bsky.graph.follow',
                'record' => [
                    '$type' => 'app.bsky.graph.follow',
                    'subject' => $did,
                    'createdAt' => gmdate('Y-m-d\TH:i:s\Z')
                ]
            ], $_SESSION['auth']);

            if (isset($res['uri'])) {
                echo "<p>✅ Followed <code>$did</code></p>";
            } else {
                echo "<p>⚠️ Failed to follow <code>$did</code></p>";
            }
        }

        if (empty($to_follow)) {
            echo "<p>👍 No new followers to follow back.</p>";
        }
    }
    ?>
  </div>

<div class="footer">
  Made by <a href="https://bsky.app/profile/garbaek.dk" target="_blank">@Garbaek</a> · <a href="https://garbaek.dk" target="_blank">garbaek.dk</a>
</div>
</body>
</html>
