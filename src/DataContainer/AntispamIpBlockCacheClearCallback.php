<?php

declare(strict_types=1);

namespace Lumas\AntispamBundle\DataContainer;

use Contao\DataContainer;
use Doctrine\DBAL\Connection;
use Psr\Cache\CacheItemPoolInterface;

final class AntispamIpBlockCacheClearCallback
{
	public function __construct(
		private readonly CacheItemPoolInterface $cachePool,
		private readonly Connection $db,
	) {
	}

	/**
	 * Wird bei "Speichern" aufgerufen (onsubmit_callback).
	 *
	 * Erwartet: tl_lumas_antispam_ip_block.ip_address
	 */
	public function onSubmit(DataContainer $dc): void
	{
		$ip = (string) ($dc->activeRecord?->ip_address ?? '');

		if ($ip === '') {
			return;
		}

		$this->purge($ip);
	}

	/**
	 * Wird bei "Löschen" aufgerufen (ondelete_callback).
	 *
	 * Hinweis: Beim Löschen ist activeRecord oft leer → IP per ID holen.
	 */
	public function onDelete(DataContainer $dc): void
	{
		$id = (int) ($dc->id ?? 0);

		if ($id <= 0) {
			return;
		}

		$ip = (string) $this->db->fetchOne(
			'SELECT ip_address FROM tl_lumas_antispam_ip_block WHERE id = ?',
			[$id],
		);

		if ($ip === '') {
			return;
		}

		$this->purge($ip);
	}

	private function purge(string $ip): void
	{
		$this->cachePool->deleteItem($this->key('lumas_antispam_status', $ip));
		$this->cachePool->deleteItem($this->key('lumas_antispam_hardlog', $ip));
	}

	private function key(string $prefix, string $ip): string
	{
		// PSR-6 Keys: nur "safe chars" -> IP normalisieren (auch IPv6)
		$normalized = preg_replace('/[^A-Za-z0-9_]/', '_', $ip) ?? '';

		return $prefix . '_' . $normalized;
	}
}
