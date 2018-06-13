<?php
/**
 * @purpose 
 * 
 * This class handles all the requests and responses for the service.
 * It takes all POST request and insert the record into the storage.txt  file
 *
 */
class Storage
{

	protected $storageFile = 'storage.txt';
    
    /**
     * Return a single record from the storage file storage.txt.
     *
     * @param string $name
     * @return array $data_arry
     */
	public function show(string $name): array
    {
		$fileHandler = $this->getFileHandler('r');
		while(!feof($fileHandler)){
			$line = fgets($fileHandler);
			$arry = explode('|', $line);
			if(strtolower($arry[0]) == strtolower($name)){
				$item = $line; 
			}
			
		}
		if(!isset($item)){
			throw new Exception('item named ('.$name.') was not found!', 404);
		}
		$data  = explode('|', $item);
		$name= $data[0];
		$link = chop($data[1]); //removes the "\n" at the end of the link
		$data_arry = [ 'name'=>$name, 'link'=>$link ];
		$this->closeFile($fileHandler);
		return $data_arry;
	}

    /**
     * Return all the records from the storage file storage.txt
     *
     * @param string $name
     * @return array $data_arry
     */
	public function showAll(): array
    {
		$fileHandler = $this->getFileHandler('r');
		$data_arry = [];
		while(!feof($fileHandler)){
			$line = fgets($fileHandler);
			$arry = explode('|', $line);
			if($arry[0]=="" || $arry[1]==null){ //To preempt possible empty lines at the end of the file.
				break; 
			}
			$name = $arry[0];
			$link = chop($arry[1]);
			array_push($data_arry, ['name'=>$name, 'link'=>$link]);
		}
		$this->closeFile($fileHandler);
		return $data_arry;
	}

    /**
     * Adds a single record to the storage file storage.txt
     *
     * @param array $data
     * @return array 
     */
	public function create(array $data): array
    {
		$fileHandler = $this->getFileHandler('a');
		$written = fwrite($fileHandler, $data['name'].'|'.$data['link']."\n");
		$this->closeFile($fileHandler);
		if($written===false){
			throw new Exception('Attempt to write to storage file failed', 501);
		}
		return ['name'=>$data['name'], 'link'=>$data['link']];
	}
	
   /**
    *  Updates a line in the storage.txt for a PUT request
    * The records in the storage.txt file is read and written to a new file storage.tmp
    * And if statement is used to determine the record of instrest. The record of interest is updated when it is identified 	
    * If a line is overwritten, the storage.tmp file is renamed storage.txt and afterwards unlinked at the end of the loop.
    *
    * @param array $data
    * @return array $updated_data
	*/
	public function update(array $data): array
    {
		$name = $data['name'];
		$new_link = $data['link'];
		$fileHandler = $this->getFileHandler('r');
		$tmpHandler = fopen('storage.tmp', 'w');
		$overWritten = false;
		$updated_data = null;
		while(!feof($fileHandler)){
			$line = fgets($fileHandler);
			$arry = explode('|', $line);
			if(strtolower($arry[0])==strtolower($name)){   
				$line  = $name.'|'.$new_link."\n"; //Line to overwride the relevant line
				$updated_data = ['name'=>$name, 'link'=>$new_link];
				$overWritten = true;
			}
			fwrite($tmpHandler, $line);
		}
        
		fclose($tmpHandler);
		$this->closeFile($fileHandler);

		if($overWritten==true){
			rename('storage.tmp', 'storage.txt');
		}else{
			unlink('storage.tmp');
			throw new Exception('item to modify does not exit', 304);
		}
		return  $updated_data;
        
	}
	/**
     * Delete a specified record in the storage.txt file for a DELETE request
     *
     * @param string $name
     * @return null
     */
	public function delete(string $name)
    {
		$fileHandler = $this->getFileHandler('r');
		$tmpHandler = fopen('storage.tmp', 'w');
		$deleted = false;
		while(!feof($fileHandler)){
			$line = fgets($fileHandler);
			$arry = explode('|', $line);
			if(strtolower($arry[0])==strtolower($name)){
				$deleted = true;	
				continue; //For DELETE operation
			}
			fwrite($tmpHandler, $line);
		}
		fclose($tmpHandler);
		$this->closeFile($fileHandler);

		if($deleted==true){
			rename('storage.tmp', 'storage.txt');
		}else{
			unlink('storage.tmp');
		}
		return null;
	}

    /**
     * @param string $action
     * @return object 
     */
	public function getFileHandler($action):object
    {
		return  fopen($this->storageFile, $action);
	}

