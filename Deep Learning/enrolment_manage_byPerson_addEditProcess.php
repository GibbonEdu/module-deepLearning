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
use Gibbon\Module\DeepLearning\Domain\EnrolmentGateway;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Module\DeepLearning\Domain\ExperienceGateway;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST, ['description' => 'HTML']);

$params = [
    'mode'                => $_POST['mode'] ?? '',
    'deepLearningEventID' => $_POST['deepLearningEventID'] ?? '',
    'gibbonPersonID'      => $_POST['gibbonPersonID'] ?? '',
];

$URL = $session->get('absoluteURL').'/index.php?q=/modules/Deep Learning/enrolment_manage_byPerson_addEdit.php&'.http_build_query($params);

if (isActionAccessible($guid, $connection2, '/modules/Deep Learning/enrolment_manage_byPerson_addEdit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $partialFail = false;

    $eventGateway = $container->get(EventGateway::class);
    $experienceGateway = $container->get(ExperienceGateway::class);
    $enrolmentGateway = $container->get(EnrolmentGateway::class);
    $settingGateway = $container->get(SettingGateway::class);

    $choices = $_POST['choices'] ?? [];

    // Validate the required values are present
    if (empty($choices) || empty($params['gibbonPersonID']) || empty($params['deepLearningEventID'])) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    // Validate the database relationships exist
    $event = $eventGateway->getEventDetailsByID($params['deepLearningEventID']);
    if (empty($event)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    $enrolments = $enrolmentGateway->selectSignUpsByPerson($params['deepLearningEventID'], $params['gibbonPersonID'])->fetchGroupedUnique();
    $enrolmentChoices = $settingGateway->getSettingByScope('Deep Learning', 'enrolmentChoices');

    // Update the sign up choices
    $choiceIDs = [];
    foreach ($choices as $choice => $deepLearningExperienceID) {
        $choice = intval($choice);

        // Validate the experience selected
        if (!$experienceGateway->exists($deepLearningExperienceID)) {
            $URL .= '&return=error5';
            header("Location: {$URL}");
            exit;
        }

        // Validate the choice number selected
        if ($choice <= 0 || $choice > $enrolmentChoices) {
            $URL .= '&return=error5';
            header("Location: {$URL}");
            exit;
        }

        // Prepare data to insert or update
        $enrolmentData = [
            'deepLearningExperienceID' => $deepLearningExperienceID,
            'deepLearningEventID'      => $params['deepLearningEventID'],
            'gibbonPersonID'           => $params['gibbonPersonID'],
            'choice'                   => $choice,
            'timestampModified'        => date('Y-m-d H:i:s'),
            'gibbonPersonIDModified'   => $session->get('gibbonPersonID'),
        ];

        $deepLearningSignUpID = $enrolments[$choice]['deepLearningSignUpID'] ?? '';

        if (!empty($deepLearningSignUpID)) {
            $partialFail &= !$enrolmentGateway->update($deepLearningSignUpID, $enrolmentData);
        } else {
            $enrolmentData['timestampCreated'] = date('Y-m-d H:i:s');
            $enrolmentData['gibbonPersonIDCreated'] = $session->get('gibbonPersonID');

            $deepLearningSignUpID = $enrolmentGateway->insert($enrolmentData);
            $partialFail &= !$deepLearningSignUpID;
        }

        $choiceIDs[] = str_pad($deepLearningSignUpID, 12, '0', STR_PAD_LEFT);
    }

    // Cleanup sign ups that have been deleted
    $enrolmentGateway->deleteSignUpsNotInList($params['deepLearningEventID'], $params['gibbonPersonID'], $choiceIDs);

    $URL .= $partialFail
        ? "&return=warning1"
        : "&return=success0";

    header($params['mode'] == 'add'
        ? "Location: {$URL}&editID={$params['deepLearningEventID']}&editID2={$params['gibbonPersonID']}"
        : "Location: {$URL}"
    );
}
