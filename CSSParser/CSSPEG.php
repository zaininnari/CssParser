<?php
/**
 * PHP CSS Def and Rule
 *
 * PHP 5.3 or higher is required.
 *
 * @license MIT License
 *
 */


require_once dirname(__FILE__) . '/NonAscii.php';

class CSSPEG extends PEG
{
	// {{{ const
	const V_UNKNOWN = 0,
		V_NUMBER = 1,
		V_PERCENTAGE = 2,
		V_EMS = 3,
		V_EXS = 4,
		V_PX = 5,
		V_CM = 6,
		V_MM = 7,
		V_IN = 8,
		V_PT = 9,
		V_PC = 10,
		V_DEG = 11,
		V_RAD = 12,
		V_GRAD = 13,
		V_MS = 14,
		V_S = 15,
		V_HZ = 16,
		V_KHZ = 17,
		V_DIMENSION = 18,
		V_STRING = 19,
		V_URI = 20,
		V_IDENT = 21,
		V_ATTR = 22,
		V_COUNTER = 23,
		V_RECT = 24,
		V_RGBCOLOR = 25,
		V_FUNCTION = 26,

		V_PAIR = 100,
		V_DASHBOARD_REGION = 101,
		V_UNICODE_RANGE = 102,

		V_PARSER_OPERATOR = 103,
		V_PARSER_INTEGER = 104,
		V_PARSER_VARIABLE_FUNCTION_SYNTAX = 105,
		V_PARSER_HEXCOLOR = 106,

		V_PARSER_IDENTIFIER = 107,
		V_TURN = 108,
		V_REMS = 109,

		V_QEM = 1000;

	// }}} const


	// {{{ definitions
	/**
	 * h		[0-9a-f]
	 *
	 * @return PEG_IParser
	 */
	protected static function defH()
	{
		static $o = null;
		return $o !== null ? $o : $o = self::char('0123456789abcdefABCDEF');
	}

	/**
	 * nonascii
	 *
	 * @return PEG_IParser
	 */
	protected static function defNonAscii()
	{
		static $o = null;
		return $o !== null ? $o : $o = new NonAscii();
	}

	/**
	 * unicode		\\{h}{1,6}(\r\n|[ \t\r\n\f])?
	 *
	 * @return PEG_IParser
	 */
	protected static function defUnicode()
	{
		static $o = null;
		return $o !== null ? $o : $o = self::join(
			self::seq(
				'\\',
				self::defH(),
				self::optional(self::defH()),
				self::optional(self::defH()),
				self::optional(self::defH()),
				self::optional(self::defH()),
				self::optional(self::defH()),
				self::optional(self::defW())
			)
		);
	}

	/**
	 * escape		{unicode}|\\[ -~\200-\377]
	 * 32 - 126 128 - 255
	 *
	 * @return PEG_IParser
	 */
	protected static function defEscape()
	{
		static $o = null;
		if ($o === null) {
			$r = array();
			for ($i = 32; $i <= 126; $i++) $r[] = chr($i);
			$r = self::memo(call_user_func_array(array('self', 'choice'), $r));
			$o = self::choice(
				self::defUnicode(),
				self::join(
					self::seq(
						'\\',
						self::choice(
							$r,
							self::defNonAscii()
						)
					)
				)
			);
		}
		return $o;
	}

	/**
	 * nmstart		[a-z]|{nonascii}|{escape}
	 *
	 * @return PEG_IParser
	 */
	protected static function defNmstart()
	{
		static $o = null;
		return $o !== null ? $o : $o = self::choice(
			self::alphabet(),
			'_',
			self::defNonAscii(),
			self::defEscape()
		);
	}

	/**
	 * nmchar		[a-z0-9-]|{nonascii}|{escape}
	 *
	 * @return PEG_IParser
	 */
	protected static function defNmchar()
	{
		static $o = null;
		return $o !== null ? $o : $o = self::choice(
			self::alphabet(),
			self::digit(),
			'-',
			'_',
			self::defNonAscii(),
			self::defEscape()
		);
	}

	/**
	 * string		{string1}|{string2}
	 * string1		\"([\t !#$%&(-~]|\\{nl}|\'|{nonascii}|{escape})*\"
	 * string2		\'([\t !#$%&(-~]|\\{nl}|\"|{nonascii}|{escape})*\'
	 *
	 * \t  ->  9
	 * ' ' -> 32
	 * !   -> 33
	 * <<skip>>    '"' -> 34
	 * #   -> 35
	 * $   -> 36
	 * %   -> 37
	 * &   -> 38
	 * <<skip>>    "'" -> 39
	 * (   -> 40
	 *      |
	 * ~   -> 126
	 *
	 * @return PEG_IParser
	 */
	protected static function defString()
	{
		static $o = null;
		if ($o === null) {
			$r = array(
				chr(9),
				chr(32), chr(33),
				chr(35), chr(36), chr(37), chr(38)
			);
			for ($i = 40; $i <= 126; $i++) $r[] = chr($i);
			$r = self::memo(call_user_func_array(array('self', 'choice'), $r));
			$o = self::choice(
				self::join(
					self::seq(
						'"',
						self::many(
							self::choice(
								self::seq('\\', self::defWhiteSpace()),
								'\'',
								self::defNonAscii(), self::defEscape(),
								$r
							)
						),
						'"'
					)
				),
				self::join(
					self::seq(
						'\'',
						self::many(
							self::choice(
								$r,
								self::seq('\\', self::defWhiteSpace()),
								'"',
								self::defNonAscii(),
								self::defEscape()
							)
						),
						'\''
					)
				)
			);
		}
		return $o;
	}

