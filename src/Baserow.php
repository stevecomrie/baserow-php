<?php

namespace Scomrie\Baserow;

/**
 * PHP Client for the Baserow.io API
 *
 * @author Steve Comrie
 * @version 0.0.1
 */


class Baserow
{
    private $_key;
    private $_apiurl;
    private $_debug;
    private $_errors             = [];
    protected $tableNamesToIds   = [];
    protected $tableFieldsByName = [];
    protected $tableFieldsById   = [];

	public function __construct($config)
    {
        if (is_array($config)) {

            $config = array_merge( [
                'api_key'   => '',
                'api_url'   => 'https://api.baserow.io/api/database/rows/table',
                'table_map' => [],
                'debug'     => false,
            ], $config );

            $this->setKey($config['api_key']);
            $this->setApiUrl($config['api_url']);
            $this->setTableMap($config['table_map']);
            $this->setDebug($config['debug']);

        } else {
            echo 'Error: __construct() - Configuration data is missing';
        }
    }

    public function setKey($key)
    {
        if( empty($key) ) {
            echo 'Error: setKey() - API key is missing';
        }
        
        $this->_key = $key;
    }

    public function getKey()
    {
        return $this->_key;
    }

    public function setDebug($debug)
    {
        $this->_debug = $debug;
    }

    public function setApiUrl($url)
    {
        $this->_apiurl = rtrim($url, '/');
    }

    public function getApiUrl($request)
    {
	    $request = str_replace( ' ', '%20', $request );

        // crude proof of concept hack to allow duplicate query param keys with multiple values
        // ideal solution: https://stackoverflow.com/questions/17161114/php-http-build-query-with-two-array-keys-that-are-same
        $request = preg_replace( '/\%5B\d+\%5D\=/', '=', $request );

        return $this->_apiurl.'/'.$request;
    }

    public function setTableMap($map)
    {
        if( empty($map) ) {
            return;
        }

        foreach ($map as $tblName => $tblDetails) {
            $this->tableNamesToIds[$tblName] = $tblDetails[0];

            foreach( $tblDetails[1] as $fieldName => $fieldId ) {
                $this->tableFieldsByName[$tblDetails[0]][$fieldName] = $fieldId;
                $this->tableFieldsById[$tblDetails[0]][$fieldId] = $fieldName;
            }

            // sort by length of field name to prevent mapping errors when performing
            // bulk search & replace on fields that start with similar strings
            uksort(
                $this->tableFieldsByName[$tblDetails[0]],
                function($a, $b) { return strlen($b) - strlen($a); }
            );
        }
    }


    public function error()
    {
        return array_pop($this->_errors);
    }


    function get($table,$rowID)
    {
        $table  = $this->_tableID($table);
        $request = new Request( $this, "$table/$rowID", [], false );

        $response = $request->getResponse();

        return $response->error
            ? $this->_handleError($response)
            : $this->_mapRecordToNames($response->parsedContent(),$table);
	}


    function list($table,$params=[])
    {
        $table  = $this->_tableID($table);
        $params = $this->_mapParams($params, $table);

        $page = empty( $params['page'] ) ? 1   : $params['page'];
        $size = empty( $params['size'] ) ? 100 : $params['size'];

        $request  = new Request( $this, $table, $params, false );
        $response = $request->getResponse();

        $records = [];
        if( !empty($response->results) ) {
            foreach( $response->results AS $row ) {
                $records[] = $this->_mapRecordToNames( $row, $table );
            }
        }

        return [
            'records'    => $records,
            'page'       => $page,
            'totalPages' => ceil( $response->count / $size ),
            'count'      => $response->count,
            'next'       => !empty( $response->next ),
            'previous'   => !empty( $response->previous ),
        ];
	}


    function all($table,$params=[])
    {
        $table  = $this->_tableID($table);
        $params = $this->_mapParams($params, $table);

        $params['size'] = 200; // override the default to get more results per request
        $params['page'] = 0;

        $request = new Request( $this, $table, $params, false );

        $records = [];
        do {
            $request->page = ++$params['page'];
            $response = $request->getResponse();

            if( !empty($response->results) ) {
                foreach( $response->results AS $row ) {
                    $records[] = $this->_mapRecordToNames( $row, $table );
                }
            }

        } while ( !empty( $response->next ) );

        return [
            'records'   => $records,
            'count'     => $response->count,
        ];
	}


