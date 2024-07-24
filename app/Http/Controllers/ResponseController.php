<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ResponseController extends Controller
{
    public function responseValidation($msg, $token = null)
    {
        return response()->json([
            'message' => $msg,
            'attributes' => $token,
        ]);
    }
    public function responseValidationError($key, $msg, $status = 401)
    {
        return response()->json([
            'errKey' => $key,
            'message' => $msg,
        ], $status);
    }
}
