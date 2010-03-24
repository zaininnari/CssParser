<?php

interface IValidate
{

	/**
	 * validate
	 *
	 * @param CssParser_Node $node CssParser_Node
	 *
	 * @return CssParser_Node
	 */
	public function validate(CssParser_Node $node);

}