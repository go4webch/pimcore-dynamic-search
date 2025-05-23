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

namespace DynamicSearchBundle\Resource;

class ResourceCandidate implements ResourceCandidateInterface
{
    public function __construct(
        protected string $contextName,
        protected string $dispatchType,
        protected bool $allowDispatchTypeModification,
        protected bool $allowResourceModification,
        protected mixed $resource
    ) {
    }

    public function isAllowedToModifyDispatchType(): bool
    {
        return $this->allowDispatchTypeModification === true;
    }

    public function getContextName(): string
    {
        return $this->contextName;
    }

    public function isAllowedToModifyResource(): bool
    {
        return $this->allowResourceModification === true;
    }

    public function setResource($resource): void
    {
        if ($this->allowResourceModification === false && $resource !== null) {
            throw new \Exception(
                sprintf(
                    'Only resource deletion (null) is allowed at current state (%s)',
                    $this->dispatchType
                )
            );
        }

        $this->resource = $resource;
    }

    public function getResource(): mixed
    {
        return $this->resource;
    }

    public function setDispatchType(string $dispatchType): void
    {
        if ($this->allowDispatchTypeModification === false) {
            throw new \Exception(
                sprintf(
                    'Changing dispatch type from "%s" to "%s" is not allowed at current state',
                    $this->dispatchType,
                    $dispatchType
                )
            );
        }

        $this->dispatchType = $dispatchType;
    }

    public function getDispatchType(): string
    {
        return $this->dispatchType;
    }
}
