<?php

/**
 * Rangine crontab server
 *
 * (c) We7Team 2019 <https://www.rangine.com>
 *
 * document http://s.w7.cc/index.php?c=wiki&do=view&id=317&list=2284
 *
 * visited https://www.rangine.com for more details
 */

namespace W7\Config\Listener;

use Swoole\Http\Server;
use W7\Config\Message\ConfigFetchMessage;
use W7\Core\Listener\ListenerAbstract;
use W7\Contract\Task\TaskDispatcherInterface;
use W7\Core\Exception\HandlerExceptions;
use W7\Core\Message\TaskMessage;

class AfterPipeMessageListener extends ListenerAbstract {
	public function run(...$params) {
		/**
		 * @var Server $server
		 */
		list($server, $workId, $message, $data) = $params;

		if ($message instanceof ConfigFetchMessage) {
			try {
				/**
				 * @var TaskDispatcherInterface $taskDispatcher
				 */
				$taskDispatcher = $this->getContainer()->singleton(TaskDispatcherInterface::class);
				$message->type = TaskMessage::OPERATION_TASK_NOW;
				$taskDispatcher->dispatch($message, $server, $this->getContext()->getCoroutineId(), $workId);
			} catch (\Throwable $throwable) {
				$this->getContainer()->singleton(HandlerExceptions::class)->getHandler()->report($throwable);
			}
		}
	}
}