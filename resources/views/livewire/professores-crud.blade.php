{{-- resources/views/livewire/professores-crud.blade.php --}}
<div>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0">Professores</h2>
            <small class="text-muted">Gerenciamento de professores e suas disciplinas</small>
        </div>
        @hasanyrole('admin|coordenador')
        <button wire:click="create" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Novo Professor</button>
        @endhasanyrole
    </div>

    @if(session()->has('success'))<div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle me-2"></i>{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if(session()->has('error'))<div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif

    <div class="card mb-3 border-0 shadow-sm">
        <div class="card-body py-2">
            <div class="row g-2 align-items-center">
                <div class="col-md-8">
                    <div class="input-group">
                        <select wire:model.live="filtro" class="form-select flex-shrink-1" style="max-width:160px;border-radius:6px 0 0 6px;border-right:none">
                            <option value="todos">Todos os campos</option>
                            <option value="nome">Nome</option>
                            <option value="email">E-mail</option>
                            <option value="cpf">CPF</option>
                            <option value="telefone">Telefone</option>
                        </select>
                        <span class="input-group-text bg-white px-2" style="border-left:none;border-right:none"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Digite para filtrar...">
                        @if($search)<button class="btn btn-outline-secondary" wire:click="$set('search', '')"><i class="bi bi-x-lg"></i></button>@endif
                    </div>
                </div>
                <div class="col-md-4">
                    <select wire:model.live="filtroAtivo" class="form-select">
                        <option value="todos">Todos os status</option>
                        <option value="ativos">Somente Ativos</option>
                        <option value="inativos">Somente Inativos</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Nome</th>
                            <th>E-mail</th>
                            <th>Telefone</th>
                            <th>CPF</th>
                            <th class="text-center">Disciplinas</th>
                            <th class="text-center">Status</th>
                            <th class="text-center pe-3">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($professores as $professor)
                        <tr class="{{ !$professor->ativo ? 'opacity-50' : '' }}">
                            <td class="ps-3 fw-medium">{{ $professor->nome }}</td>
                            <td>{{ $professor->email }}</td>
                            <td>{{ $professor->telefone ?? '—' }}</td>
                            <td style="font-family:monospace">
                                @php
                                    $cpfLimpo = preg_replace('/\D/', '', $professor->cpf);
                                    $cpfFmt = strlen($cpfLimpo) === 11
                                        ? substr($cpfLimpo,0,3).'.'.substr($cpfLimpo,3,3).'.'.substr($cpfLimpo,6,3).'-'.substr($cpfLimpo,9,2)
                                        : $professor->cpf;
                                @endphp
                                {{ $cpfFmt }}
                            </td>
                            <td class="text-center">
                                @php $total = $professor->disciplinasTurmas()->count(); @endphp
                                @if($total > 0)<span class="badge bg-primary rounded-pill">{{ $total }}</span>
                                @else<span class="text-muted small">—</span>@endif
                            </td>
                            <td class="text-center">
                                @hasanyrole('admin|coordenador')
                                <button wire:click="toggleAtivo({{ $professor->id }})"
                                    class="btn btn-sm {{ $professor->ativo ? 'btn-success' : 'btn-secondary' }}"
                                    title="{{ $professor->ativo ? 'Desativar' : 'Ativar' }}">
                                    <i class="bi bi-circle-fill me-1" style="font-size:8px"></i>
                                    {{ $professor->ativo ? 'Ativo' : 'Inativo' }}
                                </button>
                                @else
                                <span class="badge {{ $professor->ativo ? 'bg-success' : 'bg-secondary' }}">{{ $professor->ativo ? 'Ativo' : 'Inativo' }}</span>
                                @endhasanyrole
                            </td>
                            <td class="text-center pe-3">
                                @hasanyrole('admin|coordenador')
                                <button wire:click="edit({{ $professor->id }})" class="btn btn-sm btn-outline-secondary me-1"><i class="bi bi-pencil"></i></button>
                                @endhasanyrole
                                @hasrole('admin')
                                <button wire:click="confirmDelete({{ $professor->id }})" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                @endhasrole
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="text-center text-muted py-5"><i class="bi bi-inbox fs-3 d-block mb-2"></i>Nenhum professor encontrado.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($professores->hasPages())<div class="card-footer bg-white border-top-0">{{ $professores->links() }}</div>@endif
    </div>

    {{-- ═══════════════════════════════════════════════════════
         Modal Incluir / Editar Professor
    ═══════════════════════════════════════════════════════ --}}
    @if($showModal)
    <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,0.55)">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content border-0 shadow">

                <div class="modal-header border-bottom pb-3">
                    <h5 class="modal-title fw-bold"><i class="bi bi-person-badge me-2 text-primary"></i>{{ $modalTitle }}</h5>
                    <button type="button" class="btn-close" wire:click="closeModal"></button>
                </div>

                <div class="modal-body p-0">
                    <div class="row g-0">

                        {{-- ── Coluna Esquerda: Dados + Disponibilidade ── --}}
                        <div class="col-lg-5 border-end p-4">
                            <h6 class="fw-bold text-muted text-uppercase mb-3" style="font-size:11px;letter-spacing:.8px">
                                <i class="bi bi-person me-1"></i> Dados do Professor
                            </h6>
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label fw-medium">Nome <span class="text-danger">*</span></label>
                                    <input type="text" wire:model="nome" class="form-control @error('nome') is-invalid @enderror" placeholder="Nome completo">
                                    @error('nome')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-medium">CPF <span class="text-danger">*</span></label>
                                    <input type="text"
                                        wire:model.lazy="cpf"
                                        x-data
                                        x-on:input="let v=$event.target.value.replace(/\D/g,'').substring(0,11);if(v.length>9)v=v.replace(/(\d{3})(\d{3})(\d{3})(\d{1,2})/,'$1.$2.$3-$4');else if(v.length>6)v=v.replace(/(\d{3})(\d{3})(\d{1,3})/,'$1.$2.$3');else if(v.length>3)v=v.replace(/(\d{3})(\d{1,3})/,'$1.$2');$event.target.value=v;"
                                        class="form-control @error('cpf') is-invalid @enderror" placeholder="000.000.000-00" maxlength="14" style="font-family:monospace">
                                    @error('cpf')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @else
                                        @php $cpfDigits = strlen(preg_replace('/\D/', '', $cpf)); @endphp
                                        @if($cpfDigits === 11)
                                        <div class="text-success mt-1" style="font-size:12px"><i class="bi bi-check-circle me-1"></i>CPF válido</div>
                                        @elseif($cpfDigits > 0 && $cpfDigits < 11)
                                        <div class="text-warning mt-1" style="font-size:12px"><i class="bi bi-exclamation-circle me-1"></i>CPF incompleto ({{ $cpfDigits }}/11 dígitos)</div>
                                        @endif
                                    @enderror
                                    <div class="form-text">Formatação automática ao digitar</div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-medium">E-mail <span class="text-danger">*</span></label>
                                    <input type="email" wire:model.lazy="email" class="form-control @error('email') is-invalid @enderror" placeholder="email@exemplo.com">
                                    @error('email')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-medium">Telefone</label>
                                    <input type="text" wire:model.blur="telefone"
                                        x-data
                                        x-on:input="let v=$event.target.value.replace(/\D/g,'').substring(0,11);if(v.length>10)v=v.replace(/(\d{2})(\d{5})(\d{4})/,'($1) $2-$3');else if(v.length>6)v=v.replace(/(\d{2})(\d{4})(\d{1,4})/,'($1) $2-$3');else if(v.length>2)v=v.replace(/(\d{2})(\d{1,5})/,'($1) $2');$event.target.value=v;"
                                        class="form-control" placeholder="(65) 98119-0328" maxlength="15" style="font-family:monospace">
                                    <div class="form-text">Formatação automática ao digitar</div>
                                </div>
                                <div class="col-12">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" role="switch" wire:model="ativo" id="ativoProfessor">
                                        <label class="form-check-label fw-medium" for="ativoProfessor">
                                            Professor <strong>{{ $ativo ? 'Ativo' : 'Inativo' }}</strong>
                                            <span class="badge ms-1 {{ $ativo ? 'bg-success' : 'bg-secondary' }}">{{ $ativo ? 'Ativo' : 'Inativo' }}</span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <hr class="my-4">
                            <h6 class="fw-bold text-muted text-uppercase mb-1" style="font-size:11px;letter-spacing:.8px">
                                <i class="bi bi-calendar-week me-1"></i> Disponibilidade Geral
                            </h6>
                            <p class="text-muted small mb-2">
                                Marque os dias em que o professor pode lecionar.<br>
                                <span class="text-warning fw-medium"><i class="bi bi-exclamation-triangle me-1"></i>Os dias das disciplinas serão validados com base nesta seleção.</span>
                            </p>
                            <button type="button" wire:click="toggleTodosDisponibilidade"
                                class="btn btn-sm w-100 mb-2 {{ count($disponibilidade) === 5 ? 'btn-dark' : 'btn-outline-dark' }}">
                                <i class="bi bi-calendar-check me-1"></i>
                                {{ count($disponibilidade) === 5 ? '✓ Todos os dias selecionados' : 'Selecionar todos os dias' }}
                            </button>
                            <div class="d-flex gap-2 flex-wrap">
                                @foreach($diasNomes as $num => $label)
                                <div class="form-check form-check-inline m-0">
                                    <input class="form-check-input" type="checkbox" wire:model.live="disponibilidade" value="{{ $num }}" id="disp_{{ $num }}">
                                    <label class="form-check-label fw-semibold" for="disp_{{ $num }}">
                                        <span class="badge {{ in_array($num, $disponibilidade) ? 'bg-success' : 'bg-light text-dark border' }}" style="font-size:13px;min-width:42px;cursor:pointer">{{ $label }}</span>
                                    </label>
                                </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- ── Coluna Direita: Disciplinas por Turma ── --}}
                        <div class="col-lg-7 p-4 d-flex flex-column" style="background:#f8f9fa">
                            <h6 class="fw-bold text-muted text-uppercase mb-3" style="font-size:11px;letter-spacing:.8px">
                                <i class="bi bi-book me-1"></i> Disciplinas por Turma
                            </h6>

                            @error('vinculo')
                            <div class="alert alert-warning py-2 mb-3" style="font-size:13px"><i class="bi bi-exclamation-triangle me-1"></i>{{ $message }}</div>
                            @enderror

                            {{-- Formulário para adicionar vínculo --}}
                            <div class="card border-0 shadow-sm mb-3 {{ $editandoVinculoIdx >= 0 ? 'border-warning border-2' : '' }}"
                                style="{{ $editandoVinculoIdx >= 0 ? 'border:2px solid #f59e0b !important' : '' }}">
                                @if($editandoVinculoIdx >= 0)
                                <div class="card-header py-1 px-3" style="background:#fef3c7;border-bottom:1px solid #f59e0b">
                                    <span class="fw-semibold small" style="color:#92400e"><i class="bi bi-pencil me-1"></i>Editando vínculo #{{ $editandoVinculoIdx + 1 }}</span>
                                    <button type="button" wire:click="cancelarSelecao" class="btn btn-sm btn-link text-muted float-end py-0" style="font-size:12px">Cancelar edição</button>
                                </div>
                                @endif
                                <div class="card-body p-3">
                                    @if(!$sel_disciplina_id)
                                    {{-- Passo 1: Buscar disciplina --}}
                                    <label class="form-label fw-medium small mb-1"><span class="badge bg-primary me-1">1</span>Buscar Disciplina <span class="text-danger">*</span></label>
                                    <div class="input-group input-group-sm mb-1">
                                        <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                                        <input type="text" wire:model.live.debounce.300ms="buscaDisciplina" class="form-control" placeholder="Digite o nome da disciplina (mín. 2 caracteres)...">
                                        @if($buscaDisciplina)<button class="btn btn-outline-secondary btn-sm" wire:click="$set('buscaDisciplina', '')"><i class="bi bi-x"></i></button>@endif
                                    </div>
                                    @if($mostrarLista)
                                    <div class="border rounded bg-white" style="max-height:180px;overflow-y:auto">
                                        @forelse($disciplinasDisponiveis as $disc)
                                        <button type="button"
                                            wire:click="selecionarDisciplina({{ $disc['id'] }}, '{{ addslashes($disc['nome']) }}', '{{ addslashes($disc['curso_nome']) }}', {{ $disc['curso_id'] }})"
                                            class="list-group-item list-group-item-action border-0 py-2 px-3 text-start w-100">
                                            <div class="fw-medium" style="font-size:13px">{{ $disc['nome'] }}</div>
                                            <div class="text-muted" style="font-size:11px"><i class="bi bi-mortarboard me-1"></i>{{ $disc['curso_nome'] }}</div>
                                        </button>
                                        @empty
                                        <div class="text-center text-muted py-3 small"><i class="bi bi-search me-1"></i>Nenhuma disciplina encontrada</div>
                                        @endforelse
                                    </div>
                                    @elseif(strlen($buscaDisciplina) === 1)
                                    <div class="text-muted small mt-1"><i class="bi bi-info-circle me-1"></i>Digite mais um caractere...</div>
                                    @endif

                                    @else
                                    {{-- Disciplina selecionada --}}
                                    <div class="rounded p-2 mb-2 border border-primary" style="background:#e8f0fe">
                                        <div class="fw-semibold" style="font-size:13px;color:#1a56db"><i class="bi bi-check-circle-fill me-1"></i>{{ $sel_disciplina_nome }}</div>
                                        <div class="text-muted" style="font-size:11px"><i class="bi bi-mortarboard me-1"></i>{{ $sel_curso_nome }}</div>
                                    </div>

                                    {{-- Passo 2: Turma --}}
                                    <div class="mb-2">
                                        <label class="form-label fw-medium small mb-1"><span class="badge bg-primary me-1">2</span>Turma <span class="text-danger">*</span></label>
                                        <select wire:model.live="sel_turma_id" class="form-select form-select-sm">
                                            <option value="">Selecione a turma...</option>
                                            @foreach($turmasDoVinculo as $turma)
                                            <option value="{{ $turma['id'] }}">{{ $turma['nome'] }}{{ $turma['semestre'] ? ' — ' . $turma['semestre'] . 'º sem' : '' }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    {{-- Passo 3: Dias --}}
                                    @if($sel_turma_id)
                                    <div class="mb-2">
                                        <label class="form-label fw-medium small mb-1"><span class="badge bg-primary me-1">3</span>Dias para esta disciplina <span class="text-danger">*</span></label>
                                        @if(empty($disponibilidade))
                                        <div class="alert alert-warning py-2" style="font-size:12px"><i class="bi bi-exclamation-triangle me-1"></i>Defina a disponibilidade geral primeiro (coluna esquerda).</div>
                                        @else
                                        <div class="d-flex gap-2 flex-wrap">
                                            @foreach($diasNomes as $num => $label)
                                            @php $disponivel = in_array($num, $disponibilidade); $marcado = in_array($num, $sel_dias); @endphp
                                            <div class="form-check form-check-inline m-0">
                                                <input class="form-check-input" type="checkbox" wire:model.live="sel_dias" value="{{ $num }}" id="sdia_{{ $num }}" {{ !$disponivel ? 'disabled' : '' }}>
                                                <label class="form-check-label fw-semibold" for="sdia_{{ $num }}">
                                                    <span class="badge {{ !$disponivel ? 'bg-light text-muted border' : ($marcado ? 'bg-primary' : 'bg-light text-dark border') }}"
                                                          style="font-size:12px;min-width:38px;cursor:{{ $disponivel ? 'pointer' : 'not-allowed' }};opacity:{{ $disponivel ? '1' : '0.4' }}">{{ $label }}</span>
                                                </label>
                                            </div>
                                            @endforeach
                                        </div>
                                        <div class="text-muted mt-1" style="font-size:11px"><i class="bi bi-info-circle me-1"></i>Apenas dias da disponibilidade geral estão habilitados.</div>
                                        @endif
                                    </div>
                                    @endif
                                    @endif

                                    {{-- Botão Adicionar / Salvar Edição --}}
                                    @if($sel_disciplina_id && $sel_turma_id && !empty($disponibilidade))
                                    <button type="button" wire:click="adicionarVinculo"
                                        class="btn btn-sm w-100 mt-1 {{ $editandoVinculoIdx >= 0 ? 'btn-warning' : 'btn-success' }}"
                                        {{ empty($sel_dias) ? 'disabled' : '' }}>
                                        <i class="bi bi-{{ $editandoVinculoIdx >= 0 ? 'check-lg' : 'plus-circle' }} me-1"></i>
                                        {{ $editandoVinculoIdx >= 0 ? 'Salvar Alteração' : 'Adicionar Vínculo' }}
                                    </button>
                                    @endif
                                </div>
                            </div>

                            {{-- Lista de vínculos adicionados --}}
                            <div class="flex-grow-1" style="overflow-y:auto;max-height:300px">
                                @if(count($vinculos) === 0)
                                <div class="text-center text-muted py-4">
                                    <i class="bi bi-book fs-2 d-block mb-2 opacity-25"></i>
                                    <small>Nenhuma disciplina vinculada ainda.<br>Use o formulário acima para adicionar.</small>
                                </div>
                                @else
                                <div class="d-flex flex-column gap-2">
                                    @foreach($vinculos as $i => $v)
                                    <div class="card border-0 shadow-sm">
                                        <div class="card-body py-2 px-3">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div class="flex-grow-1">
                                                    <div class="fw-semibold" style="font-size:13px">{{ $v['disciplina_nome'] }}</div>
                                                    <div class="text-muted" style="font-size:11px">
                                                        <i class="bi bi-mortarboard me-1"></i>{{ $v['curso_nome'] }} &nbsp;·&nbsp;
                                                        <i class="bi bi-people me-1"></i>{{ $v['turma_nome'] }}
                                                    </div>
                                                    <div class="mt-1 d-flex gap-1 flex-wrap">
                                                        @foreach($v['dias'] as $dia)
                                                        <span class="badge bg-primary" style="font-size:10px">{{ $diasNomes[$dia] ?? $dia }}</span>
                                                        @endforeach
                                                    </div>
                                                </div>
                                                <div class="d-flex gap-1 ms-2">
                                                    <button type="button" wire:click="editarVinculo({{ $i }})" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                                    <button type="button" wire:click="removerVinculo({{ $i }})" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                                @endif
                            </div>

                            @if(count($vinculos) > 0)
                            <div class="text-muted text-end mt-2" style="font-size:12px">
                                <i class="bi bi-info-circle me-1"></i>{{ count($vinculos) }} vínculo(s) adicionado(s)
                            </div>
                            @endif
                        </div>

                    </div>
                </div>

                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-light" wire:click="closeModal">Cancelar</button>
                    <button type="button" class="btn btn-primary" wire:click="save" wire:loading.attr="disabled">
                        <span wire:loading wire:target="save" class="spinner-border spinner-border-sm me-1"></span>
                        <i wire:loading.remove wire:target="save" class="bi bi-floppy me-1"></i>Salvar Professor
                    </button>
                </div>

            </div>
        </div>
    </div>
    @endif

    {{-- Modal Confirmar Exclusão --}}
    @if($showDelete)
    <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,0.5)">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-body text-center py-4">
                    <div class="text-danger mb-3"><i class="bi bi-exclamation-triangle-fill" style="font-size:3rem"></i></div>
                    <h5 class="fw-bold mb-2">Confirmar Exclusão</h5>
                    <p class="text-muted mb-0">Tem certeza que deseja excluir este professor?<br><small>Todos os vínculos com disciplinas serão removidos.</small></p>
                </div>
                <div class="modal-footer border-top-0 justify-content-center pb-4">
                    <button type="button" class="btn btn-light px-4" wire:click="closeModal">Cancelar</button>
                    <button type="button" class="btn btn-danger px-4" wire:click="delete" wire:loading.attr="disabled">
                        <span wire:loading wire:target="delete" class="spinner-border spinner-border-sm me-1"></span>Sim, excluir
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
