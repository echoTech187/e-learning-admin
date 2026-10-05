<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Datatables Library for CodeIgniter 3
 * 
 * A fluent interface for DataTables server-side processing,
 * inspired by yajra/laravel-datatables. Includes native support
 * for High-Performance Split-Query (Late Row Lookup) mode.
 */
class FluentDatatables
{
    protected $CI;
    protected $table;
    protected $primary_key = 'id';
    
    // Core query parameters
    protected $select = '*';
    protected $where = [];
    protected $where_in = [];
    protected $joins = [];
    protected $group_by = [];
    
    // Datatables-specific configurations
    protected $column_order = [];
    protected $column_search = [];
    protected $order = [];
    
    // Output modifiers
    protected $add_columns = [];
    protected $edit_columns = [];
    protected $result_processor = NULL;
    
    // Performance and Bypass Flags
    protected $manual_recordsTotal = NULL;
    protected $manual_recordsFiltered = NULL;
    protected $manual_data = NULL;
    protected $use_fulltext = FALSE;
    protected $fulltext_mode = 'BOOLEAN MODE';
    
    // Callbacks
    protected $late_lookup_callback = NULL;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->database();
    }

    /**
     * Set the primary table name.
     * Automatically resets all previous configurations.
     * 
     * @param string $table
     * @return $this
     */
    public function of($table)
    {
        $this->reset();
        $this->table = $table;
        return $this;
    }

    /**
     * Set the primary key used for Split-Query mode (Late Lookup).
     * 
     * @param string $key Default is 'id'
     * @return $this
     */
    public function setPrimaryKey($key)
    {
        $this->primary_key = $key;
        return $this;
    }

    /**
     * Define the SELECT columns for the base query.
     * 
     * @param string|array $select
     * @return $this
     */
    public function select($select)
    {
        $this->select = $select;
        return $this;
    }

    /**
     * Add a WHERE condition to the query.
     * 
     * @param mixed $key
     * @param mixed $value
     * @return $this
     */
    public function where($key, $value = NULL)
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->where[] = ['key' => $k, 'value' => $v, 'escape' => TRUE];
            }
        } else {
            $this->where[] = ['key' => $key, 'value' => $value, 'escape' => TRUE];
        }
        return $this;
    }

    /**
     * Add a WHERE IN condition.
     * 
     * @param string $key
     * @param array $values
     * @return $this
     */
    public function where_in($key, $values)
    {
        $this->where_in[] = ['key' => $key, 'values' => $values];
        return $this;
    }

    /**
     * Add a JOIN clause to the query.
     * WARNING: Only use this if you want the join to be evaluated during count.
     * For high performance on large datasets, use withLateLookup() instead.
     * 
     * @param string $table
     * @param string $cond
     * @param string $type Default '' (Inner) or 'left', 'right', 'outer', etc.
     * @return $this
     */
    public function join($table, $cond, $type = '')
    {
        $this->joins[] = ['table' => $table, 'cond' => $cond, 'type' => $type];
        return $this;
    }

    /**
     * Group results by a column.
     * 
     * @param string|array $column
     * @return $this
     */
    public function groupBy($column)
    {
        $this->group_by[] = $column;
        return $this;
    }

    /**
     * Map frontend columns (numeric indices) to database columns for ordering.
     * 
     * @param array $columns e.g., [0 => 'id', 1 => 'created_at']
     * @return $this
     */
    public function set_column_order($columns)
    {
        $this->column_order = $columns;
        return $this;
    }

    public function set_orderable_columns($columns)
    {
        return $this->set_column_order($columns);
    }

    /**
     * Define which columns should be searched when the user types in the search box.
     * 
     * @param array $columns
     * @return $this
     */
    public function set_column_search($columns)
    {
        $this->column_search = $columns;
        return $this;
    }

    public function set_searchable_columns($columns)
    {
        return $this->set_column_search($columns);
    }

    /**
     * ENABLE FULL-TEXT SEARCH (MATCH AGAINST)
     * WARNING: Only use this if the physical database columns specified in 
     * set_searchable_columns() have a FULLTEXT INDEX applied in MySQL.
     * 
     * @param string $mode 'BOOLEAN MODE' (default) or 'NATURAL LANGUAGE MODE'
     * @return $this
     */
    public function enableFulltextSearch($mode = 'BOOLEAN MODE')
    {
        $this->use_fulltext = TRUE;
        $this->fulltext_mode = $mode;
        return $this;
    }

    /**
     * Set the default ORDER BY fallback if the user hasn't clicked a column.
     * 
     * @param array $order e.g., ['id' => 'desc']
     * @return $this
     */
    public function set_default_order($order)
    {
        $this->order = $order;
        return $this;
    }

    /**
     * Add a dynamic computed column to the JSON output.
     * 
     * @param string $name
     * @param callable $callback function($row)
     * @return $this
     */
    public function addColumn($name, $callback)
    {
        $this->add_columns[$name] = $callback;
        return $this;
    }

    /**
     * Modify an existing column's output in the JSON response.
     * 
     * @param string $name
     * @param callable $callback function($row)
     * @return $this
     */
    public function editColumn($name, $callback)
    {
        $this->edit_columns[$name] = $callback;
        return $this;
    }

    /**
     * Manually bypass the internal count_all_results for Total Records.
     * 
     * @param int $total
     * @return $this
     */
    public function setRecordsTotal($total)
    {
        $this->manual_recordsTotal = $total;
        return $this;
    }

    /**
     * Manually bypass the internal count_all_results for Filtered Records.
     * 
     * @param int $count
     * @return $this
     */
    public function setRecordsFiltered($count)
    {
        $this->manual_recordsFiltered = $count;
        return $this;
    }

    /**
     * Inject external parameters to override $_POST.
     * 
     * @param array $params
     * @return $this
     */
    public function params($params)
    {
        if (is_array($params) && !empty($params)) {
            $_POST = array_merge($_POST, $params);
        }
        return $this;
    }

    /**
     * Modify the raw CodeIgniter Query Builder fluently.
     * 
     * @param callable $callback function($db)
     * @return $this
     */
    public function setQuery($callback)
    {
        $this->CI->db->reset_query();
        call_user_func($callback, $this->CI->db);
        return $this;
    }

    /**
     * Modify the raw CodeIgniter Query Builder specifically for extra filters.
     * 
     * @param callable $callback function($db)
     * @return $this
     */
    public function filter($callback)
    {
        call_user_func($callback, $this->CI->db);
        return $this;
    }

    /**
     * Allows custom logic before returning the final array of rows.
     * 
     * @param callable $callback function(array $results): array
     * @return $this
     */
    public function modifyResult($callback)
    {
        $this->result_processor = $callback;
        return $this;
    }

    /**
     * Provide fully formatted data manually. Bypasses ALL database execution.
     * 
     * @param array|null $data
     * @return $this
     */
    public function setData($data)
    {
        $this->manual_data = $data;
        return $this;
    }

    /**
     * ENABLE SPLIT-QUERY (LATE ROW LOOKUP) MODE.
     * Defines the heavy JOINs that should ONLY be executed after Pagination IDs are fetched.
     * This avoids massive temporary tables in MySQL and speeds up queries by 90%+.
     * 
     * @param callable $callback function($db, $ids)
     * @return $this
     */
    public function withLateLookup(callable $callback)
    {
        $this->late_lookup_callback = $callback;
        return $this;
    }

    /**
     * Backward compatibility wrapper for generate()
     */
    public function make($json = TRUE)
    {
        return $this->generate(NULL, $json);
    }

    /**
     * Generate the final DataTables Response JSON.
     * 
     * @param callable|null $late_lookup_callback (Deprecated: Use withLateLookup instead)
     * @param bool $json Whether to print JSON and exit.
     */
    public function generate($late_lookup_callback = NULL, $json = TRUE)
    {
        if ($late_lookup_callback !== NULL) {
            $this->late_lookup_callback = $late_lookup_callback;
        }

        $db = $this->CI->db;

        // Bypass everything if manual data is supplied
        if ($this->manual_data !== NULL) {
            return $this->_handle_manual_data($json);
        }

        // ==========================================
        // PHASE 1: COUNT TOTAL
        // ==========================================
        if ($this->manual_recordsTotal !== NULL) {
            $recordsTotal = $this->manual_recordsTotal;
        } else {
            $temp_db = clone $db;
            $this->apply_query($temp_db);
            $recordsTotal = $temp_db->count_all_results('', FALSE);
        }
        
        // ==========================================
        // PHASE 2: COUNT FILTERED (SEARCH)
        // ==========================================
        $this->apply_query($db);
        $this->apply_search($db);
        
        $search_value = isset($_POST['search']['value']) ? $_POST['search']['value'] : '';
        if ($this->manual_recordsFiltered !== NULL) {
            $recordsFiltered = $this->manual_recordsFiltered;
        } else if ($search_value == '') {
            $recordsFiltered = $recordsTotal;
        } else {
            $temp_db_search = clone $db;
            $recordsFiltered = $temp_db_search->count_all_results('', FALSE);
        }
        
        // ==========================================
        // PHASE 3: APPLY ORDERING & PAGINATION
        // ==========================================
        $this->apply_order($db);
        
        if (isset($_POST['length']) && $_POST['length'] != -1) {
            $start = isset($_POST['start']) ? (int)$_POST['start'] : 0;
            $length = (int)$_POST['length'];
            $db->limit($length, $start);
        }
        
        // ==========================================
        // PHASE 4: EXECUTE QUERY (STANDARD OR SPLIT)
        // ==========================================
        if ($this->late_lookup_callback !== NULL) {
            $result = $this->_execute_split_query($db);
        } else {
            $result = $db->get()->result();
        }

        // Apply final processors
        if ($this->result_processor !== NULL) {
            $processor = $this->result_processor;
            $result = $processor($result);
        }
        
        return $this->_format_output($result, $recordsTotal, $recordsFiltered, $json);
    }

    /**
     * Executes the High-Performance Split-Query (Late Row Lookup) Architecture
     */
    protected function _execute_split_query($db)
    {
        // 1. Fetch exactly ONLY the Primary IDs for the paginated window
        $db->select($this->table ? $this->table . '.' . $this->primary_key : $this->primary_key);
        $id_results = $db->get()->result_array();
        
        if (empty($id_results)) {
            return [];
        }
        
        // Extract array of IDs
        $ids = [];
        foreach ($id_results as $row) {
            $ids[] = reset($row);
        }
        
        // 2. Reset query builder to scrub old filters/limits
        $db->reset_query();
        
        // Re-assign the base table
        if ($this->table) {
            $db->from($this->table);
        }
        
        // Fetch strictly the matched IDs
        $db->where_in($this->table ? $this->table . '.' . $this->primary_key : $this->primary_key, $ids);
        
        // RE-APPLY ORDERING: since WHERE IN scrambles order, we must tell DB to retain order.
        // We re-apply the order clause here.
        $this->apply_order($db);
        
        // Execute Developer's Heavy JOINs
        call_user_func($this->late_lookup_callback, $db, $ids);
        
        return $db->get()->result();
    }

    /**
     * Handles output when static data is supplied
     */
    protected function _handle_manual_data($json)
    {
        $result = $this->manual_data;
        $recordsTotal = $this->manual_recordsTotal !== NULL ? $this->manual_recordsTotal : count($result);
        
        if ($this->manual_recordsFiltered === NULL) {
            $recordsFiltered = count($result);
            if (isset($_POST['length']) && $_POST['length'] != -1) {
                $start = isset($_POST['start']) ? (int)$_POST['start'] : 0;
                $length = (int)$_POST['length'];
                $result = array_slice($result, $start, $length);
            }
        } else {
            $recordsFiltered = $this->manual_recordsFiltered;
        }

        if ($this->result_processor !== NULL) {
            $processor = $this->result_processor;
            $result = $processor($result);
        }

        return $this->_format_output($result, $recordsTotal, $recordsFiltered, $json);
    }

    /**
     * Core configuration compiler
     */
    protected function apply_query($db)
    {
        $db->select($this->select);
        if ($this->table) {
            $db->from($this->table);
        }
        
        foreach ($this->joins as $j) {
            $db->join($j['table'], $j['cond'], $j['type']);
        }
        
        foreach ($this->where as $w) {
            $db->where($w['key'], $w['value'], $w['escape']);
        }

        foreach ($this->where_in as $win) {
            $db->where_in($win['key'], $win['values']);
        }

        foreach ($this->group_by as $g) {
            $db->group_by($g);
        }
    }

    /**
     * Applies search filters across designated columns
     */
    protected function apply_search($db)
    {
        $search_value = isset($_POST['search']['value']) ? $_POST['search']['value'] : '';
        if ($search_value != '' && !empty($this->column_search)) {
            $db->group_start();
            
            if ($this->use_fulltext) {
                // MySQL FULLTEXT SEARCH MODE
                $safe_search = $db->escape_str($search_value);
                
                // If the user types multiple words, we can format them for Boolean Mode (e.g., +word1 +word2*)
                // But for safety and generic use, we pass it raw if they use Natural Language, 
                // or append a wildcard for Boolean Mode if it's a single word without operators.
                if (strpos($this->fulltext_mode, 'BOOLEAN') !== false && !preg_match('/[+\-<>()~*\"@]+/', $safe_search)) {
                    $terms = explode(' ', $safe_search);
                    $formatted = '';
                    foreach ($terms as $term) {
                        if (trim($term) !== '') {
                            $formatted .= '+' . trim($term) . '* ';
                        }
                    }
                    $safe_search = trim($formatted);
                }

                // Group columns by table prefix (e.g. 'table1.col1', 'table2.col2') 
                // because MATCH(col1, col2) must be on the same table.
                $tables = [];
                foreach ($this->column_search as $item) {
                    $parts = explode('.', $item);
                    if (count($parts) > 1) {
                        $tables[$parts[0]][] = $parts[1];
                    } else {
                        $tables[''][] = $item;
                    }
                }

                $first = true;
                foreach ($tables as $table_prefix => $cols) {
                    // Reconstruct full column names
                    $full_cols = [];
                    foreach ($cols as $c) {
                        $full_cols[] = ($table_prefix !== '') ? $table_prefix . '.' . $c : $c;
                    }
                    
                    $col_string = implode(', ', $full_cols);
                    $match_query = "MATCH($col_string) AGAINST('$safe_search' IN {$this->fulltext_mode})";
                    
                    if ($first) {
                        $db->where($match_query, NULL, FALSE);
                        $first = false;
                    } else {
                        $db->or_where($match_query, NULL, FALSE);
                    }
                }
            } else {
                // STANDARD LIKE '%...%' MODE
                $i = 0;
                foreach ($this->column_search as $item) {
                    if ($i === 0) {
                        $db->like($item, $search_value);
                    } else {
                        $db->or_like($item, $search_value);
                    }
                    $i++;
                }
            }
            
            $db->group_end();
        }
    }

    /**
     * Applies correct column sorting based on DataTables POST request
     */
    protected function apply_order($db)
    {
        if (isset($_POST['order']) && !empty($this->column_order)) {
            $column_idx = $_POST['order']['0']['column'];
            $dir = $_POST['order']['0']['dir'];
            
            if (isset($this->column_order[$column_idx])) {
                $db->order_by($this->column_order[$column_idx], $dir);
            }
        } else if (!empty($this->order)) {
            $order = $this->order;
            $db->order_by(key($order), $order[key($order)]);
        }
    }

    /**
     * Generates Final JSON payload or Array
     */
    protected function _format_output($result, $recordsTotal, $recordsFiltered, $json = TRUE)
    {
        $data = [];
        $no = isset($_POST['start']) ? (int)$_POST['start'] : 0;
        
        foreach ($result as $row) {
            $no++;
            $item = (array) $row;
            
            // Apply editColumn callbacks
            foreach ($this->edit_columns as $col => $callback) {
                if (array_key_exists($col, $item)) {
                    $item[$col] = $callback($row);
                }
            }
            
            // Apply addColumn callbacks
            foreach ($this->add_columns as $col => $callback) {
                $item[$col] = $callback($row);
            }
            
            $data[] = $item;
        }

        $output = [
            "draw" => isset($_POST['draw']) ? intval($_POST['draw']) : 0,
            "recordsTotal" => $recordsTotal,
            "recordsFiltered" => $recordsFiltered,
            "data" => $data,
        ];

        if ($json) {
            $json_str = json_encode($output);
            $this->CI->output
                ->set_content_type('application/json')
                ->set_header('Content-Length: ' . strlen($json_str))
                ->set_output($json_str);
            return;
        }

        return $output;
    }

    /**
     * Flushes all states for fresh queries
     */
    protected function reset()
    {
        $this->table = NULL;
        $this->primary_key = 'id';
        $this->select = '*';
        $this->where = [];
        $this->where_in = [];
        $this->joins = [];
        $this->group_by = [];
        $this->column_order = [];
        $this->column_search = [];
        $this->order = [];
        $this->add_columns = [];
        $this->edit_columns = [];
        $this->manual_recordsTotal = NULL;
        $this->manual_recordsFiltered = NULL;
        $this->manual_data = NULL;
        $this->use_fulltext = FALSE;
        $this->fulltext_mode = 'BOOLEAN MODE';
        $this->result_processor = NULL;
        $this->late_lookup_callback = NULL;
    }
}
