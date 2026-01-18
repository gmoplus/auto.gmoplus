<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: gmowin.com
 *  FILE: RLREPORTBROKENLISTING.CLASS.PHP
 *  
 *  The software is a commercial product delivered under single, non-exclusive,
 *  non-transferable license for one domain or IP address. Therefore distribution,
 *  sale or transfer of the file in whole or in part without permission of Flynax
 *  respective owners is considered to be illegal and breach of Flynax License End
 *  User Agreement.
 *  
 *  You are not allowed to remove this information from the file without permission
 *  of Flynax respective owners.
 *  
 *  Flynax Classifieds Software 2024 | All copyrights reserved.
 *  
 *  https://www.flynax.com
 ******************************************************************************/

namespace ReportListings;

class ReportsFilter
{
    /**
     * @var array - SQL where conditions
     */
    private $where;

    /**
     * @var string - SQL group by condition
     */
    private $group;

    /**
     * @var array - SQL query selecting fields
     */
    private $fields;

    /**
     * @var string - SQL query
     */
    private $sql;

    /**
     * @var int - SQL query start from
     */
    private $start;

    /**
     * @var string - SQL JOIN part
     */
    private $join;

    /**
     * @var string - SQL ORDER BY part
     */
    private $order;

    /**
     * @var int - SQL query end to
     */
    private $end;

    /**
     * @var string - Active database of the filtering process
     */
    private $db_table;

    /**
     * @var \rlDb
     */
    protected $rlDb;

    /**
     * @var \rlActions
     */
    protected $rlActions;

    /**
     * Last db fetch rows count
     *
     * @since 3.2.0
     * @var integer
     */
    private $count = 0;

    /**
     * ReportsFilter constructor.
     */
    public function __construct()
    {
        $this->where = array();
        $this->order = '';
        $this->start = 0;
        $this->end = 20;
        $this->rlDb = FlynaxObjectsContainer::getObject('rlDb');
        $this->rlActions = FlynaxObjectsContainer::getObject('rlActions');
        $this->db_table = RL_DBPREFIX . 'report_broken_listing';
        $this->fields = '*';
    }

    /**
     * Prepare SQL string for executing
     *
     * @since 3.2.0 - $calcRows parameter added
     *
     * @param bool    $calcRows - Calc found rows
     * @return string $sql      - Prepared SQL string
     */
    private function prepareSql($calcRows = false)
    {
        $calculate = $calcRows ? 'SQL_CALC_FOUND_ROWS' : '';
        $fields = $this->prepareFields();
        $limit = $this->prepareLimit();
        $where = $this->prepareWhere();
        $group_by = $this->prepareGroupBy();
        $order = $this->prepareOrderBy();
        $join = $this->prepareJoin();

        $sql = "SELECT {$calculate} {$fields} FROM `{$this->db_table}` AS `T1` {$join} {$where} {$group_by} {$order} {$limit}";

        return $sql;
    }

    /**
     * Prepare JOIN string
     *
     * @return string - JOING string for using in the SQL query
     */
    private function prepareJoin()
    {
        return $this->join;
    }

    /**
     * Set order by value
     *
     * @param  string        $field - Report By provided field
     * @return ReportsFilter $this  - ReportFilter class instance to continue method chaning
     */
    public function orderBy($field)
    {
        $this->order = $field;
        return $this;
    }

    /**
     * Prepare order by SQL query part
     *
     * @return string - ORDER BY SQL string
     */
    public function prepareOrderBy()
    {
        if (!$this->order) {
            return '';
        }

        return "ORDER BY `{$this->order}`";
    }

    /**
     * Prepare Group BY SQL query part
     * @return string
     */
    private function prepareGroupBy()
    {
        if(!$this->group) {
            return '';
        }

        return 'GROUP BY ' . $this->group;
    }

    /**
     * Prepare SQL limit string
     * @return string - SQL limit string
     */
    private function prepareLimit()
    {
        return (!$this->end) ? '' : "LIMIT {$this->start}, {$this->end}";
    }

