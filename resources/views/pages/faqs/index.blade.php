@extends('layouts.legal-texts')

@section('title')
    {{ __('faqs.headline') }}
@endsection

@section('content')
    <div class="faqs">
        @foreach ($faqs as $faq)
            <button class="accordion">{!! $faq->question !!}</button>
            <div class="panel">
                {!! Str::markdown($faq->answer) !!}
            </div>
        @endforeach
    </div>
@endsection

@section('scripts')
    <script>
        let acc = document.getElementsByClassName("accordion");
        let i;

        for (i = 0; i < acc.length; i++) {
            acc[i].addEventListener("click", function() {
                if(!this.classList.contains("active")) {
                    let elems = document.querySelectorAll(".accordion.active");
                    [].forEach.call(elems, function(el) {
                        el.classList.remove("active");
                        let panel = el.nextElementSibling;
                        panel.style.maxHeight = null;
                    });
                }
                this.classList.toggle("active");

                let panel = this.nextElementSibling;
                if (panel.style.maxHeight) {
                    panel.style.maxHeight = null;
                } else {
                    panel.style.maxHeight = panel.scrollHeight + "px";
                }
            });
        }
    </script>
@endsection
