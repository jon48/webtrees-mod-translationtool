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

use Composer\Package\PackageInterface;
use Fisharebest\Webtrees\Services\ModuleService;
use Gettext\Merge;
use Gettext\Translations;
use Gettext\Scanner\PhpScanner;
use Illuminate\Support\Collection;
use MyArtJaub\Webtrees\Module\ModuleMyArtJaubInterface;

/**
 * Service for extracting data from the webtrees and modules source code.
 */
class SourceCodeService
{

    /**
     * Gettext Translations merge strategy to be used - Use Theirs data
     * @var int MERGE_STRATEGY_THEIRS
     */
    private const MERGE_STRATEGY_THEIRS = Merge::HEADERS_OVERRIDE
        | Merge::TRANSLATIONS_THEIRS
        | Merge::TRANSLATIONS_OVERRIDE
        | Merge::EXTRACTED_COMMENTS_THEIRS
        | Merge::REFERENCES_THEIRS
        | Merge::FLAGS_THEIRS
        | Merge::COMMENTS_THEIRS;

    /**
     * I18N functions to be looked for in the code
     * @var array
     */
    private const I18N_FUNCTIONS = [
        'translate' => 'gettext',
        'plural' => 'ngettext',
        'translateContext' => 'pgettext'
    ];

    /**
     * Lists all paths containing source code to be scanned for translations.
     * This contains the MyArtJaub modules's resources folder,
     * as well as MyArtJaub modules PSR-4 autoloading paths loaded through Composer
     *
     * @return Collection
     */
    public function sourceCodePaths(): Collection
    {
        $paths = app(ModuleService::class)->findByInterface(ModuleMyArtJaubInterface::class)
            ->mapWithKeys(function (ModuleMyArtJaubInterface $module): array {
                return [$module->name() => [realpath($module->resourcesFolder())]];
            });

        $maj_packages = app(ComposerService::class)->listMyArtJaubPackagesPaths();

        foreach ($maj_packages as list($maj_package, $psr4_paths)) {
            /** @var PackageInterface $maj_package */
            $installer_name = $maj_package->getExtra()['installer-name'] ?? '';
            $key = $installer_name === '' ? $maj_package->getName() : '_' . $installer_name . '_';
            if (count($psr4_paths) > 0) {
                $paths->put($key, array_merge($paths->get($key, []), $psr4_paths));
            }
        }

        return $paths;
    }

    /**
     * Find all strings to be translated in PHP or PHTML files for a set of source code paths
     * The returned structure is a associated Collection with:
     *      - key: package/domain
     *      - value: Gettext Translations object for that domain
     *
     * @param Collection $source_code_paths
     * @return Collection
     */
    public function findStringsToTranslate(Collection $source_code_paths): Collection
    {
        $strings_to_translate = new Collection();
        foreach ($source_code_paths as $package => $paths) {
            $php_files = array();
            foreach ($paths as $path) {
                $php_files = array_merge($php_files, $this->glob_recursive($path . '/*.php') ?: array());
                $php_files = array_merge($php_files, $this->glob_recursive($path . '/*.phtml') ?: array());
            }

            $php_scanner = new PhpScanner(Translations::create($package));
            $php_scanner
                ->setFunctions(self::I18N_FUNCTIONS)
                ->ignoreInvalidFunctions(true);
            $php_scanner->setDefaultDomain($package);
            foreach ($php_files as $php_file) {
                $php_scanner->scanFile($php_file);
            }

            $strings_to_translate->put(
                $package,
                $strings_to_translate
                    ->get($package, Translations::create())
                    ->mergeWith($php_scanner->getTranslations()[$package], self::MERGE_STRATEGY_THEIRS)
            );
        }
        return $strings_to_translate;
    }

    /**
     * List all translations defined in MyArtJaub modules
     * The returned structure is a associated Collection with:
     *      - key: module name
     *      - value: array of translations for the module
     *
     * @param string $language
     * @return Collection
     */
    public function listMyArtJaubTranslations(string $language): Collection
    {
        return app(ModuleService::class)->findByInterface(ModuleMyArtJaubInterface::class)
            ->mapWithKeys(function (ModuleMyArtJaubInterface $module) use ($language): array {
                return [$module->name() => $module->customTranslations($language)];
            });
    }

    /**
     * Extension of the standard PHP glob function to apply it recursively.
     *
     * @param string $pattern
     * @param int $flags
     * @return string[]
     * @see glob()
     * @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
     */
    protected function glob_recursive(string $pattern, int $flags = 0): array
    {
        $files = glob($pattern, $flags) ?: [];
        $dirs = glob(dirname($pattern) . '/*', GLOB_ONLYDIR | GLOB_NOSORT) ?: [];

        foreach ($dirs as $dir) {
            $files = array_merge($files, $this->glob_recursive($dir . '/' . basename($pattern), $flags));
        }

        return $files;
    }
}
