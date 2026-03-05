<?php
// ============================================
// BBShoots — api/index.php  (Main API Router)
// URL: /bbshoots/api/index.php?action=xxx
// ============================================
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/mailer.php';

$action = trim($_GET['action'] ?? $_POST['action'] ?? '');

switch ($action) {
    // Auth
    case 'client_register':   doClientRegister();   break;
    case 'client_login':      doClientLogin();       break;
    case 'client_logout':     doClientLogout();      break;
    case 'admin_login':       doAdminLogin();        break;
    case 'admin_logout':      doAdminLogout();       break;
    case 'check_session':     doCheckSession();      break;
    // Bookings
    case 'create_booking':    doCreateBooking();     break;
    case 'get_my_bookings':   doGetMyBookings();     break;
    case 'get_all_bookings':  doGetAllBookings();    break;
    case 'update_booking':    doUpdateBooking();     break;
    // Projects
    case 'update_project':    doUpdateProject();     break;
    // Clients
    case 'get_all_clients':   doGetAllClients();     break;
    case 'remove_client':     doRemoveClient();      break;
    // Notifications
    case 'get_notifications': doGetNotifications();  break;
    case 'mark_read':         doMarkRead();          break;
    // Contact
    case 'send_contact':      doSendContact();       break;
    // Stats
    case 'get_stats':         doGetStats();          break;

    default:
        resp(false, null, 'Unknown action: ' . htmlspecialchars($action));
}

// ============================================
// AUTH
// ============================================
function doClientRegister(): void {
    $d = body();
    $name  = trim($d['name']     ?? '');
    $email = trim($d['email']    ?? '');
    $phone = trim($d['phone']    ?? '');
    $pass  = trim($d['password'] ?? '');
    if (!$name || !$email || !$phone || !$pass) resp(false, null, 'All fields are required.');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) resp(false, null, 'Invalid email address.');
    if (strlen($pass) < 6) resp(false, null, 'Password must be at least 6 characters.');

    $db = getDB();
    $chk = $db->prepare("SELECT id FROM clients WHERE email=?");
    $chk->execute([$email]);
    if ($chk->fetch()) resp(false, null, 'Email already registered. Please login.');

    $hash = password_hash($pass, PASSWORD_BCRYPT);
    $db->prepare("INSERT INTO clients(name,email,phone,password) VALUES(?,?,?,?)")->execute([$name,$email,$phone,$hash]);
    $id = (int)$db->lastInsertId();

    $_SESSION['client_id']    = $id;
    $_SESSION['client_name']  = $name;
    $_SESSION['client_email'] = $email;

    resp(true, ['id'=>$id,'name'=>$name,'email'=>$email], 'Account created!');
}

function doClientLogin(): void {
    $d = body();
    $email = trim($d['email']    ?? '');
    $pass  = trim($d['password'] ?? '');
    if (!$email || !$pass) resp(false, null, 'Email and password required.');

    $db   = getDB();
    $stmt = $db->prepare("SELECT * FROM clients WHERE email=? AND status!='removed'");
    $stmt->execute([$email]);
    $c = $stmt->fetch();

    if (!$c || !password_verify($pass, $c['password'])) resp(false, null, 'Invalid email or password.');
    if ($c['status'] === 'suspended') resp(false, null, 'Account suspended. Contact admin.');

    $_SESSION['client_id']    = $c['id'];
    $_SESSION['client_name']  = $c['name'];
    $_SESSION['client_email'] = $c['email'];

    resp(true, ['id'=>$c['id'],'name'=>$c['name'],'email'=>$c['email'],'phone'=>$c['phone']]);
}

function doClientLogout(): void {
    unset($_SESSION['client_id'],$_SESSION['client_name'],$_SESSION['client_email']);
    resp(true, null, 'Logged out.');
}

function doAdminLogin(): void {
    $d = body();
    $email = trim($d['email']    ?? '');
    $pass  = trim($d['password'] ?? '');
    if ($email === ADMIN_EMAIL && $pass === ADMIN_PASSWORD) {
        $_SESSION['admin'] = true;
        resp(true, ['admin'=>true]);
    } else {
        resp(false, null, 'Invalid admin credentials.', 401);
    }
}

function doAdminLogout(): void {
    unset($_SESSION['admin']);
    resp(true, null, 'Logged out.');
}

function doCheckSession(): void {
    resp(true, [
        'client' => isset($_SESSION['client_id']) ? [
            'id'    => $_SESSION['client_id'],
            'name'  => $_SESSION['client_name'],
            'email' => $_SESSION['client_email'],
        ] : null,
        'admin' => !empty($_SESSION['admin']),
    ]);
}

