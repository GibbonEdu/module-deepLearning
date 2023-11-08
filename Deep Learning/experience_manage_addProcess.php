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
use Gibbon\Module\DeepLearning\Domain\UnitGateway;
use Gibbon\Module\DeepLearning\Domain\EventGateway;
use Gibbon\Module\DeepLearning\Domain\ExperienceGateway;
use Gibbon\Module\DeepLearning\Domain\StaffGateway;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST, ['description' => 'HTML']);

$params = [
    'gibbonSchoolYearID' => $_REQUEST['gibbonSchoolYearID'] ?? $session->get('gibbonSchoolYearID'),
    'search'             => $_REQUEST['search'] ?? ''
];

$URL = $session->get('absoluteURL').'/index.php?q=/modules/Deep Learning/experience_manage_add.php&'.http_build_query($params);

if (isActionAccessible($guid, $connection2, '/modules/Deep Learning/experience_manage_add.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $partialFail = false;

    $experienceGateway = $container->get(ExperienceGateway::class);
    $staffGateway = $container->get(StaffGateway::class);
    $settingGateway = $container->get(SettingGateway::class);
    
    $data = [
        'deepLearningEventID'    => $_POST['deepLearningEventID'] ?? '',
        'deepLearningUnitID'     => $_POST['deepLearningUnitID'] ?? '',
        'gibbonPersonIDCreated'  => $session->get('gibbonPersonID'),
        'gibbonPersonIDModified' => $session->get('gibbonPersonID'),
    ];

    // Validate the required values are present
    if (empty($data['deepLearningEventID']) || empty($data['deepLearningUnitID'])) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    // Validate that the relational data exists
    $event = $container->get(EventGateway::class)->getByID($data['deepLearningEventID']);
    $unit = $container->get(UnitGateway::class)->getByID($data['deepLearningUnitID']);
    if (empty($event) || empty($unit)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    // Set the default values for the experience
    $data['name'] = $unit['name'];
    $data['cost'] = $unit['cost'];
    $data['enrolmentMin'] = $settingGateway->getSettingByScope('Deep Learning', 'enrolmentMin');
    $data['enrolmentMax'] = $settingGateway->getSettingByScope('Deep Learning', 'enrolmentMax');

    // Validate that this record is unique
    if (!$experienceGateway->unique($data, ['name', 'deepLearningEventID'])) {
        $URL .= '&return=error7';
        header("Location: {$URL}");
        exit;
    }

    // Create the record
    $deepLearningExperienceID = $experienceGateway->insert($data);

    if (empty($deepLearningExperienceID)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    // Create the staff records
    $staff = $_POST['staff'] ?? '';
    foreach ($staff as $person) {
        $deepLearningStaffID = $staffGateway->insert([
            'deepLearningExperienceID' => $deepLearningExperienceID,
            'gibbonPersonID'           => $person['gibbonPersonID'],
            'role'                     => $person['role'] ?? 'Assistant',
            'canEdit'                  => $person['canEdit'] ?? 'N',
        ]);

        $partialFail = !$deepLearningStaffID;
    }
    
    $URL .= $partialFail
        ? "&return=warning1"
        : "&return=success0&editID=$deepLearningExperienceID";

    header("Location: {$URL}");
}
