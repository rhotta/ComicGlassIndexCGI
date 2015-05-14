<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 3.2 Final//EN">
<?php
/* 
	[ComicGlass用ファイル一覧生成スクリプト]
	
	このスクリプトはiOSアプリ「おやゆびでお」の開発者 古田一義さんが作成したものを
	マキさん(http://www.relativetruth.net/)がComicGlass向けに修正したものをベースにしています。
	
	このスクリプトについてのご質問はComicGlassサポートページまたはtwitter(@rhotta)まで。
	
	
	[更新履歴]
	12/04/24		スクリプトをHTTPサーバのルートに置いていない時の動作を改善しました(R.Hotta)
	13/11/05		ファイルサイズ、ファイル日時を出力するように変更、不具合の修正(R.Hotta)
	14/01/27		フォルダ名に'などの特殊文字があると正しく動作しない問題を修正(R.Hotta)
	
 */
?>

<?php
	/*このスクリプトにアクセス認証をかけるにはauth=1にしてください
	 （！注意！）zipファイル等、ファイル自身にはアクセス制限がかかりません。
	 インデックスが出力されないだけですので、インターネットへの公開には利用しないでください。
	 ファイルは送信可能化状態におかれますので、他社の著作物が含まれる場合、著作権法違反となります。
	 インターネット公開に利用する場合はWebサーバのアクセス認証機能を利用してください。
	*/
	$auth = 0;
	$user = 'user';
	$password = 'comicglass';

	if($auth){
		if (!isset($_SERVER['PHP_AUTH_USER'])){
			sendAuthenticateHeader();
		}else{
			if ($_SERVER['PHP_AUTH_USER'] != $user
				|| $_SERVER['PHP_AUTH_PW'] != $password)
			{

				sendAuthenticateHeader();
		
			}
		}

		
	}
	function sendAuthenticateHeader()
	{
		header('WWW-Authenticate: Basic realm="Private Page"');
		header('HTTP/1.0 401 Unauthorized');
		die('401 Authorization Required.');
	}
?>


<?php
	 $path = $_GET['path'];
	
	 if ($path=="") {$path="./";}
	 $path = stripcslashes($path);
	 //ディレクトリトラバーサルを防止
	 $realpath = realpath($path);
	 $bookpath = realpath("./");
	 if(strpos($realpath, $bookpath, 0) !== 0)
	 {
	 	$path="./";
	 }

?>
<html>
 <head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <title><?php echo $path; ?></title>
 </head>
<body>

<h3><?php echo$path; ?></h3>
<ul>
<?php
//ファイル一覧を取得
	$d = dir("".$path);
	//ファイル名でソートするため配列に移す。
	$array_file = array();
	while ($file_name = $d->read()) {
		if (is_dir($path.$file_name)) {
			$file_mark = "0".$file_name;
		} else {
			$file_mark = "1".$file_name;
		}
		array_push($array_file, $file_mark);
	}
	sort($array_file); //ソート実行

	//ファイル一覧を出力
	foreach ($array_file as &$entry) {
		$entry = substr($entry,1, strlen($entry)-1);
		$folderpath = str_replace("%2F","/", rawurlencode($path.$entry));
		$fullpath = str_replace("./", "", "$folderpath");
		
		$fullpathraw = str_replace("./", "", $path.$entry);
		
		$filedate = filemtime($fullpathraw);
		$scriptdir = dirname($_SERVER['SCRIPT_NAME']);
		if(eregi('/$',$scriptdir)){
			$filebasepath = $scriptdir  . $fullpath;
		}else{
		
			$filebasepath = $scriptdir . "/" . $fullpath;
		}
		if (is_dir($path.$entry)) {
			if ($entry==".") {
			} elseif ($entry=="..") {
		  	} else {
				if (substr($entry,0,1) != ".") {
					echo "<li type=\"circle\"><a href=\"?path=".$folderpath."/\" bookdate=".$filedate."\">".$entry."</a></li>\n";
				}
		 	}

		//不可視ファイルは除外
		} elseif (substr($entry,0,1) == ".") {

		//表示する拡張子
		} elseif (eregi(
		'\.gif$|\.png$|\.jpg$|\.jpeg$|\.tif$|\.tiff$|\.zip$|\.rar$|\.cbz$|\.cbr$|\.bmp$|\.pdf$|\.cgt$'
		,$entry)) {
			$filesize = filesize($fullpathraw);
			echo "<li><a href=\"".$filebasepath."\"";
			echo " booktitle=\"".$entry."\"";
			echo " booksize=\"".$filesize."\"";
			echo " bookdate=\"".$filedate."\" bookfile=\"true\">".$entry."</a></li>\n";
		}
	}
	   $d->close();
?>
</ul>
</body>
</html>
