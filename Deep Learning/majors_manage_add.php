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
use Gibbon\Forms\DatabaseFormFactory;

if (isActionAccessible($guid, $connection2, '/modules/Deep Learning/majors_manage_add.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs
        ->add(__m('Manage Majors'), 'majors_manage.php')
        ->add(__m('Add Major'));

    $editLink = '';
    if (isset($_GET['editID'])) {
        $editLink = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Deep Learning/majors_manage_edit.php&deepLearningMajorID='.$_GET['editID'];
    }
    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], $editLink, null);
    }

    $form = Form::create('major', $gibbon->session->get('absoluteURL').'/modules/'.$gibbon->session->get('module').'/majors_manage_addProcess.php');
    $form->setFactory(DatabaseFormFactory::create($pdo));

    $form->addHiddenValue('address', $gibbon->session->get('address'));

    $row = $form->addRow();
        $row->addLabel('name', __('Name'))->description(__('Must be unique.'));
        $row->addTextField('name')->required()->maxLength(20);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
