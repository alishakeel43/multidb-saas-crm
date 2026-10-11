<?php

namespace Webkul\Assignment\Repositories;

use Webkul\Core\Eloquent\Repository;

class AssignmentCursorRepository extends Repository
{
    /**
     * Specify Model class name.
     */
    public function model(): string
    {
        return 'Webkul\Assignment\Contracts\AssignmentCursor';
    }
}
