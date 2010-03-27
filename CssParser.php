<?php


require_once 'PEG.php';

require_once dirname(__FILE__) . '/CssParser/Locator.php';
require_once dirname(__FILE__) . '/CssParser/Comment.php';
require_once dirname(__FILE__) . '/CssParser/Ignore.php';
require_once dirname(__FILE__) . '/CssParser/Block.php';
require_once dirname(__FILE__) . '/CssParser/NodeCreater.php';
require_once dirname(__FILE__) . '/CssParser/Node.php';
require_once dirname(__FILE__) . '/CssParser/Selector.php';
require_once dirname(__FILE__) . '/CssParser/RuleSet.php';
require_once dirname(__FILE__) . '/CssParser/AtRule.php';
require_once dirname(__FILE__) . '/CssParser/FontFace.php';

require_once dirname(__FILE__) . '/CssParser/IValidate.php';
require_once dirname(__FILE__) . '/CssParser/AValidate.php';

class CssParser
{

	static protected $css;

	/**
	 * cssをパースして構造木を返す
	 *
	 * @param string $css string
	 *
	 * @return CssParser_Node
	 */
	static function parse($css)
	{
		self::$css = self::cssClean($css);
		$result = CssParser_Locator::it()->parser->parse(PEG::context(self::$css));

		return $result;
	}

	/**
	 * cssをパースして、有効なものだけを返す。
	 *
	 * @param string $css  css
	 * @param string $type type
	 *
	 * @return CssParser_Node
	 */
	static function validate($css, $type = null)
	{
		$node = self::parse($css);
		$validator = self::factory($type);
		return $validator->validate($node);
	}

	/**
	 * 指定した種類のインスタンスを返す。
	 * 指定しなければ、css2.1のパーサインスタンスを返す。
	 *
	 * @param string $type string
	 *
	 * @return object
	 */
	static protected function factory($type = null)
	{
		$type = $type === null ? 'css21' : $type;
		$dirPath = dirname(__FILE__);
		$subDirName = __CLASS__;
		$subDirPath = $dirPath . '/' . $subDirName;
		if (!file_exists($subDirPath . '/' . $type . '.php')) throw new InvalidArgumentException;
		if (!include_once $subDirPath . '/' . $type . '.php') {
			throw new Exception('fail load file');
		}

		return $o = new $type(self::$css);
	}

	/**
	 * cssの改行コード等を統一する
	 *
	 * @param string $css css
	 *
	 * @return string
	 */
	static public function cssClean($css)
	{
		return rtrim(str_replace(array("\r\n", "\r"), "\n", $css));
	}

}
