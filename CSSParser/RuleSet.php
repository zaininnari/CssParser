<?php

class CSSParser_RuleSet implements PEG_IParser
{
	protected $parser, $unknown, $displayCommnet;
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
		$ignore  = CSSPEG::synMaybeSpace();
		// 無視しないコメント
		$this->displayCommnet = $displayCommnet = CSSPEG::synComment();

		// エラートークン。「{}」のネストに対応
		$unknownSeleBlockRef = CSSPEG::choice($displayCommnet, CSSPEG::ref($unknownSeleBlock));
		$unknownSeleBlock =  CSSPEG::seq('{', CSSPEG::many($unknownSeleBlockRef), '}');

		$unknownBlockRef = CSSPEG::synUnknownBlockRef();
		$unknownSemicolon = CSSPEG::seq(
			CSSPEG::many1(CSSPEG::choice($displayCommnet, '\;', CSSPEG::char(';', true))),
			CSSPEG::choice(';', CSSPEG::eos())
		);

		// PEGの左再帰対策
		// 右側にあるコメントと空白を削除する
		// for PHP <= 5.2
		$rightCommentTrim = create_function('$s', 'return preg_replace("/\s*((\/\*[^*]*\*+([^\/][^*]*\*+)*\/)*\s*)*$/", "", $s);');

		$property = CSSPEG::many1(CSSPEG::choice($displayCommnet, CSSPEG::choice('\{', '\}', '\:'), CSSPEG::char('{}:', true))); // プロパティ 「color:red」の「color」の部分
		$value    = CSSPEG::many1(CSSPEG::choice($displayCommnet, '\}', CSSPEG::choice('\{', '\;', '\}'), CSSPEG::char('{;}', true))); // 値 「color:red」の「red」の部分

		$declarationArr = create_function(
			'Array $a',
			'
			list($property, $value) = $a;
			// !important の 削除
			$pattern = "/\s*!\s*important\s*/i";
			if (preg_match($pattern, $value->getData())) {
				$value = new CSSParser_Node(
					$value->getType(),
					preg_replace($pattern, "", $value->getData()),
					$value->getOffset()
				);
				$isImportant = true;
			}
			return array("property" => $property, "value" => $value, "isImportant" => isset($isImportant) ? $isImportant : false);'
		);
		$declaration = CSSPEG::choice(
			CSSPEG::hook(
				$declarationArr,
				CSSPEG::seq(
					new CSSParser_NodeCreater('property', CSSPEG::hook($rightCommentTrim, CSSPEG::join($property))),
					CSSPEG::drop(':', $ignore),
					new CSSParser_NodeCreater('value', CSSPEG::hook($rightCommentTrim, CSSPEG::join($value))),
					CSSPEG::drop(CSSPEG::choice(';', CSSPEG::amp('}'), CSSPEG::eos()), $ignore)
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

		$selectorChar = CSSPEG::hook(
			$rightCommentTrim,
			$this->selectorChar()
		);

		$this->unknown = CSSPEG::seq(
			new CSSParser_NodeCreater(
				'unknown',
				CSSPEG::join(CSSPEG::choice(CSSPEG::many1($unknownSeleBlockRef), $unknownSemicolon))
			)
		);
		$parser = CSSPEG::hook(
			array($this, 'map'),
			CSSPEG::seq(
				new CSSParser_NodeCreater($this->type, $selectorChar),
				CSSPEG::drop('{', $ignore),
				CSSPEG::many($declaration),
				CSSPEG::drop(CSSPEG::choice(CSSPEG::seq('}', $ignore), CSSPEG::eos()))
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
		return CSSPEG::join(CSSPEG::many1(CSSPEG::choice($this->displayCommnet, CSSPEG::char('{;', true))));
	}

}
