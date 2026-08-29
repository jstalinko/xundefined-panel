<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Domain;

class DomainController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $payload = $request->post('payload');
        if(!$payload) return response()->json(['success'=>false,'message' => 'Payload is required'], 400,[],JSON_PRETTY_PRINT);
        
        // payload: domain.invite_key.product_id
        $extract = base64_decode($payload);
        $split = explode('|', $extract);
        $domain = $split[0];
        $inviteKey = $split[1];
        $product_id = $split[2];
        
        $user = User::where('invite_key',$inviteKey)->first();
        if(!$user) return response()->json(['success'=>false,'message' => 'Invalid invite key'], 400,[],JSON_PRETTY_PRINT);

        $checkDomain = Domain::where('domain', $domain)->where('product_id',$product_id)->where('user_id', $user->id)->first();
        if($checkDomain)
        {
            $checkDomain->incrementHits();
            return response()->json(['success' => true, 'message' => 'Domain registered'],200,[],JSON_PRETTY_PRINT);
        }
        

        return response()->json(['success' => false,'message' => 'Domain not registered, please register in xundefined.cc '],403,[],JSON_PRETTY_PRINT);
        
    }
}
