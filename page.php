<?php

include_once('./config/site.inc.php');

$request__ix = $my_cgi->check_request('ix',cgi::CV_MD5  ,'');
$request__pg = $my_cgi->check_request('pg',cgi::CV_DIGIT,1);
$request__pg--;

$fid_ix_zip = PATH_CACHE_IX.'/'.$request__ix.CACHE_IX_EXT_ZIP;
$fid_ix_cx  = PATH_CACHE_IX.'/'.$request__ix.CACHE_IX_EXT_CX;

if (!file_exists($fid_ix_zip)) {
	die('error:zip not found');
}

if (file_exists($fid_ix_cx)) {
	$hash_cx = file_get_contents($fid_ix_cx);
	$hash_cx = cgi::check_value($hash_cx,cgi::CV_MD5,'');
} else {
	$hash_cx = '';
}

$fid_comic = PATH_COMIC.'/'.file_get_contents($fid_ix_zip);

if (!file_exists($fid_comic)) {
	die('error:'.$fid_comic.' not found');
}

// zipファイルのエントリリストを取得(画像ファイルに限定)
if ($hash_cx=='') {

	$entry_data = array();
	$entry_name = array();
	$zip = zip_open($fid_comic);
	$no = -1;
	$img = array(NULL,NULL);
	while(($zip_entry = zip_read($zip)) !== false){
		$no++;
		$zip_entry_name = zip_entry_name($zip_entry);
		$zip_entry_type = strtolower(cmn__get_fileext($zip_entry_name));
		if ($zip_entry_type=='' || mb_strpos('jpg/jpeg/png/gif',$zip_entry_type)===false) continue;
		$entry_data[] = array(
			'no'   => $no,
			'name' => $zip_entry_name,
			'size' => zip_entry_filesize($zip_entry),
		);
		$entry_name[] = $zip_entry_name;
	}
	zip_close($zip);
	array_multisort($entry_name, SORT_ASC, $entry_data);

	$cx_list = serialize($entry_data);
	$hash_cx = md5($cx_list);
	$fid_cx_list = PATH_CACHE.'/comic/'.$hash_cx.'_list.txt';

	file_put_contents($fid_ix_cx,$hash_cx);
	file_put_contents($fid_cx_list,$cx_list);

} else {

	$entry_data = unserialize(file_get_contents(PATH_CACHE.'/comic/'.file_get_contents($fid_ix_cx).'_list.txt'));

}

// ページ番号からzip内番号に変換する
$zip_no[0] = isset($entry_data[$request__pg  ]) ? $entry_data[$request__pg  ]['no'] : -1;
$zip_no[1] = isset($entry_data[$request__pg+1]) ? $entry_data[$request__pg+1]['no'] : -1;

// zipファイルから指定されたページとその次ページを持ってくる
$zip = zip_open($fid_comic);
$no = 0;
$img = array(NULL,NULL);
while(($zip_entry = zip_read($zip)) !== false){
	foreach ($zip_no as $pgid => $target_no) {
		if ($no==$target_no) {
			$img[$pgid] = zip_entry_read($zip_entry,zip_entry_filesize($zip_entry));
		}
	}
	if (!is_null($img[0]) && !is_null($img[1])) break;
	$no++;
}
zip_close($zip);


// 画像を作成して出力する
if (is_null($img[0])) {

	// ページが無かった場合
	echo "no image";

} else if (is_null($img[1])) {

	// 1ページしか無かった場合
	header("Content-type: image/jpeg");
	echo $img[0];

} else {

	// 2ページある

	// GDイメージを作成
	for ($pgid=0;$pgid<=1;$pgid++) {
		$img[$pgid] = imagecreatefromstring($img[$pgid]);
		$sx[$pgid] = imagesx($img[$pgid]);
		$sy[$pgid] = imagesy($img[$pgid]);
	}

	// 
	if ($sx[0]>$sy[0] || $sx[1]>$sy[1]) {
		// 横長ページが含まれるときは、1ページ目のみを戻す
		header("Content-type: image/jpeg");
		imagejpeg($img[0]);
	} else {
		// 2ページを並べる
		$vx = $sx[0]+$sx[1];
		$vy = ($sy[0]>$sy[1] ? $sy[0] : $sy[1]);
		$view = imagecreatetruecolor($vx,$vy);
		header("Content-type: image/jpeg");
		imagecopy($view,$img[0],$sx[1],($vy-$sy[0])/2,0,0,$sx[0],$sy[0]);
		imagecopy($view,$img[1],0,($vy-$sy[1])/2,0,0,$sx[1],$sy[1]);
		imagejpeg($view);
	}


}

?>