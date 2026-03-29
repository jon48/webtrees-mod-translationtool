<?php

/**
 * webtrees-mod-translationtool: MyArtJaub Translation Tool Module for webtrees
 *
 * @package MyArtJaub\Webtrees\Module
 * @subpackage TranslationTool
 * @author Jonathan Jaubart <dev@jaubart.com>
 * @copyright Copyright (c) 2020-2026, Jonathan Jaubart
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
     * @var array<string, string>
     */
    private const I18N_FUNCTIONS = [
        'translate' => 'gettext',
        'plural' => 'ngettext',
        'translateContext' => 'pgettext'
    ];

    /** @var ModuleService $module_service */
    private $module_service;

    /** @var ComposerService $composer_service */
    private $composer_service;

    public function __construct(ModuleService $module_service, ComposerService $composer_service)
    {
        $this->module_service = $module_service;
        $this->composer_service = $composer_service;
    }

    /**
     * Lists all paths containing source code to be scanned for translations.
     * This contains the MyArtJaub modules's resources folder,
     * as well as MyArtJaub modules PSR-4 autoloading paths loaded through Composer
     *
     * @return Collection<string, array<string>>
     */
    public function sourceCodePaths(): Collection
    {
        $paths = $this->module_service->findByInterface(ModuleMyArtJaubInterface::class)
            ->mapWithKeys(function (ModuleMyArtJaubInterface $module): array {
                $mod_path = realpath($module->resourcesFolder());
                return [$module->name() => $mod_path === false ? [] : [$mod_path]];
            })->reject(fn(array $value): bool => count($value) === 0);

        $maj_packages = $this->composer_service->listMyArtJaubPackagesPaths();

        foreach ($maj_packages as list($maj_package, $psr4_paths)) {
            /** @var PackageInterface $maj_package */
            $installer_name = $maj_package->getExtra()['installer-name'] ?? '';
            $key = $installer_name === '' ? $maj_package->getName() : '_' . $installer_name . '_';
            if (count($psr4_paths) > 0) {
                $paths->put($key, array_merge($paths->get($key, []), $psr4_paths));
            }
        }

        /** @var Collection<string, array<string>> $paths */
        return $paths;
    }

    /**
     * Find all strings to be translated in PHP or PHTML files for a set of source code paths
     * The returned structure is a associated Collection with:
     *      - key: package/domain
     *      - value: Gettext Translations object for that domain
     *
     * @param Collection<string, array<string>> $source_code_paths
     * @return Collection<string, Translations>
     */
    public function findStringsToTranslate(Collection $source_code_paths): Collection
    {
        $strings_to_translate = new Collection();
        foreach ($source_code_paths as $package => $paths) {
            $php_files = array();
            foreach ($paths as $path) {
                $php_files = [...$php_files, ...$this->glob_recursive($path . '/*.php')];
                $php_files = [...$php_files, ...$this->glob_recursive($path . '/*.phtml')];
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
     * @return Collection<string, array<string,string>>
     */
    public function listMyArtJaubTranslations(string $language): Collection
    {
        $translations = $this->module_service->findByInterface(ModuleMyArtJaubInterface::class)
            ->mapWithKeys(function (ModuleMyArtJaubInterface $module) use ($language): array {
                return [$module->name() => $module->customTranslations($language)];
            });

        /** @var Collection<string, array<string, string>> $translations */
        return $translations;
    }

    /**
     * Extension of the standard PHP glob function to apply it recursively.
     *
     * @param string $pattern
     * @return array<int, string>
     * @see glob()
     * @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
     */
    protected function glob_recursive(string $pattern): array
    {
        $files_glob = glob($pattern);
        $files = $files_glob === false ? [] : $files_glob;

        $dirs_glob = glob(dirname($pattern) . '/*', GLOB_ONLYDIR | GLOB_NOSORT);
        $dirs = $dirs_glob === false ? [] : $dirs_glob;

        foreach ($dirs as $dir) {
            $files = [...$files, ...($this->glob_recursive($dir . '/' . basename($pattern)))];
        }

        return $files;
    }
}
