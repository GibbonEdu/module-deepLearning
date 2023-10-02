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
use Gibbon\Services\Format;
use Gibbon\Module\DeepLearning\Domain\EventGateway;
use Gibbon\Module\DeepLearning\Domain\DateGateway;

if (isActionAccessible($guid, $connection2, '/modules/Deep Learning/events_manage_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $deepLearningEventID = $_GET['deepLearningEventID'] ?? '';

    $page->breadcrumbs
        ->add(__m('Manage Events'), 'events_manage.php')
        ->add(__m('Edit Event'));

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    if (empty($deepLearningEventID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    $values = $container->get(EventGateway::class)->getByID($deepLearningEventID);

    if (empty($values)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    $editLink = '';
    if (isset($_GET['editID'])) {
        $editLink = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Deep Learning/events_manage_edit.php&deepLearningEventID='.$_GET['editID'];
    }
    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], $editLink, null);
    }

    $form = Form::create('event', $session->get('absoluteURL').'/modules/'.$session->get('module').'/events_manage_editProcess.php');
    $form->setFactory(DatabaseFormFactory::create($pdo));

    $form->addHiddenValue('address', $session->get('address'));
    $form->addHiddenValue('deepLearningEventID', $deepLearningEventID);

    $row = $form->addRow();
        $row->addLabel('name', __('Name'))->description(__('Must be unique.'));
        $row->addTextField('name')->required()->maxLength(40);

    $row = $form->addRow();
        $row->addLabel('description', __('Description'));
        $row->addTextField('description')->required();

    $row = $form->addRow();
        $row->addLabel('backgroundImage', __m('Background Image'))->description(__m('A banner image for the event'));
        $row->addFileUpload('backgroundImage')->accepts('.jpg,.jpeg,.gif,.png');

    $row = $form->addRow();
        $row->addLabel('active', __('Active'));
        $row->addYesNo('active')->required();

    // DATES
    $form->addRow()->addHeading(__('Event Dates'));

    // Custom Block Template
    $addBlockButton = $form->getFactory()->createButton(__('Add Event Date'))->addClass('addBlock');

    $blockTemplate = $form->getFactory()->createTable()->setClass('blank');
    $row = $blockTemplate->addRow();
        $row->addTextField('name')->setClass('w-2/3 pr-10 title')->required()->placeholder(__('Name'));
    $row = $blockTemplate->addRow();
        $row->addDate('date')->setClass('w-48 mt-1')->required()->placeholder(__('Date'))
          ->append("<input type='hidden' id='deepLearningDateID' name='deepLearningDateID' value=''/>");

    // Custom Blocks
    $row = $form->addRow();
    $customBlocks = $row->addCustomBlocks('dates', $session)
        ->fromTemplate($blockTemplate)
        ->settings(array('inputNameStrategy' => 'object', 'addOnEvent' => 'click'))
        ->placeholder(__('Event dates will be listed here...'))
        ->addToolInput($addBlockButton);

    $dataBlocks = $container->get(DateGateway::class)->selectDates($deepLearningEventID);
    while ($rowBlocks = $dataBlocks->fetch()) {
      $smart = array(
        'name' => $rowBlocks['name'],
        'date' => Format::date($rowBlocks['date']),
        'deepLearningDateID' => $rowBlocks['deepLearningDateID']
      );
      $customBlocks->addBlock($rowBlocks['deepLearningDateID'], $smart);
    }

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    $form->loadAllValuesFrom($values);

    echo $form->getOutput();
}
?>

<script>
$(document).on('click', '.addBlock', function () {
    $('input[id^="date"]').removeClass('hasDatepicker').datepicker({onSelect: function(){$(this).blur();}, onClose: function(){$(this).change();} });
});
</script>
