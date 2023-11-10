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
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Module\DeepLearning\Domain\EventGateway;
use Gibbon\Module\DeepLearning\Domain\ExperienceGateway;
use Gibbon\Module\DeepLearning\Domain\EnrolmentGateway;
use Gibbon\Forms\DatabaseFormFactory;

if (isActionAccessible($guid, $connection2, '/modules/Deep Learning/enrolment_manage_byPerson_addEdit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $params = [
        'mode'                    => $_REQUEST['mode'] ?? '',
        'deepLearningEnrolmentID' => $_REQUEST['deepLearningEnrolmentID'] ?? '',
        'deepLearningEventID'     => $_REQUEST['deepLearningEventID'] ?? '',
    ];

    $page->breadcrumbs
        ->add(__m('Manage Enrolment'), 'enrolment_manage_byPerson.php')
        ->add($params['mode'] == 'add' ? __m('Add Enrolment') : __m('Edit Enrolment'));

    if ($params['mode'] == 'add' && isset($_GET['editID'])) {
        $page->return->setEditLink($session->get('absoluteURL').'/index.php?q=/modules/Deep Learning/enrolment_manage_byPerson_addEdit.php&mode=edit&deepLearningEnrolmentID='.$_GET['editID']);
    }

    $eventGateway = $container->get(EventGateway::class);
    $experienceGateway = $container->get(ExperienceGateway::class);
    $enrolmentGateway = $container->get(EnrolmentGateway::class);
    $settingGateway = $container->get(SettingGateway::class);

    // Get events and experiences
    $events = $eventGateway->selectEventsBySchoolYear($session->get('gibbonSchoolYearID'));
    $experiences = $experienceGateway->selectExperiences()->fetchAll();

    $experienceList = array_combine(array_column($experiences, 'deepLearningExperienceID'), array_column($experiences, 'name'));
    $experienceChainedTo = array_combine(array_column($experiences, 'deepLearningExperienceID'), array_column($experiences, 'deepLearningEventID'));

    $enrolmentChoices = $settingGateway->getSettingByScope('Deep Learning', 'enrolmentChoices');

    $values = $params['mode'] == 'edit'
        ? $enrolmentGateway->getByID($params['deepLearningEnrolmentID'])
        : [];

    // FORM
    $form = Form::create('enrolment', $session->get('absoluteURL').'/modules/'.$session->get('module').'/enrolment_manage_byPerson_addEditProcess.php');
    $form->setFactory(DatabaseFormFactory::create($pdo));

    $form->addHiddenValue('address', $session->get('address'));
    $form->addHiddenValue('mode', $params['mode']);
    
    $row = $form->addRow();
        $row->addLabel('deepLearningEventID', __('Event'));
        $row->addSelect('deepLearningEventID')
            ->fromResults($events, 'groupBy')
            ->required()
            ->placeholder()
            ->selected($params['deepLearningEventID'] ?? '')
            ->readOnly($params['mode'] == 'edit');

    $row = $form->addRow();
        $row->addLabel('gibbonPersonID', __('Person'));
        $row->addSelectUsers('gibbonPersonID', $session->get('gibbonSchoolYearID'), ['includeStudents' => true])
            ->required()
            ->placeholder()
            ->readOnly($params['mode'] == 'edit');

    $row = $form->addRow();
        $row->addLabel('deepLearningExperienceID', __('Experience'));
        $row->addSelect('deepLearningExperienceID')
            ->fromArray($experienceList)
            ->chainedTo('deepLearningEventID', $experienceChainedTo)
            ->required()
            ->placeholder();

    $row = $form->addRow();
        $row->addLabel('status', __('Status'));
        $row->addSelect('status')
            ->fromArray(['Pending' => __m('Pending'), 'Confirmed' => __m('Confirmed')])
            ->required()
            ->selected('Confirmed');

    $row = $form->addRow();
        $row->addLabel('notes', __('Notes'));
        $row->addTextArea('notes')->setRows(2);
    
    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    $form->loadAllValuesFrom($values);

    echo $form->getOutput();
}
