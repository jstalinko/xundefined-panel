<?php

namespace App\Http\Controllers;

use App\Models\FreeTool;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class FreeToolController extends Controller
{
    public const ALLOWED_EXTENSIONS = ['.txt', '.zip', '.rar', '.tar.gz'];

    /**
     * Display a listing of free tools in the admin panel.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $search = $request->query('q');
        $type = $request->query('type');

        $query = FreeTool::latest();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%')
                  ->orWhere('url', 'like', '%' . $search . '%')
                  ->orWhere('file_name', 'like', '%' . $search . '%');
            });
        }

        if ($type && in_array($type, [FreeTool::TYPE_FILE, FreeTool::TYPE_URL], true)) {
            $query->where('type', $type);
        }

        $freeTools = $query->paginate(15)->withQueryString();

        $stats = [
            'total' => FreeTool::count(),
            'active' => FreeTool::where('is_active', true)->count(),
            'file_count' => FreeTool::where('type', FreeTool::TYPE_FILE)->count(),
            'url_count' => FreeTool::where('type', FreeTool::TYPE_URL)->count(),
        ];

        return view('admin.free-tool.index', compact('user', 'freeTools', 'search', 'type', 'stats'));
    }

    /**
     * Show the form for creating a new free tool.
     */
    public function create()
    {
        $user = Auth::user();
        return view('admin.free-tool.create', compact('user'));
    }

    /**
     * Store a newly created free tool in storage.
     */
    public function store(Request $request)
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'string', 'in:file,url'],
            'is_active' => ['nullable'],
        ];

        if ($request->input('type') === FreeTool::TYPE_URL) {
            $rules['url'] = ['required', 'url', 'max:2048'];
        } else {
            $rules['file'] = [
                'required',
                'file',
                'max:51200',
                function (string $attribute, mixed $value, \Closure $fail) {
                    if ($value instanceof UploadedFile && !$this->hasAllowedExtension($value->getClientOriginalName())) {
                        $fail('The uploaded file must be a .txt, .zip, .rar, or .tar.gz file.');
                    }
                },
            ];
        }

        $validated = $request->validate($rules);

        $data = [
            'name' => trim($validated['name']),
            'slug' => Str::slug($validated['name']) . '-' . Str::lower(Str::random(5)),
            'description' => $validated['description'] ?? null,
            'type' => $validated['type'],
            'is_active' => $request->boolean('is_active', true),
        ];

        if ($validated['type'] === FreeTool::TYPE_URL) {
            $data['url'] = trim($validated['url']);
            $data['file_path'] = null;
            $data['file_name'] = null;
            $data['file_size'] = null;
        } else {
            /** @var UploadedFile $uploadedFile */
            $uploadedFile = $request->file('file');
            $stored = $this->storeUploadedToolFile($uploadedFile);
            $data['url'] = null;
            $data['file_path'] = $stored['file_path'];
            $data['file_name'] = $stored['file_name'];
            $data['file_size'] = $stored['file_size'];
        }

        $tool = FreeTool::create($data);

        return redirect()->route('free-tool.index')->with('status', "Free tool '{$tool->name}' created successfully!");
    }

    /**
     * Display/redirect single free tool.
     */
    public function show(string $id)
    {
        return redirect()->route('free-tool.edit', $id);
    }

    /**
     * Show the form for editing the specified free tool.
     */
    public function edit(string $id)
    {
        $user = Auth::user();
        $freeTool = FreeTool::findOrFail($id);

        return view('admin.free-tool.edit', compact('user', 'freeTool'));
    }

    /**
     * Update the specified free tool in storage.
     */
    public function update(Request $request, string $id)
    {
        $freeTool = FreeTool::findOrFail($id);

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'string', 'in:file,url'],
            'is_active' => ['nullable'],
        ];

        if ($request->input('type') === FreeTool::TYPE_URL) {
            $rules['url'] = ['required', 'url', 'max:2048'];
        } else {
            $fileRule = empty($freeTool->file_path) ? 'required' : 'nullable';
            $rules['file'] = [
                $fileRule,
                'file',
                'max:51200',
                function (string $attribute, mixed $value, \Closure $fail) {
                    if ($value instanceof UploadedFile && !$this->hasAllowedExtension($value->getClientOriginalName())) {
                        $fail('The uploaded file must be a .txt, .zip, .rar, or .tar.gz file.');
                    }
                },
            ];
        }

        $validated = $request->validate($rules);

        $data = [
            'name' => trim($validated['name']),
            'description' => $validated['description'] ?? null,
            'type' => $validated['type'],
            'is_active' => $request->boolean('is_active', false),
        ];

        if ($validated['type'] === FreeTool::TYPE_URL) {
            if (!empty($freeTool->file_path)) {
                $this->deleteStoredFile($freeTool->file_path);
            }
            $data['url'] = trim($validated['url']);
            $data['file_path'] = null;
            $data['file_name'] = null;
            $data['file_size'] = null;
        } else {
            $data['url'] = null;
            if ($request->hasFile('file')) {
                if (!empty($freeTool->file_path)) {
                    $this->deleteStoredFile($freeTool->file_path);
                }
                /** @var UploadedFile $uploadedFile */
                $uploadedFile = $request->file('file');
                $stored = $this->storeUploadedToolFile($uploadedFile);
                $data['file_path'] = $stored['file_path'];
                $data['file_name'] = $stored['file_name'];
                $data['file_size'] = $stored['file_size'];
            }
        }

        $freeTool->update($data);

        return redirect()->route('free-tool.index')->with('status', "Free tool '{$freeTool->name}' updated successfully!");
    }

    /**
     * Remove the specified free tool from storage.
     */
    public function destroy(string $id)
    {
        $freeTool = FreeTool::findOrFail($id);
        $name = $freeTool->name;

        if (!empty($freeTool->file_path)) {
            $this->deleteStoredFile($freeTool->file_path);
        }

        $freeTool->delete();

        return redirect()->route('free-tool.index')->with('status', "Free tool '{$name}' deleted.");
    }

    /**
     * Download file for a file-based free tool from Admin Panel.
     */
    public function download(string $id)
    {
        $freeTool = FreeTool::findOrFail($id);

        if (!$freeTool->isFile() || empty($freeTool->file_path)) {
            abort(404, 'File not found for this tool.');
        }

        $fullPath = storage_path('app/private/' . ltrim($freeTool->file_path, '/'));
        if (!is_file($fullPath)) {
            abort(404, 'File does not exist on disk.');
        }

        return response()->download($fullPath, $freeTool->file_name ?: basename($fullPath));
    }

    /**
     * Check if filename ends with one of the allowed extensions (.txt, .zip, .rar, .tar.gz).
     */
    public function hasAllowedExtension(string $filename): bool
    {
        $lower = strtolower(trim($filename));
        foreach (self::ALLOWED_EXTENSIONS as $ext) {
            if (str_ends_with($lower, $ext)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Extract matched allowed extension from original filename.
     */
    protected function extractExtension(string $filename): string
    {
        $lower = strtolower(trim($filename));
        if (str_ends_with($lower, '.tar.gz')) {
            return '.tar.gz';
        }
        foreach (self::ALLOWED_EXTENSIONS as $ext) {
            if (str_ends_with($lower, $ext)) {
                return $ext;
            }
        }
        return '.' . pathinfo($filename, PATHINFO_EXTENSION);
    }

    /**
     * Store uploaded file in storage/app/private/free-tools.
     */
    protected function storeUploadedToolFile(UploadedFile $file): array
    {
        $originalName = $file->getClientOriginalName();
        $ext = $this->extractExtension($originalName);
        $baseName = substr($originalName, 0, strlen($originalName) - strlen($ext));
        $safeBase = Str::slug($baseName) ?: 'tool-file';
        $storedFileName = $safeBase . '-' . time() . '-' . Str::lower(Str::random(4)) . $ext;

        $targetDir = storage_path('app/private/free-tools');
        if (!is_dir($targetDir)) {
            @mkdir($targetDir, 0755, true);
        }

        $size = $file->getSize() ?: 0;
        $file->move($targetDir, $storedFileName);
        $fullPath = $targetDir . DIRECTORY_SEPARATOR . $storedFileName;
        if (!$size && is_file($fullPath)) {
            $size = filesize($fullPath) ?: 0;
        }

        return [
            'file_path' => 'free-tools/' . $storedFileName,
            'file_name' => $originalName,
            'file_size' => $size,
        ];
    }

    /**
     * Delete file from storage/app/private.
     */
    protected function deleteStoredFile(string $relativePath): void
    {
        $fullPath = storage_path('app/private/' . ltrim($relativePath, '/'));
        if (is_file($fullPath)) {
            @unlink($fullPath);
        }
    }
}
