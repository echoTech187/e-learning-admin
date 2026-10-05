<?php

namespace App\Libraries;

use CodeIgniter\Database\BaseBuilder;

class FluentDatatables
{
    protected $db;
    protected $table;
    protected $primary_key = 'id';
    
    protected $select = '*';
    protected $where = [];
    protected $where_in = [];
    protected $joins = [];
    protected $group_by = [];
    
    protected $column_order = [];
    protected $column_search = [];
    protected $order = [];
    
    protected $add_columns = [];
    protected $edit_columns = [];
    protected $result_processor = NULL;
    
    protected $manual_recordsTotal = NULL;
    protected $manual_recordsFiltered = NULL;
    protected $manual_data = NULL;
    protected $use_fulltext = FALSE;
    protected $fulltext_mode = 'BOOLEAN MODE';
    
    protected $late_lookup_callback = NULL;
    protected $query_callback = NULL;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    public function of($table)
    {
        $this->reset();
        $this->table = $table;
        return $this;
    }

    public function setPrimaryKey($key)
    {
        $this->primary_key = $key;
        return $this;
    }

    public function select($select)
    {
        $this->select = $select;
        return $this;
    }

    public function where($key, $value = NULL, $escape = null)
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->where[] = ['key' => $k, 'value' => $v, 'escape' => TRUE];
            }
        } else {
            $this->where[] = ['key' => $key, 'value' => $value, 'escape' => $escape ?? TRUE];
        }
        return $this;
    }

    public function where_in($key, $values)
    {
        $this->where_in[] = ['key' => $key, 'values' => $values];
        return $this;
    }

    public function join($table, $cond, $type = '')
    {
        $this->joins[] = ['table' => $table, 'cond' => $cond, 'type' => $type];
        return $this;
    }

    public function groupBy($column)
    {
        $this->group_by[] = $column;
        return $this;
    }

    public function set_column_order($columns)
    {
        $this->column_order = $columns;
        return $this;
    }

    public function set_orderable_columns($columns)
    {
        return $this->set_column_order($columns);
    }

    public function set_column_search($columns)
    {
        $this->column_search = $columns;
        return $this;
    }

    public function set_searchable_columns($columns)
    {
        return $this->set_column_search($columns);
    }

    public function enableFulltextSearch($mode = 'BOOLEAN MODE')
    {
        $this->use_fulltext = TRUE;
        $this->fulltext_mode = $mode;
        return $this;
    }

    public function set_default_order($order)
    {
        $this->order = $order;
        return $this;
    }

    public function addColumn($name, $callback)
    {
        $this->add_columns[$name] = $callback;
        return $this;
    }

    public function editColumn($name, $callback)
    {
        $this->edit_columns[$name] = $callback;
        return $this;
    }

    public function setRecordsTotal($total)
    {
        $this->manual_recordsTotal = $total;
        return $this;
    }

    public function setRecordsFiltered($count)
    {
        $this->manual_recordsFiltered = $count;
        return $this;
    }

    public function filter($callback)
    {
        $this->query_callback = $callback;
        return $this;
    }

    public function modifyResult($callback)
    {
        $this->result_processor = $callback;
        return $this;
    }

    public function setData($data)
    {
        $this->manual_data = $data;
        return $this;
    }

    public function withLateLookup(callable $callback)
    {
        $this->late_lookup_callback = $callback;
        return $this;
    }

    public function make($json = TRUE)
    {
        return $this->generate(NULL, $json);
    }

    public function generate($late_lookup_callback = NULL, $json = TRUE)
    {
        if ($late_lookup_callback !== NULL) {
            $this->late_lookup_callback = $late_lookup_callback;
        }

        $request = \Config\Services::request();
        $post = $request->getPost();
        $get = $request->getGet();
        $params = array_merge($get, $post);

        if ($this->manual_data !== NULL) {
            return $this->_handle_manual_data($json, $params);
        }

        if ($this->manual_recordsTotal !== NULL) {
            $recordsTotal = $this->manual_recordsTotal;
        } else {
            $temp_builder = $this->db->table($this->table);
            $this->apply_query($temp_builder);
            $recordsTotal = $temp_builder->countAllResults(FALSE);
        }
        
        $builder = $this->db->table($this->table);
        $this->apply_query($builder);
        $this->apply_search($builder, $params);
        
        $search_value = isset($params['search']['value']) ? $params['search']['value'] : '';
        if ($this->manual_recordsFiltered !== NULL) {
            $recordsFiltered = $this->manual_recordsFiltered;
        } else if ($search_value == '') {
            $recordsFiltered = $recordsTotal;
        } else {
            $temp_builder_search = clone $builder;
            $recordsFiltered = $temp_builder_search->countAllResults(FALSE);
        }
        
        $this->apply_order($builder, $params);
        
        if (isset($params['length']) && $params['length'] != -1) {
            $start = isset($params['start']) ? (int)$params['start'] : 0;
            $length = (int)$params['length'];
            $builder->limit($length, $start);
        }
        
        if ($this->late_lookup_callback !== NULL) {
            $result = $this->_execute_split_query($builder, $params);
        } else {
            $result = $builder->get()->getResult();
        }

        if ($this->result_processor !== NULL) {
            $processor = $this->result_processor;
            $result = $processor($result);
        }
        
        return $this->_format_output($result, $recordsTotal, $recordsFiltered, $json, $params);
    }

    protected function _execute_split_query($builder, $params)
    {
        $builder->select($this->table ? $this->table . '.' . $this->primary_key : $this->primary_key);
        $id_results = $builder->get()->getResultArray();
        
        if (empty($id_results)) {
            return [];
        }
        
        $ids = [];
        foreach ($id_results as $row) {
            $ids[] = reset($row);
        }
        
        $builder = $this->db->table($this->table);
        $builder->whereIn($this->table ? $this->table . '.' . $this->primary_key : $this->primary_key, $ids);
        
        $this->apply_order($builder, $params);
        
        call_user_func($this->late_lookup_callback, $builder, $ids);
        
        return $builder->get()->getResult();
    }

    protected function _handle_manual_data($json, $params)
    {
        $result = $this->manual_data;
        $recordsTotal = $this->manual_recordsTotal !== NULL ? $this->manual_recordsTotal : count($result);
        
        if ($this->manual_recordsFiltered === NULL) {
            $recordsFiltered = count($result);
            if (isset($params['length']) && $params['length'] != -1) {
                $start = isset($params['start']) ? (int)$params['start'] : 0;
                $length = (int)$params['length'];
                $result = array_slice($result, $start, $length);
            }
        } else {
            $recordsFiltered = $this->manual_recordsFiltered;
        }

        if ($this->result_processor !== NULL) {
            $processor = $this->result_processor;
            $result = $processor($result);
        }

        return $this->_format_output($result, $recordsTotal, $recordsFiltered, $json, $params);
    }

    protected function apply_query($builder)
    {
        $builder->select($this->select);
        
        foreach ($this->joins as $j) {
            $builder->join($j['table'], $j['cond'], $j['type']);
        }
        
        foreach ($this->where as $w) {
            if ($w['value'] === NULL && $w['escape'] === FALSE) {
                $builder->where($w['key'], null, false);
            } else {
                $builder->where($w['key'], $w['value'], $w['escape']);
            }
        }

        foreach ($this->where_in as $win) {
            $builder->whereIn($win['key'], $win['values']);
        }

        foreach ($this->group_by as $g) {
            $builder->groupBy($g);
        }

        if ($this->query_callback) {
            call_user_func($this->query_callback, $builder);
        }
    }

    protected function apply_search($builder, $params)
    {
        $search_value = isset($params['search']['value']) ? $params['search']['value'] : '';
        if ($search_value != '' && !empty($this->column_search)) {
            $builder->groupStart();
            
            if ($this->use_fulltext) {
                $safe_search = $this->db->escapeString($search_value);
                
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
                    $full_cols = [];
                    foreach ($cols as $c) {
                        $full_cols[] = ($table_prefix !== '') ? $table_prefix . '.' . $c : $c;
                    }
                    
                    $col_string = implode(', ', $full_cols);
                    $match_query = "MATCH($col_string) AGAINST('$safe_search' IN {$this->fulltext_mode})";
                    
                    if ($first) {
                        $builder->where($match_query, NULL, FALSE);
                        $first = false;
                    } else {
                        $builder->orWhere($match_query, NULL, FALSE);
                    }
                }
            } else {
                $i = 0;
                foreach ($this->column_search as $item) {
                    if ($i === 0) {
                        $builder->like($item, $search_value);
                    } else {
                        $builder->orLike($item, $search_value);
                    }
                    $i++;
                }
            }
            
            $builder->groupEnd();
        }
    }

    protected function apply_order($builder, $params)
    {
        if (isset($params['order']) && !empty($this->column_order)) {
            $column_idx = $params['order']['0']['column'];
            $dir = $params['order']['0']['dir'];
            
            if (isset($this->column_order[$column_idx])) {
                $builder->orderBy($this->column_order[$column_idx], $dir);
            }
        } else if (!empty($this->order)) {
            $order = $this->order;
            $builder->orderBy(key($order), $order[key($order)]);
        }
    }

    protected function _format_output($result, $recordsTotal, $recordsFiltered, $json, $params)
    {
        $data = [];
        $no = isset($params['start']) ? (int)$params['start'] : 0;
        
        foreach ($result as $row) {
            $no++;
            $item = (array) $row;
            
            foreach ($this->edit_columns as $col => $callback) {
                if (array_key_exists($col, $item)) {
                    $item[$col] = $callback($row);
                }
            }
            
            foreach ($this->add_columns as $col => $callback) {
                $item[$col] = $callback($row);
            }
            
            $data[] = $item;
        }

        $output = [
            "draw" => isset($params['draw']) ? intval($params['draw']) : 0,
            "recordsTotal" => $recordsTotal,
            "recordsFiltered" => $recordsFiltered,
            "data" => $data,
        ];

        if ($json) {
            $response = \Config\Services::response();
            return $response->setJSON($output);
        }

        return $output;
    }

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
        $this->query_callback = NULL;
    }
}