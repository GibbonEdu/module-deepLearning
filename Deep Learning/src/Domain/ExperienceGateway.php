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

class ExperienceGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'deepLearningExperience';
    private static $primaryKey = 'deepLearningExperienceID';
    private static $searchableColumns = ['deepLearningExperience.name', 'deepLearningEvent.name', 'deepLearningEvent.nameShort', 'deepLearningUnit.name'];

    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryExperiences(QueryCriteria $criteria, $gibbonSchoolYearID, $gibbonPersonID = null)
    {
        $query = $this
            ->newQuery()
            ->distinct()
            ->cols([
                'deepLearningEvent.deepLearningEventID',
                'deepLearningEvent.name as eventName',
                'deepLearningEvent.nameShort as eventNameShort',
                'deepLearningEvent.accessOpenDate',
                'deepLearningEvent.accessCloseDate',
                'deepLearningEvent.viewable',
                'deepLearningExperience.deepLearningExperienceID',
                'deepLearningExperience.name',
                'deepLearningExperience.active',
                "GROUP_CONCAT(DISTINCT CONCAT(gibbonPerson.preferredName, ' ', gibbonPerson.surname) ORDER BY gibbonPerson.surname SEPARATOR '<br/>') as tripLeaders",
                "COUNT(DISTINCT deepLearningStaff.deepLearningStaffID) as staffCount",
                "(SELECT COUNT(*) FROM deepLearningEnrolment WHERE deepLearningEnrolment.deepLearningExperienceID=deepLearningExperience.deepLearningExperienceID AND status='Confirmed') as studentCount",
            ])
            ->from($this->getTableName())
            ->innerJoin('deepLearningEvent', 'deepLearningEvent.deepLearningEventID=deepLearningExperience.deepLearningEventID')
            ->leftJoin('deepLearningUnit', 'deepLearningUnit.deepLearningUnitID=deepLearningExperience.deepLearningUnitID')
            ->leftJoin('deepLearningStaff', 'deepLearningStaff.deepLearningExperienceID=deepLearningExperience.deepLearningExperienceID')
            ->leftJoin('deepLearningStaff as tripLeader', 'tripLeader.deepLearningExperienceID=deepLearningExperience.deepLearningExperienceID AND tripLeader.role="Trip Leader"')
            ->leftJoin('gibbonPerson', 'gibbonPerson.gibbonPersonID=tripLeader.gibbonPersonID')
            ->where('deepLearningEvent.gibbonSchoolYearID=:gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->groupBy(['deepLearningExperience.deepLearningExperienceID']);

        if (!empty($gibbonPersonID)) {
            $query->cols(['staff.canEdit', 'staff.role'])
                ->leftJoin('deepLearningStaff as staff', 'staff.deepLearningExperienceID=deepLearningExperience.deepLearningExperienceID')
                ->where('staff.gibbonPersonID=:gibbonPersonID')
                ->bindValue('gibbonPersonID', $gibbonPersonID);
        }

        return $this->runQuery($query, $criteria);
    }

    /**
     * @param QueryCriteria $criteria
     * @param string $deepLearningEventID
     * @return DataSet
     */
    public function queryExperiencesByEvent(QueryCriteria $criteria, $deepLearningEventID)
    {
        $query = $this
            ->newQuery()
            ->distinct()
            ->cols([
                'deepLearningEvent.deepLearningEventID',
                'deepLearningEvent.name as eventName',
                'deepLearningEvent.nameShort as eventNameShort',
                'deepLearningEvent.accessOpenDate',
                'deepLearningEvent.accessCloseDate',
                'deepLearningExperience.deepLearningExperienceID',
                'deepLearningExperience.name',
                'deepLearningExperience.active',
                'deepLearningExperience.enrolmentMin',
                'deepLearningExperience.enrolmentMax',
                'deepLearningUnit.headerImage',
            ])
            ->from($this->getTableName())
            ->innerJoin('deepLearningEvent', 'deepLearningEvent.deepLearningEventID=deepLearningExperience.deepLearningEventID')
            ->leftJoin('deepLearningUnit', 'deepLearningUnit.deepLearningUnitID=deepLearningExperience.deepLearningUnitID')
            ->where('deepLearningExperience.deepLearningEventID=:deepLearningEventID')
            ->bindValue('deepLearningEventID', $deepLearningEventID);

        $criteria->addFilterRules([
            'active' => function ($query, $active) {
                return $query
                    ->where('deepLearningExperience.active = :active')
                    ->bindValue('active', $active);
            },
        ]);

        return $this->runQuery($query, $criteria);
    }

    public function selectExperiences()
    {
        $sql = "SELECT deepLearningEvent.name as eventName, deepLearningEvent.deepLearningEventID, deepLearningExperience.deepLearningExperienceID, deepLearningExperience.name 
                FROM deepLearningExperience 
                JOIN deepLearningEvent ON (deepLearningEvent.deepLearningEventID=deepLearningExperience.deepLearningEventID)
                ORDER BY deepLearningEvent.name, deepLearningExperience.name";

        return $this->db()->select($sql);
    }

    public function selectExperiencesByEvent($deepLearningEventID)
    {
        $data = ['deepLearningEventID' => $deepLearningEventID];
        $sql = "SELECT deepLearningExperienceID as value, name 
                FROM deepLearningExperience 
                WHERE deepLearningEventID=:deepLearningEventID
                ORDER BY name";

        return $this->db()->select($sql, $data);
    }

    public function selectExperienceDetailsByEvent($deepLearningEventID)
    {
        $data = ['deepLearningEventID' => $deepLearningEventID];
        $sql = "SELECT deepLearningExperienceID as groupBy, deepLearningExperience.* 
                FROM deepLearningExperience 
                WHERE deepLearningEventID=:deepLearningEventID
                AND deepLearningExperience.active='Y'
                ORDER BY name";

        return $this->db()->select($sql, $data);
    }

    public function getExperienceDetailsByID($deepLearningExperienceID)
    {
        $data = ['deepLearningExperienceID' => $deepLearningExperienceID];
        $sql = "SELECT 
                    deepLearningEvent.deepLearningEventID,
                    deepLearningEvent.name as eventName,
                    deepLearningEvent.accessOpenDate,
                    deepLearningEvent.accessCloseDate,
                    deepLearningExperience.*,
                    deepLearningUnit.headerImage,
                    deepLearningUnit.description,
                    deepLearningUnit.majors,
                    deepLearningUnit.minors,
                    GROUP_CONCAT(DISTINCT gibbonYearGroup.nameShort ORDER BY gibbonYearGroup.sequenceNumber SEPARATOR ', ') AS yearGroups,
                    COUNT(DISTINCT gibbonYearGroup.gibbonYearGroupID) as yearGroupCount
                FROM deepLearningExperience
                JOIN deepLearningEvent ON (deepLearningEvent.deepLearningEventID=deepLearningExperience.deepLearningEventID)
                LEFT JOIN deepLearningUnit ON (deepLearningUnit.deepLearningUnitID=deepLearningExperience.deepLearningUnitID)
                LEFT JOIN gibbonYearGroup ON (FIND_IN_SET(gibbonYearGroup.gibbonYearGroupID, deepLearningExperience.gibbonYearGroupIDList))
                WHERE deepLearningExperience.deepLearningExperienceID=:deepLearningExperienceID
                GROUP BY deepLearningExperience.deepLearningExperienceID";

        return $this->db()->selectOne($sql, $data);
    }

    public function getExperienceEditAccess($deepLearningExperienceID, $gibbonPersonID)
    {
        $data = ['deepLearningExperienceID' => $deepLearningExperienceID, 'gibbonPersonID' => $gibbonPersonID];
        $sql = "SELECT deepLearningStaff.canEdit
                FROM deepLearningExperience
                JOIN deepLearningStaff ON (deepLearningStaff.deepLearningExperienceID=deepLearningExperience.deepLearningExperienceID)
                WHERE deepLearningExperience.deepLearningExperienceID=:deepLearningExperienceID
                AND deepLearningStaff.gibbonPersonID=:gibbonPersonID
                GROUP BY deepLearningExperience.deepLearningExperienceID";

        return $this->db()->selectOne($sql, $data);
    }

    public function getNextExperienceByID($deepLearningExperienceID)
    {
        $data = array('deepLearningExperienceID' => $deepLearningExperienceID);
        $sql = "SELECT * FROM deepLearningExperience WHERE name=(SELECT MIN(name) FROM deepLearningExperience WHERE name > (SELECT name FROM deepLearningExperience WHERE deepLearningExperienceID=:deepLearningExperienceID))";

        return $this->db()->selectOne($sql, $data);
    }

    public function getPreviousExperienceByID($deepLearningExperienceID)
    {
        $data = array('deepLearningExperienceID' => $deepLearningExperienceID);
        $sql = "SELECT * FROM deepLearningExperience WHERE name=(SELECT MAX(name) FROM deepLearningExperience WHERE name < (SELECT name FROM deepLearningExperience WHERE deepLearningExperienceID=:deepLearningExperienceID))";

        return $this->db()->selectOne($sql, $data);
    }
}
