<?php

namespace App\Http\Livewire;

use App\Helper\Country;
use App\Models\CountryResponsible;
use Carbon\Carbon;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Livewire\Component;

class CountryResponsibleForm extends Component implements HasForms
{
    use InteractsWithForms;

    public $countries = [];
    public $original = [];
    protected $listeners = ['repeater::deleteItem' => 'incrementPostCount'];

    public function mount(): void
    {
        $countries = CountryResponsible::all()->toArray();

        $this->original = $countries;
        $this->fill([
            'countries' => $countries
        ]);
    }

    protected function getCountryLabelByISO(?string $iso)
    {
        try {
            $name = Country::from($iso)->name;
            return $this->getCountryLabel($name);
        } catch (\ValueError $e) {
            return null;
        }
    }

    protected function getCountryLabel(string $name)
    {
        return ucwords(
            strtolower(
                str_replace("_", " ", $name)
            )
        );
    }

    protected function getFormSchema(): array
    {
        $countryCases = Country::cases();
        $countries = array_combine(
            keys: array_column($countryCases, 'value'),
            values: array_map(fn ($country) => $this->getCountryLabel($country), array_column($countryCases, 'name'))
        );

        return [
            Repeater::make('countries')
                ->schema([
                    Grid::make(2)->schema([
                        Select::make('country_iso')
                            ->required()
                            ->label('Country')
                            ->searchable()
                            ->options($countries),
                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->label('Email')
                            ->placeholder('Email'),
                    ]),
                ])
                ->disableItemMovement()
                ->itemLabel(fn (array $state): ?string => $this->getCountryLabelByISO($state['country_iso']) ?? null)
                ->collapsible()
        ];
    }

    public function submit(): void
    {
        $current = $this->form->getState()['countries'];

        /**
         * Delete if not present in the current state.
         */
        $idsToDelete = collect($this->original)
            ->whereNotIn(
                'country_iso', 
                collect($current)->pluck('country_iso')
            )
            ->pluck('id')
            ->toArray();

        CountryResponsible::destroy($idsToDelete);

        /**
         * Create or Update if already exists.
         */
        $countries = collect($current)
            ->map(fn ($item) => [
                'country_iso' => $item['country_iso'], 
                'email' => $item['email'], 
                'updated_at' => Carbon::now()
            ])
            ->toArray();

        CountryResponsible::upsert(
            $countries,
            ['country_iso']
        );
    
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
