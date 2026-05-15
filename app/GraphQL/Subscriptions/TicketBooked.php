<?php

namespace App\GraphQL\Subscriptions;

use Illuminate\Http\Request;
use Nuwave\Lighthouse\Schema\Types\GraphQLSubscription;
use Nuwave\Lighthouse\Subscriptions\Subscriber;

final class TicketBooked extends GraphQLSubscription
{
    public function authorize(Subscriber $subscriber, Request $request): bool
    {
        $apiKey = $request->header('X-IAE-KEY');

        return $apiKey === '102022400236';
    }

    public function filter(Subscriber $subscriber, mixed $root): bool
    {
        return true;
    }
}
