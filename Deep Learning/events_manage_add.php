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

if (isActionAccessible($guid, $connection2, '/modules/Deep Learning/events_manage_add.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs
        ->add(__m('Manage Events'), 'events_manage.php')
        ->add(__m('Add Event'));

    if (isset($_GET['editID'])) {
        $page->return->setEditLink($session->get('absoluteURL').'/index.php?q=/modules/Deep Learning/events_manage_edit.php&deepLearningEventID='.$_GET['editID']);
    }

    $form = Form::create('event', $session->get('absoluteURL').'/modules/'.$session->get('module').'/events_manage_addProcess.php');
    $form->setFactory(DatabaseFormFactory::create($pdo));

    $form->addHiddenValue('address', $session->get('address'));

    $form->addRow()->addHeading(__('Basic Details'));

    $row = $form->addRow();
        $row->addLabel('name', __('Name'))->description(__('Must be unique for this school year.'));
        $row->addTextField('name')->required()->maxLength(90);

    $row = $form->addRow();
        $row->addLabel('nameShort', __('Short Name'))->description(__('Must be unique for this school year.'));
        $row->addTextField('nameShort')->required()->maxLength(12);

    // $row = $form->addRow();
    //     $row->addLabel('description', __('Description'));
    //     $row->addTextField('description');

    $row = $form->addRow();
        $row->addLabel('gibbonYearGroupIDList', __('Year Groups'));
        $row->addCheckboxYearGroup('gibbonYearGroupIDList')
            ->addCheckAllNone()
            ->loadFromCSV($values);

    // DATES
    $form->addRow()->addHeading(__('Event Dates'));

    // Custom Block Template
    $addBlockButton = $form->getFactory()->createButton(__m('Add Event Date'))->addClass('addBlock');

    $blockTemplate = $form->getFactory()->createTable()->setClass('blank');
    $row = $blockTemplate->addRow()->addClass('w-3/4 flex justify-between mt-1 ml-2');
        $row->addTextField('name')->setClass('w-full mr-1 title')->required()->placeholder(__('Name'));
        $row->addDate('eventDate')->setClass('w-48')->required()->placeholder(__('Date'));

    // Custom Blocks
    $row = $form->addRow();
    $customBlocks = $row->addCustomBlocks('dates', $session)
        ->fromTemplate($blockTemplate)
        ->settings(array('inputNameStrategy' => 'object', 'addOnEvent' => 'click'))
        ->placeholder(__m('Event dates will be listed here...'))
        ->addToolInput($addBlockButton);

    // ACCESS
    $form->addRow()->addHeading(__m('Access'));

    $row = $form->addRow();
        $row->addLabel('active', __('Active'));
        $row->addYesNo('active')->required()->selected('N');

    // DISPLAY
    // $form->addRow()->addHeading(__('Display'));

    // $row = $form->addRow();
    //     $row->addLabel('backgroundImage', __m('Header Image'))->description(__m('A header image for the event'));
    //     $row->addFileUpload('backgroundImage')->accepts('.jpg,.jpeg,.gif,.png');

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
?>

<script>
$(document).on('click', '.addBlock', function () {
    $('input[id^="eventDate"]').removeClass('hasDatepicker').datepicker({onSelect: function(){$(this).blur();}, onClose: function(){$(this).change();} });
});
</script>
