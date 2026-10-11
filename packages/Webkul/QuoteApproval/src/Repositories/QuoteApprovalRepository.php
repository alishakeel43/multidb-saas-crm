<?php

namespace Webkul\QuoteApproval\Repositories;

use Webkul\Core\Eloquent\Repository;

class QuoteApprovalRepository extends Repository
{
    /**
     * Specify Model class name.
     */
    public function model(): string
    {
        return 'Webkul\QuoteApproval\Contracts\QuoteApproval';
    }
}
