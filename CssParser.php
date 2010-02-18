<?php
/**
 * 高速でコンパクト, 未来指向の JavaScript ライブラリ
 * uupaa.js (http://code.google.com/p/uupaa-js)
 * uupaa.js is licensed under the terms and conditions of the MIT licence.
 * (http://uupaa-js.googlecode.com/svn/trunk/0.7/doc/LICENSE.htm)
 * の
 * cssパーサ部分
 * ( http://code.google.com/p/uupaa-js/source/browse/trunk/0.7/uu.css.parse.js )
 * をPHPに移殖し、ソフトバンクのcssをチェックできるようにしたものです。
 *
 * @license MIT License
 */



class CssParser
{
	protected $setError = array();

	protected $selectorParseList = array(
			'universal'     => '/^(\*)/',
			'descendant'   => '/^( )/',
			'id'           => '/^(#[a-zA-Z][\w\-]*)/',
			'class'        => '/^(\.[a-zA-Z][\w\-]*)/',
			'child'        => '/^(>)/',

			//Pseudo-classes
			'link'         => '/^(:(link|visited))/',
			'dynamic'      => '/^(:(link|visited|hover|active|focus))/',

			//Pseudo-elements

			//type
			'type'         => '/^([a-zA-Z][\w\-]*)/',
	);

	/**
	 * 文字列をパースしてCssParser_Nodeインスタンスからなる構文木を返す。
	 *
	 * @param string $css string
	 *
	 * @return CssParser_Node
	 */
	function parse($css)
	{
		$rv = array('specs' => array(), 'data' => array());
		$escape = $ignore = 0;

		$css = self::cssClean($css);
		if(!isset($css)) return $result;
		$ary = preg_split('/\s*\{|\}\s*/', $css, -1, PREG_SPLIT_NO_EMPTY);
		if($ary % 2 === 0) throw new InvalidArgumentException;

		for ($i = 0; $i < count($ary); $i += 2) {
			// 初期化
			$expr = $ary[$i];                              // "E>F,G"
			$decl = trim($ary[$i + 1]);                    // "color:red;text-aligh:left"
			$exprs = preg_split('/\s*,\s*/', $expr, -1, PREG_SPLIT_NO_EMPTY);       // ["E>F", "G"]
			$decls = preg_split('/\s*;\s*/', $decl . ';', -1, PREG_SPLIT_NO_EMPTY); // ["color:red", "text-align:left"]
			$gd1 = $gd2 = $gp1 = $gp2 = array();
			$gd1i = $gd2i = $gp1i = $gp2i = -1;

			// 処理
			for ($k = 0, $kz = count($decls); $k < $kz; ++$k) {
				$ignore = 0;
				if ($decls[$k]) {
					$both = preg_split('/\s*:\s*/', $decls[$k], -1, PREG_SPLIT_NO_EMPTY);
					$prop = array_shift($both);	// "color:red" -> "color"

					$val = implode(':', $both); // "color:red" -> "red"
					if (mb_strpos(chr(92), $val)) {
						++$ignore;
					} elseif (preg_match('/\!\s*important/i', $val)) { // [!important] rule
						$val = preg_replace('/\s*!\s*important\s*/i', '', $val); // trim "!important"
						// TODO 速度優先モードのみ
						// 常に成功する
						// uu.config.light is light weight mode //ja 1 で速度優先モードを有効にする
						// valid = (!uu.config.light && valids[prop]) ?
						//					uu.css.validate[prop](val).valid : 1;
						$valid = (self::blockValidate($prop, $val) === true) ? 1 : 0;
						if ($valid) {
							$gd2[++$gd2i] = $prop . ':' . $val;
							$gp2[++$gp2i] = array('prop' => $prop, 'val' => $val);
						} else {
							++$ignore;
						}
					} else { // [normal] rule
						$valid = (self::blockValidate($prop, $val) === true) ? 1 : 0;
						if ($valid) {
							$gd1[++$gd1i] = $prop . ':' . $val; // "color:red"
							$gp1[++$gp1i] = array('prop' => $prop, 'val' => $val); //{prop:"color",val:"red"}
						} else {
							++$ignore;
						}
					}
					if($ignore) $this->setError('"' . $prop . ":" . $val + '" ignore decl');
				}
			}

			// セレクタの前処理
			// セレクタを解析できない場合，宣言ブロックごと(グループ化されているものも)無視
			foreach ($exprs as $n => $v) {
				$tmp = $this->selectorValidate(
					$v,
					array(
						'parseList' => array(
							'adjacent'     => null,
							'adjacent'     => null,
							'attribute'    => null,
							'first-child'  => null,
							'language'     => null,
							'first-line'   => null,
							'first-letter' => null,
							'before-after' => null,
						)
					)
				);

				if($tmp['valid'] !== true) {
					continue 2;
				} else {
					$exprs[$n] = $tmp['cleanSelector'];
				}
			}

			// セレクタの処理
			for ($j = 0, $jz = count($exprs); $j < $jz; ++$j) {
				$v = $exprs[$j];

				// 重みを計算する
				$spec = $this->_calcSpec($v);
				if (count($gd1)) { // normal rule
					if(!isset($spec, $rv['data'][$spec])) $rv['specs'][] = $spec;
					$rv['data'][$spec][] = array('expr' => $v, 'decl' => $gd1, 'pair' => $gp1);
				}
				if (count($gd2)) { // !important rule
					$spec += 10000;
					if(!isset($spec, $rv['data'][$spec])) $rv['specs'][] = $spec;
					$rv['data'][$spec][] = array('expr' => $v, 'decl' => $gd2, 'pair' => $gp2);
				}
			}
		}

		// 重みをソート
		sort($rv['specs']);

		return $rv;
	}

