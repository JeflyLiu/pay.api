<?php

namespace App\Controller;

use App\Controller\Base;
use App\Controller\Response;
use App\Model\Inpour as InpourModel;
use Illuminate\Database\Capsule\Manager as DB;

class Inpour extends Base
{

	public function index()
	{
		$params = $this->app->request->params();
		$list = InpourModel::getList($params);

		return Response::render(200,$list);
	}

	public function getCreate()
	{
		$account_id = (int)($this->app->request->params('account_id'));
		$amount = $this->app->request->params('amount');
		$channels = $this->app->request->params('channels');
		
		$data = InpourModel::getCreate($account_id, $amount, $channels);

		return Response::render($data[0], $data[1]);
	}

	/**
	* @param $inpour_id
	* @param $amount
	* @return mixed
	*/
	public function store()
	{
		$inpour_id = (int)$this->app->request->params('inpour_id');
		$amount = $this->app->request->params('amount');
		$model = InpourModel::store($inpour_id, $amount);

		return Response::render($model['code'], $model['data']);
	}

	public function getShow($id)
	{
		$model = InpourModel::find($id);

		if (!$model)
		{
			return Response::render(404);
		}

		return Response::render(200, $model->toArray());
	}

	public function getEdit($id)
	{
		$model = InpourModel::find($id);

		if (!$model)
		{
			return Response::render(404);
		}

		$model->fill($this->app->request->params());
		$model->attr_filter();

		if($errors = $model->validate())
		{
			return Response::render(400, array('msg' => array_values($errors)));
		}

		if (!$model->save())
		{
			return Response::render(500);
		}

		return Response::render(200, $model->toArray());
	}

	public function update($id)
	{
		$model = InpourModel::find($id);

		if (!$model)
		{
			return Response::render(404);
		}

		$model->fill($this->app->request->params());
		$model->attr_filter();

		if($errors = $model->validate())
		{
			return Response::render(400, array('msg' => array_values($errors)));
		}

		if (!$model->save())
		{
			return Response::render(500);
		}

		return Response::render(200, $model->toArray());
	}

	public function destroy($id)
	{
		$model = InpourModel::find($id);

		if (! $model)
		{
			return Response::render(404);
		}

		$model->delete();

		return Response::render(200, null);
	}

}