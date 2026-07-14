<?php

namespace Modules\Workflow\Contracts;

interface WorkflowInterface
{
    public function execute(): void;
    public function rollback(): void;
}
