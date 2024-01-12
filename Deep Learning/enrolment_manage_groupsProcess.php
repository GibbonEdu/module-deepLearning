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

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$params = [
    'deepLearningEventID' => $_POST['deepLearningEventID'] ?? '',
    'sidebar'             => 'false',
];

$URL = $session->get('absoluteURL').'/index.php?q=/modules/Deep Learning/enrolment_manage_groups.php&'.http_build_query($params);

if (isActionAccessible($guid, $connection2, '/modules/Deep Learning/enrolment_manage_groups.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $partialFail = false;

    $experienceGateway = $container->get(ExperienceGateway::class);
    $enrolmentGateway = $container->get(EnrolmentGateway::class);
    $choiceGateway = $container->get(ChoiceGateway::class);

    $enrolmentList = $_POST['person'] ?? [];

    if (empty($params['deepLearningEventID']) || empty($enrolmentList)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    $unassigned = [];

    // Update student enrolments
    foreach ($enrolmentList as $gibbonPersonID => $deepLearningExperienceID) {
        if (empty($deepLearningExperienceID)) {
            $unassigned[] = $gibbonPersonID;
            continue;
        }

        // Connect the choice to the enrolment, for future queries and weighting
        $choice = $choiceGateway->getChoiceByExperienceAndPerson($deepLearningExperienceID, $gibbonPersonID);
        $choiceNumber = intval($choice['choice'] ?? 0);

        $selectBy = [
            'deepLearningEventID' => $params['deepLearningEventID'],
            'gibbonPersonID'      => $gibbonPersonID,
        ];
        $enrolment = $enrolmentGateway->selectBy($selectBy)->fetch();

        if (!empty($enrolment)) {
            // Update and existing enrolment
            $data = [
                'deepLearningExperienceID' => $deepLearningExperienceID,
                'deepLearningChoiceID'     => $choice['deepLearningChoiceID'] ?? null,
            ];

            $updated = $enrolmentGateway->updateWhere($selectBy, $data);
        } else {
            // Add a new enrolment
            $data = [
                'deepLearningExperienceID' => $deepLearningExperienceID,
                'deepLearningEventID'      => $params['deepLearningEventID'],
                'deepLearningChoiceID'     => $choice['deepLearningChoiceID'] ?? null,
                'gibbonPersonID'           => $gibbonPersonID,
                'status'                   => 'Confirmed',
                'notes'                    => '',
                'timestampCreated'         => date('Y-m-d H:i:s'),
                'gibbonPersonIDCreated'    => $session->get('gibbonPersonID'),
            ];

            $inserted = $enrolmentGateway->insert($data);
            $partialFail &= !$inserted;
        }
    }

    // Remove enrolments that have been unassigned
    foreach ($unassigned as $gibbonPersonID) {
        $enrolmentGateway->deleteWhere([
            'deepLearningEventID' => $params['deepLearningEventID'],
            'gibbonPersonID' => $gibbonPersonID,
        ]);
    }

    $URL .= $partialFail
        ? "&return=warning1"
        : "&return=success0";
    header("Location: {$URL}");
}
