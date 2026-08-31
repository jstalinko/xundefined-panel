<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Domain;
use App\Models\Order;
use App\Models\Post;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class DashboardController extends Controller
{
    /**
     * Display the cyber red dashboard.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // System overview statistics
        $stats = [
            'total_users' => User::count(),
            'active_members' => User::where('role', User::ROLE_MEMBER)->count(),
            'admin_count' => User::where('role', User::ROLE_ADMIN)->count(),
            'system_status' => 'OPTIMAL // LIVE',
            'security_level' => 'LEVEL-5 CYBER-RED',
            'server_time' => now()->format('Y-m-d H:i:s T'),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
        ];

        // Free Tool Launcher Nodes
        $toolNodes = [
            [
                'id' => 'smtp-tester',
                'badge' => 'FREE TOOL',
                'icon' => 'fa-solid fa-envelope-circle-check',
                'title' => 'SMTP Tester',
                'description' => 'Test and verify SMTP server connections, authentication, and email delivery.',
                'route' => 'https://lab.xundefined.cc/tools/smtp-tester.php?pk=XU-XXXX-TOOLS-FREE-ACCESS-TOKEN',
                'category' => 'mail'
            ],
            [
                'id' => 'encode-obfuscate',
                'badge' => 'FREE TOOL',
                'icon' => 'fa-solid fa-code-compare',
                'title' => 'Encode & Obfuscate Studio',
                'description' => 'Encode, decode, and obfuscate payloads with Base64, Hex, URL, and hash tools.',
                'route' => 'https://lab.xundefined.cc/tools/encode-tools.php?pk=XU-XXXX-TOOLS-FREE-ACCESS-TOKEN',
                'category' => 'crypto'
            ],
            [
                'id' => 'bin-checker',
                'badge' => 'FREE TOOL',
                'icon' => 'fa-solid fa-credit-card',
                'title' => 'Bin Checker',
                'description' => 'Lookup Bank Identification Number (BIN) information, issuer bank, card brand, and country.',
                'route' => 'https://lab.xundefined.cc/tools/bin.php?pk=XU-XXXX-TOOLS-FREE-ACCESS-TOKEN',
                'category' => 'financial'
            ],
            [
                'id' => 'ip-geo-lookup',
                'badge' => 'FREE TOOL',
                'icon' => 'fa-solid fa-location-dot',
                'title' => 'IP Geo Lookup',
                'description' => 'Inspect IP address geographical location, ISP, ASN details, and connection routing.',
                'route' => 'https://lab.xundefined.cc/tools/ip-info.php?pk=XU-XXXX-TOOLS-FREE-ACCESS-TOKEN',
                'category' => 'network'
            ],
        ];

        $activityLogs = ActivityLog::with('user')->latest()->take(50)->get();

        return view('dashboard.index', compact('user', 'stats', 'toolNodes', 'activityLogs'));
    }

    /**
     * Display the domain management page.
     * Shows user domains with "Register Domain" capability.
     */
    public function domain(Request $request)
    {
        $user = Auth::user();

        $domains = Domain::where('user_id', $user->id)
            ->with('product')
            ->latest()
            ->get();

        // Retrieve products user has purchased to associate domains with
        $purchasedProductIds = Order::where('user_id', $user->id)
            ->where('status', 'completed')
            ->pluck('product_id')
            ->toArray();

        $userProducts = Product::whereIn('id', $purchasedProductIds)->get();
        if ($userProducts->isEmpty()) {
            $userProducts = Product::where('active', true)->get();
        }

        // Build domain usage vs quota stats for each purchased product
        $productStats = collect();
        foreach ($userProducts as $product) {
            $orders = Order::where('user_id', $user->id)
                ->where('product_id', $product->id)
                ->where('status', 'completed')
                ->get();

            $quota = $orders->isNotEmpty() ? $orders->sum('domain_quota') : 3;
            $used = Domain::where('user_id', $user->id)->where('product_id', $product->id)->count();

            $productStats->push([
                'product_id' => $product->id,
                'product_name' => $product->name,
                'used' => $used,
                'quota' => $quota,
                'invoice' => $orders->isNotEmpty() ? $orders->pluck('invoice')->implode(', ') : 'ACTIVE LIC',
                'percentage' => $quota > 0 ? min(100, (int) round(($used / $quota) * 100)) : 0,
            ]);
        }

        return view('dashboard.domain', compact('user', 'domains', 'userProducts', 'productStats'));
    }

    /**
     * Store and register a new domain for the user.
     */
    public function storeDomain(Request $request)
    {
        $domainInput = strtolower(trim((string) $request->input('domain')));
        $domainInput = preg_replace('#^https?://#i', '', $domainInput);
        $request->merge(['domain' => $domainInput]);

        $request->validate([
            'domain' => [
                'required',
                'string',
                'max:255',
                'unique:domains,domain',
                'regex:/^(localhost|((25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)|([a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,})(:\d{1,5})?$/i'
            ],
            'product_id' => 'required|integer',
        ], [
            'domain.regex' => 'The domain format is invalid (allowed e.g. example.com, localhost:8080, or 127.0.0.1).',
            'domain.unique' => 'This domain is already registered in the neural database.',
        ]);

        $domainStr = $domainInput;
        $productId = (int) $request->input('product_id');

        $userId = Auth::id();
        $order = Order::where('user_id', $userId)->where('product_id', $productId)->first();
        $quota = $order ? $order->domain_quota : 3;

        $registeredCount = Domain::where('user_id', $userId)->where('product_id', $productId)->count();
        if ($registeredCount >= $quota) {
            return redirect()->back()->with('error', "Domain registration limit reached for this product (Quota limit: {$quota} domains).");
        }

        Domain::create([
            'user_id' => $userId,
            'product_id' => $productId,
            'domain' => $domainStr,
        ]);

        $productName = Product::find($productId)->name;
        ActivityLog::create([
            'type' => 'domain',
            'description' => "Domain registered  {$domainStr} for {$productName}",
            'user_id' => $userId,
        ]);

        return redirect()->route('dashboard.domain')->with('status', 'Domain ' . $domainStr . ' has been registered and bound successfully!');
    }

    /**
     * Delete / Disconnect a registered domain.
     */
    public function destroyDomain($id)
    {
        $domain = Domain::where('user_id', Auth::id())->findOrFail($id);
        $domainName = $domain->domain;
        $domain->delete();

        ActivityLog::create([
            'type' => 'domain',
            'description' => "Domain deleted : {$domainName}",
            'user_id' => Auth::id(),
        ]);

        return redirect()->route('dashboard.domain')->with('status', 'Domain ' . $domainName . ' has been disconnected.');
    }

    /**
     * Display news and announcements (xNotes) from Post model.
     */
    public function notes(Request $request)
    {
        $user = Auth::user();
        $selectedCategory = $request->query('category');
        $search = $request->query('q');

        $query = Post::where('is_published', true)->latest();

        if ($selectedCategory && $selectedCategory !== 'all') {
            $query->where('category', $selectedCategory);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                  ->orWhere('content', 'like', '%' . $search . '%');
            });
        }

        $posts = $query->paginate(9)->withQueryString();

        $postCounts = [
            'all' => Post::where('is_published', true)->count(),
            'announcement' => Post::where('is_published', true)->where('category', 'announcement')->count(),
            'news' => Post::where('is_published', true)->where('category', 'news')->count(),
            'changelog' => Post::where('is_published', true)->where('category', 'changelog')->count(),
            'tutorial' => Post::where('is_published', true)->where('category', 'tutorial')->count(),
            'promotion' => Post::where('is_published', true)->where('category', 'promotion')->count(),
            'general' => Post::where('is_published', true)->where('category', 'general')->count(),
        ];

        return view('dashboard.notes', compact('user', 'posts', 'selectedCategory', 'search', 'postCounts'));
    }

    /**
     * Display single note/post detail.
     */
    public function noteDetail($slug)
    {
        $user = Auth::user();
        $post = Post::where('slug', $slug)->where('is_published', true)->firstOrFail();
        $recentPosts = Post::where('is_published', true)
            ->where('id', '!=', $post->id)
            ->latest()
            ->take(4)
            ->get();

        return view('dashboard.notes-detail', compact('user', 'post', 'recentPosts'));
    }

    /**
     * Display completed downloads from orders.
     */
    public function download(Request $request)
    {
        $user = Auth::user();

        $orders = Order::where('user_id', $user->id)
            ->where('status', 'completed')
            ->with('product')
            ->latest()
            ->get();

        $orders->each(function ($order) {
            if ($order->product) {
                $rawContents = is_array($order->product->contents) 
                    ? $order->product->contents 
                    : (json_decode($order->product->contents ?? '[]', true) ?: []);

                if (empty($rawContents)) {
                    $rawContents = [[
                        'file' => ($order->product->name ?? 'package') . '.zip',
                        'version' => '1.0.0',
                        'md5sum' => md5($order->product->name ?? uniqid()),
                        'changelog' => 'Standard release build.'
                    ]];
                }

                $evaluated = [];
                foreach ($rawContents as $item) {
                    $fn = $item['file'] ?? '';
                    $fullPath = storage_path('app/private/' . $fn);
                    $exists = !empty($fn) && is_file($fullPath);
                    $sizeHuman = 'Unavailable';

                    if ($exists) {
                        $bytes = filesize($fullPath);
                        $sizeHuman = $bytes >= 1048576 
                            ? round($bytes / 1048576, 2) . ' MB'
                            : round($bytes / 1024, 2) . ' KB';
                    }

                    $item['exists_in_storage'] = $exists;
                    $item['file_size_human'] = $sizeHuman;
                    $evaluated[] = $item;
                }

                $order->product->evaluated_contents = $evaluated;
            }
        });

        return view('dashboard.download', compact('user', 'orders'));
    }

    /**
     * Generate secure payload file for download.
     */
    public function downloadFile(Request $request, $id)
    {
        $user = Auth::user();

        $product = Product::findOrFail($id);

        $order = Order::where('user_id', $user->id)
            ->where('product_id', $id)
            ->where('status', 'completed')
            ->first();

        if (!$order && !$user->isAdmin()) {
            return redirect()->route('dashboard.download')->with('error', 'Clearance denied: You do not hold an authorized license for this payload.');
        }

        $contents = is_array($product->contents) ? $product->contents : json_decode($product->contents, true);
        if (empty($contents)) {
            $contents = [[
                'file' => ($product->name . '.zip'),
                'version' => '1.0.0',
                'md5sum' => md5($product->name),
                'changelog' => 'Initial release build.'
            ]];
        }

        $requestedVersion = $request->query('version');
        $selectedItem = null;

        if ($requestedVersion) {
            foreach ($contents as $item) {
                if (($item['version'] ?? '') === $requestedVersion) {
                    $selectedItem = $item;
                    break;
                }
            }
        }

        if (!$selectedItem) {
            $selectedItem = $contents[0];
        }

        $targetFile = $selectedItem['file'] ?? ($product->name . '.zip');
        $filePath = storage_path('app/private/' . $targetFile);

        if (!is_file($filePath)) {
            return redirect()->route('dashboard.download')
                ->with('error', "Download failed: Payload package '{$targetFile}' does not exist in storage/app/private/ vault.");
        }

        return response()->download($filePath, basename($targetFile), [
            'Content-Type' => 'application/octet-stream',
        ]);
    }

    /**
     * Display cyber store products with purchase status verification.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $products = Product::where('published', true)->latest()->get();

        $purchasedProductIds = Order::where('user_id', $user->id)
            ->where('status', 'completed')
            ->pluck('product_id')
            ->toArray();

        return view('dashboard.store', compact('user', 'products', 'purchasedProductIds'));
    }

    /**
     * Process module acquisition / purchase from xStore.
     */
    public function purchaseProduct(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'payment_method' => 'required|string',
        ]);

        $user = Auth::user();
        $product = Product::findOrFail($request->input('product_id'));

        // Check if already purchased
        $existingOrder = Order::where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->where('status', 'completed')
            ->first();

        if ($existingOrder) {
            return redirect()->route('dashboard.download')->with('status', 'You already own ' . $product->name . '! Redirected to your download vault.');
        }

        $invoice = 'INV-' . strtoupper(bin2hex(random_bytes(3))) . '-' . date('ymd');

        Order::create([
            'invoice' => $invoice,
            'user_id' => $user->id,
            'product_id' => $product->id,
            'price' => $product->price,
            'payment_method' => $request->input('payment_method', 'CyberPay Instant Gateway'),
            'status' => 'completed',
        ]);

        return redirect()->route('dashboard.download')->with('status', 'Module ' . $product->name . ' acquired successfully! Invoice #' . $invoice . ' generated.');
    }

    public function logout()
    {
        Auth::logout();
        return redirect()->route('login');
    }

    /**
     * Update user profile parameters (Name, Password).
     */
    public function updateProfile(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $rules = [
            'name' => ['required', 'string', 'max:255'],
        ];

        if ($request->filled('password')) {
            $rules['current_password'] = ['required', 'string', function ($attribute, $value, $fail) use ($user) {
                if (!Hash::check($value, $user->password)) {
                    $fail('Current password does not match system records.');
                }
            }];
            $rules['password'] = ['required', 'string', 'min:8', 'confirmed'];
        }

        $validated = $request->validate($rules, [
            'name.required' => 'Operative name is required.',
            'current_password.required' => 'Current password is required to set a new password.',
            'password.min' => 'New password must be at least 8 characters long.',
            'password.confirmed' => 'New password confirmation does not match.',
        ]);

        $user->name = $validated['name'];
        if ($request->filled('password')) {
            $user->password = Hash::make($validated['password']);
        }
        $user->save();

        ActivityLog::create([
            'type' => 'account',
            'description' => "Operative profile updated (Name: {$user->name}" . ($request->filled('password') ? ", Password updated" : "") . ")",
            'user_id' => $user->id,
        ]);

        return redirect()->back()->with('status', 'PROFILE UPDATED // Security profile parameters saved successfully.');
    }
}

