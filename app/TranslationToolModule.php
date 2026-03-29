<?php

/**
 * webtrees-mod-translationtool: MyArtJaub Translation Tool Module for webtrees
 *
 * @package MyArtJaub\Webtrees\Module
 * @subpackage TranslationTool
 * @author Jonathan Jaubart <dev@jaubart.com>
 * @copyright Copyright (c) 2016-2026, Jonathan Jaubart
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3
 */

declare(strict_types=1);

namespace MyArtJaub\Webtrees\Module\TranslationTool;

use Aura\Router\Map;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Http\Middleware\AuthAdministrator;
use Fisharebest\Webtrees\Module\AbstractModule;
use Fisharebest\Webtrees\Module\ModuleConfigInterface;
use Fisharebest\Webtrees\Module\ModuleConfigTrait;
use MyArtJaub\Webtrees\Module\ModuleMyArtJaubInterface;
use MyArtJaub\Webtrees\Module\ModuleMyArtJaubTrait;
use MyArtJaub\Webtrees\Module\TranslationTool\Http\RequestHandlers\TranslationStatus;

/**
 * MyArtJaub Translation tool Module.
 * This module helps with managing translations introduced by the MyArtJaub modules.
 */
class TranslationToolModule extends AbstractModule implements ModuleMyArtJaubInterface, ModuleConfigInterface
{
    use ModuleMyArtJaubTrait;
    use ModuleConfigTrait;

    #[\Override]
    public function title(): string
    {
        return I18N::translate('Translation Tool');
    }

    #[\Override]
    public function description(): string
    {
        return I18N::translate('Manage webtrees translation.');
    }

    #[\Override]
    public function customModuleVersion(): string
    {
        return '2.1.8-v.2';
    }

    #[\Override]
    public function isEnabledByDefault(): bool
    {
        return false;
    }

    #[\Override]
    public function customModuleSupportUrl(): string
    {
        return 'https://github.com/jon48/webtrees-mod-translationtool';
    }

    #[\Override]
    public function loadRoutes(Map $router): void
    {
        $router->attach('', '', static function (Map $router): void {


            $router->attach('', '/module-maj/translationtool', static function (Map $router): void {

                $router->extras([
                    'middleware' => [
                        AuthAdministrator::class,
                    ],
                ]);
                $router->get(TranslationStatus::class, '/status', TranslationStatus::class);
            });
        });
    }

    #[\Override]
    public function getConfigLink(): string
    {
        return route(TranslationStatus::class);
    }
}
