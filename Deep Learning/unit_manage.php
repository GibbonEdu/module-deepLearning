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
use Gibbon\Module\DeepLearning\Domain\UnitGateway;

if (isActionAccessible($guid, $connection2, '/modules/Deep Learning/unit_manage.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs
        ->add(__m('Manage Units'));

    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if (empty($highestAction)) {
        $page->addError(__('You do not have access to this action.'));
        return;
    }

    $search = $_REQUEST['search'] ?? '';

    // Setup criteria
    $unitGateway = $container->get(UnitGateway::class);
    $criteria = $unitGateway->newQueryCriteria()
        ->searchBy($unitGateway->getSearchableColumns(), $search)
        ->sortBy(['name'])
        ->fromPOST();

    // Search
    $form = Form::create('filters', $session->get('absoluteURL').'/index.php', 'get');
    $form->setClass('noIntBorder fullWidth');

    $form->addHiddenValue('q', '/modules/Deep Learning/unit_manage.php');

    $row = $form->addRow();
        $row->addLabel('search', __('Search For'))->description(__m('Unit name, majors, minors, authors'));
        $row->addTextField('search')->setValue($criteria->getSearchText())->maxLength(20);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSearchSubmit($gibbon->session, 'Clear Filters', ['view', 'sidebar']);

    echo $form->getOutput();

    // Query units
    $gibbonPersonID = $highestAction == 'Manage Units_my' ? $session->get('gibbonPersonID') : null;
    $units = $unitGateway->queryUnits($criteria, $gibbonPersonID);

    // Render table
    $table = DataTable::createPaginated('units', $criteria);
    $table->setTitle(__('Units'));

    $table->addHeaderAction('add', __('Add'))
        ->setURL('/modules/Deep Learning/unit_manage_add.php')
        ->displayLabel();

    $table->modifyRows(function($values, $row) {
        if ($values['status'] == 'Draft') $row->addClass('dull');
        return $row;
    });

    $table->addMetaData('filterOptions', [
        'status:draft' => __('Status').': '.__('Draft'),
        'status:published'  => __('Status').': '.__('Published'),
    ]);

    $table->addColumn('name', __m('Unit Name'))
        ->context('primary')
        ->format(function ($values) {
            $output = $values['name'];
            if ($values['status'] == 'Draft') {
                $output .= Format::tag(__('Draft'), 'message ml-2');
            }
            return $output;
        });

    $table->addColumn('authors', __m('Author(s)'))
        ->context('primary')
        ->width('20%')
        ->sortable(['authors']);

    $table->addColumn('majors', __m('Majors'))
        ->description(__m('Minors'))
        ->width('25%')
        ->sortable(['authors'])
        ->format(function ($values) {
            return implode(', ', explode(',', $values['majors'] ?? ''));
        })
        ->formatDetails(function ($values) {
            return implode(', ', explode(',', $values['minors'] ?? ''));
        });

    // ACTIONS
    $table->addActionColumn()
        ->addParam('search', $criteria->getSearchText(true))
        ->addParam('deepLearningUnitID')
        ->format(function ($unit, $actions) use ($highestAction) {
            $actions->addAction('edit', __('Edit'))
                    ->setURL('/modules/Deep Learning/unit_manage_edit.php');

            if ($highestAction == 'Manage Units_all') {
                $actions->addAction('delete', __('Delete'))
                        ->setURL('/modules/Deep Learning/unit_manage_delete.php');
            }
        });

    echo $table->render($units);
}
