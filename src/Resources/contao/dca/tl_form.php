<?php

declare(strict_types=1);

use Contao\CoreBundle\DataContainer\PaletteManipulator;

// Selector + Subpalette
$GLOBALS['TL_DCA']['tl_form']['palettes']['__selector__'][] = 'lumas_antispam_enable';

$GLOBALS['TL_DCA']['tl_form']['subpalettes']['lumas_antispam_enable']
	= 'lumas_antispam_ip_section_info,lumas_antispam_ip_block'
	. ',lumas_antispam_time_section_info,lumas_antispam_minDelay,lumas_antispam_blockTime'
	. ',lumas_antispam_text_section_info,lumas_antispam_language,lumas_antispam_stopwordCount,lumas_antispam_maxLinks,lumas_antispam_minLen';

// ---------------------------------------------------------------------
// Info-Abschnitte (keine DB-Felder)
// ---------------------------------------------------------------------

$GLOBALS['TL_DCA']['tl_form']['fields']['lumas_antispam_ip_section_info'] = [
	'input_field_callback' => static function (): string {
		return '<div class="clr" style="padding-top:10px; clear:both;">
			<div style="background:#444; color:#fff; padding:8px 12px; border-left:4px solid #3498db; font-weight:bold;">IP-Reputation &amp; Sicherheit</div>
		</div>';
	},
	'eval' => ['doNotCopy' => true, 'tl_class' => 'clr'],
	'sql'  => null,
];

$GLOBALS['TL_DCA']['tl_form']['fields']['lumas_antispam_time_section_info'] = [
	'input_field_callback' => static function (): string {
		return '<div class="clr" style="padding-top:30px; clear:both;">
			<div style="background:#444; color:#fff; padding:8px 12px; border-left:4px solid #3498db; font-weight:bold;">Zeitbasierte Heuristik</div>
		</div>';
	},
	'eval' => ['doNotCopy' => true, 'tl_class' => 'clr'],
	'sql'  => null,
];

$GLOBALS['TL_DCA']['tl_form']['fields']['lumas_antispam_text_section_info'] = [
	'input_field_callback' => static function (): string {
		return '<div class="clr" style="padding-top:30px; clear:both;">
			<div style="background:#444; color:#fff; padding:8px 12px; border-left:4px solid #3498db; font-weight:bold;">Textanalyse &amp; Inhaltsprüfung</div>
		</div>';
	},
	'eval' => ['doNotCopy' => true, 'tl_class' => 'clr'],
	'sql'  => null,
];

// ---------------------------------------------------------------------
// Reguläre Felder (DB)
// ---------------------------------------------------------------------

$GLOBALS['TL_DCA']['tl_form']['fields']['lumas_antispam_enable'] = [
	'label'     => &$GLOBALS['TL_LANG']['tl_form']['lumas_antispam_enable'],
	'inputType' => 'checkbox',
	'eval'      => ['submitOnChange' => true, 'tl_class' => 'w50 m12'],
	'sql'       => "char(1) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_form']['fields']['lumas_antispam_ip_block'] = [
	'label'     => &$GLOBALS['TL_LANG']['tl_form']['lumas_antispam_ip_block'],
	'inputType' => 'checkbox',
	'eval'      => ['tl_class' => 'w50 m12'],
	'sql'       => "char(1) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_form']['fields']['lumas_antispam_language'] = [
	'label'     => &$GLOBALS['TL_LANG']['tl_form']['lumas_antispam_language'],
	'inputType' => 'select',
	'options'   => ['de', 'en', 'fr', 'es', 'it'],
	'reference' => &$GLOBALS['TL_LANG']['tl_form']['lumas_antispam_languages'],
	'eval'      => [
		'tl_class'            => 'w50',
		'includeBlankOption'  => true,
		'chosen'              => true,
		'blankOptionLabel'    => 'Standard (aus Startpunkt)',
	],
	'sql'       => "varchar(2) NOT NULL default ''",
];

// Numerische Overrides: NULL bedeutet "kein Override" -> Default aus Root/Defaults
$numericSettings = [
	'minDelay'      => 15,
	'blockTime'     => 30,
	'stopwordCount' => 2,
	'maxLinks'      => 1,
	'minLen'        => 15,
];

foreach ($numericSettings as $field => $placeholder) {
	$GLOBALS['TL_DCA']['tl_form']['fields']['lumas_antispam_' . $field] = [
		'label'     => &$GLOBALS['TL_LANG']['tl_form']['lumas_antispam_' . $field],
		'inputType' => 'text',
		'eval'      => [
			'rgxp'        => 'digit',
			'tl_class'    => 'w50',
			'nospace'     => true,
			'placeholder' => (string) $placeholder,
		],
		'sql'       => "int(10) unsigned NULL",
	];
}

// ---------------------------------------------------------------------
// Palette: Legend + Enable Feld einfügen
// ---------------------------------------------------------------------

PaletteManipulator::create()
	->addLegend('lumas_antispam_legend', 'title_legend', PaletteManipulator::POSITION_AFTER)
	->addField('lumas_antispam_enable', 'lumas_antispam_legend', PaletteManipulator::POSITION_APPEND)
	->applyToPalette('default', 'tl_form');
