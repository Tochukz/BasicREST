<?php
/**
 *@author Tochukwu Nwachukwu truetochukz@gmail.com
 *@link http://tochukwu.xyz
 *
 */
require_once('storage.php');
$types = Storage::getAcceptHeaderArray($_SERVER['HTTP_ACCEPT']);
$type = Storage::getPreferredType($types);

set_exception_handler(function($e){
	$code  = $e->getCode()? : 400;
	$type = $GLOBALS['type'];
	switch($type){
		case 'application/json':
			$error_data = json_encode(['error'=>$e->getMessage()]);
			break;
		case 'application/xml':
			$xml = new SimpleXMLElement('<error />');
			$xml->addChild('message', $e->getMessage());
			$error_data = $xml->asXML();
			break;
		case 'text/html':
			$error_data = "<html><body><b>Error:</b> ".$e->getMessage()."</body></html>";
		default:
			$error_data = 'Error: '.$e->getMessage();
	}
	header('Content-Type: '.$type, null, $code);
	echo $error_data;
	exit;
});

$verb = strtoupper($_SERVER['REQUEST_METHOD']);

$storage = new Storage();
$data = null;
switch($verb){
	case 'GET':
		if(isset($_GET['item']) && !empty($_GET['item'])){
			if(strtolower($_GET['item'])=='all'){
                $data = $storage->showAll();
                $status = ($data)? 200:400;
			}else if(preg_match('/[a-zA-Z0-9]+/', $_GET['item'])){
               try{
                    $name = strtolower($_GET['item']);
                    $data = $storage->show($name);
                    $status = ($data)? 200:400;
                }catch(UnexpectedValueException $e){
                    throw new Exception($e->getMessage(), $e->getCode());
                }
            }
		}        

		if($data===null){
				throw new Exception('Item not found', 404);
		}
		break;

	case 'POST':
	case 'PUT':
        try{
            $content = strtolower($_SERVER['CONTENT_TYPE']);
            if($content=='application/json'){
                $param = json_decode(file_get_contents('php://input'), true);
            }else if($content=='application/xml'){
                $param = Storage::parseXML(file_get_contents('php://input'));
            }else{
                throw new Exception('Invalid content type');
            }
        }catch(Exception $e){
            throw new Exception($e->getMessage());
        }
        
		
		if(!$param){
			throw new Exception('Data missing or invalid');
		}
        
		if($verb=='PUT'){
			try{       
				$data = $storage->update($param);
				$status = ($data)? 204:400;
               
			}catch(Exception $e){
               throw new Exception($e->getMessage());
			}
		}else if($verb=='POST'){
			$data = $storage->create($param);
			$status = ($data)? 201:400;
		}
		header("Location: http://rest.dev/index.php?item=".$param['name'], null, $status);
		break;
        
	case 'DELETE':
        $name = null;
        try{
            $input = file_get_contents('php://input');
            $content = strtolower($_SERVER['CONTENT_TYPE']);
            if($content=='application/json'){
                $param = json_decode($input, true);
                $name = $param['name'];
            }else if($content=='application/xml'){
                $param = Storage::parseXML($input);
                $nameArry = (array) $param['name'];
                $name = $nameArry[0];
            }
        }catch(Exception $e){
             throw new Exception('DELETE request header and/or body may be invalid');
        }
		$data = $storage->delete($name); //This will hold a value of null.
		$status = ($data)? 404:200;
		break;
	default:
		throw new Exception('Invalid request', 405);
}


if($status == 204 ){
	http_response_code($status); 
}else{
	header('Content-Type: '.$type, null, $status);
}
if($data!==null){
    $data =$storage::getResponseBody($data, $type);
    echo ($data);
}




