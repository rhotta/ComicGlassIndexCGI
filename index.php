<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 3.2 Final//EN">
<?php
/* 
	[ComicGlass用ファイル一覧生成スクリプト]
	
	このスクリプトはiOSアプリ「おやゆびでお」の開発者 古田一義さんが作成したものを
	マキさん(http://www.relativetruth.net/)がComicGlass向けに修正したものをベースにしています。
	
	このスクリプトについてのご質問はComicGlassサポートページまたはtwitter(@rhotta)まで。
	
	
	[更新履歴]
	2012-04-24		スクリプトをHTTPサーバのルートに置いていない時の動作を改善しました(R.Hotta)
	2013-11-05		ファイルサイズ、ファイル日時を出力するように変更、不具合の修正(R.Hotta)
	2014-01-27		フォルダ名に'などの特殊文字があると正しく動作しない問題を修正(R.Hotta)
	2015-05-14		くろきさんご指摘部分を修正（path未指定時のPHP警告抑制）	(R.hotta)	
	2016-09-01		PHP7 Compatibility (M.Cafe)
	2025-12-22      セキュリティ強化（ディレクトリトラバーサル対策）(R.Hotta)
 */
?>

<?php
	/*このスクリプトにアクセス認証をかけるにはauth=1にしてください
	 （！注意！）zipファイル等、ファイル自身にはアクセス制限がかかりません。
	 インデックスが出力されないだけですので、インターネットへの公開には利用しないでください。
	 ファイルは送信可能化状態におかれますので、他社の著作物が含まれる場合、著作権法違反となります。
	 インターネット公開に利用する場合はWebサーバ側でbasic認証を設定し、SSL化することを強く推奨します。
	 
	 To enable access authentication for this script, set $auth = 1;
	 (! Caution !) This does not apply access restrictions to the files themselves (e.g., ZIP files).
	 Since this only prevents the index from being displayed, do not use it for public internet access.
	 Files remain in a "ready for transmission" state; if they contain copyrighted works of others, it may constitute copyright infringement.
	 If you use this for public internet access, it is strongly recommended to configure Basic Authentication and SSL on the web server side.
	 */


// --- 設定エリア ---
// --- Configuration Area ---
$auth = 0;           // 認証が必要な場合は 1 に変更 / Change to 1 if authentication is required
$user = 'user';      // 認証用ユーザー名 / Username for authentication
$password = 'comicglass'; // 認証用パスワード / Password for authentication

// --- セキュリティ関数 ---
// --- Security Functions ---
/**
 * HTMLエスケープ（XSS対策）
 * HTML Escaping (XSS Prevention)
 */
function h($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/**
 * 認証ヘッダーの送信
 * Send Authentication Header
 */
function sendAuthenticateHeader() {
    header('WWW-Authenticate: Basic realm="Private Page"');
    header('HTTP/1.0 401 Unauthorized');
    die('401 Authorization Required.');
}

// --- 認証処理 ---
// --- Authentication Process ---
if ($auth) {
    if (!isset($_SERVER['PHP_AUTH_USER']) || 
        $_SERVER['PHP_AUTH_USER'] !== $user || 
        $_SERVER['PHP_AUTH_PW'] !== $password) {
        sendAuthenticateHeader();
    }
}

// --- パス処理とディレクトリトラバーサル対策 ---
// --- Path Processing and Directory Traversal Prevention ---
$base_dir = realpath('./');
$path_param = isset($_GET['path']) ? $_GET['path'] : './';
$path_param = stripcslashes($path_param); // 特殊文字の処理 / Handle special characters

$real_requested_path = realpath($path_param);

// 指定パスが公開ディレクトリ（このPHPがある場所）の外を指していないか厳格にチェック
// Strictly check if the specified path points outside the public directory (where this PHP resides)
if ($real_requested_path === false || strpos($real_requested_path, $base_dir) !== 0) {
    $path = "./";
    $real_current_path = $base_dir;
} else {
    $path = $path_param;
    if ($path === "") { $path = "./"; }
    $real_current_path = $real_requested_path;
}
?>
<html>
 <head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <title><?php echo h($path); ?></title>
 </head>
<body>

<h3><?php echo h($path); ?></h3>
<ul>
<?php
    // ディレクトリ一覧の取得
    // Get Directory Listing
    $d = @dir($real_current_path);
    if (!$d) {
        echo "<li>Cannot open directory.</li>"; // Cannot open directory.
    } else {
        $array_file = array();
        while (false !== ($file_name = $d->read())) {
            // ドットファイルと親・自ディレクトリをスキップ
            // Skip dotfiles, parent, and current directory
            if ($file_name === "." || $file_name === "..") continue;
            if (substr($file_name, 0, 1) === ".") continue;

            $full_physical_path = $real_current_path . DIRECTORY_SEPARATOR . $file_name;
            
            // ソート用にディレクトリを優先（0:ディレクトリ, 1:ファイル）
            // Prioritize directories for sorting (0: directory, 1: file)
            $prefix = is_dir($full_physical_path) ? "0" : "1";
            $array_file[] = $prefix . $file_name;
        }
        
        sort($array_file); // ファイル名でソート / Sort by filename

        foreach ($array_file as $entry_prefix) {
            $entry = substr($entry_prefix, 1);
            $full_physical_path = $real_current_path . DIRECTORY_SEPARATOR . $entry;
            
            // URLエンコード処理（ComicGlassの仕様に合わせる）
            // URL Encoding process (Match ComicGlass specifications)
            $folderpath = str_replace("%2F", "/", rawurlencode($path . $entry));
            $filedate = filemtime($full_physical_path);
            
            // ディレクトリの場合
            // If it is a directory
            if (is_dir($full_physical_path)) {
                echo '<li type="circle"><a href="?path=' . h($folderpath) . '/" bookdate="' . h($filedate) . '">' . h($entry) . '</a></li>' . "\n";
            } 
            // ファイルの場合（ComicGlass対応拡張子のみ）
            // If it is a file (Supported ComicGlass extensions only)
            else if (preg_match('/\.gif$|\.png$|\.jpg$|\.jpeg$|\.tif$|\.tiff$|\.zip$|\.rar$|\.cbz$|\.cbr$|\.bmp$|\.pdf$|\.cgt$/i', $entry)) {
                
                $filesize = filesize($full_physical_path);
                
                // ブラウザからアクセスするためのパス生成
                // Generate path for browser access
                $scriptdir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
                $relative_path = str_replace("./", "", $folderpath);
                $filebasepath = $scriptdir . '/' . ltrim($relative_path, '/');

                echo '<li><a href="' . h($filebasepath) . '"';
                echo ' booktitle="' . h($entry) . '"';
                echo ' booksize="' . h($filesize) . '"';
                echo ' bookdate="' . h($filedate) . '" bookfile="true">' . h($entry) . '</a></li>' . "\n";
            }
        }
        $d->close();
    }
?>
</ul>
</body>
</html>