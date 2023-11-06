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
    private static $searchableColumns = [''];

    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryEvents(QueryCriteria $criteria)
    {
        $query = $this
            ->newQuery()
            ->distinct()
            ->from($this->getTableName())
            ->cols(['deepLearningEvent.deepLearningEventID',
            'deepLearningEvent.name',
            'deepLearningEvent.description',
            'deepLearningEvent.backgroundImage',
            'deepLearningEvent.active',
            "COUNT(DISTINCT deepLearningExperience.deepLearningExperienceID) as experienceCount",
            "GROUP_CONCAT(DISTINCT eventDate SEPARATOR ',') AS eventDates",
            "GROUP_CONCAT(DISTINCT deepLearningExperience.name) AS experienceNames"])
            ->leftJoin('deepLearningEventDate', 'deepLearningEvent.deepLearningEventID=deepLearningEventDate.deepLearningEventID')
            ->leftJoin('deepLearningExperience', 'deepLearningEvent.deepLearningEventID=deepLearningExperience.deepLearningEventID')
            ->groupBy(['deepLearningEventID','name']);

        return $this->runQuery($query, $criteria);
    }

    public function selectEvents()
    {
        $sql = "SELECT deepLearningEventID as value, name, description, backgroundImage, active FROM deepLearningEvent ORDER BY name";

        return $this->db()->select($sql);
    }
}