	/**
	 * hexcolor		#{h}{3}|{h}{6}
	 *
	 * @return PEG_IParser
	 */
	protected static function defHexcolor()
	{
		static $o = null;
		return $o !== null ? $o : $o = self::seq(
			'#',
			self::defH(),
			self::defH(),
			self::defH(),
			self::optional(
				self::seq(
					self::defH(), self::defH(), self::defH()
				)
			)
		);
	}

	/**
	 * ident           -?{nmstart}{nmchar}*
	 *
	 * @return PEG_IParser
	 */
	protected static function defIdent()
	{
		static $o = null;
		return $o !== null ? $o : $o = self::join(
			self::seq(
				self::choice('-', ''),
				self::defNmstart(),
				self::join(self::many(self::defNmchar()))
			)
		);
	}

	/**
	 * name            {nmchar}+
	 *
	 * @return PEG_IParser
	 */
	protected static function defName()
	{
		static $o = null;
		return $o !== null ? $o : $o = self::join(
			self::many1(
				self::defNmchar()
			)
		);
	}

	/**
	 * num             [0-9]+|[0-9]*"."[0-9]+
	 *
	 * @return PEG_IParser
	 */
	protected static function defNum()
	{
		static $o = null;
		return $o !== null ? $o : $o = self::choice(
			self::join(
				self::seq(
					self::many(self::digit()),
					'.',
					self::many1(self::digit())
				)
			),
			self::join(
				self::many1(self::digit())
			)
		);
	}

	/**
	 * intnum          [0-9]+
	 *
	 * @return PEG_IParser
	 */
	protected static function defIntnum()
	{
		static $o = null;
		return $o !== null ? $o : $o = self::choice(
			self::join(self::hook(
				function ($r) {return $r[0] === false && $r[1] === false ? PEG::failure() : $r;},
				self::many1(self::digit())
			))
		);
	}

	/**
	 * url		([!#$%&*-~]|{nonascii}|{escape})*
	 *
	 * !   -> 33
	 * <<skip>>    '"' -> 34
	 * #   -> 35
	 * $   -> 36
	 * %   -> 37
	 * &   -> 38
	 * <<skip>>    "'" -> 39
	 * <<skip>>    (   -> 40
	 * <<skip>>    )   -> 41
	 * "*" -> 42
	 *      |
	 * ~   -> 126
	 *
	 * @return PEG_IParser
	 */
	protected static function defUrl()
	{
		static $o = null;
		if ($o === null) {
			$r = array(
				chr(33),
				chr(35), chr(36), chr(37), chr(38)
			);
			for ($i = 42; $i <= 126; $i++) $r[] = chr($i);
			$r = self::memo(call_user_func_array(array('self', 'choice'), $r));
			$o = self::join(
				self::many(
					self::choice($r, self::defNonAscii(), self::defEscape())
				)
			);
		}
		return $o;
	}

	/**
	 * [ \t\r\n\f]
	 *
	 * @return PEG_IParser
	 */
	protected static function defWhiteSpace()
	{
		static $obj = null;
		return $obj ? $obj : $obj = self::memo(self::char(" \t\r\n\f"));
	}

	/**
	 * w		[ \t\r\n\f]*
	 *
	 * @return PEG_IParser
	 */
	protected static function defW()
	{
		static $o = null;
		return $o !== null ? $o : $o = self::join(
			self::many(
				self::defWhiteSpace()
			)
		);
	}

	/**
	 * range		\?{1,6}|{h}(\?{0,5}|{h}(\?{0,4}|{h}(\?{0,3}|{h}(\?{0,2}|{h}(\??|{h})))))
	 *
	 * @return PEG_IParser
	 */
	protected static function defRange()
	{
		static $o = null;
		return $o !== null ? $o : $o = self::hook(
			function($d) {return $d === '' || $d instanceof PEG_Failure ? self::failure() : $d;},
			self::choice(
				self::join(
					self::seq(
						'?',
						self::optional('?'),
						self::optional('?'),
						self::optional('?'),
						self::optional('?'),
						self::optional('?')
					)
				),
				self::join(
					self::seq(
						self::defH(),
						self::choice(
							self::seq(
								self::defH(),
								self::choice(
									self::seq(
										self::defH(),
										self::choice(
											self::seq(
												self::defH(),
												self::choice(
													self::seq(
														self::defH(),
														self::choice(
															self::defH(),
															self::optional('?')
														)
													),
													self::seq(
														self::optional('?'),
														self::optional('?')
													)
												)
											),
											self::seq(
												self::optional('?'),
												self::optional('?'),
												self::optional('?')
											)
										)
									),
									self::seq(
										self::optional('?'),
										self::optional('?'),
										self::optional('?'),
										self::optional('?')
									)
								)
							),
							self::seq(
								self::optional('?'),
								self::optional('?'),
								self::optional('?'),
								self::optional('?'),
								self::optional('?')
							)
						)
					)
				)
			)
		);
	}

	/**
	 * nth             [\+-]?{intnum}*n([\+-]{intnum})?
	 *
	 * @return PEG_IParser
	 */
	protected static function defNth()
	{
		static $o = null;
		return $o !== null ? $o : $o = self::join(
			self::seq(
				self::optional(self::choice('+', '-')),
				self::many(
					self::defIntnum()
				),
				'n',
				self::drop(self::synMaybeSpace()),
				self::optional(
					self::seq(
						self::choice('+', '-'),
						self::drop(self::synMaybeSpace()),
						self::defIntnum()
					)
				),
				self::drop(self::synMaybeSpace())
			)
		);
	}

