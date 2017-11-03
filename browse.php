<?php

// ファイルブラウザ

include_once('./config/site.inc.php');

// とりあえず／抜いとけば安全？
$request__ix = $my_cgi->check_request('ix','/^[^\/]+$/','');

if ($request__ix == '') {

	// ディレクトリインデックスを出力

	$dirs = array();

	$files = scandir(PATH_COMIC);
	foreach ($files as $one) {
		if (is_dir(PATH_COMIC.'/'.$one) && $one!='.' && $one!='..') {
			$dirs[get_dir_index($one)][] = $one;
		}
	}

	$comic = '';
	$index = array();
	$idx = 0;
	foreach ($dirs as $var => $val) {
		$index[] = '<a href="#'.$idx.'">'.$var.'</a>';
		$series = '';
		foreach ($val as $one) {
			$hash = md5($one);
			file_put_contents(PATH_CACHE_IX.'/'.$hash.CACHE_IX_EXT_DIR,$one);
			$series .= '<li><a href="./browse.php?ix='.$hash.'">'.htmlspecialchars($one).'</a></li>';
		}
		$comic .= '<a id="'.$idx.'"></a><h3>'.htmlspecialchars($var).'</h3>';
		$comic .= '<ul class="series_list">'.$series.'</ul>';
		$idx++;
	}

	$my_html->set_value('indexlist',implode('|',$index));
	$my_html->set_value('comiclist',$comic);

} else {

	// ファイルリストを出力

	$dir = file_get_contents(PATH_CACHE_IX.'/'.$request__ix.CACHE_IX_EXT_DIR);
	$files = scandir(PATH_COMIC.'/'.$dir);

	$html  = '<a href="./browse.php">&crarr;戻る</a>';
	$html .= '<h3>'.htmlspecialchars($dir).'</h3>';
	$html .= '<ul class="comic_list">';
	foreach ($files as $one) {
		if (preg_match('/.+\.zip$/usi',$one)) {
			$path = $dir.'/'.$one;
			$hash = md5($path);
			file_put_contents(PATH_CACHE_IX.'/'.$hash.CACHE_IX_EXT_ZIP,$path);

			// ブックマークリストを作成する
			$bookmark = '';
			if (file_exists(PATH_CACHE_IX.'/'.$hash.CACHE_IX_EXT_CX)) {
				$cx = file_get_contents(PATH_CACHE_IX.'/'.$hash.CACHE_IX_EXT_CX);
				$mark = array();
				$page = array();
				foreach (glob(PATH_CACHE_CX.'/'.$cx.str_replace('?','*',CACHE_CX_EXT_MARK)) as $path_to_mark) {
					$a_mark = unserialize(file_get_contents($path_to_mark));
					$mark[] = $a_mark;
					$page[] = $a_mark['pg'];
				}
				if (count($mark)>0) {
					array_multisort($page, SORT_ASC, $mark);
					foreach ($mark as $a_mark) {
						$bookmark .=
							'<a href="./viewer.php?ix='.urlencode($hash).
							'&amp;pg='.$a_mark['pg'].
							'&amp;re='.urlencode('./browse.php?ix='.$request__ix).
							'" title="'.htmlspecialchars('P.'.$a_mark['pg'].' '.htmlspecialchars($a_mark['comment'])).
							'">&para;</a><sup>'.$a_mark['pg'].'</sup>'
						;
					}
				}
			}
			if ($bookmark!='') {
				$bookmark = '<div class="bookmark_list">'.$bookmark.'</div>';
			}

			$title = mb_substr($one,0,mb_strlen($one)-4);
			$html .= '<li><a href="./viewer.php?ix='.urlencode($hash).'&amp;re='.urlencode('./browse.php?ix='.$request__ix).'">'.htmlspecialchars($title).'</a>'.$bookmark.'</li>';
		}
	}
	$html .= '</ul>';

	$my_html->set_value('comiclist',$html);
}

$my_html->apply_template('browse.html',html::REMOVE_UNDEF_TAGS,html::OUTPUT_HTML);



// ディレクトリ名から索引を求める
function get_dir_index($name) {
	global $config__dir_index;

	$ret = '';

	$f = mb_substr($name,0,1);
	foreach ($config__dir_index as $var => $val) {
		if (mb_strpos($val,$f) !== false) {
			$ret = $var;
			break;
		}
		if ($val == '') {
			$ret = $var;
		}
	}

	return $ret;
}


?>