    /**
     * @param object $handler
     * @return void
     */
	public function closeFile($handler)
    {
		fclose($handler);
	}

	/* *
     * This method parses the string returned by the $_SERVER['HTTP_ACCEPT'] supper global.
     * It creates an array of all the acceptable types defined in the request header Accept.
	 * The array is sorted according to the q values of the Types with the type having the highest q value coming first
     *
     * @param string $_SERVER['HTTP_ACCEPT']
     * @return array $types
     */
        
	public static function getAcceptHeaderArray(string $http_accept): array
    {
		$types= explode(',', strtolower($http_accept));
		$typesWithQ = [];
		for($x=0; $x<count($types); $x++){
			$qVal = 1;
			if(stristr($types[$x], 'q')){
				$type_str = $types[$x];
				$types[$x] = trim(stristr($types[$x], ';', true));
				
				
				$qPosition = stripos($type_str, 'q');
				$qVal = (float) substr($type_str, $qPosition+2, 3);
				// $type = $types[$x];
			}
			array_push($typesWithQ, ['type'=>$types[$x], 'q'=>$qVal]);
		}
		usort($typesWithQ, function($a, $b){
			if($b['q']>$a['q']){
				return 1;
			}else if($b['q']<$a['q']){
				return -1;
			}else if($b['q']==$a['q']){
				return 1;
			}
		});
		for($y=0; $y<count($types); $y++){
			$types[$y] = $typesWithQ[$y]['type'];
		}
		return $types;
	}

    /**
     * This method takes the array returned by the getAcceptHeaderArray() method
     * It runs through the array element and returns the first 'Accept type' which matches any of the defined response type of the services
     * This way the Accept type having the highest q value in the service is selected
     *
     * @param array $types
     * @return string $preferred_type
     */
    
	public static function getPreferredType(array $types): string
    {
		foreach($types as $type){
			switch($type){
				case 'application/xml':
					$preferred_type = 'application/xml';
					break 2; 
				case 'application/json':
					$preferred_type = 'application/json';
					break 2;
				case 'text/html':
					$preferred_type = 'text/html';	
					break 2;
				default:
					$preferred_type = 'text/plain';	
			}
			
		}
		return $preferred_type;
	}
    /**
     * This method format the response to be returned to the client
     * It handles four content types including application/json, application/xml, text/html and text/plain which is the default
     *
     * @param array $data
     * @param string $type
     * @return string $formatedData
     */
	public static function getResponseBody($data, $type): string
    {
		switch($type){
			case 'application/json':
				$formatedData = json_encode($data, JSON_UNESCAPED_SLASHES);
				break;
			case 'application/xml':
				if(count($data)>2){
					$xml = new SimpleXMLElement('<items />');
					foreach($data as $info){
						$item = $xml->addChild('item');
						$item->addChild('name', $info['name']);
						$item->addChild('link', $info['link']);
					}
					$formatedData = $xml->asXML();

				}else{
					$xml = new SimpleXMLElement('<item />');
					$xml->addChild('name', $data['name']);
					$xml->addChild('link', $data['link']);
					$formatedData = $xml->asXML();
				}
				break;
			case 'text/html':
				if(count($data)>2){
					$formatedData = "<html><body> ";
					foreach($data as $info){
						$formatedData.="<b>Name:</b>".$info['name']." <b>Link:</b> ".$info['link']."<br />";
					}
					$formatedData .="</body></html>";
				}else{
					$formatedData = "<b>Name:</b>".$data['name']." <b>Link:</b> ".$data['link']."<br />";
				}
				
				break;	
			default:
				if(count($data)>2){
					$formatedData = '';
					foreach($data as $info){
						$formatedData .= 'Name: '.$info['name'].' Link: '.$info['link'].'\n';	
					}
				}else{
					$formatedData = 'Name: '.$data['name'].' Link: '.$data['link'].'\n';	
				}	
		}
		return $formatedData;
	}
    /**
     * This method parses the xml input from the client
     *
     * @param 'xml string' $xmlStr
     * @return array $item 
     */
    public static function parseXML($xmlStr): array
    {
        /*Basic parsing using simplexml_load_string */ 
        $xmlObj = simplexml_load_string($xmlStr);//SimpleXMLElement Object
       if(isset($xmlObj->link)){
           $item = ['name'=>$xmlObj->name, 'link'=>$xmlObj->link];   
       }else{
           $item = ['name'=>$xmlObj->name, 'link'=>''];
       }
        
        return $item;
    }

}

