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
		$data = TradeModel::add(
			$this->app->request->params('from_id'), 
			$this->app->request->params('to_id'), 
			$this->app->request->params('amount')
		);

		return Response::render($data[0], $data[1]);
	}	

	public function postStore()
	{
		$data = TradeModel::store(
			$this->app->request->params('from_id'), 
			$this->app->request->params('to_id'), 
			$this->app->request->params('amount'),
			$this->app->request->params('use_wallet')
		);

		return Response::render($data[0], $data[1]);
	}

	public function inpour()
	{
		$data = TradeModel::inpour(
			$this->app->request->params('trade_sn'),
			$this->app->request->params('amount')
		);

		return Response::render($data[0], $data[1]);
	}

	public function postStatus($trade_sn, $status = 0)
	{
		$result = TradeModel::updateStatus($trade_sn, $status);
	}

	public function postConfirm()
	{
		$data = TradeModel::confirm($this->app->request->params('trade_sn'));

		return Response::render($data[0], $data[1]);
	}



	//账户转账
	public function getTransfer()
	{
		$data = TradeModel::account(
			$this->app->request->params('from_id'),
			$this->app->request->params('to_id'),
			$this->app->request->params('amount')
		);

		return Response::render($data[0], $data[1]);
	}

	public function getShow($id = null)
	{
		if ($id)
		{
			$model = TradeModel::find($id);
		}
		else
		{
			$model = TradeModel::getTradeByTradeSN($this->app->request->params('trade_sn'));
		}
		

		if (!$model)
		{
			return Response::render(404);
		}

		return Response::render(200, $model->toArray());
	}

	public function getShipments()
	{
		$data = TradeModel::shipments($this->app->request->params('trade_sn'));

		return Response::render($data[0], $data[1]);
	}

	public function getRefund()
	{
		$data = TradeModel::refund($this->app->request->params('trade_sn'));

		return Response::render($data[0], $data[1]);
	}

	public function getCancel()
	{
		$data = TradeModel::cancel($this->app->request->params('trade_sn'));

		return Response::render($data[0], $data[1]);
	}

	public function getEdit($id = null)
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