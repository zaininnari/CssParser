<?php

interface IValidate
{

	/**
	 * validate
	 *
	 * @param CSSParser_Node $node CSSParser_Node
	 *
	 * @return CSSParser_Node
	 */
	public function validate(CSSParser_Node $node);

}