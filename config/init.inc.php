<?php

// ライブラリの読み込み
include_once(PATH_FWK.'/cmn.inc.php');
include_once(PATH_FWK.'/debug.inc.php');
include_once(PATH_FWK.'/html.inc.php');


// テンプレート用インスタンスの生成
$my_html = new html();
$my_cgi  = new cgi();

// 各テーマ構成ファイルへのパス
$my_html->probe_theme_files(PATH_THEMES,URL_THEMES,SITE_THEME);

// ディレクトリインデックス作成用
$config__dir_index = array(
	'あ～お' => 'あいうえおアイウエオ',
	'か～こ' => 'かきくけこがぎぐげごカキクケコガギグゲゴ',
	'さ～そ' => 'さしすせそざじずぜぞサシスセソザジズゼゾ',
	'た～と' => 'たちつてとだぢづでどタチツテトダヂヅデド',
	'な～の' => 'なにぬねのナニヌネノ',
	'は～ほ' => 'はひふへほばびぶべぼぱぴぷぺぽハヒフヘホバビブベボパピプペポ',
	'ま～も' => 'まみむめもマミムメモ',
	'や～よ' => 'やゐゆゑよヤヰユヱヨ',
	'ら～ろ' => 'らりるれろラリルレロ',
	'わ～ん' => 'わをんワオン',
	'その他' => '',
);


// キャッシュファイル識別用
define('CACHE_IX_EXT_DIR' ,'_dir.txt');    // ディレクトリ、ハッシュ格納用
define('CACHE_IX_EXT_ZIP' ,'_zip.txt');    // コミックZIP、ハッシュ格納用
define('CACHE_IX_EXT_CX'  ,'_cx.txt');     // コミックZIP、ハッシュ変換用(cache/comic)
define('CACHE_CX_EXT_LIST','_list.txt');   // コミックZIP内エントリリスト保持用
define('CACHE_CX_EXT_MARK','_mark#?.txt'); // ブックマーク保持用(?はページ番号に置き換わる)


// 定義した定数をすべてHTMLリソースに格納する
$vars = get_defined_constants(true);
$my_html->set_value_array($vars['user']);


?>