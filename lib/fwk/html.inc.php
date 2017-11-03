<?php

//-------------------------------------------------------------------
// HTML出力テンプレートクラス
//-------------------------------------------------------------------

class html {

	// 定数
	const PP_TYPE    = 0;
	const PP_NAME    = 1;
	const PP_PARAM_1 = 2;
	const PP_PARAM_2 = 3;
	const PP_PARAM_3 = 4;
	const PP_PARAM_4 = 5;
	const PP_PARAM_5 = 6;

	const REMOVE_UNDEF_TAGS = true;
	const LEAVE_UNDEF_TAGS  = false;

	const OUTPUT_HTML = true;
	const RETURN_HTML = false;

	// 置き換え要素
	public $values = array();

	// テーマファイル格納用
	public $theme_files = array();

	// コンストラクタ
	public function html() {

		$this->values['$$'] = array();

	}

	// テーマファイルを検索し登録する
	public function probe_theme_files($root_path,$root_url,$use_theme,$default_theme='default') {

		$def_path_len = strlen($root_path.'/'.$default_theme.'/');

		$files = $this->_scandir_r($root_path.'/'.$default_theme);
		foreach ($files as $def_fid) {
			$one = substr($def_fid,$def_path_len);
			$use_fid = $root_path.'/'.$use_theme.'/'.$one;
			if (file_exists($use_fid)) {
				$path = $use_fid;
				$url  = $root_url.'/'.$use_theme.'/'.$one;
			} else {
				$path = $def_fid;
				$url  = $root_url.'/'.$default_theme.'/'.$one;
			}
			$this->regist_theme_file($one,$path,$url);
		}

	}

	// 再帰的にディレクトリをスキャンする関数
	private function _scandir_r($path) {
		$result = array();
		$files = scandir($path);
		foreach ($files as $one) {
			if ($one=='.' || $one=='..') continue;
			if (is_dir($path.'/'.$one)) {
				$result = array_merge($result,$this->_scandir_r($path.'/'.$one));
			} else {
				$result[] = $path.'/'.$one;
			}
		}
		return $result;
	}

	// テーマファイルの登録
	public function regist_theme_file($name,$path,$url) {
		$this->theme_files[$name] = array(
			'path' => $path,
			'url'  => $url,
		);
	}

	// テンプレートファイルパスを戻す
	public function get_theme_file_path($name) {
		return $this->theme_files[$name]['path'];
	}

	// テンプレートファイルURLを戻す
	public function get_theme_file_url($name) {
		return $this->theme_files[$name]['url'];
	}

	// 置き換え要素の登録
	public function set_value($var,$val) {
		$this->values[$var] = $val;
	}

	// 置き換え要素の登録(配列版)
	public function set_value_array($ary) {
		$this->values = array_merge($this->values,$ary);
	}

	// スタイルの摘要
	public function apply_style($row,$style) {
		$html = file_get_contents($this->get_theme_file_path('style/'.$style));

		// PHP5.3以降(無名関数版)
/*
		$html = preg_replace_callback(
			'/{{row:(.+?)}}/',
			function ($match) {
				return $row[$match[1]];
			},
			$html
		);
*/
		// PHP5.3未満(クロージャスコープ参照不可なためゴリ展開)
		$seri = serialize($row);
		$html = preg_replace_callback(
			'/{{row:(.+?)}}/',
			create_function(
				'$match',
				'$seri = '.var_export($seri,true).';'.
				'$row = unserialize($seri);' .
				'return $row[$match[1]];'
			),
			$html
		);

		return $html;
	}

	// テンプレートの適用(最終出力用)
	public function apply_template($tmpl,$fixtag=false,$output=false) {

		$tmpl = $this->get_theme_file_path($tmpl);

		if (file_exists($tmpl)) {
			$html = file_get_contents($tmpl);
		} else {
			$html = '{{doc}}';
		}

		$html = $this->apply($html,$fixtag);

		if ($output) {
			echo $html;
		}

		return $html;
	}

	// {{}}タグの置換
	public function apply($html,$fixtag=false) {

		// include:xxxx
		// テンプレートファイルを読み込んで置き換えます
		$html = $this->_apply_include($html);

		// 未解決タグの処理
		if ($fixtag) {
			$html = $this->_remove_undefined_tags($html);
		}

		// その他
		// 通常パラメータを置き換えます
		$html = $this->_apply_replace($html);

		return $html;
	}

	// $$置き換え準備
	public function prepare($html) {

		$html = preg_replace_callback(
			'/{{set:(.+?)}}/',
			array($this,'_prepare_callback'),
			$html
		);

		return $html;

	}

