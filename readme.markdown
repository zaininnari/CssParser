css parser
===

CSS parser by Parsing Expression Grammer(解析表現文法)
---

@license MIT License


depend liblary
以下のライブラリに依存しています。

* document (ドキュメント)

    OpenpearPEG Documentation
    http://nimpad.jp/phppeg/

* source (ソース)

      http://openpear.org/package/PEG
* OpenpearPEG install (PEGのインストール)

        pear channel-discover openpear.org
        pear install openpear/PEG 


TODO
-----
* 引用符の文字列の回避
* IE bug:color gray bug

specifications (仕様)
---

* support vendor prefix
* support css hacks. (cssハックは現在未対応です。)
** only underscore hack and asterisk hack.
* セレクタ、プロパティ、値などに含まれるコメントは無視しません。
* 「text-decoration : underline/**/overline;」は適切な内容ですが、
   (「text-decoration : underline overline;」と解釈されます。)
   コメントを無視すると、「underlineoverline」という不明な値になってしまいます。
   パーサでは、コメント部分をそのまま返しますが、
   バリデータは適切に変換して処理します。


## example

#### css string

	@charset "UTF-8";
	@import url("style.css") screen , print;
	@font-face {
		src: local("Myfont"),url(Myfont.ttf);
	}
	@page :left {
		margin-left: 4cm;
	}
	@media screen {
		p {
			font-size: 16px;
		}
	}
	* #id.class>:link+:lang(ja) ,
	div:first-line[attr]:before:after
	{
		font-size: 16px;
		xxxxx-xxxx;       /* unknown */
	}    


