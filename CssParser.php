<?php


require_once 'PEG.php';

require_once dirname(__FILE__) . '/CssParser/Locator.php';
require_once dirname(__FILE__) . '/CssParser/Comment.php';
require_once dirname(__FILE__) . '/CssParser/Ignore.php';
require_once dirname(__FILE__) . '/CssParser/Block.php';
require_once dirname(__FILE__) . '/CssParser/NodeCreater.php';
require_once dirname(__FILE__) . '/CssParser/Node.php';
require_once dirname(__FILE__) . '/CssParser/RuleSet.php';
require_once dirname(__FILE__) . '/CssParser/AtRule.php';
require_once dirname(__FILE__) . '/CssParser/AtBlock.php';

require_once dirname(__FILE__) . '/CssParser/IValidate.php';
require_once dirname(__FILE__) . '/CssParser/AValidate.php';

require_once dirname(__FILE__) . '/CssParser/IParser.php';
require_once dirname(__FILE__) . '/CssParser/AParser.php';

class CssParser
{

	/**
	 * cssをパースして構造木を返す
	 *
	 * @param string $css string
	 *
	 * @return CssParser_Node
	 */
	static function parse($css)
	{
		$css = self::cssClean($css);
		$result = CssParser_Locator::it()->parser->parse(PEG::context($css));

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
		$type = ($type === null) ? 'css21' : $type;
		$dirPath = dirname(__FILE__);
		$subDirName = __CLASS__;
		$subDirPath = $dirPath . '/' . $subDirName;
		if(!file_exists($subDirPath . '/' . $type . '.php')) throw new InvalidArgumentException;
		if (!include_once $subDirPath . '/' . $type . '.php') {
			throw new Exception('fail load file');
		}

		return $o = new $type;
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
		$css = str_replace(array("\r\n", "\r"), "\n", $css); // 改行コードの統一
		$css = rtrim($css);                                  // 終点から空白を削除
		return $css;
	}

}
