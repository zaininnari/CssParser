<?php
abstract class AValidate implements IValidate
{
	/**
	 * メソッド名の正規表現
	 */
	const METHOD = '/[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*/';

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

		// Pseudo-classes
		'link'         => '/^(:(link|visited))/',
		'dynamic'      => '/^(:(link|visited|hover|active|focus))/',
		'first-child'  => '/^(:first-child)/',

		// language pseudo-class
		// ISO_639
		'language'     => '/^(:lang\([a-z\-]+\))/',

		// Pseudo-elements
		'first-line'   => '/^(:first-line)/',
		'first-letter' => '/^(:first-letter)/',
		'before'       => '/^(:before)/',
		'after'        => '/^(:after)/',

		// 属性セレクタ（Attribute selectors）
		'attribute' => '/^(\[[^\]]+\])/',

		// type
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

	/**
	 * propertyのリスト。
	 *
	 * @var array
	 */
	protected $propertyList = null;

	/**
	 * value内部定義
	 * 一部は、メンテナンスをしやすくするため自動生成
	 *
	 * @var array
	 */
	protected $color, $string, $ident, $uri, $familyName, $utf8, $marginWidth, $paddingWidth, $borderWidth;

	protected $integer        = '(?:(?:\+|-|)(?:[0-9]{1,}))';
	protected $numberPlus     = '(?:\+|)(?:[0-9]{1,}(\.[0-9]+)?|\.[0-9]+)';
	protected $length         = '(?:(?:\+|-|)(?:(?:[0-9]{1,}(\.[0-9]+)?|\.[0-9]+)(?:px|em|ex|in|cm|mm|pt|pc)|0))';
	protected $lengthPlus     = '(?:(?:\+|)(?:(?:[0-9]{1,}(\.[0-9]+)?|\.[0-9]+)(?:px|em|ex|in|cm|mm|pt|pc)|0))';
	protected $percentage     = '(?:(?:\+|-|)(?:[0-9]{1,}(\.[0-9]+)?|\.[0-9]+)%)';
	protected $percentagePlus = '(?:\+?(?:[0-9]{1,}(\.[0-9]+)?|\.[0-9]+)%)';
	protected $ignore         = '(?:\s*(?:\/\*[^*]*\*+(?:[^\/][^*]*\*+)*\/)*\s*)*';
	protected $genericFamily  = '(?:serif|sans-serif|cursive|fantasy|monospace)';
	protected $absoluteSize   = '(?:xx-small|x-small|small|medium|large|x-large|xx-large)';
	protected $relativeSize   = '(?:larger|smaller)';
	protected $borderStyle    = '(?:none|hidden|dotted|dashed|solid|double|groove|ridge|inset|outset)';

	protected $css;

	/**
	 * __construct
	 *
	 * @param string $css css
	 *
	 * @return ?
	 */
	function __construct($css)
	{
		$this->css = $css;
		if ($this->selectorParseList === null) $this->initialize();
	}

