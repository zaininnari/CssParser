<?php
abstract class AValidate implements IValidate
{
	/**
	 * メソッド名の正規表現
	 */
	const METHOD = '/[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*/';

	const IMPORTANT = '/\s*!\s*important\s*/i';

	protected $error = array();

	/**
	 * パースするセレクタの正規表現
	 * パースしないようにするには、継承したクラスに
	 * $removeSelectorParseList を設定する
	 *
	 * @var array
	 */
	protected $selectorParseListOrigin = array(
		'universal'     => '/^(\*)/',
		'descendant'   => '/^( )/',
		'id'           => '/^(#[a-zA-Z][\w\-]*)/',
		'class'        => '/^(\.[a-zA-Z][\w\-]*)/',
		'child'        => '/^(>)/',
		'adjacent'        => '/^(\+)/',

		// 属性セレクタ（Attribute selectors）
		'attribute'         => '/^([a-zA-Z][\w\-]*(\[[^\]]+\])+)/',

		//Pseudo-classes
		'link'         => '/^(:(link|visited))/',
		'dynamic'      => '/^(:(link|visited|hover|active|focus))/',
		'first-child'  => '/^(:first-child)/',

		//Pseudo-elements

		//type
		'type'         => '/^([a-zA-Z][\w\-]*)/',
	);

	/**
	 * $selectorParseList からパースしないセレクタの正規表現を取り除く一次配列
	 *
	 * @var array
	 */
	protected $removeSelectorParseList = array();

	/**
	 * $selectorParseListOrigin から
	 * $removeSelectorParseList のリストを取り除いたもの
	 *
	 * @var array
	 */
	protected $selectorParseList = null;

	/**
	 * valueをチェックするメソッドのprefix。
	 * 必ず指定すること。
	 * 指定しない場合、任意のメソッドが実行される危険性がある。
	 *
	 * @var string
	 */
	protected $propertyMethodPrefix = 'property';

	protected $length     = '(?:(?:\+|-)?(?:[0-9]{1,}(¥.[0-9]+)?|¥.[0-9]+)(?:px|em|ex|in|cm|mm|pt|pc)|0)';
	protected $lengthPlus = '(?:\+?(?:[0-9]{1,}(¥.[0-9]+)?|¥.[0-9]+)(?:px|em|ex|in|cm|mm|pt|pc)|0)';
	protected $percentage = '(?:(?:\+|-)?(?:[0-9]{1,}%(¥.[0-9]+)?|¥.[0-9]+))';
	protected $percentagePlus = '(?:\+?(?:[0-9]{1,}%(¥.[0-9]+)?|¥.[0-9]+))';
	protected $color = '((?:#[0-9a-fA-F]{3})|(?:#[0-9a-zA-Z]{6})|(?:rgb\(\s*(?:[0-2][0-5]{2}|[0-1][0-9]{0,2})\s*,\s*(?:[0-2][0-5]{2}|[0-1][0-9]{0,2})\s*,\s*(?:[0-2][0-5]{2}|[0-1][0-9]{0,2})\s*\))|(?:rgb\(\s*(?:[0-9]{1,3}|0)%\s*,\s*(?:[0-9]{1,3}|0)%\s*,\s*(?:[0-9]{1,3}|0)%\s*\))|(?:Black|Silver|Gray|White|Maroon|Red|Purple|Fuchsia|Green|Lime|Olive|Yellow|Navy|Blue|Teal|Aqua|Orange)|(?:aliceblue|antiquewhite|aqua|aquamarine|azure|beige|bisque|black|blanchedalmond|blue|blueviolet|brass|brown|burlywood|cadetblue|chartreuse|chocolate|coolcopper|copper|coral|cornflower|cornflowerblue|cornsilk|crimson|cyan|darkblue|darkbrown|darkcyan|darkgoldenrod|darkgray|darkgreen|darkkhaki|darkmagenta|darkolivegreen|darkorange|darkorchid|darkred|darksalmon|darkseagreen|darkslateblue|darkslategray|darkturquoise|darkviolet|deeppink|deepskyblue|dimgray|dodgerblue|feldsper|firebrick|floralwhite|forestgreen|fuchsia|gainsboro|ghostwhite|gold|goldenrod|gray|green|greenyellow|honeydew|hotpink|indianred|indigo|ivory|khaki|lavender|lavenderblush|lawngreen|lemonchiffon|lightblue|lightcoral|lightcyan|lightgoldenrodyellow|lightgreen|lightgrey|lightpink|lightsalmon|lightseagreen|lightskyblue|lightslategray|lightsteelblue|lightyellow|lime|limegreen|linen|magenta|maroon|mediumaquamarine|mediumblue|mediumorchid|mediumpurple|mediumseagreen|mediumslateblue|mediumspringgreen|mediumturquoise|mediumvioletred|midnightblue|mintcream|mistyrose|moccasin|navajowhite|navy|oldlace|olive|olivedrab|orange|orangered|orchid|palegoldenrod|palegreen|paleturquoise|palevioletred|papayawhip|peachpuff|peru|pink|plum|powderblue|purple|red|richblue|rosybrown|royalblue|saddlebrown|salmon|sandybrown|seagreen|seashell|sienna|silver|skyblue|slateblue|slategray|snow|springgreen|steelblue|tan|teal|thistle|tomato|turquoise|violet|wheat|white|whitesmoke|yellow|yellowgreen)|(?:Background|Window|WindowText|WindowFrame|ActiveBorder|InactiveBorder|ActiveCaption|InactiveCaption|CaptionText|InactiveCaptionText|Scrollbar|AppWorkspace|Highlight|HighlightText|GrayText|Menu|MenuText|ButtonFace|ButtonText|ButtonHighlight|ButtonShadow|ThreeDFace|ThreeDHighlight|ThreeDShadow|ThreeDLightShadow|ThreeDDarkShadow|InfoText|InfoBackground))';
	protected $uri = '(?:.*?)';

