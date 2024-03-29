<?php

/**
 * webtrees-mod-translationtool: MyArtJaub Translation Tool Module for webtrees
 *
 * @package MyArtJaub\Webtrees\Module
 * @subpackage TranslationTool
 * @author Jonathan Jaubart <dev@jaubart.com>
 * @copyright Copyright (c) 2016-2023, Jonathan Jaubart
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3
 */

declare(strict_types=1);

namespace MyArtJaub\Webtrees\Module\TranslationTool\Model;

use Fisharebest\Webtrees\I18N;
use Gettext\Translation;
use Illuminate\Support\Collection;
use MyArtJaub\Webtrees\Module\TranslationTool\Services\SourceCodeService;
use ReflectionClass;

/**
 * Translations Analyzer
 * Extract translations from the code, and analize the translations list.
 */
class TranslationsAnalyzer
{
    /** @var SourceCodeService $sourcecode_service */
    private $sourcecode_service;

    /**
     * List of items to be translated found in the code.
     * @var ?Collection<string, array{headers: \Gettext\Headers, translation: Translation}> $strings_to_translate
     */
    private $strings_to_translate;

    /**
     * List of translations loaded through the standard I18N library.
     * @var ?Collection<string, string> $loaded_translations
     */
    private $loaded_translations;

    /**
     * List of translations loaded within the MyArtJaub modules
     * @var ?Collection<string, array<string>> $maj_translations
     */
    private $maj_translations;

    /**
     * List of paths for source code
     * @var Collection<string, array<string>> $source_code_paths
     */
    private $source_code_paths;

    /**
     * Reference language code
     * @var string $language
     */
    private $language;

    /**
     * Constructor for TranslationAnalyzer
     *
     * @param SourceCodeService $sourcecode_service
     * @param Collection<string, array<string>> $code_paths
     * @param string $language
     */
    public function __construct(SourceCodeService $sourcecode_service, Collection $code_paths, string $language = null)
    {
        $this->sourcecode_service = $sourcecode_service;
        $this->language = $language ?? I18N::locale()->languageTag();
        $this->source_code_paths = $code_paths;
    }

    /******************************
     *  Data retrieval functions  *
     ******************************/

    /**
     * Compute the key for a given GetText Translation entry, dealing with context \x04 and plural \x00 cases.
     *
     * @param Translation $translation
     * @return string
     */
    private function getTranslationKey(Translation $translation): string
    {
        $key = $translation->getOriginal();
        $translation_plural = $translation->getPlural();
        if ($translation_plural !== null && strlen($translation_plural) > 0) {
            $key .= I18N::PLURAL . $translation_plural;
        }
        $translation_context = $translation->getContext();
        if ($translation_context !== null && strlen($translation_context) > 0) {
            $key = $translation_context . I18N::CONTEXT . $key;
        }
        return $key;
    }

    /**
     * Returns the strings tagged for translation in the source code.
     * The returned structure is an associative Collection with :
     *      - key: MD5 hash of the Translation key
     *      - value: array [
     *              headers => GetText Translations Header (including domain)
     *              translation => GetTex Translation
     *          ]
     *
     * @return Collection<string, array{headers: \Gettext\Headers, translation: \Gettext\Translation}>
     */
    private function stringsToTranslate(): Collection
    {
        if ($this->strings_to_translate === null) {
            $strings_to_translate_list = $this->sourcecode_service->findStringsToTranslate($this->source_code_paths);

            $this->strings_to_translate = new Collection();
            foreach ($strings_to_translate_list as $translations) {
                foreach ($translations as $translation) {
                    $key = md5($this->getTranslationKey($translation));
                    $this->strings_to_translate->put($key, [
                        'headers' => $translations->getHeaders(),
                        'translation' => $translation
                    ]);
                }
            }
        }
        return $this->strings_to_translate;
    }

    /**
     * Returns the list of translations loaded through the standard I18N library.
     * The returned structure is an associative Collection with :
     *      - key: Original translation key
     *      - value: Translated string
     *
     * @return Collection<string, string>
     */
    private function loadedTranslations(): Collection
    {
        if ($this->loaded_translations === null) {
            $I18N_class = new ReflectionClass(I18N::class);
            $translator_property = $I18N_class->getProperty('translator');
            $translator_property->setAccessible(true);
            $wt_translator = (object) $translator_property->getValue();

            $translator_class = new ReflectionClass(get_class($wt_translator));
            $translations_property = $translator_class->getProperty('translations');
            $translations_property->setAccessible(true);
            $this->loaded_translations = collect($translations_property->getValue($wt_translator));
        }
        return $this->loaded_translations;
    }

    /**
     * Returns the list of translations loaded in MyArtJaub modules.
     * The returned structure is an associative Collection with :
     *      - key: MD5 hash of the translation key
     *      - value: array [
     *              0 => Module name
     *              1 => Translation key
     *          ]
     *
     * @return Collection<string, array<string>>
     */
    private function loadedMyArtJaubTranslations(): Collection
    {
        if ($this->maj_translations === null) {
            $maj_translations_list = $this->sourcecode_service->listMyArtJaubTranslations($this->language);

            $this->maj_translations = new Collection();
            foreach ($maj_translations_list as $module => $maj_mod_translations) {
                foreach (array_keys($maj_mod_translations) as $maj_mod_translation) {
                    $this->maj_translations->put(md5($maj_mod_translation), [$module, $maj_mod_translation]);
                }
            }
        }
        return $this->maj_translations;
    }

    /*************************
     *  Analyzer functions   *
     *************************/


    /**
     * Returns the translations missing through the standard I18N.
     * The returned array is composed of items with the structure:
     *      - array [
     *              headers => GetText Translations Header (including domain)
     *              translation => GetTex Translation
     *          ]
     *
     * @return array<array{headers: \Gettext\Headers, translation: \Gettext\Translation}>
     */
    public function missingTranslations(): array
    {
        $missing_translations = array();
        foreach ($this->stringsToTranslate() as $translation_info) {
            $translation = $translation_info['translation'];
            if (!$this->loadedTranslations()->has($this->getTranslationKey($translation))) {
                $missing_translations[] = $translation_info;
            }
        }

        return $missing_translations;
    }

    /**
     * Returns the translations defined in the MaJ modules, but not actually used in the code.
     * The returned array is composed of items with the structure:
     *      - array [
     *              0 => Module name
     *              1 => Translation key
     *          ]
     *
     * @return array<array<string>>
     */
    public function nonUsedMajTranslations(): array
    {
        $removed_translations = [];
        $strings_to_translate_list = $this->stringsToTranslate();
        foreach ($this->loadedMyArtJaubTranslations() as $msgid => $translation_info) {
            if (!$strings_to_translate_list->has($msgid)) {
                $removed_translations[] = $translation_info;
            }
        }
        return $removed_translations;
    }

    /**
     * Get some statistics about the translations data.
     * Returns an array with the statistics:
     *      nbTranslations : total number of translations
     *      nbTranslationsFound: total number of translations found in the code
     *      nbMajTranslations: total number of translations loaded in the MyArtJaub modules
     *
     * @return array<string, int>
     */
    public function translationsStatictics(): array
    {
        return [
            'nbTranslations' => $this->loadedTranslations()->count(),
            'nbTranslationsFound' => $this->stringsToTranslate()->count(),
            'nbMajTranslations' => $this->loadedMyArtJaubTranslations()->count()
        ];
    }
}
