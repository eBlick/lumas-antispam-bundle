<?php

declare(strict_types=1);

use Contao\DC_Table;
use Contao\DataContainer;

$GLOBALS['TL_DCA']['tl_lumas_antispam_log'] = [
	'config' => [
		'dataContainer' => DC_Table::class,
		'closed'        => true,

		// wirklich read-only
		'notEditable'   => true,
		'notCopyable'   => true,
		'notSortable'   => true,

		'sql' => [
			'keys' => [
				'id'         => 'primary',
				'tstamp'     => 'index',
				'ip_address' => 'index',
				'reason'     => 'index',
				'form_alias' => 'index',
			],
		],
	],

	'list' => [
		'sorting' => [
			'mode'        => DataContainer::MODE_SORTABLE,
			'fields'      => ['tstamp DESC'],
			'flag'        => DataContainer::SORT_DAY_DESC,
			'panelLayout' => 'filter;search,limit',
		],

		'label' => [
			'fields'      => ['tstamp', 'ip_address', 'reason', 'form_alias'],
			'showColumns' => true,
		],

		'global_operations' => [
			'all' => [
				'href'       => 'act=select',
				'class'      => 'header_edit_all',
				'attributes' => 'onclick="Backend.getScrollOffset()"',
			],
		],

		'operations' => [
			'show' => [
				'href' => 'act=show',
				'icon' => 'show.svg',
			],

			// Empfehlung: Logs nicht löschbar machen.
			// Wenn du es doch willst, kommentiere das nicht aus.
			/*
			'delete' => [
				'href' => 'act=delete',
				'icon' => 'delete.svg',
			],
			*/
		],
	],

	'palettes' => [
		'default' => '{log_legend},tstamp,ip_address,reason,form_alias,details',
	],

	'fields' => [
		'id' => [
			'sql' => "int(10) unsigned NOT NULL auto_increment",
		],

		'tstamp' => [
			'label'     => &$GLOBALS['TL_LANG']['tl_lumas_antispam_log']['tstamp'],
			'sorting'   => true,
			'flag'      => DataContainer::SORT_DAY_DESC,
			'inputType' => 'text',
			'eval'      => [
				'rgxp'     => 'datim',
				'readonly' => true,
				'tl_class' => 'w50',
			],
			'sql'       => "int(10) unsigned NOT NULL default '0'",
		],

		'ip_address' => [
			'label'     => &$GLOBALS['TL_LANG']['tl_lumas_antispam_log']['ip_address'],
			'search'    => true,
			'filter'    => true,
			'inputType' => 'text',
			'eval'      => [
				'readonly'  => true,
				'maxlength' => 45,
				'tl_class'  => 'w50',
			],
			'sql'       => "varchar(45) NOT NULL default ''",
		],

		'reason' => [
			'label'     => &$GLOBALS['TL_LANG']['tl_lumas_antispam_log']['reason'],
			'filter'    => true,
			'search'    => true,
			'inputType' => 'text',
			'eval'      => [
				'readonly'  => true,
				'maxlength' => 64,
				'tl_class'  => 'w50',
			],
			'sql'       => "varchar(64) NOT NULL default ''",
		],

		'form_alias' => [
			'label'     => &$GLOBALS['TL_LANG']['tl_lumas_antispam_log']['form_alias'],
			'filter'    => true,
			'search'    => true,
			'inputType' => 'text',
			'eval'      => [
				'readonly'  => true,
				'maxlength' => 128,
				'tl_class'  => 'w50',
			],
			'sql'       => "varchar(128) NOT NULL default ''",
		],

		'details' => [
			'label'     => &$GLOBALS['TL_LANG']['tl_lumas_antispam_log']['details'],
			'inputType' => 'textarea',
			'eval'      => [
				'readonly' => true,
				'rows'     => 12,
				'tl_class' => 'clr long',
			],
			'sql'       => "text NULL",
		],
	],
];
