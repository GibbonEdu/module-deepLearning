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
use Gibbon\Module\DeepLearning\Domain\UnitPhotoGateway;

if (isActionAccessible($guid, $connection2, '/modules/Deep Learning/unit_manage_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if (empty($highestAction)) {
        $page->addError(__('You do not have access to this action.'));
        return;
    }

    $deepLearningUnitID = $_GET['deepLearningUnitID'] ?? '';

    $page->breadcrumbs
        ->add(__m('Manage Units'), 'unit_manage.php')
        ->add(__m('Edit Unit'));

    if (empty($deepLearningUnitID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    $unitGateway = $container->get(UnitGateway::class);
    $values = $unitGateway->getByID($deepLearningUnitID);

    if (empty($values)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    $canEditUnit = $unitGateway->getUnitEditAccess($deepLearningUnitID, $session->get('gibbonPersonID'));
    if ($highestAction != 'Manage Units_all' && $canEditUnit != 'Y') {
        $page->addError(__m('You do not have edit access to this record.'));
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
        $row->addLabel('status', __('Status'))->description(__m('Only published units will be available to run experiences.'));
        $row->addSelect('status')->fromArray(['Draft' => __m('Draft'), 'Published' => __m('Published'), 'Retired' => __m('Retired')])->required();

    // DETAILS
    $form->addRow()->addHeading(__('Details'))->append(__m('All experiences running this unit will use these values.'));

    $row = $form->addRow();
        $row->addLabel('cost', __m('Cost'))->description(__m('Leave empty to not display a cost.'));
        $row->addCurrency('cost')->maxLength(10);

    $row = $form->addRow();
        $row->addLabel('location', __m('Location'))->description(__m('The general location this experience will take place at.'));
        $row->addTextField('location')->maxLength(255);

    $row = $form->addRow();
        $row->addLabel('provider', __m('Provider'))->description(__m('Leave blank if not using an external provider.'));
        $row->addTextField('provider')->maxLength(255);

    $row = $form->addRow();
        $row->addLabel('enrolmentMin', __('Minimum Enrolment'))->description(__m('Experience should not run below this number of students.'));
        $row->addNumber('enrolmentMin')->onlyInteger(true)->minimum(0)->maximum(999)->maxLength(3)->required();

    $row = $form->addRow();
        $row->addLabel('enrolmentMax', __('Maximum Enrolment'))->description(__('Enrolment should not exceed this number of students.'));
        $row->addNumber('enrolmentMax')->onlyInteger(true)->minimum(0)->maximum(999)->maxLength(3)->required();

    // DISPLAY
    $form->addRow()->addHeading(__('Display'))->append(__m('All experiences running this unit will use these images and descriptions.'));

    $row = $form->addRow();
        $row->addLabel('headerImage', __m('Header Image'))->description(__m('A header image to display on the experience page.'));
        $row->addFileUpload('headerImageFile')->accepts('.jpg,.jpeg,.gif,.png')
            ->setAttachment('headerImage', $session->get('absoluteURL'), $values['headerImage']);

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

    // PHOTOS
    $form->addRow()->addHeading(__('Photos'))->append(__m('These will be displayed on experiences running this unit. Captions are optional.'));

    $addBlockButton = $form->getFactory()->createButton(__m('Add Photo'))->addClass('addBlock');

    $blockTemplate = $form->getFactory()->createTable()->setClass('blank');
    $row = $blockTemplate->addRow()->addClass('w-full flex justify-between items-center mt-1 ml-2');
        $row->addFileUpload('fileUpload')->accepts('.jpg,.jpeg,.gif,.png')
            ->setAttachment('filePath', $session->get('absoluteURL'), '')
            ->append("<input type='hidden' id='deepLearningUnitPhotoID' name='deepLearningUnitPhotoID' value=''/>");
        $row->addTextField('caption')->setClass('w-4/5 ml-6 mr-6')->placeholder(__m('Caption'));

    // Custom Blocks
    $row = $form->addRow();
    $customBlocks = $row->addCustomBlocks('photos', $session, true)
        ->fromTemplate($blockTemplate)
        ->settings(['inputNameStrategy' => 'object', 'addOnEvent' => 'click', 'sortable' => true])
        ->placeholder(__m('Photos will be listed here...'))
        ->addToolInput($addBlockButton);

    $photos = $container->get(UnitPhotoGateway::class)->selectPhotosByUnit($deepLearningUnitID);
    while ($photo = $photos->fetch()) {
        $customBlocks->addBlock($photo['deepLearningUnitPhotoID'], [
            'deepLearningUnitPhotoID' => $photo['deepLearningUnitPhotoID'],
            'filePath'                => $photo['filePath'],
            'caption'                 => $photo['caption'],
        ]);
    }

    // AUTHORS
    $form->addRow()->addHeading(__('Authors'))->append(__m('All authors have edit access to this unit.'));

    // Custom Block Template
    $addBlockButton = $form->getFactory()->createButton(__m('Add Author'))->addClass('addBlock');

    $blockTemplate = $form->getFactory()->createTable()->setClass('blank');
    $row = $blockTemplate->addRow()->addClass('w-full flex justify-between items-center mt-1 ml-2');
        $row->addSelectStaff('gibbonPersonID')->photo(true, 'small')->setClass('flex-1 mr-1')->required()->placeholder()
            ->append("<input type='hidden' id='deepLearningUnitAuthorID' name='deepLearningUnitAuthorID' value=''/>")
            ->append("<input type='hidden' id='gibbonPersonIDOriginal' name='gibbonPersonIDOriginal' value=''/>");
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
            'gibbonPersonIDOriginal'   => $person['gibbonPersonID'],
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
?>

<script>
$(document).ready(function () {

    $('input[id^=fileUpload][name^=photos]').each(function() {
        
        var filePath = $('input[id^=filePath]', $(this).parent());
        if (filePath != undefined) {
            var img = document.createElement("img");
            img.src = "<?php echo $session->get('absoluteURL'); ?>/"+filePath.val();
            img.style.height = '100px';
            img.style.maxWidth = '200px';

            $(this).parent().append(img);

            $('.input-box-meta', $(this).parent()).hide();
            $(this).parent().parent().attr('title', '');
            $(this).hide();
        }
    });
});
</script>