	/**
	 * セレクタの重みの計算
	 *
	 * @param string $expr string
	 *
	 * @return Number: spec value
	 */
	function _calcSpec($expr)
	{
		$a = $b = $c = 0;

		$_specList = array(
			array('/#[\w\x00C0-\xFFEE\-]+/' , 'a'),  // id
			array('/\.[\w\x00C0-\xFFEE\-]+/' , 'b'), // class
			array('/\w+/' , 'c'),                    // E
		);

		foreach ($_specList as $n => $_spec)
		for ($i = 0, $expr = preg_replace($_spec[0], '', $expr, -1, $count); $i < $count; $i++) {
			${$_spec[1]}++;
		}
		return $a * 100 + $b * 10 + $c;
	}


	/**
	 * セレクタのチェックをする。
	 *
	 * @param string $selector string without comma
	 * @param array  $option   option
	 *
	 * @return array
	 */
	function selectorValidate($selector,Array $option = array())
	{
		$selector = trim($selector);
		$result = array(
			'selector' => $selector,
			'cleanSelector' => null,
			'parsedSelector' => array(),
			'error' => array(),
			'valid' => false,
		);

		$parseList = isset($option['parseList']) ? $option['parseList'] : array();

		//余分な空白を取り除く
		$result['cleanSelector'] = $selector = preg_replace(
			array('/\s+/', '/\s*>\s*/', '/\s*\+\s*/'),
			array(' ',     '>',         '+'),
			$selector
		);

		// 単純セレクタ（simple selector）や結合子（combinators）に分割する
		$result['parsedSelector'] = self::selectorParse($selector, $parseList);

		// 単純セレクタ（simple selector）や結合子（combinators）の構文をチェックする
		$syntax = self::selectorSyntax($result['parsedSelector']);
		if(!empty($syntax)) $result['error'] = array_merge($result['error'], $syntax);

		//エラーがなければ成功
		if(empty($result['error'])) $result['valid'] = true;

		return $result;
	}


	/**
	 * 単純セレクタ（simple selector）や結合子（combinators）に分割する
	 *
	 * @param string $selector  dirty selector ('div    * div#id   > .class')
	 * @param array  $parseList selectorSyntaxに渡すオプション。
	 * 検索を無効にする場合は、nullを指定。書き換える場合は、正規表現を記述。
	 * array('child' => null, 'add name' => 'regex')
	 *
	 * @return array
	 */
	function selectorParse($selector,Array $parseList = array())
	{
		$res = array();

		// 単純セレクタ（simple selector）や結合子（combinators）に分割する
		$before = 0;
		while (mb_strlen($selector) !== 0) {
			foreach (array_merge($this->selectorParseList, $parseList) as $type => $pattern) {
				if ($pattern !== null && preg_match($pattern, $selector, $matches)) {
					$res[] = array($type, $matches[1]);
					$selector = mb_substr($selector, mb_strlen($matches[1]));
					$seek += mb_strlen($matches[1]);
					break;
				}
			}
			if ($seek === $before) {
				$result['error'] = array('parse' => mb_substr($selector, 0, 1));
				break;
			}
			$before = $seek;
		}
		return $res;
	}

