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
use Gibbon\Module\DeepLearning\Domain\ExperienceGateway;
use Gibbon\Module\DeepLearning\Domain\ExperienceTripGateway;
use Gibbon\Module\DeepLearning\Domain\UnitGateway;
use Gibbon\Module\DeepLearning\Domain\StaffGateway;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

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

    $eventGateway = $container->get(EventGateway::class);
    $experienceGateway = $container->get(ExperienceGateway::class);
    $experienceTripGateway = $container->get(ExperienceTripGateway::class);

    $highestAction = getHighestGroupedAction($guid, '/modules/Deep Learning/experience_manage_edit.php', $connection2);
    $canEditExperience = $experienceGateway->getExperienceEditAccess($params['deepLearningExperienceID'], $session->get('gibbonPersonID'));
    if ($highestAction != 'Manage Experiences_all' && $canEditExperience != 'Y') {
        $URL .= '&return=error0';
        header("Location: {$URL}");
        exit;
    }
    
    $type = $_POST['type'] ?? '';
    $tripPlannerRequestID = $_POST['tripPlannerRequestID'] ?? '';
    $deepLearningEventDateIDList = !empty($_POST['deepLearningEventDateIDList']) ? implode(',', $_POST['deepLearningEventDateIDList']) : null;

    // Validate the required values are present
    if (empty($params['deepLearningExperienceID']) || empty($type) ) {
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

    if ($type == 'new') {

        $URL = $session->get('absoluteURL').'/index.php?q=/modules/Trip Planner/trips_submitRequest.php&'.http_build_query($params);

    } else if ($type == 'existing') {

        if (empty($tripPlannerRequestID)) {
            $URL .= '&return=error1';
            header("Location: {$URL}");
            exit;
        }

        $updated = $experienceTripGateway->attachTripRequest($params['deepLearningExperienceID'], $tripPlannerRequestID, $deepLearningEventDateIDList);
    }

    // Do stuff
    $URL .= $partialFail
        ? "&return=warning1"
        : "&return=success0";

    header("Location: {$URL}");
}
