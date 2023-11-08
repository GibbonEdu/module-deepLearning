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

use Gibbon\Module\DeepLearning\Domain\EventGateway;
use Gibbon\Module\DeepLearning\Domain\ExperienceGateway;

if (isActionAccessible($guid, $connection2, '/modules/Deep Learning/view.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $deepLearningEventID = $_REQUEST['deepLearningEventID'] ?? '';
    $deepLearningExperienceID = $_REQUEST['deepLearningExperienceID'] ?? '';

    $page->breadcrumbs
        ->add(__m('Deep Learning Events'), 'view.php')
        ->add(__m('Explore'), 'view_event.php', ['deepLearningEventID' => $deepLearningEventID, 'sidebar' => 'false'])
        ->add(__m('View Experience'));

    $eventGateway = $container->get(EventGateway::class);
    $experienceGateway = $container->get(ExperienceGateway::class);

    if (empty($deepLearningExperienceID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    $experience = $experienceGateway->getExperienceDetailsByID($deepLearningExperienceID);
    $event = $eventGateway->getEventDetailsByID($experience['deepLearningEventID'] ?? '');

    if (empty($experience)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    if ($experience['status'] != 'Published') {
        $page->addError(__('You do not have access to this action.'));
        return;
    }
    $page->writeFromTemplate('experience.twig.html', [
        'event'      => $event,
        'experience' => $experience,
    ]);
}
