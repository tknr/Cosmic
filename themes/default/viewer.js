
// 表示しているコミック識別用
request__ix = '';
request__pg = 1;
request__re = '';


// ページ保持用変数
pg_cur = 1;
pg_max = 1;

// コミック情報取得済識別用
info_read = 0;

// ツールバー復元用
toolbar_h = 0;

// 自動ページ送り用
auto_timerid  = null;
auto_interval = 20;
auto_icon_on  = String.fromCharCode(9654); // 右向き▲です
auto_icon_off = "■";

// イメージキャッシュ用
image_cache = [];
image_cache_size = 5;


// 前のページに戻る
function goback() {

	if (request__re=='') {
		history.back();
	} else {
		location.href = request__re;
	}


}

// ページロード時の処理
$(function() {

	// 初期表示のチラツキを無くすために、ロード直後にフェードアウト
	$("img#viewbox_canvas").fadeOut(0);
	viewbox_resize();

	request__ix = $("#request_ix").html();
	request__pg = Number($("#request_pg").html());
	request__re = $("#request_re").html();

	pg_cur = request__pg;
	page_read();

});


// キー入力時の処理
$(document).keydown( function(e) {

	switch (e.which) {

	// ←
	case 37:
		page_change(+1);
		break;

	// →
	case 39:
		page_change(-1);
		break;

	// ↑
	case 38:
		page_change(+2);
		break;

	// ↓
	case 40:
		page_change(-2);
		break;

	// SPACEBAR
	case 32:
		if ($("#toolbar").height()>0) {
			toolbar_h = $("#toolbar").height();
			h = 0;
		} else {
			h = toolbar_h;
		}
		$("#toolbar").animate(
			{ 'height' : h},
			'fast',
			null,
			function (){
				// autoに戻さないと固定されてしまう
				if (h>0) { $("#toolbar").css("height","auto"); }
				viewbox_resize();
			}
		);
		break;

	// HOME
	case 36:
		pg_cur = 1;
		page_read();
		break;

	// END
	case 35:
		pg_cur = pg_max;
		page_read();
		break;

	// Page UP
	case 33:
		page_change(+10);
		break;

	// Page DOWN
	case 34:
		page_change(-10);
		break;

	// J
	case 74:
		$("#cmd_page").trigger("click");
		break;

	// A
	case 65:
		auto_timer_on_off( auto_timerid == null );
		break;

	// キーコード調査用(運用時はコメントアウトしとく)
	default:
	//	alert('Key = ' + e.which);
		break;

	}

});


// ウィンドウリサイズ時の処理
$(window).resize(function(){

	viewbox_resize();

});


// イベントハンドラの定義
$(function() {

	$("#cmd_next").click(function(){
		page_change(+1);
	});

	$("#cmd_prior").click(function(){
		page_change(-1);
	});

	$("#cmd_next2").click(function(){
		page_change(+2);
	});

	$("#cmd_prior2").click(function(){
		page_change(-2);
	});

	$("#viewbox_canvas").click(function(){
		page_change(+1);
	});

	$("#cmd_page").click(function(){
		pg_new = prompt('移動するページ番号を入力してください',pg_cur);
		if (pg_new != null) {
			if (isNaN(pg_new)) {
				alert('有効な数値を入力してください');
			} else {
				page_jump(Number(pg_new));
			}
		}
	});

	$("#cmd_auto").click(function(){
		if (auto_timerid == null) {
			interval = prompt('自動ページ送り：表示間隔を秒数で入力してください','20');
			if (interval != null) {
				if (isNaN(interval)) {
					alert('有効な数値を入力してください');
				} else {
					auto_interval = Number(interval)*1000;
					auto_timer_on_off(true);
				}
			}
		} else {
			auto_timer_on_off(false);
		}
	});

	$("#cmd_bookmark").toggle(
		function(){
			$("#bookmark").css("display","block");
			$("#bookmark").animate({
				top: "0"
			},300);
		},
		function(){
			$("#bookmark").animate({
				top: "-20.5em"
			},300);
		}
	);

});


