<?php
// trigger-reminder.php
$reminderLockFile = __DIR__ . '/reminder_lock.txt';
$now = time();
$lastRun = file_exists($reminderLockFile) ? (int)file_get_contents($reminderLockFile) : 0;

if ($now - $lastRun >= 300) {
    file_put_contents($reminderLockFile, $now);
    $phpPath    = 'C:\\wamp64\\bin\\php\\php8.3.14\\php.exe';
    $scriptPath = __DIR__ . '\\send-session-reminders.php';
    pclose(popen("start /B \"\" \"$phpPath\" \"$scriptPath\"", "r"));
    echo "triggered";
} else {
    echo "skipped";
}