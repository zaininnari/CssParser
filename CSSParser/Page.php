<?php

class CSSParser_Page extends CSSParser_RuleSet
{
	protected $type = '@page';

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
	public function parse(PEG_IContext $context)
	{
		return $this->parser->parse($context);
	}

	/**
	 * セレクタに相当するパーサインスタンスを返す。
	 *
	 * @return PEG_IParser
	 */
	function selectorChar()
	{
		return CSSPEG::join(CSSPEG::seq('@page', CSSPEG::many1(CSSPEG::choice($this->displayCommnet, CSSPEG::char('{;', true)))));
	}
}