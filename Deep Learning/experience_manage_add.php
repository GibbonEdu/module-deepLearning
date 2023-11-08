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

use Gibbon\Http\Url;
use Gibbon\Forms\Form;
use Gibbon\Module\DeepLearning\Domain\EventGateway;
use Gibbon\Module\DeepLearning\Domain\UnitGateway;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Forms\DatabaseFormFactory;

if (isActionAccessible($guid, $connection2, '/modules/Deep Learning/experience_manage_add.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $params = [
        'gibbonSchoolYearID' => $_REQUEST['gibbonSchoolYearID'] ?? $session->get('gibbonSchoolYearID'),
        'search'             => $_REQUEST['search'] ?? ''
    ];

    $page->breadcrumbs
        ->add(__m('Manage Experiences'), 'experience_manage.php', $params)
        ->add(__m('Add Experience'));

    if (isset($_GET['editID'])) {
        $page->return->setEditLink($session->get('absoluteURL').'/index.php?q=/modules/Deep Learning/experience_manage_edit.php&deepLearningExperienceID='.$_GET['editID']);
    }

    if (!empty($params['search'])) {
        $page->navigator->addSearchResultsAction(Url::fromModuleRoute('Deep Learning', 'experience_manage.php')->withQueryParams($params));
    }

    $settingGateway = $container->get(SettingGateway::class);
    $enrolmentMin = $settingGateway->getSettingByScope('Deep Learning', 'enrolmentMin');
    $enrolmentMax = $settingGateway->getSettingByScope('Deep Learning', 'enrolmentMax');

    $events = $container->get(EventGateway::class)->selectEventsBySchoolYear();
    $units = $container->get(UnitGateway::class)->selectPublishedUnits();

    // FORM
    $form = Form::create('experience', $session->get('absoluteURL').'/modules/'.$session->get('module').'/experience_manage_addProcess.php');
    $form->setFactory(DatabaseFormFactory::create($pdo));

    $form->addHiddenValue('address', $session->get('address'));

    // DETAILS
    $form->addRow()->addHeading(__('Basic Details'));

    $row = $form->addRow();
        $row->addLabel('deepLearningEventID', __('Event'))->description(__m('Each experience is part of a Deep Learning event.'));
        $row->addSelect('deepLearningEventID')->fromResults($events, 'groupBy')->required();

    $row = $form->addRow();
        $row->addLabel('deepLearningUnitID', __('Unit'))->description(__m('Must be unique within this Deep Learning event.'));
        $row->addSelect('deepLearningUnitID')->fromResults($units)->required();

    // STAFF
    $form->addRow()->addHeading(__('Staff'));

    // Custom Block Template
    $addBlockButton = $form->getFactory()->createButton(__m('Add Staff'))->addClass('addBlock');

    $roles = ['Trip Leader' => __m('Trip Leader'), 'Teacher' => __('Teacher'), 'Assistant' => __('Assistant')];
    $blockTemplate = $form->getFactory()->createTable()->setClass('blank');
    $row = $blockTemplate->addRow()->addClass('w-full flex justify-between items-center mt-1 ml-2');
        $row->addSelectStaff('gibbonPersonID')->photo(true, 'small')->setClass('flex-1 mr-1')->required()->placeholder();
        $row->addSelect('role')->fromArray($roles)->setClass('w-48 mr-1')->required()->placeholder();
        $row->addCheckbox('canEdit')->setLabelClass('w-32')->alignLeft()->setValue('Y')->checked('Y')->description(__m('Can Edit?'));

    // Custom Blocks
    $row = $form->addRow();
    $customBlocks = $row->addCustomBlocks('staff', $session)
        ->fromTemplate($blockTemplate)
        ->settings(array('inputNameStrategy' => 'object', 'addOnEvent' => 'click'))
        ->placeholder(__m('Add a Trip Leader...'))
        ->addToolInput($addBlockButton);
        
    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
