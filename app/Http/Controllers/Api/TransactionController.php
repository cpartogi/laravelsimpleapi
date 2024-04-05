<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class TransactionController extends Controller
{
    //  public function store(Request $request)
    public function store(Request $request)
    {
        if (!empty($request->json('trx_id')) && !empty($request->json('amount')) ) {
            //check token
            $token = $request->header('token');
            
            if ($token == "") {
                return response()->json([
                    "status" => "Forbidden",
                    "status_code" => 403,
                    "message" => "Login failed",
                    "data" => null,
                ], 403);
            }

            $sqlc = "SELECT id FROM users where token = '".$token."'";
            $rc=DB::select($sqlc);
           
            if (count($rc) == 0)  {
                return response()->json([
                    "status" => "Forbidden",
                    "status_code" => 403,
                    "message" => "Login failed",
                    "data" => null,
                ], 403);
            }    
            
            $id=$rc[0]->id;
            $trxId = $request->json('trx_id');
            $amount = $request->json('amount');

            if ($amount ==  0.00000001) {
                return response()->json([
                    "status" => "Bad Request",
                    "status_code" => 400,
                    "message" => "invalid amount",
                    "data" => null,
                ], 400);
            }

            //check balance
            $balance = DB::table('balance')
                ->where('user_id', $id)
                ->value('amount_available');

            if (!$balance) {
                return response()->json([
                    "status" => "Bad Request",
                    "status_code" => 400,
                    "message" => "Insufficient",
                    "data" => null,
                ], 400);
            }    

            if ($balance < $amount) {
                return response()->json([
                    "status" => "Bad Request",
                    "status_code" => 400,
                    "message" => "Insufficient",
                    "data" => null,
                ], 400);
            }

            DB::beginTransaction();

            $existingTrx = DB::table('transaction')->where('trx_id', $trxId)->exists();

            if ($existingTrx) {
                DB::rollBack();
                return response()->json([
                    "status" => "Bad Request",
                    "status_code" => 400,
                    "message" => "Process failed",
                    "data" => null,
                ], 400);
            }

            //insert table transactions
            $insertData = [
                'trx_id' => $trxId,
                'user_id' => $id,
                'amount' => $amount,
                'created_at' => now(),
                'updated_at' => now()
            ];
            
            DB::table('transaction')->insert($insertData);

            $amountAvailable = $balance - $amount;

            //update balance
            DB::table('balance')
                ->where('user_id', $id)
                ->update([
                    'amount_available' => $amountAvailable,
                    'updated_at' => now()
                ]);

            DB::commit();

            $data["user_id"] = $id;
            $data["trx_id"] = $trxId;
            $data["amount"] = $amount;
            $data["balance"] = $amountAvailable; 
            
            return response()->json([
                "status" => "Success",
                "status_code" => 200,
                "message" => "Success transaction",
                "data" => $data,
            ], 200);

        } else {
            return response()->json([
                "status" => "Bad Request",
                "status_code" => 400,
                "message" => "trx id and amount required",
                "data" => null,
            ], 400);
        }    
    }
}
