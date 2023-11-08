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
use Gibbon\Module\DeepLearning\Domain\ExperienceGateway;

if (isActionAccessible($guid, $connection2, '/modules/Deep Learning/experience_manage.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs
        ->add(__m('Manage Experiences'));

    // Query majors
    $experienceGateway = $container->get(ExperienceGateway::class);

    $criteria = $experienceGateway->newQueryCriteria()
        ->sortBy(['name'])
        ->fromPOST();

    $experiences = $experienceGateway->queryExperiences($criteria);

    // Render table
    $table = DataTable::createPaginated('majors', $criteria);

    $table->addHeaderAction('add', __('Add'))
        ->setURL('/modules/Deep Learning/experience_manage_add.php')
        ->displayLabel();

    $table->modifyRows(function($values, $row) {
        if ($values['status'] == 'Draft') $row->addClass('dull');
        return $row;
    });

    $table->addColumn('eventNameShort', __('Event'))
        ->width('10%');

    $table->addColumn('name', __('Name'))
        ->context('primary')
        ->format(function ($values) {
            $output = $values['name'];
            if ($values['status'] == 'Draft') {
                $output .= Format::tag(__('Draft'), 'message ml-2');
            }
            return $output;
        });

    $table->addColumn('students', __('Students'))
        ->width('10%');

    // ACTIONS
    $table->addActionColumn()
        ->addParam('deepLearningExperienceID')
        ->format(function ($experience, $actions) {
            $actions->addAction('edit', __('Edit'))
                    ->setURL('/modules/Deep Learning/experience_manage_edit.php');

            $actions->addAction('delete', __('Delete'))
                    ->setURL('/modules/Deep Learning/experience_manage_delete.php');
        });

    echo $table->render($experiences);
}
