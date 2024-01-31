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

class ExperienceTripGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'deepLearningExperienceTrip';
    private static $primaryKey = 'deepLearningExperienceTripID';
    private static $searchableColumns = [];

    /**
     * @param QueryCriteria $criteria
     * @param string $deepLearningEventID
     * @return DataSet
     */
    public function queryTripsByExperience(QueryCriteria $criteria, $deepLearningExperienceID)
    {
        $query = $this
            ->newQuery()
            ->distinct()
            ->cols([
                'tripPlannerRequests.tripPlannerRequestID',
                'tripPlannerRequests.creatorPersonID',
                'tripPlannerRequests.title as tripTitle',
                'tripPlannerRequests.description',
                'tripPlannerRequests.location',
                'tripPlannerRequests.status',
                'tripPlannerRequests.messengerGroupID',
                'gibbonPerson.title',
                'gibbonPerson.preferredName',
                'gibbonPerson.surname',
                '(SELECT startDate FROM tripPlannerRequestDays WHERE tripPlannerRequestID = tripPlannerRequests.tripPlannerRequestID ORDER BY startDate ASC LIMIT 1) as firstDayOfTrip',
            ])
            ->from($this->getTableName())
            ->innerJoin('tripPlannerRequests', 'tripPlannerRequests.tripPlannerRequestID=deepLearningExperienceTrip.tripPlannerRequestID')
            ->innerJoin('gibbonPerson', 'gibbonPerson.gibbonPersonID = tripPlannerRequests.creatorPersonID')
            ->innerJoin('deepLearningExperience', 'deepLearningExperience.deepLearningExperienceID=deepLearningExperienceTrip.deepLearningExperienceID')
            ->innerJoin('deepLearningEvent', 'deepLearningEvent.deepLearningEventID=deepLearningExperience.deepLearningEventID')
            ->where('deepLearningExperienceTrip.deepLearningExperienceID=:deepLearningExperienceID')
            ->bindValue('deepLearningExperienceID', $deepLearningExperienceID);

        $criteria->addFilterRules([
            'active' => function ($query, $active) {
                return $query
                    ->where('deepLearningExperience.active = :active')
                    ->bindValue('active', $active);
            },
        ]);

        return $this->runQuery($query, $criteria);
    }

    public function selectTripRequestsByCreator($gibbonSchoolYearID, $gibbonPersonID)
    {
        $data = ['gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonPersonID' => $gibbonPersonID];
        $sql = "SELECT tripPlannerRequestID as value, tripPlannerRequests.title as name
                FROM tripPlannerRequests 
                WHERE tripPlannerRequests.creatorPersonID=:gibbonPersonID
                AND tripPlannerRequests.gibbonSchoolYearID=:gibbonSchoolYearID
                AND (tripPlannerRequests.status = 'Requested' OR tripPlannerRequests.status = 'Awaiting Final Approval' OR tripPlannerRequests.status = 'Draft')
                AND (SELECT IFNULL(MAX(endDate),'0000-00-00') FROM tripPlannerRequestDays WHERE tripPlannerRequestID = tripPlannerRequests.tripPlannerRequestID) < CURRENT_DATE
                ORDER BY tripPlannerRequests.title";

        return $this->db()->select($sql, $data);
    }

    public function attachTripRequest($deepLearningExperienceID, $tripPlannerRequestID, $deepLearningEventDateIDList = null)
    {
        $data = ['deepLearningExperienceID' => $deepLearningExperienceID, 'tripPlannerRequestID' => $tripPlannerRequestID, 'deepLearningEventDateIDList' => $deepLearningEventDateIDList];
        $this->insertAndUpdate($data, ['deepLearningEventDateIDList' => $deepLearningEventDateIDList]);

        $data = ['deepLearningExperienceID' => $deepLearningExperienceID, 'tripPlannerRequestID' => $tripPlannerRequestID];
        $sql = "UPDATE tripPlannerRequests SET deepLearningExperienceID=:deepLearningExperienceID WHERE tripPlannerRequestID=:tripPlannerRequestID";

        return $this->db()->update($sql, $data);
    }
}
