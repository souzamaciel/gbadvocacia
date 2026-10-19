<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class HelpController extends Controller
{
    /**
     * Knowledge base: every tutorial on one page. Articles about areas the user's
     * profile cannot use are left out.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $categories = collect($this->categories())
            ->map(fn (array $category): array => [
                ...$category,
                'articles' => array_values(array_filter(
                    $category['articles'],
                    fn (array $article): bool => $article['can'] === null || $user->can($article['can']),
                )),
            ])
            ->filter(fn (array $category): bool => $category['articles'] !== [])
            ->values();

        return view('help.index', compact('categories'));
    }

    /**
     * @return list<array{title: string, icon: string, articles: list<array{slug: string, title: string, summary: string, can: string|null}>}>
     */
    private function categories(): array
    {
        return [
            [
                'title' => 'Primeiros passos',
                'icon' => 'rocket',
                'articles' => [
                    ['slug' => 'entrar', 'title' => 'Entrar no sistema', 'summary' => 'Login, primeiro acesso e senha esquecida.', 'can' => null],
                    ['slug' => 'inicio', 'title' => 'Tela inicial e menu', 'summary' => 'O que cada menu faz, no computador e no celular.', 'can' => null],
                    ['slug' => 'perfil', 'title' => 'Meu perfil e senha', 'summary' => 'Alterar nome, e-mail e senha e ver suas permissões.', 'can' => null],
                ],
            ],
            [
                'title' => 'Atendimentos',
                'icon' => 'clipboard-list',
                'articles' => [
                    ['slug' => 'atendimento', 'title' => 'Fazer um atendimento', 'summary' => 'Preencher a ficha, perguntas condicionais e campos obrigatórios.', 'can' => 'appointments.create'],
                    ['slug' => 'importar-ficha', 'title' => 'Importar ficha do Word', 'summary' => 'A IA lê a ficha preenchida no Word, preenche o atendimento e sugere a petição.', 'can' => 'appointments.create'],
                    ['slug' => 'rascunhos-atendimento', 'title' => 'Rascunhos de atendimento', 'summary' => 'Salvar, continuar e excluir atendimentos.', 'can' => 'appointments.create'],
                    ['slug' => 'concluir-atendimento', 'title' => 'Imprimir a ficha e gerar a petição', 'summary' => 'A ficha em Word e a petição feita pela IA.', 'can' => 'appointments.create'],
                ],
            ],
            [
                'title' => 'Petições',
                'icon' => 'file-text',
                'articles' => [
                    ['slug' => 'peticoes', 'title' => 'Petições iniciais', 'summary' => 'A lista de rascunhos de petição.', 'can' => 'petitions.edit'],
                    ['slug' => 'monte', 'title' => 'Monte sua petição', 'summary' => 'Passo 1: escolher e organizar os tópicos.', 'can' => 'petitions.edit'],
                    ['slug' => 'revisar', 'title' => 'Revisar conteúdo', 'summary' => 'Passo 2: completar as informações e conferir cada tópico.', 'can' => 'petitions.edit'],
                    ['slug' => 'gerar', 'title' => 'Gerar documento', 'summary' => 'Passo 3: baixar o Word.', 'can' => 'petitions.generate'],
                ],
            ],
            [
                'title' => 'Biblioteca de tópicos',
                'icon' => 'library',
                'articles' => [
                    ['slug' => 'biblioteca', 'title' => 'Consultar a Biblioteca', 'summary' => 'Encontrar tópicos e ler o conteúdo dos modelos.', 'can' => 'library.view'],
                    ['slug' => 'novo-topico', 'title' => 'Novo tópico com IA', 'summary' => 'Transformar um texto de petição em modelo.', 'can' => 'topics.edit'],
                    ['slug' => 'grupos', 'title' => 'Grupos', 'summary' => 'Criar, renomear e excluir grupos.', 'can' => 'groups.manage'],
                ],
            ],
            [
                'title' => 'Administração',
                'icon' => 'shield-check',
                'articles' => [
                    ['slug' => 'usuarios', 'title' => 'Usuários', 'summary' => 'Cadastrar, editar, redefinir senha e desativar.', 'can' => 'users.manage'],
                    ['slug' => 'perfis', 'title' => 'Perfis de permissão', 'summary' => 'O que cada perfil pode fazer.', 'can' => 'roles.manage'],
                    ['slug' => 'configurar-ficha', 'title' => 'Configurar a ficha de atendimento', 'summary' => 'Assuntos, perguntas, tipos e condições.', 'can' => 'appointment_form.manage'],
                ],
            ],
            [
                'title' => 'Dúvidas',
                'icon' => 'circle-help',
                'articles' => [
                    ['slug' => 'perguntas-frequentes', 'title' => 'Perguntas frequentes', 'summary' => 'Respostas rápidas para as dúvidas mais comuns.', 'can' => null],
                ],
            ],
        ];
    }
}
