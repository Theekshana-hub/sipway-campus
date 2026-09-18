<?php
session_start();

// Authentication check — GET and POST dekatama redirect/error yawanawa
if (!isset($_SESSION['student_id'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'කරුණාකර පළමුව login වන්න']);
        exit;
    } else {
        header('Location: student-login.php');
        exit;
    }
}

$fullName  = $_SESSION['student_name'] ?? 'Guest';
$studentId = (int)$_SESSION['student_id'];

// ====================== BACKEND HANDLER ======================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    require_once 'db.php';
    require_once 'mailer.php'; // ★ lecturer email notification helper
    require_once 'sms.php';    // ★ lecturer SMS notification helper (Mobitel)

    if (!isset($conn) || $conn === null) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    $package_id      = intval($input['package_id'] ?? 0);
    $lecturer_id     = intval($input['lecturer_id'] ?? 0);
    $availability_id = intval($input['availability_id'] ?? $input['slot_id'] ?? 0);
    $session_date    = trim($input['session_date'] ?? '');
    $session_time    = trim($input['session_time'] ?? '');
    $markCompleted   = !empty($input['mark_completed']);

    $packageIdParam = $package_id > 0 ? $package_id : null;

    if (empty($session_date) || empty($session_time)) {
        echo json_encode(['success' => false, 'message' => 'දිනය සහ වේලාව තෝරන්න']);
        exit;
    }

    // Past date/time only allowed when "Already completed" is ticked
    if (!$markCompleted) {
        $sessionDateTime = strtotime($session_date . ' ' . $session_time);
        if ($sessionDateTime === false || $sessionDateTime < time()) {
            echo json_encode(['success' => false, 'message' => 'අතීත දිනයකට/වේලාවකට book කරන්න බැහැ']);
            exit;
        }
    }

    $initialStatus = $markCompleted ? 'Accepted' : 'Pending';

    $conn->begin_transaction();

    try {
        // =====================================================
        // ★ 1) STUDENT PACKAGE TYPE (individual / group)
        // =====================================================
        $bookingType = 'individual';
        $capacity    = 1;

        $pkgTypeStmt = $conn->prepare("
            SELECT package_type, package_id
            FROM activated_packages
            WHERE student_id = ?
              AND status = 'active'
              AND sessions_remaining > 0
            ORDER BY activated_at ASC
            LIMIT 1
            FOR UPDATE
        ");
        $pkgTypeStmt->bind_param("i", $studentId);
        $pkgTypeStmt->execute();
        $pkgTypeRow = $pkgTypeStmt->get_result()->fetch_assoc();
        $pkgTypeStmt->close();

        if ($pkgTypeRow) {
            $rawType = strtolower(trim($pkgTypeRow['package_type'] ?? 'individual'));
            if ($rawType === 'group') {
                $bookingType = 'group';
                $capacity    = 10;
            } else {
                $bookingType = 'individual';
                $capacity    = 1;
            }
            // package_id නැත්නම් activated package එකෙන් ගන්න
            if (!$packageIdParam && !empty($pkgTypeRow['package_id'])) {
                $packageIdParam = (int)$pkgTypeRow['package_id'];
            }
        }

        // =====================================================
        // ★ 2) SLOT LOOKUP
        // =====================================================
        $slotInfo = null;

        if ($availability_id > 0) {
            $slotStmt = $conn->prepare("
                SELECT id, lecturer_id, session_type, max_capacity, is_free, date, start_time
                FROM lecturer_availability
                WHERE id = ? AND approval_status = 'approved'
                LIMIT 1
                FOR UPDATE
            ");
            $slotStmt->bind_param("i", $availability_id);
            $slotStmt->execute();
            $slotInfo = $slotStmt->get_result()->fetch_assoc();
            $slotStmt->close();

            if (!$slotInfo) {
                throw new Exception('මේ slot එක තවදුරටත් නොමැත / approve වී නැත');
            }

            $lecturer_id  = (int)$slotInfo['lecturer_id'];
            $session_date = $slotInfo['date'];
            $session_time = substr($slotInfo['start_time'], 0, 5);
        } elseif ($lecturer_id > 0) {
            $slotStmt = $conn->prepare("
                SELECT id, lecturer_id, session_type, max_capacity, is_free, date, start_time
                FROM lecturer_availability
                WHERE lecturer_id = ?
                  AND date = ?
                  AND TIME_FORMAT(start_time, '%H:%i') = ?
                  AND approval_status = 'approved'
                LIMIT 1
                FOR UPDATE
            ");
            $slotStmt->bind_param("iss", $lecturer_id, $session_date, $session_time);
            $slotStmt->execute();
            $slotInfo = $slotStmt->get_result()->fetch_assoc();
            $slotStmt->close();

            if ($slotInfo) {
                $availability_id = (int)$slotInfo['id'];
            }
        }

               // =====================================================
        // ★ 3) CAPACITY CHECK — first booker locks the type
        // =====================================================
        $bookedCount = 0;

        if ($availability_id > 0) {
            // Current bookings on this slot
            $cntStmt = $conn->prepare("
                SELECT COUNT(*) AS cnt
                FROM bookings
                WHERE availability_id = ?
                  AND status IN ('Pending', 'Accepted')
            ");
            $cntStmt->bind_param("i", $availability_id);
            $cntStmt->execute();
            $bookedCount = (int)$cntStmt->get_result()->fetch_assoc()['cnt'];
            $cntStmt->close();

            // First booking type (locks the slot)
            $firstTypeStmt = $conn->prepare("
                SELECT booking_type
                FROM bookings
                WHERE availability_id = ?
                  AND status IN ('Pending', 'Accepted')
                ORDER BY id ASC
                LIMIT 1
            ");
            $firstTypeStmt->bind_param("i", $availability_id);
            $firstTypeStmt->execute();
            $firstRow = $firstTypeStmt->get_result()->fetch_assoc();
            $firstTypeStmt->close();

            $lockedType = $firstRow ? strtolower(trim($firstRow['booking_type'] ?? '')) : null;

            // Already locked to a different type?
            if ($lockedType && $lockedType !== $bookingType) {
                if ($lockedType === 'individual') {
                    throw new Exception('මේ session එක දැනටමත් Individual book වී ඇත. Group package එකකින් book කළ නොහැක.');
                } else {
                    throw new Exception('මේ session එක දැනටමත් Group session එකක් ලෙස book වී ඇත. Individual package එකකින් book කළ නොහැක.');
                }
            }

            // Capacity limits
            if ($bookingType === 'individual') {
                if ($bookedCount >= 1) {
                    throw new Exception('මේ session එක දැනටමත් book වී ඇත. Individual package = 1 කෙනෙක් විතරයි.');
                }
            } else { // group
                if ($bookedCount >= 10) {
                    throw new Exception("මේ Group session එක full වී ඇත ({$bookedCount}/10).");
                }
            }

            // Same student cannot book the same slot twice
            $dupStmt = $conn->prepare("
                SELECT id FROM bookings
                WHERE student_id = ?
                  AND availability_id = ?
                  AND status IN ('Pending', 'Accepted')
                LIMIT 1
            ");
            $dupStmt->bind_param("ii", $studentId, $availability_id);
            $dupStmt->execute();
            if ($dupStmt->get_result()->fetch_assoc()) {
                throw new Exception('ඔබ දැනටමත් මේ session එක book කරලා තියෙනවා.');
            }
            $dupStmt->close();
        } 
        // =====================================================
        // ★ 4) INSERT BOOKING (with booking_type)
        // =====================================================
        if ($lecturer_id > 0 && $availability_id > 0) {
            $stmt = $conn->prepare("
                INSERT INTO bookings
                    (student_id, student_name, lecturer_id, availability_id, package_id,
                     booking_type, session_date, session_time, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param(
                'isiisssss',
                $studentId,
                $fullName,
                $lecturer_id,
                $availability_id,
                $packageIdParam,
                $bookingType,
                $session_date,
                $session_time,
                $initialStatus
            );
        } elseif ($lecturer_id > 0) {
            $stmt = $conn->prepare("
                INSERT INTO bookings
                    (student_id, student_name, lecturer_id, package_id,
                     booking_type, session_date, session_time, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param(
                'isisssss',
                $studentId,
                $fullName,
                $lecturer_id,
                $packageIdParam,
                $bookingType,
                $session_date,
                $session_time,
                $initialStatus
            );
        } else {
            $stmt = $conn->prepare("
                INSERT INTO bookings
                    (student_id, student_name, package_id,
                     booking_type, session_date, session_time, status)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param(
                'isissss',
                $studentId,
                $fullName,
                $packageIdParam,
                $bookingType,
                $session_date,
                $session_time,
                $initialStatus
            );
        }

        if (!$stmt->execute()) {
            throw new Exception('Booking කිරීමට නොහැකි විය: ' . $stmt->error);
        }
        $newBookingId = $stmt->insert_id;
        $stmt->close();

        $sessionsRemaining = null;
        $packageStatus     = null;

        // ==================== MARK AS ALREADY COMPLETED ====================
        if ($markCompleted) {
            $pkgStmt = $conn->prepare("
                SELECT id, sessions_remaining
                FROM activated_packages
                WHERE student_id = ? AND status = 'active' AND sessions_remaining > 0
                ORDER BY activated_at ASC
                LIMIT 1
                FOR UPDATE
            ");
            $pkgStmt->bind_param("i", $studentId);
            $pkgStmt->execute();
            $pkgRes = $pkgStmt->get_result();
            $pkg = $pkgRes->fetch_assoc();
            $pkgStmt->close();

            if (!$pkg) {
                throw new Exception('ඔබගේ සක්‍රීය පැකේජයේ ඉතිරි සැසි නොමැත. නව පැකේජයක් සක්‍රීය කරන්න.');
            }

            $activatedPackageId = (int)$pkg['id'];
            $newRemaining = (int)$pkg['sessions_remaining'] - 1;
            if ($newRemaining < 0) $newRemaining = 0;
            $newPkgStatus = $newRemaining <= 0 ? 'completed' : 'active';

            $upd = $conn->prepare("UPDATE activated_packages SET sessions_remaining = ?, status = ? WHERE id = ?");
            $upd->bind_param("isi", $newRemaining, $newPkgStatus, $activatedPackageId);
            $upd->execute();
            $upd->close();

            $logLecturerId = $lecturer_id > 0 ? $lecturer_id : null;

            $log = $conn->prepare("
                INSERT INTO session_logs (student_id, lecturer_id, booking_id, activated_package_id, session_date, session_time, source)
                VALUES (?, ?, ?, ?, ?, ?, 'booking')
            ");
            $log->bind_param(
                "iiiiss",
                $studentId,
                $logLecturerId,
                $newBookingId,
                $activatedPackageId,
                $session_date,
                $session_time
            );
            $log->execute();
            $log->close();

            $sessionsRemaining = $newRemaining;
            $packageStatus     = $newPkgStatus;
        }

        $conn->commit();

        // =====================================================
        // ★ 5) EMAIL + SMS NOTIFICATION TO LECTURER
        // =====================================================
        // Booking eka DB eke commit welada, e nisa mail/SMS eka fail
        // unath student ge booking eka affect wenne na — email
        // ekath, SMS ekath try/catch ekakin wrap karala thiyenne e nisa.
        if ($lecturer_id > 0) {
            // ★ phone column eka methana 'phone' kiyala danne — oyage
            //   lecturers table eke wena column naamayak (e.g. 'mobile',
            //   'contact_no') nam, methana query eka wenas karanna.
            $lecStmt = $conn->prepare("SELECT full_name, email, subject, phone FROM lecturers WHERE id = ? LIMIT 1");
            $lecStmt->bind_param("i", $lecturer_id);
            $lecStmt->execute();
            $lecRow = $lecStmt->get_result()->fetch_assoc();
            $lecStmt->close();

            // ---------- EMAIL ----------
            if ($lecRow && !empty($lecRow['email'])) {
                try {
                    sendBookingNotificationEmail(
                        $lecRow['email'],
                        $lecRow['full_name'] ?? 'Teacher',
                        $fullName,
                        $session_date,
                        $session_time,
                        $lecRow['subject'] ?? null,
                        $initialStatus
                    );
                } catch (\Throwable $mailErr) {
                    // Silently ignore — mail failure should never break the booking response.
                    error_log('Booking email notification failed: ' . $mailErr->getMessage());
                }
            }

            // ---------- SMS (Mobitel) ----------
            if ($lecRow && !empty($lecRow['phone'])) {
                try {
                    $smsResult = sendBookingNotificationSms(
                        $lecRow['phone'],
                        $lecRow['full_name'] ?? 'Teacher',
                        $fullName,
                        $session_date,
                        $session_time,
                        $initialStatus
                    );
                    if (!$smsResult['success']) {
                        error_log('Booking SMS notification failed: ' . $smsResult['message']);
                    }
                } catch (\Throwable $smsErr) {
                    // Silently ignore — SMS failure should never break the booking response.
                    error_log('Booking SMS notification exception: ' . $smsErr->getMessage());
                }
            }
        }

        // Friendly message
        $newCount  = $bookedCount + 1;
        $typeLabel = ($bookingType === 'group') ? 'Group' : 'Individual';
        $extraMsg  = " ({$typeLabel} {$newCount}/{$capacity})";

        echo json_encode([
            'success'            => true,
            'message'            => $markCompleted
                ? ('Session eka book karala iwarai kiyalath log una! Package eke session ' . $sessionsRemaining . ' witharak ithuru wela thiyenawa.' . $extraMsg)
                : ('ඔබේ booking request එක යවුනා! Teacher approve කරගන්නකම් ටිකක් ඉන්න.' . $extraMsg),
            'booking_id'         => $newBookingId,
            'marked_completed'   => $markCompleted,
            'sessions_remaining' => $sessionsRemaining,
            'booking_type'       => $bookingType,
            'booked_count'       => $newCount,
            'max_capacity'       => $capacity
        ]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }

    $conn->close();
    exit;
}
?>
<!DOCTYPE html>
<html lang="si">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Book Session - Sipway Campus</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Sinhala:wght@400;600;700&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
  :root{
    --navy:#1e1b4b;
    --navy-2:#7c3aed;
    --navy-soft:#f3e8ff;
    --coral:#a855f7;
    --coral-dark:#7c3aed;
    --coral-soft:#f3e8ff;
    --bg:#f4f0ff;
    --card:#ffffff;
    --line:#e9e5f5;
    --line-soft:#f3f0fa;
    --muted:#6b7280;
    --muted-2:#9ca3af;
    --text:#1e1b4b;
    --amber:#fce7f3;
    --amber-line:#f9a8d4;
    --amber-price:#be185d;
    --green:#10b981;
    --radius-lg:16px;
    --radius-md:10px;
    --radius-sm:8px;
    --shadow-card:0 8px 24px -8px rgba(124,58,237,0.14);
    --ease:cubic-bezier(.4,0,.2,1);
  }
  *{ box-sizing:border-box; }
  body{
    margin:0;
    font-family:'Inter','Noto Sans Sinhala',-apple-system,BlinkMacSystemFont,'Segoe UI',Arial,sans-serif;
    background:var(--bg);
    color:var(--text);
    -webkit-font-smoothing:antialiased;
  }
  a{ color:inherit; }
  
  .topbar{
    height:64px; display:flex; align-items:center; gap:16px;
    padding:0 20px; background:#fff;
    border-bottom:1px solid var(--line-soft);
    position:sticky; top:0; z-index:50;
  }
  .burger{ background:none; border:none; cursor:pointer; padding:8px; display:flex; border-radius:8px; color:var(--navy); flex-shrink:0; }
  .burger:hover{ background:var(--navy-soft); }
  .burger svg{ width:22px; height:22px; }
  .logo{ display:flex; align-items:center; gap:10px; font-weight:800; color:var(--navy); font-size:18px; letter-spacing:-0.2px; text-decoration:none; flex-shrink:0; }
  .logo-mark{ width:34px; height:34px; border-radius:9px; background:linear-gradient(135deg, #a855f7, #ec4899); display:flex; align-items:center; justify-content:center; color:#fff; font-size:14px; font-weight:800; }
  .top-links{ margin-left:auto; display:flex; align-items:center; gap:28px; }
  .top-links a{ font-size:13.5px; font-weight:600; color:var(--muted); text-decoration:none; white-space:nowrap; }
  .top-links a:hover{ color:var(--navy); }
  .user-menu{ display:flex; align-items:center; gap:8px; cursor:pointer; padding:6px 10px; border-radius:999px; position:relative; }
  .user-menu:hover{ background:var(--navy-soft); }
  .avatar{ width:30px; height:30px; border-radius:50%; background:var(--navy-soft); display:flex; align-items:center; justify-content:center; color:var(--navy-2); flex-shrink:0; }
  .avatar svg{ width:18px; height:18px; }
  .user-menu .chev{ width:14px; height:14px; color:var(--muted); }
  .user-name{ font-size:13.5px; font-weight:700; color:var(--text); }

  .shell{ display:flex; min-height:calc(100vh - 64px); }
  .sidebar{
    width:270px; flex-shrink:0; background:#fff;
    border-right:1px solid var(--line-soft); padding:20px 16px;
    display:flex; flex-direction:column; gap:4px;
    position:sticky; top:64px; align-self:flex-start;
    height:calc(100vh - 64px); overflow-y:auto;
    transition:transform .25s var(--ease);
  }
  .nav-item{ display:flex; align-items:center; gap:12px; padding:12px 14px; border-radius:var(--radius-sm); font-weight:700; font-size:14px; color:var(--navy-2); text-decoration:none; cursor:pointer; }
  .nav-item svg{ width:19px; height:19px; flex-shrink:0; }
  .nav-item:hover{ background:var(--navy-soft); }
  .nav-item.active{ background:linear-gradient(135deg, #a855f7, #ec4899); color:#fff; box-shadow:0 8px 18px -4px rgba(168,85,247,0.4); }
  
  .main{ flex:1; padding:28px clamp(16px, 3vw, 32px) 48px; min-width:0; display:flex; justify-content:center; }
  .back-link{
    display:inline-flex; align-items:center; gap:6px;
    font-size:13.5px; font-weight:700; color:var(--muted);
    text-decoration:none; margin-bottom:18px;
  }
  .back-link:hover{ color:var(--navy); }
  .back-link svg{ width:16px; height:16px; }

  .booking-wrap{ width:100%; max-width:560px; }
  .page-title{ font-size:24px; font-weight:800; color:var(--text); margin:0 0 6px 0; letter-spacing:-0.3px; }
  .page-sub{ font-size:13.5px; color:var(--muted); margin:0 0 24px 0; }

  .pkg-summary{
    display:flex; align-items:center; gap:14px;
    background:linear-gradient(135deg, var(--amber), #fbcfe8);
    border:1px solid var(--amber-line);
    border-radius:var(--radius-lg);
    padding:18px 20px;
    margin-bottom:24px;
  }
  .pkg-summary h3{ font-size:15px; font-weight:800; margin:0 0 4px 0; color:var(--text); }
  .pkg-summary p{ font-size:12.5px; color:#7a2158; margin:0; line-height:1.5; }

  .lecturer-summary{
    display:flex; align-items:center; gap:12px;
    background:var(--navy-soft);
    border:1px solid var(--line-soft);
    border-radius:var(--radius-lg);
    padding:14px 18px;
    margin-bottom:20px;
  }
  .lecturer-summary-avatar{
    width:40px; height:40px; border-radius:10px;
    background:linear-gradient(135deg, #0f0c29, #7c3aed);
    color:#fff; display:flex; align-items:center; justify-content:center;
    font-weight:800; font-size:15px; flex-shrink:0;
  }
  .lecturer-summary h4{ font-size:13.5px; font-weight:800; margin:0 0 2px 0; color:var(--navy); }
  .lecturer-summary p{ font-size:11.5px; color:var(--muted); margin:0; font-weight:600; }

  .booking-card{
    background:var(--card);
    border:1px solid var(--line-soft);
    border-radius:var(--radius-lg);
    box-shadow:var(--shadow-card);
    padding:26px 24px;
  }
  .form-group{ margin-bottom:18px; }
  .form-group label{
    display:block; font-size:13px; font-weight:700;
    color:var(--navy-2); margin-bottom:7px;
  }
  .form-group input{
    width:100%;
    padding:12px 13px;
    border:1px solid var(--line);
    border-radius:var(--radius-sm);
    font-size:14.5px;
    font-family:inherit;
    color:var(--text);
    background:var(--bg);
  }
  .form-group input:focus{
    outline:none;
    border-color:var(--coral);
  }
  .form-row{ display:flex; gap:16px; }
  .form-row .form-group{ flex:1; }

  .mark-done-box{
    display:flex;
    align-items:flex-start;
    gap:11px;
    background:var(--coral-soft);
    border:1px solid #e9d5ff;
    border-radius:var(--radius-sm);
    padding:13px 15px;
    margin-bottom:18px;
  }
  .mark-done-box input[type="checkbox"]{
    width:19px; height:19px;
    accent-color:var(--coral-dark);
    margin-top:1px;
    flex-shrink:0;
    cursor:pointer;
  }
  .mark-done-box label{
    font-size:12.5px;
    font-weight:600;
    color:#5b21b6;
    line-height:1.55;
    cursor:pointer;
  }
  .mark-done-box label b{ color:var(--coral-dark); font-weight:800; }

  .booking-msg{
    font-size:13px;
    font-weight:700;
    min-height:18px;
    margin-bottom:14px;
  }
  .pkg-cta{
    width:100%;
    padding:14px;
    border:none;
    border-radius:var(--radius-sm);
    background:linear-gradient(135deg, #a855f7, #ec4899);
    color:#fff;
    font-weight:800;
    font-size:14.5px;
    letter-spacing:0.3px;
    cursor:pointer;
    transition:filter .15s, transform .1s;
  }
  .pkg-cta:hover{ filter:brightness(1.1); }
  .pkg-cta:active{ transform:translateY(1px); }
  .pkg-cta:disabled{ opacity:0.65; cursor:not-allowed; }

  @media (max-width:820px){
    .top-links{ display:none; }
    .sidebar{ position:fixed; left:0; top:64px; transform:translateX(-100%); width:270px; height:calc(100vh - 64px); z-index:46; box-shadow:0 0 40px rgba(0,0,0,0.15); }
    .sidebar.open{ transform:translateX(0); }
  }
  @media (max-width:480px){
    .main{ padding:20px 14px 40px; }
    .form-row{ flex-direction:column; gap:0; }
    .pkg-summary{ flex-wrap:wrap; }
  }
  .side-divider{ height:1px; background:var(--line-soft); margin:14px 6px; }
  .kid-mode{ display:flex; align-items:center; justify-content:space-between; padding:10px 14px; font-size:14px; font-weight:700; color:var(--text); }
  .switch{ position:relative; width:40px; height:22px; flex-shrink:0; }
  .switch input{ opacity:0; width:0; height:0; }
  .switch-track{ position:absolute; inset:0; background:var(--line); border-radius:999px; cursor:pointer; transition:background .2s var(--ease); }
  .switch-track::before{ content:""; position:absolute; width:16px; height:16px; left:3px; top:3px; background:#fff; border-radius:50%; transition:transform .2s var(--ease); box-shadow:0 1px 3px rgba(0,0,0,0.25); }
  .switch input:checked + .switch-track{ background:var(--coral); }
  .switch input:checked + .switch-track::before{ transform:translateX(18px); }
  .side-link{ display:block; padding:11px 14px; font-size:13.5px; font-weight:600; color:var(--muted); text-decoration:none; border-radius:var(--radius-sm); }
  .side-link:hover{ background:var(--navy-soft); color:var(--navy-2); }
  .promo-card{ margin-top:auto; border-radius:var(--radius-md); overflow:hidden; position:relative; background:linear-gradient(160deg, #0f0c29, #4c2a8a); color:#fff; padding:18px 16px; min-height:150px; }
  .promo-card h4{ font-size:15px; font-weight:800; color:#ec4899; margin:0 0 2px 0; line-height:1.3; }
  .promo-card p{ font-size:11px; color:#c9cbe0; margin:8px 0 0 0; line-height:1.5; }
  .backdrop{ display:none; position:fixed; inset:0; background:rgba(15,12,41,0.35); z-index:45; }
  .backdrop.show{ display:block; }
</style>
</head>
<body>

  <main class="main">
    <div class="booking-wrap">
      <a href="dashboard.php" class="back-link">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6"/></svg>
        Back to Booking
      </a>
      <h1 class="page-title">Schedule Your Session</h1>
      <p class="page-sub">Pick a date and time that works for you to activate this package.</p>
      
      <div class="pkg-summary" id="pkgSummary"></div>
      <div class="lecturer-summary" id="lecturerSummary" style="display:none;"></div>

      <div class="booking-card">
        <div class="form-row">
          <div class="form-group">
            <label for="sessionDate">Session Date</label>
            <input type="date" id="sessionDate">
          </div>
          <div class="form-group">
            <label for="sessionTime">Session Time</label>
            <input type="time" id="sessionTime">
          </div>
        </div>

        <div class="mark-done-box">
          <input type="checkbox" id="markCompleted" checked>
          <label for="markCompleted">
            <b>✓ කළ පසු Book කළහොත්, පරිපාලකගේ අනුමැතියකින් තොරව එය සෘජුවම ලියාපදිංචි වන අතර, පැකේජයේ පාඩම් වාර (Session) එකක් ස්වයංක්‍රීයව අඩු වේ.

✓ නොකළහොත් (පෙරනිමි තත්ත්වය), මෙය සාමාන්‍ය Pending Booking එකක් ලෙස යවනු ලබන අතර, පරිපාලක අනුමත කරන තුරු පැකේජයේ පාඩම් වාර ගණන අඩු නොවේ.
          </label>
        </div>

        <div class="booking-msg" id="bookingMsg"></div>
        <button class="pkg-cta" id="confirmBtn">Confirm Booking</button>
      </div>
    </div>
  </main>
</div>

<script>
const params = new URLSearchParams(window.location.search);
let packageId = parseInt(params.get('pkg')) || 0;

const lecturerId     = parseInt(params.get('lecturer_id')) || 0;
const lecturerName   = params.get('lecturer_name') || '';
const prefillDate    = params.get('date') || '';
const prefillStart   = params.get('start') || '';
const availabilityId = parseInt(params.get('availability_id') || params.get('slot_id') || '0') || 0;

if (packageId === 0) {
  const savedPkg = sessionStorage.getItem('sipwayBookingPkg');
  if (savedPkg) {
    packageId = parseInt(JSON.parse(savedPkg).id) || 0;
  }
}

const sessionDateEl   = document.getElementById('sessionDate');
const sessionTimeEl   = document.getElementById('sessionTime');
const markCompletedEl = document.getElementById('markCompleted');
const confirmBtn      = document.getElementById('confirmBtn');
const bookingMsg      = document.getElementById('bookingMsg');
const pkgSummary      = document.getElementById('pkgSummary');
const lecturerSummary = document.getElementById('lecturerSummary');

if (lecturerId > 0) {
  lecturerSummary.style.display = 'flex';
  lecturerSummary.innerHTML = `
    <div class="lecturer-summary-avatar">${(lecturerName || '?').charAt(0).toUpperCase()}</div>
    <div>
      <h4>${lecturerName || 'Selected Teacher'}</h4>
      <p>Meya wenuwenma session eka book wenawa</p>
    </div>`;
}
if (prefillDate) sessionDateEl.value = prefillDate;
if (prefillStart) sessionTimeEl.value = prefillStart;

// Checkbox default eka ticked karama thiyenne (mark_completed = true by default),
// e nisa admin approval ekakin thoraw booking eka kelinma "Accepted" widihata yanawa.
markCompletedEl.checked = true;

function refreshConfirmBtnState(){
  confirmBtn.disabled = false;
  confirmBtn.textContent = markCompletedEl.checked
    ? 'Confirm & Mark as Completed'
    : 'Confirm Booking';
}
markCompletedEl.addEventListener('change', refreshConfirmBtnState);
refreshConfirmBtnState();

confirmBtn.addEventListener('click', async () => {
  const date = sessionDateEl.value;
  const time = sessionTimeEl.value;
  const markCompleted = markCompletedEl.checked;

  if (!date || !time) {
    showMsg('දිනය සහ වේලාව තෝරන්න', 'error');
    return;
  }

  confirmBtn.disabled = true;
  confirmBtn.textContent = 'Booking...';

  try {
    const response = await fetch('book-session.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        package_id: packageId,
        lecturer_id: lecturerId,
        availability_id: availabilityId,
        session_date: date,
        session_time: time,
        mark_completed: markCompleted
      })
    });

    const data = await response.json();

    if (data.success) {
      showMsg(data.message || 'Booking successful!', 'success');
      sessionStorage.removeItem('sipwayBookingPkg');
      setTimeout(() => location.href = 'index.php', 1800);
    } else {
      showMsg(data.message || 'Booking failed', 'error');
      confirmBtn.disabled = false;
      refreshConfirmBtnState();
    }
  } catch (err) {
    showMsg('Server error. Please try again.', 'error');
    confirmBtn.disabled = false;
    refreshConfirmBtnState();
  }
});

function showMsg(text, type) {
  bookingMsg.style.color = type === 'success' ? '#10b981' : '#be185d';
  bookingMsg.textContent = text;
}
</script>
</body>
</html>