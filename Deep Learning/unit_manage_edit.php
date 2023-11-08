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
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Module\DeepLearning\Domain\UnitGateway;
use Gibbon\Module\DeepLearning\Domain\UnitAuthorGateway;
use Gibbon\Module\DeepLearning\Domain\UnitTagGateway;
use Gibbon\Services\Format;

if (isActionAccessible($guid, $connection2, '/modules/Deep Learning/unit_manage_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $deepLearningUnitID = $_GET['deepLearningUnitID'] ?? '';

    $page->breadcrumbs
        ->add(__m('Manage Units'), 'unit_manage.php')
        ->add(__m('Edit Unit'));

    if (empty($deepLearningUnitID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    $values = $container->get(UnitGateway::class)->getByID($deepLearningUnitID);

    if (empty($values)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    $form = Form::create('experience', $session->get('absoluteURL').'/modules/'.$session->get('module').'/unit_manage_editProcess.php');
    $form->setFactory(DatabaseFormFactory::create($pdo));

    $form->addHiddenValue('address', $session->get('address'));
    $form->addHiddenValue('deepLearningUnitID', $deepLearningUnitID);

    // DETAILS
    $form->addRow()->addHeading(__('Basic Details'));

    $row = $form->addRow();
        $row->addLabel('name', __m('Unit Name'))->description(__m('Must be unique within this Deep Learning event.'));
        $row->addTextField('name')->required()->maxLength(90);

    $row = $form->addRow();
        $row->addLabel('status', __('Status'));
        $row->addSelect('status')->fromArray(['Draft' => __m('Draft'), 'Published' => __m('Published')])->required();

    // DISPLAY
    $form->addRow()->addHeading(__('Display'));

    $row = $form->addRow();
        $row->addLabel('headerImage', __m('Header Image'))->description(__m('A header image to display on the experience page.'));
        $row->addFileUpload('headerImageFile')->accepts('.jpg,.jpeg,.gif,.png')
            ->setAttachment('headerImage', $session->get('absoluteURL'), $values['headerImage']);

    $row = $form->addRow();
        $row->addLabel('cost', __m('Estimated Cost'))->description(__m('Experiences can customise the actual cost.'));
        $row->addCurrency('cost')->maxLength(10);

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
    $form->addRow()->addHeading(__('Resources'));

    $row = $form->addRow();
        $col = $row->addColumn()->setClass('');
        $col->addLabel('teachersNotes', __('Teacher\'s Notes'));
        $col->addEditor('teachersNotes', $guid);

    // AUTHORS
    $form->addRow()->addHeading(__('Authors'));

    // Custom Block Template
    $addBlockButton = $form->getFactory()->createButton(__m('Add Author'))->addClass('addBlock');

    $blockTemplate = $form->getFactory()->createTable()->setClass('blank');
    $row = $blockTemplate->addRow()->addClass('w-full flex justify-between items-center mt-1 ml-2');
        $row->addSelectStaff('gibbonPersonID')->photo(true, 'small')->setClass('flex-1 mr-1')->required()->placeholder()
            ->append("<input type='hidden' id='deepLearningUnitAuthorID' name='deepLearningUnitAuthorID' value=''/>");
        $row->addTextField('lastEdit')->readOnly()->setClass('text-xs text-gray-600 italic');

    // Custom Blocks
    $row = $form->addRow();
    $customBlocks = $row->addCustomBlocks('authors', $session, true)
        ->fromTemplate($blockTemplate)
        ->settings(['inputNameStrategy' => 'object', 'addOnEvent' => 'click'])
        ->placeholder(__m('Authors will be listed here...'))
        ->addToolInput($addBlockButton);

    $authors = $container->get(UnitAuthorGateway::class)->selectAuthorsByUnit($deepLearningUnitID);
    while ($person = $authors->fetch()) {
        $customBlocks->addBlock($person['deepLearningUnitAuthorID'], [
            'deepLearningUnitAuthorID' => $person['deepLearningUnitAuthorID'],
            'gibbonPersonID'           => $person['gibbonPersonID'],
            'lastEdit'                 => !empty($person['timestamp']) ? __m('Last Edit').': '.Format::dateTime($person['timestamp']) : '',
        ]);
    }

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    $form->loadAllValuesFrom($values);

    echo $form->getOutput();
}
