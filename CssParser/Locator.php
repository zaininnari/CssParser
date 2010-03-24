<?php

class CssParser_Locator
{
	protected $blockRef;
	protected $shared = array();

	private function __construct()
	{
		$this->setup();
	}

	protected function setup()
	{
		$this->block;
		$this->blockRef = PEG::memo(new CssParser_Block($this));
	}

	static function it()
	{
		static $obj = null;
		return $obj ? $obj : $obj = new self;
	}

	function __get($name)
	{
		return isset($this->shared[$name]) ?
			$this->shared[$name] :
			$this->shared[$name] = $this->{'create' . $name}();
	}

	protected function nodeCreater($type, PEG_IParser $parser, Array $keys = array())
	{
		return new CssParser_NodeCreater($type, $parser, $keys);
	}

	protected function createBlock()
	{
		return PEG::ref($this->blockRef);
	}

	protected function createRuleSet()
	{
		$parser = new CssParser_RuleSet($this->Block);
		return $this->nodeCreater('ruleSet', $parser);
	}

	protected function createIgnore()
	{
		$parser = PEG::memo(new CssParser_Ignore($this->Block));
		return $parser;
	}

	protected function createAtRule()
	{
		$parser = PEG::memo(new CssParser_AtRule($this->Block));
		return $this->nodeCreater('atRule', $parser);
	}

	protected function createParser()
	{
		return $this->nodeCreater('root', PEG::many($this->block));
	}

}
