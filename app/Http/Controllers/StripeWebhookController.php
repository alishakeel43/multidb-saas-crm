<?php

namespace App\Http\Controllers;

use App\Services\StripeCheckoutService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Throwable;

class StripeWebhookController extends Controller
{
    public function __invoke(Request $request, StripeCheckoutService $stripe): Response
    {
        try {
            $stripe->handleWebhook(
                $request->getContent(),
                $request->header('Stripe-Signature'),
            );
        } catch (Throwable $exception) {
            report($exception);

            return response($exception->getMessage(), 400);
        }

        return response('ok', 200);
    }
}
