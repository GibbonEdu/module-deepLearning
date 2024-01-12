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
use Gibbon\Module\DeepLearning\Domain\ExperienceGateway;

if (isActionAccessible($guid, $connection2, '/modules/Deep Learning/report_groups.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs->add(__m('Deep Learning Groups'));

    // Setup data
    $gibbonSchoolYearID = $_REQUEST['gibbonSchoolYearID'] ?? $session->get('gibbonSchoolYearID');
    $viewMode = $_REQUEST['format'] ?? '';

    // Setup gateways
    $experienceGateway = $container->get(ExperienceGateway::class);

    if (empty($gibbonSchoolYearID)) {
        $page->addMessage(__m('There are no active Deep Learning events.'));
        return;
    }
    
    if (empty($viewMode)) {
         // FILTER
         $form = Form::create('filter', $session->get('absoluteURL').'/index.php', 'get');

         $form->setTitle(__('Filter'));
         $form->setClass('noIntBorder fullWidth');
 
         $form->addHiddenValue('q', '/modules/'.$session->get('module').'/report_groups.php');
         $form->addHiddenValue('address', $session->get('address'));
 
         $row = $form->addRow();
             $row->addFooter();
             $row->addSearchSubmit($session);
 
         echo $form->getOutput();
    }

    // CRITERIA
    $criteria = $experienceGateway->newQueryCriteria()
        ->sortBy(['name'])
        ->fromPOST();

    $experiences = $experienceGateway->queryExperiences($criteria, $session->get('gibbonSchoolYearID'));
    
    // DATA TABLE
    $table = ReportTable::createPaginated('report_groups', $criteria)->setViewMode($viewMode, $session);

    $table->setTitle(__m('Deep Learning Groups'));

    echo $table->render($experiences);
}
