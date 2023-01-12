<form wire:submit.prevent="submit">
    {{ $this->form }}

    <div class="flex justify-end">
        <x-button type="submit" class="mt-4">
            Save
        </x-button>
    </div>
</form>
