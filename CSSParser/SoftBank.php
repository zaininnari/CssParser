<?php
class SoftBank extends AValidate
{

	protected $removeSelectorParseList = array(
		'adjacent','attribute','first-child','language',
		'first-line','first-letter','before-after'
	);

	/**
	 * 初期化
	 *
	 * @return ?
	 */
	protected function initialize()
	{
		$this->valDef['color'] = '((?:#[0-9a-fA-F]{3})|(?:#[0-9a-zA-Z]{6})|(?:Black|Silver|Gray|White|Maroon|Red|Purple|Fuchsia|Green|Lime|Olive|Yellow|Navy|Blue|Teal|Aqua))';
		parent::initialize();
		$this->valDef['border-width'] = '(?:thin|medium|thick|' . $this->valDef['lengthPlus'] . ')';
	}

	/**
	 * propertyのリストを作成する
	 *
	 * @return Array
	 */
	protected function _initializePropertyList()
	{
		$propertyList = array();
		$list = array(
			'margin-top','margin-right','margin-bottom','margin-left','margin',
			'padding-top','padding-right','padding-bottom','padding-left','padding',
			'border-top-width','border-right-width','border-bottom-width','border-left-width','border-width',
			'border-top-color','border-right-color','border-bottom-color','border-left-color','border-color',
			'border-top-style','border-right-style','border-bottom-style','border-left-style','border-style',
			'border-top','border-right','border-bottom','border-left','border',
			'color','background-color','background-image','background-repeat','background-attachment','background-position','background',
			'font-family','font-style','font-variant','font-weight','font-size','font',
			'list-style-type','list-style-position','list-style-image','list-style',
			'text-indent','text-align','text-decoration','text-transform','white-space','letter-spacing','word-spacing',
			'display','width','height','vertical-align','line-height',
		);
		foreach ($list as $v) {
			$property = '-' . $v;
			$propertyList[$property] = $this->propertyMethodPrefix . str_replace(' ', '', ucwords(str_replace('-', ' ', $property)));
		}
		return $propertyList;
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyMarginTop($val)
	{
		if ($val === 'inherit') return false;
		$method = __FUNCTION__;
		return parent::$method($val);
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyPaddingTop($val)
	{
		// 単位指定が無い場合、「em単位」として扱う => 単位なしOK
		$lengthNoUnit = '(?:[0-9]{1,}(¥.[0-9]+)?|¥.[0-9]+)';
		$pattern = '('.$this->valDef['lengthPlus'].'|'.$this->valDef['percentagePlus'].'|'.$lengthNoUnit.')';
		return preg_match('/^'.$pattern.'$/i', $val) === 1;
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyBorderTopWidth($val)
	{
		if ($val === 'inherit') return false;
		$method = __FUNCTION__;
		return parent::$method($val);
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyBorderTopColor($val)
	{
		if ($val === 'inherit') return false;
		$method = __FUNCTION__;
		return parent::$method($val);
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyBorderTopStyle($val)
	{
		if ($val === 'inherit') return false;
		$method = __FUNCTION__;
		return parent::$method($val);
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyDisplay($val)
	{
		$pattern = '(none)';
		return preg_match('/^'.$pattern.'$/i', $val) === 1;
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyWidth($val)
	{
		if ($val === 'inherit') return false;
		$method = __FUNCTION__;
		return parent::$method($val);
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyLineHeight($val)
	{
		$pattern = '('.$this->valDef['lengthPlus'].'|'.$this->valDef['percentagePlus'].'|normal)';
		return preg_match('/^'.$pattern.'$/i', $val) === 1;
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyVerticalAlign($val)
	{
		$pattern = '(baseline|sub|super|top|text-top|middle|bottom|text-bottom)';
		return preg_match('/^'.$pattern.'$/i', $val) === 1;
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyListStyleType($val)
	{
		$pattern = '(disc|circle|square|decimal|lower-roman|upper-roman|lower-alpha|upper-alpha|none)';
		return preg_match('/^'.$pattern.'$/i', $val) === 1;
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyListStyleImage($val)
	{
		if ($val === 'inherit') return false;
		$method = __FUNCTION__;
		return parent::$method($val);
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyListStylePosition($val)
	{
		if ($val === 'inherit') return false;
		$method = __FUNCTION__;
		return parent::$method($val);
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyListStyle($val)
	{
		if ($val === 'inherit') return false;
		$method = __FUNCTION__;
		return parent::$method($val);
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyColor($val)
	{
		if ($val === 'inherit') return false;
		$method = __FUNCTION__;
		return parent::$method($val);
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyBackgroundColor($val)
	{
		if ($val === 'inherit') return false;
		$method = __FUNCTION__;
		return parent::$method($val);
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyBackgroundImage($val)
	{
		if ($val === 'inherit') return false;
		$method = __FUNCTION__;
		return parent::$method($val);
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyBackgroundRepeat($val)
	{
		if ($val === 'inherit') return false;
		$method = __FUNCTION__;
		return parent::$method($val);
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyBackgroundAttachment($val)
	{
		if ($val === 'inherit') return false;
		$method = __FUNCTION__;
		return parent::$method($val);
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyBackgroundPosition($val)
	{
		$values = self::_split($val);
		$regArr = array($this->valDef['percentage'], $this->valDef['length'], '(top|center|bottom)','(left|center|right)');

		if (count($values) > 2) return false; // 3個以上はありえない
		if (count($values) === 1) {
			if (!preg_match('/^('.implode('|', $regArr).')$/i', $values[0])) return false;
		} elseif (count($values) === 2) {
			// percentageとlengthの混在は不可
			foreach ($regArr as $n => $reg) {
				if (preg_match('/^'.$reg.'$/i', $values[0])) {
					if ($n < 2 && !preg_match('/^'.$reg.'$/i', $values[1])) return false;
					if ($n === 2 && !preg_match('/^'.$regArr[3].'$/i', $values[1])) return false;
					if ($n === 3 && !preg_match('/^'.$regArr[2].'$/i', $values[1])) return false;
				}
			}
		}
		return true;
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyBackground($val)
	{
		$values = self::_split($val);;
		$regArr = array('background-color', 'background-image',
			'background-repeat', 'background-attachment', 'background-position'
		);

		if (count($values) > 6) return false; // 7個以上はありえない

		for ($i = 0; $i < count($values); $i++) {
			$before = count($regArr);
			foreach ($regArr as $n => $reg) {
				if ($this->callPropertyMethod($reg, $values[$i])) {
					if ($reg === 'background-position') {
						if (isset($values[$i+1]) === false || (count($values) < 6 && $this->callPropertyMethod($reg, $values[$i+1]))) {
							$i++;
						} else {
							if (count($values) === 6) return false;
						}
					}
					unset($regArr[$n]);
					break;
				}
			}
			if (count($regArr) === $before) return false;
		}

		return true;
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyFontFamily($val)
	{
		if ($val === 'inherit') return false;
		$method = __FUNCTION__;
		return parent::$method($val);
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyFontStyle($val)
	{
		if ($val === 'inherit') return false;
		$method = __FUNCTION__;
		return parent::$method($val);
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyFontVariant($val)
	{
		if ($val === 'inherit') return false;
		$method = __FUNCTION__;
		return parent::$method($val);
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyFontWeight($val)
	{
		if ($val === 'inherit') return false;
		$method = __FUNCTION__;
		return parent::$method($val);
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyFont($val)
	{
		$result = array();
		$arr = self::_split($val);

		// [[ <'font-style'> || <'font-variant'> || <'font-weight'> ]?
		$regArr = array('font-style', 'font-variant', 'font-weight');
		$before = null;
		while ($before !== count($regArr)) {
			if (isset($arr[0]) === false) return false;
			$before = count($regArr);
			foreach ($regArr as $n => $reg) {
				if ($this->callPropertyMethod($reg, $arr[0])) {
					$result[] = array_shift($arr);
					unset($regArr[$n]);
					break;
				}
			}
		}

		if (isset($arr[0]) === false) return false;

		// <'font-size'>?
		if (preg_match('/^'.$this->valDef['font-size'].'$/i', $arr[0])) $result[] = array_shift($arr);

		if (isset($arr[0]) === false) return false;

		// 'font-family' のみで構成されているかどうかのチェック
		if (count($arr) > 1) {
			$count = count($arr);
			for ($i=0;$i<$count;$i++) {
				if (strpos($arr[$i] . $arr[$i < $count - 1 ? $i + 1 : $i - 1], ',') === false) return false;
			}
		}

		return $this->callPropertyMethod('font-family', implode('', $arr));
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyTextIndent($val)
	{
		$lengthInteger = '(?:(-|\+)?[0-9]{1,}(?:px|em|ex|in|cm|mm|pt|pc)|0)'; // 整数のみ、実数はNG
		$pattern = "($lengthInteger|$this->valDef['percentage'])";
		return preg_match('/^'.$pattern.'$/i', $val) === 1;
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyTextAlign($val)
	{
		if ($val === 'inherit') return false;
		$method = __FUNCTION__;
		return parent::$method($val);
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyTextDecoration($val)
	{
		if ($val === 'inherit') return false;
		$method = __FUNCTION__;
		return parent::$method($val);
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyLetterSpacing($val)
	{
		if ($val === 'inherit') return false;
		$method = __FUNCTION__;
		return parent::$method($val);
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyTextTransform($val)
	{
		if ($val === 'inherit') return false;
		$method = __FUNCTION__;
		return parent::$method($val);
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyWhiteSpace($val)
	{
		$pattern = "(normal|nowrap)";
		return preg_match('/^'.$pattern.'$/i', $val) === 1;
	}

}