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
use Gibbon\Module\DeepLearning\Domain\SignUpGateway;
use Gibbon\Forms\Form;

if (isActionAccessible($guid, $connection2, '/modules/Deep Learning/signUp_manage.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs
        ->add(__m('Manage Sign Up'));

    $params = [
        'search' => $_REQUEST['search'] ?? ''
    ];

    // CRITERIA
    $signUpGateway = $container->get(SignUpGateway::class);

    $criteria = $signUpGateway->newQueryCriteria(true)
        ->searchBy($signUpGateway->getSearchableColumns(), $params['search'])
        ->sortBy(['surname', 'preferredName'])
        ->fromPOST();

    // SEARCH
    $form = Form::create('filters', $session->get('absoluteURL').'/index.php', 'get');
    $form->setClass('noIntBorder fullWidth');

    $form->addHiddenValue('q', '/modules/Deep Learning/signUp_manage.php');

    $row = $form->addRow();
        $row->addLabel('search', __('Search For'))->description(__m('Preferred name, surname, experience name, event name'));
        $row->addTextField('search')->setValue($criteria->getSearchText())->maxLength(20);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSearchSubmit($session, 'Clear Filters', ['view', 'sidebar']);

    echo $form->getOutput();

    $signUps = $signUpGateway->querySignUps($criteria, $session->get('gibbonSchoolYearID'));

    // TABLE
    $table = DataTable::createPaginated('signUps', $criteria);

    $table->addHeaderAction('add', __('Add'))
        ->setURL('/modules/Deep Learning/signUp_manage_addEdit.php')
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

    $table->addColumn('choices', __('Choices'))
        ->context('primary')
        ->format(function ($values) {
            $choices = explode(',', $values['choices']);
            return Format::list($choices, 'ol', 'ml-2 my-0 text-xs');
        });

    $table->addColumn('timestampModified', __('When'))
        ->format(Format::using('dateTimeReadable', 'timestampModified'))
        ->width('20%');


    // ACTIONS
    $table->addActionColumn()
        ->addParam('deepLearningEventID')
        ->addParam('gibbonPersonID')
        ->format(function ($values, $actions) {
            $actions->addAction('edit', __('Edit'))
                    ->setURL('/modules/Deep Learning/signUp_manage_addEdit.php')
                    ->addParam('mode', 'edit');

            $actions->addAction('delete', __('Delete'))
                    ->setURL('/modules/Deep Learning/signUp_manage_delete.php');
        });

    echo $table->render($signUps);
}
