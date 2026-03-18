<?php
// Simple mail capture viewer - for Docker testing only
$mailDir = __DIR__ . '/mail_capture';
if (!is_dir($mailDir)) {
    echo "<p>No emails captured yet. Mail directory does not exist.</p>";
    exit;
}

$action = $_GET['action'] ?? 'list';
$file = isset($_GET['file']) ? basename($_GET['file']) : '';

if ($action === 'delete' && $file) {
    $path = $mailDir . '/' . $file;
    if (file_exists($path)) unlink($path);
    header('Location: mail_viewer.php');
    exit;
}

if ($action === 'deleteall') {
    foreach (glob($mailDir . '/*.eml') as $f) unlink($f);
    header('Location: mail_viewer.php');
    exit;
}

$files = array_reverse(glob($mailDir . '/*.eml') ?: []);
?>
<!DOCTYPE html>
<html>
<head>
<title>Mail Capture Viewer</title>
<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h1 { color: #333; }
.email-list { border-collapse: collapse; width: 100%; }
.email-list th, .email-list td { border: 1px solid #ddd; padding: 8px; text-align: left; }
.email-list th { background: #f2f2f2; }
.email-list tr:hover { background: #f9f9f9; }
.email-body { background: #f9f9f9; border: 1px solid #ddd; padding: 15px; white-space: pre-wrap; font-family: monospace; font-size: 12px; margin-top: 10px; }
.btn { padding: 4px 10px; text-decoration: none; border-radius: 3px; font-size: 12px; }
.btn-view { background: #4CAF50; color: white; }
.btn-delete { background: #f44336; color: white; }
.btn-back { background: #2196F3; color: white; }
.btn-deleteall { background: #f44336; color: white; padding: 6px 14px; }
</style>
</head>
<body>
<h1>📬 Mail Capture Viewer</h1>
<p><em>Docker test environment — emails are not actually sent.</em></p>

<?php if ($action === 'view' && $file): ?>
    <?php
    $path = $mailDir . '/' . $file;
    $raw = file_exists($path) ? file_get_contents($path) : 'File not found.';
    // Parse headers
    $parts = preg_split('/\r?\n\r?\n/', $raw, 2);
    $headers = $parts[0] ?? '';
    $body = $parts[1] ?? '';
    $to = ''; $from = ''; $subject = '';
    foreach (explode("\n", $headers) as $line) {
        if (stripos($line, 'To:') === 0)      $to      = trim(substr($line, 3));
        if (stripos($line, 'From:') === 0)    $from    = trim(substr($line, 5));
        if (stripos($line, 'Subject:') === 0) $subject = trim(substr($line, 8));
    }
    ?>
    <a class="btn btn-back" href="mail_viewer.php">← Back to list</a>
    <a class="btn btn-delete" href="mail_viewer.php?action=delete&file=<?php echo urlencode($file)?>">Delete</a>
    <h2><?php echo htmlspecialchars($subject ?: '(no subject)') ?></h2>
    <p><strong>From:</strong> <?php echo htmlspecialchars($from) ?><br>
    <strong>To:</strong> <?php echo htmlspecialchars($to) ?><br>
    <strong>File:</strong> <?php echo htmlspecialchars($file) ?></p>
    <div class="email-body"><?php echo htmlspecialchars($raw) ?></div>

<?php else: ?>
    <?php if ($files): ?>
    <p>
        <strong><?php echo count($files) ?> email(s) captured.</strong>
        <a class="btn btn-deleteall" href="mail_viewer.php?action=deleteall" onclick="return confirm('Delete all emails?')">Delete All</a>
    </p>
    <table class="email-list">
        <tr><th>Time</th><th>From</th><th>To</th><th>Subject</th><th>Actions</th></tr>
        <?php foreach ($files as $f):
            $raw = file_get_contents($f);
            $parts = preg_split('/\r?\n\r?\n/', $raw, 2);
            $headers = $parts[0] ?? '';
            $to = $from = $subject = '';
            foreach (explode("\n", $headers) as $line) {
                if (stripos($line, 'To:') === 0)      $to      = trim(substr($line, 3));
                if (stripos($line, 'From:') === 0)    $from    = trim(substr($line, 5));
                if (stripos($line, 'Subject:') === 0) $subject = trim(substr($line, 8));
            }
            $fname = basename($f);
            $time = substr($fname, 0, 15);
            $time = substr($time,0,4).'-'.substr($time,4,2).'-'.substr($time,6,2).' '.substr($time,9,2).':'.substr($time,11,2).':'.substr($time,13,2);
        ?>
        <tr>
            <td><?php echo htmlspecialchars($time) ?></td>
            <td><?php echo htmlspecialchars($from) ?></td>
            <td><?php echo htmlspecialchars($to) ?></td>
            <td><?php echo htmlspecialchars($subject ?: '(no subject)') ?></td>
            <td>
                <a class="btn btn-view" href="mail_viewer.php?action=view&file=<?php echo urlencode($fname)?>">View</a>
                <a class="btn btn-delete" href="mail_viewer.php?action=delete&file=<?php echo urlencode($fname)?>" onclick="return confirm('Delete?')">Delete</a>
            </td>
        </tr>
        <?php endforeach ?>
    </table>
    <?php else: ?>
    <p>No emails captured yet. Send a test email and it will appear here.</p>
    <?php endif ?>
<?php endif ?>
</body>
</html>
