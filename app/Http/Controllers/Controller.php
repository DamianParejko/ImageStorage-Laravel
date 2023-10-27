<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function response($data = null, $code = 200, $message = null){

        switch ($code) {
            case 201:
                $message = 'Created successfully';
                break;
            case 404:
                $data = null;
                $message = 'Not Found';
                break;
            case 422:
                $data = null;
            default:
                break;
        }

        return response()->json([
            'data' => $data,
            'code' => $code,
            'message' => $message
        ], $code);
    }
}
