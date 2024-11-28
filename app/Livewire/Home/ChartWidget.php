<?php

namespace App\Livewire\Home;

use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\Lesson;
use Livewire\Attributes\Lazy;
use Livewire\Component;

#[Lazy(isolate: true)]
class ChartWidget extends Component
{
    public string $id = 'chart';
    public string $title = '';
    public string $subtitle = '';

    public function popularHits(): array
    {
        $exams = cache()->remember(
            'home_chart_popularHits',
            60 * 60 * 2,
            fn() => Exam::with('language')->active()->orderByDesc('hits')->limit(8)->get()
        );

        return [
            [
                'name' => __('Görüntüleme'),
                'data' => [
                    ...collect($exams)->map(function ($exam) {
                        return [
                            'x' => str($exam->name)->limit(10),
                            'y' => $exam->hits
                        ];
                    })->toArray()
                ]
            ]
        ];
    }

    public function popularResults(): array
    {
        $results = cache()->remember(
            'home_chart_popularResults',
            60 * 60 * 2,
            fn() => ExamResult::selectRaw('id, exam_id, SUM(question_count) as question_count, SUM(correct_count) as correct_count, SUM(incorrect_count) as incorrect_count')
                ->with('exam')
                ->groupBy('exam_id')
                ->orderByRaw('question_count DESC')
                ->limit(8)
                ->get()
        );

        $labels = [
            ['key' => 'question_count', 'name' => __('Soru Sayısı')],
            ['key' => 'correct_count', 'name' => __('Doğru')],
            ['key' => 'incorrect_count', 'name' => __('Yanlış')]
        ];


        return collect($labels)->map(function ($label) use ($results) {
            return [
                'name' => $label['name'],
                'data' => [
                    ...collect($results)->map(function ($result) use($label) {
                        return [
                            'x' => str($result->exam?->name)->limit(10),
                            'y' => $result->{$label['key']}
                        ];
                    })->toArray()
                ]
            ];
        })->toArray();
    }

    public function popularLessons(): array
    {
        $lessons = cache()->remember(
            'home-chart_popularLessons',
            60 * 60 * 2,
            fn() => Lesson::orderByDesc('hits')->limit(8)->get()
        );

        return [
            [
                'name' => __('Görüntüleme'),
                'data' => [
                    ...collect($lessons)->map(function ($lesson) {
                        return [
                            'x' => str($lesson->name)->limit(10),
                            'y' => $lesson->hits
                        ];
                    })->toArray()
                ]
            ]
        ];
    }

    public function render()
    {
        $this->dispatch('chart-render', [
            'options' => [
                'series' => method_exists($this, $this->id) ? $this->{$this->id}() : [],
                'chart' => [
                    'type' => 'bar',
                    'height' => 300,
                    'animations' => [
                        'enabled' => true,
                        'speed' => 1100
                    ],
                    'toolbar' => [
                        'show' => true,
                    ]
                ],
                'colors' => [settings()->chartColor ?? '#000', settings()->primaryColor ?? '#000', settings()->secondColor ?? '#000'],
                'xaxis' => [
                    'labels' => [
                        'rotate' => -45,
                        'rotateAlways' => false
                    ]
                ],
                'yaxis' => [
                    'title' => [
                        'text' => $this->subtitle
                    ],
                ],
                'legend' => [
                    'position' => 'bottom'
                ]
            ],
            'id' => $this->id
        ]);

        return view('livewire.backend.home.chart-widget');
    }
}
