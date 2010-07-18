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
		//return CSSPEG::synAtRulePage();
		return CSSPEG::hook(
			function ($r) {
				$result = array_combine(array('value', 'name', 'pseudo'), $r);
				if ($result['pseudo'] !== false) $result['pseudo'] = $r[2][1];
				return $result;
			},
			CSSPEG::seq(
				'@page',
				CSSPEG::drop(CSSPEG::synMaybeSpace()),
				CSSPEG::optional(CSSPEG::ruleIDENT()),
				CSSPEG::optional(':', CSSPEG::choice('left', 'right', 'first')),
				CSSPEG::drop(CSSPEG::synMaybeSpace())
			)
		);
	}
}