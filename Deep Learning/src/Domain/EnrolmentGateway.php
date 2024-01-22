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

class EnrolmentGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'deepLearningEnrolment';
    private static $primaryKey = 'deepLearningEnrolmentID';
    private static $searchableColumns = ['gibbonPerson.preferredName', 'gibbonPerson.surname', 'deepLearningEvent.name', 'deepLearningExperience.name'];

    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryEnrolment(QueryCriteria $criteria, $gibbonSchoolYearID)
    {
        $query = $this
            ->newQuery()
            ->distinct()
            ->from($this->getTableName())
            ->cols([
                'deepLearningEvent.deepLearningEventID',
                'deepLearningEvent.name as eventName',
                'deepLearningEvent.nameShort as eventNameShort',
                'deepLearningExperience.deepLearningExperienceID',
                'deepLearningExperience.name',
                'deepLearningEnrolment.deepLearningEnrolmentID',
                'deepLearningEnrolment.gibbonPersonID',
                'deepLearningEnrolment.status',
                'deepLearningEnrolment.timestampCreated',
                'gibbonPerson.preferredName',
                'gibbonPerson.surname',
                'gibbonPerson.image_240',
                'gibbonFormGroup.nameShort as formGroup',
                'deepLearningChoice.choice',
            ])
            ->innerJoin('deepLearningExperience', 'deepLearningExperience.deepLearningExperienceID=deepLearningEnrolment.deepLearningExperienceID')
            ->innerJoin('deepLearningEvent', 'deepLearningEvent.deepLearningEventID=deepLearningExperience.deepLearningEventID')
            ->innerJoin('gibbonPerson', 'gibbonPerson.gibbonPersonID=deepLearningEnrolment.gibbonPersonID')
            ->leftJoin('gibbonStudentEnrolment', 'gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID AND gibbonStudentEnrolment.gibbonSchoolYearID=deepLearningEvent.gibbonSchoolYearID')
            ->leftJoin('gibbonFormGroup', 'gibbonFormGroup.gibbonFormGroupID=gibbonStudentEnrolment.gibbonFormGroupID')
            ->leftJoin('deepLearningChoice', 'deepLearningEnrolment.deepLearningChoiceID=deepLearningChoice.deepLearningChoiceID')
            ->where('deepLearningEvent.gibbonSchoolYearID=:gibbonSchoolYearID')
            ->where('deepLearningEvent.active="Y"')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->where("gibbonPerson.status = 'Full'")
            ->where('(gibbonPerson.dateStart IS NULL OR gibbonPerson.dateStart <= :today)')
            ->where('(gibbonPerson.dateEnd IS NULL OR gibbonPerson.dateEnd >= :today)')
            ->bindValue('today', date('Y-m-d'))
            ->groupBy(['deepLearningEnrolment.gibbonPersonID']);

        return $this->runQuery($query, $criteria);
    }

    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryEnrolmentByExperience(QueryCriteria $criteria, $deepLearningExperienceID, $includeStaff = true)
    {
        $query = $this
            ->newQuery()
            ->from('deepLearningStaff')
            ->cols([
                'deepLearningExperience.name',
                'gibbonPerson.preferredName',
                'gibbonPerson.surname',
                'gibbonPerson.image_240',
                'gibbonPerson.email',
                '"" as formGroup',
                '"" as deepLearningEnrolmentID',
                'deepLearningStaff.gibbonPersonID',
                '"" as status',
                '"Staff" as roleCategory',
                'deepLearningStaff.role',
                'deepLearningStaff.notes',
                '"" as choice',
                "(FIND_IN_SET(deepLearningStaff.role, 'Trip Leader,Teacher,Support')) as roleOrder",
            ])
            ->innerJoin('deepLearningExperience', 'deepLearningExperience.deepLearningExperienceID=deepLearningStaff.deepLearningExperienceID')
            ->innerJoin('deepLearningEvent', 'deepLearningEvent.deepLearningEventID=deepLearningExperience.deepLearningEventID')
            ->innerJoin('gibbonPerson', 'gibbonPerson.gibbonPersonID=deepLearningStaff.gibbonPersonID')
            ->where('deepLearningExperience.active="Y"')            
            ->where('deepLearningExperience.deepLearningExperienceID=:deepLearningExperienceID')
            ->bindValue('deepLearningExperienceID', $deepLearningExperienceID)
            ->where("gibbonPerson.status = 'Full'")
            ->where('(gibbonPerson.dateStart IS NULL OR gibbonPerson.dateStart <= :today)')
            ->where('(gibbonPerson.dateEnd IS NULL OR gibbonPerson.dateEnd >= :today)')
            ->bindValue('today', date('Y-m-d'))
            ->groupBy(['deepLearningStaff.gibbonPersonID']);

        $this->unionAllWithCriteria($query, $criteria)
            ->from($this->getTableName())
            ->cols([
                'deepLearningExperience.name',
                'gibbonPerson.preferredName',
                'gibbonPerson.surname',
                'gibbonPerson.image_240',
                'gibbonPerson.email',
                'gibbonFormGroup.nameShort as formGroup',
                'deepLearningEnrolment.deepLearningEnrolmentID',
                'deepLearningEnrolment.gibbonPersonID',
                'deepLearningEnrolment.status',
                '"Student" as roleCategory',
                '"Student" as role',
                'deepLearningEnrolment.notes',
                'deepLearningChoice.choice',
                '10 as roleOrder'
            ])
            ->innerJoin('deepLearningExperience', 'deepLearningExperience.deepLearningExperienceID=deepLearningEnrolment.deepLearningExperienceID')
            ->innerJoin('deepLearningEvent', 'deepLearningEvent.deepLearningEventID=deepLearningExperience.deepLearningEventID')
            ->innerJoin('gibbonPerson', 'gibbonPerson.gibbonPersonID=deepLearningEnrolment.gibbonPersonID')
            ->leftJoin('gibbonStudentEnrolment', 'gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID AND gibbonStudentEnrolment.gibbonSchoolYearID=deepLearningEvent.gibbonSchoolYearID')
            ->leftJoin('gibbonFormGroup', 'gibbonFormGroup.gibbonFormGroupID=gibbonStudentEnrolment.gibbonFormGroupID')
            ->leftJoin('deepLearningChoice', 'deepLearningEnrolment.deepLearningChoiceID=deepLearningChoice.deepLearningChoiceID')
            ->where('deepLearningExperience.active="Y"')            
            ->where('deepLearningExperience.deepLearningExperienceID=:deepLearningExperienceID')
            ->bindValue('deepLearningExperienceID', $deepLearningExperienceID)
            ->where("gibbonPerson.status = 'Full'")
            ->where('(gibbonPerson.dateStart IS NULL OR gibbonPerson.dateStart <= :today)')
            ->where('(gibbonPerson.dateEnd IS NULL OR gibbonPerson.dateEnd >= :today)')
            ->bindValue('today', date('Y-m-d'))
            ->groupBy(['deepLearningEnrolment.gibbonPersonID']);

        return $this->runQuery($query, $criteria);
    }

    public function selectEnrolmentsByEvent($deepLearningEventID)
    {
        $data = ['deepLearningEventID' => $deepLearningEventID, 'today' => date('Y-m-d')];
        $sql = "SELECT deepLearningEnrolment.gibbonPersonID as groupBy,
                    deepLearningEnrolment.deepLearningExperienceID,
                    deepLearningEnrolment.gibbonPersonID,
                    deepLearningEnrolment.status,
                    deepLearningChoice.timestampCreated,
                    gibbonPerson.surname,
                    gibbonPerson.preferredName,
                    gibbonFormGroup.name as formGroup,
                    gibbonYearGroup.name as yearGroup,
                    gibbonYearGroup.sequenceNumber as yearGroupSequence,
                    MIN(CASE WHEN deepLearningChoice.choice=1 THEN deepLearningChoice.deepLearningExperienceID END) as choice1,
                    MIN(CASE WHEN deepLearningChoice.choice=2 THEN deepLearningChoice.deepLearningExperienceID END) as choice2,
                    MIN(CASE WHEN deepLearningChoice.choice=3 THEN deepLearningChoice.deepLearningExperienceID END) as choice3,
                    MIN(CASE WHEN deepLearningChoice.choice=4 THEN deepLearningChoice.deepLearningExperienceID END) as choice4,
                    MIN(CASE WHEN deepLearningChoice.choice=5 THEN deepLearningChoice.deepLearningExperienceID END) as choice5
                FROM deepLearningEnrolment
                JOIN deepLearningEvent ON (deepLearningEvent.deepLearningEventID=deepLearningEnrolment.deepLearningEventID)
                JOIN gibbonPerson ON (gibbonPerson.gibbonPersonID=deepLearningEnrolment.gibbonPersonID)
                LEFT JOIN deepLearningChoice ON (deepLearningEnrolment.deepLearningEventID=deepLearningChoice.deepLearningEventID AND deepLearningChoice.gibbonPersonID=deepLearningEnrolment.gibbonPersonID)
                LEFT JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID AND gibbonStudentEnrolment.gibbonSchoolYearID=deepLearningEvent.gibbonSchoolYearID)
                LEFT JOIN gibbonFormGroup ON (gibbonFormGroup.gibbonFormGroupID=gibbonStudentEnrolment.gibbonFormGroupID)
                LEFT JOIN gibbonYearGroup ON (gibbonYearGroup.gibbonYearGroupID=gibbonStudentEnrolment.gibbonYearGroupID)
                WHERE deepLearningEvent.deepLearningEventID=:deepLearningEventID
                AND gibbonPerson.status = 'Full'
                AND (gibbonPerson.dateStart IS NULL OR gibbonPerson.dateStart <= :today)
                AND (gibbonPerson.dateEnd IS NULL OR gibbonPerson.dateEnd >= :today)
                GROUP BY deepLearningEnrolment.gibbonPersonID
                ORDER BY gibbonYearGroup.sequenceNumber, gibbonFormGroup.name, gibbonPerson.surname, gibbonPerson.preferredName";

        return $this->db()->select($sql, $data);
    }

    public function queryUnenrolledStudentsByEvent($criteria, $deepLearningEventID)
    {
        $query = $this
            ->newQuery()
            ->from('deepLearningEvent')
            ->cols([
                'gibbonStudentEnrolment.gibbonPersonID as groupBy',
                '0 as deepLearningExperienceID',
                'deepLearningEvent.deepLearningEventID',
                'deepLearningEvent.name as eventName',
                'deepLearningEvent.nameShort as eventNameShort',
                'gibbonStudentEnrolment.gibbonPersonID',
                'gibbonPerson.surname',
                'gibbonPerson.preferredName',
                'gibbonPerson.email',
                'gibbonFormGroup.name as formGroup',
                'gibbonYearGroup.name as yearGroup',
                'gibbonYearGroup.sequenceNumber as yearGroupSequence',
                'MIN(CASE WHEN deepLearningChoice.choice=1 THEN deepLearningChoice.deepLearningExperienceID END) as choice1',
                'MIN(CASE WHEN deepLearningChoice.choice=2 THEN deepLearningChoice.deepLearningExperienceID END) as choice2',
                'MIN(CASE WHEN deepLearningChoice.choice=3 THEN deepLearningChoice.deepLearningExperienceID END) as choice3',
                'MIN(CASE WHEN deepLearningChoice.choice=4 THEN deepLearningChoice.deepLearningExperienceID END) as choice4',
                'MIN(CASE WHEN deepLearningChoice.choice=5 THEN deepLearningChoice.deepLearningExperienceID END) as choice5',
                "GROUP_CONCAT(deepLearningExperience.name ORDER BY deepLearningChoice.choice SEPARATOR ',') as choices",
            ])
            ->innerJoin('gibbonStudentEnrolment', 'gibbonStudentEnrolment.gibbonSchoolYearID=deepLearningEvent.gibbonSchoolYearID')
            ->innerJoin('gibbonPerson', 'gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID')
            ->innerJoin('gibbonYearGroup', 'gibbonYearGroup.gibbonYearGroupID=gibbonStudentEnrolment.gibbonYearGroupID')
            ->innerJoin('gibbonFormGroup', 'gibbonFormGroup.gibbonFormGroupID=gibbonStudentEnrolment.gibbonFormGroupID')
            ->leftJoin('deepLearningEnrolment', 'deepLearningEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID AND deepLearningEnrolment.deepLearningEventID=deepLearningEvent.deepLearningEventID')
            ->leftJoin('deepLearningChoice', 'deepLearningChoice.deepLearningEventID=deepLearningEvent.deepLearningEventID AND deepLearningChoice.gibbonPersonID=gibbonPerson.gibbonPersonID')
            ->leftJoin('deepLearningExperience', 'deepLearningExperience.deepLearningExperienceID=deepLearningChoice.deepLearningExperienceID')
            ->where('deepLearningEvent.deepLearningEventID=:deepLearningEventID')
            ->bindValue('deepLearningEventID', $deepLearningEventID)
            ->where('FIND_IN_SET(gibbonYearGroup.gibbonYearGroupID, deepLearningEvent.gibbonYearGroupIDList)')
            ->where("gibbonPerson.status = 'Full'")
            ->where('(gibbonPerson.dateStart IS NULL OR gibbonPerson.dateStart <= :today)')
            ->where('(gibbonPerson.dateEnd IS NULL OR gibbonPerson.dateEnd >= :today)')
            ->bindValue('today', date('Y-m-d'))
            ->where('deepLearningEnrolment.deepLearningEnrolmentID IS NULL')
            ->groupBy(['gibbonPerson.gibbonPersonID']);

        return $this->runQuery($query, $criteria);
    }

    public function getExperienceDetailsByEnrolment($deepLearningEventID, $gibbonPersonID)
    {
        $data = ['deepLearningEventID' => $deepLearningEventID, 'gibbonPersonID' => $gibbonPersonID];
        $sql = "SELECT deepLearningEnrolment.*, deepLearningExperience.* 
                FROM deepLearningEnrolment
                JOIN deepLearningExperience ON (deepLearningExperience.deepLearningExperienceID=deepLearningEnrolment.deepLearningExperienceID) 
                WHERE deepLearningEnrolment.deepLearningEventID=:deepLearningEventID
                AND deepLearningEnrolment.gibbonPersonID=:gibbonPersonID
                AND deepLearningEnrolment.status='Confirmed'
                AND deepLearningExperience.active='Y'";

        return $this->db()->selectOne($sql, $data);
    }


}