	/**
	 * __construct
	 *
	 * @return ?
	 */
	function __construct()
	{
		if($this->selectorParseList === null) $this->initialize();
	}

	/**
	 * 初期化
	 *
	 * @return ?
	 */
	protected function initialize()
	{
		//valueをチェックするメソッドのprefixのチェック。
		if (!preg_match(self::METHOD, $this->propertyMethodPrefix)) {
			throw new OutOfBoundsException();
		}

		// セレクタのリストを対応するCSSのレベルに合わせる
		$_selectorParseList = $this->selectorParseListOrigin;
		foreach ($this->removeSelectorParseList as $remove) {
			if (isset($_selectorParseList[$remove])) {
				unset($_selectorParseList[$remove]);
			}
		}
		$this->selectorParseList = $_selectorParseList;

	}


	function validate(CssParser_Node $node)
	{
		if ($node->getType() !== 'root') throw new InvalidArgumentException();

		$ret = '1';
		//$ret = $this->readNode($node);
		return $node;
	}

	protected function readNode(CssParser_Node $node)
	{
		$ret = $this->{'read' . $node->getType()}($node->getData());
		return $ret;
	}

	protected function readRoot(Array $arr)
	{
		$result = array();
		foreach ($arr as $elt) {
			$_result = $this->readNode($elt);
			if($_result !== false) $result[] = $_result;
		}


		return $result;
	}

	protected function readStyle(Array $arr)
	{
		//$selectors = preg_split('/\s*,\s*/', $arr['selector']->getData(), -1, PREG_SPLIT_NO_EMPTY);
		//foreach ($selectors as &$v) $v = $this->selectorParse($v);

		foreach ($arr['block'] as $key => $delc) {
			if ($delc['value']->getData() === ''  // css-valueが空文字
				|| $this->callPropertyMethod($delc['property']->getData(), $delc['value']->getData()) === false // css-property(メソッド名)が適切でない
			) {
				$this->setError('block', $delc);
				unset($arr['block'][$key]);
			}
		}

		$arr['block'] = array_merge($arr['block']);

		if(empty($arr['block'])) unset($arr);

		return isset($arr) ? $arr : false;
	}

	protected function isValueImportant($value)
	{
		return preg_replace(self::IMPORTANT, rtrim($value));
	}


	/**
	 * css文字列をパースして返す。
	 *
	 * @param string $css string
	 *
	 * @see ./CssParser/IParser#parse($css)
	 * @return array
	 */
	public function parse($css)
	{
		$parseCss = $this->parseCss($css); // 文字列にパース
		$validate = $this->declarationValidate($parseCss); // 宣言をチェック
		return $validate;
	}


