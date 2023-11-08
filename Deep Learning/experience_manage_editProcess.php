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

$deepLearningExperienceID = $_POST['deepLearningExperienceID'] ?? '';

$URL = $session->get('absoluteURL').'/index.php?q=/modules/Deep Learning/experience_manage_edit.php&deepLearningExperienceID='.$deepLearningExperienceID;

if (isActionAccessible($guid, $connection2, '/modules/Deep Learning/experience_manage_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {

    // Proceed!
    $experienceGateway = $container->get(ExperienceGateway::class);

    $data = [
        'name'                   => $_POST['name'] ?? '',
        'status'                 => $_POST['status'] ?? 'Draft',
        'cost'                   => $_POST['cost'] ?? null,
        'sequenceNumber'         => 0,
        'enrolmentMin'           => $_POST['enrolmentMin'] ?? null,
        'enrolmentMax'           => $_POST['enrolmentMax'] ?? null,
        'gibbonPersonIDModified' => $session->get('gibbonPersonID'),
    ];

    // Validate the required values are present
    if (empty($deepLearningExperienceID) || empty($data['name']) ) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    // Validate the database relationships exist
    if (!$experienceGateway->exists($deepLearningExperienceID)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    // Validate that this record is unique
    if (!$experienceGateway->unique($data, ['name', 'deepLearningEventID'], $deepLearningExperienceID)) {
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

    } else {
      $data['headerImage'] = $_POST['headerImage'];
    }

    // Update the record
    $updated = $experienceGateway->update($deepLearningExperienceID, $data);

    $URL .= !$updated
        ? "&return=error2"
        : "&return=success0";

    header("Location: {$URL}");
}