	// definitions }}}

	// {{{ rules

	/**
	 * [ \t\r\n\f]+            {countLines(); yyTok = WHITESPACE; return yyTok;}
	 *
	 * @return PEG_IParser
	 */
	public static function ruleWHITESPACE()
	{
		static $o = null;
		return $o !== null ? $o : $o = self::many1(
			self::defWhiteSpace()
		);
	}

	/**
	 * "~="                    {yyTok = INCLUDES; return yyTok;}
	 * "|="                    {yyTok = DASHMATCH; return yyTok;}
	 * "^="                    {yyTok = BEGINSWITH; return yyTok;}
	 * "$="                    {yyTok = ENDSWITH; return yyTok;}
	 * "*="                    {yyTok = CONTAINS; return yyTok;}
	 *
	 * @return PEG_IParser
	 */
	public static function ruleMatchPart()
	{
		static $o = null;
		return $o !== null ? $o : $o = self::choice('*=', '$=', '^=', '|=', '~=');
	}

	/**
	 * <mediaquery>"not"       {yyTok = MEDIA_NOT; return yyTok;}
	 *
	 * @return PEG_IParser
	 */
	public static function ruleMEDIA_NOT()
	{
		static $o = null;
		return $o !== null ? $o : $o = self::token('not');
	}

	/**
	 * <mediaquery>"only"      {yyTok = MEDIA_ONLY; return yyTok;}
	 *
	 * @return PEG_IParser
	 */
	public static function ruleMEDIA_ONLY()
	{
		static $o = null;
		return $o !== null ? $o : $o = self::token('only');
	}

	/**
	 * <mediaquery>"and"       {yyTok = MEDIA_AND; return yyTok;}
	 *
	 * @return PEG_IParser
	 */
	public static function ruleMEDIA_AND()
	{
		static $o = null;
		return $o !== null ? $o : $o = self::token('and');
	}

	/**
	 * <forkeyword>"for"       {BEGIN(mediaquery); yyTok = VARIABLES_FOR; return yyTok; }
	 *
	 * @return PEG_IParser
	 */
	public static function ruleVARIABLES_FOR()
	{
		static $o = null;
		return $o !== null ? $o : $o = self::token('for');
	}

	/**
	 * {string}                {yyTok = STRING; return yyTok;}
	 *
	 * @return PEG_IParser
	 */
	public static function ruleSTRING()
	{
		static $o = null;
		return $o !== null ? $o : $o = self::defString();
	}

	/**
	 * {ident}                 {yyTok = IDENT; return yyTok;}
	 *
	 * @return PEG_IParser
	 */
	public static function ruleIDENT()
	{
		static $o = null;
		return $o !== null ? $o : $o = self::defIdent();
	}

	/**
	 * {nth}                   {yyTok = NTH; return yyTok;}
	 *
	 * @return PEG_IParser
	 */
	public static function ruleNTH()
	{
		static $o = null;
		return $o !== null ? $o : $o = self::defNth();
	}

	/**
	 * "#"{hexcolor}           {yyTok = HEX; return yyTok;}
	 *
	 * @return PEG_IParser
	 */
	public static function ruleHEX()
	{
		static $o = null;
		return $o !== null ? $o : $o = self::join(self::defHexcolor());
	}

	/**
	 * "#"{ident}              {yyTok = IDSEL; return yyTok;}
	 *
	 * @return PEG_IParser
	 */
	public static function ruleIDSEL()
	{
		static $o = null;
		return $o !== null ? $o : $o = self::seq(
			'#',
			self::defIdent()
		);
	}

	/**
	 * "@import"               {BEGIN(mediaquery); yyTok = IMPORT_SYM; return yyTok;}
	 *
	 * @return PEG_IParser
	 */
	public static function ruleIMPORT_SYM()
	{
		static $o = null;
		return $o !== null ? $o : $o = self::token('@import');
	}

	/**
	 * "@page"                 {yyTok = PAGE_SYM; return yyTok;}
	 *
	 * @return PEG_IParser
	 */
	public static function rulePAGE_SYM()
	{
		static $o = null;
		return $o !== null ? $o : $o = self::token('@page');
	}

	/**
	 * "@media"                {BEGIN(mediaquery); yyTok = MEDIA_SYM; return yyTok;}
	 *
	 * @return PEG_IParser
	 */
	public static function ruleMEDIA_SYM()
	{
		static $o = null;
		return $o !== null ? $o : $o = self::token('@media');
	}

	/**
	 * "@font-face"            {yyTok = FONT_FACE_SYM; return yyTok;}
	 *
	 * @return PEG_IParser
	 */
	public static function ruleFONT_FACE_SYM()
	{
		static $o = null;
		return $o !== null ? $o : $o = self::token('@font-face');
	}

	/**
	 * "@charset"              {yyTok = CHARSET_SYM; return yyTok;}
	 *
	 * @return PEG_IParser
	 */
	public static function ruleCHARSET_SYM()
	{
		static $o = null;
		return $o !== null ? $o : $o = self::token('@charset');
	}

	/**
	 * "@namespace"            {yyTok = NAMESPACE_SYM; return yyTok; }
	 *
	 * @return PEG_IParser
	 */
	public static function ruleNAMESPACE_SYM()
	{
		static $o = null;
		return $o !== null ? $o : $o = self::token('@namespace');
	}