	/**
	 * CSS文字列をパースして返す。
	 *
	 * @param string $css string
	 *
	 * @return CssParser_Node
	 */
	protected function parseCss($css)
	{
		$rv = array('specs' => array(), 'data' => array());
		$result = array();
		$escape = $ignore = 0;

		$css = $this->cssClean($css);
		if(!isset($css)) return $result;
		$ary = preg_split('/\s*\{|\}\s*/', $css, -1, PREG_SPLIT_NO_EMPTY);
		if(count($ary) % 2 === 1) throw new InvalidArgumentException;

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
					if ($val === '' || mb_strpos(chr(92), $val)) {
						++$ignore;
					} elseif (preg_match('/\!\s*important/i', $val)) { // [!important] rule
						$val = preg_replace('/\s*!\s*important\s*/i', '', $val); // trim "!important"
						$gd2[++$gd2i] = $prop . ':' . $val;
						$gp2[++$gp2i] = array('property' => $prop, 'value' => $val);
					} else { // [normal] rule
						$gd1[++$gd1i] = $prop . ':' . $val; // "color:red"
						$gp1[++$gp1i] = array('property' => $prop, 'value' => $val); //{prop:"color",val:"red"}
					}
					if($ignore) $this->setError('"' . $prop . ":" . $val + '" ignore decl');
				}
			}

			// セレクタの前処理
			// セレクタを解析できない場合，宣言ブロックごと(グループ化されているものも)無視される
			foreach ($exprs as $n => $v) {
				$tmp = $this->selectorValidate($v);
				if ($tmp['valid'] !== true) {
					continue 2;
				} else {
					$exprs[$n] = $tmp['cleanSelector'];
				}
			}

			// セレクタの処理
			for ($j = 0, $jz = count($exprs); $j < $jz; ++$j) {
				$v = $exprs[$j];

				// 重みを計算する
				$spec = $this->calcSpec($v);
				if (count($gd1)) { // normal rule
					//if(!isset($spec, $rv['data'][$spec])) $rv['specs'][] = $spec;
					$rv['data'][$spec][] = array('expr' => $v, 'pair' => $gp1);
				}
				if (count($gd2)) { // !important rule
					$spec += 10000;
					//if(!isset($spec, $rv['data'][$spec])) $rv['specs'][] = $spec;
					$rv['data'][$spec][] = array('expr' => $v, 'pair' => $gp2);

				}

			}
		}
		// 重みをソート
		sort($rv['specs']);

		return $rv['data'];

	}

	/**
	 * セレクタの重みの計算
	 *
	 * @param string $expr string
	 *
	 * @return Number: spec value
	 */
	protected function calcSpec($expr)
	{
		$a = $b = $c = 0;

		$_specList = array(
			array('/#[\w\x00C0-\xFFEE\-]+/' , 'a'),  // id
			array('/\.[\w\x00C0-\xFFEE\-]+/' , 'b'), // class
			array('/\w+/' , 'c'),                    // E
		);

		foreach ($_specList as $n => $_spec) {
			for ($i = 0, $expr = preg_replace($_spec[0], '', $expr, -1, $count); $i < $count; $i++) {
				${$_spec[1]}++;
			}
		}

		return $a * 100 + $b * 10 + $c;
	}








	/**
	 * cssの宣言(declaration=>color:red)をチェックする。
	 * 不正・未知のvalueを含むdeclarationは無視される
	 * http://www.w3.org/TR/CSS2/syndata.html#parsing-errors
	 * ここではunsetされる
	 *
	 * @param Array $parsedCss parsedCss Array
	 *
	 * @return Array
	 */
	protected function declarationValidate(Array $parsedCss)
	{
		if (empty($parsedCss)) return $parsedCss;
		foreach ($parsedCss as $i => $spec) {
			foreach ($spec as $j => $data) {
				foreach ($data['pair'] as $k => $element) {
					$method = $this->getPropertyMethod($element['property']);
					$element['value'] = trim($element['value']);
					if ($element['value'] === ''  // css-valueが空文字
						|| $this->callPropertyMethod($element['property'], $element['value']) === false // css-property(メソッド名)が適切でない
					) {
						unset($parsedCss[$i][$j]['pair'][$k]);
					}
				}
				if(empty($parsedCss[$i][$j]['pair'])) unset($parsedCss[$i][$j]);
			}
			if(empty($parsedCss[$i])) unset($parsedCss[$i]);
		}

		return $parsedCss;
	}



	/**
	 * propertyをチェックするメソッド名を返す。
	 * メソッドが存在しない/適当でない場合、falseを返す。
	 *
	 * @param string $property css property
	 *
	 * @return string|false
	 */
	protected function getPropertyMethod($property)
	{
		if (is_array($property)) {
			$method = array();
			foreach ($property as $v) {
				$_method = $this->getPropertyMethod($v);
				if ($_method === false) return false;
				$method[] = $_method;
			}
		} else {
			$property = explode('-', trim($property));
			array_walk($property, create_function('&$s', '$s{0}=strtoupper($s{0});return $s;'));
			$property = implode('', $property);
			$method = $this->propertyMethodPrefix . str_replace('-', '_', $property);
			if ($method === $this->propertyMethodPrefix   // メソッド名が適切でない
				|| !preg_match(self::METHOD, $method)       // メソッド名が適切でない
				|| method_exists($this, $method) === false  // メソッド(cssプロパティ)が存在しない
			) {
				return false;
			}
		}
		return $method;
	}

	/**
	 * propertyをチェックするメソッドを呼ぶ。
	 * メソッドが存在しない/適当でない場合、falseを返す。
	 * 実行した結果も返す
	 *
	 * @param string $property css property
	 * @param string $value    css value
	 *
	 * @return boolean
	 */
	protected function callPropertyMethod($property, $value)
	{
		$method = $this->getPropertyMethod($property);
		if ($method === false) return false; // メソッドが存在しない/適当でない
		return call_user_func_array(array($this, $method), $value);
	}

	/**
	 * セレクタのチェックをする。
	 *
	 * @param string $selector string without comma
	 * @param array  $option   option
	 *
	 * @return array
	 */
	protected function selectorValidate($selector,Array $option = array())
	{
		$selector = trim($selector);
		$result = array(
			'selector' => $selector,
			'cleanSelector' => null,
			'parsedSelector' => array(),
			'error' => array(),
			'valid' => false,
		);

		//余分な空白を取り除く
		$result['cleanSelector'] = $selector = preg_replace(
			array('/\s+/', '/\s*>\s*/', '/\s*\+\s*/'),
			array(' ',     '>',         '+'),
			$selector
		);

		// 単純セレクタ（simple selector）や結合子（combinators）に分割する
		$result['parsedSelector'] = self::selectorParse($selector);

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
	 * @param string $selector dirty selector ('div    * div#id   > .class')
	 *
	 * @return array
	 */
	protected function selectorParse($selector)
	{
		$res = array();
		$seek = 0;

		// 単純セレクタ（simple selector）や結合子（combinators）に分割する
		$before = 0;
		while (mb_strlen($selector) !== 0) {
			foreach ($this->selectorParseList as $type => $pattern) {
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
	protected function selectorSyntax(Array $selector)
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
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function color($val)
	{
		/*$regexs = array(
			// #000
			'(?:#[0-9a-fA-F]{3})',
			// #000000
			'(?:#[0-9a-zA-Z]{6})',
			// rgb(255,0,0)
			'(?:rgb\(\s*(?:[0-2][0-5]{2}|[0-1][0-9]{0,2})\s*,\s*(?:[0-2][0-5]{2}|[0-1][0-9]{0,2})\s*,\s*(?:[0-2][0-5]{2}|[0-1][0-9]{0,2})\s*\))',
			// rgb(100%,0%,0%)
			'(?:rgb\(\s*(?:[0-9]{1,3}|0)%\s*,\s*(?:[0-9]{1,3}|0)%\s*,\s*(?:[0-9]{1,3}|0)%\s*\))',
			// 16 + 1
			'(?:Black|Silver|Gray|White|Maroon|Red|Purple|Fuchsia|Green|Lime|Olive|Yellow|Navy|Blue|Teal|Aqua)',
			// css2.1 add
			'(?:Orange)',
			// 147
			'(?:aliceblue|antiquewhite|aqua|aquamarine|azure|beige|bisque|black|blanchedalmond|blue|blueviolet|brass|brown|burlywood|cadetblue|chartreuse|chocolate|coolcopper|copper|coral|cornflower|cornflowerblue|cornsilk|crimson|cyan|darkblue|darkbrown|darkcyan|darkgoldenrod|darkgray|darkgreen|darkkhaki|darkmagenta|darkolivegreen|darkorange|darkorchid|darkred|darksalmon|darkseagreen|darkslateblue|darkslategray|darkturquoise|darkviolet|deeppink|deepskyblue|dimgray|dodgerblue|feldsper|firebrick|floralwhite|forestgreen|fuchsia|gainsboro|ghostwhite|gold|goldenrod|gray|green|greenyellow|honeydew|hotpink|indianred|indigo|ivory|khaki|lavender|lavenderblush|lawngreen|lemonchiffon|lightblue|lightcoral|lightcyan|lightgoldenrodyellow|lightgreen|lightgrey|lightpink|lightsalmon|lightseagreen|lightskyblue|lightslategray|lightsteelblue|lightyellow|lime|limegreen|linen|magenta|maroon|mediumaquamarine|mediumblue|mediumorchid|mediumpurple|mediumseagreen|mediumslateblue|mediumspringgreen|mediumturquoise|mediumvioletred|midnightblue|mintcream|mistyrose|moccasin|navajowhite|navy|oldlace|olive|olivedrab|orange|orangered|orchid|palegoldenrod|palegreen|paleturquoise|palevioletred|papayawhip|peachpuff|peru|pink|plum|powderblue|purple|red|richblue|rosybrown|royalblue|saddlebrown|salmon|sandybrown|seagreen|seashell|sienna|silver|skyblue|slateblue|slategray|snow|springgreen|steelblue|tan|teal|thistle|tomato|turquoise|violet|wheat|white|whitesmoke|yellow|yellowgreen)',
			// システムカラー
			'(?:Background|Window|WindowText|WindowFrame|ActiveBorder|InactiveBorder|ActiveCaption|InactiveCaption|CaptionText|InactiveCaptionText|Scrollbar|AppWorkspace|Highlight|HighlightText|GrayText|Menu|MenuText|ButtonFace|ButtonText|ButtonHighlight|ButtonShadow|ThreeDFace|ThreeDHighlight|ThreeDShadow|ThreeDLightShadow|ThreeDDarkShadow|InfoText|InfoBackground)',
		);
		foreach ($regexs as $regex) {
			if(preg_match('/^'.$regex.'$/i', $val)) return true;
		}*/
		return preg_match('/^'.$this->color.'$/i', $val);
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyMarginTop($val)
	{
		$pattern = '('.$this->length.'|'.$this->percentage.'|auto|inherit)';
		return preg_match('/^'.$pattern.'$/i', $val);
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyMarginRight($val)
	{
		return $this->callPropertyMethod('margin-top', $val);
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyMarginBottom($val)
	{
		return $this->callPropertyMethod('margin-top', $val);
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyMarginLeft($val)
	{
		return $this->callPropertyMethod('margin-top', $val);
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyMargin($val)
	{
		$arr = preg_split('/\s* \s*/', $val, -1, PREG_SPLIT_NO_EMPTY);
		if(count($arr) > 4) return false;
		foreach ($arr as $v) if(!$this->callPropertyMethod('margin-top', $v)) return false;
		return true;
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyPaddingTop($val)
	{
		$pattern = '('.$this->lengthPlus.'|'.$this->percentagePlus.'|inherit)';
		return preg_match('/^'.$pattern.'$/i', $val);
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyPaddingRight($val)
	{
		return $this->callPropertyMethod('padding-top', $val);
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyPaddingBottom($val)
	{
		return $this->callPropertyMethod('padding-top', $val);
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyPaddingLeft($val)
	{
		return $this->callPropertyMethod('padding-top', $val);
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyPadding($val)
	{
		$arr = preg_split('/\s* \s*/', $val, -1, PREG_SPLIT_NO_EMPTY);
		if(count($arr) > 4) return false;
		foreach ($arr as $v) if(!$this->callPropertyMethod('padding-top', $v)) return false;
		return true;
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyBorderTopWidth($val)
	{
		$pattern = '('.$this->lengthPlus.'|thin|medium|thick|inherit)';
		return preg_match('/^'.$pattern.'$/i', $val);
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyBorderRightWidth($val)
	{
		return $this->callPropertyMethod('border-top-width', $val);
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyBorderBottomWidth($val)
	{
		return $this->callPropertyMethod('border-top-width', $val);
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyBorderLeftWidth($val)
	{
		return $this->callPropertyMethod('border-top-width', $val);
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyBorderWidth($val)
	{
		$arr = preg_split('/\s* \s*/', $val, -1, PREG_SPLIT_NO_EMPTY);
		if(count($arr) > 4) return false;
		foreach ($arr as $v) if(!$this->callPropertyMethod('border-top-width', $v)) return false;
		return true;
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyBorderTopColor($val)
	{
		$pattern = '('.$this->color.'|transparent|inherit)';
		return preg_match('/^'.$pattern.'$/i', $val);
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyBorderRightColor($val)
	{
		return $this->callPropertyMethod('border-top-color', $val);
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyBorderBottomColor($val)
	{
		return $this->callPropertyMethod('border-top-color', $val);
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyBorderLeftColor($val)
	{
		return $this->callPropertyMethod('border-top-color', $val);
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyBorderColor($val)
	{
		$arr = preg_split('/\s* \s*/', $val, -1, PREG_SPLIT_NO_EMPTY);
		if(count($arr) > 4) return false;
		foreach ($arr as $v) if(!$this->callPropertyMethod('border-top-color', $v)) return false;
		return true;
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyBorderTopStyle($val)
	{
		$pattern = '(none|hidden|dotted|dashed|solid|double|groove|ridge|inset|outset|inherit)';
		return preg_match('/^'.$pattern.'$/i', $val);
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyBorderRightStyle($val)
	{
		return $this->callPropertyMethod('border-top-style', $val);
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyBorderBottomStyle($val)
	{
		return $this->callPropertyMethod('border-top-style', $val);
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyBorderLeftStyle($val)
	{
		return $this->callPropertyMethod('border-top-style', $val);
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyBorderStyle($val)
	{
		$arr = preg_split('/\s* \s*/', $val, -1, PREG_SPLIT_NO_EMPTY);
		if(count($arr) > 4) return false;
		foreach ($arr as $v) if(!$this->callPropertyMethod('border-top-style', $v)) return false;
		return true;
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyBorderTop($val)
	{
		$values = preg_split('/\s* \s*/', $val, -1, PREG_SPLIT_NO_EMPTY);
		$regArr = array('border-top-width', 'border-top-style', 'color');

		if (count($values) > 3) return false; // 4個以上はありえない
		// 1個の場合は、別途処理
		if (count($values) === 1 && preg_match('/^inherit$/i', $values[0])) return true;
		foreach ($values as $i => $value) {
			$before = count($regArr);
			foreach ($regArr as $n => $reg) {
				if ($this->callPropertyMethod($reg, $value)) {
					unset($regArr[$n]);
					break;
				}
			}
			if (count($regArr) === $before) return false;
		}
		return true;
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyBorderRight($val)
	{
		return $this->callPropertyMethod('border-top', $val);
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyBorderBottom($val)
	{
		return $this->callPropertyMethod('border-top', $val);
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyBorderLeft($val)
	{
		return $this->callPropertyMethod('border-top', $val);
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyBorder($val)
	{
		return $this->callPropertyMethod('border-top', $val);
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyDisplay($val)
	{
		$pattern = '(inline|block|list-item|run-in|compact|marker|table|inline-table|table-row-group|table-header-group|table-footer-group|table-row|table-column-group|table-column|table-cell|table-caption|none|inherit)';
		return preg_match('/^'.$pattern.'$/i', $val);
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyColor($val)
	{
		return $this->color($val);
	}

	/**
	 * コメントを削除する
	 *
	 * @param string $css css
	 *
	 * @return string
	 */
	protected function cssClean($css)
	{
		// コメントの削除
		$css = preg_replace('/\/\*[^*]*\*+([^\/][^*]*\*+)*\//m', '', $css);
		// 改行の削除
		$css = preg_replace('/\s*\n+\s*/m', chr(32), $css);

		return trim($css);
	}



	/**
	 * エラーメッセージをセットする
	 *
	 * @param string $str string
	 *
	 * @return ?
	 */
	protected function setError($str, $data = array())
	{
		$this->error[] = func_get_args();
	}

	/**
	 * エラーメッセージを取得
	 *
	 * @return array
	 */
	public function getError()
	{
		return $this->error;
	}

}