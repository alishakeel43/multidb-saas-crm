<?php

namespace Webkul\Consent\Repositories;

use Webkul\Core\Eloquent\Repository;

class PersonConsentRepository extends Repository
{
    /**
     * Specify Model class name.
     */
    public function model(): string
    {
        return 'Webkul\Consent\Contracts\PersonConsent';
    }
}
