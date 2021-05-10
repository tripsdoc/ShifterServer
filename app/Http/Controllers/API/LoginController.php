<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ShifterUser;

class LoginController extends Controller
{
    function loginShifter(Request $request) {
        $check = ShifterUser::where('UserName', $request->username)
        ->where('Password', $request->password)->first();
        
        if(!$check) {
            $response['status'] = FALSE;
            $response['error'] = 'Username or Password not correct';
            $response['profile'] = $check;
        } else {
            $response['status'] = TRUE;
            $response['error'] = '';
            $response['profile'][0] = $check;
        }
        return response($response);
    }
}
