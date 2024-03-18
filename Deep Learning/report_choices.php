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
use Gibbon\Module\DeepLearning\Domain\ChoiceGateway;
use Gibbon\Forms\Form;
use Gibbon\Module\DeepLearning\Domain\EventGateway;

if (isActionAccessible($guid, $connection2, '/modules/Deep Learning/report_choices.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs
        ->add(__m('View Student Choices'));

    $choiceGateway = $container->get(ChoiceGateway::class);
    $eventGateway = $container->get(EventGateway::class);

    $events = $eventGateway->selectEventsBySchoolYear($session->get('gibbonSchoolYearID'))->fetchKeyPair();
    $activeEvent = $eventGateway->getNextActiveEvent($session->get('gibbonSchoolYearID'));

    $params = [
        'search' => $_REQUEST['search'] ?? '',
        'deepLearningEventID' => $_REQUEST['deepLearningEventID'] ?? $activeEvent ?? '',
    ];

    // CRITERIA
    $criteria = $choiceGateway->newQueryCriteria(true)
        ->searchBy($choiceGateway->getSearchableColumns(), $params['search'])
        ->filterBy('event', $params['deepLearningEventID'])
        ->sortBy(['yearGroup', 'formGroup', 'surname', 'preferredName'])
        ->fromPOST();

    // SEARCH
    $form = Form::create('filters', $session->get('absoluteURL').'/index.php', 'get');
    
    $form->setTitle(__('Filter'));
    $form->setClass('noIntBorder fullWidth');

    $form->addHiddenValue('q', '/modules/Deep Learning/report_choices.php');

    $row = $form->addRow();
        $row->addLabel('deepLearningEventID', __('Event'));
        $row->addSelect('deepLearningEventID')->fromArray($events)->placeholder()->selected($params['deepLearningEventID']);

    $row = $form->addRow();
        $row->addLabel('search', __('Search For'))->description(__m('Preferred name, surname, experience name, event name'));
        $row->addTextField('search')->setValue($criteria->getSearchText())->maxLength(20);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSearchSubmit($session, 'Clear Filters', ['view', 'sidebar']);

    echo $form->getOutput();

    $choices = $choiceGateway->queryChoices($criteria, $session->get('gibbonSchoolYearID'));

    // TABLE
    $table = DataTable::createPaginated('choices', $criteria);

    $table->addColumn('image_240', __('Photo'))
        ->context('primary')
        ->width('8%')
        ->notSortable()
        ->format(Format::using('userPhoto', ['image_240', 'xs']));

    $table->addColumn('student', __('Person'))
        ->sortable(['gibbonPerson.surname', 'gibbonPerson.preferredName'])
        ->width('25%')
        ->format(function ($values) {
            return Format::nameLinked($values['gibbonPersonID'], '', $values['preferredName'], $values['surname'], 'Student', true, true);
        });

    $table->addColumn('formGroup', __('FormGroup'))->context('secondary');

    $table->addColumn('eventNameShort', __('Event'))
        ->width('8%');

    $table->addColumn('choiceList', __('Choices'))
        ->context('primary')
        ->width('30%')
        ->format(function ($values) {
            if (empty($values['choiceList'])) return '';
            $output = '';
            $choices = explode(',', $values['choiceList']);
            foreach ($choices as $choice) {
                list($choiceNumber,$choiceName) = array_pad(explode(':', $choice),2,'');
                $output .= $choiceNumber.'. '.$choiceName.'<br/>';
            }
            return $output;
        })
        ->formatDetails(function ($values) {
            return !empty($values['enrolledExperience'])
                ? Format::small(__m('Enrolled in {name}', ['name' => $values['enrolledExperience']]))
                : '';
        });

    $table->addColumn('timestampModified', __('When'))
        ->format(Format::using('dateReadable', 'timestampModified'))
        ->width('15%');

    echo $table->render($choices);
}
