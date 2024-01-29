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
use Gibbon\Tables\DataTable;
use Gibbon\Module\DeepLearning\Domain\EventGateway;
use Gibbon\Http\Url;
use Gibbon\Module\DeepLearning\Domain\EnrolmentGateway;

if (isActionAccessible($guid, $connection2, '/modules/Deep Learning/viewMyDL.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs
        ->add(__m('My Deep Learning'));

    $page->return->addReturns([
        'success1' => __m('You have been successfully signed up for this Deep Learning event. You can view and manage your sign up below.'),
        'error4' => __m('Sign up is currently not available for this Deep Learning event.'),
        'error5' => __m('There was an error verifying your Deep Learning choices. Please try again.'),
    ]);

    // Query events
    $eventGateway = $container->get(EventGateway::class);
    $enrolmentGateway = $container->get(EnrolmentGateway::class);
    
    $criteria = $eventGateway->newQueryCriteria()
        ->sortBy(['startDate'])
        ->fromPOST();

    $events = $eventGateway->queryEventsByPerson($criteria, $session->get('gibbonSchoolYearID'), $session->get('gibbonPersonID'));

    $table = DataTable::create('events');

    $table->addColumn('name', __('Event'))
        ->sortable(['deepLearningEvent.name'])
        ->context('primary')
        ->format(function ($values) {
            $url = Url::fromModuleRoute('Deep Learning', 'view_event.php')->withQueryParams(['deepLearningEventID' => $values['deepLearningEventID'], 'sidebar' => 'false']);
            return $values['active'] == 'Y' && $values['viewable'] == 'Y'
                ? Format::link($url, $values['name'])
                : $values['name'];
        });

    $table->addColumn('dates', __('Dates'))
        ->sortable(['eventDates'])
        ->context('primary')
        ->format(function ($values) {
            $dates = array_map(function ($date) {
                return Format::dateReadable($date);
            }, explode(',', $values['eventDates'] ?? ''));

            return implode('<br/>', $dates);
        });

    $table->addColumn('choices', __m('Experience'))
        ->context('primary')
        ->width('30%')
        ->format(function ($values) use ($enrolmentGateway) {
            $now = (new DateTime('now'))->format('U');
            $accessEnrolmentDate = DateTime::createFromFormat('Y-m-d H:i:s', $values['accessEnrolmentDate']);
            if ($accessEnrolmentDate && $accessEnrolmentDate->format('U') < $now) {
                $enrolment = $enrolmentGateway->getExperienceDetailsByEnrolment($values['deepLearningEventID'], $values['gibbonPersonID']);

                if (!empty($enrolment)) {
                    $url = Url::fromModuleRoute('Deep Learning', 'view_experience.php')->withQueryParams(['deepLearningEventID' => $values['deepLearningEventID'], 'deepLearningExperienceID' => $enrolment['deepLearningExperienceID'], 'sidebar' => 'false']);
                    return Format::small(__m('Enrolled in')).':<br>'.Format::link($url, $enrolment['name']);
                } else {
                    return '';
                }
            }

            if (empty($values['choices'])) {
                return '';
            }
            
            $choices = explode(',', $values['choices']);
            return Format::small(__m('Signed up for')).':<br/>'.Format::list($choices, 'ol', 'ml-2 my-0 text-xs');
        });

    $table->addColumn('status', __('Status'))
        ->format(function ($values) {
            if ($values['viewable'] != 'Y') {
                return;
            }

            $now = (new DateTime('now'))->format('U');

            $endDate = DateTime::createFromFormat('Y-m-d', $values['endDate'] ?? '');
            if ($endDate && $endDate->format('U') < $now) {
                return Format::tag(__m('Past'), 'dull');
            }

            $accessCloseDate = DateTime::createFromFormat('Y-m-d H:i:s', $values['accessCloseDate']);
            if ($accessCloseDate && $accessCloseDate->format('U') < $now) {
                return Format::tag(__m('Sign up closed on').'<br/>'.$accessCloseDate->format('M j \\a\\t g:ia'), 'dull');
            }

            $accessOpenDate = DateTime::createFromFormat('Y-m-d H:i:s', $values['accessOpenDate']);
            if ($accessOpenDate && ($accessOpenDate->format('U') <= $now && $accessCloseDate->format('U') >= $now)) {
                return Format::tag(__m('Sign up is open'), 'success');
            }

            if ($accessOpenDate && $accessOpenDate->format('U') > $now) {
                return Format::tag(__m('Sign up opens on').'<br/>'.$accessOpenDate->format('M j \\a\\t g:ia'), 'message');
            }

            return Format::tag(__m('Upcoming Event'), 'dull');
        });

    // ACTIONS
    $table->addActionColumn()
        ->addParam('deepLearningEventID')
        ->format(function ($values, $actions) {

            // Check that sign up is open based on the date
            $signUpIsOpen = false;
            $viewable = $values['viewable'] == 'Y';

            if (!empty($values['accessOpenDate']) && !empty($values['accessCloseDate'])) {
                $accessOpenDate = DateTime::createFromFormat('Y-m-d H:i:s', $values['accessOpenDate'])->format('U');
                $accessCloseDate = DateTime::createFromFormat('Y-m-d H:i:s', $values['accessCloseDate'])->format('U');
                $now = (new DateTime('now'))->format('U');

                $signUpIsOpen = $accessOpenDate <= $now && $accessCloseDate >= $now;
            }

            if ($viewable && $signUpIsOpen) {
                $actions->addAction('add', __('Sign Up'))
                        ->setURL('/modules/Deep Learning/view_experience_signUp.php')
                        ->modalWindow(750, 440);
            }
        });

    echo $table->render($events);
}
