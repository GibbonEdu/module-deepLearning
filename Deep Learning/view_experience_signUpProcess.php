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
use Gibbon\Module\DeepLearning\Domain\SignUpGateway;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Module\DeepLearning\Domain\ExperienceGateway;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST, ['description' => 'HTML']);

$params = [
    'deepLearningEventID' => $_REQUEST['deepLearningEventID'] ?? '',
    'deepLearningExperienceID' => $_REQUEST['deepLearningExperienceID'] ?? '',
];

$URL = $session->get('absoluteURL').'/index.php?q=/modules/Deep Learning/view_experience.php&sidebar=false&'.http_build_query($params);
$URLSuccess = $session->get('absoluteURL').'/index.php?q=/modules/Deep Learning/viewMyDL.php&'.http_build_query($params);

if (isActionAccessible($guid, $connection2, '/modules/Deep Learning/view_experience_signUp.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $partialFail = false;

    $eventGateway = $container->get(EventGateway::class);
    $experienceGateway = $container->get(ExperienceGateway::class);
    $signUpGateway = $container->get(SignUpGateway::class);
    $settingGateway = $container->get(SettingGateway::class);
    
    $gibbonPersonID = $_POST['gibbonPersonID'] ?? '';
    $choices = $_POST['choices'] ?? [];

    // Only users with manage permission can sign up a different user
    $canManageSignUp = isActionAccessible($guid, $connection2, '/modules/Deep Learning/signUp_manage.php');
    if (!$canManageSignUp) {
        $gibbonPersonID = $session->get('gibbonPersonID');
    }

    // Validate the required values are present
    if (empty($choices) || empty($gibbonPersonID) || empty($params['deepLearningEventID'])) {
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

    // Check that sign up is open based on the date
    $signUpIsOpen = false;
    if (!empty($event['accessOpenDate']) && !empty($event['accessCloseDate'])) {
        $accessOpenDate = DateTime::createFromFormat('Y-m-d H:i:s', $event['accessOpenDate'])->format('U');
        $accessCloseDate = DateTime::createFromFormat('Y-m-d H:i:s', $event['accessCloseDate'])->format('U');
        $now = (new DateTime('now'))->format('U');

        $signUpIsOpen = $accessOpenDate <= $now && $accessCloseDate >= $now;
    }

    if (!$signUpIsOpen) {
        $URL .= '&return=error4';
        header("Location: {$URL}");
        exit;
    }

    $signUps = $signUpGateway->selectSignUpsByPerson($params['deepLearningEventID'], $gibbonPersonID)->fetchGroupedUnique();
    $signUpChoices = $settingGateway->getSettingByScope('Deep Learning', 'signUpChoices');

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
        if ($choice <= 0 || $choice > $signUpChoices) {
            $URL .= '&return=error5';
            header("Location: {$URL}");
            exit;
        }

        // Prepare data to insert or update
        $signUpData = [
            'deepLearningExperienceID' => $deepLearningExperienceID,
            'deepLearningEventID'      => $params['deepLearningEventID'],
            'gibbonPersonID'           => $gibbonPersonID,
            'choice'                   => $choice,
            'timestampModified'        => date('Y-m-d H:i:s'),
            'gibbonPersonIDModified'   => $session->get('gibbonPersonID'),
        ];

        $deepLearningSignUpID = $signUps[$choice]['deepLearningSignUpID'] ?? '';

        if (!empty($deepLearningSignUpID)) {
            $partialFail &= !$signUpGateway->update($deepLearningSignUpID, $signUpData);
        } else {
            $signUpData['timestampCreated'] = date('Y-m-d H:i:s');
            $signUpData['gibbonPersonIDCreated'] = $session->get('gibbonPersonID');

            $deepLearningSignUpID = $signUpGateway->insert($signUpData);
            $partialFail &= !$deepLearningSignUpID;
        }

        $choiceIDs[] = str_pad($deepLearningSignUpID, 12, '0', STR_PAD_LEFT);
    }

    // Cleanup sign ups that have been deleted
    $signUpGateway->deleteSignUpsNotInList($params['deepLearningEventID'], $gibbonPersonID, $choiceIDs);

    if ($partialFail) {
        $URL .= '&return=warning1';
        header("Location: {$URL}");
        exit;
    }

    $URLSuccess .= '&return=success1';
    header("Location: {$URLSuccess}");
}