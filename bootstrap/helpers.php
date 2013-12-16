<?php

function detectEnvironment($base,$environments)
{
	foreach ($environments as $environment => $hosts)
	{
		foreach ((array) $hosts as $host)
		{
			if (str_is($host, $base))
			{
				return $this['env'] = $environment;
			}
		}
	}
}

function getIp()
{
	$app = new \Slim\Slim();
	return $app->request->getIp();
}