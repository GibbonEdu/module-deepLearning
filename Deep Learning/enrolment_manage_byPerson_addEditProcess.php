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

use Gibbon\Data\Validator;
use Gibbon\Module\DeepLearning\Domain\EnrolmentGateway;
use Gibbon\Module\DeepLearning\Domain\ExperienceGateway;
use Gibbon\Module\DeepLearning\Domain\ChoiceGateway;
use Gibbon\Domain\User\UserGateway;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$params = [
    'mode'                    => $_POST['mode'] ?? '',
    'origin'                  => $_POST['origin'] ?? '',
    'deepLearningEventID'     => $_POST['deepLearningEventID'] ?? '',
    'deepLearningEnrolmentID' => $_POST['deepLearningEnrolmentID'] ?? '',
];

$URL = $params['origin'] == 'byEvent'
    ? $session->get('absoluteURL').'/index.php?q=/modules/Deep Learning/enrolment_manage_byPerson_addEdit.php&'.http_build_query($params)
    : $session->get('absoluteURL').'/index.php?q=/modules/Deep Learning/enrolment_manage_byPerson_addEdit.php&'.http_build_query($params);

if (isActionAccessible($guid, $connection2, '/modules/Deep Learning/enrolment_manage_byPerson_addEdit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $experienceGateway = $container->get(ExperienceGateway::class);
    $enrolmentGateway = $container->get(EnrolmentGateway::class);
    $choiceGateway = $container->get(ChoiceGateway::class);
    $userGateway = $container->get(UserGateway::class);

    $data = [
        'deepLearningExperienceID' => $_POST['deepLearningExperienceID'] ?? '',
        'deepLearningEventID'      => $_POST['deepLearningEventID'] ?? '',
        'gibbonPersonID'           => $_POST['gibbonPersonID'] ?? '',
        'status'                   => $_POST['status'] ?? '',
        'notes'                    => $_POST['notes'] ?? '',
        'timestampCreated'         => date('Y-m-d H:i:s'),
        'gibbonPersonIDCreated'    => $session->get('gibbonPersonID'),
    ];

    // Validate the required values are present
    if (empty($data['deepLearningExperienceID']) || empty($data['deepLearningEventID']) || empty($data['gibbonPersonID']) || empty($data['status'])) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    // Validate the database relationships exist
    if (!$experienceGateway->exists($data['deepLearningExperienceID']) || !$userGateway->exists($data['gibbonPersonID'])) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    // Check if this enrolment can be attached to a choice
    $choice = $choiceGateway->selectBy([
        'deepLearningExperienceID' => $data['deepLearningExperienceID'],
        'gibbonPersonID' => $data['gibbonPersonID']],
    )->fetch();

    $data['deepLearningChoiceID'] = !empty($choice)
        ? $choice['deepLearningChoiceID']
        : null;
    

    // Create the record
    $deepLearningEnrolmentID = $enrolmentGateway->insertAndUpdate($data, [
        'deepLearningExperienceID' => $_POST['deepLearningExperienceID'] ?? '',
        'status'                   => $_POST['status'] ?? '',
        'notes'                    => $_POST['notes'] ?? '',
    ]);
    
    $URL .= $params['mode'] == 'add' && empty($deepLearningEnrolmentID)
        ? "&return=warning1"
        : "&return=success0";

    header($params['mode'] == 'add'
        ? "Location: {$URL}&$editID={$deepLearningEnrolmentID}"
        : "Location: {$URL}"
    );
}
