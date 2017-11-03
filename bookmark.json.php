<?php

include_once('./config/site.inc.php');

$request__q  = $my_cgi->check_request('q' ,'/^get|mark|remove|clear$/','get');
$request__ix = $my_cgi->check_request('ix',cgi::CV_MD5,'');

$request__pg  = $my_cgi->check_request('pg' ,cgi::CV_DIGIT,'');
$request__com = $my_cgi->check_request('com',cgi::CV_TEXT ,'');


if (!file_exists(PATH_CACHE_IX.'/'.$request__ix.CACHE_IX_EXT_CX)) {
	die('error');
}


$cx = file_get_contents(PATH_CACHE_IX.'/'.$request__ix.CACHE_IX_EXT_CX);


switch ($request__q) {

case 'mark':
	// q = mark ブックマークの登録

	$fid_mark = PATH_CACHE_CX.'/'.$cx.str_replace('?',$request__pg,CACHE_CX_EXT_MARK);

	$mark = array(
		'pg'      => $request__pg,
		'comment' => $request__com
	);

	file_put_contents($fid_mark,serialize($mark));

	break;

case 'remove':
	// q = remove ブックマークの削除

	$fid_mark = PATH_CACHE_CX.'/'.$cx.str_replace('?',$request__pg,CACHE_CX_EXT_MARK);
	unlink($fid_mark);

	break;

case 'clear':
	// q = clear ブックマークの全消去

	foreach (glob(PATH_CACHE_CX.'/'.$cx.str_replace('?','*',CACHE_CX_EXT_MARK)) as $one) {
		unlink($one);
	}

	break;

}


// q = get (省略可能) ブックマークリストの取得

$json = array();
$page = array();
$cnt = 0;

foreach (glob(PATH_CACHE_CX.'/'.$cx.str_replace('?','*',CACHE_CX_EXT_MARK)) as $one) {

	$mark = unserialize(file_get_contents($one));

	$json['mark'][] = $mark;
	$page[] = $mark['pg'];

	$cnt++;
}

if ($cnt>0) {
	array_multisort($page, SORT_ASC, $json['mark']);
}

$json['count'] = $cnt;

echo json_encode($json);

?>