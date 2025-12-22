<?php

declare(strict_types=1);

use Contao\DC_Table;
use Contao\DataContainer;

$GLOBALS['TL_DCA']['tl_lumas_antispam_ip_block'] = [
	'config' => [
		'dataContainer'    => DC_Table::class,
		'enableVersioning' => true,
		'sql'              => [
			'keys' => [
				'id'         => 'primary',
				'ip_address' => 'unique',
			],
		],
	],

	'list' => [
		'sorting' => [
			'mode'        => DataContainer::MODE_SORTABLE,
			'fields'      => ['tstamp DESC'],
			'flag'        => 6,
			'panelLayout' => 'filter;sort,search,limit',
		],

		'label' => [
			'fields'      => ['tstamp', 'ip_address', 'reputation_score', 'block_count'],
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
			'edit' => [
				'href' => 'act=edit',
				'icon' => 'edit.svg',
			],
			'delete' => [
				'href'       => 'act=delete',
				'icon'       => 'delete.svg',
				'attributes' => 'onclick="if(!confirm(\'' .
					($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? '') .
					'\'))return false;Backend.getScrollOffset()"',
			],
			'show' => [
				'href' => 'act=show',
				'icon' => 'show.svg',
			],
		],
	],

	'palettes' => [
		'default' =>
			'{ip_legend},ip_address;' .
			'{status_legend},is_whitelisted,is_hard_blocked,is_permanent;' .
			'{metrics_legend},tstamp,reputation_score,block_count,ip_block_ttl',
	],

	'fields' => [
		'id' => [
			'sql' => "int(10) unsigned NOT NULL auto_increment",
		],

		'tstamp' => [
			'label'     => &$GLOBALS['TL_LANG']['tl_lumas_antispam_ip_block']['tstamp'],
			'sorting'   => true,
			'flag'      => 6,
			'inputType' => 'text',
			'eval'      => [
				'rgxp'     => 'datim',
				'readonly' => true,
				'tl_class' => 'w50',
			],
			'sql'       => "int(10) unsigned NOT NULL default '0'",
		],

		'ip_address' => [
			'label'     => &$GLOBALS['TL_LANG']['tl_lumas_antispam_ip_block']['ip_address'],
			'search'    => true,
			'inputType' => 'text',
			'eval'      => [
				'mandatory' => true,
				'rgxp'      => 'ip',
				'unique'    => true,
				'maxlength' => 45,
				'tl_class'  => 'w50',
			],
			'sql'       => "varchar(45) NOT NULL default ''",
		],

		'block_count' => [
			'label'     => &$GLOBALS['TL_LANG']['tl_lumas_antispam_ip_block']['block_count'],
			'inputType' => 'text',
			'eval'      => [
				'rgxp'     => 'digit',
				'readonly' => true,
				'tl_class' => 'w50',
			],
			'sql'       => "int(10) unsigned NOT NULL default '0'",
		],

		'reputation_score' => [
			'label'     => &$GLOBALS['TL_LANG']['tl_lumas_antispam_ip_block']['reputation_score'],
			'inputType' => 'text',
			'eval'      => [
				'rgxp'     => 'digit',
				'readonly' => true,
				'tl_class' => 'w50',
			],
			'sql'       => "int(10) unsigned NOT NULL default '0'",
		],

		'ip_block_ttl' => [
			'label'     => &$GLOBALS['TL_LANG']['tl_lumas_antispam_ip_block']['ip_block_ttl'],
			'inputType' => 'text',
			'eval'      => [
				'rgxp'     => 'digit',
				'readonly' => true,
				'tl_class' => 'w50',
			],
			'sql'       => "int(10) unsigned NOT NULL default '24'",
		],

		'is_whitelisted' => [
			'label'     => &$GLOBALS['TL_LANG']['tl_lumas_antispam_ip_block']['is_whitelisted'],
			'inputType' => 'checkbox',
			'filter'    => true,
			'eval'      => ['tl_class' => 'w50'],
			'sql'       => "char(1) NOT NULL default ''",
		],

		'is_hard_blocked' => [
			'label'     => &$GLOBALS['TL_LANG']['tl_lumas_antispam_ip_block']['is_hard_blocked'],
			'inputType' => 'checkbox',
			'filter'    => true,
			'eval'      => ['tl_class' => 'w50'],
			'sql'       => "char(1) NOT NULL default ''",
		],

		'is_permanent' => [
			'label'     => &$GLOBALS['TL_LANG']['tl_lumas_antispam_ip_block']['is_permanent'],
			'inputType' => 'checkbox',
			'filter'    => true,
			'eval'      => ['tl_class' => 'w50'],
			'sql'       => "char(1) NOT NULL default ''",
		],
	],
];
