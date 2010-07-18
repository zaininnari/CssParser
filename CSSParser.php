<?php

require_once 'PEG.php';
require_once dirname(__FILE__) . '/CSSParser/CSSPEG.php';

require_once dirname(__FILE__) . '/CSSParser/Locator.php';
require_once dirname(__FILE__) . '/CSSParser/Block.php';
require_once dirname(__FILE__) . '/CSSParser/NodeCreater.php';
require_once dirname(__FILE__) . '/CSSParser/Node.php';

require_once dirname(__FILE__) . '/CSSParser/RuleSet.php';
require_once dirname(__FILE__) . '/CSSParser/AtRule.php';
require_once dirname(__FILE__) . '/CSSParser/FontFace.php';
require_once dirname(__FILE__) . '/CSSParser/Page.php';
require_once dirname(__FILE__) . '/CSSParser/Error.php';

require_once dirname(__FILE__) . '/CSSParser/IValidate.php';
require_once dirname(__FILE__) . '/CSSParser/AValidate.php';

class CSSParser
{

	static protected $css;

	/**
	 * cssをパースして構造木を返す
	 *
	 * @param string $css string
	 *
	 * @return CSSParser_Node
	 */
	static function parse($css)
	{
		self::$css = self::cssClean($css);
		$result = CSSParser_Locator::it()->parser->parse(CSSPEG::context(self::$css));

		return $result;
	}

	/**
	 * cssをパースして、有効なものだけを返す。
	 *
	 * @param string $css  css
	 * @param string $type type
	 *
	 * @return CSSParser_Node
	 */
	/*static function validate($css, $type = null)
	{
		$node = self::parse($css);
		$validator = self::factory($type);
		return $validator->validate($node);
	}*/

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
		$type = $type === null ? 'CSS21' : $type;
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
