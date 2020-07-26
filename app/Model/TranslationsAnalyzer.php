<?php

/**
 * webtrees-mod-translationtool: MyArtJaub Translation Tool Module for webtrees
 *
 * @package MyArtJaub\Webtrees\Module
 * @subpackage TranslationTool
 * @author Jonathan Jaubart <dev@jaubart.com>
 * @copyright Copyright (c) 2016-2020, Jonathan Jaubart
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
     * @var Collection $strings_to_translate
     */
    private $strings_to_translate;
    
    /**
     * List of translations loaded through the standard I18N library.
     * @var Collection $loaded_translations
     */
    private $loaded_translations;
    
    /**
     * List of translations loaded within the MyArtJaub modules
     * @var Collection $maj_translations
     */
    private $maj_translations;
    
    /**
     * List of paths for source code
     * @var Collection $source_code_paths
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
     * @param Collection $code_paths
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
        if ($translation->getPlural() !== null && strlen($translation->getPlural()) > 0) {
            $key .= I18N::PLURAL . $translation->getPlural();
        }
        if ($translation->getContext() !== null && strlen($translation->getContext()) > 0) {
            $key = $translation->getContext() . I18N::CONTEXT . $key;
        }
        return $key;
    }
    
    /**
     * Returns the strings tagged for translation in the source code.
     * The returned structure is an associative Collection with :
     *      - key: MD5 hash of the Translation key
     *      - value: array [
     *              0 => GetText Translations Header (including domain)
     *              1 => GetTex Translation
     *          ]
     *
     * @return Collection
     */
    private function stringsToTranslate(): Collection
    {
        if ($this->strings_to_translate === null) {
            $strings_to_translate_list = $this->sourcecode_service->findStringsToTranslate($this->source_code_paths);
            
            $this->strings_to_translate = new Collection();
            foreach ($strings_to_translate_list as $translations) {
                foreach ($translations as $translation) {
                    $key = md5($this->getTranslationKey($translation));
                    $this->strings_to_translate->put($key, [$translations->getHeaders(), $translation]);
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
     * @return Collection
     */
    private function loadedTranslations(): Collection
    {
        if ($this->loaded_translations === null) {
            $I18N_class = new ReflectionClass('\\Fisharebest\\Webtrees\\I18N');
            $translator_property = $I18N_class->getProperty('translator');
            $translator_property->setAccessible(true);
            $wt_translator = $translator_property->getValue();
            
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
     * @return Collection
     */
    private function loadedMyArtJaubTranslations(): Collection
    {
        if ($this->maj_translations === null) {
            $maj_translations_list = $this->sourcecode_service->listMyArtJaubTranslations($this->language);
            
            $this->maj_translations = new Collection();
            foreach ($maj_translations_list as $module => $maj_mod_translations) {
                foreach (array_keys($maj_mod_translations) as $maj_mod_translation) {
                    $this->maj_translations->put(md5((string) $maj_mod_translation), [$module, $maj_mod_translation]);
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
     *              0 => GetText Translations Header (including domain)
     *              1 => GetTex Translation
     *          ]
     *
     * @return array
     */
    public function missingTranslations(): array
    {
        $missing_translations = array();
        foreach ($this->stringsToTranslate() as $translation_info) {
            list(, $translation) = $translation_info;
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
     * @return array
     */
    public function nonUsedMajTranslations(): array
    {
        $removed_translations = array();
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
     * @return array
     */
    public function translationsStatictics(): array
    {
        return array(
            'nbTranslations' => $this->loadedTranslations()->count(),
            'nbTranslationsFound' => $this->stringsToTranslate()->count(),
            'nbMajTranslations' => $this->loadedMyArtJaubTranslations()->count()
        );
    }
}
