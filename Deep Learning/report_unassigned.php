<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright © 2010, Gibbon Foundation
Gibbon™, Gibbon Education Ltd. (Hong Kong)

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

use Gibbon\Forms\Form;
use Gibbon\Services\Format;
use Gibbon\Tables\Prefab\ReportTable;
use Gibbon\Module\DeepLearning\Domain\StaffGateway;
use Gibbon\Module\DeepLearning\Domain\EventGateway;
use Gibbon\Module\DeepLearning\Domain\ExperienceGateway;

if (isActionAccessible($guid, $connection2, '/modules/Deep Learning/report_unassigned.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs->add(__m('View Unassigned Staff'));

    // Setup data
    $gibbonSchoolYearID = $_REQUEST['gibbonSchoolYearID'] ?? $session->get('gibbonSchoolYearID');
    $viewMode = $_REQUEST['format'] ?? '';

    // Setup gateways
    $eventGateway = $container->get(EventGateway::class);
    $experienceGateway = $container->get(ExperienceGateway::class);
    $staffGateway = $container->get(StaffGateway::class);

    $events = $eventGateway->selectEventsBySchoolYear($session->get('gibbonSchoolYearID'))->fetchKeyPair();
    $activeEvent = $eventGateway->getNextActiveEvent($session->get('gibbonSchoolYearID'));
    
    $params = [
        'deepLearningEventID' => $_REQUEST['deepLearningEventID'] ?? $activeEvent ?? '',
        'search'             => $_REQUEST['search'] ?? ''
    ];


    if (empty($events)) {
        $page->addMessage(__m('There are no active Deep Learning events.'));
        return;
    }
    
    // CRITERIA
    $criteria = $staffGateway->newQueryCriteria(true)
        ->searchBy($staffGateway->getSearchableColumns(), $params['search'])
        ->sortBy(['surname', 'preferredName'])
        ->filterBy('event', $params['deepLearningEventID'])
        ->pageSize(-1)
        ->fromPOST();

    if (empty($viewMode)) {
        // FILTER
        $form = Form::create('filter', $session->get('absoluteURL').'/index.php', 'get');

        $form->setTitle(__('Filter'));
        $form->setClass('noIntBorder fullWidth');

        $form->addHiddenValue('q', '/modules/'.$session->get('module').'/report_unassigned.php');
        $form->addHiddenValue('address', $session->get('address'));

        $row = $form->addRow();
            $row->addLabel('deepLearningEventID', __('Event'));
            $row->addSelect('deepLearningEventID')->fromArray($events)->placeholder()->selected($params['deepLearningEventID']);

        $row = $form->addRow();
            $row->addLabel('search', __('Search For'))->description(__m('Preferred name, surname'));
            $row->addTextField('search')->setValue($criteria->getSearchText())->maxLength(20);

        $row = $form->addRow();
            $row->addFooter();
            $row->addSearchSubmit($session);

        echo $form->getOutput();
    }

    $unassigned = $staffGateway->queryUnassignedStaffByEvent($criteria, $params['deepLearningEventID']);
    
    // DATA TABLE
    $table = ReportTable::createPaginated('report_unassigned', $criteria)->setViewMode($viewMode, $session);

    $table->setTitle(__m('View Unassigned Staff'));

    $table->addColumn('image_240', __('Photo'))
        ->context('primary')
        ->width('8%')
        ->notSortable()
        ->format(Format::using('userPhoto', ['image_240', 'xs']));

    $table->addColumn('fullName', __('Name'))
        ->description(__('Initials'))
        ->context('primary')
        ->width('25%')
        ->sortable(['surname', 'preferredName'])
        ->format(function ($values) {
            return Format::nameLinked($values['gibbonPersonID'], '', $values['preferredName'], $values['surname'], 'Staff', true, true);
        })
        ->formatDetails(function ($values) {
            return Format::small($values['initials']);
        });

    $table->addColumn('type', __('Type'))->width('25%')->translatable();

    $table->addColumn('jobTitle', __('Job Title'))->width('25%');

    echo $table->render($unassigned);

}
