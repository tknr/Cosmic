<?php

//==========================================================
// フレームワーク関連
//==========================================================

// ライブラリのインクルード
function cmn__include_libs($libs) {
	foreach ($libs as $one) {
		include_once($one.'.inc.php');
	}
}


//==========================================================
// ファイル操作関連
//==========================================================

// ファイルパスより拡張子を返す
function cmn__get_fileext($path) {

	$p = strrpos($path,'.');
	if ($p!==false) {
		$ext = substr($path,$p+1);
	} else {
		$ext = '';
	}

	return $ext;
}

// scandirの再起呼び出し版
function cmn__scandir_r($path) {

	// 最後のスラッシュを除く
	if ($path!='' && substr($path,-1)=='/') {
		$path = substr($path,0,strlen($path)-1);
	}

	// 再起呼び出し
	$files = _cmn__scandir_r_proc($path,'');

	return $files;
}

// cmn__scandir_r が内部的に使う関数
function _cmn__scandir_r_proc($root,$subdir) {

	$result = array();
	if ($subdir!='') {
		$result[] = $subdir;
	}

	$files = scandir($root.'/'.$subdir);
	foreach ($files as $one) {
		if ($one=='.' || $one=='..') continue;
		$one = cmn__concat($subdir,$one,'/');
		if (is_dir($root.'/'.$one)) {
			$result = array_merge($result,_cmn__scandir_r_proc($root,$one));
		} else {
			$result[] = $one;
		}
	}

	return $result;
}


//==========================================================
// 文字列操作関連
//==========================================================

// 連結文字を考慮した文字列結合
function cmn__concat($str1,$str2,$glue='') {
	$str = $str1;
	if ($str!='') $str .= $glue;
	$str .= $str2;
	return $str;
}

?>