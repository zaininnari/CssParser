<?php
require_once dirname(__FILE__) . '/IValidate.php';

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
	protected $selectorParseListOrigin = array();

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
	 * メンテナンスをしやすくするため自動生成
	 *
	 * @var array
	 */
	protected $def, $valDef;
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
		// propertyのリストを作成する
		/////////////////////////////////////////////////////////////////
		$this->propertyList = $this->_initializePropertyList();

		/////////////////////////////////////////////////////////////////
		// css 内部定義構築
		// http://www.w3.org/TR/CSS21/grammar.html#scanner
		/////////////////////////////////////////////////////////////////

		$nl = '(?:\n|\r\n|\r|\f)';
		$ascii    = '[\x00-\x7f]'; // x00-x7f hexdec   0 - 127 decoct   \0 - \177
		$nonascii = '[\x80-\xff]'; // x80-xff hexdec 128 - 255 decoct \200 - \377
		$unicode = '(?:\\\[0-9a-fA-F]{1,6}[ \t\r\n\f]?)';
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
		$escape = '(?:'.$unicode.'|\\\( |-|~|'.$nonascii.'))';
		$nmstart = '(?:[_a-zA-Z]|'.$nonascii.'|'.$escape.'|'.$utf8.')';
		$nmchar = '(?:[_a-zA-Z0-9-]|'.$nonascii.'|'.$escape.'|'.$utf8.')';
		$string1 = '(\"([\t !#$%&(-~]|\\\\'.$nl.'|\\\'|'.$nonascii.'|'.$escape.'|'.$utf8.')*\")';
		$string2 = '(\\\'([\t !#$%&(-~]|\\\\'.$nl.'|"|'.$nonascii.'|'.$escape.'|'.$utf8.')*\\\')';
		$string = '('.$string1.'|'.$string2.')';

		$ident = '-?'.$nmstart.$nmchar.'*';
		$_url = '([!#$%&*-~]|'.$nonascii.'|'.$escape.')+';
		$url1 = 'url\(\s*'.$string.'*\s*\)';
		$url2 = 'url\(\s*'.$_url.'\s*\)';
		$url = '('.$url1.'|'.$url2.')';

		$this->def = array(
			'ident'   => $ident,
			'uri'     => $url,
			'string'  => $string,
			'unicode' => $unicode
		);

		/////////////////////////////////////////////////////////////////
		// セレクタのリストを対応するCSSのレベルに合わせる
		/////////////////////////////////////////////////////////////////
		$_selectorParseList = $this->selectorParseListOrigin = $this->_initializeSelector();

		foreach ($this->removeSelectorParseList as $remove) {
			if (isset($_selectorParseList[$remove])) {
				unset($_selectorParseList[$remove]);
			}
		}
		$this->selectorParseList = $_selectorParseList;

		$this->valDef = $this->_initializeValue();
	}

	/**
	 * propertyのリストを作成する
	 *
	 * @return Array
	 */
	protected function _initializePropertyList()
	{
		$propertyList = array();
		/*$list = array(
			'text-indent','text-align','text-decoration','letter-spacing','word-spacing','text-transform','white-space',
			'color','background-color','background-image','background-repeat','background-attachment','background-position','background',
			'font-family','font-style','font-variant','font-weight','font-size','font',
			'margin-top','margin-right','margin-bottom','margin-left','margin',
			'padding-top','padding-right','padding-bottom','padding-left','padding',
			'border-top-width','border-right-width','border-bottom-width','border-left-width','border-width',
			'border-top-color','border-right-color','border-bottom-color','border-left-color','border-color',
			'border-top-style','border-right-style','border-bottom-style','border-left-style','border-style',
			'border-top','border-right','border-bottom','border-left','border',
			'display','position','top','bottom','right','left','float','clear','z-index','direction','unicode-bidi',
			'width','min-width','max-width','height','min-height','max-height','line-height','vertical-align',
			'overflow','clip','visibility',
			'caption-side','table-layout','border-collapse','border-spacing','empty-cells',
			'content','quotes','counter-reset','counter-increment','list-style-type','list-style-image','list-style-position','list-style',
			'cursor','outline','outline-width','outline-style','outline-color',
		);
		foreach ($list as $v) {
			$property = '-' . $v;
			$propertyList[$property] = $this->propertyMethodPrefix . str_replace(' ', '', ucwords(str_replace('-', ' ', $property)));
		}
		return $propertyList;*/

		$ref = new ReflectionClass(__CLASS__);
		$methods = $ref->getMethods();
		$len = strlen($this->propertyMethodPrefix);

		foreach ($methods as $method) {
			if (strpos($method->name, $this->propertyMethodPrefix) === 0 && strlen($method->name) > $len) {
				$property = preg_replace('/^'.$this->propertyMethodPrefix.'/', '', $method->name, 1);
				$property = strtolower(preg_replace('/([A-Z])/', '-$1', $property));
				$propertyList[$property] = $method->name;
			}
		}
		return $propertyList;
	}

	/**
	 * 初期化
	 *
	 * @return ?
	 */
	protected function _initializeSelector()
	{
		return array(
			'universal'    => '(\*)',
			'descendant'   => '( )',
			'id'           => '(#'.$this->def['ident'].')',
			'class'        => '(\.'.$this->def['ident'].')',
			'child'        => '(>)',
			'adjacent'     => '(\+)',

			// Pseudo-classes
			'link'         => '(:(link|visited))',
			'dynamic'      => '(:(link|visited|hover|active|focus))',
			'first-child'  => '(:first-child)',

			// language pseudo-class
			// ISO_639
			'language'     => '(:lang\([a-z\-]+\))',

			// Pseudo-elements
			'first-line'   => '(:first-line)',
			'first-letter' => '(:first-letter)',
			'before'       => '(:before)',
			'after'        => '(:after)',

			// 属性セレクタ（Attribute selectors）
			// '[' S* IDENT S* [ [ '=' | INCLUDES | DASHMATCH ] S* [ IDENT | STRING ] S* ]? ']'
			'attribute'    => '(\[\s*'.$this->def['ident'].'\s*(?:(?:=|~=|\|=)\s*(?:'.$this->def['ident'].'|'.$this->def['string'].')\s*)?\])',

			// type
			'type'         => '('.$this->def['ident'].')',
		);
	}

	/**
	 * 初期化
	 *
	 * @return ?
	 */
	protected function _initializeValue()
	{
		$valDef = array();
		$valDef['integer']        = $integer        = '(?:(?:\+|-|)(?:[0-9]{1,}))';
		$valDef['numberPlus']     = $numberPlus     = '(?:\+|)(?:[0-9]{1,}(\.[0-9]+)?|\.[0-9]+)';
		$valDef['length']         = $length         = '(?:(?:\+|-|)(?:(?:[0-9]{1,}(\.[0-9]+)?|\.[0-9]+)(?:px|em|ex|in|cm|mm|pt|pc)|0))';
		$valDef['lengthPlus']     = $lengthPlus     = '(?:(?:\+|)(?:(?:[0-9]{1,}(\.[0-9]+)?|\.[0-9]+)(?:px|em|ex|in|cm|mm|pt|pc)|0))';
		$valDef['percentage']     = $percentage     = '(?:(?:\+|-|)(?:[0-9]{1,}(\.[0-9]+)?|\.[0-9]+)%)';
		$valDef['percentagePlus'] = $percentagePlus = '(?:\+?(?:[0-9]{1,}(\.[0-9]+)?|\.[0-9]+)%)';
		$valDef['ignore']         = $ignore         = '(?:\s*(?:\/\*[^*]*\*+(?:[^\/][^*]*\*+)*\/)*\s*)*';

		if (isset($this->valDef['color'])) {
			$valDef['color'] = $color = $this->valDef['color'];
		} else {
			$rgb1 = '(?:[0-2][0-5]{2}|[0-1][0-9]{0,2})';
			$rgb2 = '(?:[0-9]{1,3}|0)%';
			$colorArr = array(
				// #000
				'(?:#[0-9a-fA-F]{3})',
				// #000000
				'(?:#[0-9a-zA-Z]{6})',
				// rgb(255,0,0)
				'(?:rgb\('.$ignore.$rgb1.$ignore.','.$ignore.$rgb1.$ignore.','.$ignore.$rgb1.$ignore.'\))',
				// rgb(100%,0%,0%)
				'(?:rgb\('.$ignore.$rgb2.$ignore.','.$ignore.$rgb2.$ignore.','.$ignore.$rgb2.$ignore.'\))',
				// 基本カラー 16
				'(?:Black|Silver|Gray|White|Maroon|Red|Purple|Fuchsia|Green|Lime|Olive|Yellow|Navy|Blue|Teal|Aqua)',
				// 追加基本カラー css2.1 add
				'(?:Orange)',
				// 147
				'(?:aliceblue|antiquewhite|aqua|aquamarine|azure|beige|bisque|black|blanchedalmond|blue|blueviolet|brass|brown|burlywood|cadetblue|chartreuse|chocolate|coolcopper|copper|coral|cornflower|cornflowerblue|cornsilk|crimson|cyan|darkblue|darkbrown|darkcyan|darkgoldenrod|darkgray|darkgreen|darkkhaki|darkmagenta|darkolivegreen|darkorange|darkorchid|darkred|darksalmon|darkseagreen|darkslateblue|darkslategray|darkturquoise|darkviolet|deeppink|deepskyblue|dimgray|dodgerblue|feldsper|firebrick|floralwhite|forestgreen|fuchsia|gainsboro|ghostwhite|gold|goldenrod|gray|green|greenyellow|honeydew|hotpink|indianred|indigo|ivory|khaki|lavender|lavenderblush|lawngreen|lemonchiffon|lightblue|lightcoral|lightcyan|lightgoldenrodyellow|lightgreen|lightgrey|lightpink|lightsalmon|lightseagreen|lightskyblue|lightslategray|lightsteelblue|lightyellow|lime|limegreen|linen|magenta|maroon|mediumaquamarine|mediumblue|mediumorchid|mediumpurple|mediumseagreen|mediumslateblue|mediumspringgreen|mediumturquoise|mediumvioletred|midnightblue|mintcream|mistyrose|moccasin|navajowhite|navy|oldlace|olive|olivedrab|orange|orangered|orchid|palegoldenrod|palegreen|paleturquoise|palevioletred|papayawhip|peachpuff|peru|pink|plum|powderblue|purple|red|richblue|rosybrown|royalblue|saddlebrown|salmon|sandybrown|seagreen|seashell|sienna|silver|skyblue|slateblue|slategray|snow|springgreen|steelblue|tan|teal|thistle|tomato|turquoise|violet|wheat|white|whitesmoke|yellow|yellowgreen)',
				// システムカラー
				'(?:Background|Window|WindowText|WindowFrame|ActiveBorder|InactiveBorder|ActiveCaption|InactiveCaption|CaptionText|InactiveCaptionText|Scrollbar|AppWorkspace|Highlight|HighlightText|GrayText|Menu|MenuText|ButtonFace|ButtonText|ButtonHighlight|ButtonShadow|ThreeDFace|ThreeDHighlight|ThreeDShadow|ThreeDLightShadow|ThreeDDarkShadow|InfoText|InfoBackground)',
			);
			$valDef['color'] = $color = '(?:'.implode('|', $colorArr).')';
		}

		$valDef['generic-family'] = '(?:serif|sans-serif|cursive|fantasy|monospace)';
		$valDef['family-name']    = '(?:'.$this->def['string'].'|(?:'.$this->def['ident'].'+))';
		$valDef['absolute-size']  = '(?:xx-small|x-small|small|medium|large|x-large|xx-large)';
		$valDef['relative-size']  = '(?:larger|smaller)';
		$valDef['font-size']      = '(?:'.$valDef['absolute-size'].'|'.$valDef['relative-size'].'|'.$valDef['lengthPlus'].'|'.$valDef['percentagePlus'].')';

		$valDef['line-height']    = '(?:normal|'.$numberPlus.'|'. $lengthPlus .'|'. $percentagePlus .')';

		$valDef['margin-width']   = '(?:' . $length . '|' . $percentage . '|auto)';
		$valDef['padding-width']  = '(?:' . $lengthPlus . '|' . $percentagePlus . ')';

		$valDef['border-width']   = $valDef['outline-width'] = '(?:thin|medium|thick|' . $lengthPlus . '|' . $percentagePlus . ')';
		$valDef['border-style']   = $valDef['outline-style'] = '(?:none|hidden|dotted|dashed|solid|double|groove|ridge|inset|outset)';
		$valDef['border-color']   = '(?:transparent|'.$color.')';
		$valDef['outline-color']  = '(?:invert|'.$color.')';

		// css2 -> css2.1 : delete  "hebrew", "cjk-ideographic", "hiragana", "katakana", "hiragana-iroha", "katakana-iroha"
		$valDef['list-style-type']     = '(?:disc|circle|square|decimal|decimal-leading-zero|lower-roman|upper-roman|lower-greek|lower-latin|upper-latin|armenian|georgian|lower-alpha|upper-alpha|none)';
		$valDef['list-style-image']    = '(?:'.$this->def['uri'].'|none)';
		$valDef['list-style-position'] = '(?:inside|outside)';

		return $valDef;
	}

	/**
	 * validate
	 *
	 * @param CSSParser_Node $node node object
	 *
	 * @see CSSParser/IValidate#validate($node)
	 *
	 * @return CSSParser_Node
	 */
	function validate(CSSParser_Node $node)
	{
		if ($node->getType() !== 'root') throw new InvalidArgumentException();
		return $this->readNode($node);
	}

	/**
	 * read node
	 *
	 * @param CSSParser_Node $node node object
	 *
	 * @return array
	 */
	protected function readNode(CSSParser_Node $node)
	{
		$ret = $this->{'read' . $node->getType()}($node->getData());
		return $o = new CSSParser_Node($node->getType(), $ret, $node->getOffset());
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
			if ($delc instanceof CSSParser_Node && $delc->getType() === 'unknown') continue;
			if ($delc['value']->getData() === ''  // css-valueが空文字
				|| $this->callPropertyMethod($delc['property']->getData(), $delc['value']->getData()) === false // css-property(メソッド名)が適切でない
			) {
				$isValid = false;
			} else {
				$isValid = true;
			}
			$arr['block'][$key]['isValid'] = $isValid;
		}

		return array(
			'selector' => new CSSParser_Node($arr['selector']->getType(), $selectors, $arr['selector']->getOffset()),
			'block' => $arr['block']
		);
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
		if ($arr['selector'] instanceof CSSParser_Node && $arr['selector']->getType() === 'unknown') return $arr;
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
		if (!isset($args[0], $args[1])) return false;
		list($method, $arg) = $args;

		$method = $this->getPropertyMethod($method);
		if ($method === false) return false; // メソッドが存在しない/適当でない
		return $this->{$method}($arg);
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
		$selector = preg_replace(
			array('/\s+/', '/\s*>\s*/', '/\s*\+\s*/'),
			array(' ',     '>',         '+'),
			$selector
		);

		// te\st -> test
		$selector = preg_replace('/\\\([g-zG-Z])/', '$1', $selector);

		// \61 -> a
		if (preg_match_all('/'.$this->def['unicode'].'/i', $selector, $matches)) {
			foreach ($matches[0] as $match) {
				$selector = str_replace($match, chr(hexdec($match)), $selector);
			}
		}

		$result['cleanSelector'] = $selector;

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
				if ($pattern !== null && preg_match('/^'.$pattern.'/', $selector, $matches)) {
					$res[] = array($type, $matches[1]);
					$selector = mb_substr($selector, mb_strlen($matches[1]));
					$seek += mb_strlen($matches[1]);
					break;
				}
			}
			if ($seek === $before) {
				// TODO
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
	 * mediaQueryのチェックをする。
	 *
	 * @param string $mediaType string without comma
	 *
	 * @return array
	 */
	//protected function mediaQueryValidate($mediaQuery)
	//{
	//}

	/**
	 * parse media types
	 *
	 * @param string $string dirty media types
	 *
	 * @return array
	 */
	protected function mediaQueryParse($string)
	{
		$result = array();
		$mediaTypes = '(all|braille|embossed|handheld|print|projection|screen|speech|tty|tv)';


		// Grammar http://www.w3.org/TR/CSS21/grammar.html
		// media_list : medium [ COMMA S* medium]*;
		// medium : IDENT S*;

		// http://www.w3.org/TR/css3-mediaqueries/#syntax
		// media_query_list : S* media_query [ ',' S* media_query]* ;
		// media_query : [ONLY | NOT]? S* media_type S* [ AND S* expression ]* | expression [ AND S* expression ]* ;
		// media_type : IDENT ;
		// expression : '(' S* media_feature S* [':' S* expr]? ')' S* ;
		// media_feature : IDENT ;
		// {O}{N}{L}{Y}      {return ONLY;}
		// {N}{O}{T}         {return NOT;}
		// {A}{N}{D}         {return AND;}
		// {num}{D}{P}{I}    {return RESOLUTION;}
		// {num}{D}{P}{C}{M} {return RESOLUTION;}
		$onlyOrNot = '(only|not)';
		$mediaFeature = $mediaType = '('.$this->def['ident'].')';
		$expression = '\(\s*'.$mediaFeature.'\s*(?::\s*)?\)';
		if (preg_match('/^'.$mediaTypes.'$/i', $string, $matches)) return $matches[0];
		else return false;
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
		$pattern = '('. $this->valDef['margin-width'] .'|inherit)';
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
		$patternOne   = '(inherit|'.$this->valDef['margin-width'].')';
		$patternMulti = '('.$this->valDef['margin-width'].')';
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
		$pattern = '('.$this->valDef['padding-width'].'|inherit)';
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
		$patternOne   = '(inherit|'.$this->valDef['padding-width'].')';
		$patternMulti = '('.$this->valDef['padding-width'].')';
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
		$pattern = '('.$this->valDef['border-width'].'|inherit)';
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
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyBorderWidth($val)
	{
		$patternOne   = '(inherit|'.$this->valDef['border-width'].')';
		$patternMulti = $this->valDef['border-width'];
		return self::_propertyMarginOrPaddingOrBorder($val, $patternOne, $patternMulti);
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
		$pattern = '(inherit|'.$this->valDef['border-color'].')';
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
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyBorderColor($val)
	{
		$patternOne   = '(inherit|'.$this->valDef['border-color'].')';
		$patternMulti = $this->valDef['border-color'];
		return self::_propertyMarginOrPaddingOrBorder($val, $patternOne, $patternMulti);
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
		$pattern = '('.$this->valDef['border-style'].'|inherit)';
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
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyBorderStyle($val)
	{
		$patternOne   = '('.$this->valDef['border-style'].'|inherit)';
		$patternMulti = $this->valDef['border-style'];
		return self::_propertyMarginOrPaddingOrBorder($val, $patternOne, $patternMulti);
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
			$this->valDef['border-color'],
			// <border-style> = none|hidden|dotted|dashed|solid|double|groove|ridge|inset|outset
			$this->valDef['border-style'],
			// <border-width> = thin | medium | thick | <lengthPlus>
			$this->valDef['border-width']
		);
		$arr = self::_split($val);

		if (count($arr) > 4) return false;
		if (count($arr) === 1) {
			if ($arr[0] === 'inherit') return true;
			foreach ($patternArr as $v) if (preg_match('/^'.$v.'$/i', $arr[0])) return true;
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
	protected function propertyListStyleType($val)
	{
		$pattern = '('.$this->valDef['list-style-type'].'|inherit)';
		return preg_match('/^'.$pattern.'$/i', $val) === 1;
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyListStyleImage($val)
	{
		$pattern = '('.$this->valDef['list-style-image'].'|inherit)';
		return preg_match('/^'.$pattern.'$/i', $val) === 1;
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyListStylePosition($val)
	{
		$pattern = '('.$this->valDef['list-style-position'].'|inherit)';
		return preg_match('/^'.$pattern.'$/i', $val) === 1;
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyListStyle($val)
	{
		$arr = self::_split($val);
		$patternArr = array('list-style-type', 'list-style-image', 'list-style-position');
		if (count($arr) > 3) return false;
		if (count($arr) === 1) {
			if ($arr[0] === 'inherit') return true;
			foreach ($patternArr as $pattern) if ($this->callPropertyMethod($pattern, $arr[0])) return true;
			return false;
		}

		foreach ($arr as $value) {
			$before = count($patternArr);
			foreach ($patternArr as $n => $pattern) {
				if ($this->callPropertyMethod($pattern, $value)) {
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
		$pattern = '(auto|'.$this->valDef['integer'].'|inherit)';
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
		$pattern = '('.$this->valDef['lengthPlus'].'|'.$this->valDef['percentagePlus'].'|auto|inherit)';
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
		$pattern = '('.$this->valDef['lengthPlus'].'|'.$this->valDef['percentagePlus'].'|inherit)';
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
		$pattern = '('.$this->valDef['lengthPlus'].'|'.$this->valDef['percentagePlus'].'|none|inherit)';
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
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyLineHeight($val)
	{
		// normal | <number> | <length> | <percentage> | inherit
		$pattern = '('.$this->valDef['line-height'].'|inherit)';
		return preg_match('/^'.$pattern.'$/i', $val) === 1;
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
		$pattern = '(baseline|sub|super|top|text-top|middle|bottom|text-bottom|'.$this->valDef['percentage'].'|'.$this->valDef['length'].'|inherit)';
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
		$clip = '(auto|'.$this->valDef['length'].')';
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
		$ident         = $this->def['ident'];
		$string        = $this->def['string'];
		$ig            = $this->valDef['ignore'];
		$listStyleType = $this->valDef['list-style-type'];

		$arr = self::_split($val);
		// normal | none | [ <string> | <uri> | <counter> | attr(<identifier>) | open-quote | close-quote | no-open-quote | no-close-quote ]+ | inherit
		$patternMultiArr = array(
			$string,
			$this->def['uri'],
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
		$patternMulti = '('.$this->def['string'].')';
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
	protected function propertyCounterReset($val)
	{
		// [ <identifier> <integer>? ]+ | none | inherit
		$arr = self::_split($val);
		$result = array();
		if (count($arr) === 1) {
			$patternOne = '('.$this->def['ident'].'|none|inherit)';
			return preg_match('/^'.$patternOne.'$/i', $arr[0]) === 1;
		}
		$ident = $this->def['ident'];
		$int   = $this->valDef['integer'];
		while (count($arr) > 0) {
			if (!preg_match('/^'.$ident.'$/i', $arr[0])) {
				return false;
			} else {
				$result[] = array_shift($arr);
				if (isset($arr[0]) && preg_match('/^'.$int.'$/i', $arr[0])) $result[] = array_shift($arr);
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
	protected function propertyCounterIncrement($val)
	{
		return $this->callPropertyMethod('counter-reset', $val);
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
		$pattern = '('.$this->valDef['color'].'|inherit)';
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
		$pattern = '('.$this->valDef['color'].'|transparent|inherit)';
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
		$pattern = '('.$this->def['uri'].'|none|inherit)';
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
			$patternOne = '('.$this->valDef['percentage'].'|'.$this->valDef['length'].'|top|center|bottom|left|right|inherit)';
			return preg_match('/^'.$patternOne.'$/i', $arr[0]) === 1;
		}

		// [top|bottom] が [left|right] に先行する場合のみ、先に検証する
		if (preg_match('/^(top|bottom)$/i', $arr[0]) && preg_match('/^(left|center|right)$/i', $arr[1])) return true;
		$patternMulti1 = '('.$this->valDef['percentage'].'|'.$this->valDef['length'].'|left|center|right)';
		$patternMulti2 = '('.$this->valDef['percentage'].'|'.$this->valDef['length'].'|top|center|bottom)';
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
			$patternOne = '('.$this->valDef['generic-family'].'|'.$this->valDef['family-name'].'|inherit)';
			return preg_match('/^'.$patternOne.'$/i', $arr[0]) === 1;
		}
		$patternMulti = '('.$this->valDef['generic-family'].'|'.$this->valDef['family-name'].')';
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
	/* css2.1 delete
	protected function propertyFontStretch($val)
	{
		$pattern = '(normal|wider|narrower|ultra-condensed|extra-condensed|condensed|semi-condensed|semi-expanded|expanded|extra-expanded|ultra-expanded|inherit)';
		return preg_match('/^'.$pattern.'$/i', $val) === 1;
	}
	*/

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyFontSize($val)
	{
		$pattern = '('.$this->valDef['font-size'].'|inherit)';
		return preg_match('/^'.$pattern.'$/i', $val) === 1;
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	/* css2.1 delete
	protected function propertyFontSizeAdjust($val)
	{
		$pattern = '('.$this->valDef['numberPlus'].'|none|inherit)';
		return preg_match('/^'.$pattern.'$/i', $val) === 1;
	}
	*/

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

		// <'font-size'> [ / <'line-height'> ]?
		$fontSize = array(
			$this->valDef['font-size'],
			'('.$this->valDef['font-size'].'\\/'.$this->valDef['line-height'].')'
		);

		if (isset($arr[0]) === false) return false;
		if (preg_match('/^'.$fontSize[0].'$/i', $arr[0])) {
			$result[] = array_shift($arr);
			if (isset($arr[0]) && preg_match('/^(\\/'.$this->valDef['line-height'].')$/i', $arr[0])) $result[] = array_shift($arr);
		} elseif (preg_match('/^'.$fontSize[1].'$/i', $arr[0])) {
			$result = array_merge($result, explode('/', array_shift($arr)));
		} else {
			return false;
		}

		if (isset($arr[0]) === false) return false;

		// 'font-family' のみで構成されているかどうかのチェック
		if (count($arr) > 1) {
			$count = count($arr);
			for ($i=0;$i<$count;$i++) {
				if (strpos($arr[$i] . $arr[$i < $count - 1 ? $i + 1 : $i - 1], ',') === false) return false;
			}
		}

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
		$pattern = '('.$this->valDef['length'].'|'. $this->valDef['percentage'] .'|inherit)';
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
		$pattern = '(normal|inherit|'.$this->valDef['length'].')';
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
			$patternOne = '('.$this->valDef['lengthPlus'].'|inherit)';
			return preg_match('/^'.$patternOne.'$/i', $arr[0]) === 1;
		}

		foreach ($arr as $v) if (!preg_match('/^'.$this->valDef['lengthPlus'].'$/i', $v)) return false;

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
		foreach ($arr as $v) if (!preg_match('/^'.$this->def['uri'].'$/i', $v)) return false;
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
			$this->valDef['outline-color'],
			$this->valDef['outline-style'],
			$this->valDef['outline-width']
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
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyOutlineColor($val)
	{
		// <color> | invert | inherit
		$pattern = '('.$this->valDef['outline-color'].'|inherit)';
		return preg_match('/^'.$pattern.'$/i', $val) === 1;
	}

}