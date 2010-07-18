<?php

class CSSParser_Locator
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
		$this->blockRef = CSSPEG::memo(new CSSParser_Block($this));
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
		return new CSSParser_NodeCreater($type, $parser, $keys);
	}

	protected function createBlock()
	{
		return CSSPEG::ref($this->blockRef);
	}

	protected function createRuleSet()
	{
		$parser = new CSSParser_RuleSet();
		return $this->nodeCreater('ruleSet', $parser);
	}

	protected function createAtRule()
	{
		$parser = CSSPEG::memo(new CSSParser_AtRule());
		return $this->nodeCreater('atRule', $parser);
	}

	protected function createParser()
	{
		return $this->nodeCreater('root', CSSPEG::many($this->block));
	}

}
