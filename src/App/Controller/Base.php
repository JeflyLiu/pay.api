<?php
namespace App\Controller;

class Base
{
	/**
     * @var Slim
     */
    protected $app;
    
    const ERROR_PARAM_MISS = 100;
    const ERROR_PARAM_WRONG_FORMAT = 101;

    public function __construct()
    {
        $this->initSlim();
    }

    /**
    *
    * @return \Slim\Slim
    */
	public function initSlim()
    {
    	return $this->app ?: $this->app = \Slim\Slim::getInstance();
    }
    
    /**
     * 验证接口外部调用参数的合法性
     * @param unknown_type $params_config
     * @return multitype:number string
     */
    function validateParams($params_config, $params){
    	$result = array('result'=>0,'msg'=>'');
    	foreach ($params_config as $key=>$key_configs){
    		$type = $key_configs[0];
    		$required = $key_configs[1];
    		if(isset($params[$key])){
    			if($type == 'int'){
    				if(preg_match("/^\\d+$/",$params[$key]) === 0){
    					$result['result'] = self::ERROR_PARAM_WRONG_FORMAT;
    					$result['msg'] = "参数{$key}的格式不正确";
    					break;
    				}
    			}
    			elseif($type == 'string'){
    
    			}
    			elseif($type == 'decimal'){
    				if(!is_numeric($params[$key])){
    					$result['result'] = self::ERROR_PARAM_WRONG_FORMAT;
    					$result['msg'] = "参数{$key}的格式不正确";
    					break;
    				}
    			}
    			else{
    				if(!preg_match($type,$params[$key]) === 0){
    					$result['result'] = self::ERROR_PARAM_WRONG_FORMAT;
    					$result['msg'] = "参数{$key}的格式不正确";
    					break;
    				}
    			}
    		}
    		else{
    			if($required == 'required'){
    				$result['result'] = self::ERROR_PARAM_MISS;
    				$result['msg'] = "未发送必须的参数{$key}";
    				break;
    			}
    		}
    	}
    	return $result;
    }

}