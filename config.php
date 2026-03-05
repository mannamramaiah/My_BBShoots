<?php
// ============================================
// BBShoots — config.php  (FIXED v2)
// ============================================

define('DB_HOST', 'localhost');
define('DB_NAME', 'bbshoots');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_PORT', 3306);

define('ADMIN_EMAIL',    'bbshoots49@gmail.com');
define('ADMIN_PASSWORD', 'BBShoots@2025');

define('MAIL_HOST',      'smtp.gmail.com');
define('MAIL_PORT',      587);
define('MAIL_USERNAME',  'bbshoots49@gmail.com');
define('MAIL_PASSWORD',  'YOUR_GMAIL_APP_PASSWORD');
define('MAIL_FROM',      'bbshoots49@gmail.com');
define('MAIL_FROM_NAME', 'BBShoots Productions');

define('APP_URL', 'http://localhost/bbshoots');

// ── Session FIRST — before any headers ───────
// Must start session before sending any output
if (session_status() === PHP_SESSION_NONE) {
    // Fix: use specific cookie settings so session persists across requests
    session_name('BBSHOOTS');
    ini_set('session.cookie_path',     '/');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.gc_maxlifetime',  '86400'); // 24 hours
    session_set_cookie_params([
        'lifetime' => 86400,
        'path'     => '/',
        'domain'   => 'localhost',
        'secure'   => false,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// ── CORS Headers — MUST allow credentials ────
// Cannot use * with credentials=true — use exact origin
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : 'http://localhost';

// Allow localhost on any port
if (preg_match('#^https?://localhost(:\d+)?$#', $origin)) {
    header('Access-Control-Allow-Origin: ' . $origin);
} else {
    header('Access-Control-Allow-Origin: http://localhost');
}

header('Access-Control-Allow-Credentials: true');   // ← THIS is what was missing
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ── DB Connection ─────────────────────────────
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT
                 . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            resp(false, null, 'Database error: ' . $e->getMessage(), 500);
        }
    }
    return $pdo;
}

// ── Helpers ───────────────────────────────────
function resp(bool $ok, $data = null, string $msg = '', int $code = 200): void {
    http_response_code($code);
    echo json_encode(['success' => $ok, 'data' => $data, 'error' => $msg]);
    exit();
}

function body(): array {
    $raw  = file_get_contents('php://input');
    $json = json_decode($raw, true);
    return is_array($json) ? $json : (is_array($_POST) ? $_POST : []);
}

function generateRef(): string {
    $db    = getDB();
    $year  = date('Y');
    $count = (int)$db->query("SELECT COUNT(*) FROM bookings WHERE YEAR(created_at)=$year")->fetchColumn() + 1;
    return 'BK-' . $year . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);
}

function addNotif(string $type, string $msg): void {
    $db = getDB();
    $db->prepare("INSERT INTO notifications(type,message) VALUES(?,?)")->execute([$type, $msg]);
}

function requireAdmin(): void {
    if (empty($_SESSION['admin'])) {
        resp(false, null, 'Admin access required. Please login again.', 401);
    }
}

function requireClient(): void {
    if (empty($_SESSION['client_id'])) {
        resp(false, null, 'Login required.', 401);
    }
}