<x-filament::widget>
    <h3 class="mb-2 text-sm font-medium leading-4 text-gray-700">Questions Answers</h3>

    <x-filament::card>
        <div class="space-y-4">
            @if ($record->questions_answers && count($record->questions_answers) > 0)
                @foreach ($record->questions_answers as $key => $item)
                    <div class="flex justify-between pb-4 border-b">
                        <div class="space-y-2">
                            <p>
                                <span class="text-sm font-semibold text-gray-300">Q:</span>
                                <span class="text-sm text-gray-600 text-semibold">{{ $item['question'] }}</span>
                            </p>
                            <p>
                                <span class="text-sm font-semibold text-gray-300">A:</span>
                                <span class="font-bold">{{ $item['answer'] }}</span>
                            </p>
                        </div>
                        <div class="min-w-[15%]">
                            @if (str_contains($key, 'mandatory'))
                                <p class="w-full mt-1 text-xs font-bold text-right text-red-500">Mandatory</p>
                            @endif
                        </div>
                    </div>
                @endforeach
            @else
                <p class="w-full py-8 text-sm font-semibold text-center text-gray-300">No questions answered.</p>
            @endif
        </div>
    </x-filament::card>
</x-filament::widget>
