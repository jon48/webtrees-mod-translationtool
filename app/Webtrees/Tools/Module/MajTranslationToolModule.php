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
namespace MyArtJaub\Webtrees\Tools\Module;

use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Module\AbstractModule;
use Fisharebest\Webtrees\Module\ModuleConfigInterface;
use MyArtJaub\Webtrees\Mvc\Dispatcher;

/**
 * MyArtJaub Translation tool Module.
 * This module helps with managing translations introduced by the MyArtJaub modules.
 */
class MajTranslationToolModule extends AbstractModule implements ModuleConfigInterface
{
     /**
      * {@inheritDoc}
      * @see \Fisharebest\Webtrees\Module\AbstractModule::getTitle()
      */
     public function getTitle()
    {
         return I18N::translate('Translation Tool');
    }

    /**
     * {@inheritDoc}
     * @see \Fisharebest\Webtrees\Module\AbstractModule::getDescription()
     */
    public function getDescription()
    {
        return I18N::translate('Manage webtrees translation.');
    }
    
    /**
     * {@inheritDoc}
     * @see \Fisharebest\Webtrees\Module\AbstractModule::modAction()
     */
    public function modAction($mod_action) {
        Dispatcher::getInstance()->handle($this, $mod_action);
    }

    /**
     * {@inheritDoc}
     * @see \Fisharebest\Webtrees\Module\ModuleConfigInterface::getConfigLink()
     */
    public function getConfigLink()
    {
        return 'module.php?mod=' . $this->getName() . '&amp;mod_action=Translation@status';
    }
    
}