    /**
     * Prepare SQL selecting fields string
     *
     * @return string - fields selecting string
     */
    private function prepareFields()
    {
        return is_array($this->fields) ? implode(', ', $this->fields) : $this->fields;
    }

    public function fetchField($fields)
    {
        if (is_array($fields)) {
            $this->fields = implode(', ', $fields);
            return $this;
        }

        $this->fields = "`{$fields}` ";
        return $this;
    }

    /**
     * Prepare SQL where condition string
     * @return string - prepared SQL string
     */
    private function prepareWhere()
    {
        if (!$this->where) {
            return '';
        }

        $conditions = array();
        $where_string = 'WHERE ';
        foreach ($this->where as $condition => $value) {
            if ($condition == 'Date') {
                if ($value['from']) {
                    $conditions['Date'] = "UNIX_TIMESTAMP(DATE(`T1`.`Date`)) >= UNIX_TIMESTAMP('{$value['from']}') ";
                }

                if ($value['to']) {
                    $conditions['Date'] .= "AND UNIX_TIMESTAMP(DATE(`T1`.`Date`)) <= UNIX_TIMESTAMP('{$value['to']}') ";
                }
                continue;
            }

            $condition_value = is_int($value) ? $value : "'{$value}'";
            $conditions[] = "`T1`.`{$condition}`" . ' = ' . $condition_value;
        }
        $where_string .= implode(' AND ', $conditions);

        return $where_string;
    }

    /**
     * Filter report by provided condition.
     *
     * @param  string         $condition - Filter condition, should be equals to the table column of the report points table
     * @param  string         $value     - Filtering value
     * @param  bool           $is_push   - Is this filter is single.
     * @return ReportsFilter  $this      - Current class object
     */
    public function filterBy($condition, $value, $is_push = false)
    {
        if (!$is_push) {
            $this->where = array($condition => $value);
        } else {
            $this->where[$condition] = $value;
        }

        return $this;
    }

    /**
     * Filtering by plural condition (filterBy wrapper)
     *
     * @param  array         $args - Filtering condition
     * @return ReportsFilter $this - Current class object
     */
    public function massFilter($args)
    {
        $this->clearAll();
        foreach ($args as $filter => $value) {
            $this->filterBy($filter, $value, true);
        }

        return $this;
    }

    /**
     * Get result of the prepared SQL query
     *
     * @since 3.2.0 - $calcRows parameter added
     *
     * @param  int  $start    - Start rows position
     * @param  int  $end      - Limit of rows
     * @param  bool $calcRows - Calc found rows flag
     * @return array          - SQL query executing result
     */
    public function get($start = 0, $end = 0, $calcRows = false)
    {
        $this->start = $start;
        $this->end = $end;

        $sql = $this->prepareSql($calcRows);
        $result = $this->rlDb->getAll($sql);

        if ($calcRows) {
            $this->count = $this->rlDb->getRow("SELECT FOUND_ROWS() AS `count`", 'count');
        }

        return $result;
    }

    /**
     * Get last query found rows count
     *
     * @since 3.2.0
     * @return int - Rows found count
     */
    public function getLastCount()
    {
        $count = $this->count;
        $this->count = 0;

        return $count;
    }

    /**
     * Getting first row from database answer
     *
     * @return array $result - First row of the answer
     */
    public function first()
    {
        $sql = $this->prepareSql();
        $result = $this->rlDb->getRow($sql);

        $this->clearAll();

        return $result;
    }

    /**
     * Set Group By field
     *
     * @param  string $condition   - Grouping by provided field
     * @return ReportsFilter $this - Report Filter class intance to continue chaning
     */
    public function groupBy($condition)
    {
        $this->group = "`{$condition}`";

        return $this;
    }

    /**
     * Getting total rows of the prepared SQL query
     * @return int - Total rows
     */
    public function total()
    {
        $this->start = 0;
        $this->end = 0;
        $this->fields = array("COUNT(`ID`) as 'count'");
        $sql = $this->prepareSql();
        $result = $this->rlDb->getRow($sql);
        $this->clearAll();
        return $result['count'];
    }

    /**
     * Clear all prepared filtering data
     */
    public function clearAll()
    {
        $this->where = array();
    }
}
