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
    private static $searchableColumns = [''];

    public function selectStaffByExperience($deepLearningExperienceID)
    {
        $data = ['deepLearningExperienceID' => $deepLearningExperienceID];
        $sql = "SELECT deepLearningStaff.deepLearningStaffID, deepLearningStaff.role, deepLearningStaff.canEdit, gibbonPerson.gibbonPersonID, gibbonPerson.surname, gibbonPerson.preferredName
                FROM deepLearningStaff
                JOIN gibbonPerson ON (gibbonPerson.gibbonPersonID=deepLearningStaff.gibbonPersonID) 
                WHERE deepLearningStaff.deepLearningExperienceID=:deepLearningExperienceID
                ORDER BY deepLearningStaff.role DESC, gibbonPerson.surname, gibbonPerson.preferredName";

        return $this->db()->select($sql, $data);
    }

    public function deleteStaffNotInList($deepLearningExperienceID, $staffIDList)
    {
        $staffIDList = is_array($staffIDList) ? implode(',', $staffIDList) : $staffIDList;

        $data = ['deepLearningExperienceID' => $deepLearningExperienceID, 'staffIDList' => $staffIDList];
        $sql = "DELETE FROM deepLearningStaff WHERE deepLearningExperienceID=:deepLearningExperienceID AND NOT FIND_IN_SET(deepLearningStaffID, :staffIDList)";

        return $this->db()->delete($sql, $data);
    }
}
