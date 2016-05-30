<?php
/**
 * webtrees-lib: MyArtJaub library for webtrees
 *
 * @package MyArtJaub\Webtrees\Tools
 * @subpackage MajTranslationTool
 * @author Jonathan Jaubart <dev@jaubart.com>
 * @copyright Copyright (c) 2013-2016, Jonathan Jaubart
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3
 */
namespace MyArtJaub\Webtrees\Tools\Module\MajTranslationTool\Model;

use Fisharebest\Localization\Locale;
use Fisharebest\Localization\Translation;
use Fisharebest\Webtrees\I18N;
use Gettext\Utils\PhpFunctionsScanner;

/**
 * Translations Analyzer
 * Extract translations from the code, and analize the translations list.
 */
class TranslationsAnalyzer
{   
    /**
     * List of items to be translated found in the code.
     * @var array $translations_found
     */
    protected $translations_found;
    
    /**
     * List of translations loaded through the standard I18N library.
     * @var array $translations_loaded
     */
    protected $translations_loaded;
    
    /**
     * List of translations done within the MyArtJaub modules
     * @var array $maj_translations
     */
    protected $maj_translations;
    
    /**
     * List of paths for source code
     * @var string[] $source_code_paths
     */
    protected $source_code_paths;
    
    /**
     * Reference locale
     * @var Locale $locale
     */
    protected $locale;
    
    /**
     * Reference locale code
     * @var string $locale_code
     */
    protected $locale_code;
    
    /**
     * Indicate whether the data been loaded
     * @var bool $is_loaded
     */
    protected $is_loaded;
    
    /**
     * Constructor for TranslationAnalyzer
     * 
     * @param string[] $code_paths
     * @param string $locale_code
     * @throws \InvalidArgumentException
     */
    public function __construct($code_paths, $locale_code = null) {
        if(!is_array($code_paths)) {
            throw new \InvalidArgumentException('The code_paths argument must be an array.');
        }
        
        $this->translations_found = array();
        $this->translations_loaded = array();
        $this->maj_translations = array();  
        
        $this->locale_code = $locale_code ?: WT_LOCALE;
        $this->source_code_paths = $code_paths;
        
        $this->is_loaded = false;
    }
    
    /**
     * Get the reference Locale
     * 
     * @return \Fisharebest\Localization\Locale\LocaleInterface
     */
    public function getLocale() {
        if(!$this->is_loaded) $this->load();
        return $this->locale;
    }
    
    /*************************
     *  Loading functions    *
     *************************/
    
    /**
     * Loads translations data for the analyzer.
     */
    public function load()
    {
        $this->loadLocale();
        $this->findTranslations();
        $this->loadExistingTranslations();
        $this->loadMajModulesTranslations();    
        $this->is_loaded = true;
    }
    
    /**
     * Loads reference locale, based on the locale code.
     */
    protected function loadLocale() {
        $this->locale = Locale::create($this->locale_code);
    }
    
