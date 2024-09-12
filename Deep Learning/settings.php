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
use Gibbon\Domain\System\SettingGateway;

if (isActionAccessible($guid, $connection2, '/modules/Deep Learning/settings.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs->add(__('Settings'));

    $settingGateway = $container->get(SettingGateway::class);

    // FORM
    $form = Form::create('settings', $session->get('absoluteURL').'/modules/Deep Learning/settingsProcess.php');
    $form->setTitle(__('Settings'));

    $form->addHiddenValue('address', $session->get('address'));

    $setting = $settingGateway->getSettingByScope('Deep Learning', 'welcomeText', true);
    $column = $form->addRow()->addColumn();
        $column->addLabel($setting['name'], __m($setting['nameDisplay']))->description(__m($setting['description']));
        $column->addEditor($setting['name'], $guid)->setValue($setting['value'])->setRows(6);

    $setting = $settingGateway->getSettingByScope('Deep Learning', 'signUpText', true);
    $column = $form->addRow()->addColumn();
        $column->addLabel($setting['name'], __m($setting['nameDisplay']))->description(__m($setting['description']));
        $column->addEditor($setting['name'], $guid)->setValue($setting['value'])->setRows(6);

    $setting = $settingGateway->getSettingByScope('Deep Learning', 'signUpChoices', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __m($setting['nameDisplay']))->description(__m($setting['description']));
        $row->addSelect($setting['name'])->fromArray(range(1, 5))->required()->selected($setting['value']);

    $setting = $settingGateway->getSettingByScope('Deep Learning', 'enrolmentMin', true);
        $row = $form->addRow();
            $row->addLabel($setting['name'], __m($setting['nameDisplay']))->description(__m($setting['description']));
            $row->addNumber($setting['name'])->onlyInteger(true)->required()->setValue($setting['value']);

    $setting = $settingGateway->getSettingByScope('Deep Learning', 'enrolmentMax', true);
        $row = $form->addRow();
            $row->addLabel($setting['name'], __m($setting['nameDisplay']))->description(__m($setting['description']));
            $row->addNumber($setting['name'])->onlyInteger(true)->required()->setValue($setting['value']);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
