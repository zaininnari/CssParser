<?php

require_once dirname(__FILE__) . '/Comment.php';

class CssParser_Ignore implements PEG_IParser
{
	protected $parser;

	/**
	 * construct
	 *
	 * @param PEG_IParser $parser PEG_IParser
	 *
	 * @return unknown_type
	 */
	function __construct(PEG_IParser $parser)
	{
		$comment = new CssParser_Comment($parser);
		$space   = PEG::many1(PEG::char(chr(13).chr(10).chr(9).chr(32).chr(12)));
		$p       = PEG::many(PEG::choice($space, $comment));

		$this->parser = $p;

	}

	/**
	 * パースに失敗した場合はPEG_Failureを返すこと。
	 * 成功した場合はなんらかの値を返すこと。
	 *
	 * @param PEG_IContext $context PEG_IContext
	 *
	 * @see PEG/PEG_IParser#parse($c)
	 *
	 * @return mixed
	 */
	function parse(PEG_IContext $context)
	{
		return $this->parser->parse($context);
	}

}
