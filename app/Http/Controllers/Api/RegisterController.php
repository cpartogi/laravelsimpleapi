<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RegisterController extends Controller
{
    //

    public function store(Request $request)
    {
        if (!empty($request->json('email')) && !empty($request->json('password')) ) {
            $email = $request->json('email');
            $password = $request->json('password');

            //check if email exist
            $exists = DB::table('users')
                ->select('id')
                ->where('email', $email)
                ->exists();

            if ($exists)  {
                return response()->json([
                    "status" => "Conflict",
                    "status_code" => 409,
                    "message" => "Email already exist",
                    "data" => null,
                ], 409);
    
            }    

            //insert to database
            $id=DB::table('users')->insertGetId([
                'email' => $email,
                'password' => $password,
            ]);

            $data["user_id"] = $id;

            return response()->json([
                "status" => "Success",
                "status_code" => 200,
                "message" => "Success register",
                "data" => $data,
            ], 200);

        } else {
            return response()->json([
                "status" => "Bad Request",
                "status_code" => 400,
                "message" => "Email and Password required",
                "data" => null,
            ], 400);
        }    
    }
}
