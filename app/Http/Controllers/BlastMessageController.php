<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\BlastMessage;
use App\Models\User;
use App\Services\TelegramService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BlastMessageController extends Controller
{
    /**
     * Display the Blast Message dashboard and history.
     */
    public function index(Request $request): View
    {
        $user = auth()->user();

        // Audience counts
        $totalSubscribers = User::whereNotNull('telegram_id')->where('telegram_id', '!=', '')->count();
        $memberSubscribers = User::whereNotNull('telegram_id')
            ->where('telegram_id', '!=', '')
            ->where(function ($q) {
                $q->where('role', 'user')->orWhere('role', User::ROLE_MEMBER);
            })
            ->count();
        $adminSubscribers = User::whereNotNull('telegram_id')
            ->where('telegram_id', '!=', '')
            ->where(function ($q) {
                $q->where('role', 'admin')->orWhere('role', User::ROLE_ADMIN);
            })
            ->count();
        $orderSubscribers = User::whereNotNull('telegram_id')
            ->where('telegram_id', '!=', '')
            ->whereHas('orders', function ($q) {
                $q->where('status', 'completed');
            })
            ->distinct()
            ->count();

        // Transmission statistics
        $totalBlasts = BlastMessage::count();
        $totalDelivered = (int) BlastMessage::sum('successful_count');
        $totalAttempted = (int) BlastMessage::sum('total_recipients');
        $overallSuccessRate = $totalAttempted > 0 ? round(($totalDelivered / $totalAttempted) * 100, 1) : 0;

        $stats = [
            'total_subscribers' => $totalSubscribers,
            'members' => $memberSubscribers,
            'admins' => $adminSubscribers,
            'orders' => $orderSubscribers,
            'total_blasts' => $totalBlasts,
            'total_delivered' => $totalDelivered,
            'success_rate' => $overallSuccessRate,
        ];

        // Search in blasts history
        $query = BlastMessage::with('user')->latest();
        if ($request->filled('q')) {
            $term = trim($request->input('q'));
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', "%{$term}%")
                  ->orWhere('message', 'like', "%{$term}%")
                  ->orWhere('target_audience', 'like', "%{$term}%");
            });
        }

        $blasts = $query->paginate(12)->withQueryString();

        return view('admin.blast.index', compact('user', 'stats', 'blasts'));
    }

    /**
     * Dispatch broadcast message to Telegram recipients.
     */
    public function send(Request $request, TelegramService $telegramService): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:120',
            'message' => 'required|string|max:4096',
            'target_audience' => 'required|in:all,members,admins,orders,custom,test',
            'custom_chat_ids' => 'required_if:target_audience,custom|nullable|string',
            'test_chat_id' => 'required_if:target_audience,test|nullable|string',
            'parse_mode' => 'nullable|in:Markdown,HTML,plain',
            'button_text' => 'nullable|string|max:64',
            'button_url' => 'nullable|url|max:255',
            'image_url' => 'nullable|url|max:500',
        ]);

        $targetAudience = $validated['target_audience'];
        $parseMode = $validated['parse_mode'] ?? 'Markdown';
        $message = trim($validated['message']);

        // Collect recipients based on selected target audience
        $recipients = match ($targetAudience) {
            'all' => User::whereNotNull('telegram_id')
                ->where('telegram_id', '!=', '')
                ->get(['id', 'name', 'telegram_id', 'telegram_username'])
                ->map(fn ($u) => [
                    'user_id' => $u->id,
                    'chat_id' => $u->telegram_id,
                    'name' => $u->name,
                    'username' => $u->telegram_username,
                ])->all(),

            'members' => User::whereNotNull('telegram_id')
                ->where('telegram_id', '!=', '')
                ->where(function ($q) {
                    $q->where('role', 'user')->orWhere('role', User::ROLE_MEMBER);
                })
                ->get(['id', 'name', 'telegram_id', 'telegram_username'])
                ->map(fn ($u) => [
                    'user_id' => $u->id,
                    'chat_id' => $u->telegram_id,
                    'name' => $u->name,
                    'username' => $u->telegram_username,
                ])->all(),

            'admins' => User::whereNotNull('telegram_id')
                ->where('telegram_id', '!=', '')
                ->where(function ($q) {
                    $q->where('role', 'admin')->orWhere('role', User::ROLE_ADMIN);
                })
                ->get(['id', 'name', 'telegram_id', 'telegram_username'])
                ->map(fn ($u) => [
                    'user_id' => $u->id,
                    'chat_id' => $u->telegram_id,
                    'name' => $u->name,
                    'username' => $u->telegram_username,
                ])->all(),

            'orders' => User::whereNotNull('telegram_id')
                ->where('telegram_id', '!=', '')
                ->whereHas('orders', function ($q) {
                    $q->where('status', 'completed');
                })
                ->distinct()
                ->get(['id', 'name', 'telegram_id', 'telegram_username'])
                ->map(fn ($u) => [
                    'user_id' => $u->id,
                    'chat_id' => $u->telegram_id,
                    'name' => $u->name,
                    'username' => $u->telegram_username,
                ])->all(),

            'custom' => array_values(array_filter(array_map(function ($item) {
                $t = trim($item);
                return !empty($t) ? ['chat_id' => $t, 'name' => 'Custom ID: ' . $t] : null;
            }, preg_split('/[\r\n,]+/', (string) ($validated['custom_chat_ids'] ?? ''))))),

            'test' => [
                [
                    'chat_id' => trim($validated['test_chat_id'] ?: (auth()->user()?->telegram_id ?? '')),
                    'name' => 'Test: ' . (auth()->user()?->name ?? 'Admin'),
                ],
            ],

            default => [],
        };

        // Deduplicate recipients by chat_id
        $uniqueRecipients = [];
        $seen = [];
        foreach ($recipients as $item) {
            $cid = trim((string) ($item['chat_id'] ?? ''));
            if (!empty($cid) && !isset($seen[$cid])) {
                $seen[$cid] = true;
                $uniqueRecipients[] = $item;
            }
        }

        if (empty($uniqueRecipients)) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'No eligible Telegram recipients found for target audience: ' . ucfirst($targetAudience));
        }

        // Prepare optional reply markup
        $replyMarkup = null;
        if (!empty($validated['button_text']) && !empty($validated['button_url'])) {
            $replyMarkup = [
                'inline_keyboard' => [
                    [
                        [
                            'text' => $validated['button_text'],
                            'url' => $validated['button_url'],
                        ],
                    ],
                ],
            ];
        }

        // Execute broadcast
        $result = $telegramService->broadcast(
            $uniqueRecipients,
            $message,
            $parseMode,
            $replyMarkup,
            $validated['image_url'] ?? null
        );

        $status = 'completed';
        if ($result['successful'] === 0 && $result['failed'] > 0) {
            $status = 'failed';
        } elseif ($result['failed'] > 0) {
            $status = 'partial';
        }

        $title = $validated['title'] ?? null;
        if (empty($title)) {
            $title = 'Broadcast: ' . ucfirst($targetAudience) . ' (' . count($uniqueRecipients) . ' recipients)';
        }

        $blast = BlastMessage::create([
            'user_id' => auth()->id(),
            'title' => $title,
            'message' => $message,
            'parse_mode' => $parseMode,
            'target_audience' => $targetAudience,
            'button_text' => $validated['button_text'] ?? null,
            'button_url' => $validated['button_url'] ?? null,
            'image_url' => $validated['image_url'] ?? null,
            'total_recipients' => $result['total'],
            'successful_count' => $result['successful'],
            'failed_count' => $result['failed'],
            'recipient_details' => $result['details'],
            'status' => $status,
            'is_test' => $targetAudience === 'test',
        ]);

        Activity::create([
            'user_id' => auth()->id(),
            'action' => 'BLAST_MESSAGE',
            'description' => "Dispatched blast message '{$title}' to {$result['total']} Telegram recipients ({$result['successful']} delivered, {$result['failed']} failed)",
            'properties' => [
                'blast_id' => $blast->id,
                'target' => $targetAudience,
                'total' => $result['total'],
                'successful' => $result['successful'],
                'failed' => $result['failed'],
            ],
        ]);

        $feedbackMsg = "Broadcast executed! Delivered to {$result['successful']} of {$result['total']} Telegram recipients.";
        if ($result['failed'] > 0) {
            $feedbackMsg .= " ({$result['failed']} failed). Check history logs for details.";
        }

        if ($result['successful'] === 0 && $result['failed'] > 0) {
            return redirect()->route('admin.blast-message')
                ->with('error', "Broadcast failed for all {$result['failed']} recipients. Check transmission logs for errors.");
        }

        return redirect()->route('admin.blast-message')
            ->with('status', $feedbackMsg);
    }

    /**
     * Preview recipients count and sample for a target audience (AJAX).
     */
    public function previewRecipients(Request $request): JsonResponse
    {
        $targetAudience = $request->query('target', 'all');

        $query = match ($targetAudience) {
            'all' => User::whereNotNull('telegram_id')->where('telegram_id', '!=', ''),
            'members' => User::whereNotNull('telegram_id')
                ->where('telegram_id', '!=', '')
                ->where(function ($q) {
                    $q->where('role', 'user')->orWhere('role', User::ROLE_MEMBER);
                }),
            'admins' => User::whereNotNull('telegram_id')
                ->where('telegram_id', '!=', '')
                ->where(function ($q) {
                    $q->where('role', 'admin')->orWhere('role', User::ROLE_ADMIN);
                }),
            'orders' => User::whereNotNull('telegram_id')
                ->where('telegram_id', '!=', '')
                ->whereHas('orders', function ($q) {
                    $q->where('status', 'completed');
                }),
            default => null,
        };

        if ($query === null) {
            return response()->json([
                'success' => true,
                'count' => 0,
                'sample' => [],
            ]);
        }

        $count = $query->count();
        $sample = $query->limit(8)->get(['id', 'name', 'telegram_username', 'telegram_id'])->map(function ($u) {
            return [
                'name' => $u->name,
                'telegram' => $u->telegram_username ? '@' . $u->telegram_username : 'ID: ' . $u->telegram_id,
            ];
        });

        return response()->json([
            'success' => true,
            'count' => $count,
            'sample' => $sample,
        ]);
    }

    /**
     * View detailed JSON breakdown of a past blast message.
     */
    public function show($id): JsonResponse
    {
        $blast = BlastMessage::with('user')->findOrFail($id);

        return response()->json([
            'success' => true,
            'blast' => [
                'id' => $blast->id,
                'title' => $blast->title,
                'message' => $blast->message,
                'parse_mode' => $blast->parse_mode,
                'target_audience' => $blast->target_audience_label,
                'button_text' => $blast->button_text,
                'button_url' => $blast->button_url,
                'image_url' => $blast->image_url,
                'total_recipients' => $blast->total_recipients,
                'successful_count' => $blast->successful_count,
                'failed_count' => $blast->failed_count,
                'success_rate' => $blast->success_rate . '%',
                'status' => $blast->status,
                'is_test' => $blast->is_test,
                'created_at' => $blast->created_at->format('Y-m-d H:i:s'),
                'sender' => $blast->user?->name ?? 'System',
                'details' => $blast->recipient_details ?? [],
            ],
        ]);
    }

    /**
     * Delete a broadcast history record.
     */
    public function destroy($id): RedirectResponse
    {
        $blast = BlastMessage::findOrFail($id);
        $blast->delete();

        return redirect()->route('admin.blast-message')
            ->with('status', 'Blast history record deleted successfully.');
    }
}
