<?php

define('PROJECT_PATH', (empty($_SERVER['DOCUMENT_ROOT']) ? '.' : $_SERVER['DOCUMENT_ROOT']));
define('PROJECT_PATH_SRC', dirname(PROJECT_PATH).'/src.'.DOMAIN);
define('PROJECT_PATH_CLASSES',  PROJECT_PATH.'/makasimka/inc/classes/');
define('PROJECT_PATH_TEMPLATE', PROJECT_PATH.'/makasimka/inc/template/');

$appError = false;

function _setError($msg = NULL, $type = 1, $data = NULL, $timeout = 10, $param = NULL){

    global $appError;
    $appError = true;

    $msg = (empty($msg) ? 'ERROR_SERVER' : $msg);

    $array = [
        'error'=>[
            'type'=>($type > 0 ? $type : 1),
            'text'=>$msg,
            'data'=>$data,
            'time'=>(int)($timeout && $timeout > 0 ? $timeout : 10),
            'timeout'=>(int)($timeout && $timeout > 0 ? $timeout : 10),
        ]
    ];

    print Info::_parseData($array);
    exit;
}

spl_autoload_register(function ($className){
    $lib = '';

    $substr = substr($className, 0, 3);

    switch($substr):
        case 'Abs':
            $lib = 'abstract/';
            break;
        case 'Dat':
            $lib = 'data/';
            break;
        case 'Inc':
            $lib = 'interface/';
            break;
        case 'Act':
            $lib = 'action/';
            break;
        case 'Cac':
            $lib = 'cache/';
            break;
        case 'Gam':
            $lib = 'game/';
            break;
        case 'Npc':
            $lib = 'npc/';
            break;
        case 'Inf':
            if($className != 'Info'){
                $lib = 'info/';
            }
            break;
        case 'Use':
          if($className != 'User'){
              $lib = 'user/';
          }
        break;
        default:
            $lib = '';
    endswitch;

    $construct = PROJECT_PATH_CLASSES.$lib.ucfirst($className).'.php';

      if(file_exists($construct)){

          require_once($construct);

          if(!(class_exists($className, false) || interface_exists($className, false))){
              _setError('Unable to load class.', 1, null, '__UNABLE_CLASS['.$className.']['.__CLASS__.']['.__METHOD__.']'.'['.__LINE__.']');
          }
      }else{
          _setError('Unable to load class.', 1, null, '__UNABLE_CLASS_FILE['.$construct.']['.__CLASS__.']['.__METHOD__.']'.'['.__LINE__.']');
      }

});

if(!empty($mysqli)){
    new Work($mysqli);
}
