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
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Module\DeepLearning\Domain\EventGateway;
use Gibbon\Module\DeepLearning\Domain\ExperienceGateway;
use Gibbon\Module\DeepLearning\Domain\SignUpGateway;

if (isActionAccessible($guid, $connection2, '/modules/Deep Learning/view_experience_signUp.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $deepLearningExperienceID = $_REQUEST['deepLearningExperienceID'] ?? '';

    $eventGateway = $container->get(EventGateway::class);
    $experienceGateway = $container->get(ExperienceGateway::class);
    $signUpGateway = $container->get(SignUpGateway::class);
    $settingGateway = $container->get(SettingGateway::class);

    if (empty($deepLearningExperienceID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    $experience = $experienceGateway->getExperienceDetailsByID($deepLearningExperienceID);
    $event = $eventGateway->getEventDetailsByID($experience['deepLearningEventID'] ?? '');

    if (empty($experience) || empty($event)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    // Check that sign up is open based on the date
    $signUpIsOpen = false;
    if (!empty($event['accessOpenDate']) && !empty($event['accessCloseDate'])) {
        $accessOpenDate = DateTime::createFromFormat('Y-m-d H:i:s', $event['accessOpenDate'])->format('U');
        $accessCloseDate = DateTime::createFromFormat('Y-m-d H:i:s', $event['accessCloseDate'])->format('U');
        $now = (new DateTime('now'))->format('U');

        $signUpIsOpen = $accessOpenDate <= $now && $accessCloseDate >= $now;
    }

    if (!$signUpIsOpen) {
        $page->addError(__m('Sign up is currently not available for this Deep Learning event.'));
        return;
    }

    // Get experiences
    $experiences = $experienceGateway->selectExperiencesByEvent($experience['deepLearningEventID'])->fetchKeyPair();
    $signUps = $signUpGateway->selectSignUpsByPerson($experience['deepLearningEventID'], $session->get('gibbonPersonID'))->fetchGroupedUnique();

    $signUpText = $settingGateway->getSettingByScope('Deep Learning', 'signUpText');
    $signUpChoices = $settingGateway->getSettingByScope('Deep Learning', 'signUpChoices');

    $choiceList = [1 => __m('First Choice'), 2 => __m('Second Choice'), 3 => __m('Third Choice'), 4 => __m('Fourth Choice'), 5 => __m('Fifth Choice')];
    $choice = [];
    for ($i = 1; $i <= $signUpChoices; $i++) {
        $choice[$i] = $signUps[$i]['deepLearningExperienceID'] ?? '';
        if ($i == 1 && empty($choice[$i])) $choice[$i] = $deepLearningExperienceID;
    }
    
    // FORM
    $form = Form::create('event', $session->get('absoluteURL').'/modules/'.$session->get('module').'/view_experience_signUpProcess.php');
    $form->setTitle(__m("Deep Learning Sign Up"));
    $form->setDescription($signUpText);

    $form->addHiddenValue('address', $session->get('address'));
    $form->addHiddenValue('gibbonPersonID', $session->get('gibbonPersonID'));
    $form->addHiddenValue('deepLearningEventID', $experience['deepLearningEventID']);
    $form->addHiddenValue('deepLearningExperienceID', $deepLearningExperienceID);

    for ($i = 1; $i <= $signUpChoices; $i++) {
        $row = $form->addRow();
        $row->addLabel("choices[{$i}]", $choiceList[$i] ?? $i);
        $row->addSelect("choices[{$i}]")
            ->fromArray($experiences)
            ->setID("choices{$i}")
            ->addClass('signUpChoice')
            ->required()
            ->placeholder()
            ->selected($choice[$i] ?? '');
    }

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
?>

<script>
$(document).on('change input', '.signUpChoice', function () {
    var currentChoice = this;

    $('.signUpChoice').not(this).each(function() {
        if ($(currentChoice).val() == $(this).val()) {
            $(this).val($(this).find("option:first-child").val());
        }
    });
});
</script>
