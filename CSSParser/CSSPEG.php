<?php
/**
 * PHP CSS Def and Rule
 *
 * PHP 5.2 or higher is required.
 *
 * @license MIT License
 *
 * === BEGIN ===
 * PHP PEG Parser Combinator
 * http://openpear.org/package/PEG
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 * @author anatoo<anatoo@nequal.jp>
 * ===  END  ===a
 *
 */

require_once 'PEG.php';

require_once dirname(__FILE__) . '/NonAscii.php';

class CSSPEG extends PEG
{

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
		return $o !== null ? $o : self::join(
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
		return $o !== null ? $o : self::choice(
			self::alphabet(),
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
		return $o !== null ? $o : self::choice(
			self::alphabet(),
			self::digit(),
			'-',
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
	 * hexcolor		{h}{3}|{h}{6}
	 *
	 * @return PEG_IParser
	 */
	protected static function defHexcolor()
	{
		static $o = null;
		return $o !== null ? $o : self::choice(
			self::join(
				self::seq(
					self::defH(), self::defH(), self::defH(),
					self::optional(
						self::seq(
							self::defH(), self::defH(), self::defH()
						)
					)
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
		return $o !== null ? $o : self::join(
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
		return $o !== null ? $o : self::join(
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
		return $o !== null ? $o : self::choice(
			self::join(
				self::many1(self::digit())
			),
			self::join(
				self::seq(
					self::many(self::digit()),
					'.',
					self::many1(self::digit())
				)
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
		return $o !== null ? $o : self::choice(
			self::join(
				self::many1(self::digit())
			)
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
		return $o !== null ? $o : self::join(
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
		return $o !== null ? $o : self::hook(
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
		return $o !== null ? $o : self::join(
			self::seq(
				self::optional('+', '-'),
				self::many(
					self::defIntnum()
				),
				'n',
				self::optional(
					self::seq(
						self::choice('+', '-'),
						self::defIntnum()
					)
				)
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
		return $o !== null ? $o : self::join(
			self::many1(
				self::defWhiteSpace()
			)
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
		return $o !== null ? $o : self::choice('*=', '$=', '^=', '|=', '~=');
	}

	/**
	 * <mediaquery>"not"       {yyTok = MEDIA_NOT; return yyTok;}
	 *
	 * @return PEG_IParser
	 */
	public static function ruleMEDIA_NOT()
	{
		static $o = null;
		return $o !== null ? $o : self::token('not');
	}

	/**
	 * <mediaquery>"only"      {yyTok = MEDIA_ONLY; return yyTok;}
	 *
	 * @return PEG_IParser
	 */
	public static function ruleMEDIA_ONLY()
	{
		static $o = null;
		return $o !== null ? $o : self::token('only');
	}

	/**
	 * <mediaquery>"and"       {yyTok = MEDIA_AND; return yyTok;}
	 *
	 * @return PEG_IParser
	 */
	public static function ruleMEDIA_AND()
	{
		static $o = null;
		return $o !== null ? $o : self::token('and');
	}

	/**
	 * <forkeyword>"for"       {BEGIN(mediaquery); yyTok = VARIABLES_FOR; return yyTok; }
	 *
	 * @return PEG_IParser
	 */
	public static function ruleVARIABLES_FOR()
	{
		static $o = null;
		return $o !== null ? $o : self::token('for');
	}

	/**
	 * {string}                {yyTok = STRING; return yyTok;}
	 *
	 * @return PEG_IParser
	 */
	public static function ruleSTRING()
	{
		static $o = null;
		return $o !== null ? $o : self::defString();
	}

	/**
	 * {ident}                 {yyTok = IDENT; return yyTok;}
	 *
	 * @return PEG_IParser
	 */
	public static function ruleIDENT()
	{
		static $o = null;
		return $o !== null ? $o : self::defIdent();
	}

	/**
	 * {nth}                   {yyTok = NTH; return yyTok;}
	 *
	 * @return PEG_IParser
	 */
	public static function ruleNTH()
	{
		static $o = null;
		return $o !== null ? $o : self::defNth();
	}

	/**
	 * "#"{hexcolor}           {yyTok = HEX; return yyTok;}
	 *
	 * @return PEG_IParser
	 */
	public static function ruleHEX()
	{
		static $o = null;
		return $o !== null ? $o : self::join(
			self::seq(
				'#',
				self::defHexcolor()
			)
		);
	}

	/**
	 * "#"{ident}              {yyTok = IDSEL; return yyTok;}
	 *
	 * @return PEG_IParser
	 */
	public static function ruleIDSEL()
	{
		static $o = null;
		return $o !== null ? $o : self::join(
			self::seq(
				'#',
				self::defIdent()
			)
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
		return $o !== null ? $o : self::token('@import');
	}

	/**
	 * "@page"                 {yyTok = PAGE_SYM; return yyTok;}
	 *
	 * @return PEG_IParser
	 */
	public static function rulePAGE_SYM()
	{
		static $o = null;
		return $o !== null ? $o : self::token('@page');
	}

	/**
	 * "@media"                {BEGIN(mediaquery); yyTok = MEDIA_SYM; return yyTok;}
	 *
	 * @return PEG_IParser
	 */
	public static function ruleMEDIA_SYM()
	{
		static $o = null;
		return $o !== null ? $o : self::token('@media');
	}

	/**
	 * "@font-face"            {yyTok = FONT_FACE_SYM; return yyTok;}
	 *
	 * @return PEG_IParser
	 */
	public static function ruleFONT_FACE_SYM()
	{
		static $o = null;
		return $o !== null ? $o : self::token('@font-face');
	}

	/**
	 * "@charset"              {yyTok = CHARSET_SYM; return yyTok;}
	 *
	 * @return PEG_IParser
	 */
	public static function ruleCHARSET_SYM()
	{
		static $o = null;
		return $o !== null ? $o : self::token('@charset');
	}

	/**
	 * "@namespace"            {yyTok = NAMESPACE_SYM; return yyTok; }
	 *
	 * @return PEG_IParser
	 */
	public static function ruleNAMESPACE_SYM()
	{
		static $o = null;
		return $o !== null ? $o : self::token('@namespace');
	}

	/**
	 * "@"{ident}              {yyTok = ATKEYWORD; return yyTok; }
	 *
	 * @return PEG_IParser
	 */
	public static function ruleATKEYWORD()
	{
		static $o = null;
		return $o !== null ? $o : self::join(
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
		return $o !== null ? $o : self::join(
			self::seq(
				'!',
				self::defW(),
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
		return $o !== null ? $o : self::join(
			self::seq(
				self::defNum(),
				self::choice(
					'kHz',
					'Hz',
					's',
					'ms',
					'turn',
					'grad',
					'rad',
					'deg',
					'pc',
					'pt',
					'in',
					'mm',
					'cm',
					'px',
					'ex',
					'__qem',
					'rem',
					'em'
				)
			)
		);
	}

	/**
	 * {num}{ident}            {yyTok = DIMEN; return yyTok;}
	 *
	 * @return PEG_IParser
	 */
	public static function ruleDIMEN()
	{
		static $o = null;
		return $o !== null ? $o : self::join(
			self::seq(
				self::defNum(),
				self::defIdent()
			)
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
		return $o !== null ? $o : self::join(
			self::seq(
				self::defNum(),
				self::many1('%')
			)
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
		return $o !== null ? $o : self::defIntnum();
	}

	/**
	 * {num}                   {yyTok = FLOATTOKEN; return yyTok;}
	 *
	 * @return PEG_IParser
	 */
	public static function ruleFLOATTOKEN()
	{
		static $o = null;
		return $o !== null ? $o : self::defNum();
	}

	/**
	 * "not("                  {yyTok = NOTFUNCTION; return yyTok;}
	 *
	 * @return PEG_IParser
	 */
	public static function ruleNOTFUNCTION()
	{
		static $o = null;
		return $o !== null ? $o : self::token('not(');
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
		return $o !== null ? $o : self::choice(
			self::join(
				self::seq(
					'url(',
					self::drop(self::defW()),
					self::defUrl(),
					self::drop(self::defW()),
					')'
				)
			),
			self::join(
				self::seq(
					'url(',
					self::drop(self::defW()),
					self::ruleSTRING(),
					self::drop(self::defW()),
					')'
				)
			)
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
		return $o !== null ? $o : self::join(
			self::seq(
				self::defIdent(),
				'('
			)
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
		return $o !== null ? $o : self::choice(
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
		return $o !== null ? $o : self::seq(
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
		return $o !== null ? $o : self::choice(
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
		return $o !== null ? $o : self::ruleComment();
	}

	/**
	 * space
	 *
	 * @return PEG_IParser
	 */
	public static function synSpace()
	{
		static $o = null;
		return $o !== null ? $o : self::many1(self::ruleSpace());
	}

	/**
	 * maybe space
	 *
	 * @return PEG_IParser
	 */
	public static function synMaybeSpace()
	{
		static $o = null;
		return $o !== null ? $o : self::many(self::ruleSpace());
	}


	/**
	 * expr
	 *
	 * @return PEG_IParser
	 */
	public static function synExpr()
	{
		static $o = null;
		return $o !== null ? $o : self::choice(
			self::ruleUnit(),
			self::rulePERCENTAGE(),
			self::ruleINTEGER()
		);
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
					create_function('$r', 'return $r === false ? CSSPEG::failure() : $r;'),
					self::char('}', true)
				)
			);
			$block = self::seq(
				'{',
				self::many($blockRef),
				'}'
			);
		}

		return $blockRef;
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
			$comment = self::synComment();
			$space = self::synSpace();
			$maybeSpace = self::synMaybeSpace();
			// http://www.w3.org/TR/css3-syntax/#error-handling
			//   while observing the rules for matching pairs of
			//   (), [], {}, "", and '', and correctly handling escapes
			$errorBlock = self::choice(
				self::seq('"', self::many(self::choice('\"', self::char('"', true))), '"'),
				self::seq("'", self::many(self::choice("\'", self::char("'", true))), "'"),
				self::seq('{', self::many(self::choice('\}', '\{', self::char('{}', true))), '}'),
				self::seq('(', self::many(self::choice('\)', self::char(')', true))), ')'),
				self::seq('[', self::many(self::choice('\]', self::char(']', true))), ']')
			);

			$errorTokenBlock = self::seq(
				'{',
				self::seq(
					self::optional(self::many(self::char('{}', true))),
					self::optional(self::ref($errorBrace)),
					self::optional(self::many(self::char('{}', true)))
				),
				'}'
			);
			$errorBrace = self::choice(
				$comment,
				$errorBlock,
				'\;',
				'\{',
				'\}',
				self::many1(self::char(';{', true)),
				$errorTokenBlock,
				self::eos()
			);

			$errorToken = self::choice(
				self::amp(';'),
				$errorBrace
			);

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

			$andQuery = self::choice(
				self::hook(
					create_function(
						'Array $arr',
						'
						$result = array();
						if (empty($arr[0])
							&& $arr[1] instanceof CSSParser_Node
							&& $arr[1]->getData() === ";"
						) return array();
						if (empty($arr[0])
							&& $arr[1] === false
						) return CSSPEG::failure();

						if (!empty($arr[0])) $result = $arr[0];
						if (!empty($arr[1])) $result[] = $arr[1];
						return $result;
						'
					),
					self::seq(
						self::many(
							self::third(
								self::ruleMEDIA_AND(),
								$space,
								$exp,
								$maybeSpace
							)
						),
						self::optional(
							new CSSParser_NodeCreater(
								'unknown',
								self::andalso(
									self::not(self::eos()),
									self::join(self::seq($errorToken))
								)
							)
						)
					)
				),
				self::seq(
					new CSSParser_NodeCreater(
						'unknown',
						self::andalso(
							self::not(self::eos()),
							self::join(self::many(self::anything()))
						)
					)
				)
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
					self::join(self::seq($errorToken))
				)
			);
			$o = $mediaQuery;
		}
		return $o;
	}

}
