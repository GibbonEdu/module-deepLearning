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

class EventGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'deepLearningEvent';
    private static $primaryKey = 'deepLearningEventID';
    private static $searchableColumns = ['deepLearningEvent.name'];

    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryEvents(QueryCriteria $criteria, $gibbonSchoolYearID)
    {
        $query = $this
            ->newQuery()
            ->distinct()
            ->from($this->getTableName())
            ->cols([
                'deepLearningEvent.deepLearningEventID',
                'deepLearningEvent.name',
                'deepLearningEvent.description',
                'deepLearningEvent.backgroundImage',
                'deepLearningEvent.active',
                'deepLearningEvent.viewable',
                'deepLearningEvent.accessOpenDate',
                'deepLearningEvent.accessCloseDate',
                "MIN(deepLearningEventDate.eventDate) as startDate",
                "MAX(deepLearningEventDate.eventDate) as endDate",
                "COUNT(DISTINCT deepLearningExperience.deepLearningExperienceID) as experienceCount",
                "GROUP_CONCAT(DISTINCT deepLearningEventDate.eventDate SEPARATOR ',') AS eventDates",
                "GROUP_CONCAT(DISTINCT deepLearningExperience.name) AS experienceNames",
                "GROUP_CONCAT(DISTINCT gibbonYearGroup.nameShort ORDER BY gibbonYearGroup.sequenceNumber SEPARATOR ', ') AS yearGroups",
                "COUNT(DISTINCT gibbonYearGroup.gibbonYearGroupID) as yearGroupCount"
            ])
            ->leftJoin('deepLearningEventDate', 'deepLearningEvent.deepLearningEventID=deepLearningEventDate.deepLearningEventID')
            ->leftJoin('deepLearningExperience', 'deepLearningEvent.deepLearningEventID=deepLearningExperience.deepLearningEventID')
            ->leftJoin('gibbonYearGroup', 'FIND_IN_SET(gibbonYearGroup.gibbonYearGroupID, deepLearningEvent.gibbonYearGroupIDList)')
            ->where('deepLearningEvent.gibbonSchoolYearID=:gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->groupBy(['deepLearningEventID','name']);

        $criteria->addFilterRules([
            'active' => function ($query, $active) {
                return $query
                    ->where('deepLearningEvent.active = :active')
                    ->bindValue('active', $active);
            },
        ]);

        return $this->runQuery($query, $criteria);
    }

    public function selectEventsBySchoolYear()
    {
        $sql = "SELECT gibbonSchoolYear.name as groupBy, deepLearningEventID as value, deepLearningEvent.name 
                FROM deepLearningEvent
                JOIN gibbonSchoolYear ON (deepLearningEvent.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) 
                ORDER BY gibbonSchoolYear.sequenceNumber DESC, name";

        return $this->db()->select($sql);
    }

    public function selectYearGroupsByEvent($deepLearningEventID)
    {
        $data = ['deepLearningEventID' => $deepLearningEventID];
        $sql = "SELECT gibbonYearGroup.gibbonYearGroupID as `value`, gibbonYearGroup.name
                FROM deepLearningEvent
                JOIN gibbonYearGroup ON (FIND_IN_SET(gibbonYearGroup.gibbonYearGroupID, deepLearningEvent.gibbonYearGroupIDList))
                WHERE deepLearningEvent.deepLearningEventID=:deepLearningEventID
                ORDER BY gibbonYearGroup.sequenceNumber, gibbonYearGroup.name";

        return $this->db()->select($sql, $data);
    }

    public function getEventDetailsByID($deepLearningEventID)
    {
        $data = ['deepLearningEventID' => $deepLearningEventID];
        $sql = "SELECT deepLearningEvent.*,
                    gibbonSchoolYear.name as schoolYear, 
                    GROUP_CONCAT(DISTINCT deepLearningEventDate.eventDate SEPARATOR ',') AS eventDates,
                    GROUP_CONCAT(DISTINCT gibbonYearGroup.nameShort ORDER BY gibbonYearGroup.sequenceNumber SEPARATOR ', ') AS yearGroups,
                    COUNT(DISTINCT gibbonYearGroup.gibbonYearGroupID) as yearGroupCount
                FROM deepLearningEvent
                JOIN gibbonSchoolYear ON (deepLearningEvent.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) 
                JOIN deepLearningEventDate ON (deepLearningEvent.deepLearningEventID=deepLearningEventDate.deepLearningEventID)
                LEFT JOIN gibbonYearGroup ON (FIND_IN_SET(gibbonYearGroup.gibbonYearGroupID, deepLearningEvent.gibbonYearGroupIDList))
                WHERE deepLearningEvent.deepLearningEventID=:deepLearningEventID
                GROUP BY deepLearningEvent.deepLearningEventID";

        return $this->db()->selectOne($sql, $data);
    }
}
