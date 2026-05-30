{{-- resources/views/components/help-modal.blade.php --}}
{{-- Apenas o modal — o botão ? é adicionado inline em cada tela --}}
@props(['titulo' => 'Ajuda', 'id' => 'helpModal'])

<div class="modal fade" id="{{ $id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header" style="background:#1a1a1a;color:white;border-bottom:3px solid #E30613">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-question-circle me-2"></i>{{ $titulo }}
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="font-size:14px">
                {{ $slot }}
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>
