<?php

class CssParser_RuleSet implements PEG_IParser
{
	protected $parser, $selector, $ignore, $blockEmpty, $declaration;
	protected $type = 'selector';

	/**
	 * construct
	 *
	 * @param PEG_IParser $parser PEG_IParser
	 *
	 * @return unknown_type
	 */
	public function __construct(PEG_IParser $parser)
	{
		// 無視する要素 空白 コメント
		$comment      = PEG::memo(PEG::many(new CssParser_Comment($parser)));
		$this->ignore = $ignore = PEG::memo(new CssParser_Ignore($parser));
		// 無視しないコメント
		$displayCommnet = PEG::seq('/*', PEG::many(PEG::tail(PEG::not('*/'), PEG::anything())), '*/');

		// エラートークン。「{}」のネストに対応
		$unknownSeleBlockRef = PEG::choice($displayCommnet, PEG::ref($unknownSeleBlock), PEG::anything());
		$unknownSeleBlock =  PEG::seq('{', PEG::many($unknownSeleBlockRef), '}');
		$unknownBlockRef = PEG::choice($displayCommnet, PEG::ref($unknownBlock), PEG::char('}', true));
		$unknownBlock =  PEG::seq('{', PEG::many($unknownBlockRef), '}');

		// PEGの左再帰対策
		// 右側にあるコメントと空白を削除する
		// for PHP <= 5.2
		$rightCommentTrim = create_function('$s', 'return preg_replace("/\s*((\/\*[^*]*\*+([^\/][^*]*\*+)*\/)*\s*)*$/", "", $s);');

		$property = PEG::many1(PEG::choice($displayCommnet, PEG::char('{:', true))); // プロパティ 「color:red」の「color」の部分
		$value    = PEG::many1(PEG::choice($displayCommnet, PEG::char(';}', true))); // 値 「color:red」の「red」の部分
		$this->declaration = PEG::choice(
			PEG::seq(
				new CssParser_NodeCreater('property', PEG::hook($rightCommentTrim, PEG::join($property))),
				PEG::drop(':', $ignore),
				new CssParser_NodeCreater('value', PEG::hook($rightCommentTrim, PEG::join($value)))
			),
			PEG::seq(
				new CssParser_NodeCreater('unknown', PEG::join(PEG::many1($unknownBlockRef)))
			)
		);

		$selectorChar = PEG::hook(
			$rightCommentTrim,
			PEG::join(
				PEG::first(
					PEG::many1(
						PEG::choice(
							$displayCommnet,
							PEG::char('{', true)
						)
					),
					PEG::drop('{')
				)
			)
		);
		$this->parser = PEG::choice(
			new CssParser_NodeCreater($this->type, $selectorChar),
			new CssParser_NodeCreater('unknown', PEG::join(PEG::many1($unknownSeleBlockRef)))
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
		// パースを行う。
		$type = $this->parser->parse($context);

		$result = array(
			$this->type => $type,
			'block'     => array()
		);

		if ($context->eos() === true || $type->getType() === 'unknown') return $result;
		$char = $context->readElement();      // チェック用に1つ取得する
		$context->seek($context->tell() - 1); // チェック用に動かしたので、1つ戻す

		// 宣言ブロック内を読み込む
		while ($context->eos() !== true && $char !== '}') {
			$this->ignore->parse($context); // コメントがある場合、進める
			if ($context->eos() === true) return $result;
			$char = $context->readElement(); // 次の文字を取得する
			if ($char === '}') return $result;
			$context->seek($context->tell() - 1); // 取得用に動かしたので、1つ戻す

			$declaration = $this->declaration->parse($context);
			if ($declaration[0] instanceof CssParser_Node && $declaration[0]->getType() === 'unknown') {
				$result['block'] = $declaration;
			} else {
				list($property, $value) = $declaration;

				// !important の 削除
				$pattern = '/\s*!\s*important\s*/i';
				if (preg_match($pattern, $value->getData())) {
					$value = new CssParser_Node(
						$value->getType(),
						preg_replace($pattern, '', $value->getData()),
						$value->getOffset()
					);
					$isImportant = true;
				}

				$result['block'][] = array(
					'property'    => $property,
					'value'       => $value,
					'isImportant' => isset($isImportant) ? $isImportant : false
				);
				if ($context->eos() === true) return $result;
				$char = $context->readElement();      // 次の文字を取得する
				$context->seek($context->tell() - 1); // 取得用に動かしたので、1つ戻す
			}

			if ($context->eos() === true) return $result;
			$char = $context->readElement();      // 次の文字を取得する
			$context->seek($context->tell() - 1); // 取得用に動かしたので、1つ戻す

			while ($context->eos() !== true && $char === ';') {
				$char = $context->readElement(); // 宣言（declaration）が複数あればさらに進める
				$this->ignore->parse($context);
				if ($context->eos() === true) return $result;
				$char = $context->readElement(); // 次の文字を取得する
				$context->seek($context->tell() - 1); // 取得用に動かしたので、1つ戻す
			}

		}
		$this->ignore->parse($context);
		if ($context->eos() === true) return $result;
		$char = $context->readElement(); // 次の文字を取得する
		$context->seek($context->tell() - 1); // 取得用に動かしたので、1つ戻す

		if ($char === '}') $char = $context->readElement();

		return $result;
	}

}
