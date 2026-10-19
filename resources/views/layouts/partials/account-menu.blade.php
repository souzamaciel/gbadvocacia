<li>
    <a class="dropdown-item" href="{{ route('profile.edit') }}">
        <i data-lucide="user-round"></i> Meu perfil
    </a>
</li>
<li>
    <a class="dropdown-item" href="{{ route('profile.edit') }}#senha">
        <i data-lucide="key-round"></i> Alterar senha
    </a>
</li>
<li>
    <a class="dropdown-item" href="{{ route('help.index') }}">
        <i data-lucide="book-open"></i> Base de conhecimento
    </a>
</li>
<li><hr class="dropdown-divider my-1"></li>
<li>
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="dropdown-item">
            <i data-lucide="log-out"></i> Sair
        </button>
    </form>
</li>
