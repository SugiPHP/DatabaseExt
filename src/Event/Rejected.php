<?php

declare(strict_types=1);

namespace SugiPHP\DatabaseExt\Event;

/**
 * Event dispatched when a statement is rejected by PdoExt::canExecute() because the
 * connection is in STATE_READ_ONLY or STATE_UNAVAILABLE.
 */
class Rejected extends BeforeEvent
{
    public function __construct(
        string $queryString,
        private readonly string $state,
        private readonly ?array $params = null,
    ) {
        parent::__construct($queryString, 'Rejected');
    }

    /**
     * Get the read/write state (PdoExt::STATE_*) that caused the statement to be rejected.
     *
     * @return string The state.
     */
    public function getState(): string
    {
        return $this->state;
    }

    /**
     * Get the parameters bound to the query, if any.
     *
     * @return array|null The bound parameters or null if none were provided.
     */
    public function getParams(): ?array
    {
        return $this->params;
    }
}
