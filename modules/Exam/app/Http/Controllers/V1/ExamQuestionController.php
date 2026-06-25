<?php

namespace Modules\Exam\App\Http\Controllers\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Routing\Controller;
use Modules\Exam\App\Models\Exam;
use Modules\Exam\App\Models\ExamQuestion;
use Modules\Exam\App\Models\ExamOption;
use Modules\Exam\App\Http\Requests\V1\ExamQuestionRequest;
use Modules\Exam\App\Http\Resources\V1\ExamQuestionResource;

/**
 * @OA\Tag(name="Exam Questions", description="Manage questions and answer options within an exam")
 */
class ExamQuestionController extends Controller
{

    protected function getResource(): string
    {
        return ExamQuestionResource::class;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/{channel_slug}/exams/{exam_id}/questions",
     *     summary="List questions for an exam",
     *     tags={"Exam Questions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="exam_id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Question list with options"),
     *     @OA\Response(response=404, description="Exam not found"),
     *     @OA\Response(response=403, description="Requires exams.view")
     * )
     */
    public function index(int $examId): JsonResponse
    {
        $exam = Exam::findOrFail($examId);

        $questions = $exam->questions()->with('options')->get();

        return $this->successResponse(
            ExamQuestionResource::collection($questions),
            __('exam.question.retrieved')
        );
    }

    /**
     * @OA\Post(
     *     path="/api/v1/{channel_slug}/exams/{exam_id}/questions",
     *     summary="Add a question to an exam",
     *     tags={"Exam Questions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="exam_id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"question","type"},
     *             @OA\Property(property="question", type="string"),
     *             @OA\Property(property="type", type="string", enum={"mcq","true_false","short_answer","essay"}),
     *             @OA\Property(property="marks", type="number", example=1),
     *             @OA\Property(property="order", type="integer", example=0),
     *             @OA\Property(property="explanation", type="string", nullable=true),
     *             @OA\Property(
     *                 property="options",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="text", type="string"),
     *                     @OA\Property(property="is_correct", type="boolean"),
     *                     @OA\Property(property="order", type="integer")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="Question added"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=403, description="Cannot modify a closed exam")
     * )
     */
    public function store(ExamQuestionRequest $request, int $examId): JsonResponse
    {
        $exam = Exam::findOrFail($examId);

        if ($exam->status === 'closed') {
            return $this->errorResponse(__('exam.cannot_modify_closed'), 422);
        }

        DB::beginTransaction();
        try {
            $channelId = app()->has('current_channel_id')
                ? app('current_channel_id')
                : auth('user')->user()->channel_id;

            $question = ExamQuestion::create([
                'channel_id'  => $channelId,
                'exam_id'     => $exam->id,
                'question'    => $request->question,
                'type'        => $request->type,
                'marks'       => $request->input('marks', 1),
                'order'       => $request->input('order', 0),
                'explanation' => $request->explanation,
            ]);

            if (in_array($request->type, ['mcq', 'true_false'])) {
                foreach ($request->input('options', []) as $opt) {
                    ExamOption::create([
                        'question_id' => $question->id,
                        'text'        => $opt['text'],
                        'is_correct'  => $opt['is_correct'],
                        'order'       => $opt['order'] ?? 0,
                    ]);
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->errorResponse(__('exam.operation_failed'), 500);
        }

        return $this->successResponse(
            new ExamQuestionResource($question->load('options')),
            __('exam.question.created'),
            201
        );
    }

    /**
     * @OA\Get(
     *     path="/api/v1/{channel_slug}/exams/{exam_id}/questions/{id}",
     *     summary="Get a single question",
     *     tags={"Exam Questions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="exam_id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Question with options"),
     *     @OA\Response(response=404, description="Question not found"),
     *     @OA\Response(response=403, description="Requires exams.view")
     * )
     */
    public function show(int $examId, int $id): JsonResponse
    {
        $question = ExamQuestion::with('options')
            ->where('exam_id', $examId)
            ->findOrFail($id);

        return $this->successResponse(
            new ExamQuestionResource($question),
            __('exam.question.retrieved')
        );
    }

    /**
     * @OA\Put(
     *     path="/api/v1/{channel_slug}/exams/{exam_id}/questions/{id}",
     *     summary="Update a question (replaces options for MCQ)",
     *     tags={"Exam Questions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="exam_id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/ExamQuestionRequest")),
     *     @OA\Response(response=200, description="Question updated"),
     *     @OA\Response(response=403, description="Cannot modify a closed exam")
     * )
     */
    public function update(ExamQuestionRequest $request, int $examId, int $id): JsonResponse
    {
        $exam = Exam::findOrFail($examId);

        if ($exam->status === 'closed') {
            return $this->errorResponse(__('exam.cannot_modify_closed'), 422);
        }

        $question = ExamQuestion::where('exam_id', $examId)->findOrFail($id);

        DB::beginTransaction();
        try {
            $question->update($request->only(['question', 'type', 'marks', 'order', 'explanation']));

            if (in_array($request->type, ['mcq', 'true_false']) && $request->has('options')) {
                $question->options()->delete();

                foreach ($request->input('options', []) as $opt) {
                    ExamOption::create([
                        'question_id' => $question->id,
                        'text'        => $opt['text'],
                        'is_correct'  => $opt['is_correct'],
                        'order'       => $opt['order'] ?? 0,
                    ]);
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->errorResponse(__('exam.operation_failed'), 500);
        }

        return $this->successResponse(
            new ExamQuestionResource($question->load('options')),
            __('exam.question.updated')
        );
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/{channel_slug}/exams/{exam_id}/questions/{id}",
     *     summary="Delete a question",
     *     tags={"Exam Questions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="exam_id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Question deleted"),
     *     @OA\Response(response=403, description="Cannot modify a closed exam")
     * )
     */
    public function destroy(int $examId, int $id): JsonResponse
    {
        $exam = Exam::findOrFail($examId);

        if ($exam->status === 'closed') {
            return $this->errorResponse(__('exam.cannot_modify_closed'), 422);
        }

        $question = ExamQuestion::where('exam_id', $examId)->findOrFail($id);
        $question->options()->delete();
        $question->delete();

        return $this->successResponse(null, __('exam.question.deleted'));
    }
}