	/**
	 * 初期化
	 *
	 * @return ?
	 */
	protected function initialize()
	{
		/////////////////////////////////////////////////////////////////
		// valueをチェックするメソッドのprefixのチェック。
		/////////////////////////////////////////////////////////////////
		if (!preg_match(self::METHOD, $this->propertyMethodPrefix)) {
			throw new OutOfBoundsException('invalid $propertyMethodPrefix');
		}

		/////////////////////////////////////////////////////////////////
		// セレクタのリストを対応するCSSのレベルに合わせる
		/////////////////////////////////////////////////////////////////
		$_selectorParseList = $this->selectorParseListOrigin;
		foreach ($this->removeSelectorParseList as $remove) {
			if (isset($_selectorParseList[$remove])) {
				unset($_selectorParseList[$remove]);
			}
		}
		$this->selectorParseList = $_selectorParseList;

		/////////////////////////////////////////////////////////////////
		// propertyのリストを作成する
		/////////////////////////////////////////////////////////////////
		$ref = new ReflectionClass(__CLASS__);
		$methods = $ref->getMethods();
		$len = strlen($this->propertyMethodPrefix);
		$propertyList = array();
		foreach ($methods as $method) {
			if (strpos($method->name, $this->propertyMethodPrefix) === 0 && strlen($method->name) > $len) {
				$property = preg_replace('/^'.$this->propertyMethodPrefix.'/', '', $method->name, 1);
				$property = strtolower(preg_replace('/([A-Z])/', '-$1', $property));
				$propertyList[$property] = $method->name;
			}
		}
		$this->propertyList = $propertyList;

		/////////////////////////////////////////////////////////////////
		// value 内部定義構築
		/////////////////////////////////////////////////////////////////
		$nl = '(\n|\r\n|\r|\f)';
		$ascii    = '[\x00-\x7f]'; // x00-x7f hexdec   0 - 127 decoct   \0 - \177
		$nonascii = '[\x80-\xff]'; // x80-xff hexdec 128 - 255 decoct \200 - \377
		$unicode = '(\\[0-9a-fA-F]{1,6}[ \t\r\n\f]?)';
		$utf8Arr = array(
			'(?:[\xc2-\xd4][\x80-\xbf])',
			'(?:\xef[\xa4-\xab][\x80-\xbf])',
			'(?:\xef[\xbc-\xbd][\x80-\xbf])',
			'(?:\xef\xbe[\x80-\x9f])',
			'(?:\xef\xbf[\xa0-\xa5])',
			'(?:[\xe2-\xe9][\x80-\xbf][\x80-\xbf])',
			//'(?:[\x09\x0a\x0d\x20-\x7e])',
		);
		$utf8 = '(?:'.implode('|', $utf8Arr).')';
		$escape = '('.$unicode.'|\\\( |-|~|'.$nonascii.'))';
		$nmstart = '([_a-zA-Z]|'.$nonascii.'|'.$escape.'|'.$utf8.')';
		$nmchar = '([_a-zA-Z0-9-]|'.$nonascii.'|'.$escape.'|'.$utf8.')';
		$string1 = '(\"([\t !#$%&(-~]|\\\\'.$nl.'|\\\'|'.$nonascii.'|'.$escape.'|'.$utf8.')*\")';
		$string2 = '(\\\'([\t !#$%&(-~]|\\\\'.$nl.'|"|'.$nonascii.'|'.$escape.'|'.$utf8.')*\\\')';
		$string = '('.$string1.'|'.$string2.')';

		$this->utf8 = '([\t !#$%&(-~]|\\\\'.$nl.'|'.$nonascii.'|'.$escape.'|'.$utf8.')';
		$this->string = $string;

		$ident = '-?'.$nmstart.$nmchar.'*';
		$_url = '([!#$%&*-~]|'.$nonascii.'|'.$escape.')+';
		$url1 = 'url\(\s*'.$string.'*\s*\)';
		$url2 = 'url\(\s*'.$_url.'\s*\)';
		$url = '('.$url1.'|'.$url2.')';

		$this->uri = $url;
		$this->ident = $ident;

		$c = $this->ignore;
		$rgb1 = '(?:[0-2][0-5]{2}|[0-1][0-9]{0,2})';
		$rgb2 = '(?:[0-9]{1,3}|0)%';
		$colorArr = array(
			// #000
			'(?:#[0-9a-fA-F]{3})',
			// #000000
			'(?:#[0-9a-zA-Z]{6})',
			// rgb(255,0,0)
			'(?:rgb\('.$c.$rgb1.$c.','.$c.$rgb1.$c.','.$c.$rgb1.$c.'\))',
			// rgb(100%,0%,0%)
			'(?:rgb\('.$c.$rgb2.$c.','.$c.$rgb2.$c.','.$c.$rgb2.$c.'\))',
			// 基本カラー 16
			'(?:Black|Silver|Gray|White|Maroon|Red|Purple|Fuchsia|Green|Lime|Olive|Yellow|Navy|Blue|Teal|Aqua)',
			// 追加基本カラー css2.1 add
			'(?:Orange)',
			// 147
			'(?:aliceblue|antiquewhite|aqua|aquamarine|azure|beige|bisque|black|blanchedalmond|blue|blueviolet|brass|brown|burlywood|cadetblue|chartreuse|chocolate|coolcopper|copper|coral|cornflower|cornflowerblue|cornsilk|crimson|cyan|darkblue|darkbrown|darkcyan|darkgoldenrod|darkgray|darkgreen|darkkhaki|darkmagenta|darkolivegreen|darkorange|darkorchid|darkred|darksalmon|darkseagreen|darkslateblue|darkslategray|darkturquoise|darkviolet|deeppink|deepskyblue|dimgray|dodgerblue|feldsper|firebrick|floralwhite|forestgreen|fuchsia|gainsboro|ghostwhite|gold|goldenrod|gray|green|greenyellow|honeydew|hotpink|indianred|indigo|ivory|khaki|lavender|lavenderblush|lawngreen|lemonchiffon|lightblue|lightcoral|lightcyan|lightgoldenrodyellow|lightgreen|lightgrey|lightpink|lightsalmon|lightseagreen|lightskyblue|lightslategray|lightsteelblue|lightyellow|lime|limegreen|linen|magenta|maroon|mediumaquamarine|mediumblue|mediumorchid|mediumpurple|mediumseagreen|mediumslateblue|mediumspringgreen|mediumturquoise|mediumvioletred|midnightblue|mintcream|mistyrose|moccasin|navajowhite|navy|oldlace|olive|olivedrab|orange|orangered|orchid|palegoldenrod|palegreen|paleturquoise|palevioletred|papayawhip|peachpuff|peru|pink|plum|powderblue|purple|red|richblue|rosybrown|royalblue|saddlebrown|salmon|sandybrown|seagreen|seashell|sienna|silver|skyblue|slateblue|slategray|snow|springgreen|steelblue|tan|teal|thistle|tomato|turquoise|violet|wheat|white|whitesmoke|yellow|yellowgreen)',
			// システムカラー
			'(?:Background|Window|WindowText|WindowFrame|ActiveBorder|InactiveBorder|ActiveCaption|InactiveCaption|CaptionText|InactiveCaptionText|Scrollbar|AppWorkspace|Highlight|HighlightText|GrayText|Menu|MenuText|ButtonFace|ButtonText|ButtonHighlight|ButtonShadow|ThreeDFace|ThreeDHighlight|ThreeDShadow|ThreeDLightShadow|ThreeDDarkShadow|InfoText|InfoBackground)',
		);
		$this->color = '(?:'.implode('|', $colorArr).')';

		$this->familyName = '(?:'.$this->string.'|(?:'.$this->utf8.'+))';

		$this->marginWidth = '(?:' . $this->length . '|' . $this->percentage . '|auto)';
		$this->paddingWidth = '(?:' . $this->lengthPlus . '|' . $this->percentagePlus . ')';
		$this->borderWidth = '(?:thin|medium|thick|' . $this->lengthPlus . '|' . $this->percentagePlus . ')';
	}

