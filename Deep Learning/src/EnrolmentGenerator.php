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

namespace Gibbon\Module\DeepLearning;

use Gibbon\Module\DeepLearning\Domain\ChoiceGateway;
use Gibbon\Module\DeepLearning\Domain\EnrolmentGateway;
use Gibbon\Module\DeepLearning\Domain\ExperienceGateway;

/**
 * Facilitates turning student choices into a set of potential enrolment groups 
 * for each of the selected experiences.
 */
class EnrolmentGenerator 
{
    protected $experienceGateway;
    protected $enrolmentGateway;
    protected $choiceGateway;

    protected $experiences;
    protected $choices;
    protected $groups;

    public function __construct(ExperienceGateway $experienceGateway, EnrolmentGateway $enrolmentGateway, ChoiceGateway $choiceGateway)
    {
        $this->experienceGateway = $experienceGateway;
        $this->enrolmentGateway = $enrolmentGateway;
        $this->choiceGateway = $choiceGateway;
    }

    public function getExperiences()
    {
        return $this->experiences;
    }

    public function getGroups()
    {
        return $this->groups;
    }

    public function loadExperiences(string $deepLearningEventID, array $experienceList)
    {
        // Filter details to only those checked for this generation process
        $this->experiences = $this->experienceGateway->selectExperienceDetailsByEvent($deepLearningEventID)->fetchGroupedUnique();
        $this->experiences = array_intersect_key($this->experiences, $experienceList);

        // Update min and max values for the selected experiences
        foreach ($this->experiences as $deepLearningExperienceID => $experience) {
            $experience['enrolmentMin'] = $experienceList[$deepLearningExperienceID]['enrolmentMin'] ?? $experience['enrolmentMin'];
            $experience['enrolmentMax'] = $experienceList[$deepLearningExperienceID]['enrolmentMax'] ?? $experience['enrolmentMax'];

            $this->experienceGateway->update($deepLearningExperienceID, [
                'enrolmentMin' => $experience['enrolmentMin'],
                'enrolmentMax' => $experience['enrolmentMax'],
            ]);

            $this->experiences[$deepLearningExperienceID] = $experience;
        }

        return $this;
    }

    public function loadChoices(string $deepLearningEventID)
    {
        $this->choices = $this->choiceGateway->selectChoicesByEvent($deepLearningEventID)->fetchGroupedUnique();

        foreach ($this->choices as $gibbonPersonID => $person) {
            $person['choice1'] = str_pad($person['choice1'], 12, '0', STR_PAD_LEFT);
            $person['choice2'] = str_pad($person['choice2'], 12, '0', STR_PAD_LEFT);
            $person['choice3'] = str_pad($person['choice3'], 12, '0', STR_PAD_LEFT);

            $person['choice1Name'] = $this->experiences[$person['choice1']]['name'] ?? '';
            $person['choice2Name'] = $this->experiences[$person['choice2']]['name'] ?? '';
            $person['choice3Name'] = $this->experiences[$person['choice3']]['name'] ?? '';

            $this->choices[$gibbonPersonID] = $person;
        }

        return $this;
    }

    public function generateGroups()
    {

        // foreach ($this->experiences as $deepLearningExperienceID => $experience) {


        foreach ($this->choices as $gibbonPersonID => $person) {

            $enrolmentGroup = 0;

            for ($i = 1; $i <= 3; $i++) {
                if (empty($person["choice{$i}"])) continue;

                $choiceExperience = $this->experiences[$person["choice{$i}"]] ?? [];
                $groupCount = count($this->groups[$person["choice{$i}"]] ?? []);

                if ($groupCount < $choiceExperience['enrolmentMax']) {
                    $enrolmentGroup = $person["choice{$i}"];
                    break;
                }
            }


            $this->groups[$enrolmentGroup][$gibbonPersonID] = $person;
        }

        return $this;
    }

    public function createEnrolments($deepLearningEventID, $enrolmentList, $gibbonPersonIDCreated = null) : array
    {
        $results = ['total' => 0, 'choice0' => 0, 'choice1' => 0, 'choice2' => 0, 'choice3' => 0, 'choice4' => 0, 'choice5' => 0, 'unassigned' => 0];

        foreach ($enrolmentList as $gibbonPersonID => $deepLearningExperienceID) {
            if (empty($deepLearningExperienceID)) {
                $results['unassigned']++;
                continue;
            }
            $choice = $this->choiceGateway->getChoiceByExperienceAndPerson($deepLearningExperienceID, $gibbonPersonID);
            $choiceNumber = intval($choice['choice'] ?? 0);

            $data = [
                'deepLearningExperienceID' => $deepLearningExperienceID,
                'deepLearningEventID'      => $deepLearningEventID,
                'deepLearningChoiceID'     => $choice['deepLearningChoiceID'],
                'gibbonPersonID'           => $gibbonPersonID,
                'status'                   => 'Confirmed',
                'notes'                    => '',
                'timestampCreated'         => date('Y-m-d H:i:s'),
                'gibbonPersonIDCreated'    => $gibbonPersonIDCreated,
            ];

            $inserted = $this->enrolmentGateway->insert($data);
            if ($inserted) {
                $results['total']++;
                $results["choice".$choiceNumber]++;
            }
        }

        return $results;
    }
    
}
