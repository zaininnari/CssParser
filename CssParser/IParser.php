<?php

interface IParser
{

	/**
	 * CSS文字列をパースして返す。
	 *
	 * @param string $css string
	 *
	 * 	array(
	 * 		'<spec>' => array(
	 * 			array(
	 * 				'expr' => '<selector>',
	 * 				'pair' => array(
	 * 					array(
	 * 						'property' => '<property>',
	 * 						'value' => '<value>'
	 * 					),
	 * 					array(
	 * 						'property' => '<property>',
	 * 						'value' => '<value>'
	 * 					),
	 * 				)
	 * 			)
	 * 		)
	 * 		'<spec>' => array(
	 * 			array(
	 * 				'expr' => '<selector>',
	 * 				'pair' => array(
	 * 					array(
	 * 						'property' => '<property>',
	 * 						'value' => '<value>'
	 * 					),
	 * 					array(
	 * 						'property' => '<property>',
	 * 						'value' => '<value>'
	 * 					),
	 * 				)
	 * 			)
	 * 		)
	 * 	)
	 *
	 * @return Array
	 */
	public function parse($css);

	/**
	 * エラーメッセージを取得
	 *
	 * @return array
	 */
	public function getError();

}