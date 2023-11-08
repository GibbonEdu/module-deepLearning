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
    private static $searchableColumns = ['deepLearningExperience.name'];

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
                'deepLearningEvent.name as eventName',
                'deepLearningEvent.nameShort as eventNameShort',
                'deepLearningEvent.deepLearningEventID',
                'deepLearningExperience.deepLearningExperienceID',
                'deepLearningExperience.name',
                'deepLearningExperience.active',
                'deepLearningStaff.canEdit',
                'deepLearningStaff.role',
                "GROUP_CONCAT(DISTINCT CONCAT(gibbonPerson.preferredName, ' ', gibbonPerson.surname) ORDER BY gibbonPerson.surname SEPARATOR '<br/>') as tripLeaders",
                "COUNT(DISTINCT deepLearningStaff.deepLearningStaffID) as staffCount",
            ])
            ->from($this->getTableName())
            ->innerJoin('deepLearningEvent', 'deepLearningEvent.deepLearningEventID=deepLearningExperience.deepLearningEventID')
            ->leftJoin('deepLearningStaff', 'deepLearningStaff.deepLearningExperienceID=deepLearningExperience.deepLearningExperienceID')
            ->leftJoin('deepLearningStaff as tripLeader', 'tripLeader.deepLearningExperienceID=deepLearningExperience.deepLearningExperienceID AND tripLeader.role="Trip Leader"')
            ->leftJoin('gibbonPerson', 'gibbonPerson.gibbonPersonID=tripLeader.gibbonPersonID')
            ->where('deepLearningEvent.gibbonSchoolYearID=:gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->groupBy(['deepLearningExperience.deepLearningExperienceID']);

        if (!empty($gibbonPersonID)) {
            $query->where('deepLearningStaff.gibbonPersonID=:gibbonPersonID')
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
                'deepLearningEvent.name as eventName',
                'deepLearningEvent.nameShort as eventNameShort',
                'deepLearningEvent.deepLearningEventID',
                'deepLearningExperience.deepLearningExperienceID',
                'deepLearningExperience.name',
                'deepLearningExperience.active',
                'deepLearningUnit.headerImage',
            ])
            ->from($this->getTableName())
            ->innerJoin('deepLearningEvent', 'deepLearningEvent.deepLearningEventID=deepLearningExperience.deepLearningEventID')
            ->innerJoin('deepLearningUnit', 'deepLearningUnit.deepLearningUnitID=deepLearningExperience.deepLearningUnitID')
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
        $sql = "SELECT deepLearningExperienceID as value, name FROM deepLearningExperience ORDER BY name";

        return $this->db()->select($sql);
    }

    public function selectExperiencesByEvent($deepLearningEventID)
    {
        $data = ['deepLearningEventID' => $deepLearningEventID];
        $sql = "SELECT deepLearningExperienceID as value, name FROM deepLearningExperience 
                WHERE deepLearningEventID=:deepLearningEventID
                ORDER BY name";

        return $this->db()->select($sql, $data);
    }

    public function getExperienceDetailsByID($deepLearningExperienceID)
    {
        $data = ['deepLearningExperienceID' => $deepLearningExperienceID];
        $sql = "SELECT 
                    deepLearningEvent.deepLearningEventID,
                    deepLearningEvent.name as eventName,
                    deepLearningExperience.*
                FROM deepLearningExperience
                JOIN deepLearningEvent ON (deepLearningEvent.deepLearningEventID=deepLearningExperience.deepLearningEventID)
                WHERE deepLearningExperience.deepLearningExperienceID=:deepLearningExperienceID
                GROUP BY deepLearningExperience.deepLearningExperienceID";

        return $this->db()->selectOne($sql, $data);
    }
}
