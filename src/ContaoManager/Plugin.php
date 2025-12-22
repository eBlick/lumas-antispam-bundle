<?php

declare(strict_types=1);

namespace Lumas\AntispamBundle\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Lumas\AntispamBundle\LumasAntispamBundle;

class Plugin implements BundlePluginInterface
{
	public function getBundles(ParserInterface $parser): array
	{
		return [
			BundleConfig::create(LumasAntispamBundle::class)
				->setLoadAfter([ContaoCoreBundle::class]),
		];
	}
}
