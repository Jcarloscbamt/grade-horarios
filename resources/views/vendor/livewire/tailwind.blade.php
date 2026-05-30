{{-- resources/views/vendor/livewire/tailwind.blade.php --}}
@if ($paginator->hasPages())
<nav class="d-flex align-items-center justify-content-between flex-wrap gap-2 py-2">
    {{-- Info --}}
    <div class="text-muted small">
        Mostrando <strong>{{ $paginator->firstItem() }}</strong>
        a <strong>{{ $paginator->lastItem() }}</strong>
        de <strong>{{ $paginator->total() }}</strong> registros
    </div>

    {{-- Botões --}}
    <div class="d-flex gap-1 flex-wrap">

        {{-- Primeira página --}}
        @if($paginator->onFirstPage())
            <button class="btn btn-sm btn-outline-secondary" disabled title="Primeira página">
                <i class="bi bi-chevron-double-left"></i>
            </button>
        @else
            <button wire:click="gotoPage(1)" class="btn btn-sm btn-outline-secondary" title="Primeira página">
                <i class="bi bi-chevron-double-left"></i>
            </button>
        @endif

        {{-- Anterior --}}
        @if($paginator->onFirstPage())
            <button class="btn btn-sm btn-outline-secondary" disabled>
                <i class="bi bi-chevron-left"></i>
            </button>
        @else
            <button wire:click="previousPage" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-chevron-left"></i>
            </button>
        @endif

        {{-- Páginas numeradas --}}
        @foreach($elements as $element)
            @if(is_string($element))
                <button class="btn btn-sm btn-outline-secondary" disabled>…</button>
            @endif
            @if(is_array($element))
                @foreach($element as $page => $url)
                    @if($page == $paginator->currentPage())
                        <button class="btn btn-sm btn-primary" disabled>{{ $page }}</button>
                    @else
                        <button wire:click="gotoPage({{ $page }})" class="btn btn-sm btn-outline-secondary">{{ $page }}</button>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- Próxima --}}
        @if($paginator->hasMorePages())
            <button wire:click="nextPage" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-chevron-right"></i>
            </button>
        @else
            <button class="btn btn-sm btn-outline-secondary" disabled>
                <i class="bi bi-chevron-right"></i>
            </button>
        @endif

        {{-- Última página --}}
        @if($paginator->hasMorePages())
            <button wire:click="gotoPage({{ $paginator->lastPage() }})" class="btn btn-sm btn-outline-secondary" title="Última página">
                <i class="bi bi-chevron-double-right"></i>
            </button>
        @else
            <button class="btn btn-sm btn-outline-secondary" disabled title="Última página">
                <i class="bi bi-chevron-double-right"></i>
            </button>
        @endif

    </div>
</nav>
@endif
