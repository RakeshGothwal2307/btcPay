<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use BTCPayServer\Client\Invoice;
use BTCPayServer\Client\InvoiceCheckoutOptions;
use BTCPayServer\Util\PreciseNumber;
use App\Models\{Payment, Provider};
use Illuminate\Support\Facades\DB;


class PaymentController extends Controller
{
    public function payment(Request $request)
    {
        $host = 'https://mainnet.demo.btcpayserver.org';
        $apiKey = '52dc2656c9247dd562246488691b73deeb1ababe';
        $storeId = 'Fb26zd7d3G76jCjxcvovPNsTTHKsgu4ACfrJ8Di4z2yt';
        $amount = 7;
        $currency = "USD";
        $orderId = 'AP' . rand(0, 9999);
        $buyerEmail = $request->buyerEmail;
        $id = $request->id;


        try {
            $client = new Invoice($host, $apiKey);

            // BTCPay Server.
            $metaData = [
                'buyerName' => $request->buyerName,
                'buyerCity' => $request->buyerCity,
                'buyerState' => $request->buyerState,
                'buyerCountry' => $request->buyerCountry,
                'buyerPhone' => $request->buyerPhone,
                'physical' => false,
                // 'taxIncluded' => 2.15,
            ];

            // Setup custom checkout options, defaults get picked from store config.
            $checkoutOptions = new InvoiceCheckoutOptions();
            $checkoutOptions
                ->setSpeedPolicy($checkoutOptions::SPEED_HIGH)
                ->setPaymentMethods(['BTC'])
                ->setExpirationMinutes(30)
                ->setRedirectURL('https://staging.aphroditecollection.com/btcpay/redirect/?id=' . $id . '&amount=' . $amount . '&email=' . $buyerEmail . '&mobile=' . $request->buyerPhone . '&points=' . $request->points);

            $data = $client->createInvoice(
                $storeId,
                $currency,
                PreciseNumber::parseString($amount),
                $orderId,
                $buyerEmail,
                $metaData,
                $checkoutOptions
            );
            session()->put('invoice_id', $data['id']);
            return redirect($data['checkoutLink']);
        } catch (\Throwable $e) {
            echo "Error: " . $e->getMessage();
        }
    }

    public function redirect()
    {
        $id = $_GET['id'];
        $amount = $_GET['amount'];
        $email = $_GET['email'];
        $mobile = $_GET['mobile'];
        $points = $_GET['points'];
        
        // dd($update_points);
        if ($id == "client") {
            $insert_Payment = Payment::create(['client_id' => auth()->guard('member')->id(), 'type' => $id, 'points' => $amount, 'descp' => 'Purchased Points using Bitcoin', 'amount' => $amount, 'email' => $email, 'mobile' => $mobile]);
            $login_id = auth()->guard('member')->id();
            $total_points = DB::table('client_points_table')->where(['client_id' => $login_id])->first();
            $update_points = DB::table('client_points_table')->where(['client_id' => $login_id])->update(['points' => $points + $total_points->points]);

            $insert_points = DB::table('client_points_history')->insert(
                [
                    'client_id' => $login_id,
                    'total_points' => $points + $total_points->points,
                    'description' => 'Purchased Points using Bitcoin',
                    'added' => $points,
                ]
            );
            return redirect()->route('client.dashbord');
        }

        if ($id == "provider") {
            $insert_Payment = Payment::create(['client_id' => auth()->guard('provider')->id(), 'type' => $id, 'points' => $amount, 'descp' => 'Purchased Points using Bitcoin', 'amount' => $amount, 'email' => $email, 'mobile' => $mobile]);
            $login_id = auth()->guard('provider')->id();
            $total_points = DB::table('points_table')->where(['provider_id' => $login_id])->first();
            $update_points = DB::table('points_table')->where(['provider_id' => $login_id])->update(['points' => $points + $total_points->points]);

            $insert_points = DB::table('points_history')->insert(
                [
                    'provider_id' => $login_id,
                    'total_points' => $points + $total_points->points,
                    'description' => 'Purchased Points using Bitcoin',
                    'added' => $points,
                ]
            );
            return redirect()->route('provider.dashboard');
        }
    }

    public function payment_provider()
    {
        $provider = Provider::find(auth()->guard('provider')->id());
        $payments = DB::table('payment')->where(['client_id' => auth()->guard('provider')->id(), 'type' => 'provider'])->paginate(10);
        return view('provider.payment_history', compact('provider', 'payments'));
    }
}
