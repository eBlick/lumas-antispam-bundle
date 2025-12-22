<?php

declare(strict_types=1);

$GLOBALS['TL_LANG']['tl_lumas_antispam_ip_block']['ip_legend']     = 'IP-Informationen';
$GLOBALS['TL_LANG']['tl_lumas_antispam_ip_block']['status_legend'] = 'Status & Sperre';
$GLOBALS['TL_LANG']['tl_lumas_antispam_ip_block']['metrics_legend'] = 'Bewertung & Historie';

$GLOBALS['TL_LANG']['tl_lumas_antispam_ip_block']['tstamp'] = [
	'Zeitpunkt',
	'Zeitpunkt der letzten Eskalation bzw. des letzten Verstoßes.'
];

$GLOBALS['TL_LANG']['tl_lumas_antispam_ip_block']['ip_address'] = [
	'IP-Adresse',
	'Gültige IPv4- oder IPv6-Adresse.'
];

$GLOBALS['TL_LANG']['tl_lumas_antispam_ip_block']['block_count'] = [
	'Verstöße',
	'Anzahl der bisherigen Spamschutz-Verletzungen.'
];

$GLOBALS['TL_LANG']['tl_lumas_antispam_ip_block']['reputation_score'] = [
	'Reputations-Score',
	'Gesamtbewertung der IP-Adresse. Steigt mit jedem Verstoß.'
];

$GLOBALS['TL_LANG']['tl_lumas_antispam_ip_block']['ip_block_ttl'] = [
	'Sperrdauer (Stunden)',
	'Aktuelle Dauer der IP-Sperre in Stunden. Erhöht sich automatisch mit steigender Eskalationsstufe.'
];

$GLOBALS['TL_LANG']['tl_lumas_antispam_ip_block']['is_whitelisted'] = [
	'Whitelist',
	'Diese IP-Adresse wird niemals automatisch blockiert.'
];

$GLOBALS['TL_LANG']['tl_lumas_antispam_ip_block']['is_hard_blocked'] = [
	'Hard-Block',
	'Diese IP-Adresse ist unabhängig von der Eskalationslogik dauerhaft gesperrt.'
];

$GLOBALS['TL_LANG']['tl_lumas_antispam_ip_block']['is_permanent'] = [
	'Permanente Sperre',
	'Die IP-Adresse wird niemals automatisch freigegeben.'
];
