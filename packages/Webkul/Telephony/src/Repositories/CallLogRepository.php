<?php

namespace Webkul\Telephony\Repositories;

use Webkul\Core\Eloquent\Repository;

class CallLogRepository extends Repository
{
    /**
     * Specify the model class name.
     *
     * @return string
     */
    public function model()
    {
        return 'Webkul\Telephony\Contracts\CallLog';
    }
}