    function create($table,$fields=[])
	{
        if( empty( $fields ) ) {
            return $this->_handleError([
                'error'  => "ERROR_MISSING_SAVE_DATA",
                'detail' => "No column data provided for create action"
            ]);
        }

        $table  = $this->_tableID($table);
        $fields = $this->_mapNamesToFields($fields,$table);

		$request = new Request( $this, $table, $fields, true );

        $response = $request->getResponse();

        return $response->error
            ? $this->_handleError($response)
            : $this->_mapRecordToNames($response->parsedContent(),$table);
	}


	function update($table, $fields=[], $rowID=null)
	{
        if( empty($fields) ) {
            return $this->_handleError([
                'error'  => "ERROR_MISSING_UPDATE_DATA",
                'detail' => "No column data provided for update action"
            ]);
        }

        $table  = $this->_tableID($table);
        $fields = $this->_mapNamesToFields($fields, $table);

        if( $rowID && !empty( $fields['id'] ) ) {
            $rowID = $fields['id'];
            unset($fields['id']);
        }

        if( empty( $rowID ) ) {
            return $this->_handleError([
                'error'  => "ERROR_MISSING_ROW_ID",
                'detail' => "No row ID provided for update action"
            ]);
        }

		$request = new Request( $this, "$table/$rowID", $fields, 'patch' );

        $response = $request->getResponse();

        return $response->error
            ? $this->_handleError($response)
            : $this->_mapRecordToNames($response->parsedContent(),$table);
	}


	function delete($table,$rowID=null)
    {
        if( empty( $rowID ) ) {
            return $this->_handleError([
                'error'  => "ERROR_MISSING_ROW_ID",
                'detail' => "No row ID provided for delete action"
            ]);
        }

        $table = $this->_tableID($table);
        $request = new Request( $this, "$table/$rowID", [], 'delete' );
        $response = $request->getResponse();

        return $response->error
            ? $this->_handleError($response)
            : $rowID;

        // check for an error
        if( $response->error ) {
            $this->_errors[] = [ 'error' => $response->error,  'detail' => $response->detail ];
            return false;
        }

        // success
        return $rowId;
    }
    

    private function _mapRecordToNames($record, $tableID)
    {
        if( empty( $this->tableFieldsById[$tableID] ) ) {
            return $record; // don't do anything if we don't have a map for this table
        }

        $record = (array) $record;
        $x = [];
        if( !empty($record['id']) ) {
            $x['id'] = $record['id'];
        } 
        foreach ( $this->tableFieldsById[$tableID] as $key => $name) {
            if( array_key_exists( $key, $record ) ) {
                $x[$name] = $record[$key];
            }
        }

        return (object) $x;
    }


    private function _mapParams($params, $tableID)
    {
        if( empty($this->tableFieldsByName[$tableID]) ) {
            return $params; // don't do anything if we don't have a map for this table
        }

        $mappedParams = [];
        foreach( $params AS $key => $value ) {
            
            if( substr($key, 0, 8) == 'filter__' ) {
                $filter = explode("__", $key);
                $filter[1] = str_replace(
                    array_keys($this->tableFieldsByName[$tableID]),
                    array_values($this->tableFieldsByName[$tableID]),
                    $filter[1]
                );
                $key = implode("__", $filter);
            }
            
            if( in_array( $key, ['order_by', 'include', 'exclude'] ) ) {
                $value = str_replace(
                    array_keys($this->tableFieldsByName[$tableID]),
                    array_values($this->tableFieldsByName[$tableID]),
                    $value
                );
            }

            $mappedParams[ $key ] = $value;
        }
        return $mappedParams;
    }


    private function _mapNamesToFields($fields, $tableID)
    {
        if( empty($this->tableFieldsByName[$tableID]) ) {
            return $fields; // don't do anything if we don't have a map for this table
        }

        $mappedFields = [];
        foreach( $fields AS $key => $value ) {
            $newKey = str_replace(
                array_keys($this->tableFieldsByName[$tableID]),
                array_values($this->tableFieldsByName[$tableID]),
                $key
            );
            $mappedFields[ $newKey ] = $value;
        }
        return $mappedFields;
    }


    private function _tableID($tableName)
    {
        return empty( $this->tableNamesToIds[$tableName] )
            ? $tableName
            : $this->tableNamesToIds[$tableName];
    }


    private function _handleError( $error )
    {
        $this->_errors[] = is_array($error)
            ? $error
            : [ 'error' => $error->error,  'detail' => $error->detail ];

        if( $this->_debug ) {
            print_r( $this->error() );
            exit;
        }

        return false;
    }
}