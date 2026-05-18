<article class="legal-page">
    <h1>Política de Privacidade</h1>
    <p class="text-muted">Última atualização: <?= date('d/m/Y') ?> · Conforme LGPD (Lei 13.709/2018)</p>

    <section>
        <h2>1. Controlador</h2>
        <p>O Rezult atua como controlador dos dados pessoais coletados na plataforma de gestão financeira.</p>
    </section>
    <section>
        <h2>2. Dados coletados</h2>
        <ul>
            <li>Identificação: nome, e-mail</li>
            <li>Dados financeiros da empresa: lançamentos, contas, categorias</li>
            <li>Registros técnicos: IP, data/hora de acesso (segurança)</li>
        </ul>
    </section>
    <section>
        <h2>3. Finalidades e bases legais</h2>
        <ul>
            <li>Execução de contrato — prestação do serviço SaaS</li>
            <li>Consentimento — comunicações de marketing (opcional)</li>
            <li>Legítimo interesse — segurança, prevenção a fraudes e auditoria</li>
        </ul>
    </section>
    <section>
        <h2>4. Seus direitos</h2>
        <p>Acesso, correção, portabilidade, eliminação, revogação de consentimento e informação sobre compartilhamento.</p>
        <?php if (!empty($_SESSION['usuario_id'])): ?>
        <p><a class="btn btn-primary" href="/privacidade/meus-dados">Gerenciar meus dados</a></p>
        <?php endif; ?>
    </section>
    <section>
        <h2>5. Retenção e segurança</h2>
        <p>Dados são armazenados enquanto a conta estiver ativa. Exclusão anonimiza registros conforme solicitação. Utilizamos criptografia de senha, CSRF, isolamento multi-empresa e logs de auditoria.</p>
    </section>
    <section>
        <h2>6. Encarregado (DPO)</h2>
        <p>Contato: <a href="mailto:<?= htmlspecialchars(\App\Core\App::config('lgpd_dpo_email')) ?>"><?= htmlspecialchars(\App\Core\App::config('lgpd_dpo_email')) ?></a></p>
    </section>
</article>
