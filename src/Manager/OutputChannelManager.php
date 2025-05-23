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

namespace DynamicSearchBundle\Manager;

use DynamicSearchBundle\Configuration\ConfigurationInterface;
use DynamicSearchBundle\Context\ContextDefinitionInterface;
use DynamicSearchBundle\Logger\LoggerInterface;
use DynamicSearchBundle\OutputChannel\Modifier\OutputChannelModifierFilterInterface;
use DynamicSearchBundle\OutputChannel\OutputChannelInterface;
use DynamicSearchBundle\OutputChannel\RuntimeOptions\RuntimeOptionsBuilderInterface;
use DynamicSearchBundle\OutputChannel\RuntimeOptions\RuntimeQueryProviderInterface;
use DynamicSearchBundle\Registry\OutputChannelRegistryInterface;

class OutputChannelManager implements OutputChannelManagerInterface
{
    public function __construct(
        protected LoggerInterface $logger,
        protected ConfigurationInterface $configuration,
        protected OutputChannelRegistryInterface $outputChannelRegistry
    ) {
    }

    public function getOutputChannel(ContextDefinitionInterface $contextDefinition, string $outputChannelName): ?OutputChannelInterface
    {
        $outputChannelServiceName = $contextDefinition->getOutputChannelServiceName($outputChannelName);

        // output channel is disabled by default.
        if ($outputChannelServiceName === null) {
            return null;
        }

        if (!$this->outputChannelRegistry->hasOutputChannelService($outputChannelServiceName)) {
            return null;
        }

        return $this->outputChannelRegistry->getOutputChannelService($outputChannelServiceName);
    }

    public function getOutputChannelRuntimeQueryProvider(string $provider): ?RuntimeQueryProviderInterface
    {
        if (!$this->outputChannelRegistry->hasOutputChannelRuntimeQueryProvider($provider)) {
            return null;
        }

        return $this->outputChannelRegistry->getOutputChannelRuntimeQueryProvider($provider);
    }

    public function getOutputChannelRuntimeOptionsBuilder(string $provider): ?RuntimeOptionsBuilderInterface
    {
        if (!$this->outputChannelRegistry->hasOutputChannelRuntimeOptionsBuilder($provider)) {
            return null;
        }

        return $this->outputChannelRegistry->getOutputChannelRuntimeOptionsBuilder($provider);
    }

    public function getOutputChannelModifierAction(string $outputChannelServiceName, string $action): array
    {
        if (!$this->outputChannelRegistry->hasOutputChannelModifierAction($outputChannelServiceName, $action)) {
            return [];
        }

        return $this->outputChannelRegistry->getOutputChannelModifierAction($outputChannelServiceName, $action);
    }

    public function getOutputChannelModifierFilter(string $outputChannelServiceName, string $filter): ?OutputChannelModifierFilterInterface
    {
        if (!$this->outputChannelRegistry->hasOutputChannelModifierFilter($outputChannelServiceName, $filter)) {
            return null;
        }

        return $this->outputChannelRegistry->getOutputChannelModifierFilter($outputChannelServiceName, $filter);
    }
}
