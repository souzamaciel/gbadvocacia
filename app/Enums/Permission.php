<?php

namespace App\Enums;

enum Permission: string
{
    case AppointmentsCreate = 'appointments.create';
    case PetitionsEdit = 'petitions.edit';
    case PetitionsGenerate = 'petitions.generate';
    case PetitionsDelete = 'petitions.delete';
    case LibraryView = 'library.view';
    case TopicsEdit = 'topics.edit';
    case TopicsAi = 'topics.ai';
    case TopicsDelete = 'topics.delete';
    case GroupsManage = 'groups.manage';
    case UsersManage = 'users.manage';
    case RolesManage = 'roles.manage';
    case AppointmentFormManage = 'appointment_form.manage';

    public function label(): string
    {
        return match ($this) {
            self::AppointmentsCreate => 'Fazer atendimento e gerar a petição',
            self::PetitionsEdit => 'Criar e editar rascunhos',
            self::PetitionsGenerate => 'Gerar o documento Word',
            self::PetitionsDelete => 'Excluir rascunhos',
            self::LibraryView => 'Consultar tópicos',
            self::TopicsEdit => 'Criar e editar tópicos',
            self::TopicsAi => 'Usar a IA no “Novo tópico”',
            self::TopicsDelete => 'Excluir tópicos',
            self::GroupsManage => 'Gerenciar grupos',
            self::UsersManage => 'Gerenciar usuários',
            self::RolesManage => 'Gerenciar perfis de permissão',
            self::AppointmentFormManage => 'Configurar a ficha de atendimento',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::AppointmentsCreate => 'Preencher o atendimento e baixar a petição feita pela IA.',
            self::PetitionsEdit => 'Montar petições, revisar os textos e continuar rascunhos do escritório.',
            self::PetitionsGenerate => 'Baixar o Word da petição revisada.',
            self::PetitionsDelete => 'Apagar rascunhos de petição.',
            self::LibraryView => 'Abrir a Biblioteca e ver os tópicos.',
            self::TopicsEdit => 'Cadastrar tópicos novos e alterar os existentes.',
            self::TopicsAi => 'Pedir à IA as variáveis, o título e o grupo. Cada uso gera custo.',
            self::TopicsDelete => 'Apagar tópicos da Biblioteca.',
            self::GroupsManage => 'Criar, renomear e excluir grupos da Biblioteca.',
            self::UsersManage => 'Cadastrar, editar, desativar usuários e redefinir senhas.',
            self::RolesManage => 'Criar e alterar os perfis e suas permissões.',
            self::AppointmentFormManage => 'Adicionar, editar e excluir assuntos e perguntas da ficha de atendimento.',
        };
    }

    public function area(): string
    {
        return match ($this) {
            self::AppointmentsCreate => 'Atendimentos',
            self::PetitionsEdit, self::PetitionsGenerate, self::PetitionsDelete => 'Petições',
            self::LibraryView, self::TopicsEdit, self::TopicsAi, self::TopicsDelete, self::GroupsManage => 'Biblioteca',
            self::UsersManage, self::RolesManage, self::AppointmentFormManage => 'Administração',
        };
    }

    /**
     * Permission that must also be granted for this one to make sense.
     */
    public function requires(): ?self
    {
        return match ($this) {
            self::PetitionsGenerate, self::PetitionsDelete => self::PetitionsEdit,
            self::TopicsEdit, self::TopicsDelete, self::GroupsManage => self::LibraryView,
            self::TopicsAi => self::TopicsEdit,
            default => null,
        };
    }

    /**
     * @return array<string, list<self>>
     */
    public static function byArea(): array
    {
        $areas = [];

        foreach (self::cases() as $permission) {
            $areas[$permission->area()][] = $permission;
        }

        return $areas;
    }

    /**
     * Keeps only known permissions and adds the ones they depend on.
     *
     * @param  array<int, string>  $values
     * @return list<string>
     */
    public static function normalize(array $values): array
    {
        $granted = [];

        foreach ($values as $value) {
            $permission = self::tryFrom((string) $value);

            while ($permission !== null) {
                $granted[$permission->value] = true;
                $permission = $permission->requires();
            }
        }

        return array_values(array_filter(
            array_map(fn (self $permission): string => $permission->value, self::cases()),
            fn (string $value): bool => isset($granted[$value]),
        ));
    }
}
