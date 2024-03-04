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

use Gibbon\Services\Format;
use Gibbon\Data\Validator;
use Gibbon\Module\DeepLearning\Domain\EventGateway;
use Gibbon\Module\DeepLearning\Domain\UnitGateway;
use Gibbon\Module\DeepLearning\Domain\ExperienceGateway;
use Gibbon\Module\DeepLearning\Domain\StaffGateway;
use Gibbon\Module\DeepLearning\Domain\ExperienceTripGateway;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST, ['description' => 'HTML']);

$params = [
    'deepLearningExperienceID' => $_REQUEST['deepLearningExperienceID'] ?? '',
    'gibbonSchoolYearID'       => $_REQUEST['gibbonSchoolYearID'] ?? $session->get('gibbonSchoolYearID'),
    'search'                   => $_REQUEST['search'] ?? ''
];

$URL = $session->get('absoluteURL').'/index.php?q=/modules/Deep Learning/experience_manage_edit.php&'.http_build_query($params);

if (isActionAccessible($guid, $connection2, '/modules/Deep Learning/experience_manage_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $partialFail = false;

    $experienceGateway = $container->get(ExperienceGateway::class);
    $eventGateway = $container->get(EventGateway::class);
    $unitGateway = $container->get(UnitGateway::class);
    $staffGateway = $container->get(StaffGateway::class);

    $highestAction = getHighestGroupedAction($guid, $_POST['address'], $connection2);
    $canEditExperience = $experienceGateway->getExperienceEditAccess($params['deepLearningExperienceID'], $session->get('gibbonPersonID'));
    if ($highestAction != 'Manage Experiences_all' && $canEditExperience != 'Y') {
        $URL .= '&return=error0';
        header("Location: {$URL}");
        exit;
    }
    
    $data = [
        'deepLearningEventID'    => $_POST['deepLearningEventID'] ?? '',
        'name'                   => $_POST['name'] ?? '',
        'active'                 => $_POST['active'] ?? 'N',
        'gibbonYearGroupIDList'  => !empty($_POST['gibbonYearGroupIDList'])? implode(',', $_POST['gibbonYearGroupIDList']) : '',
        'gibbonPersonIDModified' => $session->get('gibbonPersonID'),
    ];

    // Validate the required values are present
    if (empty($params['deepLearningExperienceID']) || empty($data['name']) ) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    // Validate the database relationships exist
    if (!$experienceGateway->exists($params['deepLearningExperienceID'])) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    // Validate that this record is unique
    if (!$experienceGateway->unique($data, ['name', 'deepLearningEventID'], $params['deepLearningExperienceID'])) {
        $URL .= '&return=error7';
        header("Location: {$URL}");
        exit;
    }

    // Update the record
    $updated = $experienceGateway->update($params['deepLearningExperienceID'], $data);

    // Update the staff records
    $staff = $_POST['staff'] ?? '';
    $staffIDs = [];
    foreach ($staff as $person) {
        $staffData = [
            'deepLearningExperienceID' => $params['deepLearningExperienceID'],
            'gibbonPersonID'           => $person['gibbonPersonID'],
            'role'                     => $person['role'] ?? 'Support',
            'canEdit'                  => $person['canEdit'] ?? 'N',
            'notes'                    => $person['notes'] ?? '',
        ];

        $deepLearningStaffID = $person['deepLearningStaffID'] ?? '';

        if (!empty($deepLearningStaffID)) {
            $partialFail &= !$staffGateway->update($deepLearningStaffID, $staffData);
        } else {
            $deepLearningStaffID = $staffGateway->insert($staffData);
            $partialFail &= !$deepLearningStaffID;
        }

        $staffIDs[] = str_pad($deepLearningStaffID, 12, '0', STR_PAD_LEFT);
    }

    // Cleanup staff that have been deleted
    $staffGateway->deleteStaffNotInList($params['deepLearningExperienceID'], $staffIDs);

    // Sync Trip Planner Staff
    $experienceTripGateway = $container->get(ExperienceTripGateway::class);
    $tripPlanner = $experienceTripGateway->getTripPlannerModule();
    if (!empty($tripPlanner)) {
        $experienceTripGateway->syncTripStaff($params['deepLearningExperienceID']);
        $experienceTripGateway->syncTripStudents($params['deepLearningExperienceID']);
        $experienceTripGateway->syncTripGroups($params['deepLearningExperienceID']);
    }

    $URL .= !$updated || $partialFail
        ? "&return=warning1"
        : "&return=success0";

    header("Location: {$URL}");
}
