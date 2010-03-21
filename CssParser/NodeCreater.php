<?php

class CssParser_NodeCreater implements PEG_IParser
{
	protected $type, $keys, $parser;
	function __construct($type, PEG_IParser $parser, Array $keys = array())
	{
		$this->type = $type;
		$this->keys = $keys;
		$this->parser = $parser;
	}
	function parse(PEG_IContext $context)
	{
		$offset = $context->tell();
		$result = $this->parser->parse($context);

		if ($result instanceof PEG_Failure) {
			return $result;
		}

		$data = array();
		if (count($this->keys) > 0) foreach ($this->keys as $i => $key) {
			$data[$key] = $result[$i];
		} else {
			$data = $result;
		}

		return new CssParser_Node(
			$this->type, $data, $offset);
	}
}
