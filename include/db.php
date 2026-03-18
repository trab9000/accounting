<?php
/*
 $Rev: 249 $ | $LastChangedBy: brieb $
 $LastChangedDate: 2007-07-02 15:50:36 -0600 (Mon, 02 Jul 2007) $
 +-------------------------------------------------------------------------+
 | Copyright (c) 2004 - 2007, Kreotek LLC                                  |
 | All rights reserved.                                                    |
 +-------------------------------------------------------------------------+
 |                                                                         |
 | Redistribution and use in source and binary forms, with or without      |
 | modification, are permitted provided that the following conditions are  |
 | met:                                                                    |
 |                                                                         |
 | - Redistributions of source code must retain the above copyright        |
 |   notice, this list of conditions and the following disclaimer.         |
 |                                                                         |
 | - Redistributions in binary form must reproduce the above copyright     |
 |   notice, this list of conditions and the following disclaimer in the   |
 |   documentation and/or other materials provided with the distribution.  |
 |                                                                         |
 | - Neither the name of Kreotek LLC nor the names of its contributore may |
 |   be used to endorse or promote products derived from this software     |
 |   without specific prior written permission.                            |
 |                                                                         |
 | THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS     |
 | "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT       |
 | LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A |
 | PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT      |
 | OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,   |
 | SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT        |
 | LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,   |
 | DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY   |
 | THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT     |
 | (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE   |
 | OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.    |
 |                                                                         |
 +-------------------------------------------------------------------------+
*/

class db{
		//we may want to do more than connect via mysql;
		var $type="mysql";

		// mysql vars;
		var $db_link;
		var $hostname;
		var $schema;
		var $dbuser;
		var $dbpass;
		var $pconnect=true;

		var $showError=false;
		var $logError=true;
		var $stopOnError=true;
		var $errorFormat="xhtml";

		var $error = NULL;

		function __construct($connect = true, $hostname = NULL, $schema = NULL, $user = NULL, $pass = NULL, $pconnect = NULL, $type = "mysql"){

			if($type!="mysql")
			$this->type=$type;

			switch($this->type){
				default:
				case "mysql":
					if(defined("MYSQL_SERVER"))
						$this->hostname = MYSQL_SERVER;
					if($hostname!=NULL)
						$this->hostname = $hostname;

					if(defined("MYSQL_DATABASE"))
						$this->schema = MYSQL_DATABASE;
					if($schema!=NULL)
						$this->schema = $schema;

					if(defined("MYSQL_USER"))
						$this->dbuser = MYSQL_USER;
					if($schema!=NULL)
						$this->dbuser = $user;

					if(defined("MYSQL_USERPASS"))
						$this->dbpass = MYSQL_USERPASS;
					if($schema!=NULL)
						$this->dbpass = $pass;

					if(defined("MYSQL_PCONNECT"))
						$this->pconnect = MYSQL_PCONNECT;
					if($pconnect!=NULL)
						$this->pconnect = $pconnect;
				break;
			}

			if($connect){
				if($this->connect()){
					if($this->selectSchema())
						return;
					else
						return;
				}
			}
		}//end function


		function connect(){
			// This functions connects to the database.  It uses pconnect if the variable is set;
			$host = $this->pconnect ? "p:".$this->hostname : $this->hostname;

			$this->db_link = @mysqli_connect($host, $this->dbuser, $this->dbpass);

			if(!$this->db_link){
				$error = new appError(-400,"Could not connect to database server.\n\n".mysqli_connect_error(),"",$this->showError,$this->stopOnError,false,$this->errorFormat);
				return false;
			} else
				return $this->db_link;
		}


		function selectSchema($schema=NULL){
			if($schema!=NULL)
				$this->schema=$schema;

			if(!@mysqli_select_db($this->db_link, $this->schema)){
				$error = new appError(-410,"Could not open schema ".$this->schema,"",$this->showError,$this->stopOnError,false,$this->errorFormat);
				return false;
			} else
				return true;
		}


		function query($sqlstatement){
			switch($this->type){
				case "mysql":
					if(!isset($this->db_link))
						if(!$this->dataB()) die($this->error);
					$queryresult = @mysqli_query($this->db_link, $sqlstatement);
					if(!$queryresult){
						$this->error = mysqli_error($this->db_link);
						$error = new appError(-420, mysqli_error($this->db_link)."\n\nStatement: ".$sqlstatement,"",$this->showError,$this->stopOnError,$this->logError,$this->errorFormat);
						return false;
					}
				break;
			}//end case

			$this->error=NULL;
			return $queryresult;
		}//end function


		function setEncoding($encoding = "utf8"){
			switch($this->type){
				case "mysql":
					@mysqli_query($this->db_link, "SET NAMES ".$encoding);
					break;
			}//endswitch
		}//end method