	/**
	 * "@"{ident}              {yyTok = ATKEYWORD; return yyTok; }
	 *
	 * @return PEG_IParser
	 */
	public static function ruleATKEYWORD()
	{
		static $o = null;
		return $o !== null ? $o : $o = self::join(
			self::seq(
				'@',
				self::defIdent()
			)
		);
	}

	/**
	 * "!"{w}"important"       {yyTok = IMPORTANT_SYM; return yyTok;}
	 *
	 * @return PEG_IParser
	 */
	public static function ruleIMPORTANT_SYM()
	{
		static $o = null;
		return $o !== null ? $o : $o = self::join(
			self::seq(
				'!',
				self::many(self::choice(self::defWhiteSpace(), self::ruleComment())),
				'important'
			)
		);
	}

	/**
	 * {num}em                 {yyTok = EMS; return yyTok;}
	 * {num}rem                {yyTok = REMS; return yyTok;}
	 * {num}__qem              {yyTok = QEMS; return yyTok;} //* quirky ems
	 * {num}ex                 {yyTok = EXS; return yyTok;}
	 * {num}px                 {yyTok = PXS; return yyTok;}
	 * {num}cm                 {yyTok = CMS; return yyTok;}
	 * {num}mm                 {yyTok = MMS; return yyTok;}
	 * {num}in                 {yyTok = INS; return yyTok;}
	 * {num}pt                 {yyTok = PTS; return yyTok;}
	 * {num}pc                 {yyTok = PCS; return yyTok;}
	 * {num}deg                {yyTok = DEGS; return yyTok;}
	 * {num}rad                {yyTok = RADS; return yyTok;}
	 * {num}grad               {yyTok = GRADS; return yyTok;}
	 * {num}turn               {yyTok = TURNS; return yyTok;}
	 * {num}ms                 {yyTok = MSECS; return yyTok;}
	 * {num}s                  {yyTok = SECS; return yyTok;}
	 * {num}Hz                 {yyTok = HERZ; return yyTok;}
	 * {num}kHz                {yyTok = KHERZ; return yyTok;}
	 *
	 * @return PEG_IParser
	 */
	public static function ruleUnit()
	{
		static $o = null;
		if ($o === null) {
			$units = array(
				// array('<key>', <returnValue>)
				array('px',    self::V_PX),
				array('em',    self::V_EMS),
				array('kHz',   self::V_KHZ),
				array('Hz',    self::V_HZ),
				array('s',     self::V_S),
				array('ms',    self::V_MS),
				array('turn',  self::V_TURN),
				array('grad',  self::V_GRAD),
				array('rad',   self::V_RAD),
				array('deg',   self::V_DEG),
				array('pc',    self::V_PC),
				array('pt',    self::V_PT),
				array('in',    self::V_IN),
				array('mm',    self::V_MM),
				array('cm',    self::V_CM),
				array('ex',    self::V_EXS),
				array('rem',   self::V_REMS),
				array('__qem', self::V_QEM)
			);
			$V_PERCENTAGE = self::V_PERCENTAGE;
			$V_NUMBER = self::V_NUMBER;
			$V_DIMENSION = self::V_DIMENSION;
			$unitParser = self::memo(self::parserOf(function (PEG_IContext $c) use($units) {
				$offset = $c->tell();
				foreach ($units as $unit) {
					$r = PEG::token($unit[0], false)->parse($c);
					if ($r instanceof PEG_Failure) {
						$c->seek($offset);
					} else {
						$r = is_array($r) ? join('', $r) : $r;
						return array($r , $unit[1]);
					}
				}
				return PEG::failure();
			}));

			$o = self::memo(
				self::hook(
					function ($r) {
						if (!array_key_exists(2, $r)) $r[2] = false;
						else $r[2] = $r[1] . $r[2];
						if (!array_key_exists(3, $r)) $r[3] = false;
						if (!array_key_exists(4, $r)) $r[4] = false;
						return $r;
					},
					self::flatten(
						self::seq(
							self::optional(CSSPEG::choice('+', '-')),
							self::choice(
								self::seq(self::defNum(), $unitParser),
								self::hook(function ($r) use ($V_PERCENTAGE) {return array($r[0], join('', $r[1]), $V_PERCENTAGE);}, self::rulePERCENTAGE()),
								self::hook(function ($r) use ($V_DIMENSION) {return array($r[0], $r[1], $V_DIMENSION);}, self::ruleDIMEN()),
								self::hook(function ($r) use ($V_NUMBER) {return array($r, '', $V_NUMBER);}, self::ruleFLOATTOKEN()),
								self::hook(function ($r) use ($V_NUMBER) {return array($r, '', $V_NUMBER);}, self::ruleINTEGER())
							)
						)
					)
				)
			);
		}


		return $o;
	}

	/**
	 * {num}{ident}            {yyTok = DIMEN; return yyTok;}
	 *
	 * @return PEG_IParser
	 */
	public static function ruleDIMEN()
	{
		static $o = null;
		return $o !== null ? $o : $o = self::seq(
			self::defNum(),
			self::defIdent()
		);
	}

	/**
	 * {num}%+                 {yyTok = PERCENTAGE; return yyTok;}
	 *
	 * @return PEG_IParser
	 */
	public static function rulePERCENTAGE()
	{
		static $o = null;
		return $o !== null ? $o : $o = self::seq(
			self::defNum(),
			self::many1('%')
		);
	}

