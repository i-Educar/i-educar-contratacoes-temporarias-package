<form class="form-grid" method="post" action="{{ url('/contratacoes-temporarias/inscricoes') }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="processo_id" value="{{ $processoId }}">
                <label>Nome completo <input name="nome" required placeholder="Como no documento"></label>
                <label>CPF <input name="cpf" required placeholder="somente números" inputmode="numeric"></label>
                <label>E-mail <input type="email" name="email" placeholder="para contato"></label>
                <label>Telefone <input name="telefone" placeholder="Telefone"></label>
                @include('buriti-ui::select-preferencias-vaga', [
                    'campo' => 'preferencias[]',
                    'rotulo' => 'Preferência de cargo e escola',
                    'icone' => 'fa-briefcase',
                    'opcoes' => $vagas ?? [],
                    'maximo' => (int) ($slaPrefs ?? 3),
                    'ajuda' => 'Toque nos cards para ordenar a preferência (até '.($slaPrefs ?? 3).').',
                ])
                @foreach(($documentos ?? []) as $d)
                    @include('buriti-ui::campo-anexo', [
                        'campo' => 'doc_'.$d['codigo'],
                        'rotulo' => $d['nome'].($d['obrigatorio'] ? ' *' : ''),
                        'tituloAcao' => 'Soltar «'.$d['nome'].'» aqui',
                        'ajuda' => strtoupper(implode(', ', $d['extensoes'])).' · até '.$anexoMaxKb.' KB · máx '.$d['pontuacao_max'].' pts',
                        'accept' => '.'.implode(',.', $d['extensoes']),
                        'obrigatorio' => ! empty($d['obrigatorio']),
                        'icone' => 'fa-id-card',
                    ])
                @endforeach
                <label class="col-wide">Observação <textarea name="observacao" placeholder="Algo que a banca precise saber…"></textarea></label>
                <div class="acoes"><button class="botao" type="submit"><i class="fa fa-paper-plane"></i> Inscrever e gerar protocolo</button></div>
            </form>
