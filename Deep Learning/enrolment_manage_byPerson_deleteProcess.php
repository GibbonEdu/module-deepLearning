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

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$params = [
    'origin'                  => $_POST['origin'] ?? '',
    'deepLearningEventID'     => $_POST['deepLearningEventID'] ?? '',
    'deepLearningEnrolmentID' => $_POST['deepLearningEnrolmentID'] ?? '',
];

$deepLearningEnrolmentID = $_POST['deepLearningEnrolmentID'] ?? '';
$origin = $_POST['origin'] ?? '';

$URL = $params['origin'] == 'byEvent'
    ? $session->get('absoluteURL').'/index.php?q=/modules/Deep Learning/enrolment_manage_byEvent.php&'.http_build_query($params)
    : $session->get('absoluteURL').'/index.php?q=/modules/Deep Learning/enrolment_manage_byPerson.php&'.http_build_query($params);

if (isActionAccessible($guid, $connection2, '/modules/Deep Learning/enrolment_manage_byPerson_delete.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} elseif (empty($params['deepLearningEnrolmentID'])) {
    $URL .= '&return=error1';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $enrolmentGateway = $container->get(EnrolmentGateway::class);

    $values = $container->get(EnrolmentGateway::class)->getByID($params['deepLearningEnrolmentID']);
    if (empty($values)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    $deleted = $enrolmentGateway->delete($params['deepLearningEnrolmentID']);

    $URL .= !$deleted
        ? '&return=error2'
        : '&return=success0';

    header("Location: {$URL}");
}
