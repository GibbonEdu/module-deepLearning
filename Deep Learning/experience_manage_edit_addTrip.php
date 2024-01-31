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
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Module\DeepLearning\Domain\ExperienceTripGateway;
use Gibbon\Module\DeepLearning\Domain\EventDateGateway;

if (isActionAccessible($guid, $connection2, '/modules/Deep Learning/experience_manage_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $highestAction = getHighestGroupedAction($guid, '/modules/Deep Learning/experience_manage_edit.php', $connection2);
    if (empty($highestAction)) {
        $page->addError(__('You do not have access to this action.'));
        return;
    }

    // Cancel out if Trip Planner not installed or not accessible
    $highestTripAction = getHighestGroupedAction($guid, '/modules/Trip Planner/trips_manage.php', $connection2);
    if (empty($highestTripAction)) {
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

    $experienceTripGateway = $container->get(ExperienceTripGateway::class);
    $experienceGateway = $container->get(ExperienceGateway::class);

    $experience = $experienceGateway->getExperienceDetailsByID($deepLearningExperienceID);
    if (empty($experience)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    $canEditExperience = $experienceGateway->getExperienceEditAccess($deepLearningExperienceID, $session->get('gibbonPersonID'));
    if ($highestAction != 'Manage Experiences_all' && $canEditExperience != 'Y') {
        $page->addError(__m('You do not have edit access to this record.'));
        return;
    }

    // $event = $container->get(EventGateway::class)->getByID($experience['deepLearningEventID']);
    // $unit = $container->get(UnitGateway::class)->getByID($experience['deepLearningUnitID']);

    $form = Form::create('experienceTrip', $session->get('absoluteURL').'/modules/'.$session->get('module').'/experience_manage_edit_addTripProcess.php');
    $form->setFactory(DatabaseFormFactory::create($pdo));

    $form->addHiddenValue('address', $session->get('address'));
    $form->addHiddenValue('deepLearningEventID', $experience['deepLearningEventID']);
    $form->addHiddenValue('deepLearningExperienceID', $deepLearningExperienceID);

    // DETAILS
    $options = ['new' => __m('New Trip Request'), 'existing' => __m('Existing Trip Request')];
    $row = $form->addRow();
        $row->addLabel('type', __('Type'));
        $row->addSelect('type')->fromArray($options)->required()->selected('new');

    $form->toggleVisibilityByClass('existingTrips')->onSelect('type')->when('existing');
    
    $existingTrips = $experienceTripGateway->selectTripRequestsByCreator($experience['gibbonSchoolYearID'], $session->get('gibbonPersonID'))->fetchKeyPair();
    $row = $form->addRow()->addClass('existingTrips');
        $row->addLabel('tripPlannerRequestID', __m('My Trip Requests'));
        $row->addSelect('tripPlannerRequestID')->fromArray($existingTrips)->required()->placeholder();

    $eventDates = $container->get(EventDateGateway::class)->selectDates($experience['deepLearningEventID']);
    $row = $form->addRow();
        $row->addLabel('deepLearningEventDateIDList', __m('Trip Days'));
        $row->addCheckbox('deepLearningEventDateIDList')->fromResults($eventDates)->addCheckAllNone()->checkAll();

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