	/**
	 * 単純セレクタ（simple selector）や結合子（combinators）の構文をチェックする
	 *
	 * @param array $selector array(array('universal', '*'),array('type', 'div'))
	 *
	 * @return array
	 */
	function selectorSyntax(Array $selector)
	{
		$result = array();

		$selectorSyntaxList = array(
			'start' => array(
				'end'        => false,
				'combinator' => false,
				'universal'  => true,
				'type'       => true,
				'id'         => true,
				'link'       => true,
			),
			'combinator' => array(
				'end'        => false,
				'combinator' => false,
				'universal'  => true,
				'type'       => true,
				'id'         => true,
				'link'       => true,
			),
			'universal' => array(
				'end'        => true,
				'combinator' => true,
				'universal'  => false,
				'type'       => false,
				'id'         => false,
				'link'       => true,
			),
			'type' => array(
				'end'        => true,
				'combinator' => true,
				'universal'  => false,
				'type'       => false,
				'id'         => true,
				'link'       => true,
			),
			'id' => array(
				'end'        => true,
				'combinator' => true,
				'universal'  => false,
				'type'       => false,
				'id'         => true,
				'link'       => true,
			),
			'link' => array(
				'end'        => true,
				'combinator' => true,
				'universal'  => false,
				'type'       => false,
				'id'         => true,
				'link'       => true,
			),
		);

		//グルーピングルール
		$group = array(
			'combinator' => array('descendant', 'child'),
			'id'          => array('id', 'class'),
			'link'        => array('link', 'dynamic'),
		);

		// 分割したセレクタの構文をチェックする
		$before = 'start';
		$selector[] = array('end' , '{'); // 一番最後にと終了を意味する識別文字を挿入する
		foreach ($selector as $n => $v) {
			$test = $v[0];
			foreach ($group as $name => $member) { // セレクタのグルーピング
				if(in_array($test, $member)) $test = $name;
			}
			if ($selectorSyntaxList[$before][$test] === false) { //有効かどうかチェックする
				$result[($n === 0) ? $n : $n - 1] = ($n === 0) ? $selector[$n] : $selector[$n - 1];
				break;
			}
			$before = $test; // 今回の結果を記録する
		}

		return $result;
	}

