<?php

namespace App\Http\Controllers\Backend;

use App\Enums\YesNoEnum;
use App\Helpers\LearningTable;
use App\Helpers\ServerTable;
use App\Http\Controllers\Controller;
use App\Models\AnswerAI;
use App\Models\AnswerAIVote;
use App\Models\Language;
use App\Models\LatexImage;
use App\Models\Lesson;
use App\Models\Question;
use App\Models\Topic;
use App\Models\User;
use App\Models\UserAiUsage;
use Illuminate\Http\Request;

class AiController extends Controller
{
    protected array $aiTabs = [
        'answer',
        'usages',
        'votes',
        'edit',
    ];

    public function getData(Request $request)
    {
        LearningTable::access('ai');
        $sorts = ['id' => 'answer_a_i_s.id', 'language' => Language::select('name')->whereColumn('languages.code', 'answer_a_i_s.locale'),
            'lesson_name' => Lesson::select('name->'.app()->getLocale())->whereColumn('lessons.id', 'answer_a_i_s.lesson_id'),
            'topic_title' => Topic::select('title->'.app()->getLocale())->whereColumn('topics.id', 'answer_a_i_s.topic_id'),
            'attachment' => Question::select('attachment')->whereColumn('questions.id', 'answer_a_i_s.question_id'),
            'votes_count' => 'votes_count', 'votes_avg_vote' => 'votes_avg_vote', 'usages_count' => 'usages_count',
            'created_at' => 'answer_a_i_s.created_at', 'report' => 'answer_a_i_s.report'];
        $parameters = ServerTable::parameters($request, $sorts, 'created_at', [...LearningTable::filters(), 'report' => ['nullable', 'integer', 'in:0,1'], 'status' => ['prohibited']]);
        if (! $request->has('order')) {
            $parameters['order'] = 'desc';
        }
        $query = AnswerAI::select(['id', 'lesson_id', 'topic_id', 'question_id', 'locale', 'title', 'report', 'created_at'])
            ->with(['lesson', 'topic', 'question', 'language'])->withCount(['votes', 'usages'])->withAvg('votes', 'vote');
        LearningTable::filter($query, $parameters);
        if (! empty($parameters['report'])) {
            $query->where('report', 1);
        }
        if ($parameters['search'] !== '') {
            $value = '%'.$parameters['search'].'%';
            $query->where(fn ($query) => $query->where('title', 'like', $value)->orWhere('report', 'like', $value)
                ->orWhereHas('lesson', fn ($lesson) => $lesson->where('name', 'like', $value))
                ->orWhereHas('topic', fn ($topic) => $topic->where('title', 'like', $value))
                ->orWhereHas('question', fn ($question) => $question->where('attachment', 'like', $value)));
        }
        $data = ServerTable::page('ai', $query, $parameters, $sorts, fn (AnswerAI $answer) => [
            'id' => $answer->id, 'title' => $answer->title, 'language' => $answer->language?->name ?? $answer->locale,
            'lesson_name' => $answer->lesson?->name ?? '-', 'topic_title' => $answer->topic?->title ?? '-',
            'attachment' => $answer->question?->attachment && $answer->question->attachment_url !== '' ? asset($answer->question->attachment_url) : null,
            'votes_count' => $answer->votes_count, 'votes_avg_vote' => $answer->votes_avg_vote === null ? null : round((float) $answer->votes_avg_vote, 2), 'usages_count' => $answer->usages_count,
            'created_at' => dateFormat($answer->created_at, 'd/m/Y H:i'),
            'report' => ['label' => YesNoEnum::YES->is($answer->report) ? __('Raporlandı') : __('Raporlanmadı'), 'class' => YesNoEnum::YES->is($answer->report) ? 'danger' : 'success'],
            'view_url' => route('admin.ai.edit', $answer), 'edit_url' => null, 'can_delete' => false,
        ]);

        return response()->json($data)->header('Cache-Control', 'private, no-store');
    }

    public function getUsagesData(Request $request)
    {
        return $this->activityData($request, false);
    }

    public function getVotesData(Request $request)
    {
        return $this->activityData($request, true);
    }

