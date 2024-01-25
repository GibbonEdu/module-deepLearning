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
use Gibbon\Module\DeepLearning\Domain\UnitPhotoGateway;

if (isActionAccessible($guid, $connection2, '/modules/Deep Learning/view_experience.php') == false) {
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

    $page->return->addReturns([
        'error4' => __m('Sign up is currently not available for this Deep Learning event.'),
        'error5' => __m('There was an error verifying your Deep Learning choices. Please try again.'),
    ]);

    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if (empty($highestAction)) {
        $page->addError(__('You do not have access to this action.'));
        return;
    }

    // Check records exist and are available
    $eventGateway = $container->get(EventGateway::class);
    $experienceGateway = $container->get(ExperienceGateway::class);
    $unitPhotoGateway = $container->get(UnitPhotoGateway::class);

    if (empty($deepLearningExperienceID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    $experience = $experienceGateway->getExperienceDetailsByID($deepLearningExperienceID);
    $event = $eventGateway->getEventDetailsByID($experience['deepLearningEventID'] ?? '');

    if (empty($experience) || empty($event)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    if ($experience['active'] != 'Y' || $event['active'] != 'Y') {
        $page->addError(__('You do not have access to this action.'));
        return;
    }

    if ($event['viewable'] != 'Y') {
        $page->addMessage(__m('This event is not viewable at this time. Please return to the Events page to explore a different event.'));
        return;
    }

    // Get photos
    $experience['photos'] = $unitPhotoGateway->selectPhotosByExperience($deepLearningExperienceID)->fetchAll();

    // Check sign-up access
    $canSignUp = isActionAccessible($guid, $connection2, '/modules/Deep Learning/view.php', 'Deep Learning Events_signUp');
    $signUpIsOpen = false;

    if (!empty($event['accessOpenDate']) && !empty($event['accessCloseDate'])) {
        $accessOpenDate = DateTime::createFromFormat('Y-m-d H:i:s', $event['accessOpenDate'])->format('U');
        $accessCloseDate = DateTime::createFromFormat('Y-m-d H:i:s', $event['accessCloseDate'])->format('U');
        $now = (new DateTime('now'))->format('U');

        $signUpIsOpen = $accessOpenDate <= $now && $accessCloseDate >= $now;
    }

    $signUpEvent = $eventGateway->getEventSignUpAccess($experience['deepLearningEventID'], $session->get('gibbonPersonID'));
    $signUpExperience = $experienceGateway->getExperienceSignUpAccess($deepLearningExperienceID, $session->get('gibbonPersonID'));

    $page->writeFromTemplate('experience.twig.html', [
        'event'      => $event,
        'experience' => $experience,

        'nextExperience' => $experienceGateway->getNextExperienceByID($deepLearningExperienceID),
        'prevExperience' => $experienceGateway->getPreviousExperienceByID($deepLearningExperienceID),

        'canSignUp'  => $canSignUp,
        'signUpIsOpen' => $signUpIsOpen,
        'signUpAccess' => $signUpEvent && $signUpExperience,
    ]);
}