	/**
	 * {intnum}                {yyTok = INTEGER; return yyTok;}
	 *
	 * @return PEG_IParser
	 */
	public static function ruleINTEGER()
	{
		static $o = null;
		return $o !== null ? $o : $o = self::defIntnum();
	}

	/**
	 * {num}                   {yyTok = FLOATTOKEN; return yyTok;}
	 *
	 * @return PEG_IParser
	 */
	public static function ruleFLOATTOKEN()
	{
		static $o = null;
		return $o !== null ? $o : $o = self::defNum();
	}

	/**
	 * "not("                  {yyTok = NOTFUNCTION; return yyTok;}
	 *
	 * @return PEG_IParser
	 */
	public static function ruleNOTFUNCTION()
	{
		static $o = null;
		return $o !== null ? $o : $o = self::token('not(');
	}

	/**
	 * "url("{w}{string}{w}")" {yyTok = URI; return yyTok;}
	 * "url("{w}{url}{w}")"    {yyTok = URI; return yyTok;}
	 *
	 * @return PEG_IParser
	 */
	public static function ruleURI()
	{
		static $o = null;
		return $o !== null ? $o : $o = self::seq(
			'url',
			self::drop('(', self::defW()),
			self::choice(self::ruleSTRING(), self::defUrl()),
			self::drop(self::defW(), ')')
		);
	}


	/**
	 * {ident}"("              {yyTok = FUNCTION; return yyTok;}
	 *
	 * @return PEG_IParser
	 */
	public static function ruleFUNCTION()
	{
		static $o = null;
		return $o !== null ? $o : $o = self::seq(
			self::defIdent(),
			'('
		);
	}

	/**
	 * U\+{range}              {yyTok = UNICODERANGE; return yyTok;}
	 * U\+{h}{1,6}-{h}{1,6}    {yyTok = UNICODERANGE; return yyTok;}
	 *
	 * @return PEG_IParser
	 */
	public static function ruleUNICODERANGE()
	{
		static $o = null;
		return $o !== null ? $o : $o = self::choice(
			self::join(
				self::seq(
					'U+',
					self::defH(),
					self::optional(self::defH()),
					self::optional(self::defH()),
					self::optional(self::defH()),
					self::optional(self::defH()),
					self::optional(self::defH()),
					'-',
					self::defH(),
					self::optional(self::defH()),
					self::optional(self::defH()),
					self::optional(self::defH()),
					self::optional(self::defH()),
					self::optional(self::defH())
				)
			),
			self::join(
				self::seq(
					'U+',
					self::defRange()
				)
			)
		);
	}

	/**
	 * commnt
	 *
	 * @return PEG_IParser
	 */
	public static function ruleComment()
	{
		static $o = null;
		return $o !== null ? $o : $o = self::seq(
			'/*',
			self::many(self::tail(self::not('*/'), self::anything())),
			self::choice('*/', self::eos())
		);
	}

	/**
	 * space
	 *
	 * @return PEG_IParser
	 */
	public static function ruleSpace()
	{
		static $o = null;
		return $o !== null ? $o : $o = self::choice(
			self::ruleWHITESPACE(),
			self::ruleComment()
		);
	}

	// rules }}}


	/**
	 * comment
	 *
	 * @return PEG_IParser
	 */
	public static function synComment()
	{
		static $o = null;
		return $o !== null ? $o : $o = self::ruleComment();
	}

	/**
	 * space
	 *
	 * @return PEG_IParser
	 */
	public static function synSpace()
	{
		static $o = null;
		return $o !== null ? $o : $o = self::many1(self::ruleSpace());
	}

	/**
	 * maybe space
	 *
	 * @return PEG_IParser
	 */
	public static function synMaybeSpace()
	{
		static $o = null;
		return $o !== null ? $o : $o = self::many(self::ruleSpace());
	}

	/**
	 * operator
	 *   : '/' S* | ',' S* | /* empty *
	 *   ;
	 *
	 * @return PEG_IParser
	 */
	public static function synOperator()
	{
		static $o = null;
		return $o !== null ? $o : $o = self::choice('/', ',');
	}

