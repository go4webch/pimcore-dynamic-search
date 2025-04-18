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

namespace DynamicSearchBundle\Registry;

use DynamicSearchBundle\Filter\FilterInterface;
use DynamicSearchBundle\Index\IndexFieldInterface;
use DynamicSearchBundle\Registry\Storage\RegistryStorage;

class IndexRegistry implements IndexRegistryInterface
{
    protected RegistryStorage $registryStorage;

    public function __construct()
    {
        $this->registryStorage = new RegistryStorage();
    }

    public function registerField(IndexFieldInterface $service, string $identifier, ?string $alias, string $indexProviderName): void
    {
        $namespace = sprintf('fields_%s', $indexProviderName);
        $this->registryStorage->store($service, IndexFieldInterface::class, $namespace, $identifier, $alias);
    }

    public function registerFilter(FilterInterface $service, string $identifier, ?string $alias, string $indexProviderName): void
    {
        $namespace = sprintf('filter_%s', $indexProviderName);
        $this->registryStorage->store($service, FilterInterface::class, $namespace, $identifier, $alias);
    }

    public function hasFieldForIndexProvider(string $indexProviderName, string $identifier): bool
    {
        $namespace = sprintf('fields_%s', $indexProviderName);

        return $this->registryStorage->has($namespace, $identifier);
    }

    public function getFieldForIndexProvider(string $indexProviderName, string $identifier): ?IndexFieldInterface
    {
        $namespace = sprintf('fields_%s', $indexProviderName);

        return $this->registryStorage->get($namespace, $identifier);
    }

    public function hasFilterForIndexProvider(string $indexProviderName, string $identifier): bool
    {
        $namespace = sprintf('filter_%s', $indexProviderName);

        return $this->registryStorage->has($namespace, $identifier);
    }

    public function getFilterForIndexProvider(string $indexProviderName, string $identifier): ?FilterInterface
    {
        $namespace = sprintf('filter_%s', $indexProviderName);

        return $this->registryStorage->get($namespace, $identifier);
    }
}