// ============================================
// BOOKINGS
// ============================================
function doCreateBooking(): void {
    $d = body();
    $required = ['client_name','email','phone','event_type','event_date','location','package'];
    foreach ($required as $f) {
        if (empty(trim($d[$f] ?? ''))) resp(false, null, "Field '$f' is required.");
    }

    $db  = getDB();
    $ref = generateRef();

    // Link to logged-in client or find by email
    $clientId = null;
    if (!empty($_SESSION['client_id'])) {
        $clientId = $_SESSION['client_id'];
    } else {
        $ch = $db->prepare("SELECT id FROM clients WHERE email=?");
        $ch->execute([trim($d['email'])]);
        $ex = $ch->fetch();
        if ($ex) $clientId = $ex['id'];
    }

    $db->prepare("INSERT INTO bookings
        (booking_ref,client_id,client_name,email,phone,event_type,event_date,location,package,notes)
        VALUES(?,?,?,?,?,?,?,?,?,?)")
        ->execute([
            $ref,
            $clientId,
            trim($d['client_name']),
            trim($d['email']),
            trim($d['phone']),
            trim($d['event_type']),
            trim($d['event_date']),
            trim($d['location']),
            trim($d['package']),
            trim($d['notes'] ?? ''),
        ]);
    $bookingId = (int)$db->lastInsertId();

    // Create project row
    $db->prepare("INSERT INTO projects(booking_id) VALUES(?)")->execute([$bookingId]);

    addNotif('new_booking', "New booking $ref from {$d['client_name']} — {$d['event_type']} on {$d['event_date']}");

    $bData = [
        'booking_ref'  => $ref,
        'client_name'  => trim($d['client_name']),
        'email'        => trim($d['email']),
        'phone'        => trim($d['phone']),
        'event_type'   => trim($d['event_type']),
        'event_date'   => trim($d['event_date']),
        'location'     => trim($d['location']),
        'package'      => trim($d['package']),
        'notes'        => trim($d['notes'] ?? ''),
    ];

    // Send emails (errors won't stop the response)
    @mailBookingConfirm($bData);
    @mailAdminNewBooking($bData);

    resp(true, ['booking_ref'=>$ref,'id'=>$bookingId], 'Booking submitted successfully!');
}

function doGetMyBookings(): void {
    requireClient();
    $db   = getDB();
    $stmt = $db->prepare("
        SELECT b.*, p.status AS project_status, p.video_urls, p.admin_notes
        FROM bookings b
        LEFT JOIN projects p ON p.booking_id=b.id
        WHERE b.client_id=? OR b.email=?
        ORDER BY b.created_at DESC
    ");
    $stmt->execute([$_SESSION['client_id'], $_SESSION['client_email']]);
    $rows = $stmt->fetchAll();
    foreach ($rows as &$r) {
        $r['video_urls'] = json_decode($r['video_urls'] ?? '[]', true) ?: [];
    }
    resp(true, $rows);
}

function doGetAllBookings(): void {
    requireAdmin();
    $allowed = ['created_at','event_date','status','client_name','package'];
    $sort = in_array($_GET['sort'] ?? '', $allowed) ? $_GET['sort'] : 'created_at';
    $dir  = ($_GET['dir'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';

    $db   = getDB();
    $rows = $db->query("
        SELECT b.*, p.status AS project_status, p.video_urls, p.admin_notes, p.id AS project_id
        FROM bookings b
        LEFT JOIN projects p ON p.booking_id=b.id
        ORDER BY b.$sort $dir
    ")->fetchAll();

    foreach ($rows as &$r) {
        $r['video_urls'] = json_decode($r['video_urls'] ?? '[]', true) ?: [];
    }
    resp(true, $rows);
}

function doUpdateBooking(): void {
    requireAdmin();
    $d  = body();
    $id = (int)($d['id'] ?? 0);
    if (!$id) resp(false, null, 'Booking ID required.');

    $db  = getDB();
    $chk = $db->prepare("SELECT * FROM bookings WHERE id=?");
    $chk->execute([$id]);
    $bk = $chk->fetch();
    if (!$bk) resp(false, null, 'Booking not found.');

    $valid = ['pending','confirmed','completed','cancelled','rejected'];
    $newStatus = $d['status'] ?? $bk['status'];
    if (!in_array($newStatus, $valid)) resp(false, null, 'Invalid status.');

    // Build dynamic update — admin can edit any field
    $sets = ['status=?'];
    $vals = [$newStatus];

    $editable = ['client_name','phone','event_type','event_date','location','package','notes'];
    foreach ($editable as $field) {
        if (isset($d[$field]) && $d[$field] !== '') {
            $sets[] = "$field=?";
            $vals[] = trim($d[$field]);
        }
    }
    $vals[] = $id;
    $db->prepare("UPDATE bookings SET " . implode(',', $sets) . " WHERE id=?")->execute($vals);

    if ($newStatus !== $bk['status']) {
        $bk['status'] = $newStatus;
        // Refresh bk with latest data for email
        $chk->execute([$id]);
        $bk = $chk->fetch() ?: $bk;
        @mailStatusUpdate($bk);
        addNotif('status_update', "Booking {$bk['booking_ref']} → $newStatus");
    }
    resp(true, ['status'=>$newStatus], 'Booking updated.');
}

// ============================================
// PROJECTS
// ============================================
function doUpdateProject(): void {
    requireAdmin();
    $d  = body();
    $bid = (int)($d['booking_id'] ?? 0);
    if (!$bid) resp(false, null, 'booking_id required.');

    $db  = getDB();
    $prj = $db->prepare("SELECT * FROM projects WHERE booking_id=?");
    $prj->execute([$bid]);
    $proj = $prj->fetch();
    if (!$proj) resp(false, null, 'Project not found.');

    $sets = [];
    $vals = [];

    if (isset($d['status'])) {
        $vs = ['scheduled','shooting','editing','review','completed'];
        if (!in_array($d['status'], $vs)) resp(false, null, 'Invalid project status.');
        $sets[] = 'status=?'; $vals[] = $d['status'];
    }
    if (isset($d['video_urls'])) {
        $sets[] = 'video_urls=?';
        $vals[] = json_encode(array_values(array_filter((array)$d['video_urls'])));
    }
    if (isset($d['admin_notes'])) {
        $sets[] = 'admin_notes=?'; $vals[] = $d['admin_notes'];
    }
    if (!$sets) resp(false, null, 'Nothing to update.');

    $vals[] = $bid;
    $db->prepare("UPDATE projects SET " . implode(',', $sets) . " WHERE booking_id=?")->execute($vals);

    // If completed → email client videos
    if (isset($d['status']) && $d['status'] === 'completed') {
        $bkStmt = $db->prepare("SELECT * FROM bookings WHERE id=?");
        $bkStmt->execute([$bid]);
        $bk = $bkStmt->fetch();
        $urls = isset($d['video_urls'])
            ? array_values(array_filter((array)$d['video_urls']))
            : (json_decode($proj['video_urls'] ?? '[]', true) ?: []);
        if ($bk) @mailVideosReady($bk, $urls);
        addNotif('project_complete', "Project {$bk['booking_ref']} completed — client notified");
    }
    resp(true, null, 'Project updated.');
}

// ============================================
// CLIENTS
// ============================================
function doGetAllClients(): void {
    requireAdmin();
    $db = getDB();
    $rows = $db->query("
        SELECT c.*, COUNT(b.id) AS booking_count
        FROM clients c
        LEFT JOIN bookings b ON b.client_id=c.id
        GROUP BY c.id ORDER BY c.created_at DESC
    ")->fetchAll();
    resp(true, $rows);
}

function doRemoveClient(): void {
    requireAdmin();
    $d  = body();
    $id = (int)($d['id'] ?? 0);
    $act = $d['action'] ?? 'remove';
    if (!$id) resp(false, null, 'Client ID required.');

    $map = ['remove'=>'removed','suspend'=>'suspended','restore'=>'active'];
    $ns  = $map[$act] ?? 'removed';
    getDB()->prepare("UPDATE clients SET status=? WHERE id=?")->execute([$ns,$id]);
    addNotif('client_action', "Client #$id {$act}d by admin");
    resp(true, ['status'=>$ns], "Client $act completed.");
}

// ============================================
// NOTIFICATIONS
// ============================================
function doGetNotifications(): void {
    requireAdmin();
    $db    = getDB();
    $limit = min((int)($_GET['limit'] ?? 50), 100);
    $stmt  = $db->prepare("SELECT * FROM notifications ORDER BY created_at DESC LIMIT ?");
    $stmt->execute([$limit]);
    $rows  = $stmt->fetchAll();
    $unread = (int)$db->query("SELECT COUNT(*) FROM notifications WHERE is_read=0")->fetchColumn();
    resp(true, ['items'=>$rows,'unread'=>$unread]);
}

function doMarkRead(): void {
    requireAdmin();
    getDB()->query("UPDATE notifications SET is_read=1 WHERE is_read=0");
    resp(true, null, 'Marked read.');
}

// ============================================
// CONTACT
// ============================================
function doSendContact(): void {
    $d    = body();
    $name = trim($d['name']    ?? '');
    $email= trim($d['email']   ?? '');
    $msg  = trim($d['message'] ?? '');
    if (!$name || !$email || !$msg) resp(false, null, 'All fields are required.');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) resp(false, null, 'Invalid email address.');

    $db = getDB();
    $db->prepare("INSERT INTO contact_messages(name,email,message) VALUES(?,?,?)")->execute([$name,$email,$msg]);
    addNotif('contact', "Contact message from $name <$email>");
    @mailAdminContact($name, $email, $msg);
    resp(true, null, "Message sent! We'll get back to you within 24 hours.");
}

// ============================================
// STATS
// ============================================
function doGetStats(): void {
    requireAdmin();
    $db = getDB();
    resp(true, [
        'total_bookings' => (int)$db->query("SELECT COUNT(*) FROM bookings")->fetchColumn(),
        'pending'        => (int)$db->query("SELECT COUNT(*) FROM bookings WHERE status='pending'")->fetchColumn(),
        'confirmed'      => (int)$db->query("SELECT COUNT(*) FROM bookings WHERE status='confirmed'")->fetchColumn(),
        'completed'      => (int)$db->query("SELECT COUNT(*) FROM bookings WHERE status='completed'")->fetchColumn(),
        'total_clients'  => (int)$db->query("SELECT COUNT(*) FROM clients WHERE status='active'")->fetchColumn(),
        'unread_notifs'  => (int)$db->query("SELECT COUNT(*) FROM notifications WHERE is_read=0")->fetchColumn(),
    ]);
}
