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

namespace MyArtJaub\Webtrees\Module\TranslationTool\Http\RequestHandlers;

use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Exceptions\HttpNotFoundException;
use Fisharebest\Webtrees\Http\ViewResponseTrait;
use Fisharebest\Webtrees\Services\ModuleService;
use MyArtJaub\Webtrees\Module\TranslationTool\TranslationToolModule;
use MyArtJaub\Webtrees\Module\TranslationTool\Model\TranslationsAnalyzer;
use MyArtJaub\Webtrees\Module\TranslationTool\Services\SourceCodeService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Request handler for displaying the status of translations in MyArtJaub modules
 */
class TranslationStatus implements RequestHandlerInterface
{
    use ViewResponseTrait;

    /** @var ?TranslationToolModule $module */
    private $module;

    /** @var SourceCodeService $sourcecode_service */
    private $sourcecode_service;

    /**
     * Constructor for TranslationStatus request handler
     *
     * @param ModuleService $module_service
     * @param SourceCodeService $sourcecode_service
     */
    public function __construct(ModuleService $module_service, SourceCodeService $sourcecode_service)
    {
        $this->module = $module_service->findByInterface(TranslationToolModule::class)->first();
        $this->sourcecode_service = $sourcecode_service;
    }

    /**
     * {@inheritDoc}
     * @see \Psr\Http\Server\RequestHandlerInterface::handle()
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->layout = 'layouts/administration';

        if ($this->module === null) {
            throw new HttpNotFoundException(I18N::translate('The attached module could not be found.'));
        }

        $locale = I18N::locale();
        $sourceCodePaths = $this->sourcecode_service->sourceCodePaths();
        $translation_analyser = new TranslationsAnalyzer(
            $this->sourcecode_service,
            $sourceCodePaths,
            $locale->languageTag()
        );

        return $this->viewResponse($this->module->name() . '::status', [
            'title'                 =>  $this->module->title(),
            'language'              =>  $locale->endonym(),
            'source_code_paths'     =>  $sourceCodePaths->flatten()->sort(),
            'translations_stats'    =>  $translation_analyser->translationsStatictics(),
            'missing_translations'  =>  $translation_analyser->missingTranslations(),
            'non_used_translations' =>  $translation_analyser->nonUsedMajTranslations()
        ]);
    }
}
