<?php

declare(strict_types=1);

namespace Lumas\AntispamBundle\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\Form;
use Contao\PageModel;
use Contao\Widget;
use Doctrine\DBAL\Connection;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final class LumasAntiSpamListener
{
	// Cache TTL for isBlockedGlobal() cache
	private const STATUS_TTL_SEC = 300;

	// IP block threshold (active when block_count >= 5)
	private const GLOBAL_BLOCK_THRESHOLD = 5;

	// Session: 2 Fehlversuche erlaubt => Block ab 3. Fehlversuch
	private const SESSION_INVALID_THRESHOLD = 2;

	/**
	 * Request-scope state per formKey:
	 * - true  => this submit is spam/blocked (add errors to all fields)
	 * - false => passed
	 * - null  => undecided
	 *
	 * @var array<string, bool|null>
	 */
	private array $processedForms = [];

	private array $stopwords = [
		'de' => ['aber','alle','als','auch','bei','bin','bis','das','dass','der','die','du','ein','eine','es','für','ich','in','ist','mit','nicht','oder','sein','sie','und','von','zu'],
		'en' => ['a','about','and','are','as','at','be','by','for','from','has','have','he','i','in','is','it','of','on','that','the','to','was','with','you'],
		'fr' => ['au','aux','avec','ce','ces','dans','de','des','du','elle','en','et','il','je','la','le','les','mais','ne','pas','pour','que','qui','sur','un','une','vous'],
		'es' => ['a','al','como','con','de','del','el','ella','en','es','la','las','lo','los','no','para','por','porque','que','se','si','su','un','una','y'],
		'it' => ['a','ad','al','alla','anche','con','da','di','e','gli','ha','ho','il','in','io','la','le','lo','ma','mi','ne','noi','non','o','per','più','se','si','sono','su','tra','un','una','uno','voi'],
	];

	/**
	 * Defaults; can be overridden by form or root page fields (lumas_antispam_*)
	 * NOTE: ip_block_ttl here is just a base/default for compatibility/UI; the TTL is computed from reputation steps.
	 */
	private array $defaults = [
		'minDelay'      => 15,
		'minLen'        => 15,
		'stopwordCount' => 2,
		'language'      => 'de',
		'ip_block_ttl'  => 24, // base hours (kept for compatibility / admin UI)
		'ip_block'      => 0,  // enforcement toggle
		'blockTime'     => 30, // minutes (session block time)
	];

	public function __construct(
		private readonly LoggerInterface $logger,
		private readonly RequestStack $requestStack,
		private readonly Connection $db,
		private readonly CacheInterface $cache,
		private readonly CacheItemPoolInterface $cachePool,
	) {
	}

	/* =========================================================
	 * Helpers
	 * =======================================================*/

	private function isOne(mixed $v): bool
	{
		return (string) ($v ?? '0') === '1';
	}

	private function normalizeIp(?string $ip): ?string
	{
		$ip = $ip ? trim($ip) : null;

		return ($ip !== null && $ip !== '') ? $ip : null;
	}

	/**
	 * Key for the CacheInterface layer (status cache).
	 * Must be stable and PSR-compatible.
	 */
	private function cacheKeyForIp(string $ip): string
	{
		return 'lumas_antispam_status_' . preg_replace('/[^A-Za-z0-9_]/', '_', $ip);
	}

	/**
	 * ONE consistent key for session state + timestamps.
	 * In Contao, $formId is usually auto_form_XX and matches FORM_SUBMIT.
	 */
	private function getFormKey(Request $request, string $formId): string
	{
		return (string) ($request->request->get('FORM_SUBMIT') ?: $formId);
	}

	/** Nice alias for logging */
	private function getFormAliasForLogging(Form $form, string $formId): string
	{
		return (string) ($form->formID ?: $formId);
	}

	private function extractEmail(array $formData, Request $request): ?string
	{
		foreach (['email', 'e-mail', 'mail', 'email_address'] as $k) {
			$v = $formData[$k] ?? $request->request->get($k);
			$v = is_string($v) ? trim($v) : null;

			if ($v !== null && $v !== '') {
				return $v;
			}
		}

		return null;
	}

	private function getSetting(string $key, Form $form): mixed
	{
		$dcaKey = 'lumas_antispam_' . $key;

		// 1) per-form
		if (($val = $form->{$dcaKey} ?? null) !== null && (string) $val !== '') {
			return $val;
		}

		// 2) root page
		$request = $this->requestStack->getCurrentRequest();
		$page = $request?->attributes->get('pageModel') ?? ($GLOBALS['objPage'] ?? null);

		if ($page instanceof PageModel) {
			$rootId = (int) ($page->rootId ?: $page->id);
			$root = PageModel::findByPk($rootId);

			if ($root instanceof PageModel) {
				if (($val = $root->{$dcaKey} ?? null) !== null && (string) $val !== '') {
					return $val;
				}
			}
		}

		return $this->defaults[$key] ?? null;
	}

	/**
	 * Marker: stable per POST-request, different across separate POSTs.
	 * Used to avoid double-counting if validateFormField runs multiple times.
	 */
	private function makeSubmitMarker(string $formKey, SessionInterface $session, Request $request): string
	{
		$t = (string) ($request->server->get('REQUEST_TIME') ?? time());

		return hash('sha256', $formKey . '|' . $session->getId() . '|' . $t);
	}

	private function logOnly(string $ip, string $reason, string $formAlias, array $details = []): void
	{
		try {
			$this->db->insert('tl_lumas_antispam_log', [
				'tstamp'     => time(),
				'ip_address' => $ip,
				'reason'     => $reason,
				'form_alias' => $formAlias,
				'details'    => $details ? json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
			]);
		} catch (\Throwable $e) {
			$this->logger->error('AntiSpam DB Log failed: ' . $e->getMessage());
		}
	}

	private function logOnce(string $key, int $ttlSec, callable $fn): void
	{
		try {
			$this->cache->get($key, function (ItemInterface $item) use ($ttlSec, $fn) {
				$item->expiresAfter($ttlSec);
				$fn();

				return 1;
			});
		} catch (\Throwable) {
			// ignore
		}
	}

	private function isSpamMessageField(Widget $widget): bool
	{
		return \in_array($widget->name, ['message', 'nachricht', 'comment'], true);
	}

	/* =========================================================
	 * Kernel Request (optional)
	 * =======================================================*/

	#[AsEventListener(event: 'kernel.request', priority: 255)]
	public function onKernelRequest(RequestEvent $event): void
	{
		if (!$event->isMainRequest()) {
			return;
		}

		$request = $event->getRequest();

		// no-op: placeholder for future request-level checks
		if (str_starts_with($request->getPathInfo(), '/contao')) {
			return;
		}
	}

	/* =========================================================
	 * compileFormFields
	 * =======================================================*/

	#[AsHook('compileFormFields')]
	public function compileFormFields(array $fields, string $formId, Form $form): array
	{
		if (!$this->isOne($form->lumas_antispam_enable ?? null)) {
			return $fields;
		}

		$request = $this->requestStack->getCurrentRequest();

		if (!$request || !$request->hasSession()) {
			return $fields;
		}

		$session = $request->getSession();
		$ip = $this->normalizeIp($request->getClientIp());

		if ($ip === null) {
			return $fields;
		}

		$formKey   = $this->getFormKey($request, $formId);
		$formAlias = $this->getFormAliasForLogging($form, $formId);

		// Session block => hide form (throttled log)
		$stateKey = 'lumas_antispam_state_' . $formKey;
		$state = $session->get($stateKey, ['invalidCount' => 0, 'blockedAt' => null, 'lastMarker' => null]);

		$blockTimeMin = (int) $this->getSetting('blockTime', $form);
		$blockTimeSec = max(1, $blockTimeMin) * 60;

		if (($state['blockedAt'] ?? null) !== null) {
			$age = time() - (int) $state['blockedAt'];

			if ($age < $blockTimeSec) {
				$logKey = 'lumas_antispam_sb_get_' . $formKey . '_' . md5($session->getId());
				$this->logOnce($logKey, 60, function () use ($ip, $formAlias, $request, $blockTimeSec, $age) {
					$this->logOnly($ip, 'SESSION_BLOCK_ACTIVE_FORM_HIDDEN', $formAlias, [
						'remaining' => max(0, $blockTimeSec - $age),
						'uri'       => $request->getRequestUri(),
						'ua'        => (string) $request->headers->get('User-Agent', ''),
					]);
				});

				return [];
			}

			// expired => reset
			$session->set($stateKey, ['invalidCount' => 0, 'blockedAt' => null, 'lastMarker' => null]);
		}

		// IP block => hide form ONLY if enforcement is enabled (throttled log)
		if ($this->isOne($this->getSetting('ip_block', $form)) && $this->isBlockedGlobal($ip)) {
			$logKey = 'lumas_antispam_ip_get_' . $formKey . '_' . md5($session->getId());
			$this->logOnce($logKey, 120, function () use ($ip, $formAlias, $request) {
				$this->logOnly($ip, 'IP_BLOCK_ACTIVE_FORM_HIDDEN', $formAlias, [
					'uri' => $request->getRequestUri(),
					'ua'  => (string) $request->headers->get('User-Agent', ''),
				]);
			});

			return [];
		}

		// Start time for minDelay:
		// IMPORTANT: set ONLY on GET and ONLY if not existing (avoid "TOO_FAST always")
		if (!$request->isMethod('POST')) {
			$map = $session->get('lumas_antispam_form_start', []);

			if (!isset($map[$formKey])) {
				$map[$formKey] = time();
				$session->set('lumas_antispam_form_start', $map);
			}
		}

		return $fields;
	}

	/* =========================================================
	 * validateFormField
	 * =======================================================*/

	#[AsHook('validateFormField')]
	public function __invoke(Widget $widget, string $formId, array $formData, Form $form): Widget
	{
		if (!$this->isOne($form->lumas_antispam_enable ?? null)) {
			return $widget;
		}

		$request = $this->requestStack->getCurrentRequest();

		if (!$request || !$request->hasSession()) {
			return $widget;
		}

		$session = $request->getSession();
		$ip = $this->normalizeIp($request->getClientIp());

		if ($ip === null) {
			return $widget;
		}

		$formKey   = $this->getFormKey($request, $formId);
		$formAlias = $this->getFormAliasForLogging($form, $formId);

		// IMPORTANT: If this submit was already marked as spam/blocked,
		// enforce an error on EVERY field to prevent Contao success flow (redirect/NC).
		if (($this->processedForms[$formKey] ?? null) === true) {
			$widget->addError('Ihre Nachricht hat die Spamschutzkriterien nicht bestanden.');

			return $widget;
		}

		$isMessageField = $this->isSpamMessageField($widget);

		// Session state
		$stateKey = 'lumas_antispam_state_' . $formKey;
		$state = $session->get($stateKey, ['invalidCount' => 0, 'blockedAt' => null, 'lastMarker' => null]);

		// 1) IP block enforcement (only if ip_block enabled)
		if ($this->isOne($this->getSetting('ip_block', $form)) && $this->isBlockedGlobal($ip)) {
			$this->processedForms[$formKey] = true;

			$this->logOnly($ip, 'IP_BLOCK_ACTIVE_ON_POST', $formAlias, [
				'form_key' => $formKey,
				'uri'      => $request->getRequestUri(),
				'ua'       => (string) $request->headers->get('User-Agent', ''),
				'email'    => $this->extractEmail($formData, $request),
			]);

			$widget->addError('Ihre Nachricht hat die Spamschutzkriterien nicht bestanden.');

			return $widget;
		}

		// 2) Session block enforcement (always)
		$blockTimeSec = max(1, (int) $this->getSetting('blockTime', $form)) * 60;

		if (($state['blockedAt'] ?? null) !== null) {
			$age = time() - (int) $state['blockedAt'];

			if ($age < $blockTimeSec) {
				$this->processedForms[$formKey] = true;

				$this->logOnly($ip, 'SESSION_BLOCK_ACTIVE_ON_POST', $formAlias, [
					'form_key'     => $formKey,
					'remaining'    => max(0, $blockTimeSec - $age),
					'uri'          => $request->getRequestUri(),
					'ua'           => (string) $request->headers->get('User-Agent', ''),
					'email'        => $this->extractEmail($formData, $request),
					'invalidCount' => (int) ($state['invalidCount'] ?? 0),
				]);

				$widget->addError('Ihre Nachricht hat die Spamschutzkriterien nicht bestanden.');

				return $widget;
			}
		}

		// 3) Content checks + attempt logging happen ONLY on message field (=> 1 log per submit)
		if ($isMessageField) {
			$startMap = $session->get('lumas_antispam_form_start', []);
			$start = (int) ($startMap[$formKey] ?? 0);
			$hadTime = $start > 0;

			$minDelay = (int) $this->getSetting('minDelay', $form);

			$reason = $this->checkSpam(
				$widget,
				$request,
				$start,
				$hadTime,
				$minDelay,
				(int) $this->getSetting('stopwordCount', $form),
				(string) $this->getSetting('language', $form),
				(int) $this->getSetting('minLen', $form),
			);

			if ($reason !== null) {
				$this->processedForms[$formKey] = true;

				// Count strikes once per POST submit (marker)
				$marker = $this->makeSubmitMarker($formKey, $session, $request);

				$blockSetNow = false;

				if (($state['lastMarker'] ?? null) !== $marker) {
					$state['invalidCount'] = (int) ($state['invalidCount'] ?? 0) + 1;
					$state['lastMarker'] = $marker;

					// Block ab 3. Fehlversuch (2 sind erlaubt)
					if (
						$state['invalidCount'] >= (self::SESSION_INVALID_THRESHOLD + 1)
						&& ($state['blockedAt'] ?? null) === null
					) {
						$state['blockedAt'] = time();
						$blockSetNow = true;
					}

					$session->set($stateKey, $state);
				}

				// Log THIS attempt (always)
				$this->logAttempt($ip, $form, $reason, $widget, $formAlias, $request, $formData, $formKey, $state, $start, $minDelay);

				// Log the moment the session block is set (extra explicit)
				if ($blockSetNow) {
					$this->logOnly($ip, 'SESSION_BLOCK_SET', $formAlias, [
						'form_key'     => $formKey,
						'uri'          => $request->getRequestUri(),
						'ua'           => (string) $request->headers->get('User-Agent', ''),
						'email'        => $this->extractEmail($formData, $request),
						'invalidCount' => (int) ($state['invalidCount'] ?? 0),
						'blockTimeMin' => (int) $this->getSetting('blockTime', $form),
					]);
				}

				// Reputation counts for EVERY error, independent of session/IP enforcement
				$this->updateReputation($ip);

				$widget->addError('Ihre Nachricht hat die Spamschutzkriterien nicht bestanden.');
			} else {
				$this->processedForms[$formKey] = false;
			}
		}

		return $widget;
	}

	/* =========================================================
	 * Core checks
	 * =======================================================*/

	private function isBlockedGlobal(string $ip): bool
	{
		$ck = $this->cacheKeyForIp($ip);

		try {
			return (bool) $this->cache->get($ck, function (ItemInterface $item) use ($ip) {
				$item->expiresAfter(self::STATUS_TTL_SEC);

				$status = $this->db->fetchAssociative(
					'SELECT is_hard_blocked,is_whitelisted,is_permanent,block_count,tstamp,ip_block_ttl
					 FROM tl_lumas_antispam_ip_block WHERE ip_address = ?',
					[$ip],
				);

				if (!$status) {
					return false;
				}

				if ($this->isOne($status['is_whitelisted'] ?? 0)) {
					return false;
				}

				if ($this->isOne($status['is_permanent'] ?? 0)) {
					return true;
				}

				$ttl = (int) ($status['ip_block_ttl'] ?? 24);

				if (!empty($status['tstamp']) && time() - (int) $status['tstamp'] > $ttl * 3600) {
					return false;
				}

				return $this->isOne($status['is_hard_blocked'] ?? 0)
					|| (int) ($status['block_count'] ?? 0) >= self::GLOBAL_BLOCK_THRESHOLD;
			});
		} catch (\Throwable) {
			return false;
		}
	}

	private function checkSpam(
		Widget $widget,
		Request $request,
		int $start,
		bool $hadTime,
		int $minDelay,
		int $minStop,
		string $lang,
		int $minLen,
	): ?string {
		// Honeypot
		if ((string) $request->request->get('hp_field', '') !== '') {
			return 'HONEYPOT';
		}

		// Too fast
		if ($hadTime) {
			$delta = time() - $start;

			if ($delta < $minDelay) {
				return 'TOO_FAST';
			}
		}

		// Too short
		$text = trim((string) ($widget->value ?? ''));

		if (mb_strlen($text) < $minLen) {
			return 'TOO_SHORT';
		}

		// Language mismatch
		if (!$this->isCorrectLanguage($text, $lang, $minStop)) {
			return 'LANGUAGE_MISMATCH';
		}

		return null;
	}

	private function isCorrectLanguage(string $text, string $lang, int $minStop): bool
	{
		$text = (string) preg_replace('/https?:\/\/[^\s]+/iu', '', $text);
		$tokens = preg_split('/\P{L}+/u', mb_strtolower($text), -1, PREG_SPLIT_NO_EMPTY);

		if (!$tokens) {
			return false;
		}

		$list = $this->stopwords[$lang] ?? $this->stopwords['de'];
		$set = array_flip($list);

		$hits = 0;

		foreach ($tokens as $t) {
			if (isset($set[$t]) && ++$hits >= $minStop) {
				return true;
			}
		}

		return false;
	}

	/* =========================================================
	 * Logging
	 * =======================================================*/

	private function logAttempt(
		string $ip,
		Form $form,
		string $reason,
		Widget $widget,
		string $formAlias,
		Request $request,
		array $formData,
		string $formKey,
		array $state,
		int $start,
		int $minDelay,
	): void {
		$now = time();
		$delta = $start > 0 ? ($now - $start) : null;

		$this->logOnly($ip, $reason, $formAlias, [
			'form_key'      => $formKey,
			'field'         => $widget->name,
			'email'         => $this->extractEmail($formData, $request),
			'uri'           => $request->getRequestUri(),
			'ua'            => (string) $request->headers->get('User-Agent', ''),
			'lang'          => (string) $this->getSetting('language', $form),
			'invalidCount'  => (int) ($state['invalidCount'] ?? 0),
			'session_block' => (($state['blockedAt'] ?? null) !== null) ? 1 : 0,

			// timing debug (helps prove TOO_FAST)
			'start'    => $start ?: null,
			'now'      => $now,
			'delta'    => $delta,
			'minDelay' => $minDelay,
		]);
	}

	/* =========================================================
	 * Reputation / IP block duration logic
	 * =======================================================*/

	/**
	 * Reputation counts for every error independent of session/ip enforcement.
	 *
	 * Rule:
	 * - after 5 errors => 24h IP block
	 * - after 10 errors => 5*24h = 120h
	 * - after 15 errors => 10*24h = 240h
	 * - after 20 errors => 15*24h = 360h
	 * ... (every +5 errors adds +120h)
	 *
	 * block_count == reputation_score == total error count.
	 * tstamp is updated on every error (rolling window).
	 */
	private function updateReputation(string $ip): void
	{
		try {
			$now = time();

			$row = $this->db->fetchAssociative(
				'SELECT id, reputation_score FROM tl_lumas_antispam_ip_block WHERE ip_address=?',
				[$ip],
			);

			$score = 1;

			if ($row) {
				$score = (int) ($row['reputation_score'] ?? 0) + 1;
			}

			// TTL in hours according to step logic
			if ($score < 5) {
				$ttlHours = 24; // not blocked yet, but keep a sensible value
			} elseif ($score < 10) {
				$ttlHours = 24; // 5..9 => 24h
			} else {
				// 10..14 => 120h, 15..19 => 240h, 20..24 => 360h, ...
				$step = intdiv($score, 5) - 1; // 10->1, 15->2, 20->3 ...
				$ttlHours = 120 * $step;
			}

			if (!$row) {
				$this->db->insert('tl_lumas_antispam_ip_block', [
					'ip_address'       => $ip,
					'tstamp'           => $now,
					'block_count'      => $score,
					'reputation_score' => $score,
					'ip_block_ttl'     => $ttlHours,
				]);
			} else {
				$this->db->update(
					'tl_lumas_antispam_ip_block',
					[
						'tstamp'           => $now,
						'block_count'      => $score,
						'reputation_score' => $score,
						'ip_block_ttl'     => $ttlHours,
					],
					['id' => (int) $row['id']],
				);
			}

			// clear cached status (CacheInterface key)
			try {
				$this->cachePool->deleteItem($this->cacheKeyForIp($ip));
			} catch (\Throwable) {
				// ignore
			}
		} catch (\Throwable $e) {
			$this->logger->error('AntiSpam updateReputation failed: ' . $e->getMessage());
		}
	}
}
