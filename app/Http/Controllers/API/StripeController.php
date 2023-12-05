<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;


class StripeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $stripe = new \Stripe\StripeClient(config('stripe.sk'));
            $accounts = $stripe->accounts->all(['limit' => 10]);
            
            return response()->json($accounts, 200);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'An error has occured'
            ], 500);
        }
    }

    public function getThisStripe($stripe_id)
    {
        try {
            $stripe = new \Stripe\StripeClient(config('stripe.sk'));
            $account = $stripe->accounts->retrieve($stripe_id, []);
            \Stripe\Stripe::setApiKey(config('stripe.sk'));
            // Retrieve balance details for the connected account
            $balance = \Stripe\Balance::retrieve(
                ['stripe_account' => $stripe_id]
            );
            // Retrieve all payouts for the connected account
            $transfers = $stripe->transfers->all([
                'destination' => $stripe_id,
                'limit' => 10, // You can adjust the limit as needed
            ]);

            
            return response()->json([
                'account' => $account,
                'balance' => $balance->instant_available,
                'transfers' => $transfers,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'An error has occured'
            ], 500);
        }
    }

    public function store(Request $request) {
        if (! $user = JWTAuth::parseToken()->authenticate()) {
            return response()->json(['status' => 'User not found!'], 404);
        }
        try {
            // Set your secret key. Remember to switch to your live secret key in production.
            // See your keys here: https://dashboard.stripe.com/apikeys
            $stripe = new \Stripe\StripeClient(config('stripe.sk'));
            // create stripe account

            $account = $stripe->accounts->create([
                'country' => 'CA',
                'type' => 'express',
                'capabilities' => [
                    'card_payments' => ['requested' => true],
                    'transfers' => ['requested' => true],
                ],
                'business_type' => 'individual',
                
            ]);
            $link = $stripe->accountLinks->create([
                'account' => $account->id,
                'refresh_url' => 'https://example.com/reauth',
                'return_url' => config('hosts.fe').'/payout',
                'type' => 'account_onboarding',
                'collect' => 'eventually_due'
            ]);
            $user->stripe_account_id = $account->id;
            $user->update();
            
            return response()->json($link->url, 200);

        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'An error has occured'
            ], 500);
        }
    }
    public function FinishOnboarding() {
        if (! $user = JWTAuth::parseToken()->authenticate()) {
            return response()->json(['status' => 'User not found!'], 404);
        }
        try {
            // Set your secret key. Remember to switch to your live secret key in production.
            // See your keys here: https://dashboard.stripe.com/apikeys
            $stripe = new \Stripe\StripeClient(config('stripe.sk'));
            // create stripe account

            $link = $stripe->accountLinks->create([
                'account' => $user->stripe_account_id,
                'refresh_url' => 'https://example.com/reauth', // URL to redirect to if the link is used after expiry
                'return_url' => config('hosts.fe').'/payout',
                'type' => 'account_onboarding',
                'collect' => 'eventually_due'
            ]);
            return response()->json($link->url, 200);

        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'An error has occured'
            ], 500);
        }
    }
    public function GoToStripeDashboard($stripe_id)
    {

        \Stripe\Stripe::setApiKey(config('stripe.sk'));
        $loginLink = \Stripe\Account::createLoginLink($stripe_id);
        return response()->json($loginLink->url, 200);

    }
    public function update(Request $request, string $id)
    {
        // $user = User::findOrFail($id);
        // $stripe = new \Stripe\StripeClient(config('stripe.sk'));
        // $stripe->accounts->update(
        //     $user->stripe_account_id,
        //     ['metadata' => ['order_id' => '6735']]
        // );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
