<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

use Gibbon\Forms\Form;
use Gibbon\Module\DeepLearning\Domain\EventGateway;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Module\DeepLearning\Domain\UnitTagGateway;

if (isActionAccessible($guid, $connection2, '/modules/Deep Learning/unit_manage_add.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs
        ->add(__m('Manage Units'), 'unit_manage.php')
        ->add(__m('Add Unit'));

    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if (empty($highestAction)) {
        $page->addError(__('You do not have access to this action.'));
        return;
    }

    if (isset($_GET['editID'])) {
        $page->return->setEditLink($session->get('absoluteURL').'/index.php?q=/modules/Deep Learning/unit_manage_edit.php&deepLearningUnitID='.$_GET['editID']);
    }

    $form = Form::create('unit', $session->get('absoluteURL').'/modules/'.$session->get('module').'/unit_manage_addProcess.php');
    $form->setFactory(DatabaseFormFactory::create($pdo));
    
    $form->addHiddenValue('address', $session->get('address'));

    // DETAILS
    $form->addRow()->addHeading(__('Basic Details'));

    $row = $form->addRow();
        $row->addLabel('name', __m('Unit Name'))->description(__('Must be unique.'));
        $row->addTextField('name')->required()->maxLength(90);

    $row = $form->addRow();
        $row->addLabel('gibbonPersonIDCreated', __m('Unit Author'));
        $row->addSelectStaff('gibbonPersonIDCreated')
            ->photo(true, 'small')
            ->required()
            ->placeholder()
            ->selected($session->get('gibbonPersonID'))
            ->readOnly($highestAction == 'Manage Units_my');

    $row = $form->addRow();
        $row->addLabel('status', __('Status'))->description(__m('Only published units will be available to run experiences.'));
        $row->addSelect('status')->fromArray(['Draft' => __m('Draft'), 'Published' => __m('Published')])->required();

    // DEFAULTS
    $form->addRow()->addHeading(__('Defaults'))->append(__m('Each experience can customise the final values.'));

    $row = $form->addRow();
        $row->addLabel('cost', __m('Cost'))->description(__m('Leave empty to not display a cost.'));
        $row->addCurrency('cost')->maxLength(10);

    $row = $form->addRow();
        $row->addLabel('location', __m('Location'))->description(__m('The general location this experience will take place at.'));
        $row->addTextField('location')->maxLength(255);

    $row = $form->addRow();
        $row->addLabel('provider', __m('Provider'))->description(__m('Leave blank if not using an external provider.'));
        $row->addTextField('provider')->maxLength(255);

    // DISPLAY
    $form->addRow()->addHeading(__('Display'))->append(__m('All experiences running this unit will use these images and descriptions.'));

    $row = $form->addRow();
        $row->addLabel('headerImage', __m('Header Image'))->description(__m('A header image to display on the experience page.'));
        $row->addFileUpload('headerImageFile')->accepts('.jpg,.jpeg,.gif,.png');

    $row = $form->addRow();
        $col = $row->addColumn()->setClass('');
        $col->addLabel('description', __('Description'));
        $col->addEditor('description', $guid);

    $tags = $container->get(UnitTagGateway::class)->selectAllTags()->fetchAll(\PDO::FETCH_COLUMN);

    $row = $form->addRow();
        $col = $row->addColumn();
        $col->addLabel('majors', __('Majors'));
        $col->addFinder('majors')
            ->fromArray($tags)
            ->setParameter('hintText', __('Type a tag...'))
            ->setParameter('allowFreeTagging', true);

    $row = $form->addRow();
        $col = $row->addColumn();
        $col->addLabel('minors', __('Minors'));
        $col->addFinder('minors')
            ->fromArray($tags)
            ->setParameter('hintText', __('Type a tag...'))
            ->setParameter('allowFreeTagging', true);

    // RESOURCES
    $form->addRow()->addHeading(__('Resources'))->append(__m('Instructions and files that will help a teacher run this unit.'));

    $row = $form->addRow();
        $col = $row->addColumn()->setClass('');
        $col->addLabel('teachersNotes', __('Teacher\'s Notes'));
        $col->addEditor('teachersNotes', $guid);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
