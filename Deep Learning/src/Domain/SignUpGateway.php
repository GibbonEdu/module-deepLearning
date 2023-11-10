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

namespace Gibbon\Module\DeepLearning\Domain;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

class SignUpGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'deepLearningSignUp';
    private static $primaryKey = 'deepLearningSignUpID';
    private static $searchableColumns = ['gibbonPerson.preferredName', 'gibbonPerson.surname', 'deepLearningEvent.name', 'deepLearningExperience.name'];

    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function querySignUps(QueryCriteria $criteria, $gibbonSchoolYearID)
    {
        $query = $this
            ->newQuery()
            ->distinct()
            ->from($this->getTableName())
            ->cols([
                'deepLearningEvent.deepLearningEventID',
                'deepLearningEvent.name as eventName',
                'deepLearningEvent.nameShort as eventNameShort',
                'deepLearningSignUp.gibbonPersonID',
                'deepLearningSignUp.timestampModified',
                'gibbonPerson.preferredName',
                'gibbonPerson.surname',
                'gibbonFormGroup.nameShort as formGroup',
                "GROUP_CONCAT(deepLearningExperience.name ORDER BY deepLearningSignUp.choice SEPARATOR ',') as choices",
                
            ])
            ->innerJoin('deepLearningExperience', 'deepLearningExperience.deepLearningExperienceID=deepLearningSignUp.deepLearningExperienceID')
            ->innerJoin('deepLearningEvent', 'deepLearningEvent.deepLearningEventID=deepLearningExperience.deepLearningEventID')
            ->innerJoin('gibbonPerson', 'gibbonPerson.gibbonPersonID=deepLearningSignUp.gibbonPersonID')
            ->leftJoin('gibbonStudentEnrolment', 'gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID AND gibbonStudentEnrolment.gibbonSchoolYearID=deepLearningEvent.gibbonSchoolYearID')
            ->leftJoin('gibbonFormGroup', 'gibbonFormGroup.gibbonFormGroupID=gibbonStudentEnrolment.gibbonFormGroupID')
            ->where('deepLearningEvent.gibbonSchoolYearID=:gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->groupBy(['deepLearningSignUp.gibbonPersonID']);

        return $this->runQuery($query, $criteria);
    }

    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function querySignUpsByPerson(QueryCriteria $criteria, $gibbonSchoolYearID, $gibbonPersonID)
    {
        $query = $this
            ->newQuery()
            ->distinct()
            ->from($this->getTableName())
            ->cols([
                'deepLearningEvent.deepLearningEventID',
                'deepLearningEvent.name as eventName',
                'deepLearningEvent.nameShort as eventNameShort',
                'deepLearningSignUp.gibbonPersonID',
                'deepLearningSignUp.timestampModified',
                'gibbonPerson.preferredName',
                'gibbonPerson.surname',
                "GROUP_CONCAT(deepLearningExperience.name ORDER BY deepLearningSignUp.choice SEPARATOR ',') as choices",
                
            ])
            ->innerJoin('deepLearningExperience', 'deepLearningExperience.deepLearningExperienceID=deepLearningSignUp.deepLearningExperienceID')
            ->innerJoin('deepLearningEvent', 'deepLearningEvent.deepLearningEventID=deepLearningExperience.deepLearningEventID')
            ->innerJoin('gibbonPerson', 'gibbonPerson.gibbonPersonID=deepLearningSignUp.gibbonPersonID')
            ->where('deepLearningEvent.gibbonSchoolYearID=:gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->where('deepLearningSignUp.gibbonPersonID=:gibbonPersonID')
            ->bindValue('gibbonPersonID', $gibbonPersonID)
            ->groupBy(['deepLearningSignUp.gibbonPersonID']);

        return $this->runQuery($query, $criteria);
    }

    public function selectSignUpsByPerson($deepLearningEventID, $gibbonPersonID)
    {
        $data = ['deepLearningEventID' => $deepLearningEventID, 'gibbonPersonID' => $gibbonPersonID];
        $sql = "SELECT deepLearningSignUp.choice as groupBy, deepLearningSignUp.*
                FROM deepLearningSignUp
                JOIN deepLearningExperience ON (deepLearningExperience.deepLearningExperienceID=deepLearningSignUp.deepLearningExperienceID)
                WHERE deepLearningExperience.deepLearningEventID=:deepLearningEventID
                AND deepLearningSignUp.gibbonPersonID=:gibbonPersonID
                ORDER BY deepLearningSignUp.choice";

        return $this->db()->select($sql, $data);
    }

    public function deleteSignUpsNotInList($deepLearningEventID, $gibbonPersonID, $choiceIDs)
    {
        $choiceIDs = is_array($choiceIDs) ? implode(',', $choiceIDs) : $choiceIDs;

        $data = ['deepLearningEventID' => $deepLearningEventID, 'gibbonPersonID' => $gibbonPersonID, 'choiceIDs' => $choiceIDs];
        $sql = "DELETE FROM deepLearningSignUp 
                WHERE deepLearningEventID=:deepLearningEventID 
                AND gibbonPersonID=:gibbonPersonID
                AND NOT FIND_IN_SET(deepLearningSignUpID, :choiceIDs)";

        return $this->db()->delete($sql, $data);
    }

}
