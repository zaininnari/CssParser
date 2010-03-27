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
		// 無視しないコメント
		$displayCommnet = PEG::seq('/*', PEG::many(PEG::tail(PEG::not('*/'), PEG::anything())), '*/');

		// PEGの左再帰対策
		// 右側にあるコメントと空白を削除する
		// for PHP <= 5.2
		$rightCommentTrim = create_function('$s', 'return preg_replace("/\s*((\/\*[^*]*\*+([^\/][^*]*\*+)*\/)*\s*)*$/", "", $s);');

		$_charsetMain = PEG::seq(PEG::many1(PEG::anything(), PEG::drop(PEG::not(PEG::char('\\', true), '"'))), PEG::anything(), PEG::anything());
		$charset = PEG::seq(
			new CssParser_NodeCreater('@charset', PEG::token('@charset')),
			PEG::drop(' "'),
			new CssParser_NodeCreater('value', PEG::join($_charsetMain)),
			PEG::drop('";')
		);

		$mediaType = PEG::seq(PEG::many(PEG::anything(), PEG::drop(PEG::not(PEG::choice($ignore, PEG::char('\\', true)), ';'))), PEG::choice($ignore, PEG::anything()), PEG::anything());
		$mediaType = new CssParser_NodeCreater('mediaType', PEG::join($mediaType));
		$mediaType = PEG::choice(PEG::drop(';'), PEG::first($mediaType, $ignore, ';'));

		// url
		foreach (array('Double' => '"', 'Single' => '\'', 'No' => null) as $n => $v) {
			$n = '_importUrlOnly'.$n.'Quotes';
			${$n} = PEG::seq(
				new CssParser_NodeCreater('@import', PEG::token('@import')),
				PEG::drop(chr(32), $ignore),
				new CssParser_NodeCreater(
					'value',
					PEG::join(
						PEG::seq(
							'url(', PEG::drop($ignore), $v !== null ? $v : '',
							PEG::many1(PEG::anything(), PEG::drop(PEG::not($v !== null ? PEG::seq(PEG::char('\\', true), $v, $ignore) : $ignore, ')', $ignore, $mediaType))),
							$v !== null ? PEG::seq(PEG::anything(), PEG::anything()) : PEG::anything(),
							$v !== null ? $v : '', PEG::drop($ignore), ')'
						)
					)
				),
				PEG::drop($ignore),
				$mediaType
			);
		}

		// No Url
		foreach (array('Double' => '"', 'Single' => '\'') as $n => $v) {
			$n = '_importNoUrl'.$n.'Quotes';
			${$n} = PEG::seq(
				new CssParser_NodeCreater('@import', PEG::token('@import')),
				PEG::drop(chr(32), $ignore, $v !== null ? $v : ''),
				new CssParser_NodeCreater('value', PEG::join(PEG::seq(PEG::many1(PEG::anything(), PEG::drop(PEG::not(($v !== null) ? PEG::seq(PEG::char('\\', true), $v, $ignore) : $ignore, $mediaType))), ($v !== null) ? PEG::seq(PEG::anything(), PEG::anything()) : PEG::anything()))),
				PEG::drop($v !== null ? $v : '', $ignore),
				$mediaType
			);
		}

		$import = PEG::choice(
			$_importUrlOnlyDoubleQuotes, $_importUrlOnlySingleQuotes, $_importUrlOnlyNoQuotes,
			$_importNoUrlDoubleQuotes, $_importNoUrlSingleQuotes
		);

		$mediaChar = PEG::hook(
			$rightCommentTrim,
			PEG::join(PEG::seq('@media', PEG::many1(PEG::choice($displayCommnet, PEG::char('{;', true)))))
		);

		$checkEnd = create_function(
			'$a',
			'
			if ($a instanceof PEG_Failure) return PEG::failure();
			if (mb_strpos($a["selector"]->getData(), "}") === 0) {
				return PEG::failure();
			}
			return $a;
			'
		);
		$media = PEG::seq(
			new CssParser_NodeCreater('@media', $mediaChar),
			PEG::drop('{', $ignore),
			PEG::many(
				PEG::second(
					PEG::hook($checkEnd, PEG::amp(new CssParser_RuleSet(PEG::anything()))),
					new CssParser_RuleSet(PEG::anything())
				)
			),
			PEG::drop(PEG::choice('}', PEG::eos()))
		);

		$parser = PEG::choice(
			$charset,
			$import,
			$media,
			new CssParser_FontFace(PEG::anything()),
			new CssParser_Page(PEG::anything()),
			new CssParser_RuleSet(PEG::anything())
		);

		$this->parser = PEG::memo($parser);
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
		if ($res instanceof CssParser_Node && $res->getType() === 'unknown') $res = array($res);
		if (isset($res[0])
			&& $res[0] instanceof CssParser_Node
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
