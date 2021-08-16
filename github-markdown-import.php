<?php
function get_github_markdown($url_api){
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $url_api);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curl, CURLOPT_TIMEOUT, 8);
	curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
	$headers = array();
	$headers[] = 'Accept: application/vnd.github.v3+json';
	$headers[] = 'Content-Type: application/json';
	curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
	$output = curl_exec($curl);
	curl_close($curl);
	return base64_decode(json_decode($output)->content);
}

function get_github_html($content_mardown){
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, 'https://api.github.com/markdown');
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curl, CURLOPT_TIMEOUT, 8);
	curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
	$headers = array();
	$headers[] = 'Accept: application/vnd.github.v3+json';
	$headers[] = 'Content-Type: application/x-www-form-urlencoded';
	curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($curl, CURLOPT_POST, 1);
	curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode(['text'=>$content_mardown]));	
	$output = curl_exec($curl);
	curl_close($curl);
	return $output;
}

function store_document($path) {
	$content_markdown = get_github_markdown('https://api.github.com/repos/'.$path);
	$content_html = get_github_html($content_markdown);	
	$n = 0;
	$menu = PHP_EOL.'<div class="sticky-top mb-5" style="margin-left:-28px;">'.PHP_EOL;
	$menu .= '<nav id="navbar-scrollspy" class="navbar pt-5">'.PHP_EOL;
	$menu .= '<nav class="nav nav-pills flex-column">'.PHP_EOL;
	$ref = 2;
	$content_html = preg_replace_callback('/<h([1|2|3])>\s*<a.*?<\/a>(.*?)<\/h[1|2|3]>/s',
			function($matches) use (&$n, &$menu, &$ref) {
				if ($matches[1] != 1) {
					$n++;
					$tab = ($matches[1] == 3) ? ' ml-3 my-1': '';
					if ($matches[1] == $ref) {
						$menu .= '<a class="nav-link'.$tab.'" href="#item-'.$matches[1].'-'.$n.'">'.$matches[2].'</a>'.PHP_EOL;
					} else {
						if ($matches[1] == 2) $menu .= '</nav>'.PHP_EOL.'<a class="nav-link" href="#item-2-'.$n.'">'.$matches[2].'</a>'.PHP_EOL;
						if ($matches[1] == 3) $menu .= '<nav class="nav nav-pills flex-column">'.PHP_EOL.'<a class="nav-link'.$tab.'" href="#item-3-'.$n.'">'.$matches[2].'</a>'.PHP_EOL;
						$ref = $matches[1];
					}
				}
				return PHP_EOL."<h".$matches[1].' id="item-'.$matches[1].'-'.$n.'">' . $matches[2] . '</h'.$matches[1].'>';
		}, $content_html);	
	$menu .= ($ref == 3) ? '</nav>'.PHP_EOL : '';	
	$menu .= '</nav>'.PHP_EOL;
	$menu .= '</nav>'.PHP_EOL;
	$menu .= '</div>'.PHP_EOL;
	$content_html = PHP_EOL.'<div style="margin-bottom:800px">'.$content_html.'</div>'.PHP_EOL;
	Storage::put('github_import/'.rawurlencode($path).'_menu', $menu);
	Storage::put('github_import/'.rawurlencode($path).'_content', $content_html);
}

function github_import($path) {
	if ((isset($_GET['a']) AND $_GET['a'] == 'sync')
		OR (!Storage::disk('local')->exists('github_import/'.rawurlencode($path).'_menu'))) {
		store_document($path);
	}	
	$github_document['menu'] = Storage::get('github_import/'.rawurlencode($path).'_menu');
	$github_document['content'] = Storage::get('github_import/'.rawurlencode($path).'_content');
	return $github_document;
}
?>
