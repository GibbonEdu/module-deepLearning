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
use Gibbon\Comms\NotificationEvent;
use Gibbon\Services\Format;
use Gibbon\Domain\Students\StudentGateway;
use Gibbon\Module\DeepLearning\Domain\EventGateway;

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

    $eventGateway = $container->get(EventGateway::class);
    $experienceGateway = $container->get(ExperienceGateway::class);
    $enrolmentGateway = $container->get(EnrolmentGateway::class);
    $choiceGateway = $container->get(ChoiceGateway::class);
    $studentGateway = $container->get(StudentGateway::class);

    $enrolmentList = $_POST['person'] ?? [];

    // Validate the required values are present
    if (empty($params['deepLearningEventID']) || empty($enrolmentList)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    // Validate the database relationships exist
    $eventDetails = $eventGateway->getByID($params['deepLearningEventID']);
    if (empty($eventDetails)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    $unassigned = [];
    $changeList = [];

    // Update student enrolments
    foreach ($enrolmentList as $gibbonPersonID => $deepLearningExperienceID) {
        $change = '';

        // Get any existing enrolment
        $enrolment = $enrolmentGateway->getEventEnrolmentByPerson($params['deepLearningEventID'], $gibbonPersonID);
        $experience = $experienceGateway->getByID($deepLearningExperienceID, ['name']);
        $student = $studentGateway->selectActiveStudentByPerson($eventDetails['gibbonSchoolYearID'], $gibbonPersonID)->fetch();

        if (empty($student)) {
            $partialFail = true;
            continue;
        }

        if (empty($deepLearningExperienceID)) {
            // Record this removal so it can be updated in the database
            $unassigned[] = $gibbonPersonID;

            if (!empty($enrolment['deepLearningExperienceID'])) {
                $change = __m('Removed from');
            }
        } else {
            // Connect the choice to the enrolment, for future queries and weighting
            $choice = $choiceGateway->getChoiceByExperienceAndPerson($deepLearningExperienceID, $gibbonPersonID);
            $choiceNumber = intval($choice['choice'] ?? 0);

            if (!empty($enrolment)) {
                // Update and existing enrolment
                $data = [
                    'deepLearningExperienceID' => $deepLearningExperienceID,
                    'deepLearningChoiceID'     => $choice['deepLearningChoiceID'] ?? null,
                ];

                $updated = $enrolmentGateway->updateWhere([
                    'deepLearningEventID' => $params['deepLearningEventID'],
                    'gibbonPersonID'      => $gibbonPersonID,
                ], $data);

                if ($enrolment['deepLearningExperienceID'] != $data['deepLearningExperienceID']) {
                    $change = __m('Moved to');
                }
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
                $change = __m('Added to');
            }
        }

        if (!empty($change)) {
            $changeList[] = __m('{student} ({formGroup}) - <i>{change} {experience} ({status})</i>', [
                'student'    => Format::name('', $student['preferredName'], $student['surname'], 'Student', false, true),
                'formGroup'  => $student['formGroup'],
                'change'     => $change,
                'experience' => $experience['name'] ?? $enrolment['experienceName'] ?? '',
                'status'     => $data['status'] ?? $enrolment['status'] ?? __m('Removed'),
            ]);
        }
    }

    // Remove enrolments that have been unassigned
    foreach ($unassigned as $gibbonPersonID) {
        $enrolmentGateway->deleteWhere([
            'deepLearningEventID' => $params['deepLearningEventID'],
            'gibbonPersonID'      => $gibbonPersonID,
        ]);
    }

    // Raise a new notification event
    if (!empty($changeList)) {
        $event = new NotificationEvent('Deep Learning', 'Enrolment Changes');
        $event->setNotificationText(__('{person} has made the following changes to {event} enrolment:', [
            'person' => Format::name('', $session->get('preferredName'), $session->get('surname'), 'Staff', false, true),
            'event' => $eventDetails['name'] ?? __('Deep Learning'),
        ]).'<br/>'.Format::list($changeList));
        $event->setActionLink("/index.php?q=/modules/Deep Learning/report_overview.php&deepLearningEventID=".$params['deepLearningEventID']);
        $event->sendNotifications($pdo, $session);
    }

    $URL .= $partialFail
        ? "&return=warning1"
        : "&return=success0";
    header("Location: {$URL}");
}
