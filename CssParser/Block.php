<?php


class CssParser_Block implements PEG_IParser
{
	protected $ruleSet, $ignore, $firstCharTable;

	/**
	 * construct
	 *
	 * @param CssParser_Locator $locator CssParser_Locator
	 *
	 * @return unknown_type
	 */
	public function __construct(CssParser_Locator $locator)
	{
		$this->ruleSet = $locator->ruleSet;
		$this->ignore = $locator->ignore;
		$this->firstCharTable = array(
			'@' => $locator->atRule
		);
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
	public function parse(PEG_IContext $context)
	{
		$this->ignore->parse($context);             // 空白&コメントを無視する
		if ($context->eos()) return PEG::failure(); // 読むべきものがなくなったら終了
		$char = $context->readElement();            // チェック用に1つ取得する
		$context->seek($context->tell() - 1);       // チェック用に動かしたので、1つ戻す

		if ($char === '@') { // @規則
			return $this->firstCharTable[$char]->parse($context);
		}

		return $this->ruleSet->parse($context);
	}
}
