<?php

/*
 * This source file is available under two different licenses:
 *   - GNU General Public License version 3 (GPLv3)
 *   - DACHCOM Commercial License (DCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) DACHCOM.DIGITAL AG (https://www.dachcom-digital.com)
 * @license    GPLv3 and DCL
 */

namespace DynamicSearchBundle\EventSubscriber;

use DynamicSearchBundle\DynamicSearchEvents;
use DynamicSearchBundle\Event\ErrorEvent;
use DynamicSearchBundle\Exception\ProcessCancelledException;
use DynamicSearchBundle\Exception\RuntimeException;
use DynamicSearchBundle\Logger\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ErrorEventSubscriber implements EventSubscriberInterface
{
    protected bool $dispatched;
    protected LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->dispatched = false;
        $this->logger = $logger;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            DynamicSearchEvents::ERROR_DISPATCH_ABORT    => ['onAbort'],
            DynamicSearchEvents::ERROR_DISPATCH_CRITICAL => ['onCritical'],
        ];
    }

    /**
     * @throws ProcessCancelledException
     */
    public function onAbort(ErrorEvent $event): void
    {
        $this->logger->error($event->getMessage(), $event->getProviderName(), $event->getContextName());

        throw new ProcessCancelledException($event->getMessage());
    }

    /**
     * @throws RuntimeException
     */
    public function onCritical(ErrorEvent $event): void
    {
        if ($this->dispatched === true) {
            return;
        }

        $this->logger->error($event->getMessage(), $event->getProviderName(), $event->getContextName());

        throw new RuntimeException($event->getMessage());
    }
}
