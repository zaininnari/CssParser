<?php

/**
 * @author pc
 *
 */
class NonAscii implements PEG_IParser
{

	/**
	 * http://tools.ietf.org/html/rfc3629
	 *
	 * Char. number range  |		UTF-8 octet sequence
	 *	(hexadecimal)	|			  (binary)
	 * --------------------+---------------------------------------------
	 * 0000 0000-0000 007F | 0xxxxxxx
	 * 0000 0080-0000 07FF | 110xxxxx 10xxxxxx
	 * 0000 0800-0000 FFFF | 1110xxxx 10xxxxxx 10xxxxxx
	 * 0001 0000-0010 FFFF | 11110xxx 10xxxxxx 10xxxxxx 10xxxxxx
	 *
	 * @param PEG_IContext $context PEG_IContext
	 *
	 * @see PEG/PEG_IParser#parse($c)
	 *
	 * @return ?
	 */
	function parse(PEG_IContext $context)
	{
		/**
		 * http://tools.ietf.org/html/rfc3629
		 *
		 * UTF8-octets = *( UTF8-char )
		 * UTF8-char   = UTF8-1 / UTF8-2 / UTF8-3 / UTF8-4
		 * UTF8-1      = %x00-7F
		 * UTF8-2      = %xC2-DF UTF8-tail
		 * UTF8-3      = %xE0 %xA0-BF UTF8-tail / %xE1-EC 2( UTF8-tail ) /
		 *               %xED %x80-9F UTF8-tail / %xEE-EF 2( UTF8-tail )
		 * UTF8-4      = %xF0 %x90-BF 2( UTF8-tail ) / %xF1-F3 3( UTF8-tail ) /
		 *               %xF4 %x80-8F 2( UTF8-tail )
		 * UTF8-tail   = %x80-BF
		 *
		 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
		 *
		 * Surrogates are not allowed. (U+D800 - U+DFFF)
		 *
		 * [\x00-\x7f]                                         U+0000   - U+007F
		 * [\xc2-\xdf] [\x80-\xbf]                             U+0080   - U+07FF
		 *       \xe0  [\xa0-\xbf] [\x80-\xbf]                 U+0800   - U+0FFF
		 * [\xe1-\xec] [\x80-\xbf] [\x80-\xbf]                 U+1000   - U+CFFF
		 *       \xed  [\x80-\x9f] [\x80-\xbf]                 U+D000   - U+D7FF
		 * [\xee-\xef] [\x80-\xbf] [\x80-\xbf]                 U+E000   - U+FFFF
		 *       \xf0  [\x90-\xbf] [\x80-\xbf] [\x80-\xbf]     U+10000  - U+3FFFF
		 * [\xf1-\xf3] [\x80-\xbf] [\x80-\xbf] [\x80-\xbf]     U+40000  - U+FFFFF
		 *       \xf4  [\x80-\x8f] [\x80-\xbf] [\x80-\xbf]     U+100000 - U+10FFFF
		 */
		$offset = $context->tell();
		$byte1 = $context->readElement();

		// ascii
		// [\x00-\x7f]                                         U+0000   - U+007F
		if ($byte1 <= "\x7F") return PEG::failure();

		// byte1 invalid code
		// -\xc1][\xf5-
		if ($byte1 <= "\xc1" || $byte1 >= "\xf5") return PEG::failure();

		// 2byte char
		// [\xc2-\xdf] [\x80-\xbf]                             U+0080   - U+07FF
		$byte2 = $context->readElement();
		if ( ($byte1 >= "\xc2" && $byte1 <= "\xdf")
			&& ($byte2 >= "\x80" && $byte2 <= "\xbf")
		) return $byte1.$byte2;

		// 3byte char
		//       \xe0  [\xa0-\xbf] [\x80-\xbf]                 U+0800   - U+0FFF
		// [\xe1-\xec] [\x80-\xbf] [\x80-\xbf]                 U+1000   - U+CFFF
		//       \xed  [\x80-\x9f] [\x80-\xbf]                 U+D000   - U+D7FF
		// [\xee-\xef] [\x80-\xbf] [\x80-\xbf]                 U+E000   - U+FFFF
		$byte3 = $context->readElement();
		$isBype3 = $byte3 >= "\x80" && $byte3 <= "\xbf" ? true : false;

		//       \xe0  [\xa0-\xbf] [\x80-\xbf]                 U+0800   - U+0FFF
		if ($byte1 === "\xe0"
			&& ($byte2 >= "\xa0" && $byte2 <= "\xbf")
			&& $isBype3 === true
		) return $byte1.$byte2.$byte3;

		// [\xe1-\xec] [\x80-\xbf] [\x80-\xbf]                 U+1000   - U+CFFF
		// [\xee-\xef] [\x80-\xbf] [\x80-\xbf]                 U+E000   - U+FFFF
		if ((($byte1 >= "\xe1" && $byte1 <= "\xec")
			|| ($byte1 >= "\xee" && $byte1 <= "\xef"))
			&& ($byte2 >= "\x80" && $byte2 <= "\xbf")
			&& $isBype3 === true
		) return $byte1.$byte2.$byte3;

		//       \xed  [\x80-\x9f] [\x80-\xbf]                 U+D000   - U+D7FF
		if ($byte1 === "\xed"
			&& ($byte2 >= "\x80" && $byte2 <= "\x9f")
			&& $isBype3 === true
		) return $byte1.$byte2.$byte3;

		$context->seek($offset);

		return PEG::failure();
	}
}