    public function getImagesData(Request $request)
    {
        LearningTable::access('ai');
        $sorts = ['id' => 'latex_images.id', 'formula' => 'latex_images.formula',
            'image' => 'latex_images.image', 'created_at' => 'latex_images.created_at'];
        $parameters = ServerTable::parameters($request, $sorts, 'id', ['status' => ['prohibited']]);
        $query = LatexImage::select(['id', 'formula', 'image', 'created_at']);
        if ($parameters['search'] !== '') {
            $value = '%'.$parameters['search'].'%';
            $query->where(fn ($query) => $query->where('formula', 'like', $value)->orWhere('image', 'like', $value));
        }

        return response()->json(ServerTable::page('ai-latex-images', $query, $parameters, $sorts, fn (LatexImage $record) => [
            'id' => $record->id, 'formula' => $record->formula, 'image' => getImage($record->image),
            'created_at' => dateFormat($record->created_at, 'd/m/Y H:i'), 'hide_detail' => true,
            'edit_url' => auth()->user()->can('ai:update') ? route('admin.ai.imageForm', $record) : null,
            'can_delete' => auth()->user()->can('ai:delete'),
        ]))->header('Cache-Control', 'private, no-store');
    }

    private function activityData(Request $request, bool $votes)
    {
        LearningTable::access('ai');
        $table = $votes ? 'answer_a_i_votes' : 'user_ai_usages';
        $date = $votes ? 'created_at' : 'usage_date';
        $sorts = ['id' => $table.'.id', 'username' => User::select('username')->whereColumn('users.id', $table.'.user_id'),
            'name' => User::select('name')->whereColumn('users.id', $table.'.user_id'), 'surname' => User::select('surname')->whereColumn('users.id', $table.'.user_id'),
            $date => $table.'.'.$date];
        $sorts += $votes ? ['vote' => $table.'.vote'] : ['usage' => $table.'.usage', 'remaining' => $table.'.remaining'];
        $parameters = ServerTable::parameters($request, $sorts, $votes ? 'created_at' : 'id', [
            'answer_ai_id' => ['nullable', 'integer', 'min:0'], 'date' => ['nullable', 'date_format:Y-m-d'],
            'users' => ['sometimes', 'array', 'max:100'], 'users.*' => ['integer', 'distinct', 'exists:users,id'],
        ]);
        if (! $request->has('order')) {
            $parameters['order'] = 'desc';
        }
        if (! empty($parameters['answer_ai_id'])) {
            AnswerAI::findOrFail($parameters['answer_ai_id']);
        }
        $students = LearningTable::studentIds();
        $query = ($votes ? AnswerAIVote::query() : UserAiUsage::query())->with('user:id,username,name,surname')
            ->when($students !== null, fn ($q) => $q->whereIn('user_id', $students));
        if (! empty($parameters['answer_ai_id'])) {
            $query->where('answer_ai_id', $parameters['answer_ai_id']);
        }
        if (! empty($parameters['users'])) {
            $query->whereIn('user_id', $parameters['users']);
        }
        if (! empty($parameters['date'])) {
            $query->whereDate($date, $parameters['date']);
        }
        if ($parameters['search'] !== '') {
            $value = '%'.$parameters['search'].'%';
            $query->whereHas('user', fn ($q) => $q->where('username', 'like', $value)->orWhere('name', 'like', $value)->orWhere('surname', 'like', $value));
        }

        return response()->json(ServerTable::page($votes ? 'ai-votes' : 'ai-usages', $query, $parameters, $sorts, fn ($record) => [
            'id' => $record->id, 'username' => $record->user?->username ?? '-', 'name' => $record->user?->name ?? '-', 'surname' => $record->user?->surname ?? '-',
            ...($votes ? ['vote' => $record->vote] : ['usage' => $record->usage, 'remaining' => $record->remaining]),
            $date => dateFormat($record->$date, 'd/m/Y H:i'), 'hide_detail' => true, 'can_delete' => auth()->user()->can('ai:delete'),
        ], [$students]))->header('Cache-Control', 'private, no-store');
    }

    public function index()
    {
        return view('backend.ai.index', [
            'title' => __('Yapay Zeka'),
        ]);
    }

    public function images()
    {
        return view('backend.ai.images', [
            'title' => __('Latex için Üretilen Görseller'),
        ]);
    }

    public function imageForm(LatexImage $latexImage)
    {
        return view('backend.ai.image-form', [
            'title' => __('Latex Görseli Güncelle'),
            'latexImage' => $latexImage,
        ]);
    }

    public function edit(AnswerAI $answerAI)
    {
        if (! request()->user()->canany(['ai:view'])) {
            abort(403, __('Yanıt güncellenemez, yetkiniz bulunmuyor!'));
        }

        $activeTab = in_array(request()->input('tab'), $this->aiTabs) ? request()->input('tab') : 'answer';

        return view('backend.ai.edit', [
            'title' => __('Yapay Zeka Yanıtı'),
            'answerAI' => $answerAI,
            'activeTab' => $activeTab,
        ]);
    }
}
