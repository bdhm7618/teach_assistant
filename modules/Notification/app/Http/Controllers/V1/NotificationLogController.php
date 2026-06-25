<?php

namespace Modules\Notification\App\Http\Controllers\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Prettus\Repository\Eloquent\BaseRepository;
use Modules\Channel\App\Http\Controllers\V1\BaseController;
use Modules\Notification\App\Models\NotificationLog;
use Modules\Notification\App\Http\Resources\V1\NotificationLogResource;
use Modules\Notification\App\Notifications\InvoiceOverdueNotification;
use Modules\Notification\App\Services\NotificationService;
use Modules\Payment\App\Models\Invoice;

/**
 * @OA\Tag(name="Notifications", description="Notification log and manual dispatch endpoints")
 */
class NotificationLogController extends BaseController
{
    public function __construct(protected NotificationService $notificationService) {}

    protected function getRepository(): BaseRepository
    {
        throw new \LogicException('NotificationLogController does not use a repository.');
    }

    protected function getResource(): string
    {
        return NotificationLogResource::class;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/{channel_slug}/notifications",
     *     summary="List notification logs",
     *     tags={"Notifications"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="type", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="status", in="query", required=false, @OA\Schema(type="string", enum={"sent","failed"})),
     *     @OA\Parameter(name="notifiable_id", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer", default=20)),
     *     @OA\Response(response=200, description="Notification log list"),
     *     @OA\Response(response=403, description="Requires reports.view")
     * )
     */
    public function show($id): JsonResponse
    {
        return successResponse(null);
    }

    public function index(Request $request): JsonResponse
    {
        $query = NotificationLog::query()
            ->when($request->input('type'),          fn($q, $v) => $q->where('type', $v))
            ->when($request->input('status'),        fn($q, $v) => $q->where('status', $v))
            ->when($request->input('notifiable_id'), fn($q, $v) => $q->where('notifiable_id', $v))
            ->latest();

        $data = $query->paginate($request->input('per_page', 20));

        return successResponse(
            NotificationLogResource::collection($data)->response()->getData(true)
        );
    }

    /**
     * @OA\Post(
     *     path="/api/v1/{channel_slug}/notifications/send-overdue-reminders",
     *     summary="Manually trigger overdue invoice reminders",
     *     tags={"Notifications"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Reminders sent"),
     *     @OA\Response(response=403, description="Requires reports.view")
     * )
     */
    public function sendOverdueReminders(): JsonResponse
    {
        $channelId = app('current_channel_id');

        $overdueInvoices = Invoice::where('channel_id', $channelId)
            ->where('status', 'overdue')
            ->with('student')
            ->get();

        $sent = 0;

        foreach ($overdueInvoices as $invoice) {
            $student = $invoice->student;

            if (!$student || !$student->email) {
                continue;
            }

            $daysOverdue = now()->diffInDays($invoice->due_date ?? now(), false);

            $this->notificationService->send(
                $student,
                new InvoiceOverdueNotification(
                    studentName:     $student->name,
                    channelName:     app('current_channel')->name ?? 'Your Center',
                    remainingAmount: (float) $invoice->remaining_amount,
                    dueDate:         $invoice->due_date?->toDateString() ?? '',
                    daysOverdue:     abs((int) $daysOverdue),
                ),
                $channelId,
                'invoice_overdue'
            );

            $sent++;
        }

        return successResponse(
            ['reminders_sent' => $sent],
            __('notification::app.overdue_reminders_sent', ['count' => $sent])
        );
    }
}
