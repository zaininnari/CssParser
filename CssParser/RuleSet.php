<?php

class CssParser_RuleSet implements PEG_IParser
{
	protected $parser, $selector, $ignore, $blockEmpty, $declaration, $unknown;
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
		$comment = PEG::memo(PEG::many(new CssParser_Comment($parser)));
		$this->ignore = $ignore = PEG::memo(new CssParser_Ignore($parser));

		// セレクタが有効として値を返すべきだけど、宣言が適当でない要素の検出
		$this->blockEmpty = array(
			// 宣言が成立していない場合(プロパティや値が適当でない場合)、その宣言は無視される。
			// プロパティが適当でない場合 -> そのプロパティと対応する値が無視される。
			// 値が適当でない場合        -> その値と対応するプロパティが無視される。

			// ブロックが成立している場合   (宣言が成立していない。文字はあるが、「:」がない)
			PEG::first('{', $ignore, PEG::many1(PEG::char(':}', true)), $ignore, '}', $ignore),
			// ブロックが成立している場合   (宣言が成立していない。プロパティがない、「:」がある、値がない)
			PEG::first('{', $ignore, ':', $ignore, '}', $ignore),
			// ブロックが成立している場合   (宣言が成立していない。プロパティがない、「:」がある、値がある)
			PEG::first('{', $ignore, ':', $ignore, PEG::many1(PEG::char('}', true)), '}', $ignore),
			// ブロックが成立している場合   (宣言が成立していない。プロパティがある、「:」がある、値がない)
			PEG::first('{', $ignore, PEG::many1(PEG::char(':', true)), $ignore, ':', $ignore, '}', $ignore),
		);

		// PEGの左再帰対策
		// 右側にあるコメントと空白を削除する
		// for PHP <= 5.2
		$rightCommentTrim = create_function('$s', 'return preg_replace("/\s*((\/\*[^*]*\*+([^\/][^*]*\*+)*\/)*\s*)*$/", "", $s);');

		// プロパティ 「color:red」の「color」の部分
		$property = PEG::first(
			PEG::many1(PEG::char(':;', true)),
			$ignore
		);

		// 値 「color:red」の「red」の部分
		$value = PEG::first(
			PEG::many1(PEG::char(';}', true)),
			$ignore
		);

		$this->declaration = PEG::seq(
			new CssParser_NodeCreater('property', PEG::hook($rightCommentTrim, PEG::join($property))),
			PEG::drop(':', $ignore),
			new CssParser_NodeCreater('value', PEG::hook($rightCommentTrim, PEG::join($value)))
		);

		$unknown = PEG::join(
			PEG::seq(
				PEG::many(PEG::anything(), PEG::drop(PEG::not(PEG::choice($ignore, PEG::char(';}', true)), $ignore, PEG::choice(';', '}')))),
				PEG::char(';}', true)
			)
		);

		// エラートークン
		$this->unknown = new CssParser_NodeCreater('unknown', $unknown);

		// セレクタの内部にあるコメント。コメント内部の「{」を検出させない
		$displayCommnet = PEG::seq('/*', PEG::many(PEG::tail(PEG::not('*/'), PEG::anything())), '*/');

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
					$ignore
				)
			)
		);
		$this->parser = new CssParser_NodeCreater($this->type, $selectorChar);
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

		// 以下の場合は、失敗を返す。
		// ・読むべきものがなくなったとき
		// ・パースに失敗したとき
		// TODO
		if ($type instanceof PEG_Failure || $context->eos() === true) return PEG::failure();

		$result = array(
			$this->type => $type,
			'block'    => array(),
			'error'    => array()
		);

		// 宣言が空の場合終了
		foreach ($this->blockEmpty as $blockEmpty) {
			if (!PEG::amp($blockEmpty)->parse($context) instanceof PEG_Failure) {
				$result['error'][] = new CssParser_Node('error', 'empty block', $context->tell());
				$blockEmpty->parse($context); // 進める
				return $result;
			}
		}

		if ($context->eos() === true) return $result;
		$char = $context->readElement();            // チェック用に1つ取得する
		$context->seek($context->tell() - 1);       // チェック用に動かしたので、1つ戻す

		// 宣言ブロック内を読み込む
		while ($context->eos() !== true && $char !== '}') {
			if ($char !== ';') {

				// 宣言ブロックの開始「{」
				if ($char === '{') $context->seek($context->tell() + 1);
				$this->ignore->parse($context); // コメントがある場合、進める
				if ($context->eos() === true) return $result;
				$declaration = PEG::amp($this->declaration)->parse($context); //先読みする
				if ($declaration instanceof PEG_Failure) { // 失敗した場合は、unknownとして扱う。
					if (PEG::amp($this->unknown)->parse($context) instanceof PEG_Failure ) {
						if ($char === '{') $char = $context->readElement();
					} else {
						$result['block'][] = $this->unknown->parse($context);
					}
				} else {
					list($property, $value) = $this->declaration->parse($context);

					$pattern = '/\s*!\s*important\s*/i';
					// !important
					if (!$declaration instanceof PEG_Failure && preg_match($pattern, $value->getData())) {
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
					$char = $context->readElement(); // 次の文字を取得する
					$context->seek($context->tell() - 1); // 取得用に動かしたので、1つ戻す
				}
			}

			$char = $context->readElement(); // 次の文字を取得する
			$context->seek($context->tell() - 1); // 取得用に動かしたので、1つ戻す

			while ($context->eos() !== true && $char === ';') {
				$char = $context->readElement(); // 宣言（declaration）が複数あればさらに進める
				$this->ignore->parse($context);
				$char = $context->readElement(); // 次の文字を取得する
				$context->seek($context->tell() - 1); // 取得用に動かしたので、1つ戻す
			}

		}
		$this->ignore->parse($context);
		$char = $context->readElement(); // 次の文字を取得する
		$context->seek($context->tell() - 1); // 取得用に動かしたので、1つ戻す

		if ($char === '}') $char = $context->readElement();

		return $result;
	}

}
