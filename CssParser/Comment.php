<?php

class CssParser_Comment implements PEG_IParser
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
		$p = PEG::seq(
			PEG::drop('/*'),
			PEG::many(PEG::tail(PEG::not('*/'), PEG::anything())),
			PEG::drop('*/')
		);

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
