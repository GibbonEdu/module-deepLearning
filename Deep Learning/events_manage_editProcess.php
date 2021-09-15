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
use Gibbon\Module\DeepLearning\Domain\EventGateway;
use Gibbon\Module\DeepLearning\Domain\DateGateway;

require_once '../../gibbon.php';

$deepLearningEventID = $_POST['deepLearningEventID'] ?? '';

$URL = $session->get('absoluteURL').'/index.php?q=/modules/Deep Learning/events_manage_edit.php&deepLearningEventID='.$deepLearningEventID;

if (isActionAccessible($guid, $connection2, '/modules/Deep Learning/events_manage_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {

    // Proceed!
    $eventGateway = $container->get(EventGateway::class);
    $dateGateway = $container->get(DateGateway::class);
    $partialFail = false;

    $data = [
        'name'          => $_POST['name'] ?? '',
        'description'   => $_POST['description'] ?? '',
        'backgroundImage' => $_POST['backgroundImage'] ?? '',
        'active'        => $_POST['active'] ?? '',
    ];

    // Validate the required values are present
    if (empty($deepLearningEventID) || empty($data['name']) || empty($data['description']) || empty($data['active']) ) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    // Validate the database relationships exist
    if (!$eventGateway->exists($deepLearningEventID)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    // Validate that this record is unique
    if (!$eventGateway->unique($data, ['name'], $deepLearningEventID)) {
        $URL .= '&return=error7';
        header("Location: {$URL}");
        exit;
    }

    //Move attached file, if there is one
    $attachment = null;
    if (!empty($_FILES['file']['tmp_name'])) {
        $fileUploader = new Gibbon\FileUploader($pdo, $session);
        $fileUploader->getFileExtensions('Graphics/Design');

        $file = $_FILES['file'] ?? null;

        // Upload the file, return the /uploads relative path
        $data['backgroundImage'] = $fileUploader->uploadFromPost($file, $data, $data['backgroundImage']);

        if (empty($data['backgroundImage'])) {
            $partialFail = true;
        }

    } else {
      $data['backgroundImage']=$_POST['backgroundImage'];
    }

    // Update the record
    $updated = $eventGateway->update($deepLearningEventID, $data);

    // Update blocks
    $dates = $_POST['dates'] ?? [];
    $blockIDs = [];

    foreach ($dates as $i) {
        $data = [
            'deepLearningEventID' => $deepLearningEventID,
            'name'                  => $i['name'] ?? '',
            'date'                   => Format::dateConvert($i['date']) ?? '',
        ];

        $deepLearingDateID = $i["deepLearningDateID"] ?? '';

        if (!empty($deepLearingDateID)) {
            $partialFail &= !$dateGateway->update($deepLearingDateID, $data);
        } else {
            $deepLearingDateID = $dateGateway->insert($data);
            $partialFail &= !$deepLearingDateID;
        }

        $blockIDs[] = str_pad($deepLearingDateID, 10, '0', STR_PAD_LEFT);
    }

    // Remove orphaned blocks
    if (!empty($blockIDs)) {
        $data = ['deepLearningEventID' => $deepLearningEventID, 'blockIDs' => implode(',', $blockIDs)];
        $sql = "DELETE FROM deepLearningDate WHERE deepLearningEventID=:deepLearningEventID AND NOT FIND_IN_SET(deepLearningDateID, :blockIDs)";
        $pdo->statement($sql, $data);
    }

    if (!$updated) {
      $URL .= "&return=error2";
    } else if ($partialFail) {
      $URL .= "&return=warning1";
    } else {
      $URL .= "&return=success0";
    }

    header("Location: {$URL}");
}
