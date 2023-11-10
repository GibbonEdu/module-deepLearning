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
use Gibbon\Module\DeepLearning\Domain\ExperienceGateway;

if (isActionAccessible($guid, $connection2, '/modules/Deep Learning/experience_manage.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs
        ->add(__m('Manage Experiences'));

    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if (empty($highestAction)) {
        $page->addError(__('You do not have access to this action.'));
        return;
    }

    $params = [
        'gibbonSchoolYearID' => $_REQUEST['gibbonSchoolYearID'] ?? $session->get('gibbonSchoolYearID'),
        'search'             => $_REQUEST['search'] ?? ''
    ];

    $page->navigator->addSchoolYearNavigation($params['gibbonSchoolYearID']);

    // Setup criteria
    $experienceGateway = $container->get(ExperienceGateway::class);
    $criteria = $experienceGateway->newQueryCriteria(true)
        ->searchBy($experienceGateway->getSearchableColumns(), $params['search'])
        ->sortBy(['name'])
        ->fromPOST();

    // Search
    if ($highestAction == 'Manage Experiences_all') {
        $form = Form::create('filters', $session->get('absoluteURL').'/index.php', 'get');
        $form->setClass('noIntBorder fullWidth');

        $form->addHiddenValue('q', '/modules/Deep Learning/experience_manage.php');

        $row = $form->addRow();
            $row->addLabel('search', __('Search For'))->description(__m('Experience name, unit name'));
            $row->addTextField('search')->setValue($criteria->getSearchText())->maxLength(20);

        $row = $form->addRow();
            $row->addFooter();
            $row->addSearchSubmit($session, 'Clear Filters', ['view', 'sidebar']);

        echo $form->getOutput();
    }

    // Query experiences
    $gibbonPersonID = $highestAction == 'Manage Experiences_my' ? $session->get('gibbonPersonID') : null;
    $experiences = $experienceGateway->queryExperiences($criteria, $params['gibbonSchoolYearID'], $gibbonPersonID);

    // Render table
    $table = DataTable::createPaginated('experiences', $criteria);

    if ($highestAction == 'Manage Experiences_all') {
        $table->addHeaderAction('add', __('Add'))
            ->setURL('/modules/Deep Learning/experience_manage_add.php')
            ->addParams($params)
            ->displayLabel();
    } else {
        $table->setDescription(__m('This section shows all Deep Learning experiences that you are a member of.'));
    }

    $table->modifyRows(function($values, $row) {
        if ($values['active'] == 'N') $row->addClass('error');
        return $row;
    });

    $table->addColumn('eventNameShort', __('Event'))
        ->width('8%');

    $table->addColumn('name', __('Name'))
        ->context('primary');

    $table->addColumn('tripLeaders', __m('Trip Leader(s)'));

    $table->addColumn('staffCount', __('Staff'))
        ->width('10%');

    $table->addColumn('studentCount', __('Students'))
        ->width('10%');

    $table->addColumn('active', __('Active'))
        ->format(Format::using('yesNo', 'active'))
        ->width('10%');

    // ACTIONS
    $table->addActionColumn()
        ->addParam('search', $criteria->getSearchText(true))
        ->addParam('gibbonSchoolYearID', $params['gibbonSchoolYearID'])
        ->addParam('deepLearningExperienceID')
        ->format(function ($experience, $actions) use ($highestAction) {
            if ($highestAction == 'Manage Experiences_all' || (!empty($experience['canEdit']) && $experience['canEdit'] == 'Y')) {
                $actions->addAction('edit', __('Edit'))
                        ->setURL('/modules/Deep Learning/experience_manage_edit.php');
            }

            if ($highestAction == 'Manage Experiences_all') {
                $actions->addAction('delete', __('Delete'))
                        ->setURL('/modules/Deep Learning/experience_manage_delete.php')
                        ->modalWindow(650, 400);
            }
        });

    echo $table->render($experiences ?? []);
}