#### parser
<pre class="xdebug-var-dump"><b>object</b>(<i>CssParser_Node</i>)[<i>973</i>]
  <i>protected</i> 'type' <font color="#888a85">=&gt;</font> <small>string</small> <font color="#cc0000">'root'</font> <i>(length=4)</i>
  <i>protected</i> 'offset' <font color="#888a85">=&gt;</font> <small>int</small> <font color="#4e9a06">0</font>
  <i>protected</i> 'data' <font color="#888a85">=&gt;</font> 
    <b>array</b>
      0 <font color="#888a85">=&gt;</font> 
        <b>object</b>(<i>CssParser_Node</i>)[<i>950</i>]
          <i>protected</i> 'type' <font color="#888a85">=&gt;</font> <small>string</small> <font color="#cc0000">'atRule'</font> <i>(length=6)</i>
          <i>protected</i> 'offset' <font color="#888a85">=&gt;</font> <small>int</small> <font color="#4e9a06">0</font>
          <i>protected</i> 'data' <font color="#888a85">=&gt;</font> 
            <b>array</b>
              'selector' <font color="#888a85">=&gt;</font> 
                <b>object</b>(<i>CssParser_Node</i>)[<i>948</i>]
                  <i>protected</i> 'type' <font color="#888a85">=&gt;</font> <small>string</small> <font color="#cc0000">'@charset'</font> <i>(length=8)</i>
                  <i>protected</i> 'offset' <font color="#888a85">=&gt;</font> <small>int</small> <font color="#4e9a06">0</font>
                  <i>protected</i> 'data' <font color="#888a85">=&gt;</font> <small>string</small> <font color="#cc0000">'@charset'</font> <i>(length=8)</i>
              'value' <font color="#888a85">=&gt;</font> 
                <b>object</b>(<i>CssParser_Node</i>)[<i>949</i>]
                  <i>protected</i> 'type' <font color="#888a85">=&gt;</font> <small>string</small> <font color="#cc0000">'value'</font> <i>(length=5)</i>
                  <i>protected</i> 'offset' <font color="#888a85">=&gt;</font> <small>int</small> <font color="#4e9a06">10</font>
                  <i>protected</i> 'data' <font color="#888a85">=&gt;</font> <small>string</small> <font color="#cc0000">'UTF-8'</font> <i>(length=5)</i>
      1 <font color="#888a85">=&gt;</font> 
        <b>object</b>(<i>CssParser_Node</i>)[<i>954</i>]
          <i>protected</i> 'type' <font color="#888a85">=&gt;</font> <small>string</small> <font color="#cc0000">'atRule'</font> <i>(length=6)</i>
          <i>protected</i> 'offset' <font color="#888a85">=&gt;</font> <small>int</small> <font color="#4e9a06">18</font>
          <i>protected</i> 'data' <font color="#888a85">=&gt;</font> 
            <b>array</b>
              'selector' <font color="#888a85">=&gt;</font> 
                <b>object</b>(<i>CssParser_Node</i>)[<i>951</i>]
                  <i>protected</i> 'type' <font color="#888a85">=&gt;</font> <small>string</small> <font color="#cc0000">'@import'</font> <i>(length=7)</i>
                  <i>protected</i> 'offset' <font color="#888a85">=&gt;</font> <small>int</small> <font color="#4e9a06">18</font>
                  <i>protected</i> 'data' <font color="#888a85">=&gt;</font> <small>string</small> <font color="#cc0000">'@import'</font> <i>(length=7)</i>
              'value' <font color="#888a85">=&gt;</font> 
                <b>object</b>(<i>CssParser_Node</i>)[<i>952</i>]
                  <i>protected</i> 'type' <font color="#888a85">=&gt;</font> <small>string</small> <font color="#cc0000">'value'</font> <i>(length=5)</i>
                  <i>protected</i> 'offset' <font color="#888a85">=&gt;</font> <small>int</small> <font color="#4e9a06">26</font>
                  <i>protected</i> 'data' <font color="#888a85">=&gt;</font> <small>string</small> <font color="#cc0000">'url("style.css")'</font> <i>(length=16)</i>
              'mediaType' <font color="#888a85">=&gt;</font> 
                <b>object</b>(<i>CssParser_Node</i>)[<i>953</i>]
                  <i>protected</i> 'type' <font color="#888a85">=&gt;</font> <small>string</small> <font color="#cc0000">'mediaType'</font> <i>(length=9)</i>
                  <i>protected</i> 'offset' <font color="#888a85">=&gt;</font> <small>int</small> <font color="#4e9a06">43</font>
                  <i>protected</i> 'data' <font color="#888a85">=&gt;</font> <small>string</small> <font color="#cc0000">'screen , print'</font> <i>(length=14)</i>
      2 <font color="#888a85">=&gt;</font> 
        <b>object</b>(<i>CssParser_Node</i>)[<i>958</i>]
          <i>protected</i> 'type' <font color="#888a85">=&gt;</font> <small>string</small> <font color="#cc0000">'atRule'</font> <i>(length=6)</i>
          <i>protected</i> 'offset' <font color="#888a85">=&gt;</font> <small>int</small> <font color="#4e9a06">59</font>
          <i>protected</i> 'data' <font color="#888a85">=&gt;</font> 
            <b>array</b>
              'selector' <font color="#888a85">=&gt;</font> 
                <b>object</b>(<i>CssParser_Node</i>)[<i>955</i>]
                  <i>protected</i> 'type' <font color="#888a85">=&gt;</font> <small>string</small> <font color="#cc0000">'@font-face'</font> <i>(length=10)</i>
                  <i>protected</i> 'offset' <font color="#888a85">=&gt;</font> <small>int</small> <font color="#4e9a06">59</font>
                  <i>protected</i> 'data' <font color="#888a85">=&gt;</font> <small>string</small> <font color="#cc0000">'@font-face'</font> <i>(length=10)</i>
              'block' <font color="#888a85">=&gt;</font> 
                <b>array</b>
                  0 <font color="#888a85">=&gt;</font> 
                    <b>array</b>
                      'property' <font color="#888a85">=&gt;</font> 
                        <b>object</b>(<i>CssParser_Node</i>)[<i>956</i>]
                          <i>protected</i> 'type' <font color="#888a85">=&gt;</font> <small>string</small> <font color="#cc0000">'property'</font> <i>(length=8)</i>
                          <i>protected</i> 'offset' <font color="#888a85">=&gt;</font> <small>int</small> <font color="#4e9a06">73</font>
                          <i>protected</i> 'data' <font color="#888a85">=&gt;</font> <small>string</small> <font color="#cc0000">'src'</font> <i>(length=3)</i>
                      'value' <font color="#888a85">=&gt;</font> 
                        <b>object</b>(<i>CssParser_Node</i>)[<i>957</i>]
                          <i>protected</i> 'type' <font color="#888a85">=&gt;</font> <small>string</small> <font color="#cc0000">'value'</font> <i>(length=5)</i>
                          <i>protected</i> 'offset' <font color="#888a85">=&gt;</font> <small>int</small> <font color="#4e9a06">78</font>
                          <i>protected</i> 'data' <font color="#888a85">=&gt;</font> <small>string</small> <font color="#cc0000">'local("Myfont"),url(Myfont.ttf)'</font> <i>(length=31)</i>
                      'isImportant' <font color="#888a85">=&gt;</font> <small>boolean</small> <font color="#75507b">false</font>
      3 <font color="#888a85">=&gt;</font> 
        <b>object</b>(<i>CssParser_Node</i>)[<i>962</i>]
          <i>protected</i> 'type' <font color="#888a85">=&gt;</font> <small>string</small> <font color="#cc0000">'atRule'</font> <i>(length=6)</i>
          <i>protected</i> 'offset' <font color="#888a85">=&gt;</font> <small>int</small> <font color="#4e9a06">113</font>
          <i>protected</i> 'data' <font color="#888a85">=&gt;</font> 
            <b>array</b>
              'selector' <font color="#888a85">=&gt;</font> 
                <b>object</b>(<i>CssParser_Node</i>)[<i>959</i>]
                  <i>protected</i> 'type' <font color="#888a85">=&gt;</font> <small>string</small> <font color="#cc0000">'@page'</font> <i>(length=5)</i>
                  <i>protected</i> 'offset' <font color="#888a85">=&gt;</font> <small>int</small> <font color="#4e9a06">113</font>
                  <i>protected</i> 'data' <font color="#888a85">=&gt;</font> <small>string</small> <font color="#cc0000">'@page :left'</font> <i>(length=11)</i>
              'block' <font color="#888a85">=&gt;</font> 
                <b>array</b>
                  0 <font color="#888a85">=&gt;</font> 
                    <b>array</b>
                      'property' <font color="#888a85">=&gt;</font> 
                        <b>object</b>(<i>CssParser_Node</i>)[<i>960</i>]
                          <i>protected</i> 'type' <font color="#888a85">=&gt;</font> <small>string</small> <font color="#cc0000">'property'</font> <i>(length=8)</i>
                          <i>protected</i> 'offset' <font color="#888a85">=&gt;</font> <small>int</small> <font color="#4e9a06">128</font>
                          <i>protected</i> 'data' <font color="#888a85">=&gt;</font> <small>string</small> <font color="#cc0000">'margin-left'</font> <i>(length=11)</i>
                      'value' <font color="#888a85">=&gt;</font> 
                        <b>object</b>(<i>CssParser_Node</i>)[<i>961</i>]
                          <i>protected</i> 'type' <font color="#888a85">=&gt;</font> <small>string</small> <font color="#cc0000">'value'</font> <i>(length=5)</i>
                          <i>protected</i> 'offset' <font color="#888a85">=&gt;</font> <small>int</small> <font color="#4e9a06">141</font>
                          <i>protected</i> 'data' <font color="#888a85">=&gt;</font> <small>string</small> <font color="#cc0000">'4cm'</font> <i>(length=3)</i>
                      'isImportant' <font color="#888a85">=&gt;</font> <small>boolean</small> <font color="#75507b">false</font>
      4 <font color="#888a85">=&gt;</font> 
        <b>object</b>(<i>CssParser_Node</i>)[<i>970</i>]
          <i>protected</i> 'type' <font color="#888a85">=&gt;</font> <small>string</small> <font color="#cc0000">'atRule'</font> <i>(length=6)</i>
          <i>protected</i> 'offset' <font color="#888a85">=&gt;</font> <small>int</small> <font color="#4e9a06">148</font>
          <i>protected</i> 'data' <font color="#888a85">=&gt;</font> 
            <b>array</b>
              'selector' <font color="#888a85">=&gt;</font> 
                <b>object</b>(<i>CssParser_Node</i>)[<i>963</i>]
                  <i>protected</i> 'type' <font color="#888a85">=&gt;</font> <small>string</small> <font color="#cc0000">'@media'</font> <i>(length=6)</i>
                  <i>protected</i> 'offset' <font color="#888a85">=&gt;</font> <small>int</small> <font color="#4e9a06">148</font>
                  <i>protected</i> 'data' <font color="#888a85">=&gt;</font> <small>string</small> <font color="#cc0000">'@media screen'</font> <i>(length=13)</i>
              'value' <font color="#888a85">=&gt;</font> 
                <b>array</b>
                  0 <font color="#888a85">=&gt;</font> 
                    <b>array</b>
                      'selector' <font color="#888a85">=&gt;</font> 
                        <b>object</b>(<i>CssParser_Node</i>)[<i>967</i>]
                          <i>protected</i> 'type' <font color="#888a85">=&gt;</font> <small>string</small> <font color="#cc0000">'selector'</font> <i>(length=8)</i>
                          <i>protected</i> 'offset' <font color="#888a85">=&gt;</font> <small>int</small> <font color="#4e9a06">165</font>
                          <i>protected</i> 'data' <font color="#888a85">=&gt;</font> <small>string</small> <font color="#cc0000">'p'</font> <i>(length=1)</i>
                      'block' <font color="#888a85">=&gt;</font> 
                        <b>array</b>
                          0 <font color="#888a85">=&gt;</font> 
                            <b>array</b>
                              'property' <font color="#888a85">=&gt;</font> 
                                <b>object</b>(<i>CssParser_Node</i>)[<i>968</i>]
                                  <i>protected</i> 'type' <font color="#888a85">=&gt;</font> <small>string</small> <font color="#cc0000">'property'</font> <i>(length=8)</i>
                                  <i>protected</i> 'offset' <font color="#888a85">=&gt;</font> <small>int</small> <font color="#4e9a06">171</font>
                                  <i>protected</i> 'data' <font color="#888a85">=&gt;</font> <small>string</small> <font color="#cc0000">'font-size'</font> <i>(length=9)</i>
                              'value' <font color="#888a85">=&gt;</font> 
                                <b>object</b>(<i>CssParser_Node</i>)[<i>969</i>]
                                  <i>protected</i> 'type' <font color="#888a85">=&gt;</font> <small>string</small> <font color="#cc0000">'value'</font> <i>(length=5)</i>
                                  <i>protected</i> 'offset' <font color="#888a85">=&gt;</font> <small>int</small> <font color="#4e9a06">182</font>
                                  <i>protected</i> 'data' <font color="#888a85">=&gt;</font> <small>string</small> <font color="#cc0000">'16px'</font> <i>(length=4)</i>
                              'isImportant' <font color="#888a85">=&gt;</font> <small>boolean</small> <font color="#75507b">false</font>
      5 <font color="#888a85">=&gt;</font> 
        <b>object</b>(<i>CssParser_Node</i>)[<i>972</i>]
          <i>protected</i> 'type' <font color="#888a85">=&gt;</font> <small>string</small> <font color="#cc0000">'ruleSet'</font> <i>(length=7)</i>
          <i>protected</i> 'offset' <font color="#888a85">=&gt;</font> <small>int</small> <font color="#4e9a06">193</font>
          <i>protected</i> 'data' <font color="#888a85">=&gt;</font> 
            <b>array</b>
              'selector' <font color="#888a85">=&gt;</font> 
                <b>object</b>(<i>CssParser_Node</i>)[<i>964</i>]
                  <i>protected</i> 'type' <font color="#888a85">=&gt;</font> <small>string</small> <font color="#cc0000">'selector'</font> <i>(length=8)</i>
                  <i>protected</i> 'offset' <font color="#888a85">=&gt;</font> <small>int</small> <font color="#4e9a06">193</font>
                  <i>protected</i> 'data' <font color="#888a85">=&gt;</font> <small>string</small> <font color="#cc0000">'* #id.class&gt;:link+:lang(ja) ,