    /**
     * Extension of the standard PHP glob functionm to apply it recursively.
     * 
     * @param string $pattern
     * @param int $flags
     * @return string[]
     * @see glob()
     */
    protected function glob_recursive($pattern, $flags = 0){
        $files = glob($pattern, $flags);
         
        foreach (glob(dirname($pattern).'/*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir){
            $files = array_merge($files, $this->glob_recursive($dir.'/'.basename($pattern), $flags));
        }
         
        return $files;
    }
    
    /**
     * Find translations in the source code.
     * The parser if looking for the *translate* or * translateContext* methods.
     */
    protected function findTranslations() {
        $php_files = array();
        foreach($this->source_code_paths as $path) {
            $php_files = array_merge($php_files, $this->glob_recursive($path . '/*.php') ?: array());
        }
        foreach($php_files as $php_file) {
            $code = file_get_contents($php_file);
            $php_scanner = new PhpFunctionsScanner($code);
            foreach($php_scanner->getFunctions() as $function) {
                if($function[0] == 'translate' || $function[0] == 'translateContext') {
                    $args = $function[2];
                    if($args && count($args) > 0) {
                        $msgid = md5($args[0]);
                        if(!isset($this->translations_found[$msgid])) {
                            $this->translations_found[$msgid] = array ('text' => $args[0], 'references' => array());
                        }
                        $this->translations_found[$msgid]['references'][] = substr($php_file, strlen(WT_ROOT)) . ':' . $function[1];
                    }
                }
            }
        }        
    }
    
    /**
     * Load translations loaded by the standard I18N library.   
     */
    protected function loadExistingTranslations() {
        $I18N_class = new \ReflectionClass('\\Fisharebest\\Webtrees\\I18N');
        $translator_property = $I18N_class->getProperty('translator');
        $translator_property->setAccessible(true);
        $wt_translator = $translator_property->getValue();
        
        $translator_class = new \ReflectionClass(get_class($wt_translator));
        $translations_property = $translator_class->getProperty('translations');
        $translations_property->setAccessible(true);
        $this->translations_loaded = $translations_property->getValue($wt_translator);
    }
    
    /**
     * Load translations defined in the MyArtJaub modules.
     */
    protected function loadMajModulesTranslations() {
        if (defined('GLOB_BRACE')) {
            $maj_translations_files = glob(WT_MODULES_DIR . 'myartjaub_*/language/' . $this->locale->languageTag() . '.{csv,php,mo}', GLOB_BRACE) ?: array();
        } else {
            // Some servers do not have GLOB_BRACE - see http://php.net/manual/en/function.glob.php
            $maj_translations_files = array_merge(
                glob(WT_MODULES_DIR . 'myartjaub_*/language/' . $this->locale->languageTag() . '.csv') ?: array(),
                glob(WT_MODULES_DIR . 'myartjaub_*/language/' . $this->locale->languageTag() . '.php') ?: array(),
                glob(WT_MODULES_DIR . 'myartjaub_*/language/' . $this->locale->languageTag() . '.mo') ?: array()
                );
        }
        
        foreach ($maj_translations_files as $translation_file) {
            $translation  = new Translation($translation_file);
            foreach(array_keys($translation->asArray()) as $msg) {
                $msgid = md5($msg);
                if(!isset($this->maj_translations[$msgid])) {
                    $this->maj_translations[$msgid] = array ('text' => $msg, 'references' => array());
                }
                $path_parts = explode(DIRECTORY_SEPARATOR, substr($translation_file, strlen(WT_MODULES_DIR)));
                $path_parts = explode('/', $path_parts[0]);
                $this->maj_translations[$msgid]['references'][] = $path_parts[0];
            }
        }
    }
    
    /*************************
     *  Analyzer functions   *
     *************************/
    
    /**
     * Returns the translations missing through the standard I18N.
     * The returned array is composed of items with the structure:
     *      - text: Translation message
     *      - references: List of lines referencing the message 
     * 
     * @return array
     */
    public function getMissingTranslations() {
        if(!$this->is_loaded) $this->load();
        $missing_translations = array();
        foreach($this->translations_found as $found) {
            if(!array_key_exists ($found['text'], $this->translations_loaded)){
                $missing_translations[] = $found;
             }
        }
        
        return $missing_translations;
    }
    
    /**
     * Returns the translations defined in the MaJ modules, but not actually used in the code.
     * The returned array is composed of items with the structure:
     *      - text: Translation message
     *      - references: List of lines referencing the message 
     * 
     * @return array
     */
    public function getMajNonUsedTranslations() {
        if(!$this->is_loaded) $this->load();
        $removed_translations = array();
        foreach(array_keys($this->translations_loaded) as $msgid){
            if(!isset($this->translations_found[md5($msgid)]) 
                && isset($this->maj_translations[md5($msgid)])){
                $removed_translations[] = $this->maj_translations[md5($msgid)];
            }
        }
    
        return $removed_translations;
    }
    
    /**
     * Get some statistics about the translations data loaded.
     * Returns an array with the statistics:
     *      nbTranslations : total number of translations
     *      nbTranslationsFound: total number of translations found in the code
     *      nbMajTranslations: total number of translations done in the MyArtJaub modules
     * 
     * @return array
     */
    public function getLoadingStatistics() 
    {
        if(!$this->is_loaded) $this->load();
        return array(
            'nbTranslations' => count($this->translations_loaded),
            'nbTranslationsFound' => count($this->translations_found),
            'nbMajTranslations' => count($this->maj_translations)
        );
    }
}