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

class StaffGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'deepLearningStaff';
    private static $primaryKey = 'deepLearningStaffID';
    private static $searchableColumns = ['gibbonPerson.preferredName', 'gibbonPerson.surname', 'deepLearningStaff.role'];

    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryStaffByEvent(QueryCriteria $criteria, $deepLearningEventID)
    {
        $query = $this
            ->newQuery()
            ->from('deepLearningStaff')
            ->cols([
                'deepLearningEvent.name as eventName',
                'deepLearningEvent.nameShort as eventNameShort',
                'deepLearningExperience.name',
                'deepLearningExperience.deepLearningExperienceID',
                'deepLearningEvent.active',
                'deepLearningEvent.accessOpenDate',
                'deepLearningEvent.accessCloseDate',
                "(CASE WHEN CURRENT_TIMESTAMP >= deepLearningEvent.viewableDate THEN 'Y' ELSE 'N' END) as viewable",
                'gibbonPerson.preferredName',
                'gibbonPerson.surname',
                'gibbonPerson.title',
                'gibbonPerson.image_240',
                'gibbonPerson.email',
                'deepLearningStaff.gibbonPersonID',
                'deepLearningStaff.role',
                'gibbonStaff.initials',
                "(FIND_IN_SET(deepLearningStaff.role, 'Trip Leader,Teacher,Support')) as roleOrder",
            ])
            ->innerJoin('deepLearningExperience', 'deepLearningExperience.deepLearningExperienceID=deepLearningStaff.deepLearningExperienceID')
            ->innerJoin('deepLearningEvent', 'deepLearningEvent.deepLearningEventID=deepLearningExperience.deepLearningEventID')
            ->innerJoin('gibbonPerson', 'gibbonPerson.gibbonPersonID=deepLearningStaff.gibbonPersonID')
            ->innerJoin('gibbonStaff', 'gibbonPerson.gibbonPersonID=gibbonStaff.gibbonPersonID')
            ->where('deepLearningExperience.active="Y"')            
            ->where('deepLearningExperience.deepLearningEventID=:deepLearningEventID')
            ->bindValue('deepLearningEventID', $deepLearningEventID)
            ->where("gibbonPerson.status = 'Full'")
            ->where('(gibbonPerson.dateStart IS NULL OR gibbonPerson.dateStart <= :today)')
            ->where('(gibbonPerson.dateEnd IS NULL OR gibbonPerson.dateEnd >= :today)')
            ->bindValue('today', date('Y-m-d'))
            ->groupBy(['deepLearningStaff.gibbonPersonID']);

        return $this->runQuery($query, $criteria);
    }


    public function queryUnassignedStaffByEvent($criteria, $deepLearningEventID)
    {
        $query = $this
            ->newQuery()
            ->from('gibbonPerson')
            ->cols([
                '0 as deepLearningExperienceID',
                'deepLearningEvent.deepLearningEventID',
                'deepLearningEvent.name as eventName',
                'deepLearningEvent.nameShort as eventNameShort',
                'gibbonPerson.gibbonPersonID',
                'gibbonPerson.surname',
                'gibbonPerson.preferredName',
                'gibbonPerson.title',
                'gibbonPerson.email',
                'gibbonPerson.image_240',
                'gibbonStaff.type',
                'gibbonStaff.jobTitle',
                'gibbonStaff.initials',
            ])
            ->innerJoin('gibbonStaff', 'gibbonStaff.gibbonPersonID=gibbonPerson.gibbonPersonID')
            ->leftJoin('deepLearningEvent', 'deepLearningEvent.deepLearningEventID=:deepLearningEventID')
            ->leftJoin('deepLearningExperience', 'deepLearningExperience.deepLearningEventID=deepLearningEvent.deepLearningEventID')
            ->leftJoin('deepLearningStaff', 'deepLearningStaff.deepLearningExperienceID=deepLearningExperience.deepLearningExperienceID AND deepLearningStaff.gibbonPersonID=gibbonPerson.gibbonPersonID')
            ->bindValue('deepLearningEventID', $deepLearningEventID)
            ->where("gibbonPerson.status = 'Full'")
            ->where('(gibbonPerson.dateStart IS NULL OR gibbonPerson.dateStart <= :today)')
            ->where('(gibbonPerson.dateEnd IS NULL OR gibbonPerson.dateEnd >= :today)')
            ->bindValue('today', date('Y-m-d'))
            ->groupBy(['gibbonPerson.gibbonPersonID'])
            ->having('COUNT(DISTINCT deepLearningStaff.deepLearningStaffID) = 0');

        $criteria->addFilterRules([
            'type' => function ($query, $deepLearningEventID) {
                return $query
                    ->where('deepLearningEvent.deepLearningEventID = :deepLearningEventID')
                    ->bindValue('deepLearningEventID', $deepLearningEventID);
            },
        ]);

        return $this->runQuery($query, $criteria);
    }

    public function selectStaffByEvent($deepLearningEventID)
    {
        $data = ['deepLearningEventID' => $deepLearningEventID];
        $sql = "SELECT gibbonPerson.gibbonPersonID as groupBy,
                    deepLearningExperience.deepLearningExperienceID,
                    deepLearningStaff.deepLearningStaffID,
                    deepLearningStaff.role,
                    deepLearningStaff.canEdit,
                    gibbonPerson.gibbonPersonID,
                    gibbonPerson.surname,
                    gibbonPerson.preferredName,
                    gibbonPerson.image_240
                FROM deepLearningStaff
                JOIN deepLearningExperience ON (deepLearningExperience.deepLearningExperienceID=deepLearningStaff.deepLearningExperienceID)
                JOIN gibbonPerson ON (gibbonPerson.gibbonPersonID=deepLearningStaff.gibbonPersonID) 
                WHERE deepLearningExperience.deepLearningEventID=:deepLearningEventID
                ORDER BY deepLearningStaff.role DESC, gibbonPerson.surname, gibbonPerson.preferredName";

        return $this->db()->select($sql, $data);
    }

    public function selectStaffByEventAndPerson($deepLearningEventID, $gibbonPersonID)
    {
        $data = ['deepLearningEventID' => $deepLearningEventID, 'gibbonPersonID' => $gibbonPersonID];
        $sql = "SELECT deepLearningExperience.deepLearningExperienceID,
                    deepLearningExperience.deepLearningEventID,
                    deepLearningExperience.name,
                    deepLearningStaff.deepLearningStaffID,
                    deepLearningStaff.gibbonPersonID,
                    deepLearningStaff.role,
                    deepLearningStaff.canEdit
                FROM deepLearningStaff
                JOIN deepLearningExperience ON (deepLearningExperience.deepLearningExperienceID=deepLearningStaff.deepLearningExperienceID)
                JOIN gibbonPerson ON (gibbonPerson.gibbonPersonID=deepLearningStaff.gibbonPersonID) 
                WHERE deepLearningExperience.deepLearningEventID=:deepLearningEventID
                AND deepLearningStaff.gibbonPersonID=:gibbonPersonID";

        return $this->db()->select($sql, $data);
    }

    public function selectStaffByExperience($deepLearningExperienceID)
    {
        $data = ['deepLearningExperienceID' => $deepLearningExperienceID];
        $sql = "SELECT deepLearningStaff.deepLearningStaffID, deepLearningStaff.role, deepLearningStaff.canEdit, deepLearningStaff.notes, gibbonPerson.gibbonPersonID, gibbonPerson.surname, gibbonPerson.preferredName
                FROM deepLearningStaff
                JOIN gibbonPerson ON (gibbonPerson.gibbonPersonID=deepLearningStaff.gibbonPersonID) 
                WHERE deepLearningStaff.deepLearningExperienceID=:deepLearningExperienceID
                ORDER BY deepLearningStaff.role DESC, gibbonPerson.surname, gibbonPerson.preferredName";

        return $this->db()->select($sql, $data);
    }

    public function selectTripLeadersByExperience($deepLearningExperienceID)
    {
        $deepLearningExperienceIDList = is_array($deepLearningExperienceID) ? implode(',', $deepLearningExperienceID) : $deepLearningExperienceID;

        $data = ['deepLearningExperienceIDList' => $deepLearningExperienceIDList];
        $sql = "SELECT deepLearningStaff.deepLearningStaffID, deepLearningStaff.role, deepLearningStaff.canEdit, deepLearningStaff.notes, gibbonPerson.gibbonPersonID, gibbonPerson.surname, gibbonPerson.preferredName
                FROM deepLearningStaff
                JOIN gibbonPerson ON (gibbonPerson.gibbonPersonID=deepLearningStaff.gibbonPersonID) 
                WHERE FIND_IN_SET(deepLearningStaff.deepLearningExperienceID, :deepLearningExperienceIDList)
                AND deepLearningStaff.role='Trip Leader'
                ORDER BY deepLearningStaff.role DESC, gibbonPerson.surname, gibbonPerson.preferredName";

        return $this->db()->select($sql, $data);
    }

    public function getStaffExperienceAccess($deepLearningExperienceID, $gibbonPersonID)
    {
        $data = ['deepLearningExperienceID' => $deepLearningExperienceID, 'gibbonPersonID' => $gibbonPersonID];
        $sql = "SELECT deepLearningStaff.deepLearningStaffID, deepLearningStaff.role, deepLearningStaff.canEdit, deepLearningStaff.notes, gibbonPerson.gibbonPersonID, gibbonPerson.surname, gibbonPerson.preferredName
                FROM deepLearningStaff
                JOIN gibbonPerson ON (gibbonPerson.gibbonPersonID=deepLearningStaff.gibbonPersonID) 
                WHERE deepLearningStaff.deepLearningExperienceID=:deepLearningExperienceID
                AND deepLearningStaff.gibbonPersonID=:gibbonPersonID";

        return $this->db()->selectOne($sql, $data);
    }

    public function deleteStaffByEvent($deepLearningEventID, $gibbonPersonID)
    {
        $data = ['deepLearningEventID' => $deepLearningEventID, 'gibbonPersonID' => $gibbonPersonID];
        $sql = "DELETE deepLearningStaff 
                FROM deepLearningStaff 
                JOIN deepLearningExperience ON (deepLearningExperience.deepLearningExperienceID=deepLearningStaff.deepLearningExperienceID) 
                WHERE deepLearningExperience.deepLearningEventID=:deepLearningEventID 
                AND deepLearningStaff.gibbonPersonID=:gibbonPersonID";

        return $this->db()->delete($sql, $data);
    }

    public function deleteStaffNotInList($deepLearningExperienceID, $staffIDList)
    {
        $staffIDList = is_array($staffIDList) ? implode(',', $staffIDList) : $staffIDList;

        $data = ['deepLearningExperienceID' => $deepLearningExperienceID, 'staffIDList' => $staffIDList];
        $sql = "DELETE FROM deepLearningStaff WHERE deepLearningExperienceID=:deepLearningExperienceID AND NOT FIND_IN_SET(deepLearningStaffID, :staffIDList)";

        return $this->db()->delete($sql, $data);
    }
}
