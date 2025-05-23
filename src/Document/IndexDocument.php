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

namespace DynamicSearchBundle\Document;

use DynamicSearchBundle\Normalizer\Resource\ResourceMetaInterface;
use DynamicSearchBundle\Resource\Container\IndexFieldContainerInterface;
use DynamicSearchBundle\Resource\Container\OptionFieldContainerInterface;

class IndexDocument
{
    protected array $optionFields = [];
    protected array $indexFields = [];

    public function __construct(
        protected ?ResourceMetaInterface $resourceMeta,
        protected array $documentConfiguration = []
    ) {
    }

    public function getDocumentId(): null|string|int
    {
        return $this->resourceMeta instanceof ResourceMetaInterface ? $this->resourceMeta->getDocumentId() : null;
    }

    public function getResourceMeta(): ?ResourceMetaInterface
    {
        return $this->resourceMeta;
    }

    public function getDocumentConfiguration(): array
    {
        return $this->documentConfiguration;
    }

    public function addOptionField(OptionFieldContainerInterface $fieldContainer): void
    {
        $this->optionFields[] = $fieldContainer;
    }

    public function addIndexField(IndexFieldContainerInterface $fieldContainer): void
    {
        $this->indexFields[] = $fieldContainer;
    }

    public function hasIndexFields(): bool
    {
        return count($this->indexFields) > 0;
    }

    /**
     * @return array<int, IndexFieldContainerInterface>
     */
    public function getIndexFields(): array
    {
        return !$this->hasIndexFields() ? [] : $this->indexFields;
    }

    public function hasOptionFields(): bool
    {
        return count($this->optionFields) > 0;
    }

    /**
     * @return array<int, OptionFieldContainerInterface>
     */
    public function getOptionFields(): array
    {
        return !$this->hasOptionFields() ? [] : $this->optionFields;
    }
}
