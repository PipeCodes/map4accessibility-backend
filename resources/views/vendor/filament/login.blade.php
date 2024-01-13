<form wire:submit.prevent="authenticate" class="space-y-8">
    {{ $this->form }}
    <div class="flex items-center justify-space-around mt-4">
        @if (Route::has('password.request'))
            <a class="underline text-sm text-gray-600 hover:text-gray-900" href="{{ route('password.request') }}">
                {{ __('Forgot your password?') }}
            </a>
        @endif
        <x-filament::button type="submit" form="authenticate" class="w-full">
            {{ __('filament::login.buttons.submit.label') }}
        </x-filament::button>
    </div>
</form>