	// $$置き換え準備(コールバック関数)
	private function _prepare_callback($match) {

		$dat = preg_split('/,/',$match[1]);

		$this->values['$$'][] = $dat;

		if ($dat[1]=='none') {
			$tag = '';
		} else {
			$tag = '{{'.$dat[1].'}}';
		}

		return $tag;

	}

	// インクルード処理
	private function _apply_include($html) {

		$html = preg_replace_callback(
			'/{{include:(.+?)}}/',
			array($this,'_apply_include_callback'),
			$html
		);

		return $html;

	}

	// インクルード処理(コールバック関数)
	private function _apply_include_callback($match) {

		$path = $this->get_theme_file_path('include/'.$match[1]);
		if (file_exists($path)) {
			return file_get_contents($path);
		} else {
			return '';
		}
	}

	// 置き換え処理
	private function _apply_replace($html) {

		$html = preg_replace_callback(
			'/{{((.+):)?(.+?)}}/',
			array($this,'_apply_replace_callback'),
			$html
		);

		return $html;

	}

	// 置き換え処理(コールバック関数)
	private function _apply_replace_callback($match) {

		$val = '';

		switch ($match[2]) {
		case 'themefile':
			$val = $this->get_theme_file_url($match[3]);
			break;
		default:
			if (array_key_exists($match[3],$this->values)) {
				$val = $this->values[$match[3]];
			} else {
				$val = $match[0];
			}
		}

		return $val;
	}

	// 未解決タグを解決
	private function _remove_undefined_tags($html) {

		$html = preg_replace_callback(
			'/{{((.+):)?(.+?)}}/',
			array($this,'_remove_undefined_tags_callback'),
			$html
		);

		return $html;

	}

	// 未解決タグを解決(コールバック関数)
	private function _remove_undefined_tags_callback($match) {

		$val = $match[0];

		if ($match[2]=='' && !array_key_exists($match[3],$this->values)) {
			$val = '';
		}

		return $val;
	}

	// scriptタグの作成
	public static function mktag_script($url) {
		return '<script type="text/javascript" src="'.$url.'"></script>';
	}

}


//-------------------------------------------------------------------
// CGI処理用クラス
//-------------------------------------------------------------------

class cgi {

	// GET, POST チェック用正規表現
	const CV_TEXT   = '/^.+$/';
	const CV_ALNUM  = '/^[[:alnum:]]+$/';  // アルファベットと数値、[:alpha:] + [:digit:]
	const CV_ALPHA  = '/^[[:alpha:]]+$/';  // 大小文字アルファベット、 [:lower:] + [:upper:]
	const CV_LOWER  = '/^[[:lower:]]+$/';  // 小文字アルファベット
	const CV_UPPER  = '/^[[:upper:]]+$/';  // 大文字アルファベット
	const CV_DIGIT  = '/^[[:digit:]]+$/';  // 数値
	const CV_BLANK  = '/^[[:blank:]]+$/';  // 空白文字、スペースとタブ
	const CV_CNTRL  = '/^[[:cntrl:]]+$/';  // 制御文字(000-037, 177('DEL')
	const CV_GRAPH  = '/^[[:graph:]]+$/';  // グラフィカル文字 ([:alnum:] と [:punct:])|
	const CV_PRINT  = '/^[[:print:]]+$/';  // 印字可能な文字、[:alnum:] + [:punct:] + space
	const CV_PUNCT  = '/^[[:punct:]]+$/';  // パンクチュエーション文字 ! " # $ % & ' ( ) * + , - . /
	const CV_SPACE  = '/^[[:space:]]+$/';  // 空白文字、タブ、改行、水平タブ、給紙、キャリッジリターン、空白
	const CV_XDIGIT = '/^[[:xdigit:]]+$/'; // 16進数 0 1 2 3 4 5 6 7 8 9 A B C D E F a b c d e f
	const CV_MD5    = '/^[a-f0-9]{32}$/';  // md5ハッシュ値
	const CV_EMAIL  = '/^[\w.-]+\@([\w-]+\.)+\w+$/'; // メールアドレス

	// GET, POST パラメータチェック
	public static function check_request($var,$chk='',$def='') {

		$val = isset($_REQUEST[$var]) ? $_REQUEST[$var] : $def;
		if ($chk!='') {
			if (preg_match($chk,$val)==0) {
				$val = $def;
			}
		}

		$_REQUEST[$var] = $val;

		return $val;
	}

	// 一般変数の内容チェック
	public static function check_value(&$val,$pat,$def) {

		if (preg_match($pat,$val)==0) {
			$val = $def;
		}

		return $val;
	}

}


?>