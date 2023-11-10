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

use Gibbon\Module\DeepLearning\Domain\SignUpGateway;

require_once '../../gibbon.php';

$deepLearningEventID = $_POST['deepLearningEventID'] ?? '';
$gibbonPersonID = $_POST['gibbonPersonID'] ?? '';

$URL = $session->get('absoluteURL').'/index.php?q=/modules/Deep Learning/signUp_manage.php';

if (isActionAccessible($guid, $connection2, '/modules/Deep Learning/signUp_manage_delete.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} elseif (empty($deepLearningEventID) || empty($gibbonPersonID)) {
    $URL .= '&return=error1';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $signUpGateway = $container->get(SignUpGateway::class);

    $signUps = $container->get(SignUpGateway::class)->selectSignUpsByPerson($deepLearningEventID, $gibbonPersonID);
    if (empty($signUps)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    $deleted = $signUpGateway->deleteWhere(['deepLearningEventID' => $deepLearningEventID, 'gibbonPersonID' => $gibbonPersonID]);

    $URL .= !$deleted
        ? '&return=error2'
        : '&return=success0';

    header("Location: {$URL}");
}
