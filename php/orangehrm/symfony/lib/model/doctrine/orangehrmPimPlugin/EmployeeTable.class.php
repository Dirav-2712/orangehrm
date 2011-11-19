<?php

/**
 * EmployeeTable
 *
 * This class has been auto-generated by the Doctrine ORM Framework
 */
class EmployeeTable extends PluginEmployeeTable {
    /**
     * Returns an instance of this class.
     *
     * @return object EmployeeTable
     */
    public static function getInstance() {
        return Doctrine_Core::getTable('Employee');
    }


    /**
     * Mapping of search field names to database fields
     * @var array
     */
    protected static $searchMapping = array(
            'id' => 'e.employee_id',
            'employee_name' => 'concat_ws(\' \', e.emp_firstname,e.emp_middle_name,e.emp_lastname)',
            'middleName' => 'e.emp_middle_name',
            'lastName' => 'e.emp_lastName',
            'job_title' => 'j.job_title',
            'employee_status' => 'es.estat_name',
            'sub_unit' => 'cs.name',
            'supervisor_name' => 'concat_ws(\' \', s.emp_firstname,s.emp_middle_name,s.emp_lastname)',
            'supervisorId' => 's.emp_firstname',
    );

    /**
     * Mapping of sort field names to database fields
     * @var array
     */
    protected static $sortMapping = array(
            'employeeId' => 'e.employee_id',
            'firstName' => 'e.emp_firstname',
            'middleName' => 'e.emp_middle_name',
            'firstMiddleName' => array('e.emp_firstname','e.emp_middle_name'),
            'lastName' => 'e.emp_lastName',
            'fullName' => array('e.emp_firstname', 'e.emp_middle_name', 'e.emp_lastName'),
            'jobTitle' => 'j.job_title',
            'employeeStatus' => 'es.estat_name',
            'subDivision' => 'cs.name',
            'supervisor' => array('s.emp_firstname', 's.emp_lastname'),
    );


    /**
     * Get employee list after sorting and filtering using given parameters.
     *
     * @param array $sortField
     * @param $sortOrder
     * @param $filters
     * @return array
     */
    public function getEmployeeList($sortField = 'empNumber', $sortOrder = 'asc', array $filters = null, $offset = null, $limit = null) {

        $select = '';
        $query = '';
        $bindParams = array();
        $orderBy = '';

        $this->_getEmployeeListQuery($select, $query, $bindParams, $orderBy,
                $sortField, $sortOrder, $filters);

        $completeQuery = $select . ' ' . $query . ' ' . $orderBy;

        if (!is_null($offset) && !is_null($limit)) {
            $completeQuery .= ' LIMIT ' . $offset . ', ' . $limit;
        }

        if (sfConfig::get('sf_logging_enabled')) {
            $msg = $completeQuery;
            if (count($bindParams) > 0 ) {
                $msg .=  ' (' . implode(',', $bindParams) . ')';
            }
            sfContext::getInstance()->getLogger()->info($msg);
        }

        $conn = Doctrine_Manager::connection();
        $statement = $conn->prepare($completeQuery);
        $result = $statement->execute($bindParams);
        //$statement->setFetchMode(PDO::FETCH_ASSOC);

        $employees = new Doctrine_Collection(Doctrine::getTable('Employee'));

        if ($result) {
            while ($row = $statement->fetch() ) {
                $employee = new Employee();

                $employee->setEmpNumber($row['empNumber']);
                $employee->setEmployeeId($row['employeeId']);
                $employee->setFirstName($row['firstName']);
                $employee->setMiddleName($row['middleName']);
                $employee->setLastName($row['lastName']);

                $jobTitle = new JobTitle();
                $jobTitle->setId($row['jobTitleId']);
                $jobTitle->setJobTitleName($row['jobTitle']);
                $employee->setJobTitle($jobTitle);

                $employeeStatus = new EmployeeStatus();
                $employeeStatus->setId($row['employeeStatusId']);
                $employeeStatus->setName($row['employeeStatus']);
                $employee->setEmployeeStatus($employeeStatus);

                $workStation = new CompanyStructure();
                $workStation->setTitle($row['subDivision']);
                $workStation->setId($row['subDivisionId']);
                $employee->setSubDivision($workStation);

                $supervisorList = $row['supervisors'];

                if (!empty($supervisorList)) {

                    $supervisors = new Doctrine_Collection(Doctrine::getTable('Employee'));

                    $supervisorArray = explode(',', $supervisorList);
                    foreach ($supervisorArray as $supervisor) {
                        list($first, $last) = explode(' ', $supervisor);
                        $supervisor = new Employee();
                        $supervisor->setFirstName($first);
                        $supervisor->setLastName($last);
                        $employee->supervisors[] = $supervisor;
                    }
                }

                $employees[] = $employee;
            }
        }

        return $employees;

    }

