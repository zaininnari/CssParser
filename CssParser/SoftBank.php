<?php
class SoftBank extends AParser
{



	protected $removeSelectorParseList = array(
		'adjacent','attribute','first-child','language',
		'first-line','first-letter','before-after'
	);


	protected $color = '((?:#[0-9a-fA-F]{3})|(?:#[0-9a-zA-Z]{6})|(?:Black|Silver|Gray|White|Maroon|Red|Purple|Fuchsia|Green|Lime|Olive|Yellow|Navy|Blue|Teal|Aqua))';



	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyMarginTop($val)
	{
		$pattern = '('.$this->length.'|'.$this->percentage.'|auto)';
		return preg_match('/^'.$pattern.'$/i', $val);
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
		$pattern = '('.$this->lengthPlus.'|'.$this->percentagePlus.'|'.$lengthNoUnit.')';
		return preg_match('/^'.$pattern.'$/i', $val);
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
		$pattern = '('.$this->lengthPlus.'|thin|medium|thick)';
		return preg_match('/^'.$pattern.'$/i', $val);
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
		$pattern = '('.$this->color.'|transparent)';
		return preg_match('/^'.$pattern.'$/i', $val);
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
		$pattern = '(none|hidden|dotted|dashed|solid|double|groove|ridge|inset|outset)';
		return preg_match('/^'.$pattern.'$/i', $val);
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyBorderTop($val)
	{
		$values = preg_split('/\s* \s*/', $val, -1, PREG_SPLIT_NO_EMPTY);
		$regArr = array('(thin|medium|thick)','(none|hidden|solid)', $this->color);
		if (count($values) > 3) return false; // 4個以上はありえない
		foreach ($values as $i => $value) {
			$before = count($regArr);
			foreach ($regArr as $n => $reg) {
				if (preg_match('/^'.$reg.'$/i', $value)) {
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
	protected function propertyDisplay($val)
	{
		$pattern = '(none)';
		return preg_match('/^'.$pattern.'$/i', $val);
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
		$pattern = '('.$this->lengthPlus.'|'.$this->percentagePlus.'|auto)';
		return preg_match('/^'.$pattern.'$/i', $val);
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyHeight($val)
	{
		$pattern = '('.$this->lengthPlus.'|'.$this->percentagePlus.'|auto)';
		return preg_match('/^'.$pattern.'$/i', $val);
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
		$pattern = '('.$this->lengthPlus.'|'.$this->percentagePlus.'|normal)';
		return preg_match('/^'.$pattern.'$/i', $val);
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
		return preg_match('/^'.$pattern.'$/i', $val);
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
		return preg_match('/^'.$pattern.'$/i', $val);
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
		$pattern = '('.$this->uri.'|none)';
		return preg_match('/^'.$pattern.'$/i', $val);
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
		$pattern = '(inside|outside)';
		return preg_match('/^'.$pattern.'$/i', $val);
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
		$values = preg_split('/\s* \s*/', $val, -1, PREG_SPLIT_NO_EMPTY);
		$regArr = array('propertyListStyleType', 'propertyListStyleImage', 'propertyListStyle');

		if (count($values) > 3) return false; // 4個以上はありえない
		foreach ($values as $i => $value) {
			$before = count($regArr);
			foreach ($regArr as $n => $reg) {
				if ($this->{$reg}($value)) {
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
	protected function propertyBackgroundColor($val)
	{
		$pattern = '('.$this->color.'|transparent)';
		return preg_match('/^'.$pattern.'$/i', $val);
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
		$pattern = '('.$this->uri.'|none)';
		return preg_match('/^'.$pattern.'$/i', $val);
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
		$pattern = '(repeat|repeat-x|repeat-y|no-repeat)';
		return preg_match('/^'.$pattern.'$/i', $val);
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
		$pattern = '(scroll|fixed)';
		return preg_match('/^'.$pattern.'$/i', $val);
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
		$values = preg_split('/\s* \s*/', $val, -1, PREG_SPLIT_NO_EMPTY);
		$regArr = array($this->percentage, $this->length, '(top|center|bottom)','(left|center|right)');

		if (count($values) > 2) return false; // 3個以上はありえない
		if (count($values) === 1) {
			if (!preg_match('/^('.implode('|', $regArr).')$/i', $values[0])) return false;
		} elseif (count($values) === 2) {
			// percentageとlengthの混在は不可
			foreach ($regArr as $n => $reg) {
				if (preg_match('/^'.$reg.'$/i', $value[0])) {
					if ($n < 2 && !preg_match('/^'.$reg.'$/i', $value[1])) return false;
					if ($n === 2 && !preg_match('/^'.$regArr[3].'$/i', $value[1])) return false;
					if ($n === 3 && !preg_match('/^'.$regArr[2].'$/i', $value[1])) return false;
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
		$values = preg_split('/\s* \s*/', $val, -1, PREG_SPLIT_NO_EMPTY);
		$regArr = array('propertyBackgroundColor', 'propertyBackgroundImage',
			'propertyBackgroundRepeat', 'propertyBackgroundAttachment', 'propertyBackgroundPosition'
		);

		if (count($values) > 7) return false; // 6個以上はありえない
		if (count($values) === 6) {
			for ($i = 0; $i < count($values); $i++) {
				$before = count($regArr);
				foreach ($regArr as $n => $reg) {
					if ($this->{$reg}($values[$i])) {
						if ($reg === 'propertyBackgroundPosition') {
							if (count($values) < 6 && $this->{$reg}($values[$i+1])) {
								$i++;
							} else {
								return false;
							}
						}
						unset($regArr[$n]);
						break;
					}
				}
				if (count($regArr) === $before) return false;
			}
		} else {
			for ($i = 0; $i < count($values); $i++) {
				$before = count($regArr);
				foreach ($regArr as $n => $reg) {
					if ($this->{$reg}($values[$i])) {
						if ($reg === 'propertyBackgroundPosition') {
							if (count($values) < 6 && $this->{$reg}($values[$i+1])) {
								$i++;
							}
						}
						unset($regArr[$n]);
						break;
					}
				}
				if (count($regArr) === $before) return false;
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
	protected function propertyFontFamily($val)
	{
		// font-family内の半角空白をエスケープ
		$val = preg_replace('/([\"\'].*?) (.*?[\"\'])/', '$1&nbsp;$2', $val);
		$values = preg_split('/\s*,\s*/', $val, -1, PREG_SPLIT_NO_EMPTY); // 区切りは「,」
		$pattern = '(serif|sans-serif|cursive|fantasy|monospace|\"(?:.*?)\")';
		foreach ($values as $value) {
			if(!preg_match('/^'.$pattern.'$/i', $value)) return false;
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
	protected function propertyFontStyle($val)
	{
		$pattern = '(normal|italic|oblique)';
		return preg_match('/^'.$pattern.'$/i', $val);
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
		$pattern = '(normal|small-caps)';
		return preg_match('/^'.$pattern.'$/i', $val);
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
		$pattern = '(normal|bold|bolder|lighter|100|200|300|400|500|600|700|800|900)';
		return preg_match('/^'.$pattern.'$/i', $val);
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyFontSize($val)
	{
		$absolutesize = '(?:xx-small|x-small|small|medium|large|x-large|xx-large)';
		$relativesize = '(?:smaller|larger)';

		$pattern = "($absolutesize|$relativesize|$this->lengthPlus|$this->percentagePlus|inherit)";
		return preg_match('/^'.$pattern.'$/i', $val);
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
		// font-family内の半角空白をエスケープ
		$val = preg_replace('/([\"\'].*?) (.*?[\"\'])/', '$1&nbsp;$2', $val);
		$regArr = array('font-style', 'font-variant', 'font-weight', 'font-size', 'font-family');
		$regArr = $this->getPropertyMethod($regArr); // メソッド名を取得
		if ($regArr === false) return false; // メソッドがない場合、失敗する
		$fontFamily = array_pop($regArr); // 'font-family'メソッド名を取得
		$font_size = array_pop($regArr); // 'font-size'メソッド名を取得

		$values = preg_split('/\s* \s*/', $val, -1, PREG_SPLIT_NO_EMPTY); // 半角空白で分割

		if (count($values) > 5) return $val; // 6個以上はありえない
		if (count($values) > 2) {
			$secondLast = count($values) - 1;
			for ($i=0;$i<$secondLast;$i++) {
				$before = count($regArr);
				if ($i === $secondLast - 1) { //最後から2番目は、sizeも加える
					$regArr[] = $font_size;
					$before = count($regArr); // 再カウント
				}
				foreach ($regArr as $j => $reg) {
					if ($this->{$reg}($values[$i])) {
						unset($regArr[$j]);
						break;
					}
				}
				if (count($regArr) === $before) return false;
			}
		}
		// 最後尾は'font-family'
		if($this->{$fontFamily}(array_pop($values)) === false) return false;
		return true;
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
		$lengthInteger = '(?:(-|+)?[0-9]{1,}(?:px|em|ex|in|cm|mm|pt|pc)|0)'; // 整数のみ、実数はNG
		$pattern = "($lengthInteger|$this->percentage)";
		return preg_match('/^'.$pattern.'$/i', $val);
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
		$pattern = '(left|right|center|justify)';
		return preg_match('/^'.$pattern.'$/i', $val);
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
		$values = preg_split('/\s* \s*/', $val, -1, PREG_SPLIT_NO_EMPTY); // 半角空白で分割
		$regArr = array('underline', 'overline', 'line-through', 'blink');

		if (count($values) === 1) { // 1個の場合は、'none'が含まれる可能性がある
			$regArr[] = 'none';
			if (!preg_match("/^(?:".implode('|', $regArr).")$/", $values[0], $m)) {
				return false;
			}
		} else {
			foreach ($values as $value) {
				$before = count($regArr);
				foreach ($regArr as $n => $reg) {
					if (preg_match("/^(?:$reg)$/", $value)) {
						unset($regArr[$n]);
						break;
					}
				}
				if (count($regArr) === $before) return false; // 変化しない => マッチしなかった
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
	protected function propertyLetterSpacing($val)
	{
		$pattern = "(normal|$this->length)";
		return preg_match('/^'.$pattern.'$/i', $val);
	}

	/**
	 * check css value
	 *
	 * @param string $val css value
	 *
	 * @return boolean
	 */
	protected function propertyWordSpacing($val)
	{
		return propertyLetterSpacing($val);
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
		$pattern = "(capitalize|uppercase|lowercase|none)";
		return preg_match('/^'.$pattern.'$/i', $val);
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
		return preg_match('/^'.$pattern.'$/i', $val);
	}

}