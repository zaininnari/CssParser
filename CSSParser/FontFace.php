<?php

class CSSParser_FontFace extends CSSParser_RuleSet
{
	protected $type = '@font-face';

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
		return CSSPEG::hook(
			function ($r) {
				$result = array_combine(array('value'), $r);
				return $result;
			},
			CSSPEG::seq(
				'@font-face',
				CSSPEG::drop(CSSPEG::synMaybeSpace())
			)
		);
		return CSSPEG::join(CSSPEG::seq('@font-face', CSSPEG::synMaybeSpace()));
	}
}