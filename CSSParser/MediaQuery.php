<?php

class MediaQuery
{
	private $m_restrictor;
	private $m_mediaType;
	private $m_expressions;

	protected $i = 0;


	public static function it()
	{
		static $o;
		return $o === null ? $o = new MediaQuery : $o;
	}




}
