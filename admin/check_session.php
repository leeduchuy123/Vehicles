<?php
// This file is for debugging session issues

// Start session
session_start();

// Output session information
header('Content-Type: text/plain');

echo "Session Information:\n";
echo "-------------------\n";
echo "Session ID: " . session_id() . "\n\n";

echo "Session Variables:\n";
if (empty($_SESSION)) {
    echo "No session variables set.\n";
} else {
    foreach ($_SESSION as $key => $value) {
        echo "$key: " . (is_array($value) ? json_encode($value) : $value) . "\n";
    }
}

echo "\n\nCookies:\n";
if (empty($_COOKIE)) {
    echo "No cookies set.\n";
} else {
    foreach ($_COOKIE as $key => $value) {
        echo "$key: $value\n";
    }
}

echo "\n\nPHP Session Configuration:\n";
echo "session.save_path: " . ini_get('session.save_path') . "\n";
echo "session.name: " . ini_get('session.name') . "\n";
echo "session.cookie_lifetime: " . ini_get('session.cookie_lifetime') . "\n";
echo "session.cookie_path: " . ini_get('session.cookie_path') . "\n";
echo "session.cookie_domain: " . ini_get('session.cookie_domain') . "\n";
echo "session.cookie_secure: " . ini_get('session.cookie_secure') . "\n";
echo "session.cookie_httponly: " . ini_get('session.cookie_httponly') . "\n";
echo "session.use_cookies: " . ini_get('session.use_cookies') . "\n";
echo "session.use_only_cookies: " . ini_get('session.use_only_cookies') . "\n";
?>
