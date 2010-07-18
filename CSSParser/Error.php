<?php

class CSSParser_Error implements PEG_IParser
{
	protected $parsers;
	function __construct(Array $parsers = array())
	{
		$this->parsers = $parsers;
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
	function parse(PEG_IContext $context)
	{
		$offset = $context->tell();
		$results = array();
		$str = $context->read(strlen($context->get()) - $context->tell());
		foreach ($this->parsers as $parser) {
			if (!$parser instanceof PEG_IParser) $parser = PEG::token($parser);
			$ret = $parser->parse(PEG::context($str));
			if (!$ret instanceof PEG_Failure) {
				$len = strlen($ret);
				$obj = new stdClass;
				$obj->len = $len;
				$obj->result = $ret;
				if (!isset($results[$len])) {
					$results[$len] = $obj;
				}
			}
		}
		if (empty($results)) return PEG::failure();
		ksort($results);
		$result = array_shift($results);
		$context->seek($offset + $result->len);
		return $result->result;
	}
}
