<?php

namespace App\Http\Livewire;

use App\Helper\County;
use App\Models\CountyEmails;
use Carbon\Carbon;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Livewire\Component;

class CountyResponsibleForm extends Component implements HasForms
{
    use InteractsWithForms;

    public $counties = [];

    public $original = [];

    protected $listeners = ['repeater::deleteItem' => 'incrementPostCount'];

    public function mount(): void
    {
        $counties = CountyEmails::all()->toArray();

        $this->original = $counties;
        $this->fill([
            'counties' => $counties,
        ]);
    }

    protected function getFormSchema(): array
    {
        $countryCases = County::cases();
        $counties = array_combine(
            keys: array_column($countryCases, 'value'),
            values: array_map(fn ($c) => $c->getLabel(), $countryCases)
        );

        return [
            Repeater::make('counties')
                ->schema([
                    Grid::make(2)->schema([
                        Select::make('county_iso')
                            ->required()
                            ->label('County')
                            ->searchable()
                            ->options($counties),
                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->label('Email')
                            ->placeholder('Email'),
                    ]),
                ])
                ->disableItemMovement()
                ->itemLabel(fn (array $state): ?string => County::tryFrom($state['county_iso'])?->getLabel() ?? null)
                ->collapsible(),
        ];
    }

    public function submit(): void
    {
        $current = $this->form->getState()['Counties'];

        /**
         * Delete if not present in the current state.
         */
        $idsToDelete = collect($this->original)
            ->whereNotIn(
                'county_iso',
                collect($current)->pluck('county_iso')
            )
            ->pluck('id')
            ->toArray();

        if (count($idsToDelete) > 0) {
            CountyEmails::destroy($idsToDelete);
        }

        /**
         * Create or Update if already exists.
         */
        $counties = collect($current)
            ->map(fn ($item) => [
                'county_iso' => $item['county_iso'],
                'email' => $item['email'],
                'updated_at' => Carbon::now(),
            ])
            ->toArray();

        CountyEmails::upsert(
            $counties,
            ['country_iso']
        );

        $this->original = CountyEmails::all()->toArray();

        Notification::make()
            ->title('Saved successfully')
            ->success()
            ->send();
    }

    public function render()
    {
        return view('livewire.country-responsible-form');
    }
}
