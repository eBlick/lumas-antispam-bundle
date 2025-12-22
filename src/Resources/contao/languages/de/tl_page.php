<?php

declare(strict_types=1);

$GLOBALS['TL_LANG']['tl_page']['lumas_antispam_legend'] = 'LUMAS AntiSpam (Standardwerte für diese Domain)';

// Klarnamen für die Sprachauswahl (muss zur DCA reference passen)
$GLOBALS['TL_LANG']['tl_page']['lumas_antispam_languages'] = [
	'de' => 'Deutsch',
	'en' => 'Englisch',
	'fr' => 'Französisch',
	'es' => 'Spanisch',
	'it' => 'Italienisch',
];

$GLOBALS['TL_LANG']['tl_page']['lumas_antispam_ip_block'] = [
	'IP-Reputations-Sperre',
	'Aktiviert die domainweite Überprüfung gegen die Spam-Datenbank.',
];

$GLOBALS['TL_LANG']['tl_page']['lumas_antispam_ip_block_ttl'] = [
	'Globale IP-Sperrdauer (Stunden)',
	'Standard: 24. Wie lange eine IP systemweit gesperrt bleibt.',
];

$GLOBALS['TL_LANG']['tl_page']['lumas_antispam_minDelay'] = [
	'Mindest-Ausfüllzeit (Sekunden)',
	'Standard: 15. Mindestzeit zwischen Seitenaufruf und Absenden.',
];

$GLOBALS['TL_LANG']['tl_page']['lumas_antispam_blockTime'] = [
	'Session-Sperre (Minuten)',
	'Standard: 30. Lokaler Block im Browser nach Fehlversuchen.',
];

$GLOBALS['TL_LANG']['tl_page']['lumas_antispam_language'] = [
	'Prüf-Sprache',
	'Welche Stopwort-Liste soll genutzt werden?',
];

$GLOBALS['TL_LANG']['tl_page']['lumas_antispam_stopwordCount'] = [
	'Mindestanzahl Stopwörter',
	'Standard: 2. Minimale Trefferanzahl in der Stopwort-Analyse.',
];

$GLOBALS['TL_LANG']['tl_page']['lumas_antispam_maxLinks'] = [
	'Maximal erlaubte Links (optional)',
	'Standard: 1. Maximale Anzahl URLs im Text.',
];

$GLOBALS['TL_LANG']['tl_page']['lumas_antispam_minLen'] = [
	'Mindestlänge Text',
	'Standard: 15. Minimale Zeichenanzahl.',
];