	/**
	 * validate
	 *
	 * @param CssParser_Node $node node object
	 *
	 * @see CssParser/IValidate#validate($node)
	 *
	 * @return CssParser_Node
	 */
	function validate(CssParser_Node $node)
	{
		if ($node->getType() !== 'root') throw new InvalidArgumentException();
		return $this->readNode($node);
	}

	/**
	 * read node
	 *
	 * @param CssParser_Node $node node object
	 *
	 * @return array
	 */
	protected function readNode(CssParser_Node $node)
	{
		$ret = $this->{'read' . $node->getType()}($node->getData());
		return $o = new CssParser_Node($node->getType(), $ret, $node->getOffset());
	}

	/**
	 * read node
	 *
	 * @param array $arr node data
	 *
	 * @return array
	 */
	protected function readRoot(Array $arr)
	{
		$result = array();
		foreach ($arr as $elt) {
			$_result = $this->readNode($elt);
			if($_result !== false) $result[] = $_result;
		}

		return $result;
	}

	/**
	 * read node
	 *
	 * @param array $arr node data
	 *
	 * @return array
	 */
	protected function readRuleSet(Array $arr)
	{
		if ($arr['selector']->getType() === 'unknown') return $arr;
		$selectors = preg_split('/\s*,\s*/', $arr['selector']->getData(), -1, PREG_SPLIT_NO_EMPTY);
		foreach ($selectors as &$v) $v = $this->selectorValidate($v);

		foreach ($arr['block'] as $key => &$delc) {
			if ($delc instanceof CssParser_Node && $delc->getType() === 'unknown') continue;
			if ($delc['value']->getData() === ''  // css-valueが空文字
				|| $this->callPropertyMethod($delc['property']->getData(), $delc['value']->getData()) === false // css-property(メソッド名)が適切でない
			) {
				$isValid = false;
			} else {
				$isValid = true;
			}
			$arr['block'][$key]['isValid'] = $isValid;
		}

		return array('selector' => new CssParser_Node($arr['selector']->getType(), $selectors, $arr['selector']->getOffset()), 'block' => $arr['block']);
	}

	/**
	 * read node
	 *
	 * @param array $arr node data
	 *
	 * @return array
	 */
	protected function readAtRule(Array $arr)
	{
		//TODO
		if ($arr['selector'] instanceof CssParser_Node && $arr['selector']->getType() === 'unknown') return $arr;
		if ($arr['selector'] === '@charset') {
			//TODO charset validate
			// http://www.iana.org/assignments/character-sets
			return $arr;
		}
		if ($arr['selector'] === '@import') {
			// ・ファイルの文字列の形式のチェックはしない
			//   入力形式によって異なる
			//   url  -> 入力ドメインへ、相対パスを解決してファイル取得
			//   string OR file -> ファイル取得しない。
			// ・メディアタイプの形式はチェックする
			return $this->atRuleImport($arr);
		}
		return $arr;
	}

	/**
	 * TODO
	 *
	 * @param array $arr Array
	 *
	 * @return Array
	 */
	protected function atRuleImport(Array $arr)
	{
		if (empty($arr['mediaType'])) return $arr;
		return $arr;
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
		$property = strtolower(trim($property));
		$_property = $property{0} === '-' ? $property : '-'.$property; // for vendor prefixes
		return isset($this->propertyList[$_property]) ? $this->propertyList[$_property] : false;
	}

	/**
	 * propertyをチェックするメソッドを呼ぶ。
	 * メソッドが存在しない/適当でない場合、falseを返す。
	 * 実行した結果も返す
	 *
	 * @return boolean
	 */
	protected function callPropertyMethod()
	{
		$args = func_get_args();
		if (!isset($args[0], $args[1]) || !is_string($args[0]) || !is_string($args[0])) return false;
		$method = $this->getPropertyMethod(array_shift($args));
		if ($method === false) return false; // メソッドが存在しない/適当でない
		return call_user_func_array(array($this, $method), $args);
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
			'isValid' => false,
		);

		//余分な空白を取り除く
		$result['cleanSelector'] = preg_replace(
			array('/\s+/', '/\s*>\s*/', '/\s*\+\s*/'),
			array(' ',     '>',         '+'),
			$selector
		);

		// 単純セレクタ（simple selector）や結合子（combinators）に分割する
		$result['parsedSelector'] = self::selectorParse($result['cleanSelector']);

		// 単純セレクタ（simple selector）や結合子（combinators）の構文をチェックする
		$syntax = self::selectorSyntax($result['parsedSelector']);
		if (!empty($syntax)) $result['error'] = array_merge($result['error'], $syntax);

