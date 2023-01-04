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
        var acc = document.getElementsByClassName("accordion");
        var i;

        for (i = 0; i < acc.length; i++) {
            acc[i].addEventListener("click", function() {
                if(!this.classList.contains("active")) {
                    var elems = document.querySelectorAll(".accordion.active");
                    [].forEach.call(elems, function(el) {
                        el.classList.remove("active");
                        var panel = el.nextElementSibling;
                        panel.style.maxHeight = null;
                    });
                }
                this.classList.toggle("active");

                var panel = this.nextElementSibling;
                if (panel.style.maxHeight) {
                    panel.style.maxHeight = null;
                } else {
                    panel.style.maxHeight = panel.scrollHeight + "px";
                }
            });
        }
    </script>
@endsection
