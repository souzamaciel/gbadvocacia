/*
 * Editor de texto dos tópicos (Quill 1.3.7, tema snow).
 * Só oferece formatações que chegam ao Word: negrito, itálico, sublinhado e listas.
 */
window.gbEditor = (function () {
    const TOOLBAR = [
        ['bold', 'italic', 'underline'],
        [{ list: 'ordered' }, { list: 'bullet' }],
        ['clean'],
    ];

    const TITLES = {
        bold: 'Negrito',
        italic: 'Itálico',
        underline: 'Sublinhado',
        ordered: 'Lista numerada',
        bullet: 'Lista com marcadores',
        clean: 'Limpar formatação',
    };

    function labelToolbar(quill) {
        quill.getModule('toolbar').container.querySelectorAll('button').forEach(button => {
            const key = button.value || button.className.replace('ql-', '');
            const title = TITLES[key];
            if (title) {
                button.setAttribute('title', title);
                button.setAttribute('aria-label', title);
            }
        });
    }

    return {
        create(element, { placeholder = '', label = 'Texto do tópico' } = {}) {
            const quill = new Quill(element, {
                theme: 'snow',
                placeholder,
                formats: ['bold', 'italic', 'underline', 'list'],
                modules: { toolbar: TOOLBAR },
            });
            quill.root.setAttribute('aria-label', label);
            quill.root.setAttribute('role', 'textbox');
            quill.root.setAttribute('aria-multiline', 'true');
            labelToolbar(quill);
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
