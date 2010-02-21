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

require_once dirname(__FILE__) . '/CssParser/IParser.php';
require_once dirname(__FILE__) . '/CssParser/AParser.php';

class CssParser
{
	/**
	 * 指定した種類のインスタンスを返す。
	 * 指定しなければ、css2.1のパーサインスタンスを返す。
	 *
	 * @param string $type string
	 *
	 * @return object
	 */
	static function factory($type = null)
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
}