    /**
     * Get employee list after sorting and filtering using given parameters.
     *
     * @param array $sortField
     * @param $sortOrder
     * @param $filters
     * @return array
     */
    public function getEmployeeCount(array $filters = null) {

        $select = '';
        $query = '';
        $bindParams = array();
        $orderBy = '';

        $this->_getEmployeeListQuery($select, $query, $bindParams, $orderBy, null, null, $filters);

        $countQuery = 'SELECT COUNT(*) FROM (' . $select . ' ' . $query . ' ) AS countqry';

        if (sfConfig::get('sf_logging_enabled')) {
            $msg = 'COUNT: ' . $countQuery;
            if (count($bindParams) > 0 ) {
                $msg .=  ' (' . implode(',', $bindParams) . ')';
            }
            sfContext::getInstance()->getLogger()->info($msg);
        }

        $conn = Doctrine_Manager::connection();
        $statement = $conn->prepare($countQuery);
        $result = $statement->execute($bindParams);
        $count = 0;
        if ($result) {
            if ($statement->rowCount() > 0) {
                $count = $statement->fetchColumn();
            }
        }

        return $count;
    }

    /**
     * Get SQL Query which can be used fetch employee list with the given
     * sorting and filtering options
     *
     * @param &$select select part of query
     * @param &$query  query
     * @param &$bindParams bind params for query
     * @param &$orderBy order by part of query
     * @param array $sortField
     * @param $sortOrder
     * @param $filters
     * @return none
     */
    private function _getEmployeeListQuery(&$select, &$query, array &$bindParams, &$orderBy,
            $sortField = null, $sortOrder = null, array $filters = null) {

        $searchByStatus = false;

        /*
	     * Using direct SQL since it is difficult to use Doctrine DQL or RawSQL to get an efficient
	     * query taht searches the company structure tree and supervisors.
        */
        $supervisorNameSubQuery = '(SELECT GROUP_CONCAT(emp_firstname, \' \', emp_lastname) ' .
                ' FROM hs_hr_employee WHERE emp_number IN (SELECT erep_sup_emp_number ' . 
                ' FROM hs_hr_emp_reportto where erep_sub_emp_number = e.emp_number))';
        
        $select = 'SELECT e.emp_number AS empNumber, e.employee_id AS employeeId, ' .
                'e.emp_firstname AS firstName, e.emp_lastname AS lastName, ' .
                'e.emp_middle_name AS middleName, ' .
                'cs.name AS subDivision, cs.id AS subDivisionId,' .
                'j.job_title AS jobTitle, j.id AS jobTitleId, ' .
                'es.name AS employeeStatus, es.id AS employeeStatusId, ' .
                //'GROUP_CONCAT(s.emp_firstname, \' \', s.emp_lastname ORDER BY erep_reporting_mode ) ' .
                //' AS supervisors ';
                $supervisorNameSubQuery . ' AS supervisors';

        $query = 'FROM hs_hr_employee e ' .
                '  LEFT JOIN ohrm_subunit cs ON cs.id = e.work_station ' .
                '  LEFT JOIN ohrm_job_title j on j.id = e.job_title_code ' .
                '  LEFT JOIN ohrm_emp_status es on e.emp_status = es.id ' .
                '  LEFT JOIN hs_hr_emp_reportto rt on e.emp_number = rt.erep_sub_emp_number ' .
                '  LEFT JOIN hs_hr_employee s on s.emp_number = rt.erep_sup_emp_number ';

        /* search filters */
        $conditions = array();

        if (!empty($filters)) {

            $filterCount = 0;

            // { ["employee_name"]=> string(3) "Abc"
            // ["supervisor_name"]=> string(3) "def"
            // ["id"]=> string(0) "" ["job_title"]=> string(0) ""
            // ["employee_status"]=> string(0) ""
            // ["sub_unit"]=> string(0) "" }

            foreach ($filters as $searchField=>$searchBy ) {
                if (!empty($searchField) && !empty($searchBy)
                        && array_key_exists($searchField, self::$searchMapping) ) {
                    $field = self::$searchMapping[$searchField];

                    if ($searchField == 'sub_unit') {

                        /*
                         * Not efficient if searching substations by more than one value, but
                         * we only have the facility to search by one value in the UI.
                        */
                        $conditions[] =  'e.work_station IN (SELECT n.id FROM ohrm_subunit n ' .
                                'INNER JOIN ohrm_subunit p WHERE n.lft >= p.lft ' .
                                'AND n.rgt <= p.rgt AND p.id = ? )';
                        $bindParams[] = $searchBy;
                    } else if ($searchField == 'id') {
                        $conditions[] = ' e.employee_id LIKE ? ';
                        $bindParams[] = $searchBy;
                        //$bindParams[] = '%' . $searchBy . '%';
                    } else if ($searchField == 'job_title') {
                        $conditions[] = ' j.id = ? ';
                        $bindParams[] = $searchBy;
                    } else if ($searchField == 'employee_status') {
                        $conditions[] = ' es.estat_code = ? ';
                        $bindParams[] = $searchBy;
                    } else if ($searchField == 'supervisorId') {
                        
                        $subordinates = $this->_getSubordinateIds($searchBy);
                        if (count($subordinates) > 0) {
                            $conditions[] = ' e.emp_number IN (' . implode(',', $subordinates) . ') ';
                        } else {                        
                            $conditions[] = ' s.emp_number = ? ';
                            $bindParams[] = $searchBy;
                        }
                    } else if ($searchField == 'supervisor_name') {
                        $conditions[] = $field . ' LIKE ? ';
                        // Replace multiple spaces in string with wildcards
                        $value = preg_replace('!\s+!', '%', $searchBy);
                        $bindParams[] = '%' . $value . '%';

                    } else if ($searchField == 'employee_name') {
                        $conditions[] = $field . ' LIKE ? ';
                        // Replace multiple spaces in string with wildcards
                        $value = preg_replace('!\s+!', '%', $searchBy);
                        $bindParams[] = '%' . $value . '%';
                    }
                    $filterCount++;

                    if ($searchField == 'employee_status') {
                        $searchByStatus = true;
                    }
                }
            }
        }

        /* If not searching by employee status, hide terminated employees */
        if (!$searchByStatus) {
            $conditions[] = "( e.emp_status != 'EST000' OR e.emp_status IS NULL )";
        }

        /* Build the query */
        $numConditions = 0;
        foreach ($conditions as $condition) {
            $numConditions++;

            if ($numConditions == 1) {
                $query .= ' WHERE ' . $condition;
            } else {
                $query .= ' AND ' . $condition;
            }
        }

        /* Group by */
        $query .= ' GROUP BY e.emp_number ';

        /* sorting */
        $order = array();

        if( !empty($sortField) && !empty($sortOrder) ) {
            if( array_key_exists($sortField, self::$sortMapping) ) {
                $field = self::$sortMapping[$sortField];
                if (is_array($field)) {
                    foreach ($field as $name) {
                        $order[$name] = $sortOrder;
                    }
                } else {
                    $order[$field] = $sortOrder;
                }
            }
        }

        /* Default sort by emp_number, makes resulting order predictable, useful for testing */
        $order['e.emp_lastname'] = 'asc';

        /* Sort subordinates direct first, then indirect, then by supervisor name */
        $order['rt.erep_reporting_mode'] = 'asc';

        if ($sortField != 'supervisor') {
            $order['s.emp_firstname'] = 'asc';
            $order['s.emp_lastname'] = 'asc';
        }
        $order['e.emp_number'] = 'asc';

        /* Build the order by part */
        $numOrderBy = 0;
        foreach ($order as $field=>$dir) {
            $numOrderBy++;
            if ($numOrderBy == 1) {
                $orderBy = ' ORDER BY ' . $field . ' ' . $dir;
            } else {
                $orderBy .= ', ' . $field . ' ' . $dir;
            }
        }
    }


    /**
     * Delete Employees with given IDs.
     *
     * @param array $ids Array of employee ids to delete
     * @return int Number of employees deleted.
     */
    public function delete(array $ids) {
        $count = Doctrine_Query::create()
                ->delete()
                ->from('Employee')
                ->whereIn('empNumber', $ids)
                ->execute();

        return $count;
    }
    
    /**
     * Get list of subordinate employee Ids as an array on integers
     * 
     * @return type Comma separated list or false if no subordinates
     */
    private function _getSubordinateIds($supervisorId) {

        $employeeService = new EmployeeService();
        $employeeService->setEmployeeDao(new EmployeeDao());
        $subordinatesList = $employeeService->getSupervisorEmployeeChain($supervisorId);

        $ids = array();
        
        foreach ($subordinatesList as $employee) {        
            $ids[] = intval($employee->getEmpNumber());
        }        
        
        return $ids;
    }    
}