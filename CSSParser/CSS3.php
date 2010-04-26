<?php
class CSS3 extends AValidate
{


	protected function propertyPageBreakBefore($val)
	{
		$pattern = '(auto|always|avoid|left|right|inherit)';
		return preg_match('/^'.$pattern.'$/i', $val) === 1;
	}

	protected function propertyPageBreakAfter($val)
	{
		return $this->callPropertyMethod('page-break-before', $val);
	}

	protected function propertyPageBreakInside($val)
	{
		$pattern = '(avoid|auto|inherit)';
		return preg_match('/^'.$pattern.'$/i', $val) === 1;
	}

	protected function propertyOrphans($val)
	{
		$pattern = '('.$this->valDef['integer'].'|inherit)';
		return preg_match('/^'.$pattern.'$/i', $val) === 1;
	}

	protected function propertyWidows($val)
	{
		$pattern = '('.$this->valDef['integer'].'|inherit)';
		return preg_match('/^'.$pattern.'$/i', $val) === 1;
	}


}