div:first-line[attr]:before:after'</font> <i>(length=63)</i>
              'block' <font color="#888a85">=&gt;</font> 
                <b>array</b>
                  0 <font color="#888a85">=&gt;</font> 
                    <b>array</b>
                      'property' <font color="#888a85">=&gt;</font> 
                        <b>object</b>(<i>CssParser_Node</i>)[<i>965</i>]
                          <i>protected</i> 'type' <font color="#888a85">=&gt;</font> <small>string</small> <font color="#cc0000">'property'</font> <i>(length=8)</i>
                          <i>protected</i> 'offset' <font color="#888a85">=&gt;</font> <small>int</small> <font color="#4e9a06">260</font>
                          <i>protected</i> 'data' <font color="#888a85">=&gt;</font> <small>string</small> <font color="#cc0000">'font-size'</font> <i>(length=9)</i>
                      'value' <font color="#888a85">=&gt;</font> 
                        <b>object</b>(<i>CssParser_Node</i>)[<i>966</i>]
                          <i>protected</i> 'type' <font color="#888a85">=&gt;</font> <small>string</small> <font color="#cc0000">'value'</font> <i>(length=5)</i>
                          <i>protected</i> 'offset' <font color="#888a85">=&gt;</font> <small>int</small> <font color="#4e9a06">271</font>
                          <i>protected</i> 'data' <font color="#888a85">=&gt;</font> <small>string</small> <font color="#cc0000">'16px'</font> <i>(length=4)</i>
                      'isImportant' <font color="#888a85">=&gt;</font> <small>boolean</small> <font color="#75507b">false</font>
                  1 <font color="#888a85">=&gt;</font> 
                    <b>object</b>(<i>CssParser_Node</i>)[<i>971</i>]
                      <i>protected</i> 'type' <font color="#888a85">=&gt;</font> <small>string</small> <font color="#cc0000">'unknown'</font> <i>(length=7)</i>
                      <i>protected</i> 'offset' <font color="#888a85">=&gt;</font> <small>int</small> <font color="#4e9a06">278</font>
                      <i>protected</i> 'data' <font color="#888a85">=&gt;</font> <small>string</small> <font color="#cc0000">'xxxxx-xxxx;       /* 宣言の形を成していないもの */
'</font> <i>(length=64)</i>

</pre>


### other
#### PEAR CodeSniffer
* PEAR を基準

以下を除外

    PEAR.NamingConventions.ValidFunctionName
    PEAR.Commenting.FileCommentSniff


    --tab-width=4 -v -n --sniffs=PEAR.Files.LineLength,PEAR.Files.IncludingFile,PEAR.Files.LineEndings,PEAR.Classes.ClassDeclaration,PEAR.WhiteSpace.ScopeClosingBrace,PEAR.WhiteSpace.ScopeIndent,PEAR.WhiteSpace.ObjectOperatorIndent,PEAR.Commenting.FunctionComment,PEAR.Commenting.InlineComment,PEAR.Functions.FunctionCallSignature,PEAR.Functions.FunctionCallArgumentSpacing,PEAR.Functions.FunctionDeclaration,PEAR.Functions.ValidDefaultValue,PEAR.ControlStructures.InlineControlStructure,PEAR.ControlStructures.ControlSignature,PEAR.ControlStructures.MultiLineCondition,PEAR.Formatting.MultiLineAssignment,PEAR.NamingConventions.ValidClassName,PEAR.NamingConventions.ValidVariableName ${resource_loc}
