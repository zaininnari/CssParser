<?php

interface IValidate
{


	public function validate(CssParser_Node $node);

	/**
	 * エラーメッセージを取得
	 *
	 * @return array
	 */
	public function getError();

}