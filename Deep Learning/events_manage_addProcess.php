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

$URL = $gibbon->session->get('absoluteURL').'/index.php?q=/modules/Deep Learning/events_manage_add.php';

if (isActionAccessible($guid, $connection2, '/modules/Deep Learning/events_manage_add.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $eventGateway = $container->get(EventGateway::class);
    $dateGateway = $container->get(DateGateway::class);

    $data = [
        'name'          => $_POST['name'] ?? '',
        'description'   => $_POST['description'] ?? '',
        'backgroundImage' => $_POST['backgroundImage'] ?? '',
        'active'        => $_POST['active'] ?? '',
    ];

    // Validate the required values are present
    if (empty($data['name'])) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    // Validate that this record is unique
    if (!$eventGateway->unique($data, ['name'])) {
        $URL .= '&return=error7';
        header("Location: {$URL}");
        exit;
    }

    //Move attached file, if there is one
    if (!empty($_FILES['backgroundImage']['tmp_name'])) {
        $fileUploader = new Gibbon\FileUploader($pdo, $gibbon->session);
        $fileUploader->getFileExtensions('Graphics/Design');

        $file = $_FILES['backgroundImage'] ?? null;

        // Upload the file, return the /uploads relative path
        $data['backgroundImage'] = $fileUploader->uploadFromPost($file, $data, $data['name']);

        if (empty($data['backgroundImage'])) {
            $partialFail = true;
        }
    }

    // Create the record
    $deepLearningEventID = $eventGateway->insert($data);


    $data = [];
    $data['deepLearningEventID'] = $deepLearningEventID;
    $dates = $_POST['dates'] ?? [];
    foreach ($dates as $date) {
      $data['name'] = $date['name'];
      $data['date'] = Format::dateConvert($date['date']);
      $dateGateway->insert($data);
    }

    $URL .= !$deepLearningEventID
        ? "&return=error2"
        : "&return=success0&editID=$deepLearningEventID";

    header("Location: {$URL}");
}
