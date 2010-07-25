<?php

class CSSParser_AtRule implements PEG_IParser
{
	protected $parser;

	/**
	 * construct
	 *
	 * @param PEG_IParser $parser PEG_IParser
	 */
	function __construct()
	{
		$synMaybeSpace = CSSPEG::synMaybeSpace();
		// 無視しないコメント
		$displayCommnet = CSSPEG::synComment();

		$charsetSuccess = CSSPEG::first(
			CSSPEG::drop($synMaybeSpace),
			new CSSParser_NodeCreater('value', CSSPEG::ruleSTRING()),
			CSSPEG::drop($synMaybeSpace),
			';'
		);

		$charsetError = new CSSParser_NodeCreater(
			'unknown',
			CSSPEG::synError(CSSPEG::synErrorInvalidBlock(), CSSPEG::synErrorSemicolon())
		);
		$charset = CSSPEG::seq(
			new CSSParser_NodeCreater('@charset', CSSPEG::token('@charset')),
			CSSPEG::choice($charsetSuccess, $charsetError)
		);

		$mediaType = CSSPEG::synMediaQuery();
		$importSuccess = CSSPEG::seq(
			CSSPEG::drop($synMaybeSpace),
			new CSSParser_NodeCreater(
				'value',
				CSSPEG::choice(
					CSSPEG::hook(function ($r) {return array($r, CSSPEG::V_STRING);}, CSSPEG::ruleSTRING()),
					CSSPEG::hook(function ($r) {return array($r[1], CSSPEG::V_URI);}, CSSPEG::ruleURI())
				),
				array('value', 'unit')
			),
			CSSPEG::drop($synMaybeSpace),
			CSSPEG::synMediaQuery(),
			';'
		);
		$importError1 = CSSPEG::seq(
			CSSPEG::drop($synMaybeSpace),
			new CSSParser_NodeCreater(
				'value',
				CSSPEG::choice(
					CSSPEG::hook(function ($r) {return array($r, CSSPEG::V_STRING);}, CSSPEG::ruleSTRING()),
					CSSPEG::hook(function ($r) {return array($r[1], CSSPEG::V_URI);}, CSSPEG::ruleURI())
				),
				array('value', 'unit')
			),
			CSSPEG::drop($synMaybeSpace),
			CSSPEG::synMediaQuery()
		);
		$importError2 = CSSPEG::seq(
			new CSSParser_NodeCreater(
				'unknown',
				CSSPEG::synError(CSSPEG::synErrorSemicolon(), CSSPEG::synErrorInvalidBlock())
			)
		);

		$import = CSSPEG::hook( // array flatten
			create_function('$r', '$tmp = array_pop($r);return array_merge($r, $tmp);'),
			CSSPEG::seq(
				new CSSParser_NodeCreater('@import', CSSPEG::token('@import')),
				CSSPEG::choice($importSuccess, $importError1, $importError2)
			)
		);

		$mediaChar = CSSPEG::join(CSSPEG::seq('@media', CSSPEG::many1(CSSPEG::choice($displayCommnet, CSSPEG::char('{;', true)))));
		$media = CSSPEG::seq(
			new CSSParser_NodeCreater('@media', $mediaChar),
			CSSPEG::drop('{', $synMaybeSpace),
			CSSPEG::choice(
				// for nothing rules
				CSSPEG::hook(function ($r) {return array();}, CSSPEG::amp('}')),
				// for only one rule
				CSSPEG::seq(
					CSSPEG::first(new CSSParser_RuleSet(), CSSPEG::amp('}'))
				),
				// for rules
				CSSPEG::seq(
					CSSPEG::first(CSSPEG::many1(CSSPEG::first(new CSSParser_RuleSet(), CSSPEG::not('}')))),
					CSSPEG::first(new CSSParser_RuleSet(), CSSPEG::amp('}'))
				)
			),
			CSSPEG::drop($synMaybeSpace),
			CSSPEG::drop(CSSPEG::choice('}', CSSPEG::eos()))
		);

		$parser = CSSPEG::choice(
			$charset,
			$import,
			$media,
			new CSSParser_FontFace(),
			new CSSParser_Page(),
			new CSSParser_RuleSet()
		);

		$this->parser = CSSPEG::memo($parser);
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
		$result = array();
		$res = $this->parser->parse($context);
		if ($res instanceof CSSParser_Node && $res->getType() === 'unknown') $res = array($res);
		if (isset($res[0])
			&& $res[0] instanceof CSSParser_Node
			&& ($res[0]->getType() === '@import' || $res[0]->getType() === '@charset' || $res[0]->getType() === '@media')
		) {
			$result = array('selector' => $res[0], 'value' => $res[1]);
			if ($res[0]->getType() === '@import') {
				if (isset($res[3]) && $res[3] !== ';') {
					$res[2] = new CSSParser_Node($res[2]->getType(), $res[2]->getData() . $res[3], $res[2]->getOffset());
				}
				$result['mediaType'] = isset($res[2]) ? $res[2] : array();
			}
		} else {
			$result = $res;
		}

		return $result;
	}
}