	/**
	 * block(「{」~「}」)の構文チェック
	 * true  :成功
	 * 文字列:失敗したcssのvalue値
	 * false :失敗するcssのvalue値がない場合に返す
	 *
	 * @param string $prop property。改行を含まないこと。内部の余分な空白は削除しなくてもOK。グループでないこと。
	 * @param string $val  value。改行を含まないこと。内部の余分な空白は削除しなくてもOK。
	 *
	 * @return boolean|string
	 */
	static function blockValidate($prop,$val)
	{
		$prop = trim($prop);
		$val = trim($val);
		if($prop === '' || $val === '') return false;

		$length = '(?:[0-9]{1,4}(?:px|em|ex|in|cm|mm|pt|pc)|0)';
		$percentage = '(?:[0-9]{1,3}%)';
		$color = '(?:black|silver|gray|white|maroon|red|purple|fuchsia|green|lime|olive|yellow|navy|blue|teal|aqua|#[0-9a-fA-F]{3}|#[0-9a-zA-Z]{6})';
		$uri = '(?:.*?)';
		$absolutesize = '(?:xx-small|x-small|small|medium|large|x-large|xx-large)';
		$relativesize = '(?:smaller|larger)';

		$border_style = '(?:none|hidden|solid|groove|dotted|dashed|double|ridge|inset|outset)';
		$length_percentage_auto = "($length|$percentage|auto)";
		$length_percentage_1_4_auto = "(?:($length|$percentage)\s*".str_repeat("($length|$percentage)?\s*", 3)."|auto)";
		$left_right_center_justify = '(left|right|center|justify)';

		$padding_xxx = "($length|$percentage)";
		$length_percentage = "($length|$percentage)";
		$thin_medium_thick_length = "(thin|medium|thick|{$length})";
		$thin_medium_thick_length_1_4 = "(thin|medium|thick|$length)\s*".str_repeat("(thin|medium|thick|$length)?\s*", 3);
		$color_transparent = "($color|transparent)";
		$color_transparent_1_4 = "($color|transparent)\s*".str_repeat("($color|transparent)?\s*", 3);
		$none_hidden_solid_groove = "($border_style)";
		$none_hidden_solid_groove_1_4 = "($border_style)\s*".str_repeat("($border_style)?\s*", 3);
		$thin_none_color = "((?:thin|medium|thick)|(?:none|hidden|solid)|$color)";
		$normal_length_percentage = "(normal|$length|$percentage)";
		$baseline_sub_super_top = "(baseline|sub|super|top|text-top|middle|bottom|text-bottom)";
		$disc_circle_square_decimal = "(disc|circle|square|decimal|lower-roman|upper-roman|lower-alpha|upper-alpha|none)";
		$uri_none = "(url\([\"\']?{$uri}[\"\']?\)|none)";
		$inside_outside = "(inside|outside)";
		$disc_uri_inside = "((?:disc|circle|square|decimal|lower-roman|upper-roman|lower-alpha|upper-alpha|none)|(?:url\([\"\']?{$uri}[\"\']?\)|none)|(?:inside|outside))";
		$repeat = "(repeat|repeat-x|repeat-y|no-repeat)";
		$scroll_fixed = "(scroll|fixed)";
		$percentage_length_1_2_top_left = "(?:(?:($percentage|$length)\s*($percentage|$length)?)|(?:(top|center|bottom)\s*(left|center|right)))";
		$normal_italic_oblique = "(normal|italic|oblique)";
		$normal_smallcaps = "(normal|small-caps)";
		$normal_bold_100_900 = "(normal|bold|bolder|lighter|100|200|300|400|500|600|700|800|900)";
		$absolutesize_relativesize_length_percentage_inherit = "($absolutesize|$relativesize|$length|$percentage|inherit)";
		$normal_length = "(normal|$length)";
		$capitalize_uppercase_lowercase_none = '(capitalize|uppercase|lowercase|none)';
		$normal_nowrap = '(normal|nowrap)';

		$array = array(
			'margin-top' => $length_percentage_auto,'margin-right' => $length_percentage_auto,
			'margin-bottom' => $length_percentage_auto, 'margin-left' => $length_percentage_auto,
			'margin' => $length_percentage_1_4_auto,

			'padding-top' =>$length_percentage, 'padding-right' =>$length_percentage,
			'padding-bottom' =>$length_percentage, 'padding-left' =>$length_percentage,
			'padding' => $length_percentage_1_4_auto,

			'border-top-width' => $thin_medium_thick_length,'border-left-width' => $thin_medium_thick_length,
			'border-bottom-width' => $thin_medium_thick_length,'border-right-width' => $thin_medium_thick_length,
			'border-width' => $thin_medium_thick_length_1_4,
			'border-top-color' => $color_transparent,'border-right-color' => $color_transparent,
			'border-bottom-color' => $color_transparent,'border-left-color' => $color_transparent,
			'border-color' => $color_transparent_1_4,
			'border-top-style' => $none_hidden_solid_groove, 'border-right-style' => $none_hidden_solid_groove,
			'border-bottom-style' => $none_hidden_solid_groove,'border-left-style' => $none_hidden_solid_groove,
			'border-style' => $none_hidden_solid_groove_1_4,
			'border-top' => $thin_none_color, 'border-bottom' => $thin_none_color,
			'border-right' => $thin_none_color, 'border-left' => $thin_none_color,
			'border' => $thin_none_color,

			'width' => $length_percentage_auto, 'height' => $length_percentage_auto,
			'line-height' => $normal_length_percentage,
			'vertical-align' => $baseline_sub_super_top,

			'list-style-type' => $disc_circle_square_decimal,
			'list-style-image' => $uri_none,
			'list-style-position' => $inside_outside,
			'list-style' => $disc_uri_inside,

			'color' => "($color)",
			'background-color' => $color_transparent,
			'background-image' => $uri_none,
			'background-repeat' => $repeat,
			'background-attachment' => $scroll_fixed,
			'background-position' => $percentage_length_1_2_top_left,
			// background

			// font-family
			'font-style' => $normal_italic_oblique,'font-variant' => $normal_smallcaps,
			'font-weight' => $normal_bold_100_900,
			'font-size' => $absolutesize_relativesize_length_percentage_inherit,
			// font

			'text-indent' => $length_percentage,
			'text-align' => $left_right_center_justify,
			//text-decoration
			'letter-spacing' => $normal_length,'word-spacing' => $normal_length,
			'text-transform' => $capitalize_uppercase_lowercase_none,
			'white-space' => $normal_nowrap,
		);

		// 例外処理をするプロパティのリスト
		// 対象:値の出現順が任意のもの
		// 処理:空白でトークンに分け、正規表現で1個ずつチェック
		// 形式: '<property>' => array('regex' => '<string>', 'splitDelimiter' => '<string>')
		$exceptionProp = array(
			//少なくとも4種類の出現順が任意 4P4
			'background' => array('regex' => "(?:$color_transparent|$uri_none|$repeat|$scroll_fixed|$percentage_length_1_2_top_left)",'splitDelimiter' => ' '),
			'font-family' => array('regex' => "(serif|sans-serif|cursive|fantasy|monospace|\"(?:.*?)\")",'splitDelimiter' => ','),
			'text-decoration' => array('regex' => "(?:none|(underline|overline|line-through|blink))",'splitDelimiter' => ' ')
		);

		if ($prop === 'font') {
			$res = self::font(
				$val,
				array($normal_italic_oblique,$normal_smallcaps,$normal_bold_100_900),
				$absolutesize_relativesize_length_percentage_inherit
			);
			if($res !== true) return $res;
		} elseif ($prop === 'text-decoration') {
			$res = self::text_decoration(
				$val,
				array('underline', 'overline', 'line-through', 'blink')
			);
			if($res !== true) return $res;
		} elseif (isset($exceptionProp[$prop])) { // 例外処理 値の順が任意
			foreach (preg_split('/\s*'.$exceptionProp[$prop]['splitDelimiter'].'\s*/', $val, -1, PREG_SPLIT_NO_EMPTY) as $n => $v) {
				$res = preg_match('/^'.$exceptionProp[$prop]['regex'].'$/i', $v, $m);
				if($res === 0) return $prop;// マッチしない
			}
		} elseif (isset($array[$prop])) {// ノーマル処理 値の順が一意
			if(!preg_match("/".$array[$prop]."/", $val, $m)) return $prop;
		} else {
			return false;
		}

		return true;
	}

