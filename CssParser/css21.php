<?php
class Css21 extends AParser {

	function cssClean($css)
	{
		// コメントの削除
		$css = preg_replace('/\/\*[^*]*\*+([^\/][^*]*\*+)*\//m', '', $css);
		// 改行の削除
		$css = preg_replace('/\s*\n+\s*/m', chr(32), $css);

		return trim($css);
	}

}