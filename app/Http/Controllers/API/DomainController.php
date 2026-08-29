<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Domain;
use App\Models\Order;

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
        $domain = $split[0] ?? null;
        $inviteKey = $split[1] ?? null;
        $product_id = $split[2] ?? null;
        
        $user = User::where('invite_key',$inviteKey)->first();
        if(!$user) return response()->json(['success'=>false,'message' => 'Invalid invite key'], 400,[],JSON_PRETTY_PRINT);

        $checkDomain = Domain::where('domain', $domain)->where('product_id',$product_id)->where('user_id', $user->id)->first();
        if($checkDomain)
        {
            $checkDomain->incrementHits();
            return response()->json(['success' => true, 'message' => 'Domain registered'],200,[],JSON_PRETTY_PRINT);
        }

        // Check Order domain_quota and count registered domains for this user and product
        $order = Order::where('user_id', $user->id)->where('product_id', $product_id)->first();
        $domainQuota = $order ? $order->domain_quota : 0;

        $registeredCount = Domain::where('user_id', $user->id)->where('product_id', $product_id)->count();
        if ($registeredCount >= $domainQuota) {
            return response()->json([
                'success' => false,
                'message' => 'Domain quota limit reached for this product. Quota limit: ' . $domainQuota
            ], 403, [], JSON_PRETTY_PRINT);
        }
        

        return response()->json(['success' => false,'message' => 'Domain: '.$domain.' not registered, please register in xundefined.cc '],403,[],JSON_PRETTY_PRINT);
        
    }
}
