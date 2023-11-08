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
use Gibbon\Module\DeepLearning\Domain\ExperienceGateway;
use Gibbon\Data\Validator;

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
    $experienceGateway = $container->get(ExperienceGateway::class);
    $eventGateway = $container->get(EventGateway::class);
    $unitGateway = $container->get(UnitGateway::class);

    $data = [
        'name'                   => $_POST['name'] ?? '',
        'active'                 => $_POST['active'] ?? 'N',
        'cost'                   => !empty($_POST['cost']) ? $_POST['cost'] : null,
        'enrolmentMin'           => $_POST['enrolmentMin'] ?? null,
        'enrolmentMax'           => $_POST['enrolmentMax'] ?? null,
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
    foreach ($staff as $person) {
        $staffData = [
            'deepLearningExperienceID' => $deepLearningExperienceID,
            'gibbonPersonID'           => $person['gibbonPersonID'],
            'role'                     => $person['role'] ?? 'Assistant',
            'canEdit'                  => $person['canEdit'] ?? 'N',
        ];
        $deepLearningStaffID = $staffGateway->insertAndUpdate($staffData, $staffData);

        $partialFail = !$deepLearningStaffID;
    }

    $URL .= !$updated || $partialFail
        ? "&return=warning1"
        : "&return=success0";

    header("Location: {$URL}");
}
