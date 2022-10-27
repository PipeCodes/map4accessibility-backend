<?php

namespace App\Http\Livewire;

use App\Models\RateAnswer;
use App\Models\RateQuestion;
use Closure;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Illuminate\Support\Str;
use Livewire\Component;

class RateQuestionForm extends Component implements HasForms
{
    use InteractsWithForms;

    public $original = [];
    public $questions = [];

    public function initialize()
    {
        $questions = RateQuestion::with('answers')->get()->toArray();

        $this->original = $questions;
        $this->fill([
            'questions' =>  $questions
        ]);
    }

    public function mount(): void
    {
        $this->initialize();
    }

    protected function getFormSchema(): array
    {
        return [
            Repeater::make('questions')
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('title')
                            ->required()
                            ->label('Question Title')
                            ->placeholder('Question Title')
                            ->reactive()
                            ->afterStateUpdated(function (Closure $set, $state) {
                                $set('slug', Str::slug($state));
                            }),
                        TextInput::make('slug')
                            ->hidden(),
                        Select::make('place_type')
                            ->required()
                            ->label('Place Type')
                            ->options([
                                'type1' => 'Type 1',
                                'type2' => 'Type 2',
                                'type3' => 'Type 3',
                            ]),
                    ]),
                    Repeater::make('answers')
                        ->schema([
                            TextInput::make('order')
                                ->numeric()
                                ->minValue(1)
                                ->maxValue(4)
                                ->label('Answer Order')
                                ->placeholder('Answer Order')
                                ->required(),
                            TextInput::make('body')
                                ->label('Answer Text')
                                ->placeholder('Answer Text')
                                ->reactive()
                                ->afterStateUpdated(function (Closure $set, $state) {
                                    $set('slug', Str::slug($state));
                                })
                                ->required(),
                            TextInput::make('slug')
                                ->disabled()
                        ])
                        ->columns(3)
                        ->maxItems(4)
                        ->disableItemMovement()
                        ->itemLabel(fn (array $state): ?string => $state['body'] ?? null)

                ])
                ->disableItemMovement()
                ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                ->collapsible()
        ];
    }

    protected function deleteQuestions($state) 
    {
        $questionsToDelete = collect($this->original)
            ->whereNotIn(
                'id', 
                collect($state)->pluck('id')
            )
            ->pluck('id');
        
        if (count($questionsToDelete) > 0) {
            RateQuestion::destroy($questionsToDelete->toArray());
        }
    }

    public function submit(): void
    {
        $current = $this->form->getState()['questions'];

        $this->deleteQuestions($current);
        
        $answersToDelete = collect();
        
        foreach ($current as $question) {
            /**
             * Update Question
             */
            if (array_key_exists('id', $question)) {
                $original = collect($this->original)
                    ->firstWhere('id', $question['id']);

                $answersToDelete = $answersToDelete->merge(
                    collect($original['answers'])
                        ->whereNotIn(
                            'id', 
                            collect($question['answers'])->pluck('id')
                        )
                        ->pluck('id')
                );

                foreach ($question['answers'] as $answer) {
                    if (array_key_exists('id', $answer)) {
                        /**
                         * Update Answer
                         */
                        RateAnswer::where('id', $answer['id'])
                            ->update([
                                'order' => $answer['order'],
                                'body' => $answer['body'],
                                'slug' => $answer['slug'],
                            ]);
                    } else {
                        /**
                         * Create Answer
                         */
                        RateAnswer::create([
                            ...$answer,
                            'rate_question_id' => $question['id']
                        ]);
                    }
                }

                RateQuestion::where('id', $question['id'])
                    ->update([
                        'title' => $question['title'],
                        'slug' => $question['slug'],
                        'place_type' => $question['place_type'],
                    ]);
            } else {
                /**
                 * Create question
                 */
                $model = RateQuestion::create($question);

                $model->answers()->saveMany(
                    collect($question['answers'])
                        ->map(fn ($answer) => new RateAnswer([
                            'body' => $answer['body'],
                            'slug' => $answer['slug'],
                            'order' => $answer['order'],
                        ]))
                        ->toArray()
                );
            }
        }

        if (count($answersToDelete) > 0) {
            RateAnswer::destroy($answersToDelete->toArray());
        }
    
        Notification::make() 
            ->title('Saved successfully')
            ->success()
            ->send(); 

        $this->initialize();
    }

    public function render()
    {
        return view('livewire.rate-question-form');
    }
}
