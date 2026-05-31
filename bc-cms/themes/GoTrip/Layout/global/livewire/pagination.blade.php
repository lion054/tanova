@php
if (! isset($scrollTo)) {
    $scrollTo = 'body';
}

$scrollIntoViewJsSnippet = ($scrollTo !== false)
    ? <<<JS
   
       (\$el.closest('{$scrollTo}') || document.querySelector('{$scrollTo}')).scrollIntoView({ behavior: 'smooth'})
    JS
    : '';
@endphp

@if ($paginator->hasPages() && $paginator->total() > 1)
    <div class="border-top-light mt-30 pt-30 custom-pagination">
        <div class="row x-gap-10 y-gap-20 justify-between md:justify-center">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <div class="col-auto md:order-1 disabled p-item">
                    <span class="button -blue-1 size-40 rounded-full border-light p-link">
                        <i class="icon-chevron-left text-12"></i>
                    </span>
                </div>
            @else
                <div class="col-auto md:order-1 p-item">
                    <a wire:click.prevent="previousPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }} "href="{{ $paginator->previousPageUrl() }}" rel="prev" class="button -blue-1 size-40 rounded-full border-light p-link">
                        <i class="icon-chevron-left text-12"></i>
                    </a>
                </div>
            @endif

            {{-- Pagination Elements --}}
            <div class="col-md-auto md:order-3">
                <div class="row x-gap-20 y-gap-20 items-center md:d-none">
                    @foreach ($elements as $element)
                        {{-- "Three Dots" Separator --}}
                        @if (is_string($element))
                            <div class="col-auto p-item" wire:key="paginator-{{ $paginator->getPageName() }}-page-{{ $page }}">
                                <div class="size-40 flex-center rounded-full p-link">{{ $element }}</div>
                            </div>
                        @endif

                        {{-- Array Of Links --}}
                        @if (is_array($element))
                            @foreach ($element as $page => $url)
                                @if ($page == $paginator->currentPage())
                                    <div class="col-auto active p-item" wire:key="paginator-{{ $paginator->getPageName() }}-page-{{ $page }}">
                                        <div class="size-40 flex-center rounded-full p-link">{{ $page }}</div>
                                    </div>
                                @else
                                    <div class="col-auto p-item" wire:key="paginator-{{ $paginator->getPageName() }}-page-{{ $page }}">
                                        <a  wire:click.prevent="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}"  href="{{$url}}" class="size-40 flex-center rounded-full p-link">{{ $page }}</a>
                                    </div>
                                @endif
                            @endforeach
                        @endif
                    @endforeach
                </div>
            </div>
            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <div class="col-auto md:order-2 p-item">
                    <a wire:click.prevent="nextPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}"  href="{{ $paginator->nextPageUrl() }}" rel="next" class="button -blue-1 size-40 rounded-full border-light">
                        <i class="icon-chevron-right text-12"></i>
                    </a>
                </div>
            @else
                <div class="col-auto md:order-2 disabled p-item">
                    <span href="{{ $paginator->nextPageUrl() }}" class="button -blue-1 size-40 rounded-full border-light">
                        <i class="icon-chevron-right text-12"></i>
                    </span>
                </div>
            @endif
        </div>
    </div>
@endif