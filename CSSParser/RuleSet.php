<?php

class CSSParser_RuleSet implements PEG_IParser
{
	protected $parser, $unknown;
	protected $type = 'selector';

	/**
	 * construct
	 *
	 * @param PEG_IParser $parser PEG_IParser
	 */
	function __construct()
	{
		$synMaybeSpace  = CSSPEG::synMaybeSpace();
		$unknownBlockRef = CSSPEG::synUnknownBlockRef();

		$property = CSSPEG::first( // プロパティ 「color:red」の「color」の部分
			CSSPEG::join(CSSPEG::seq(
				CSSPEG::optional(CSSPEG::choice('_', '*')), // underscore hack, asterisk hack
				CSSPEG::ruleIDENT()
			)),
			$synMaybeSpace
		);
		$value    = CSSPEG::first(CSSPEG::synExpr(), $synMaybeSpace); // 値 「color:red」の「red」の部分

		$declaration = CSSPEG::choice(
			CSSPEG::hook(
				function (Array $r) {
					return array_combine(array('property', 'value', 'isImportant'), $r);
				},
				CSSPEG::seq(
					new CSSParser_NodeCreater('property', $property),
					CSSPEG::drop(':', $synMaybeSpace),
					new CSSParser_NodeCreater('value', $value),
					CSSPEG::hook(
						function ($r) {return $r === false ? $r : true;},
						CSSPEG::optional(CSSPEG::synImportant())
					),
					CSSPEG::drop(CSSPEG::choice(';', CSSPEG::amp('}'), CSSPEG::eos()), $synMaybeSpace)
				)
			),
			new CSSParser_NodeCreater(
				'unknown',
				CSSPEG::join(
					CSSPEG::seq(
						CSSPEG::many1(
							CSSPEG::hook(
								create_function('$r', 'return $r === ";" ? CSSPEG::failure() : $r;'),
								$unknownBlockRef
							)
						),
						CSSPEG::optional(';')
					)
				)
			),
			new CSSParser_NodeCreater(
				'unknown',
				$unknownBlockRef
			)
		);

		$selectorChar = $this->selectorChar();

		// use only RuleSet Class
		$this->unknown = CSSPEG::seq(
			new CSSParser_NodeCreater(
				'unknown',
				CSSPEG::choice(
					CSSPEG::synError(CSSPEG::synErrorInvalidBlock(), CSSPEG::synErrorSemicolon()),
					CSSPEG::join(CSSPEG::many1(CSSPEG::anything()))
				)
			)
		);

		$parser = CSSPEG::hook(
			array($this, 'map'),
			CSSPEG::seq(
				new CSSParser_NodeCreater($this->type, $selectorChar),
				CSSPEG::drop('{', $synMaybeSpace),
				CSSPEG::many($declaration),
				CSSPEG::drop(CSSPEG::choice(CSSPEG::seq('}', $synMaybeSpace), CSSPEG::eos()))
			)
		);

		$this->parser = $parser;
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
		$offset = $context->tell();
		// パースを行う。
		$result = $this->parser->parse($context);
		if ($result instanceof PEG_Failure) $context->seek($offset);
		else return $result;

		$result = $this->unknown->parse($context);
		if ($result instanceof PEG_Failure) $context->seek($offset);
		else return $this->map($result);

		return CSSPEG::failure();
	}

	/**
	 * 結果を加工する
	 *
	 * @param array $arr array
	 *
	 * @return Array
	 */
	function map(Array $arr)
	{
		return array(
			'selector' => $arr[0],
			'block'    => $arr[0]->getType() === 'unknown' ? array() : $arr[1]
		);
	}

	/**
	 * セレクタに相当するパーサインスタンスを返す。
	 *
	 * @return PEG_IParser
	 */
	function selectorChar()
	{
		return CSSPEG::synSelectorsGroup();
	}

}
