<?php

class CSSParser_AtRule implements PEG_IParser
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
		$ignore = CSSPEG::synMaybeSpace();
		// 無視しないコメント
		$displayCommnet = CSSPEG::synComment();

		// PEGの左再帰対策
		// 右側にあるコメントと空白を削除する
		// for PHP <= 5.2
		$rightCommentTrim = create_function('$s', 'return preg_replace("/\s*((\/\*[^*]*\*+([^\/][^*]*\*+)*\/)*\s*)*$/", "", $s);');

		$charset = CSSPEG::seq(
			new CSSParser_NodeCreater('@charset', CSSPEG::token('@charset')),
			CSSPEG::drop($ignore),
			new CSSParser_NodeCreater('value', CSSPEG::ruleSTRING()),
			CSSPEG::drop($ignore),
			';'
		);

		// TODO
		$mediaType = CSSPEG::seq(
			CSSPEG::many(
				CSSPEG::anything(),
				CSSPEG::drop(CSSPEG::not(CSSPEG::choice($ignore, CSSPEG::char('\\', true)), ';'))
			),
			CSSPEG::choice(
				$ignore,
				CSSPEG::anything()
			),
			CSSPEG::anything()
		);
		//$mediaType = CSSPEG::seq(CSSPEG::many1(CSSPEG::choice($displayCommnet, '\;', CSSPEG::char(';', true))));
		$mediaType = new CSSParser_NodeCreater('mediaType', CSSPEG::join($mediaType));
		$mediaType = CSSPEG::choice(CSSPEG::drop(';'), CSSPEG::first($mediaType, $ignore, ';'));
		$mediaType = CSSPEG::synMediaQuery();
/*
		// url
		foreach (array('Double' => '"', 'Single' => '\'', 'No' => null) as $n => $v) {
			$n = '_importUrlOnly'.$n.'Quotes';
			${$n} = CSSPEG::seq(
				new CSSParser_NodeCreater('@import', CSSPEG::token('@import')),
				CSSPEG::drop(chr(32), $ignore),
				new CSSParser_NodeCreater(
					'value',
					CSSPEG::join(
						CSSPEG::seq(
							'url(', CSSPEG::drop($ignore), $v !== null ? $v : '',
							CSSPEG::many1(CSSPEG::anything(), CSSPEG::drop(CSSPEG::not($v !== null ? CSSPEG::seq(CSSPEG::char('\\', true), $v, $ignore) : $ignore, ')', $ignore, $mediaType))),
							$v !== null ? CSSPEG::seq(CSSPEG::anything(), CSSPEG::anything()) : CSSPEG::anything(),
							$v !== null ? $v : '', CSSPEG::drop($ignore), ')'
						)
					)
				),
				CSSPEG::drop($ignore),
				$mediaType
			);
		}

		// No Url
		foreach (array('Double' => '"', 'Single' => '\'') as $n => $v) {
			$n = '_importNoUrl'.$n.'Quotes';
			${$n} = CSSPEG::seq(
				new CSSParser_NodeCreater('@import', CSSPEG::token('@import')),
				CSSPEG::drop(chr(32), $ignore, $v !== null ? $v : ''),
				new CSSParser_NodeCreater('value', CSSPEG::join(CSSPEG::seq(CSSPEG::many1(CSSPEG::anything(), CSSPEG::drop(CSSPEG::not(($v !== null) ? CSSPEG::seq(CSSPEG::char('\\', true), $v, $ignore) : $ignore, $mediaType))), ($v !== null) ? CSSPEG::seq(CSSPEG::anything(), CSSPEG::anything()) : CSSPEG::anything()))),
				CSSPEG::drop($v !== null ? $v : '', $ignore),
				$mediaType
			);
		}

		$import = CSSPEG::choice(
			$_importUrlOnlyDoubleQuotes, $_importUrlOnlySingleQuotes, $_importUrlOnlyNoQuotes,
			$_importNoUrlDoubleQuotes, $_importNoUrlSingleQuotes
		);
*/
		$import = CSSPEG::seq(
			new CSSParser_NodeCreater('@import', CSSPEG::token('@import')),
			CSSPEG::drop($ignore),
			new CSSParser_NodeCreater(
				'value',
				CSSPEG::choice(
					CSSPEG::ruleSTRING(),
					CSSPEG::ruleURI()
				)
			),
			CSSPEG::drop($ignore),
			$mediaType,
			';'
		);

		$mediaChar = CSSPEG::hook(
			$rightCommentTrim,
			CSSPEG::join(CSSPEG::seq('@media', CSSPEG::many1(CSSPEG::choice($displayCommnet, CSSPEG::char('{;', true)))))
		);

		$checkEnd = create_function(
			'$a',
			'
			if ($a instanceof PEG_Failure) return CSSPEG::failure();
			if (mb_strpos($a["selector"]->getData(), "}") === 0) {
				return CSSPEG::failure();
			}
			return $a;
			'
		);
		$media = CSSPEG::seq(
			new CSSParser_NodeCreater('@media', $mediaChar),
			CSSPEG::drop('{', $ignore),
			CSSPEG::many(
				CSSPEG::second(
					CSSPEG::hook($checkEnd, CSSPEG::amp(new CSSParser_RuleSet(CSSPEG::anything()))),
					new CSSParser_RuleSet(CSSPEG::anything())
				)
			),
			CSSPEG::drop(CSSPEG::choice('}', CSSPEG::eos()))
		);

		$parser = CSSPEG::choice(
			$charset,
			$import,
			$media,
			new CSSParser_FontFace(CSSPEG::anything()),
			new CSSParser_Page(CSSPEG::anything()),
			new CSSParser_RuleSet(CSSPEG::anything())
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
			if ($res[0]->getType() === '@import') $result['mediaType'] = isset($res[2]) ? $res[2] : array();
		} else {
			$result = $res;
		}

		return $result;
	}
}
