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
namespace MyArtJaub\Webtrees\Tools\Module\MajTranslationTool\Views;

use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Module\AbstractModule;
use MyArtJaub\Webtrees\Mvc\View\AbstractView;

/**
 * View for Translation@status
 */
class TranslationStatusView extends AbstractView {
        
	/**
	 * {@inhericDoc}
	 * @see \MyArtJaub\Webtrees\Mvc\View\AbstractView::renderContent()
	 */
    protected function renderContent() {
        
        /** @var AbstractModule $module  */
        $module = $this->data->get('module'); 
        $table_id = $this->data->get('table_id');
        $missing_translations = $this->data->get('missing_translations');
        $non_used_translations = $this->data->get('non_used_translations');
        $loading_stats = $this->data->get('loading_stats');
        
        ?>        
        <ol class="breadcrumb small">
        	<li><a href="admin.php"><?php echo I18N::translate('Control panel'); ?></a></li>
			<li><a href="admin_modules.php"><?php echo I18N::translate('Module administration'); ?></a></li>
			<li class="active"><?php echo $module->getTitle(); ?></li>
			<li class="active"><?php echo $this->data->get('title'); ?></li>
		</ol>
		
		<h1><?php echo $this->data->get('title'); ?></h1>
		
		<h3><?php echo I18N::translate('Paths for source code'); ?></h3>
		
		<?php foreach($this->data->get('source_code_paths') as $path) { ?>
			<samp><?php echo $path; ?></samp><br>
		<?php } ?>
		
		<h3><?php echo I18N::translate('Statistics'); ?></h3>
		
		<table class="table table-bordered">
			<tr>
				<td><?php echo I18N::translate('Number of translations'); ?></td>
				<td><?php echo I18N::number($loading_stats['nbTranslations']); ?></td>
			</tr>
			<tr>
				<td><?php echo I18N::translate('Number of translations in MyArtJaub modules'); ?></td>
				<td><?php echo I18N::number($loading_stats['nbMajTranslations']); ?></td>
			</tr>
			<tr>
				<td><?php echo I18N::translate('Number of translations found in code'); ?></td>
				<td><?php echo I18N::number($loading_stats['nbTranslationsFound']); ?></td>
			</tr>
		</table>
		
		<?php if(count($missing_translations) > 0) { ?>
		<h3><?php echo I18N::translate('Missing translations (%s)', I18N::number(count($missing_translations))); ?></h3>
		
		<table id="table_missing_<?php echo $table_id; ?>" class="table table-condensed table-bordered">
			<thead>
				<tr>
					<th><?php echo I18N::translate('Message'); ?></th>
					<th><?php echo I18N::translate('References'); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($missing_translations as $missing) { ?>
				<tr>
					<td><?php echo $missing['text']; ?></td>
					<td><?php echo implode('<br>', $missing['references']); ?></td>
				</tr>
				<?php } ?>
			</tbody>
		</table>
		<?php } ?>
		
		<?php if(count($non_used_translations) > 0) { ?>
		<h3><?php echo I18N::translate('Non used MyArtJaub translations (%s)', I18N::number(count($non_used_translations))); ?></h3>
		
		<table id="table_nonused_<?php echo $table_id; ?>" class="table table-condensed table-bordered">
			<thead>
				<tr>
					<th><?php echo I18N::translate('Message'); ?></th>
					<th><?php echo I18N::translate('References'); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($non_used_translations as $non_used) { ?>
				<tr>
					<td><?php echo $non_used['text']; ?></td>
					<td><?php echo implode('<br>', $non_used['references']); ?></td>
				</tr>
				<?php } ?>
			</tbody>
		</table>
		<?php } ?>
		
		<?php        
    }
    
}
 