		// エラーがなければ成功
		if (empty($result['error'])
			&& implode('', call_user_func(create_function('Array $a', '$r=array();foreach($a as $b)$r[]=$b[1];return $r;'), $result['parsedSelector'])) === $result['cleanSelector']
		) $result['isValid'] = true;

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
				'attribute'  => true,
			),
			'combinator' => array(
				'end'        => false,
				'combinator' => false,
				'universal'  => true,
				'type'       => true,
				'id'         => true,
				'link'       => true,
				'attribute'  => true,
			),
			'universal' => array(
				'end'        => true,
				'combinator' => true,
				'universal'  => false,
				'type'       => false,
				'id'         => false,
				'link'       => true,
				'attribute'  => true,
			),
			'type' => array(
				'end'        => true,
				'combinator' => true,
				'universal'  => false,
				'type'       => false,
				'id'         => true,
				'link'       => true,
				'attribute'  => true,
			),
			'id' => array(
				'end'        => true,
				'combinator' => true,
				'universal'  => false,
				'type'       => false,
				'id'         => true,
				'link'       => true,
				'attribute'  => true,
			),
			'link' => array(
				'end'        => true,
				'combinator' => true,
				'universal'  => false,
				'type'       => false,
				'id'         => true,
				'link'       => true,
				'attribute'  => true,
			),
			'attribute'  => array(
				'end'        => true,
				'combinator' => true,
				'universal'  => false,
				'type'       => false,
				'id'         => true,
				'link'       => true,
				'attribute'  => true,
			),
		);

		//グルーピングルール
		$group = array(
			'combinator'  => array('descendant', 'child', 'adjacent'),
			'id'          => array('id', 'class'),
			'link'        => array('link', 'dynamic', 'language', 'first-line', 'first-letter', 'before', 'after'),
		);

		// 分割したセレクタの構文をチェックする
		$before = 'start';
		$selector[] = array('end' , '{'); // 一番最後に終了を意味する識別文字を挿入する
		foreach ($selector as $n => $v) {
			$test = $v[0];
			foreach ($group as $name => $member) { // セレクタのグルーピング
				if (in_array($test, $member)) {
					$test = $name;
					break;
				}
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
	 * value のコメント・改行を削除し、空白文字(White Space)で分割して返す。
	 *
	 * @param string $val       css value
	 * @param string $delimiter regex delimiter. default '\s+'
	 *
	 * @return Array
	 */
	static protected function _split($val, $delimiter = '\s+')
	{
		$comment = '(?:\/\*[^*]*\*+([^\/][^*]*\*+)*\/)';
		$patternArr = array(
			// commnet
			$comment,
			// function
			'(?:(?i:rgb|hsl|rgba|hsla|url|rect|attr|counter|counters))\((?:'.$comment.'|\\\\\)|[^\)])*\)',
			// double quote
			'("('.$comment.'|\\\"|[^"])*")',
			// single quote
			'(\'('.$comment.'|\\\\\'|[^\'])*\')',
		);
		$pattern = '(?:'.implode('|', $patternArr).')';
		preg_match_all('/'.$pattern.'/', $val, $matches, PREG_OFFSET_CAPTURE);
		$matches = $matches[0];

		$res = array();
		// 連続しないエスケープするトークン間のトークンは、$delimiter による分割を試みる
		for ($i=0;$i<count($matches);$i++) {
			if ($i === 0 && $matches[$i][1] !== 0) {
				$res = array_merge($res, preg_split('/'.$delimiter.'/', substr($val, 0, $matches[$i][1]), -1, PREG_SPLIT_NO_EMPTY));
			}
			if ($matches[$i][0]{0} === '/') continue; // コメントは無視
			$res[] = $matches[$i][0];
			$len = $matches[$i][1] + strlen($matches[$i][0]); // 現在の文字列の終点offset
			// 現在の文字列の終点offset から ( 次点の文字列の開始offset OR 終点 )までの距離
			$length = isset($matches[$i+1][1]) ? $matches[$i+1][1] - $len : strlen($val) - $len;
			if ($length === 0) continue; // 0 -> トークン間のトークンはない
			$str = substr($val, $len, $length);
			$res = array_merge($res, preg_split('/'.$delimiter.'/', $str, -1, PREG_SPLIT_NO_EMPTY));
		}
		if ($i === 0) $res = preg_split('/'.$delimiter.'/', $val, -1, PREG_SPLIT_NO_EMPTY);

		return $res;
	}






	/**
	 * margin / padding / border
	 *
	 * @param string $val          css value
	 * @param string $patternOne   css内部定義
	 * @param string $patternMulti css内部定義
	 *
	 * @return boolean
	 */
	static protected function _propertyMarginOrPaddingOrBorder($val, $patternOne, $patternMulti)
	{
		$arr = self::_split($val);
		if (count($arr) > 4) return false;
		if (count($arr) === 1) return preg_match('/^'.$patternOne.'$/i', $val) === 1;
		foreach ($arr as $v) if(!preg_match('/^'.$patternMulti.'$/i', $v)) return false;
		return true;
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
		$pattern = '('. $this->marginWidth .'|inherit)';
		return preg_match('/^'.$pattern.'$/i', $val) === 1;
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
		$patternOne   = '(inherit|'.$this->marginWidth.')';
		$patternMulti = '('.$this->marginWidth.')';
		return self::_propertyMarginOrPaddingOrBorder($val, $patternOne, $patternMulti);
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
		$pattern = '('.$this->paddingWidth.'|inherit)';
		return preg_match('/^'.$pattern.'$/i', $val) === 1;
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
		$patternOne   = '(inherit|'.$this->paddingWidth.')';
		$patternMulti = '('.$this->paddingWidth.')';
		return self::_propertyMarginOrPaddingOrBorder($val, $patternOne, $patternMulti);
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
		$pattern = '('.$this->borderWidth.'|inherit)';
		return preg_match('/^'.$pattern.'$/i', $val) === 1;
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
	 * @param string  $val    css value
	 * @param boolean $return trueの場合、パターンを返す。
	 *
	 * @return boolean / string
	 */
	protected function propertyBorderWidth($val, $return = false)
	{
		$patternOne   = '(inherit|'.$this->borderWidth.')';
		$patternMulti = '('.$this->borderWidth.')';
		return $return === false ? self::_propertyMarginOrPaddingOrBorder($val, $patternOne, $patternMulti) : $patternMulti;
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
		$pattern = '(transparent|inherit|'.$this->color.')';
		return preg_match('/^'.$pattern.'$/i', $val) === 1;
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
	 * @param string  $val    css value
	 * @param boolean $return trueの場合、パターンを返す。
	 *
	 * @return boolean / string
	 */
	protected function propertyBorderColor($val, $return = false)
	{
		$patternOne   = '(inherit|transparent|'.$this->color.')';
		$patternMulti = '(transparent|'.$this->color.')';
		return $return === false ? self::_propertyMarginOrPaddingOrBorder($val, $patternOne, $patternMulti) : $patternMulti;
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
		return preg_match('/^'.$pattern.'$/i', $val) === 1;
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
	 * @param string  $val    css value
	 * @param boolean $return trueの場合、パターンを返す。
	 *
	 * @return boolean / string
	 */
	protected function propertyBorderStyle($val, $return = false)
	{
		$patternOne   = '(none|hidden|dotted|dashed|solid|double|groove|ridge|inset|outset|inherit)';
		$patternMulti = '(none|hidden|dotted|dashed|solid|double|groove|ridge|inset|outset)';
		return $return === false ? self::_propertyMarginOrPaddingOrBorder($val, $patternOne, $patternMulti) : $patternMulti;
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
		$patternArr = array(
			// <border-top-color> = <color> | transparent
			$this->callPropertyMethod('border-color', $val, true),
			// <border-style> = none|hidden|dotted|dashed|solid|double|groove|ridge|inset|outset
			$this->callPropertyMethod('border-style', $val, true),
			// <border-width> = thin | medium | thick | <lengthPlus>
			$this->callPropertyMethod('border-width', $val, true),
		);
		$arr = self::_split($val);

		if (count($arr) > 4) return false;
		if (count($arr) === 1) {
			if ($arr[0] === 'inherit') return true;
			foreach ($patternArr as $v) if (!preg_match('/^'.$v.'$/i', $arr[0])) return true;
			return false;
		}

		foreach ($arr as $value) {
			$before = count($patternArr);
			foreach ($patternArr as $n => $pattern) {
				if (preg_match('/^'.$pattern.'$/i', $value)) {
					unset($patternArr[$n]);
					break;
				}
			}
			if (count($patternArr) === $before) return false;
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
		return preg_match('/^'.$pattern.'$/i', $val) === 1;
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyPosition($val)
	{
		$pattern = '(static|relative|absolute|fixed|inherit)';
		return preg_match('/^'.$pattern.'$/i', $val) === 1;
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyTop($val)
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
	protected function propertyBottom($val)
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
	protected function propertyLeft($val)
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
	protected function propertyRight($val)
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
	protected function propertyFloat($val)
	{
		$pattern = '(left|right|none|inherit)';
		return preg_match('/^'.$pattern.'$/i', $val) === 1;
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyClear($val)
	{
		$pattern = '(none|left|right|both|inherit)';
		return preg_match('/^'.$pattern.'$/i', $val) === 1;
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyZIndex($val)
	{
		$pattern = '(auto|'.$this->integer.'|inherit)';
		return preg_match('/^'.$pattern.'$/i', $val) === 1;
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyDirection($val)
	{
		$pattern = '(ltr|rtl|inherit)';
		return preg_match('/^'.$pattern.'$/i', $val) === 1;
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyUnicodeBidi($val)
	{
		$pattern = '(normal|embed|bidi-override|inherit)';
		return preg_match('/^'.$pattern.'$/i', $val) === 1;
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyWidth($val)
	{
		$pattern = '('.$this->lengthPlus.'|'.$this->percentagePlus.'|auto|inherit)';
		return preg_match('/^'.$pattern.'$/i', $val) === 1;
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyMinWidth($val)
	{
		$pattern = '('.$this->lengthPlus.'|'.$this->percentagePlus.'|inherit)';
		return preg_match('/^'.$pattern.'$/i', $val) === 1;
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyMaxWidth($val)
	{
		$pattern = '('.$this->lengthPlus.'|'.$this->percentagePlus.'|none|inherit)';
		return preg_match('/^'.$pattern.'$/i', $val) === 1;
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyHeight($val)
	{
		return $this->callPropertyMethod('width', $val);
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyMinHeight($val)
	{
		return $this->callPropertyMethod('min-height', $val);
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyMaxHeight($val)
	{
		return $this->callPropertyMethod('max-height', $val);
	}

	/**
	 * check css value
	 *
	 * @param string  $val    css value
	 * @param boolean $return trueの場合、パターンを返す。
	 *
	 * @return boolean
	 */
	protected function propertyLineHeight($val, $return = false)
	{
		// normal | <number> | <length> | <percentage> | inherit
		$pattern = '(normal|'.$this->numberPlus.'|'. $this->lengthPlus .'|'. $this->percentagePlus .'|inherit)';
		return $return === false ? preg_match('/^'.$pattern.'$/i', $val) === 1 : $pattern;
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyVerticalAlign($val)
	{
		$pattern = '(baseline|sub|super|top|text-top|middle|bottom|text-bottom|'.$this->percentage.'|'.$this->length.'|inherit)';
		return preg_match('/^'.$pattern.'$/i', $val) === 1;
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyOverflow($val)
	{
		$pattern = '(visible|hidden|scroll|auto|inherit)';
		return preg_match('/^'.$pattern.'$/i', $val) === 1;
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyClip($val)
	{
		$clip = '(auto|'.$this->length.')';
		$c = '(?:\s*(?:\/\*[^*]*\*+(?:[^\/][^*]*\*+)*\/)*\s*)*';
		$rect = 'rect\('.$c.$clip.$c.','.$c.$clip.$c.','.$c.$clip.$c.','.$c.$clip.$c.'\)';
		$pattern = '(auto|'.$rect.')';
		return preg_match('/^'.$pattern.'$/i', $val) === 1;
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyVisibility($val)
	{
		$pattern = '(visible|hidden|collapse|inherit)';
		return preg_match('/^'.$pattern.'$/i', $val) === 1;
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyContent($val)
	{
		$url = $this->uri;
		$ident = $this->ident;
		$string = $this->string;
		$ig = $this->ignore;

		$listStyleType = '(disc|circle|square|decimal|decimal-leading-zero|lower-roman|upper-roman|lower-greek|lower-alpha|lower-latin|upper-alpha|upper-latin|hebrew|armenian|georgian|cjk-ideographic|hiragana|katakana|hiragana-iroha|katakana-iroha|none|inherit)';

		$arr = self::_split($val);
		// normal | none | [ <string> | <uri> | <counter> | attr(<identifier>) | open-quote | close-quote | no-open-quote | no-close-quote ]+ | inherit
		$patternMultiArr = array(
			$string,
			$url,
			'attr\('.$ig.$ident.$ig.'\)',

			// <counter> counter(<identifier>) | counter(<identifier>,<list-style-type>) | counters(<identifier>,<string>) | counters(<identifier>,<string>,<list-style-type>)
			'counter\('.$ig.$ident.$ig.'\)',
			'counter\('.$ig.$ident.$ig.','.$ig.$listStyleType.$ig.'\)',
			'counters\('.$ig.$ident.$ig.','.$ig.$string.$ig.'\)',
			'counters\('.$ig.$ident.$ig.','.$ig.$string.$ig.','.$ig.$listStyleType.$ig.'\)',
			'open-quote',
			'close-quote',
			'no-open-quote',
			'no-close-quote',
		);
		$patternOne = '(normal|none|'.implode('|', $patternMultiArr).'|inherit)';
		if (count($arr) === 1) return preg_match('/^'.$patternOne.'$/i', $arr[0]) === 1;
		$patternMulti = '('.implode('|', $patternMultiArr).')';
		foreach ($arr as $v) if(!preg_match('/^'.$patternMulti.'$/i', $v)) return false;

		return true;
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyQuotes($val)
	{
		$arr = self::_split($val);
		// [<string> <string>]+ | none | inheri
		$patternOne = '(none|inherit)';
		if (count($arr) === 1) return preg_match('/^'.$patternOne.'$/i', $arr[0]) === 1;
		if (count($arr) % 2 === 1) return false;
		$patternMulti = '('.$this->string.')';
		foreach ($arr as $v) if(!preg_match('/^'.$patternMulti.'$/i', $v)) return false;
		return true;
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyPageBreakBefore($val)
	{
		$pattern = '(auto|always|avoid|left|right|inherit)';
		return preg_match('/^'.$pattern.'$/i', $val) === 1;
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyPageBreakAfter($val)
	{
		return $this->callPropertyMethod('page-break-before', $val);
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyPageBreakInside($val)
	{
		$pattern = '(avoid|auto|inherit)';
		return preg_match('/^'.$pattern.'$/i', $val) === 1;
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyOrphans($val)
	{
		$pattern = '('.$this->integer.'|inherit)';
		return preg_match('/^'.$pattern.'$/i', $val) === 1;
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyWidows($val)
	{
		$pattern = '('.$this->integer.'|inherit)';
		return preg_match('/^'.$pattern.'$/i', $val) === 1;
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
		$pattern = '('.$this->color.'|inherit)';
		return preg_match('/^'.$pattern.'$/i', $val) === 1;
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyBackgroundColor($val)
	{
		// <color> | transparent | inherit
		$pattern = '('.$this->color.'|transparent|inherit)';
		return preg_match('/^'.$pattern.'$/i', $val) === 1;
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyBackgroundImage($val)
	{
		// <uri> | none | inherit
		$pattern = '('.$this->uri.'|none|inherit)';
		return preg_match('/^'.$pattern.'$/i', $val) === 1;
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyBackgroundRepeat($val)
	{
		// repeat | repeat-x | repeat-y | no-repeat | inherit
		$pattern = '(repeat|repeat-x|repeat-y|no-repeat|inherit)';
		return preg_match('/^'.$pattern.'$/i', $val) === 1;
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyBackgroundAttachment($val)
	{
		// scroll | fixed | inherit
		$pattern = '(scroll|fixed|inherit)';
		return preg_match('/^'.$pattern.'$/i', $val) === 1;
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyBackgroundPosition($val)
	{
		$arr = self::_split($val);
		// [[<percentage> | <length>]{1,2} | [top | center | bottom] || [left | center | right]] | inherit
		if (count($arr) > 2) return false;
		if (count($arr) === 1) {
			$patternOne = '('.$this->percentage.'|'.$this->length.'|top|center|bottom|left|right|inherit)';
			return preg_match('/^'.$patternOne.'$/i', $arr[0]) === 1;
		}

		// [top|bottom] が [left|right] に先行する場合のみ、先に検証する
		if (preg_match('/^(top|bottom)$/i', $arr[0]) && preg_match('/^(left|center|right)$/i', $arr[1])) return true;
		$patternMulti1 = '('.$this->percentage.'|'.$this->length.'|left|center|right)';
		$patternMulti2 = '('.$this->percentage.'|'.$this->length.'|top|center|bottom)';
		if (!preg_match('/^'.$patternMulti1.'$/i', $arr[0]) || !preg_match('/^'.$patternMulti2.'$/i', $arr[1])) return false;

		return true;
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyBackground($val)
	{
		$values = self::_split($val);
		$regArr = array(
			'background-color', 'background-image',
			'background-repeat', 'background-attachment', 'background-position'
		);

		if (count($values) > 6) return false; // 7個以上はありえない
		if (count($values) === 1 && $values[0] === 'inherit') return true;

		for ($i = 0; $i < count($values); $i++) {
			if ($values[$i] === 'inherit') return false;
			$before = count($regArr);
			foreach ($regArr as $n => $reg) {
				if ($this->callPropertyMethod($reg, $values[$i])) {
					if ($reg === 'background-position') {
						if (isset($values[$i+1]) === false
							|| (isset($values[$i+1]) && $this->callPropertyMethod($reg, $values[$i+1]))
						) $i++;
					}
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
	protected function propertyFontFamily($val)
	{
		$val = implode('', self::_split($val)); // 空白文字の削除
		if (strpos($val, ',') === 0 // 「,」の位置が適当でない場合、失敗する
			|| strpos($val, ',,') !== false
			|| strrpos($val, ',') === strlen($val) - 1
		) return false;
		$arr = self::_split($val, '\s*,\s*');
		if (count($arr) === 1) {
			$patternOne = '('.$this->genericFamily.'|'.$this->familyName.'|inherit)';
			return preg_match('/^'.$patternOne.'$/i', $arr[0]) === 1;
		}
		$patternMulti = '('.$this->genericFamily.'|'.$this->familyName.')';
		foreach ($arr as $v) if(!preg_match('/^'.$patternMulti.'$/i', $v)) return false;
		return true;
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyFontStyle($val)
	{
		$pattern = '(normal|italic|oblique|inherit)';
		return preg_match('/^'.$pattern.'$/i', $val) === 1;
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyFontVariant($val)
	{
		$pattern = '(normal|small-caps|inherit)';
		return preg_match('/^'.$pattern.'$/i', $val) === 1;
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyFontWeight($val)
	{
		$pattern = '(normal|bold|bolder|lighter|100|200|300|400|500|600|700|800|900|inherit)';
		return preg_match('/^'.$pattern.'$/i', $val) === 1;
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyFontStretch($val)
	{
		$pattern = '(normal|wider|narrower|ultra-condensed|extra-condensed|condensed|semi-condensed|semi-expanded|expanded|extra-expanded|ultra-expanded|inherit)';
		return preg_match('/^'.$pattern.'$/i', $val) === 1;
	}

	/**
	 * check css value
	 *
	 * @param string  $val    css value
	 * @param boolean $return trueの場合、パターンを返す。
	 *
	 * @return boolean
	 */
	protected function propertyFontSize($val, $return = false)
	{
		$pattern = '('.$this->absoluteSize.'|'.$this->relativeSize.'|'.$this->lengthPlus.'|'.$this->percentagePlus.'|inherit)';
		return $return === false ? preg_match('/^'.$pattern.'$/i', $val) === 1 : $pattern;
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyFontSizeAdjust($val)
	{
		$pattern = '('.$this->numberPlus.'|none|inherit)';
		return preg_match('/^'.$pattern.'$/i', $val) === 1;
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyFont($val)
	{
		$result = array();
		// [[ <'font-style'> || <'font-variant'> || <'font-weight'> ]? <'font-size'> [ / <'line-height'> ]? <'font-family'> ] | caption | icon | menu | message-box | small-caption | status-bar | inherit
		$arr = self::_split($val);
		if (count($arr) === 1) {
			$patternOne = '(caption|icon|menu|message-box|small-caption|status-bar|inherit)';
			return preg_match('/^'.$patternOne.'$/i', $arr[0]) === 1;
		}

		// [[ <'font-style'> || <'font-variant'> || <'font-weight'> ]?
		$regArr = array('font-style', 'font-variant', 'font-weight');
		$before = null;
		while ($before !== count($regArr)) {
			if (isset($arr[0]) === false) return false;
			$before = count($regArr);
			foreach ($regArr as $n => $reg) {
				if ($this->callPropertyMethod($reg, $arr[0])) {
					$result[] = array_shift($arr);
					unset($regArr[$n]);
					break;
				}
			}
		}

		$arr = array_merge($arr, array());

		// <'font-size'> [ / <'line-height'> ]?
		$fontSize = array(
			$this->callPropertyMethod('font-size', $val, true),
			'('.$this->callPropertyMethod('font-size', $val, true).'\\/'.$this->callPropertyMethod('line-height', $val, true).')'
		);

		if (isset($arr[0]) === false) return false;
		if (preg_match('/^'.$fontSize[0].'$/i', $arr[0])) {
			$result[] = array_shift($arr);
			if (isset($arr[0]) && preg_match('/^(\\/'.$this->callPropertyMethod('line-height', $val, true).')$/i', $arr[0])) $result[] = array_shift($arr);
			elseif (isset($arr[0]) && $this->callPropertyMethod('line-height', $arr[0])) return false; // font-familyの<string>対策
		} elseif (preg_match('/^'.$fontSize[1].'$/i', $arr[0])) {
			$result = array_merge($result, explode('/', array_shift($arr)));
		} else {
			return false;
		}

		if (isset($arr[0]) === false) return false;

		return $this->callPropertyMethod('font-family', implode('', $arr));
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyTextIndent($val)
	{
		$pattern = '('.$this->length.'|'. $this->percentage .'|inherit)';
		return preg_match('/^'.$pattern.'$/i', $val) === 1;
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyTextAlign($val)
	{
		$pattern = '(left|right|center|justify|inherit)';
		return preg_match('/^'.$pattern.'$/i', $val) === 1;
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyTextDecoration($val)
	{
		$values = self::_split($val);
		$regArr = array('underline', 'overline', 'line-through', 'blink');

		if (count($values) === 1) { // 1個の場合は、'none','inherit'が含まれる可能性がある
			$regArr = array_merge($regArr, array('none', 'inherit'));
			if (!preg_match("/^(?:".implode('|', $regArr).")$/", $values[0], $m)) {
				return false;
			}
		} else {
			foreach ($values as $value) {
				$before = count($regArr);
				foreach ($regArr as $n => $reg) {
					if (preg_match("/^(?:$reg)$/", $value)) {
						unset($regArr[$n]);
						break;
					}
				}
				if (count($regArr) === $before) return false; // 変化しない => マッチしなかった
			}
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
	protected function propertyLetterSpacing($val)
	{
		$pattern = '(normal|inherit|'.$this->length.')';
		return preg_match('/^'.$pattern.'$/i', $val) === 1;
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyWordSpacing($val)
	{
		return $this->callPropertyMethod('letter-spacing', $val);
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyTextTransform($val)
	{
		$pattern = '(capitalize|uppercase|lowercase|none|inherit)';
		return preg_match('/^'.$pattern.'$/i', $val) === 1;
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyWhiteSpace($val)
	{
		$pattern = '(normal|pre|nowrap|pre-wrap|pre-line|inherit)';
		return preg_match('/^'.$pattern.'$/i', $val) === 1;
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyCaptionSide($val)
	{
		$pattern = '(top|bottom|inherit)';
		return preg_match('/^'.$pattern.'$/i', $val) === 1;
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyTableLayout($val)
	{
		$pattern = '(auto|fixed|inherit)';
		return preg_match('/^'.$pattern.'$/i', $val) === 1;
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyBorderCollapse($val)
	{
		$pattern = '(collapse|separate|inherit)';
		return preg_match('/^'.$pattern.'$/i', $val) === 1;
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyBorderSpacing($val)
	{
		$arr = self::_split($val);
		if (count($arr) === 1) {
			$patternOne = '('.$this->lengthPlus.'|inherit)';
			return preg_match('/^'.$patternOne.'$/i', $arr[0]) === 1;
		}

		foreach ($arr as $v) if (!preg_match('/^'.$this->lengthPlus.'$/i', $v)) return false;

		return true;
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyEmptyCells($val)
	{
		$pattern = '(show|hide|inherit)';
		return preg_match('/^'.$pattern.'$/i', $val) === 1;
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyCursor($val)
	{
		$patternOne = '(auto|crosshair|default|pointer|move|e-resize|ne-resize|nw-resize|n-resize|se-resize|sw-resize|s-resize|w-resize|text|wait|help|progress|inherit)';
		if (preg_match('/^'.$patternOne.'$/i', $val) === 1) return true;

		if (strpos($val, ',') === 0 // 「,」の位置が適当でない場合、失敗する
			|| strpos($val, ',,') !== false
			|| strrpos($val, ',') === strlen($val) - 1
		) return false;
		$arr = self::_split($val, '\s*,\s*');
		if (count($arr) < 2) return false;
		$last = array_pop($arr);
		foreach ($arr as $v) if (!preg_match('/^'.$this->uri.'$/i', $v)) return false;
		$patternMulti = '(auto|crosshair|default|pointer|move|e-resize|ne-resize|nw-resize|n-resize|se-resize|sw-resize|s-resize|w-resize|text|wait|help|progress)';
		return preg_match('/^'.$patternMulti.'$/i', $last) === 1;
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyOutline($val)
	{
		// [ <'outline-color'> || <'outline-style'> || <'outline-width'> ] | inherit
		$arr = self::_split($val);
		$patternArr = array(
			$this->callPropertyMethod('outline-color', $val, true),
			$this->borderStyle,
			$this->borderWidth
		);


		if (count($arr) === 1) {
			$patternOne = '('.implode('|', $patternArr).'|inherit)';
			return preg_match('/^'.$patternOne.'$/i', $arr[0]) === 1;
		}

		foreach ($arr as $value) {
			$before = count($patternArr);
			foreach ($patternArr as $n => $pattern) {
				if (preg_match('/^'.$pattern.'$/i', $value)) {
					unset($patternArr[$n]);
					break;
				}
			}
			if (count($patternArr) === $before) return false;
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
	protected function propertyOutlineWidth($val)
	{
		// <border-width> | inherit
		return $this->callPropertyMethod('border-top-width', $val);
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyOutlineStyle($val)
	{
		// <border-style> | inherit
		return $this->callPropertyMethod('border-top-style', $val);
	}

	/**
	 * check css value
	 *
	 * @param string  $val    css value
	 * @param boolean $return trueの場合、パターンを返す。
	 *
	 * @return boolean
	 */
	protected function propertyOutlineColor($val, $return = false)
	{
		// <color> | invert | inherit
		$outlineColor = '(?:'.$this->color.'|invert)';
		$pattern = '('.$outlineColor.'|inherit)';
		return $return === false ? preg_match('/^'.$pattern.'$/i', $val) === 1 : $outlineColor;
	}









}