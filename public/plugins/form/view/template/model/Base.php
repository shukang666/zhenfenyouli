<?php

namespace app\common\model;

class Base extends  \think\Model{
	
	/**
	 * @var array [field,order] eq:[id,desc]
	 */
	public $sort=[];
	
	
	protected $autoWriteTimestamp = true;
	
	public function getCreateTimeAttr($value,$data)
	{
		if(empty($value)){
			return "";
		}
		return date('Y-m-d H:i:s',$value);
	}
	public function getDeleteTimeAttr($value){
		if(empty($value)){
			return "";
		}
		return date('Y-m-d H:i:s',$value);
	}
	public function getUpdateTimeAttr($value){
		if(empty($value)){
			return "";
		}
		return date('Y-m-d H:i:s',$value);
	}
}