<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Mail\BookingAccepted;
use App\Mail\BookingDeclined;
use App\Mail\EventBookingRequest;
use App\Models\Booking;
use App\Models\Email;
use App\Models\Event;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BookingController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['ConnectWebHooks']]);
    }
    public function index()
    {
        //
    }
    public function PreBookEvent($id) {
        $user = auth()->user();
        $event = Event::all()->where('id', $id)->first();
        $spot_left = $event->attendance_limit - $event->limit_count;

        if($spot_left < 1) {
            return response()->json('Sorry, there is no spot left', 404);
        }
        try {
            $receipt_no = rand(1111111111,9999999999);
            $booking = new Booking();
            $booking->user_id = $user->id;
            $booking->event_id = $event->id;
            $booking->guide_id = $event->user_id;
            $booking->event_name = $event->event_name;
            $booking->total_price = $event->price;
            $booking->receipt_no = $receipt_no;
            $booking->save();
            $bookings = DB::table('bookings')
                ->join('events', 'bookings.event_id', '=', 'events.id')
                ->where('bookings.user_id', $user->id)
                ->where('climber_delete', false)
                ->select('events.*', 'bookings.event_id', 'bookings.paid', 'bookings.accepted', 'bookings.guide_delete')
                ->get();
            //Send email
            $email = User::where('id', $booking->guide_id)->first()->email;
            $data = new Email();
            $data->email = $email;
            $data->frontend_url = config('hosts.fe');
            $data->s3bucket = config('hosts.s3');
            Mail::to($email)->send(new EventBookingRequest($data));
            return response()->json([
                'message' => 'Your request has been placed. We will reach out to you soon.',
                'bookings' => $bookings
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'An error has occured'
            ], 500);
        }

    }
    public function AcceptBooking($id) {
        try {
            $booking = Booking::findOrFail($id);
            $booking->accepted = true;
            $booking->update();
            $email = User::where('id', $booking->user_id)->first()->email;
            $data = new Email();
            $data->email = $email;
            $data->frontend_url = config('hosts.fe');
            $data->s3bucket = config('hosts.s3');
            Mail::to($email)->send(new BookingAccepted($data));
            return response()->json([
                'booking' => $booking,
                'message' => 'Booking accepted',
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'An error has occured'
            ], 500);
        }

    }
    public function DeclineBooking($id) {
        try {
            $booking = Booking::findOrFail($id);
            $booking->guide_delete = true;
            $booking->update();
            $email = User::where('id', $booking->user_id)->first()->email;
            $data = new Email();
            $data->email = $email;
            $data->frontend_url = config('hosts.fe');
            $data->s3bucket = config('hosts.s3');
            Mail::to($email)->send(new BookingDeclined($data));
            return response()->json([
                'booking' => $booking,
                'message' => 'Booking is declined',
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'An error has occured'
            ], 500);
        }
    }
    public function AttemptPayment(Request $request, $id) {
        $event = Event::where('id', $request->id)->first();
        $name = 'Payment for the event: '.$event->event_name;
        $guide = User::where('id', $event->user_id)->first();
        $connected_account_id = $guide->stripe_account_id;

        // $spot_left = $event->attendance_limit - $event->limit_count;

        // if($spot_left < 1) {
        //     return response()->json('Sorry, there is no spot left', 404);
        // }
        // Calculate application fee
        $applicationFee = $event->price * 0.1; // For example, 10% fee

        try {
            \Stripe\Stripe::setApiKey(config('stripe.sk'));
            $session = \Stripe\Checkout\Session::create([
                'line_items' => [[
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                    'name' => $name,
                    ],
                    'unit_amount' => $event->price * 100,
                ],
                'quantity' => 1,
                ]],
                'mode' => 'payment',
                'payment_intent_data' => [
                    'application_fee_amount' => $applicationFee * 100, // Application fee in cents
                    'description' => 'Payment for event: '.$event->event_name,
                    'transfer_data' => [
                        'destination' => $connected_account_id, // Replace with connected account ID
                    ],
                ],
                'success_url' => config('hosts.fe').'/booking/success/{CHECKOUT_SESSION_ID}',
                'cancel_url' => config('hosts.fe').'/booking/canceled/{CHECKOUT_SESSION_ID}',
            ]);

        // return response()->json($session, 200);

            
            $booking = Booking::where('receipt_no', $id)->first();
            $booking->payment_session_id = $session->id;
            $booking->update();
            return response()->json($session->url, 200);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'An error has occured'
            ], 500);
        }
    }
    public function CompleteBooking(Request $request)
    {
        try {
            $session_id = $request->session_id;
            \Stripe\Stripe::setApiKey(config('stripe.sk'));
            $session = \Stripe\Checkout\Session::retrieve($session_id);
            if(!$session) {
                throw new NotFoundHttpException();
            }
            $booking = Booking::where('payment_session_id', $session_id)->first();
            if(!$booking) {
                throw new NotFoundHttpException();
            }
            if(!$booking->paid) {
                $this->FinishBooking($booking);
            }
            return response()->json([
                'booking' => $booking
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'An error has occured'
            ], 500);
        }
    }
    public function FinishBooking($booking)
    {
        $booking->paid = true;
        $booking->update();
        // $event = Event::where('id', $booking->event_id)->first();
        // $event->limit_count = DB::raw('limit_count + 1');
        // $event->update();

        //send email
        
    }
    public function CancelBooking(Request $request)
    {
        try {
            $session_id = $request->session_id;
            \Stripe\Stripe::setApiKey(config('stripe.sk'));
            $session = \Stripe\Checkout\Session::retrieve($session_id);
            if($session) {
                $booking = Booking::where('payment_session_id', $session_id)
                ->where('paid', false)->first();
                if($booking) {
                    $booking->payment_session_id = null;
                    $booking->update();
                }
            }
            return response()->json('success', 200);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'An error has occured'
            ], 500);
        }
    }
    // public function WebHooks()
    // {
    //     $endpoint_secret = config('stripe.wh');
    //     $payload = @file_get_contents('php://input');
    //     $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
    //     $event = null;
    //     try {
    //     $event = \Stripe\Webhook::constructEvent(
    //         $payload, $sig_header, $endpoint_secret
    //     );
    //     } catch(\UnexpectedValueException $e) {
    //         return response('', 400);
    //     exit();
    //     } catch(\Stripe\Exception\SignatureVerificationException $e) {
    //         return response('', 400);
    //     }
    //     // Handle the event
    //     switch ($event->type) {
    //     case 'checkout.session.completed':
    //         $session = $event->data->object;
    //         $session_id = $session->id;
    //         $booking = Booking::where('payment_session_id', $session_id)->first();
    //         if($booking && !$booking->paid) {
    //             $this->FinishBooking($booking);
    //         }
    //     case 'checkout.session.async_payment_failed': //or expired
    //         $session = $event->data->object;
    //         $session_id = $session->id;
    //         $booking = Booking::where('payment_session_id', $session_id)
    //             ->where('paid', false)->first();
    //             if($booking) {
    //                 $booking->payment_session_id = null;
    //                 $booking->update();
    //             }
    //     default:
    //         echo 'Received unknown event type ' . $event->type;
    //     }
    //     return response('', 200);
    // }
    public function ConnectWebHooks() {
        
        // This is your Stripe CLI webhook secret for testing your endpoint locally.
        $endpoint_secret = config('stripe.wh');

        $payload = @file_get_contents('php://input');
        $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
        $event = null;

        try {
        $event = \Stripe\Webhook::constructEvent(
            $payload, $sig_header, $endpoint_secret
        );
        } catch(\UnexpectedValueException $e) {
        // Invalid payload
        http_response_code(400);
        exit();
        } catch(\Stripe\Exception\SignatureVerificationException $e) {
        // Invalid signature
        http_response_code(400);
        exit();
        }

        // Handle the event
        switch ($event->type) {
        case 'account.updated':
            $account = $event->data->object;
            $user = User::where('stripe_account_id', $account->id)->first();
            $user->charges_enabled = $account->charges_enabled;
            $user->details_submitted = $account->details_submitted;
            $user->payouts_enabled = $account->payouts_enabled;
            $user->update();
        case 'checkout.session.completed':
            $session = $event->data->object;
            $booking = Booking::where('payment_session_id', $session->id)->first();
            if($booking && !$booking->paid) {
                $this->FinishBooking($booking);
            }
        case 'checkout.session.async_payment_failed': //or expired
            $session = $event->data->object;
            $booking = Booking::where('payment_session_id', $session->id)
                ->where('paid', false)->first();
                if($booking) {
                    $booking->payment_session_id = null;
                    $booking->update();
                }
        default:
            echo 'Received unknown event type ' . $event->type;
        }

        return response('', 200);
    }
}
