<?php

namespace W7\Config\Fetcher;

use W7\App;
use W7\Config\Task\ConfigSyncTask;
use W7\Core\Process\ProcessServerAbstract;
use W7\Core\Server\SwooleServerAbstract;
use W7\Config\Message\ConfigFetchMessage;

abstract class ConfigFetcherAbstract {
	abstract public function fetch();

	protected function syncConfig(array $data) {
		$pipeMessage = new ConfigFetchMessage();
		$pipeMessage->params['data'] = $data;
		$pipeMessage->task = ConfigSyncTask::class;

		if (App::$server instanceof ProcessServerAbstract) {
			/**
			 * @var ProcessServerAbstract $server
			 */
			$server = App::$server;
			for ($processId = 0; $processId <= $server->getPool()->getProcessFactory()->count(); ++$processId) {
				$process = $server->getPool()->getProcessFactory()->get($processId);
				//待调整，process中暂时没有类似pipMessage的回调
				$process && $process->sendMsg($pipeMessage->pack());
			}
		} elseif (App::$server instanceof SwooleServerAbstract) {
			$workerCount = App::$server->setting['worker_num'] + App::$server->setting['task_worker_num'] - 1;
			for ($workerId = 0; $workerId <= $workerCount; ++$workerId) {
				App::$server->getServer()->sendMessage($pipeMessage->pack(), $workerId);
			}
		}
	}
}