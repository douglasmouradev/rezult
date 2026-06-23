<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\App;
use App\Core\View;
use App\Helpers\Sanitize;
use App\Helpers\Session;
use App\Helpers\Upload;
use App\Helpers\Validator;
use App\Models\Usuario;
use App\Policies\TenantPolicy;
use App\Services\AuditoriaService;

final class PerfilController
{
    public function index(): void
    {
        $uid = TenantPolicy::usuarioId();
        View::render('perfil/index', [
            'title' => 'Meu perfil',
            'usuario' => (new Usuario())->find($uid),
        ]);
    }

    public function atualizar(): void
    {
        $uid = TenantPolicy::usuarioId();
        $nome = Sanitize::raw($_POST['nome'] ?? '');
        if ($nome === '') {
            Session::flash('error', 'Nome é obrigatório.');
            View::redirect('/perfil');
        }
        App::pdo()->prepare('UPDATE usuarios SET nome = :n WHERE id = :id')->execute(['n' => $nome, 'id' => $uid]);
        $iaConsent = !empty($_POST['ia_consentimento']) ? 1 : 0;
        App::pdo()->prepare('UPDATE usuarios SET ia_consentimento = :ia WHERE id = :id')
            ->execute(['ia' => $iaConsent, 'id' => $uid]);
        if (!empty($_FILES['avatar']['name'])) {
            $path = Upload::storeForUser($_FILES['avatar'], 'avatars', $uid);
            if ($path) {
                App::pdo()->prepare('UPDATE usuarios SET avatar_url = :u WHERE id = :id')
                    ->execute(['u' => '/arquivo?path=' . urlencode($path), 'id' => $uid]);
            }
        }
        $u = (new Usuario())->find($uid);
        Session::set('usuario', ['id' => $uid, 'nome' => $u['nome'], 'email' => $u['email'], 'avatar_url' => $u['avatar_url'] ?? null]);
        AuditoriaService::registrar('perfil_atualizado', 'usuario', $uid);
        Session::flash('success', 'Perfil atualizado.');
        View::redirect('/perfil');
    }

    public function senha(): void
    {
        $uid = TenantPolicy::usuarioId();
        $v = new Validator($_POST);
        $v->required('senha_atual', 'senha', 'senha_confirmacao')->min('senha', 8)->password('senha');
        if ($_POST['senha'] !== ($_POST['senha_confirmacao'] ?? '')) {
            Session::flash('error', 'Senhas não coincidem.');
            View::redirect('/perfil');
        }
        $usuario = (new Usuario())->find($uid);
        if (!(new Usuario())->verificarSenha($usuario, $_POST['senha_atual'] ?? '')) {
            Session::flash('error', 'Senha atual incorreta.');
            View::redirect('/perfil');
        }
        if ($v->fails()) {
            Session::flash('error', $v->first());
            View::redirect('/perfil');
        }
        App::pdo()->prepare('UPDATE usuarios SET senha_hash = :h WHERE id = :id')->execute([
            'h' => password_hash($_POST['senha'], PASSWORD_BCRYPT, ['cost' => 12]),
            'id' => $uid,
        ]);
        Session::flash('success', 'Senha alterada.');
        View::redirect('/perfil');
    }
}
