<?php

namespace elleracompany\cookieconsent\controllers;

use Craft;
use craft\web\Controller;
use craft\web\Response;

class ConsentController extends Controller
{
	public function actionUpdate()
	{
		Craft::$app->response->format = Response::FORMAT_JSON;
		return Craft::$app->request->post();
	}
}