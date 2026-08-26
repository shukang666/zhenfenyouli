<?php
namespace app\admin\controller;

use cmf\controller\AdminBaseController;

class BaseController extends AdminBaseController{
	
	/**
	 * @var \app\common\model\Base
	 */
	protected  $model =null;
	protected  $where=[];
	
	public function initialize(){
		parent::initialize();
	}
	
	protected  function where_like($key){
		$value = $this->request->post($key);
		if($value){
			$this->assign($key, $value);
			$this->where[]= [$key, 'like', "%".$value."%"];
		}
	}
	protected  function where_eq($key){
		$value = $this->request->post($key);
		if($value){
			$this->assign($key, $value);
			$this->where[]= [$key, '=',$value];
		}
	}
	
	
	protected function base_index(array $where=[],array $order=[]){
		if(count($order)===0){
			$sort = $this->model->sort;
			if($sort){
				$order[$sort[0]]=$sort[1];
			}
		}
		$where=array_merge($this->where,$where);
		$list= $this->model->where($where)->order($order)->paginate([
			'list_rows'=>20,
			'query'=>$this->request->param()
		]);
		$this->assign("list",$list);
		$this->assign('page', $list->render());//单独提取分页出来
		
		return $this->fetch();
	}
	
	
	
	protected function base_edit(){
		$pk = $this->model->getPk();
		$id =$this->request->param($pk);
		if(empty($id)){
			$this->error("not found id");
		}
		$data=$this->model->find($id);
		if(empty($data)){
			$this->error("not found data");
		}
		$toArray = $data->toArray();
		foreach($toArray as $k=> $v){
			$this->assign($k,$v);
		}
		return $this->fetch();
	}
	
	protected function base_edit_post(string $validate=''){
		$data= $this->request->post();
		if($validate){
			$res=$this->validate($data,$validate);
			if($res!==true){
				$this->error($res);
			}
		}
		$this->model->update($data);
		$this->success("保存成功");
	}
	
	
	protected function base_add(){
		return  $this->fetch();
	}
	
	protected function base_add_post(string $validate=''){
		$data= $this->request->post();
		if($validate){
			$res=$this->validate($data,$validate);
			if($res!==true){
				$this->error($res);
			}
		}
		$this->model->insert($data);
		$this->success("添加成功",url("index"));
	}
	
	
	protected function base_delete(){
		$id = $this->request->get($this->model->getPk());
		if(!empty($id)){
			if(!$this->model->where($this->model->getPk(),"=",$id)->delete()){
				$this->error("删除失败");
			}
			$this->success("删除成功");
		}
		
		$ids=  $this->request->post("ids");
		if(!empty($ids)){
			if(!$this->model->whereIn($this->model->getPk(),$ids)->delete()){
				$this->error("没有找到主键");
			}
			$this->success("批量删除成功",url('index'));
		}
	}
}
