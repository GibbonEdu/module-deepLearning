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
use Gibbon\Module\DeepLearning\Domain\ExperienceGateway;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST, ['description' => 'HTML']);

$URL = $session->get('absoluteURL').'/index.php?q=/modules/Deep Learning/experience_manage_add.php';

if (isActionAccessible($guid, $connection2, '/modules/Deep Learning/experience_manage_add.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $experienceGateway = $container->get(ExperienceGateway::class);

    $data = [
        'deepLearningEventID'    => $_POST['deepLearningEventID'] ?? '',
        'name'                   => $_POST['name'] ?? '',
        'status'                 => 'Draft',
        'cost'                   => $_POST['cost'] ?? null,
        'sequenceNumber'         => 0,
        'enrolmentMin'           => 0,
        'enrolmentMax'           => 0,
        'gibbonPersonIDCreated'  => $session->get('gibbonPersonID'),
        'gibbonPersonIDModified' => $session->get('gibbonPersonID'),
    ];

    // Validate the required values are present
    if (empty($data['deepLearningEventID']) || empty($data['name'])) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    // Validate that this record is unique
    if (!$experienceGateway->unique($data, ['name', 'deepLearningEventID'])) {
        $URL .= '&return=error7';
        header("Location: {$URL}");
        exit;
    }

    // Move attached file, if there is one
    if (!empty($_FILES['headerImageFile']['tmp_name'])) {
        $fileUploader = new Gibbon\FileUploader($pdo, $session);
        $fileUploader->getFileExtensions('Graphics/Design');

        $file = $_FILES['headerImageFile'] ?? null;

        // Upload the file, return the /uploads relative path
        $data['headerImage'] = $fileUploader->uploadFromPost($file, $data['name']);

        if (empty($data['headerImage'])) {
            $partialFail = true;
        }

    }
    // Create the record
    $deepLearningExperienceID = $experienceGateway->insert($data);

    $URL .= !$deepLearningExperienceID
        ? "&return=error2"
        : "&return=success0&editID=$deepLearningExperienceID";

    header("Location: {$URL}");
}
