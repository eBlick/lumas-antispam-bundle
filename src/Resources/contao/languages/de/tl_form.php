<?php

declare(strict_types=1);

$GLOBALS['TL_LANG']['tl_form']['lumas_antispam_legend'] = 'LUMAS AntiSpam Einstellungen';

$GLOBALS['TL_LANG']['tl_form']['lumas_antispam_enable'] = [
	'AntiSpam aktivieren',
	'Schaltet den erweiterten Schutz für dieses Formular ein.',
];

$GLOBALS['TL_LANG']['tl_form']['lumas_antispam_ip_block'] = [
	'IP-Historie nutzen',
	'Aktiviert die globale IP-Sperrlogik auf Basis der IP-Historie (Reputation/Eskalation).',
];

$GLOBALS['TL_LANG']['tl_form']['lumas_antispam_language'] = [
	'Spracherkennung',
	'Sprache für die Stopwort-Analyse.',
];

$GLOBALS['TL_LANG']['tl_form']['lumas_antispam_minDelay'] = [
	'Mindestdauer (Sek.)',
	'Zeit zwischen Aufruf und Absenden (Standard: 15).',
];

$GLOBALS['TL_LANG']['tl_form']['lumas_antispam_minLen'] = [
	'Mindestlänge',
	'Erforderliche Zeichenanzahl der Nachricht (Standard: 15).',
];

$GLOBALS['TL_LANG']['tl_form']['lumas_antispam_blockTime'] = [
	'Sperrdauer (Min.)',
	'Dauer der Session-Sperre nach wiederholten Verstößen (Standard: 30).',
];

$GLOBALS['TL_LANG']['tl_form']['lumas_antispam_stopwordCount'] = [
	'Min. Stopwörter',
	'Minimale Anzahl gefundener Stopwörter zur Sprachvalidierung (Standard: 2).',
];

// Optional: nur behalten, wenn wir es in der Listener-Logik auch prüfen
$GLOBALS['TL_LANG']['tl_form']['lumas_antispam_maxLinks'] = [
	'Max. Links (optional)',
	'Maximale Anzahl erlaubter URLs im Text (Standard: 1).',
];

// Namen für die Sprach-Optionen
$GLOBALS['TL_LANG']['tl_form']['lumas_antispam_languages'] = [
	'de' => 'Deutsch',
	'en' => 'Englisch',
	'fr' => 'Französisch',
	'es' => 'Spanisch',
	'it' => 'Italienisch',
];
