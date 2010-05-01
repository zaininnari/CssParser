<?php

require_once dirname(__FILE__) . '/Comment.php';
require_once dirname(__FILE__) . '/Ignore.php';

class MediaQuery implements PEG_IParser
{
	protected $parser;

	/**
	 * construct
	 *
	 * @param PEG_IParser $parser PEG_IParser
	 */
	public function __construct(PEG_IParser $parser)
	{
		$comment = new CSSParser_Comment($parser);
		$_whiteSpace   = PEG::choice(chr(13), chr(10), chr(9), chr(32), chr(12));
		$_space = PEG::memo(PEG::choice($_whiteSpace, $comment));
		$space = PEG::many1($_space);
		$maybeSpace = PEG::many($_space);
		$errorToken = CSSPEG::choice($comment, '\,',CSSPEG::char(',', true));

		$restrictor = PEG::optional(
			new CSSParser_NodeCreater(
				'restrictor',
				PEG::join(
					PEG::choice(
						PEG::seq(PEG::char('nN'), PEG::char('oO'), PEG::char('tT')),
						PEG::seq(PEG::char('oO'), PEG::char('nN'), PEG::char('lL'), PEG::char('yY'))
					)
				)
			)
		);

		$maybeMediaValue = CSSPEG::optional(
			CSSPEG::third(
				':',
				$maybeSpace,
				new CSSParser_NodeCreater('value', CSSPEG::synExpr()),
				$maybeSpace
			)
		);
		$exp = new CSSParser_NodeCreater(
				'expression',
				CSSPEG::seq(
					CSSPEG::drop('(', $maybeSpace),
					CSSPEG::synMedia_feature(),
					CSSPEG::drop($maybeSpace),
					$maybeMediaValue,
					CSSPEG::drop(')', $maybeSpace)
				),
				array('property', 'value')
			);
		$mediaType = new CSSParser_NodeCreater(
			'mediaType',
			CSSPEG::ruleIDENT()
		);

		$andQuery = 	CSSPEG::many(
			CSSPEG::choice(
				CSSPEG::third(
					CSSPEG::ruleMEDIA_AND(),
					$space,
					$exp,
					$maybeSpace
				),
				new CSSParser_NodeCreater(
					'error',
					CSSPEG::andalso(
						CSSPEG::not(CSSPEG::eos()),
						CSSPEG::join(CSSPEG::many1($errorToken))
					)
				)
			)
		);

		$mediaQuery = CSSPEG::choice(
			new CSSParser_NodeCreater(
				'mediaQuery',
				CSSPEG::seq(
					$restrictor,
					CSSPEG::drop($maybeSpace),
					$mediaType,
					CSSPEG::drop($maybeSpace),
					$andQuery
				),
				array('restrictor', 'mediaType', 'andQuery')
			),
			new CSSParser_NodeCreater(
				'error',
				CSSPEG::join(CSSPEG::many($errorToken))
			)
		);

		$this->parser = $mediaQuery;
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
		return $this->parser->parse($context);
	}

}
