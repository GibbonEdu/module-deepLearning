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

class UnitGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'deepLearningUnit';
    private static $primaryKey = 'deepLearningUnitID';
    private static $searchableColumns = ['deepLearningUnit.name', 'deepLearningUnit.majors', 'deepLearningUnit.minors', 'gibbonPerson.surname', 'gibbonPerson.preferredName'];

    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryUnits(QueryCriteria $criteria, $gibbonPersonID = null)
    {
        $query = $this
            ->newQuery()
            ->distinct()
            ->cols([
                'deepLearningUnit.deepLearningUnitID',
                'deepLearningUnit.name',
                'deepLearningUnit.majors',
                'deepLearningUnit.minors',
                'deepLearningUnit.status',
                "GROUP_CONCAT(DISTINCT CONCAT(gibbonPerson.preferredName, ' ', gibbonPerson.surname) ORDER BY gibbonPerson.surname SEPARATOR '<br/>') as authors",
                "(CASE WHEN deepLearningUnit.status = 'Retired' THEN 1 ELSE 0 END) as activeUnit",
            ])
            ->from($this->getTableName())
            ->leftJoin('deepLearningUnitAuthor', 'deepLearningUnitAuthor.deepLearningUnitID=deepLearningUnit.deepLearningUnitID')
            ->leftJoin('gibbonPerson', 'gibbonPerson.gibbonPersonID=deepLearningUnitAuthor.gibbonPersonID')
            ->groupBy(['deepLearningUnit.deepLearningUnitID']);

        if (!empty($gibbonPersonID)) {
            $query->leftJoin('deepLearningUnitAuthor as authorship', 'authorship.deepLearningUnitID=deepLearningUnit.deepLearningUnitID')
                ->where('authorship.gibbonPersonID=:gibbonPersonID')
                ->bindValue('gibbonPersonID', $gibbonPersonID);
        }

        $criteria->addFilterRules([
            'status' => function ($query, $status) {
                return $query
                    ->where('deepLearningUnit.status = :status')
                    ->bindValue('status', ucfirst($status));
            },
        ]);

        return $this->runQuery($query, $criteria);
    }

    public function selectPublishedUnits()
    {
        $sql = "SELECT deepLearningUnitID as value, name FROM deepLearningUnit WHERE status='Published' ORDER BY name";

        return $this->db()->select($sql);
    }
}
