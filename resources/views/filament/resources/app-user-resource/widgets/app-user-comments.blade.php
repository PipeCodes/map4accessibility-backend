<x-filament::widget>
    <x-filament::card>
        <p class="text-lg font-bold">{{ $record->full_name }}'s Evaluations:</p>
        <div class="divide-y">
            @forelse ($record->placeEvaluations as $evaluation)
                <div class="flex items-center py-4 space-x-4">
                    <div>
                        @if ($evaluation->thumb_direction)
                            <span class="text-lg">&#128077</span>
                        @else
                            <span class="text-lg">&#128078</span>
                        @endif
                    </div>
                    <div class="pl-4 border-l min-w-max">
                        <div>
                            <span class="text-sm font-semibold text-gray-400">Name:</span>
                            <span class="text-sm font-bold">{{ $evaluation->name }}</span>
                        </div>
                        <div>
                            <span class="text-sm font-semibold text-gray-400">Country:</span>
                            <span class="text-sm font-bold">{{ $evaluation->country }}</span>
                        </div>
                    </div>
                    <div class="px-4 border-l border-r min-w-max">
                        <div>
                            <span class="text-sm font-semibold text-gray-400">Posted at:</span>
                            <span class="text-sm font-bold">{{ $evaluation->created_at }}</span>
                        </div>
                        <div>
                            <span class="text-sm font-semibold text-gray-400">Google Place ID:</span>
                            <span class="text-sm font-bold">{{ $evaluation->google_place_id }}</span>
                        </div>
                    </div>
                    <div class="self-start">
                        <div class="flex flex-col"> 
                            <span class="text-sm font-semibold text-gray-400">Comment:</span>
                            <span class="text-xs font-semibold text-gray-800">{{ $evaluation->comment }}</span>
                        </div>
                    </div>
                </div>
            @empty
                <div class="flex justify-center w-full py-4 font-bold text-gray-300 align-content-center">
                    <span>No Place Evaluations made yet.</span>
                </div>
            @endforelse
        </div>
    </x-filament::card>
</x-filament::widget>