	/**
	 * expr
	 *   : term [ operator term ]*
	 *   ;
	 *
	 * term
	 *   : unary_operator?
	 *     [ NUMBER S* | PERCENTAGE S* | LENGTH S* | EMS S* | EXS S* | ANGLE S* |
	 *       TIME S* | FREQ S* | function ]
	 *   | STRING S* | IDENT S* | URI S* | UNICODERANGE S* | hexcolor
	 *   ;
	 *
	 *
	 * @return PEG_IParser
	 */
	public static function synExpr()
	{
		static $o = null;
		if ($o === null) {
			$func = self::seq(
				self::first(self::ruleFUNCTION()),
				self::drop(self::synMaybeSpace()),
				self::ref($o),
				self::drop(self::synMaybeSpace()),
				self::drop(')')
			);
			$V_STRING = self::V_STRING;
			$V_IDENT = self::V_IDENT;
			$V_FUNCTION = self::V_FUNCTION;
			$V_URI = self::V_URI;
			$V_PARSER_HEXCOLOR = self::V_PARSER_HEXCOLOR;
			$V_UNKNOWN = self::V_UNKNOWN;
			$term = new CSSParser_NodeCreater(
				'expr',
				self::hook(
					function ($r) {
						if (!array_key_exists(3, $r)) $r[3] = false;
						return $r;
					},
					self::choice(
						self::ruleUnit(),
						self::hook(function ($r) use($V_PARSER_HEXCOLOR) {return array(false, $r, $r, $V_PARSER_HEXCOLOR, false);}, self::ruleHEX()),
						self::hook(
							function ($r) use($V_URI) {
								$args = $r[1];
								return array(false, $args, 'url(' . $args . ')', $V_URI, false);
							},
							self::ruleURI()
						),
						self::hook(
							function ($r) use($V_FUNCTION) {
								$name = $r[0];
								$args = $r[1];
								$argsToString = array();
								foreach ($args as $node) {
									switch ($node->getType()) {
										case 'expr':
											$argsToString[] = $node->at('parsed');
										break;
										default:
											$argsToString[] = $node->getData();
										break;
									}
								}
								$parsed = $name . '(' . join('', $argsToString) . ')';
								return array(false, null, $parsed, $V_FUNCTION, array('name' => $name, 'args' => $args));
							},
							$func
						),
						// TODO
						//self::ruleUNICODERANGE()
						self::hook(function ($r) use($V_STRING) {return array(false, $r, $r, $V_STRING, false);}, self::ruleSTRING()),
						self::hook(function ($r) use($V_IDENT) {return array(false, $r, $r, $V_IDENT, false);}, self::ruleIDENT()),
						self::hook(
							function ($r) use($V_UNKNOWN) {return array(false, $r, $r, $V_UNKNOWN, false);},
							self::join(self::many1(self::choice(self::join(self::ruleIDSEL()), '#', '%')))
						)
					)
				),
				array('operator', 'value', 'parsed', 'unit', 'function')
			);
			$o = self::choice(
				self::flatten($term, self::drop(self::synSpace()), self::ref($deep)),
				self::flatten($term, self::drop(self::synMaybeSpace()), new CSSParser_NodeCreater('combinator', self::choice(',', '/')), self::drop(self::synMaybeSpace()), self::ref($deep)),
				self::first(self::seq($term), self::synSpace()),
				self::seq($term)
			);
			$deep = self::memo(self::choice($o, $term));
		}
		return $o;
	}

	/**
	 * UnknownBlockRef
	 *
	 * @return PEG_IParser
	 */
	public static function synUnknownBlockRef()
	{
		static $o = null;
		if ($o === null) {
			$blockRef = self::choice(
				self::synComment(),
				self::ref($block),
				self::hook(
					create_function('$r', 'return $r === false ? PEG::failure() : $r;'),
					self::char('}', true)
				)
			);
			$block = self::seq(
				'{',
				self::many($blockRef),
				self::choice('}', self::eos())
			);
			$o = $blockRef;
		}

		return $o;
	}

	/**
	 * UnknownBlockRef
	 *
	 * @return PEG_IParser
	 */
	public static function synErrorBlock()
	{
		static $o = null;
		if ($o === null) {
			$blockRef = self::choice(
				self::synComment(),
				self::ref($block),
				self::hook(
					create_function('$r', 'return $r === false ? PEG::failure() : $r;'),
					self::char('}', true)
				)
			);
			$block = self::seq(
				'{',
				self::many($blockRef),
				'}'
			);
			$o = $blockRef;
		}

		return $o;
	}

	/**
	 * MediaQuery
	 *
	 * @return PEG_IParser
	 */
	public static function synMediaQuery()
	{
		static $o = null;
		if ($o === null) {
			$space = self::synSpace();
			$maybeSpace = self::synMaybeSpace();

			$restrictor = self::optional(
				new CSSParser_NodeCreater(
					'restrictor',
					self::join(
						self::choice(
							self::seq(self::char('nN'), self::char('oO'), self::char('tT')),
							self::seq(self::char('oO'), self::char('nN'), self::char('lL'), self::char('yY'))
						)
					)
				)
			);

			$maybeMediaValue = self::optional(
				self::third(
					':',
					$maybeSpace,
					new CSSParser_NodeCreater('value', self::synExpr()),
					$maybeSpace
				)
			);
			$mediaFeature = self::first(
				new CSSParser_NodeCreater('property', self::ruleIDENT()),
				$maybeSpace
			);

			$exp = new CSSParser_NodeCreater(
				'expression',
				self::seq(
					self::drop('(', $maybeSpace),
					$mediaFeature,
					self::drop($maybeSpace),
					$maybeMediaValue,
					self::drop(')', $maybeSpace)
				),
				array('property', 'value')
			);
			$mediaType = new CSSParser_NodeCreater(
				'mediaType',
				self::ruleIDENT()
			);

			$andQuery = self::first(
				self::many(
					self::third(
						self::ruleMEDIA_AND(),
						$space,
						$exp,
						$maybeSpace
					)
				),
				self::drop(self::choice(self::amp(';'), self::amp(self::eos())))
			);

			$mediaQuery = self::choice(
				new CSSParser_NodeCreater(
					'mediaQuery',
					self::seq(
						$restrictor,
						self::drop($maybeSpace),
						$mediaType,
						self::drop($maybeSpace),
						$andQuery
					),
					array('restrictor', 'mediaType', 'andQuery')
				),
				self::hook(
					create_function(
						'$str',
						'return $str instanceof PEG_Failure ? $str : array();'
					),
					self::amp(';')
				),
				new CSSParser_NodeCreater(
					'unknown',
					self::synError(self::synErrorInvalidBlock(), self::synErrorSemicolon(false))
				)
			);
			$o = $mediaQuery;
		}
		return $o;
	}

	//!{{{ selector

