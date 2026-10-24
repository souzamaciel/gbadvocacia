@extends('layouts.app')

@section('title', 'Importar ficha')

@section('page-header')
    <x-page-header title="Importar ficha de atendimento"
                   description="Envie a ficha preenchida no Word. A IA preenche o atendimento e sugere a petição para você conferir."
                   :breadcrumbs="['Atendimentos' => route('appointment.index'), 'Importar ficha']" />
@endsection

@section('content')
    <div class="row g-3 align-items-start">
        <div class="col-12 col-lg-7">
            <form class="card" id="import-form" novalidate>
                <div class="card-header d-flex align-items-center gap-3">
                    <span class="gb-icon-tile gb-icon-tile-sm"><i data-lucide="file-up"></i></span>
                    <div>
                        <h2 class="card-title">Arquivo da ficha</h2>
                        <p class="card-description">Documento do Word (.docx), até 10 MB.</p>
                    </div>
                </div>
                <div class="card-body p-4">
                    <label class="gb-dropzone" id="dropzone" for="import-file">
                        <input type="file" id="import-file" name="file" accept=".docx,application/vnd.openxmlformats-officedocument.wordprocessingml.document" class="visually-hidden">
                        <span class="gb-dropzone-icon"><i data-lucide="upload"></i></span>
                        <span class="gb-dropzone-title" id="dropzone-title">Arraste a ficha até aqui ou clique para escolher</span>
                        <span class="gb-dropzone-hint" id="dropzone-hint">Somente Word (.docx)</span>
                    </label>
                    <div class="gb-import-progress" id="import-progress" hidden role="status" aria-live="polite">
                        <span class="spinner-border spinner-border-sm" aria-hidden="true"></span>
                        <div>
                            <p class="gb-petition-title m-0" id="progress-title">Lendo a ficha…</p>
                            <p class="gb-petition-meta m-0">Isso leva de 30 segundos a 1 minuto. Não feche esta página.</p>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex flex-wrap align-items-center justify-content-end gap-2 bg-white">
                    <p class="gb-footer-hint me-auto m-0" id="import-hint" aria-live="polite"></p>
                    <a href="{{ route('appointment.index') }}" class="btn btn-white" id="import-cancel">Cancelar</a>
                    <button type="submit" class="btn btn-primary" id="import-submit" disabled><i data-lucide="sparkles"></i> Ler ficha com IA</button>
                </div>
            </form>
        </div>

        <div class="col-12 col-lg-5">
            <section class="card" aria-labelledby="how-title">
                <div class="card-header">
                    <h2 id="how-title" class="card-title">Como funciona</h2>
                </div>
                <div class="card-body p-4 gb-help-body">
                    <ol class="mb-3">
                        <li>A IA lê a ficha e preenche as perguntas do atendimento do sistema.</li>
                        <li>Ela também <strong>sugere as preliminares e os pedidos</strong> da petição, com base nos fatos da ficha.</li>
                        <li>O atendimento abre preenchido e salvo como <strong>rascunho</strong>. Confira tudo, ajuste o que precisar e clique em <strong>Concluir e revisar petição</strong>: cada tópico passa pela revisão antes de gerar o Word.</li>
                    </ol>
                    <div class="gb-help-note is-warning mb-2"><i data-lucide="shield-check"></i><div>O arquivo <strong>não fica guardado</strong> no sistema, e a <strong>senha GOV</strong> é retirada antes do envio à IA.</div></div>
                    <div class="gb-help-note mb-0"><i data-lucide="info"></i><div>Ficha em <strong>.doc</strong> ou <strong>PDF</strong>? Abra no Word e use “Salvar como” → Documento do Word (.docx).</div></div>
                </div>
            </section>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        (function () {
            const input = document.getElementById('import-file');
            const dropzone = document.getElementById('dropzone');
            const submit = document.getElementById('import-submit');
            const hint = document.getElementById('import-hint');
            let busy = false;

            function setHint(text, warning) {
                hint.className = 'gb-footer-hint me-auto m-0' + (warning ? ' is-warning' : '');
                hint.innerHTML = text ? `<i data-lucide="${warning ? 'circle-alert' : 'circle-check'}"></i><span>${text}</span>` : '';
                lucide.createIcons();
            }

            function refresh() {
                const file = input.files[0];
                const valid = !!file && /\.docx$/i.test(file.name) && file.size <= 10 * 1024 * 1024;
                submit.disabled = busy || !valid;
                document.getElementById('dropzone-title').textContent = file ? file.name : 'Arraste a ficha até aqui ou clique para escolher';
                document.getElementById('dropzone-hint').textContent = file ? (file.size / 1024 / 1024).toFixed(2).replace('.', ',') + ' MB' : 'Somente Word (.docx)';
                dropzone.classList.toggle('has-file', !!file);
                if (!file) { setHint('Escolha o arquivo da ficha.', true); }
                else if (!/\.docx$/i.test(file.name)) { setHint('Envie a ficha em Word (.docx). Se estiver em .doc ou PDF, salve como .docx no Word.', true); }
                else if (file.size > 10 * 1024 * 1024) { setHint('O arquivo deve ter no máximo 10 MB.', true); }
                else { setHint('Pronto para ler a ficha.', false); }
            }

            input.addEventListener('change', refresh);
            ['dragenter', 'dragover'].forEach(type => dropzone.addEventListener(type, event => { event.preventDefault(); dropzone.classList.add('is-dragging'); }));
            ['dragleave', 'drop'].forEach(type => dropzone.addEventListener(type, () => dropzone.classList.remove('is-dragging')));
            dropzone.addEventListener('drop', event => {
                event.preventDefault();
                if (event.dataTransfer.files.length) { input.files = event.dataTransfer.files; refresh(); }
            });

            window.gbIsDirty = () => busy;

            document.getElementById('import-form').addEventListener('submit', event => {
                event.preventDefault();
                if (submit.disabled) { return; }
                busy = true;
                refresh();
                dropzone.hidden = true;
                document.getElementById('import-progress').hidden = false;
                setHint('', false);
                const steps = ['Lendo a ficha…', 'Preenchendo as perguntas do atendimento…', 'Escolhendo preliminares e pedidos…', 'Quase pronto…'];
                let step = 0;
                const timer = setInterval(() => { step = Math.min(step + 1, steps.length - 1); document.getElementById('progress-title').textContent = steps[step]; }, 12000);

                const data = new FormData();
                data.append('file', input.files[0]);
                gbGeneration.run(@json(route('appointment.import.store')), data, document.querySelector('meta[name=csrf-token]').content)
                .then(({ result }) => { window.gbLeaving = true; location.href = result.redirect; })
                .catch(error => {
                    busy = false;
                    dropzone.hidden = false;
                    document.getElementById('import-progress').hidden = true;
                    refresh();
                    Swal.fire({ icon: 'error', title: 'Não foi possível importar', text: error.message, confirmButtonColor: 'var(--df-brand-01)' });
                })
                .finally(() => clearInterval(timer));
            });

            refresh();
        })();
    </script>
@endsection
