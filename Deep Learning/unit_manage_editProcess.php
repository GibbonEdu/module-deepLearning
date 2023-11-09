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
use Gibbon\Module\DeepLearning\Domain\UnitGateway;
use Gibbon\Data\Validator;
use Gibbon\Module\DeepLearning\Domain\UnitTagGateway;
use Gibbon\Module\DeepLearning\Domain\UnitAuthorGateway;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST, ['description' => 'HTML']);

$deepLearningUnitID = $_POST['deepLearningUnitID'] ?? '';

$URL = $session->get('absoluteURL').'/index.php?q=/modules/Deep Learning/unit_manage_edit.php&deepLearningUnitID='.$deepLearningUnitID;

if (isActionAccessible($guid, $connection2, '/modules/Deep Learning/unit_manage_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {

    // Proceed!
    $unitGateway = $container->get(UnitGateway::class);
    $unitTagGateway = $container->get(UnitTagGateway::class);
    $unitAuthorGateway = $container->get(UnitAuthorGateway::class);

    $data = [
        'name'                   => $_POST['name'] ?? '',
        'status'                 => $_POST['status'] ?? 'Draft',
        'cost'                   => !empty($_POST['cost']) ? $_POST['cost'] : null,
        'location'               => $_POST['location'] ?? '',
        'provider'               => $_POST['provider'] ?? '',
        'majors'                 => $_POST['majors'] ?? '',
        'minors'                 => $_POST['minors'] ?? '',
        'description'            => $_POST['description'] ?? '',
        'teachersNotes'          => $_POST['teachersNotes'] ?? '',
        'timestampModified'      => date('Y-m-d H:i:s'),
        'gibbonPersonIDModified' => $session->get('gibbonPersonID'),
    ];

    // Validate the required values are present
    if (empty($deepLearningUnitID) || empty($data['name']) ) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    // Validate the database relationships exist
    if (!$unitGateway->exists($deepLearningUnitID)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    // Validate that this record is unique
    if (!$unitGateway->unique($data, ['name'], $deepLearningUnitID)) {
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
        $data['headerImage'] = $_POST['headerImage'] ?? '';
    }

    // Update the record
    $updated = $unitGateway->update($deepLearningUnitID, $data);
    $partialFail = !$updated;
    
    // Update the authors
    $authors = $_POST['authors'] ?? '';
    $authorIDs = [];
    foreach ($authors as $person) {
        $authorData = [
            'deepLearningUnitID' => $deepLearningUnitID,
            'gibbonPersonID'     => $person['gibbonPersonID'],
        ];

        if ($person['gibbonPersonID'] == $session->get('gibbonPersonID')) {
            $authorData['timestamp'] = date('Y-m-d H:i:s');
        } else if ($person['gibbonPersonIDOriginal'] != $person['gibbonPersonID']) {
            $authorData['timestamp'] = null;
        }
        
        $deepLearningUnitAuthorID = $person['deepLearningUnitAuthorID'] ?? '';

        if (!empty($deepLearningUnitAuthorID)) {
            $partialFail &= !$unitAuthorGateway->update($deepLearningUnitAuthorID, $authorData);
        } else {
            $deepLearningUnitAuthorID = $unitAuthorGateway->insert($authorData);
            $partialFail &= !$deepLearningUnitAuthorID;
        }

        $authorIDs[] = str_pad($deepLearningUnitAuthorID, 12, '0', STR_PAD_LEFT);
    }

    // Cleanup authors that have been deleted
    $unitAuthorGateway->deleteAuthorsNotInList($deepLearningUnitID, $authorIDs);

    // Update the tags
    $tags = array_unique(array_filter(array_merge(explode(',', $data['majors'] ?? ''), explode(',', $data['minors'] ?? ''))));
    foreach ($tags as $tag) {
        $unitTagGateway->insertAndUpdate(['tag' => $tag], ['tag' => $tag]);
    }

    $URL .= $partialFail
        ? "&return=warning1"
        : "&return=success0";

    header("Location: {$URL}");
}
