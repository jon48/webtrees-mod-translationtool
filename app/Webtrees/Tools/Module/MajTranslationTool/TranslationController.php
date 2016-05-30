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
namespace MyArtJaub\Webtrees\Tools\Module\MajTranslationTool;

use Fisharebest\Webtrees\Auth;
use Fisharebest\Webtrees\Controller\PageController;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Theme;
use Fisharebest\Webtrees\Theme\AdministrationTheme;
use MyArtJaub\Webtrees\Mvc\Controller\MvcController;
use MyArtJaub\Webtrees\Mvc\View\ViewBag;
use MyArtJaub\Webtrees\Mvc\View\ViewFactory;
use MyArtJaub\Webtrees\Tools\Module\MajTranslationTool\Model\TranslationsAnalyzer;

/**
 * Controller for Translation
 */
class TranslationController extends MvcController
{    

    /**
     * Translation@status
     */
    public function status(){
        global $WT_TREE;
        
        $table_id = \Rhumsaa\Uuid\Uuid::uuid4();
        
        Theme::theme(new AdministrationTheme)->init($WT_TREE);
        $ctrl = new PageController();
        $ctrl
            ->restrictAccess(Auth::isAdmin())
            ->setPageTitle(I18N::translate('Translations status'))
            ->addExternalJavascript(WT_JQUERY_DATATABLES_JS_URL)
            ->addExternalJavascript(WT_DATATABLES_BOOTSTRAP_JS_URL)
            ->addInlineJavascript('
                //Datatable initialisation
				jQuery.fn.dataTableExt.oSort["unicode-asc"  ]=function(a,b) {return a.replace(/<[^<]*>/, "").localeCompare(b.replace(/<[^<]*>/, ""))};
				jQuery.fn.dataTableExt.oSort["unicode-desc" ]=function(a,b) {return b.replace(/<[^<]*>/, "").localeCompare(a.replace(/<[^<]*>/, ""))};
				jQuery.fn.dataTableExt.oSort["num-html-asc" ]=function(a,b) {a=parseFloat(a.replace(/<[^<]*>/, "")); b=parseFloat(b.replace(/<[^<]*>/, "")); return (a<b) ? -1 : (a>b ? 1 : 0);};
				jQuery.fn.dataTableExt.oSort["num-html-desc"]=function(a,b) {a=parseFloat(a.replace(/<[^<]*>/, "")); b=parseFloat(b.replace(/<[^<]*>/, "")); return (a>b) ? -1 : (a<b ? 1 : 0);};
	
				jQuery("#table_missing_'.$table_id.'").DataTable({
					'.I18N::datatablesI18N().',			
					sorting: [[0, "asc"]],                    
					pageLength: 15,
                    columns: [
						/* 0 Message	 	*/ null,
                        /* 1 Reference      */ null
					],
				});
                
                jQuery("#table_nonused_'.$table_id.'").DataTable({
					'.I18N::datatablesI18N().',			
					sorting: [[0, "asc"]],                    
					pageLength: 15,
                    columns: [
						/* 0 Message	 	*/ null,
                        /* 1 Reference      */ null
					],
				});
            ');
        
        $source_code_paths = array(
            WT_ROOT . 'vendor/jon48/webtrees-lib/src',
            WT_ROOT . 'vendor/jon48/webtrees-tools/src/app'            
        );
        $analyzer = new TranslationsAnalyzer($source_code_paths);
        $analyzer->load();
        $locale = $analyzer->getLocale();
        
        $view_bag = new ViewBag();
        $view_bag->set('table_id', $table_id);
        $view_bag->set('module', $this->module);
        $view_bag->set('source_code_paths', $source_code_paths);
        $view_bag->set('title', $ctrl->getPageTitle() . ' - ' . I18N::languageName($locale->languageTag()));
        $view_bag->set('missing_translations', $analyzer->getMissingTranslations());
        $view_bag->set('non_used_translations', $analyzer->getMajNonUsedTranslations());
        $view_bag->set('loading_stats', $analyzer->getLoadingStatistics());
        
        ViewFactory::make('TranslationStatus', $this, $ctrl, $view_bag)->render();
    }
    
}