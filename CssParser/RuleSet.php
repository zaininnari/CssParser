<?php

class CssParser_RuleSet implements PEG_IParser
{
	protected $parser,$unknown;
	protected $type = 'selector';

	/**
	 * construct
	 *
	 * @param PEG_IParser $parser PEG_IParser
	 *
	 * @return unknown_type
	 */
	function __construct(PEG_IParser $parser)
	{
		$ignore  = PEG::memo(new CssParser_Ignore($parser));
		// 無視しないコメント
		$displayCommnet = PEG::seq('/*', PEG::many(PEG::tail(PEG::not('*/'), PEG::anything())), '*/');

		// エラートークン。「{}」のネストに対応
		$unknownSeleBlockRef = PEG::choice($displayCommnet, PEG::ref($unknownSeleBlock));
		$unknownSeleBlock =  PEG::seq('{', PEG::many($unknownSeleBlockRef), '}');
		$unknownBlockRef = PEG::choice($displayCommnet, PEG::ref($unknownBlock), PEG::hook(create_function('$r', 'return $r === false ? PEG::failure() : $r;'), PEG::char('}', true)));
		$unknownBlock =  PEG::seq('{', PEG::many($unknownBlockRef), '}');
		$unknownSemicolon = PEG::seq(
			PEG::many1(PEG::choice($displayCommnet, PEG::char(';', true))),
			PEG::choice(';', PEG::eos())
		);

		// PEGの左再帰対策
		// 右側にあるコメントと空白を削除する
		// for PHP <= 5.2
		$rightCommentTrim = create_function('$s', 'return preg_replace("/\s*((\/\*[^*]*\*+([^\/][^*]*\*+)*\/)*\s*)*$/", "", $s);');

		$property = PEG::many1(PEG::choice($displayCommnet, PEG::char('{}:', true))); // プロパティ 「color:red」の「color」の部分
		$value    = PEG::many1(PEG::choice($displayCommnet, PEG::char('{;}', true))); // 値 「color:red」の「red」の部分

		$declarationArr = create_function(
			'Array $a',
			'
			list($property, $value) = $a;
			// !important の 削除
			$pattern = "/\s*!\s*important\s*/i";
			if (preg_match($pattern, $value->getData())) {
				$value = new CssParser_Node(
					$value->getType(),
					preg_replace($pattern, "", $value->getData()),
					$value->getOffset()
				);
				$isImportant = true;
			}
			return array("property" => $property, "value" => $value, "isImportant" => isset($isImportant) ? $isImportant : false);'
		);
		$declaration = PEG::choice(
			PEG::hook(
				$declarationArr,
				PEG::seq(
					new CssParser_NodeCreater('property', PEG::hook($rightCommentTrim, PEG::join($property))),
					PEG::drop(':', $ignore),
					new CssParser_NodeCreater('value', PEG::hook($rightCommentTrim, PEG::join($value))),
					PEG::drop(PEG::choice(';', PEG::amp('}'), PEG::eos()), $ignore)
				)
			),
			new CssParser_NodeCreater('unknown', PEG::join(PEG::many1($unknownBlockRef)))
		);

		$selectorChar = PEG::hook(
			$rightCommentTrim,
			PEG::join(PEG::many1(PEG::choice($displayCommnet, PEG::char('{;', true))))
		);
		$selectorChar = new CssParser_Selector(PEG::anything());


		$this->unknown = PEG::seq(new CssParser_NodeCreater('unknown', PEG::join(PEG::choice(PEG::many1($unknownSeleBlockRef), $unknownSemicolon))));
		$parser = PEG::hook(array($this,'map'),
			PEG::seq(
				new CssParser_NodeCreater($this->type, $selectorChar),
				PEG::drop('{', $ignore),
				PEG::many($declaration),
				PEG::drop(PEG::choice(PEG::seq('}', $ignore), PEG::eos()))
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

		return PEG::failure();
	}

	function map(Array $arr)
	{
		return array(
			'selector' => $arr[0],
			'block'    => $arr[0]->getType() === 'unknown' ? array() : $arr[1]
		);
	}

}