		function numRows($queryresult){
			switch($this->type){
				case "mysql":
					$numrows = @mysqli_num_rows($queryresult);
					if(!is_numeric($numrows)){
						$error= new appError(-430,"","Could Not Retrieve Rows.","",$this->showError,$this->stopOnError,$this->logError,$this->errorFormat);
						return false;
					}
				break;
			}//end case
			$this->error=NULL;
			return $numrows;
		}//end function


		function fetchArray($queryresult){
			//Fetches associative array of current row
			switch($this->type){
				case "mysql":
					$row = @mysqli_fetch_assoc($queryresult);
				break;
			}//end case
			return $row;
		}//end function


		function seek($queryresult,$rownum){
			switch($this->type){
				case "mysql":
					$thereturn = @mysqli_data_seek($queryresult, $rownum);
				break;
			}//end case
			return $thereturn;
		}//end function


		function numFields($queryresult){
			switch($this->type){
				case "mysql":
					$thereturn = @mysqli_num_fields($queryresult);
				break;
			}//end case
			return $thereturn;
		}//end function


		function fieldTable($queryresult,$offset){
			switch($this->type){
				case "mysql":
					$field = @mysqli_fetch_field_direct($queryresult, $offset);
					$thereturn = $field ? $field->table : false;
				break;
			}//end case
			return $thereturn;
		}//end function


		function fieldName($queryresult,$offset){
			switch($this->type){
				case "mysql":
					$field = @mysqli_fetch_field_direct($queryresult, $offset);
					$thereturn = $field ? $field->name : false;
				break;
			}//end case
			return $thereturn;
		}//end function


		function tableInfo($tablename){
			//this function returns a multi-dimensional array describing the fields in a given table
			$thereturn = false;
			switch($this->type){
				case "mysql":
					$queryresult = @mysqli_query($this->db_link, "SHOW COLUMNS FROM `".$tablename."`");
					if($queryresult){
						$thereturn = array();
						while($row = mysqli_fetch_assoc($queryresult)){
							$name = $row['Field'];
							$thereturn[$name]["type"]   = $this->_normalizeFieldType($row['Type']);
							$thereturn[$name]["length"]  = $this->_parseFieldLength($row['Type']);
							$thereturn[$name]["flags"]   = $this->_parseFieldFlags($row);
						}
					}
				break;
			}//end case
			return $thereturn;
		}


		function insertId(){
			$thereturn = false;
			switch($this->type){
				case "mysql":
					$thereturn = @mysqli_insert_id($this->db_link);
				break;
			}
			return $thereturn;
		}

		function affectedRows(){
			$thereturn = false;
			switch($this->type){
				case "mysql":
					$thereturn = @mysqli_affected_rows($this->db_link);
				break;
			}
			return $thereturn;
		}

		function escape($value){
			return mysqli_real_escape_string($this->db_link, $value ?? '');
		}

		// Normalize raw MySQL column types to the simplified names the old mysql_field_type() returned
		function _normalizeFieldType($typestr){
			$base = strtolower(preg_replace('/\\(.*/', '', $typestr));
			$base = trim(preg_replace('/\s+unsigned$/', '', $base));
			switch($base){
				case 'tinyblob':
				case 'blob':
				case 'mediumblob':
				case 'longblob':
					return 'blob';
				case 'char':
				case 'varchar':
				case 'tinytext':
				case 'text':
				case 'mediumtext':
				case 'longtext':
				case 'enum':
				case 'set':
					return 'string';
				case 'tinyint':
				case 'smallint':
				case 'mediumint':
				case 'int':
				case 'bigint':
					return 'int';
				case 'float':
				case 'double':
				case 'decimal':
				case 'numeric':
					return 'real';
				case 'date':
					return 'date';
				case 'time':
					return 'time';
				case 'datetime':
				case 'timestamp':
					return 'datetime';
				default:
					return $base;
			}
		}


		// helper: extract length from type string e.g. "varchar(255)" -> 255
		function _parseFieldLength($typestr){
			if(preg_match('/\((\d+)\)/', $typestr, $m))
				return (int)$m[1];
			return 0;
		}

		// helper: reconstruct a flags string from SHOW COLUMNS row
		function _parseFieldFlags($row){
			$flags = array();
			if($row['Null'] === 'NO')       $flags[] = 'not_null';
			if($row['Key']  === 'PRI')       $flags[] = 'primary_key';
			if($row['Key']  === 'UNI')       $flags[] = 'unique_key';
			if($row['Extra'] === 'auto_increment') $flags[] = 'auto_increment';
			return implode(' ', $flags);
		}

}//end db class
?>
