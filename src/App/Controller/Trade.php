<?php

namespace App\Controller;

use App\Controller\Base;
use App\Controller\Response;
use App\Model\Trade as TradeModel;
use Illuminate\Database\Capsule\Manager as DB;

class Trade extends Base
{

	public function getIndex()
	{
		$params = $this->app->request->params();
		$list = TradeModel::searchList($params);

		return Response::render(200,$list);
	}

	public function getCreate()
	{
		$from_id = (int) ($this->app->request->params('from_id'));
		$to_id = (int) ($this->app->request->params('to_id'));
		$amount = $this->app->request->params('amount');

		$data = TradeModel::create($from_id, $to_id, $amount);

		return Response::render($data[0], $data[1]);
	}

	public function getTransfer()
	{
		$data = TradeModel::account(
			$this->app->request->params('from_id'),
			$this->app->request->params('to_id'),
			$this->app->request->params('amount')
		);

		return Response::render($data[0], $data[1]);
	}

	public function postStore()
	{
		$data = TradeModel::store(
			$this->app->request->params('trade_sn'),
			$this->app->request->params('trade_sn')
		);

		return Response::render($data[0], $data[1]);
	}

	public function getShow($id)
	{
		$model = TradeModel::find($id);

		if (!$model)
		{
			return Response::render(404);
		}

		return Response::render(200, $model->toArray());
	}

	public function getEdit($id)
	{
		$model = TradeModel::find($id);

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
		$model = TradeModel::find($id);

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
		$model = TradeModel::find($id);

		if (! $model)
		{
			return Response::render(404);
		}

		$model->delete();

		return Response::render(200, null);
	}

}