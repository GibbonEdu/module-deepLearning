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

class UnitPhotoGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'deepLearningUnitPhoto';
    private static $primaryKey = 'deepLearningUnitPhotoID';
    private static $searchableColumns = [''];

    public function selectPhotosByUnit($deepLearningUnitID)
    {
        $data = ['deepLearningUnitID' => $deepLearningUnitID];
        $sql = "SELECT deepLearningUnitPhoto.deepLearningUnitPhotoID, deepLearningUnitPhoto.filePath, deepLearningUnitPhoto.caption
                FROM deepLearningUnitPhoto
                WHERE deepLearningUnitPhoto.deepLearningUnitID=:deepLearningUnitID
                ORDER BY deepLearningUnitPhoto.sequenceNumber";

        return $this->db()->select($sql, $data);
    }

    public function selectPhotosByExperience($deepLearningExperienceID)
    {
        $data = ['deepLearningExperienceID' => $deepLearningExperienceID];
        $sql = "SELECT deepLearningUnitPhoto.deepLearningUnitPhotoID, deepLearningUnitPhoto.filePath, deepLearningUnitPhoto.caption
                FROM deepLearningUnitPhoto
                JOIN deepLearningExperience ON (deepLearningExperience.deepLearningUnitID=deepLearningUnitPhoto.deepLearningUnitID)
                WHERE deepLearningExperience.deepLearningExperienceID=:deepLearningExperienceID
                ORDER BY deepLearningUnitPhoto.sequenceNumber";

        return $this->db()->select($sql, $data);
    }

    public function selectPhotosNotInList($deepLearningUnitID, $photoIDList)
    {
        $photoIDList = is_array($photoIDList) ? implode(',', $photoIDList) : $photoIDList;

        $data = ['deepLearningUnitID' => $deepLearningUnitID, 'photoIDList' => $photoIDList];
        $sql = "SELECT * FROM deepLearningUnitPhoto WHERE deepLearningUnitID=:deepLearningUnitID AND NOT FIND_IN_SET(deepLearningUnitPhotoID, :photoIDList)";

        return $this->db()->select($sql, $data);
    }
}
