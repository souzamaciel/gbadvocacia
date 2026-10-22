/*
 * Acompanha trabalhos da IA feitos em segundo plano (petição, importação da ficha).
 * O servidor responde na hora com um endereço de situação; aqui perguntamos a cada
 * poucos segundos até ficar pronto e então baixamos o Word.
 */
window.gbGeneration = (function () {
    const sleep = ms => new Promise(resolve => setTimeout(resolve, ms));

    async function wait(statusUrl, { intervalMs = 2500, maxMinutes = 10 } = {}) {
        const deadline = Date.now() + maxMinutes * 60 * 1000;
        let networkErrors = 0;

        while (Date.now() < deadline) {
            await sleep(intervalMs);
            let response;
            try {
                response = await fetch(statusUrl, { headers: { 'Accept': 'application/json' }, cache: 'no-store' });
            } catch (error) {
                if (++networkErrors >= 5) { throw new Error('A conexão com o sistema caiu. Confira a internet e tente novamente.'); }
                continue;
            }
            networkErrors = 0;
            const data = await response.json().catch(() => ({}));
            if (data.status === 'done') { return data; }
            if (data.status === 'failed' || !response.ok) { throw new Error(data.message || 'Não foi possível concluir agora. Tente novamente em instantes.'); }
        }

        throw new Error('A geração está demorando mais que o esperado. Tente novamente em instantes.');
    }

    async function download(url, fileName) {
        const response = await fetch(url, { headers: { 'Accept': 'application/json' } });
        if (!response.ok) {
            const data = await response.json().catch(() => ({}));
            throw new Error(data.message || 'Não foi possível baixar o arquivo.');
        }
        const blob = await response.blob();
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = fileName || 'peticao-inicial.docx';
        document.body.appendChild(link);
        link.click();
        link.remove();
        setTimeout(() => URL.revokeObjectURL(link.href), 2000);
    }

    /* Inicia (POST que responde 202 com status_url), espera e devolve a situação final. */
    async function run(url, body, csrfToken) {
        const response = await fetch(url, {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body,
        });
        const data = await response.json().catch(() => ({}));
        if (!response.ok) {
            const error = new Error(data.errors ? Object.values(data.errors)[0][0] : (data.message || 'Não foi possível iniciar. Tente novamente.'));
            error.data = data;
            throw error;
        }
        const result = await wait(data.status_url);
        return { ...data, result };
    }

    return { wait, download, run };
})();
