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
use Gibbon\Module\DeepLearning\Domain\ExperienceGateway;
use Gibbon\Module\DeepLearning\Domain\EventGateway;
use Gibbon\Http\Url;
use Gibbon\Module\DeepLearning\Domain\StaffGateway;
use Gibbon\Module\DeepLearning\Domain\UnitGateway;

if (isActionAccessible($guid, $connection2, '/modules/Deep Learning/experience_manage_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if (empty($highestAction)) {
        $page->addError(__('You do not have access to this action.'));
        return;
    }

    $deepLearningExperienceID = $_GET['deepLearningExperienceID'] ?? '';
    $params = [
        'gibbonSchoolYearID' => $_REQUEST['gibbonSchoolYearID'] ?? $session->get('gibbonSchoolYearID'),
        'search'             => $_REQUEST['search'] ?? ''
    ];

    $page->breadcrumbs
        ->add(__m('Manage Experiences'), 'experience_manage.php', $params)
        ->add(__m('Edit Experience'));

    if (!empty($params['search'])) {
        $page->navigator->addSearchResultsAction(Url::fromModuleRoute('Deep Learning', 'experience_manage.php')->withQueryParams($params));
    }

    if (empty($deepLearningExperienceID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    $experienceGateway = $container->get(ExperienceGateway::class);
    $values = $experienceGateway->getByID($deepLearningExperienceID);
    if (empty($values)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    $canEditExperience = $experienceGateway->getExperienceEditAccess($deepLearningExperienceID, $session->get('gibbonPersonID'));
    if ($highestAction != 'Manage Experiences_all' && $canEditExperience != 'Y') {
        $page->addError(__m('You do not have edit access to this record.'));
        return;
    }

    $event = $container->get(EventGateway::class)->getByID($values['deepLearningEventID']);
    $unit = $container->get(UnitGateway::class)->getByID($values['deepLearningUnitID']);

    $form = Form::create('experience', $session->get('absoluteURL').'/modules/'.$session->get('module').'/experience_manage_editProcess.php');
    $form->setFactory(DatabaseFormFactory::create($pdo));

    $form->addHiddenValue('address', $session->get('address'));
    $form->addHiddenValue('deepLearningEventID', $values['deepLearningEventID']);
    $form->addHiddenValue('deepLearningExperienceID', $deepLearningExperienceID);

    // DETAILS
    $form->addRow()->addHeading(__('Basic Details'));

    $row = $form->addRow();
        $row->addLabel('event', __('Event'));
        $row->addTextField('event')->required()->setValue($event['name'])->readOnly();

    // $row = $form->addRow();
    //     $row->addLabel('unit', __('Unit'));
    //     $row->addTextField('unit')->required()->setValue($unit['name'])->readOnly();

    $row = $form->addRow();
        $row->addLabel('name', __m('Experience Name'))->description(__m('Must be unique within this Deep Learning event.'));
        $row->addTextField('name')->required()->maxLength(90);

    $row = $form->addRow();
        $row->addLabel('location', __m('Location'))->description(__m('The general location this experience will take place at.'));
        $row->addTextField('location')->maxLength(255);

    $row = $form->addRow();
        $row->addLabel('provider', __m('Provider'))->description(__m('Leave blank if not using an external provider.'));
        $row->addTextField('provider')->maxLength(255);

    $row = $form->addRow();
        $row->addLabel('active', __('Active'))->description(__m('Inactive experiences are only visible to users with view permissions.'));
        $row->addYesNo('active')->required()->selected('N');

    // ENROLMENT
    $form->addRow()->addHeading(__('Enrolment'));

    $row = $form->addRow();
        $row->addLabel('cost', __('Cost'))->description(__m('Leave empty to not display a cost.'));
        $row->addCurrency('cost')->maxLength(10);

    $row = $form->addRow();
        $row->addLabel('enrolmentMin', __('Minimum Enrolment'))->description(__m('Experience should not run below this number of students.'));
        $row->addNumber('enrolmentMin')->onlyInteger(true)->minimum(0)->maximum(999)->maxLength(3)->required();

    $row = $form->addRow();
        $row->addLabel('enrolmentMax', __('Maximum Enrolment'))->description(__('Enrolment should not exceed this number of students.'));
        $row->addNumber('enrolmentMax')->onlyInteger(true)->minimum(0)->maximum(999)->maxLength(3)->required();

    $yearGroups = $container->get(EventGateway::class)->selectYearGroupsByEvent($values['deepLearningEventID'])->fetchKeyPair();
    $row = $form->addRow();
        $row->addLabel('gibbonYearGroupIDList', __('Year Groups'))->description(__m('Defaults to the year groups for the event itself.'));
        $row->addCheckbox('gibbonYearGroupIDList')
            ->fromArray($yearGroups)
            ->addCheckAllNone()
            ->loadFromCSV($values);

    // STAFF
    $form->addRow()->addHeading(__('Staff'));

    // Custom Block Template
    $addBlockButton = $form->getFactory()->createButton(__m('Add Staff'))->addClass('addBlock');

    $roles = ['Trip Leader' => __m('Trip Leader'), 'Teacher' => __('Teacher'), 'Support' => __('Support')];
    $blockTemplate = $form->getFactory()->createTable()->setClass('blank');
    $row = $blockTemplate->addRow()->addClass('w-full flex justify-between items-center mt-1 ml-2');
        $row->addSelectStaff('gibbonPersonID')->photo(true, 'small')->setClass('flex-1 mr-1')->required()->placeholder();
        $row->addSelect('role')->fromArray($roles)->setClass('w-48 mr-1')->required()->placeholder();
        $row->addCheckbox('canEdit')->setLabelClass('w-32')->alignLeft()->setValue('Y')->description(__m('Can Edit?'))
            ->append("<input type='hidden' id='deepLearningStaffID' name='deepLearningStaffID' value=''/>");
    $row = $blockTemplate->addRow()->addClass('w-full flex justify-between items-center mt-1 ml-2');
        $row->addLabel('notesLabel',__('Notes'))->setClass('w-12 ml-3 text-right italic text-xxs');
        $row->addTextField('notes')->setClass('w-full');

    // Custom Blocks
    $row = $form->addRow();
    $customBlocks = $row->addCustomBlocks('staff', $session)
        ->fromTemplate($blockTemplate)
        ->settings(array('inputNameStrategy' => 'object', 'addOnEvent' => 'click'))
        ->placeholder(__m('Add a Trip Leader...'))
        ->addToolInput($addBlockButton);

    $staff = $container->get(StaffGateway::class)->selectStaffByExperience($deepLearningExperienceID);
    while ($person = $staff->fetch()) {
        $customBlocks->addBlock($person['deepLearningStaffID'], [
            'deepLearningStaffID' => $person['deepLearningStaffID'],
            'gibbonPersonID'      => $person['gibbonPersonID'],
            'role'                => $person['role'] ?? 'Support',
            'canEdit'             => $person['canEdit'] ?? 'N',
            'notes'               => $person['notes'] ?? '',
        ]);
    }

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    $form->loadAllValuesFrom($values);

    echo $form->getOutput();
}
