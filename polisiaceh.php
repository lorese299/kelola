<?php
$directory = __DIR__;
$extensions = ['php', 'shtml', 'sh'];
$keywords = ['shell_exec', 'file_get_contents', 'curl', 'string'];
$since = time() - (30 * 24 * 60 * 60); // 30 hari terakhir

$lockFile = __DIR__ . '/.scan.lock';
$self = __FILE__;

// Cek dan simpan waktu pertama akses
if (!file_exists($lockFile)) {
    file_put_contents($lockFile, time());
} else {
    $firstAccess = (int)file_get_contents($lockFile);
    if (time() - $firstAccess >= 180) { // 180 detik = 3 menit
        unlink($lockFile);      // hapus lock
        unlink($self);          // hapus file scan.php
        exit('🧨 File ini telah dihancurkan otomatis setelah 3 menit.');
    }
}

function scanDirRecursive($dir) {
    $files = [];
    foreach (scandir($dir) as $item) {
        if ($item === '.' || $item === '..') continue;
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        if (is_dir($path)) {
            $files = array_merge($files, scanDirRecursive($path));
        } else {
            $files[] = $path;
        }
    }
    return $files;
}

$results = [];

foreach (scanDirRecursive($directory) as $file) {
    $ext = pathinfo($file, PATHINFO_EXTENSION);
    if (!in_array(strtolower($ext), $extensions)) continue;
    if (filemtime($file) < $since) continue;

    $contents = @file_get_contents($file);
    if ($contents === false) continue;

    foreach ($keywords as $keyword) {
        if (stripos($contents, $keyword) !== false) {
            $results[] = $file;
            break;
        }
    }
}

$results = array_unique($results);
sort($results);

if (isset($_GET['download']) && $_GET['download'] === 'txt') {
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="result.txt"');
    echo "Hasil Scan File Mencurigakan (30 Hari Terakhir)\n\n";
    echo empty($results) ? "Tidak ada file mencurigakan ditemukan.\n" : implode("\n", $results);
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Hasil Scan File</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f8f8f8; padding: 20px; }
        table { border-collapse: collapse; width: 100%; background: #fff; }
        th, td { border: 1px solid #ccc; padding: 8px 12px; }
        th { background-color: #eee; }
        .success { color: green; }
        .download-btn {
            display: inline-block;
            margin-top: 20px;
            background-color: #007acc;
            color: #fff;
            padding: 10px 15px;
            text-decoration: none;
            border-radius: 4px;
        }
        .download-btn:hover { background-color: #005f99; }
    </style>
</head>
<body>
    <h2>Hasil Scan File Mencurigakan (30 Hari Terakhir)</h2>

    <?php if (empty($results)): ?>
        <p class="success">✅ Tidak ada file mencurigakan ditemukan.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr><th>🚨 File Terdeteksi</th></tr>
            </thead>
            <tbody>
                <?php foreach ($results as $result): ?>
                    <tr><td><code><?= htmlspecialchars($result) ?></code></td></tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <a class="download-btn" href="?download=txt">⬇️ Unduh Hasil Scan (result.txt)</a>
    <?php endif; ?>
</body>
</html>