<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    /**
     * Display a listing of all products for admin.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $search = $request->query('q');
        $statusFilter = $request->query('status');

        $query = Product::latest();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        if ($statusFilter !== null && $statusFilter !== '') {
            $query->where('active', (bool) $statusFilter);
        }

        $products = $query->paginate(10)->withQueryString();

        $stats = [
            'total' => Product::count(),
            'active' => Product::where('active', true)->count(),
            'inactive' => Product::where('active', false)->count(),
        ];

        return view('admin.product.index', compact('user', 'products', 'search', 'statusFilter', 'stats'));
    }

    /**
     * Show the form for creating a new product.
     */
    public function create()
    {
        $user = Auth::user();
        $storageFiles = $this->getStorageFiles();
        return view('admin.product.create', compact('user', 'storageFiles'));
    }

    /**
     * Store a newly created product in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'active' => ['nullable'],
            'contents_raw' => ['nullable', 'string'],
            'releases' => ['nullable', 'array'],
        ]);

        $contents = $this->parseContents($request);

        $product = Product::create([
            'name' => trim($validated['name']),
            'price' => (int) $validated['price'],
            'description' => $validated['description'] ?? null,
            'active' => $request->boolean('active', true),
            'contents' => $contents,
        ]);

        $redirectRoute = routeExists('admin.product.index') ? 'admin.product.index' : 'product.index';
        return redirect()->route($redirectRoute)->with('status', "Product '{$product->name}' created successfully!");
    }

    /**
     * Display the specified product.
     */
    public function show(string $id)
    {
        $user = Auth::user();
        $product = Product::findOrFail($id);

        return view('admin.product.show', compact('user', 'product'));
    }

    /**
     * Show the form for editing the specified product.
     */
    public function edit(string $id)
    {
        $user = Auth::user();
        $product = Product::findOrFail($id);
        $storageFiles = $this->getStorageFiles();

        return view('admin.product.edit', compact('user', 'product', 'storageFiles'));
    }

    /**
     * Update the specified product in storage.
     */
    public function update(Request $request, string $id)
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'active' => ['nullable'],
            'contents_raw' => ['nullable', 'string'],
            'releases' => ['nullable', 'array'],
        ]);

        $contents = $this->parseContents($request);

        $product->update([
            'name' => trim($validated['name']),
            'price' => (int) $validated['price'],
            'description' => $validated['description'] ?? null,
            'active' => $request->boolean('active', false),
            'contents' => $contents,
        ]);

        $redirectRoute = routeExists('admin.product.index') ? 'admin.product.index' : 'product.index';
        return redirect()->route($redirectRoute)->with('status', "Product '{$product->name}' updated successfully!");
    }

    /**
     * Remove the specified product from storage.
     */
    public function destroy(string $id)
    {
        $product = Product::findOrFail($id);
        $name = $product->name;
        $product->delete();

        $redirectRoute = routeExists('admin.product.index') ? 'admin.product.index' : 'product.index';
        return redirect()->route($redirectRoute)->with('status', "Product '{$name}' has been deleted.");
    }

    /**
     * Helper to parse dynamic releases or raw JSON input into array format.
     */
    protected function parseContents(Request $request): array
    {
        // 1. Check if structured dynamic releases array is present
        if ($request->has('releases') && is_array($request->input('releases'))) {
            $parsed = [];
            foreach ($request->input('releases') as $rel) {
                if (!empty($rel['file']) || !empty($rel['version'])) {
                    $parsed[] = [
                        'file' => trim($rel['file'] ?? 'package.zip'),
                        'version' => trim($rel['version'] ?? '1.0.0'),
                        'changelog' => trim($rel['changelog'] ?? 'Standard release.'),
                        'md5sum' => trim($rel['md5sum'] ?? md5($rel['file'] ?? uniqid())),
                    ];
                }
            }
            if (!empty($parsed)) {
                return $parsed;
            }
        }

        // 2. Check if raw JSON was provided
        if ($request->filled('contents_raw')) {
            $json = json_decode($request->input('contents_raw'), true);
            if (is_array($json)) {
                return $json;
            }
        }

        // 3. Fallback default single release package
        return [
            [
                'file' => strtolower(preg_replace('/[^a-zA-Z0-9]/', '-', $request->input('name', 'product'))) . '-v1.0.0.zip',
                'version' => '1.0.0',
                'changelog' => 'Initial release package.',
                'md5sum' => md5(uniqid()),
            ]
        ];
    }

    /**
     * Get list of available files in storage/app/private.
     */
    protected function getStorageFiles(): array
    {
        $privateDir = storage_path('app/private');
        if (!is_dir($privateDir)) {
            @mkdir($privateDir, 0755, true);
        }

        $files = [];
        if (is_dir($privateDir)) {
            $scanned = scandir($privateDir);
            foreach ($scanned as $f) {
                if ($f === '.' || $f === '..' || $f === '.gitignore') {
                    continue;
                }
                $fullPath = $privateDir . DIRECTORY_SEPARATOR . $f;
                if (is_file($fullPath)) {
                    $bytes = filesize($fullPath);
                    $humanSize = $bytes >= 1048576 
                        ? round($bytes / 1048576, 2) . ' MB'
                        : round($bytes / 1024, 2) . ' KB';
                    $files[] = [
                        'filename' => $f,
                        'size' => $bytes,
                        'size_human' => $humanSize,
                        'md5' => md5_file($fullPath) ?: md5($f),
                    ];
                }
            }
        }

        return $files;
    }
}

/**
 * Check if named route exists in Route collection.
 */
function routeExists($name): bool
{
    return \Illuminate\Support\Facades\Route::has($name);
}

