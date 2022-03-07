<?php

/**
 * webtrees-mod-translationtool: MyArtJaub Translation Tool Module for webtrees
 *
 * @package MyArtJaub\Webtrees\Module
 * @subpackage TranslationTool
 * @author Jonathan Jaubart <dev@jaubart.com>
 * @copyright Copyright (c) 2020, Jonathan Jaubart
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3
 */

declare(strict_types=1);

namespace MyArtJaub\Webtrees\Module\TranslationTool\Services;

use Composer\Composer;
use Composer\Factory;
use Composer\IO\NullIO;
use Composer\Package\PackageInterface;
use Fisharebest\Webtrees\Webtrees;

/**
 * Service for accessing Composer data for the root package
 */
class ComposerService
{
    /**
     * Checks whether a Composer package is a MyArtJaub one.
     *
     * @param PackageInterface $package
     * @return bool
     */
    public function isMyArtJaubPackage(PackageInterface $package): bool
    {
        list($vendor) = explode('/', $package->getName(), 2);
        return $vendor == self::MYARTJAUB_VENDOR;
    }

    /**
     * Name of the MyArtJaub modules' vendor
     * @var string MYARTJAUB_VENDOR
     * */
    private const MYARTJAUB_VENDOR = 'jon48';

    /**
     * List all the PSR-4 paths used in MyArtJaub packages autoloading.
     * The returned array is composed of items with the structure:
     *      - array [
     *              0 => Package Name
     *              1 => Array of normalised paths
     *          ]
     *
     * @return array<array<PackageInterface|array<string>>>
     */
    public function listMyArtJaubPackagesPaths(): array
    {
        if (getenv('HOME') === false) {
            putenv('HOME=' . Webtrees::DATA_DIR);
        }

        $composer = Factory::create(new NullIO(), Webtrees::ROOT_DIR . 'composer.json');

        $packages = $composer->getRepositoryManager()
            ->getLocalRepository()
            ->getPackages();

        $maj_packages = [];
        foreach ($packages as $package) {
            if ($this->isMyArtJaubPackage($package)) {
                $maj_packages[] = $this->extractPsr4Paths($composer, $package);
            }
        }

        return $maj_packages;
    }

    /**
     * Extract and normalise the PSR-4 paths used in a package autoloading.
     * The returned array is a 2-tuple with the structure:
     *      - array [
     *              0 => Package Name
     *              1 => Array of normalised paths
     *          ]
     *
     * @param Composer $composer
     * @param PackageInterface $package
     * @return array<PackageInterface|array<string>>
     */
    private function extractPsr4Paths(Composer $composer, PackageInterface $package): array
    {
        $autoload_generator = $composer->getAutoloadGenerator();

        $package_map = $autoload_generator->buildPackageMap(
            $composer->getInstallationManager(),
            $composer->getPackage(),
            [$package]
        );
        array_shift($package_map);
        $autoloads = count($package_map) == 0 ? ['psr-4' => []] :
            $autoload_generator->parseAutoloads($package_map, $composer->getPackage());
        $psr4_paths = [];
        foreach ($autoloads['psr-4'] as $psr4_ns_paths) {
            foreach ($psr4_ns_paths as $psr4_ns_path) {
                if (false !== $real_path = realpath($psr4_ns_path)) {
                    $psr4_paths[] = $real_path;
                }
            }
        }
        return [$package, $psr4_paths];
    }
}
