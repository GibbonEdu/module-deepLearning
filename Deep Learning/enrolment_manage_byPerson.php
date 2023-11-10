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

use Gibbon\Forms\Form;
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Module\DeepLearning\Domain\EnrolmentGateway;

if (isActionAccessible($guid, $connection2, '/modules/Deep Learning/enrolment_manage_byPerson.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs
        ->add(__m('Manage Enrolment'));

    $params = [
        'search' => $_REQUEST['search'] ?? ''
    ];

    // CRITERIA
    $enrolmentGateway = $container->get(EnrolmentGateway::class);

    $criteria = $enrolmentGateway->newQueryCriteria(true)
        ->searchBy($enrolmentGateway->getSearchableColumns(), $params['search'])
        ->sortBy(['surname', 'preferredName'])
        ->fromPOST();

    // SEARCH
    $form = Form::create('filters', $session->get('absoluteURL').'/index.php', 'get');
    $form->setClass('noIntBorder fullWidth');

    $form->addHiddenValue('q', '/modules/Deep Learning/enrolment_manage_byPerson.php');

    $row = $form->addRow();
        $row->addLabel('search', __('Search For'))->description(__m('Preferred name, surname, experience name, event name'));
        $row->addTextField('search')->setValue($criteria->getSearchText())->maxLength(20);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSearchSubmit($session, 'Clear Filters', ['view', 'sidebar']);

    echo $form->getOutput();

    $enrolment = $enrolmentGateway->queryEnrolment($criteria, $session->get('gibbonSchoolYearID'));

    // TABLE
    $table = DataTable::createPaginated('enrolment', $criteria);

    $table->addHeaderAction('add', __('Add'))
        ->setURL('/modules/Deep Learning/enrolment_manage_byPerson_addEdit.php')
        ->addParam('mode', 'add')
        ->displayLabel();

    $table->addColumn('eventNameShort', __('Event'))
        ->width('8%');

    $table->addColumn('student', __('Person'))
        ->sortable(['gibbonPerson.surname', 'gibbonPerson.preferredName'])
        ->width('25%')
        ->format(function ($values) {
            return Format::nameLinked($values['gibbonPersonID'], '', $values['preferredName'], $values['surname'], 'Student', true, true);
        });

    $table->addColumn('formGroup', __('FormGroup'))->context('secondary');

    $table->addColumn('name', __('Experience'))
        ->context('primary');

    $table->addColumn('timestampCreated', __('When'))
        ->format(Format::using('dateTimeReadable', 'timestampCreated'))
        ->width('20%');


    // ACTIONS
    $table->addActionColumn()
        ->addParam('deepLearningEnrolmentID')
        ->format(function ($values, $actions) {
            $actions->addAction('edit', __('Edit'))
                    ->setURL('/modules/Deep Learning/enrolment_manage_byPerson_addEdit.php')
                    ->addParam('mode', 'edit');

            $actions->addAction('delete', __('Delete'))
                    ->setURL('/modules/Deep Learning/enrolment_manage_byPerson_delete.php');
        });

    echo $table->render($enrolment);
}
