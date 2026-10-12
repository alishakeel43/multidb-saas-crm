<?php

namespace Webkul\Telephony\Services;

use Twilio\Rest\Client as TwilioClient;
use Webkul\Core\Repositories\CoreConfigRepository;

class TwilioClientFactory
{
    /**
     * Create a Twilio REST client using the current tenant's credentials.
     *
     * Credentials are read from core_config so they are isolated per tenant.
     */
    public function make(): TwilioClient
    {
        /** @var CoreConfigRepository $config */
        $config = app(CoreConfigRepository::class);

        $accountSid = $config->getConfigData('telephony.credentials.account_sid');
        $authToken = $config->getConfigData('telephony.credentials.auth_token');

        return new TwilioClient($accountSid, $authToken);
    }
}
