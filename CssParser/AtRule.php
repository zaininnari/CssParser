<?php

class CssParser_AtRule implements PEG_IParser
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
		$ignore = new CssParser_Ignore(PEG::anything());

		$_charsetMain = PEG::seq(PEG::many1(PEG::anything(), PEG::drop(PEG::not(PEG::char('\\', true), '"'))), PEG::anything(), PEG::anything());
		$charset = PEG::seq(PEG::drop('@charset "'), new CssParser_NodeCreater('charset', PEG::join($_charsetMain)), PEG::drop('";'));

		$mediaType = PEG::seq(PEG::many(PEG::anything(), PEG::drop(PEG::not(PEG::choice($ignore, PEG::char('\\', true)), ';'))), PEG::choice($ignore, PEG::anything()), PEG::anything());
		$mediaType = new CssParser_NodeCreater('mediaType', PEG::join($mediaType));
		$mediaType = PEG::choice(PEG::drop(';'), PEG::first($mediaType, $ignore, ';'));

		// url
		foreach (array('Double' => '"', 'Single' => '\'', 'No' => null) as $n => $v) {
			$n = '_importUrlOnly'.$n.'Quotes';
			${$n} = PEG::seq(PEG::drop('@import' . chr(32), $ignore, 'url(', $ignore, ($v !== null) ? $v : ''), new CssParser_NodeCreater('import', PEG::join(PEG::seq(PEG::many1(PEG::anything(), PEG::drop(PEG::not($v !== null ? PEG::seq(PEG::char('\\', true), $v, $ignore) : $ignore, ')', $ignore, ';'))), $v !== null ? PEG::seq(PEG::anything(), PEG::anything()) : PEG::anything()))), PEG::drop($v !== null ? $v : '', $ignore, ')', $ignore, ';'));
		}

		// No Url
		foreach (array('Double' => '"', 'Single' => '\'') as $n => $v) {
			$n = '_importNoUrl'.$n.'Quotes';
			${$n} = PEG::seq(PEG::drop('@import' . chr(32), $ignore, $v !== null ? $v : ''), new CssParser_NodeCreater('import', PEG::join(PEG::seq(PEG::many1(PEG::anything(), PEG::drop(PEG::not(($v !== null) ? PEG::seq(PEG::char('\\', true), $v, $ignore) : $ignore, $mediaType))), ($v !== null) ? PEG::seq(PEG::anything(), PEG::anything()) : PEG::anything()))), PEG::drop(($v !== null) ? $v : '', $ignore), $mediaType);
		}

		$import = PEG::choice(
			$_importUrlOnlyDoubleQuotes, $_importUrlOnlySingleQuotes, $_importUrlOnlyNoQuotes,
			$_importNoUrlDoubleQuotes, $_importNoUrlSingleQuotes
		);

		$_unknown = PEG::seq('@', PEG::many(PEG::anything(), PEG::drop(PEG::not(PEG::char('\\', true), ';'))), PEG::anything(), PEG::anything(), ';');
		$unknownSemicolon = PEG::seq(new CssParser_NodeCreater('unknown', PEG::join($_unknown)));

		$parser = PEG::choice(
			$charset,
			$import,
			$unknownSemicolon,
			new CssParser_RuleSet(PEG::anything())
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
	function parse(PEG_IContext $context)
	{
		return $this->parser->parse($context);
	}
}
