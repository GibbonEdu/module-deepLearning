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

use Gibbon\Tables\DataTable;
use Gibbon\Services\Format;
use Gibbon\Module\DeepLearning\Domain\EventGateway;
use Gibbon\Http\Url;

if (isActionAccessible($guid, $connection2, '/modules/Deep Learning/events_manage.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs
        ->add(__m('Manage Events'));

    $gibbonSchoolYearID = $_REQUEST['gibbonSchoolYearID'] ?? $session->get('gibbonSchoolYearID');
    $page->navigator->addSchoolYearNavigation($gibbonSchoolYearID);

    // Query events
    $eventGateway = $container->get(EventGateway::class);

    $criteria = $eventGateway->newQueryCriteria()
        ->sortBy(['startDate'])
        ->fromPOST();

    $events = $eventGateway->queryEvents($criteria, $gibbonSchoolYearID);

    // Render table
    $table = DataTable::createPaginated('events', $criteria);

    $table->addHeaderAction('add', __('Add'))
        ->setURL('/modules/Deep Learning/events_manage_add.php')
        ->displayLabel();

    $table->modifyRows(function($values, $row) {
        if ($values['active'] == 'N') $row->addClass('error');
        if ($values['viewable'] == 'N') $row->addClass('dull');
        return $row;
    });

    $table->addExpandableColumn('more')->format(function($values) {
        return implode('<br/>', explode(',', $values['experienceNames'] ?? ''));
    });

    $table->addColumn('name', __('Name'))
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

    $table->addColumn('signUp', __('Sign-up'))
        ->sortable(['accessOpenDate'])
        ->format(Format::using('dateRangeReadable', ['accessOpenDate', 'accessCloseDate']));
        
    $table->addColumn('experienceCount', __m('Experiences'))
        ->sortable(['experienceCount'])
        ->width('12%')
        ->format(function ($values) {
            $url = Url::fromModuleRoute('Deep Learning', 'experience_manage.php')->withQueryParams(['search' => $values['nameShort']]);

            return intval($values['experienceCount']) > 0 
                ? Format::link($url, $values['experienceCount'])
                : $values['experienceCount'];
        });

    $table->addColumn('active', __('Active'))
        ->format(Format::using('yesNo', 'active'))
        ->width('10%');

    $table->addColumn('viewable', __('Viewable'))
        ->format(Format::using('yesNo', 'viewable'))
        ->width('10%');

    // ACTIONS
    $table->addActionColumn()
        ->addParam('deepLearningEventID')
        ->format(function ($major, $actions) {
            $actions->addAction('edit', __('Edit'))
                    ->setURL('/modules/Deep Learning/events_manage_edit.php');

            $actions->addAction('delete', __('Delete'))
                    ->setURL('/modules/Deep Learning/events_manage_delete.php')
                    ->modalWindow(650, 400);
        });

    echo $table->render($events);
}