	static protected function font($val,Array $regArr, $addReg)
	{
		// font-family内の半角空白をエスケープ
		$val = preg_replace('/([\"\'].*?) (.*?[\"\'])/', '$1&nbsp;$2', $val);
		//$font_style_variant_weight = array($normal_italic_oblique,$normal_smallcaps,$normal_bold_100_900);
		$values = preg_split('/\s* \s*/', $val, -1, PREG_SPLIT_NO_EMPTY); // 半角空白で分割
		// 6個以上はありえない
		if (count($values) > 5) return $val;
		if (count($values) > 2) {
			$secondLast = count($values) - 2;
			for ($i=0;$i<$secondLast;$i++) {
				$before = count($regArr);
				foreach ($regArr as $n => $reg) {
					if (preg_match("/^(?:$reg)$/", $values[$i], $m)) {
						unset($regArr[$n]);
						break;
					}
				}
				if (count($regArr) === $before) return $values[$i];
			}
			//最後から2番目は、sizeも加える
			$regArr[] = $addReg;
			if (!preg_match("/^(?:".implode('|', $regArr).")$/", $values[$secondLast], $m)) {
				return $values[$secondLast];
			}
		}
		// 最後尾は'font-family'
		$lastArr = array_pop($values);
		if(self::blockValidate('font-family', $lastArr) !== true) return $lastArr;
		return true;
	}

	static protected function text_decoration($val, Array $regArr) {
		$values = preg_split('/\s* \s*/', $val, -1, PREG_SPLIT_NO_EMPTY); // 半角空白で分割

		if (count($values) === 1) { // 一番目は、'none'が含まれる可能性がある
			$regArr[] = 'none';
			if (!preg_match("/^(?:".implode('|', $regArr).")$/", $values[0], $m)) {
				return $values[0];
			}
		} else {
			foreach ($values as $value) {
				$before = count($regArr);
				foreach ($regArr as $n => $reg) {
					if (preg_match("/^(?:$reg)$/", $value, $m)) {
						unset($regArr[$n]);
						break;
					}
				}
				if (count($regArr) === $before) return $value; // 変化しない => マッチしなかった
			}
		}

		return true;
	}

	/**
	 * エラーメッセージをセットする
	 *
	 * @param string $str string
	 *
	 * @return ?
	 */
	protected function setError($str)
	{
		$this->setError[] = $str;
	}
	/**
	 * コメントを削除する
	 *
	 * @param string $css css
	 *
	 * @return string
	 */
	static protected function cssClean($css)
	{
		// コメントの削除
		$css = preg_replace('/\/\*[^*]*\*+([^\/][^*]*\*+)*\//m', '', $css);
		// 改行の削除
		$css = preg_replace('/\s*\n+\s*/m', chr(32), $css);

		return trim($css);
	}
}
