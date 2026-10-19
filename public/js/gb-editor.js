/*
 * Editor de texto dos tópicos (Quill 1.3.7, tema snow).
 * Barra completa do DashForge, só com formatações que chegam ao Word
 * (sem fonte, imagem, vídeo, fórmula e escrita da direita para a esquerda).
 */
window.gbEditor = (function () {
    const TOOLBAR = [
        [{ size: ['small', false, 'large', 'huge'] }],
        ['bold', 'italic', 'underline', 'strike'],
        [{ color: [] }, { background: [] }],
        [{ script: 'sub' }, { script: 'super' }],
        [{ header: 1 }, { header: 2 }, 'blockquote', 'code-block'],
        [{ list: 'ordered' }, { list: 'bullet' }, { indent: '-1' }, { indent: '+1' }],
        [{ align: [] }],
        ['link'],
        ['clean'],
    ];

    const FORMATS = ['size', 'bold', 'italic', 'underline', 'strike', 'color', 'background', 'script', 'header', 'blockquote', 'code-block', 'list', 'indent', 'align', 'link'];

    const TITLES = {
        bold: 'Negrito',
        italic: 'Itálico',
        underline: 'Sublinhado',
        strike: 'Tachado',
        'script:sub': 'Subscrito',
        'script:super': 'Sobrescrito',
        'header:1': 'Título 1',
        'header:2': 'Título 2',
        blockquote: 'Citação recuada',
        'code-block': 'Bloco de código',
        'list:ordered': 'Lista numerada',
        'list:bullet': 'Lista com marcadores',
        'indent:-1': 'Diminuir recuo',
        'indent:+1': 'Aumentar recuo',
        link: 'Inserir link',
        clean: 'Limpar formatação',
        size: 'Tamanho do texto',
        color: 'Cor do texto',
        background: 'Cor de fundo (marca-texto)',
        align: 'Alinhamento',
    };

    function labelToolbar(quill) {
        const toolbar = quill.getModule('toolbar').container;

        toolbar.querySelectorAll('button').forEach(button => {
            const format = button.className.replace('ql-', '').split(' ')[0];
            const title = TITLES[button.value ? `${format}:${button.value}` : format] || TITLES[format];
            if (title) {
                button.setAttribute('title', title);
                button.setAttribute('aria-label', title);
            }
        });

        toolbar.querySelectorAll('.ql-picker').forEach(picker => {
            const format = Array.from(picker.classList).find(name => name.startsWith('ql-') && name !== 'ql-picker').replace('ql-', '');
            const label = picker.querySelector('.ql-picker-label');
            if (TITLES[format] && label) {
                label.setAttribute('title', TITLES[format]);
                label.setAttribute('aria-label', TITLES[format]);
            }
        });
    }

    return {
        create(element, { placeholder = '', label = 'Texto do tópico' } = {}) {
            const quill = new Quill(element, {
                theme: 'snow',
                placeholder,
                formats: FORMATS,
                modules: { toolbar: TOOLBAR },
            });
            quill.root.setAttribute('aria-label', label);
            quill.root.setAttribute('role', 'textbox');
            quill.root.setAttribute('aria-multiline', 'true');
            labelToolbar(quill);
            const linkInput = quill.theme.tooltip?.textbox;
            if (linkInput) {
                linkInput.dataset.link = 'https://exemplo.com.br';
                linkInput.setAttribute('aria-label', 'Endereço do link');
            }
            return quill;
        },

        setHtml(quill, html) {
            quill.setContents([]);
            quill.clipboard.dangerouslyPasteHTML(0, html || '');
            quill.history.clear();
        },

        getHtml(quill) {
            return quill.getText().trim() === '' ? '' : quill.root.innerHTML;
        },
    };
})();