	public static function synSelectorsGroup()
	{
		static $o = null;
		if ($o === null) {
			$o = self::memo(self::hook(
				function ($r) {
					$res = array();
					$res[] = $r[0];
					$res = array_merge($res, $r[1]);
					return $res;
				},
				self::seq(
					self::synSelector(),
					self::many(self::third(
						',',
						self::synMaybeSpace(),
						self::synSelector()
					))
			)));
		}

		return $o;
	}

	public static function synSelector()
	{
		static $o = null;
		if ($o === null) {
			// mix commnet('/**/') and space(' ') -> return only one space
			// text : '/**/ /**/ /**/ ' -> retrun : ' '
			$space = self::memo(self::first(self::many1(self::second(
				self::many(self::ruleComment()),
				self::first(self::ruleWHITESPACE()),
				self::many(self::ruleComment())
			))));
			$o = self::memo(self::choice(
				self::flatten(self::synSimpleSelector(), new CSSParser_NodeCreater('combinator', $space, array('value')), self::ref($deep)),
				self::flatten(self::synSimpleSelector(), self::drop(self::synMaybeSpace()), self::synCombinator(), self::ref($deep)),
				self::first(self::synSimpleSelector(), self::ruleSpace()),
				self::synSimpleSelector()
			));
			$deep = self::memo(self::choice($o, self::synSimpleSelector()));
		}
		return $o;
	}

	public static function synCombinator()
	{
		static $o = null;
		return $o !== null ? $o : $o = new CSSParser_NodeCreater(
			'combinator',
			self::first(self::choice('+', '~', '>'), self::synMaybeSpace()),
			array('value')
		);
	}

	/**
	 * @return Array | PEG_Failure
	 */
	public static function synSimpleSelector()
	{
		static $o = null;
		return $o !== null ? $o : $o = self::choice(
			self::flatten(self::seq(
				self::choice(self::synUniversal(), self::synType()),
				self::synSpecifierGroup()
			)),
			self::seq(self::choice(self::synUniversal(), self::synType())),
			self::synSpecifierGroup()
		);
	}

	public static function synUniversal()
	{
		static $o = null;
		return $o !== null ? $o : $o = new CSSParser_NodeCreater(
			'universal',
			self::token('*'),
			array('value')
		);
	}

	public static function synType()
	{
		static $o = null;
		return $o !== null ? $o : $o = new CSSParser_NodeCreater(
			'type',
			self::seq(self::ruleIDENT()),
			array('value')
		);
	}

	public static function synSpecifierGroup()
	{
		static $o = null;
		return $o !== null ? $o : $o = self::many1(self::synSpecifier());
	}

	public static function synSpecifier()
	{
		static $o = null;
		return $o !== null ? $o : $o = self::choice(
			self::synId(),
			self::synClass(),
			self::synAttribute(),
			self::synPseudo()
		);
	}

	public static function synId()
	{
		static $o = null;
		return $o !== null ? $o : $o = new CSSParser_NodeCreater(
			'id',
			self::hook(function (Array $r) {return array($r[1]);}, self::ruleIDSEL()),
			array('value')
		);
	}

	public static function synClass()
	{
		static $o = null;
		return $o !== null ? $o : $o = new CSSParser_NodeCreater(
			'class',
			self::seq(self::drop('.'), self::ruleIDENT()),
			array('value')
		);
	}

	public static function synAttrName()
	{
		static $o = null;
		return $o !== null ? $o : $o = self::first(
			self::ruleIDENT(),
			self::synMaybeSpace()
		);
	}

	public static function synMatch()
	{
		static $o = null;
		return $o !== null ? $o : $o = self::choice(
			'=', '~=', '|=', '^=', '$=', '*='
		);
	}

	/**
	 * [title]
	 * array(
	 *   'value' => 'title',
	 *   'match' => ']',
	 *   'attribute' => null
	 * )
	 *
	 * [href^="https"]
	 * array(
	 *   'value' => 'href',
	 *   'match' => '^=',
	 *   'attribute' => '"https"'
	 * )
	 *
	 * @return PEG_IParser
	 */
	public static function synAttribute()
	{
		static $o = null;
		return $o !== null ? $o : $o = new CSSParser_NodeCreater(
			'attribute',
			self::hook(
				function (Array $r) {
					if (count($r) === 2) $r[] = null;
					return $r;
				},
				self::choice(
					self::seq(self::drop('[', self::synMaybeSpace()), self::synAttrName(), ']'),
					self::seq(self::drop('[', self::synMaybeSpace()), self::synAttrName(), self::synMatch(), self::drop(self::synMaybeSpace()), self::choice(self::ruleIDENT(), self::ruleSTRING()), self::drop(self::synMaybeSpace(), ']'))
					//TODO namespace selector
				)
			),
			array('value', 'match', 'attribute')
		);
	}

	/**
	 * CSSParser_Node::getData()
	 *
	 * array(
	 * 	'type' => 'pseudo',
	 * 	'value' => '<string>',
	 * 	'function' => <array> | <null>,
	 * 	'valid' => <boolean>
	 * )
	 *
	 * - 'valid' : false
	 *             unknown pseudo-class or pseudo-element,
	 *             limitation of negation pseudo-class : 「:not()」 nest, pseudo-elements, multi simple selectors
	 *
	 * @return PEG_IParser
	 */
	public static function synPseudo()
	{
		static $o = null;
		return $o !== null ? $o : $o = new CSSParser_NodeCreater(
			'pseudo',
			self::choice(
				self::synPseudoNot(),
				self::synPseudoEth(),
				self::synPseudoClass(),
				self::synPseudoElement()
			),
			array('type', 'value', 'function', 'valid')
		);
	}

