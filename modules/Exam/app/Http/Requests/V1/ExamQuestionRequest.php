<?php

namespace Modules\Exam\App\Http\Requests\V1;

use Modules\Channel\App\Http\Requests\V1\BaseRequest;

class ExamQuestionRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'question'    => 'required|string',
            'type'        => 'required|in:mcq,true_false,short_answer,essay',
            'marks'       => 'sometimes|numeric|min:0.5',
            'order'       => 'sometimes|integer|min:0',
            'explanation' => 'nullable|string',

            // Options required for objective questions
            'options'              => 'required_if:type,mcq|required_if:type,true_false|array|min:2',
            'options.*.text'       => 'required|string|max:500',
            'options.*.is_correct' => 'required|boolean',
            'options.*.order'      => 'sometimes|integer|min:0',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $type = $this->input('type');
            $options = $this->input('options', []);

            if (in_array($type, ['mcq', 'true_false']) && count($options) > 0) {
                $correctCount = collect($options)->where('is_correct', true)->count();
                if ($correctCount !== 1) {
                    $v->errors()->add('options', __('exam.question.exactly_one_correct'));
                }
            }
        });
    }
}
