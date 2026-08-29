<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class PostController extends Controller
{
    /**
     * Display a listing of posts for admin.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $search = $request->query('q');
        $category = $request->query('category');

        $query = Post::latest();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                  ->orWhere('content', 'like', '%' . $search . '%');
            });
        }

        if ($category && $category !== 'all') {
            $query->where('category', $category);
        }

        $posts = $query->paginate(15)->withQueryString();

        $stats = [
            'total' => Post::count(),
            'published' => Post::where('is_published', true)->count(),
        ];

        return view('admin.post.index', compact('user', 'posts', 'search', 'category', 'stats'));
    }

    /**
     * Show the form for creating a new post.
     */
    public function create()
    {
        $user = Auth::user();
        return view('admin.post.create', compact('user'));
    }

    /**
     * Store a newly created post in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'category' => ['required', 'string', 'in:tutorial,changelog,announcement,news,general,promotion'],
            'image' => ['nullable', 'string'],
            'is_published' => ['nullable'],
        ]);

        $slug = Str::slug($validated['title']) . '-' . Str::random(5);

        $post = Post::create([
            'slug' => $slug,
            'title' => trim($validated['title']),
            'content' => $validated['content'],
            'category' => $validated['category'],
            'image' => $validated['image'] ?? '/no-image.svg',
            'is_published' => $request->boolean('is_published', true),
        ]);

        return redirect()->route('post.index')->with('status', "Post '{$post->title}' created successfully!");
    }

    /**
     * Show the form for editing the post.
     */
    public function edit(string $id)
    {
        $user = Auth::user();
        $post = Post::findOrFail($id);

        return view('admin.post.edit', compact('user', 'post'));
    }

    /**
     * Update the specified post in storage.
     */
    public function update(Request $request, string $id)
    {
        $post = Post::findOrFail($id);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'category' => ['required', 'string', 'in:tutorial,changelog,announcement,news,general,promotion'],
            'image' => ['nullable', 'string'],
            'is_published' => ['nullable'],
        ]);

        $post->update([
            'title' => trim($validated['title']),
            'content' => $validated['content'],
            'category' => $validated['category'],
            'image' => $validated['image'] ?? $post->image,
            'is_published' => $request->boolean('is_published', false),
        ]);

        return redirect()->route('post.index')->with('status', "Post '{$post->title}' updated successfully!");
    }

    /**
     * Remove the specified post from storage.
     */
    public function destroy(string $id)
    {
        $post = Post::findOrFail($id);
        $title = $post->title;
        $post->delete();

        return redirect()->route('post.index')->with('status', "Post '{$title}' deleted.");
    }
}