	public static function synPseudoClass()
	{
		static $o = null;
		return $o !== null ? $o : $o = self::flatten(
			self::seq(
				':',
				self::choice(
					self::hook(
						function ($r) {return array($r , null, true);},
						self::choice(
							'root', 'first-child', 'last-child', 'first-of-type',
							'last-of-type', 'only-child', 'only-of-type', 'empty', 'link',
							'visited', 'active', 'hover', 'focus', 'target',
							'enabled', 'disabled', 'checked'
						)
					),
					self::hook(function ($r) {return array($r , null, false);}, self::ruleIDENT())
				)
			)
		);
	}

	public static function synPseudoElement()
	{
		static $o = null;
		return $o !== null ? $o : $o = self::flatten(
			self::seq(
				'::',
				self::choice(
					self::hook(
						function ($r) {return array($r , null, true);},
						self::choice('before', 'after', 'first-letter', 'first-line')
					),
					self::hook(function ($r) {return array($r , null, false);}, self::ruleIDENT())
				)
			)
		);
	}

	public static function synPseudoNot()
	{
		static $o = null;
		if ($o === null) {
			$parser = self::seq(
				':',
				self::token('not', false),
				self::drop('(', self::synMaybeSpace()),
				self::ref($simpleSelector),
				self::drop(self::synMaybeSpace(), ')')
			);
			$o = self::hook(
				function (Array $r) {
					if (count($r[2]) !== 1) {
						$r[3] = false;
					} elseif (isset($r[2][0])) {
						$node = $r[2][0];
						if ($node instanceof CSSParser_Node
							&& $node->getType() === 'pseudo'
							&& ($node->at('type') === '::' || $node->at('value') === 'not')
						) {
							$r[3] = false;
						}
					}
					if (!array_key_exists(3, $r)) $r[3] = true;
					return $r;
				},
				$parser
			);
			$simpleSelector = self::synSimpleSelector();
		}
		return $o;
	}

	public static function synPseudoEth()
	{
		static $o = null;
		return $o !== null ? $o : $o = self::flatten(
			self::seq(
				':',
				self::choice(
					// :nth-*()
					self::hook(
						function (Array $r) {return array($r[0], $r[1], true);},
						self::seq(
							self::choice('nth-child', 'nth-last-child', 'nth-of-type', 'nth-last-of-type'),
							self::drop('(', self::synMaybeSpace()),
							self::choice(
								self::ruleNTH(), // 2n+1
								self::join(self::seq(self::optional(self::choice('+', '-')), self::ruleINTEGER())), //+1
								self::choice('odd', 'even')
							)
						)
					),
					// :lang()
					self::hook(
						function (Array $r) {return array($r[0], $r[1], true);},
						self::seq(
							'lang',
							self::drop('(', self::synMaybeSpace()),
							self::ruleIDENT()
						)
					),
					// :FUNCTION()
					self::hook(
						function (Array $r) {return array($r[0], $r[1], false);},
						self::seq(
							self::first(self::ruleFUNCTION()),
							self::drop(self::synMaybeSpace()),
							self::ruleIDENT()
						)
					)
				),
				self::drop(self::synMaybeSpace(), ')')
			)
		);
	}

	//!}}} selector

	public static function synValue()
	{
		static $o = null;
		return $o !== null ? $o : $o = self::first(self::synExpr());
	}

	public static function synImportant()
	{
		static $o = null;
		return $o !== null ? $o : $o = self::first(self::ruleIMPORTANT_SYM(), self::synMaybeSpace());
	}

	public static function synAtRulePage()
	{
		static $o = null;
		if ($o === null) {
			//FIXME
		}
		return $o;
	}

	public static function synError(){
		return new CSSParser_Error(self::asParserArray(func_get_args()));
	}

	public static function synErrorSemicolon($includingSemicolon = true)
	{
		static $obj = null, $objNot = null;
		if ($includingSemicolon) {
			return $obj !== null ? $obj : $obj = self::_synErrorSemicolon($includingSemicolon);
		} else {
			return $objNot !== null ? $objNot : $objNot = self::_synErrorSemicolon($includingSemicolon);
		}
	}

	protected static function _synErrorSemicolon($includingSemicolon = true)
	{
		return self::join(
			self::seq(
				self::many1(CSSPEG::choice(
					self::ruleSTRING(), self::synInvalidBlock(), self::token('\{'), self::char(';', true))
				),
				$includingSemicolon === true ? ';' : ''
			)
		);
	}

	public static function synErrorInvalidBlock()
	{
		static $o = null;
		return $o !== null ? $o : $o = self::join(
			CSSPEG::seq(
				CSSPEG::many(CSSPEG::choice(CSSPEG::ruleSTRING(), CSSPEG::token('\{'), CSSPEG::char('{', true))),
				CSSPEG::synInvalidBlock()
			)
		);
	}

	public static function synInvalidBlock()
	{
		static $o = null;
		if ($o === null) {
			$blockRef = self::choice(
				self::synComment(),
				self::ruleSTRING(),
				self::token('\}'),
				self::ref($block),
				self::char('}', true)
			);
			$block = self::seq(
				'{',
				self::many($blockRef),
				self::choice('}', self::eos())
			);
			$o = $block;
		}

		return $o;
	}

}



