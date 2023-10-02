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

if (isActionAccessible($guid, $connection2, '/modules/Deep Learning/events_manage.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs
        ->add(__m('Manage Events'));

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    // Query events
    $eventGateway = $container->get(EventGateway::class);

    $criteria = $eventGateway->newQueryCriteria()
        ->sortBy(['name'])
        ->fromPOST();

    $events = $eventGateway->queryEvents($criteria);

    // Render table
    $table = DataTable::createPaginated('events', $criteria);

    $table->addHeaderAction('add', __('Add'))
        ->setURL('/modules/Deep Learning/events_manage_add.php')
        ->displayLabel();

    $table->addExpandableColumn('more')->format(function($events) {
        $output = '';
        $dates = explode(',', $events['eventDates']);
        foreach ($dates as $index=>$date) {
            $dates[$index]= Format::date($date);
        }
        $output .= implode('<br/>', $dates).'<br/>';

        $names = explode(',', $events['experienceNames']);
        foreach ($names as $index=>$name) {
            $names[$index]=$name;
        }
        $output .= implode('<br/>', $names);
        return $output;
    });

    $table->addColumn('name', __('Name'))
        ->sortable(['deepLearningEvent.name']);

    $table->addColumn('description', __('Description'))
        ->sortable(['deepLearningEvent.description']);

    $table->addColumn('active', __('Active'))->format(Format::using('yesNo', 'active'));

    // ACTIONS
    $table->addActionColumn()
        ->addParam('deepLearningEventID')
        ->format(function ($major, $actions) {
            $actions->addAction('edit', __('Edit'))
                    ->setURL('/modules/Deep Learning/events_manage_edit.php');

            $actions->addAction('delete', __('Delete'))
                    ->setURL('/modules/Deep Learning/events_manage_delete.php');
        });

    echo $table->render($events);
}