// 表示ページを変更する
function page_change(n) {

	pg_bak = pg_cur;

	pg_cur += n;

	if (pg_cur<1) {
		pg_cur = 1;
	}

	if (pg_cur>pg_max) {
		pg_cur = pg_max;
		// 自動送りタイマーがセットされていたら停止させる
		if (auto_timerid != null) {
			$("a#auto").trigger("click");
		}
	}

	if (pg_cur!=pg_bak) {
		page_read();
		// 自動送りタイマーがセットされていたら残り秒数をリセット
		if (auto_timerid != null) {
			auto_timer_on_off(true);
		}
	}

}


// ページ指定ジャンプ
function page_jump(pg) {
	pg_cur = 0;
	page_change(pg);
}


// ページを読み込んで表示する
function page_read() {

	$("#viewbox_canvas").fadeOut("fast",function() {
		$(this).attr("src","./page.php?ix="+request__ix+"&pg="+pg_cur);
		$(this).load(function () {

			// 最初のページ読み込み後にコミックの情報を取得する

			if (info_read==0) {

				$.getJSON("./comic.json.php?q=page&ix="+$("#request_ix").html(), function(json){
					$("#title").text(json.path);
					pg_max = json.page.max;
					page_change_after();
				});

				$.getJSON("./bookmark.json.php?q=get&ix="+$("#request_ix").html(), function(json){
					bookmark_parse(json);
				});

				info_read = 1;

			} else {

				page_change_after();

			}

			$(this).fadeIn("fast");

		});
	});

}

function page_change_after() {

	$("#cmd_page").text(pg_cur + " / " + pg_max);

	$(window).trigger("resize");

	// 次の1ページをプレロード
	if (info_read == 1 && pg_cur < pg_max) {
		image = document.createElement('img');
		image.src = "./page.php?ix="+request__ix+"&pg="+(pg_cur+1);
		image_cache.push(image);
		if (image_cache.length > image_cache_size) {
			image_cache.shift();
		}
	}

}


// 表示ボックスをリサイズ
function viewbox_resize() {

	h = $(window).height() - $("div#toolbar").height();

	$("#viewbox").attr("height",h);
	$("#viewbox_canvas").attr("height",h*0.95);

}

// 自動ページ送りのタイマーを作動させる
function auto_timer_on_off(sw) {

	if (auto_timerid != null) {
		clearInterval(auto_timerid);
		auto_timerid = null;
	}

	if (sw) {
		auto_timerid = setInterval("page_change(+1)",auto_interval);
		$("a#auto").text(auto_icon_off);
	} else {
		$("a#auto").text(auto_icon_on);
	}

}


/* 新しいブックマークを追加 */
function bookmark_create() {

	comment = prompt('ブックマーク(P.'+pg_cur+')：コメントを入力して下さい','');
	if (comment != null) {
		$.post(
			'./bookmark.json.php',
			{
				'q'   : 'mark',
				'ix'  : request__ix,
				'pg'  : pg_cur,
				'com' : comment
			},
			function(data) {
				json = $.parseJSON(data);
				bookmark_parse(json);
			}
		);
	}

}

/* ブックマークを削除 */
function bookmark_remove(pg) {

	$.post(
		'./bookmark.json.php',
		{
			'q'   : 'remove',
			'ix'  : request__ix,
			'pg'  : pg
		},
		function(data) {
			json = $.parseJSON(data);
			bookmark_parse(json);
		}
	);

}

/* ブックマーク全消去 */
function bookmark_clear() {

	if (confirm('登録されているブックマークをすべて消去しますか？')) {
		$.post(
			'./bookmark.json.php',
			{
				'q'   : 'clear',
				'ix'  : request__ix
			},
			function(data) {
				json = $.parseJSON(data);
				bookmark_parse(json);
			}
		);
	}

}

// 受け取ったjsonデータをメニューに表示
function bookmark_parse(json) {

	$("#bookmark_list").empty();

	for (i=0;i<json.count;i++) {
		li =
			"<li><a href=\"javascript://\" onclick=\"javascript:bookmark_click("+json.mark[i].pg+");\">"+
			json.mark[i].comment+"(P."+json.mark[i].pg+")</a> "+
			"<a href=\"javascript://\" onclick=\"javascript:bookmark_remove("+json.mark[i].pg+");\">×削除</a>"+
			"</li>"
		;
		$("#bookmark_list").append(li);
	}

}

// ブックマークの全消去
function bookmark_click(pg) {
	page_jump(pg);
	$('#cmd_bookmark').trigger('click');
}
