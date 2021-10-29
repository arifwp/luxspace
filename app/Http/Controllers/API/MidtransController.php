<?php

namespace App\Http\Controllers\API;

use Midtrans\Config;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class MidtransController extends Controller
{
    public function callback() {

        // Set konfigurasi midtrans
        Config::$serverKey = config('services.midtrans.serverKey');
        Config::$isProduction = config('services.midtrans.isProduction');
        Config::$isSanitized = config('services.midtrans.isSanitized');
        Config::$is3ds = config('services.midtrans.is3ds');

        // Buat instance midtrans notifikasi
        $notification = new Notification();

        // Assign ke variable
        $status = $notification->transaction_status;
        $type = $notification->payment_type;
        $fraud = $notification->fraud_status;
        $order_id = $notification->order_id;

        // Get transaction id
        $order = explode('-', $order_id);

        // Cari tranksasi berdasarkan ID
        $transaction = Transaction::findOrFailr($order[1]);

        // Menghandle notifikasi status miditrans
        if($status == 'capture') {
            if($type == 'credit_card') {
                if($fraud == 'challenge') {
                    $transaction->status = 'PENDING';
                } else {
                    $transaction->status = 'SUCCESS';
                }
            }
        }

        else if($status == 'settlement') {
            $transaction->status = 'SUCCESS';
        }

        else if($status == 'deny') {
            $transaction->status = 'PENDING';
        }

        else if ($status == 'expire') {
            $transaction->status = 'CANCELLED';
        }

        else if($status == 'cancel') {
            $transaction->status = 'CANCELLED';
        }

        // Simpan tranksasi
        $transaction->save();

        // Return response untuk midtrans
        return response()->json([
            'meta' => [
                'code' => 200,
                'message' => 'Midtrans Notification Success!'
            ]
        ]);

    }
}
