<nav class="lp-nav" id="lp-nav">
    <div class="lp-container lp-nav-inner">
        <a href="/" class="lp-logo">
            <span class="lp-logo-mark">R</span>
            Rezult
        </a>
        <button type="button" class="lp-menu-toggle" aria-label="Menu" id="lp-menu-btn">
            <i class="ph ph-list"></i>
        </button>
        <div class="lp-nav-links" id="lp-nav-links">
            <a href="#recursos">Recursos</a>
            <a href="#como-funciona">Como funciona</a>
            <a href="#planos">Planos</a>
            <a href="#faq">FAQ</a>
        </div>
        <div class="lp-nav-cta">
            <a href="/login" class="lp-btn lp-btn-ghost">Entrar</a>
            <a href="/cadastro" class="lp-btn lp-btn-primary">Começar grátis</a>
        </div>
    </div>
</nav>

<header class="lp-hero">
    <div class="lp-container lp-hero-grid">
        <div>
            <div class="lp-hero-badge">
                <i class="ph ph-sparkle"></i>
                Gestão financeira para PMEs
            </div>
            <h1>Controle total das finanças da sua <span>empresa</span></h1>
            <p class="lp-hero-lead">
                Organize receitas, despesas, fluxo de caixa e relatórios em um só lugar.
                Simples como planilha, poderoso como um ERP — com segurança e LGPD.
            </p>
            <div class="lp-hero-actions">
                <a href="/cadastro" class="lp-btn lp-btn-primary lp-btn-lg">
                    Criar conta gratuita <i class="ph ph-arrow-right"></i>
                </a>
                <a href="/login" class="lp-btn lp-btn-ghost lp-btn-lg">Já tenho conta</a>
            </div>
            <div class="lp-hero-trust">
                <span><i class="ph ph-shield-check"></i> Dados isolados por empresa</span>
                <span><i class="ph ph-lock"></i> Criptografia e CSRF</span>
                <span><i class="ph ph-scales"></i> Conformidade LGPD</span>
            </div>
        </div>
        <div class="lp-mockup" aria-hidden="true">
            <div class="lp-mockup-bar"><span></span><span></span><span></span></div>
            <div class="lp-mockup-body">
                <div class="lp-stat-row">
                    <div class="lp-stat-card receita"><small>Receitas do mês</small><strong>R$ 84.250</strong></div>
                    <div class="lp-stat-card despesa"><small>Despesas do mês</small><strong>R$ 52.180</strong></div>
                </div>
                <div class="lp-chart-bar">
                    <div style="height:45%"></div>
                    <div style="height:70%"></div>
                    <div style="height:55%"></div>
                    <div style="height:90%"></div>
                    <div style="height:65%"></div>
                    <div style="height:80%"></div>
                </div>
            </div>
        </div>
    </div>
</header>

<section class="lp-logos">
    <div class="lp-container">
        <p>Feito para quem precisa de clareza financeira no dia a dia</p>
        <div class="lp-logos-grid">
            <span>Comércio</span>
            <span>Serviços</span>
            <span>Agências</span>
            <span>Consultorias</span>
            <span>Startups</span>
        </div>
    </div>
</section>

<?php require __DIR__ . '/_showcase.php'; ?>


<section class="lp-section lp-section-alt" id="como-funciona">
    <div class="lp-container">
        <div class="lp-section-header">
            <h2>Comece em minutos</h2>
            <p>Sem instalação complexa. Acesse pelo navegador e convide sua equipe.</p>
        </div>
        <div class="lp-steps">
            <div class="lp-step">
                <div class="lp-step-num">1</div>
                <h3>Crie sua conta</h3>
                <p>Cadastro gratuito com confirmação por e-mail.</p>
            </div>
            <div class="lp-step">
                <div class="lp-step-num">2</div>
                <h3>Configure a empresa</h3>
                <p>Contas, categorias e centros de custo.</p>
            </div>
            <div class="lp-step">
                <div class="lp-step-num">3</div>
                <h3>Lance movimentações</h3>
                <p>Manual, importação ou via API.</p>
            </div>
            <div class="lp-step">
                <div class="lp-step-num">4</div>
                <h3>Analise resultados</h3>
                <p>Dashboard e relatórios sempre atualizados.</p>
            </div>
        </div>
    </div>
</section>

<section class="lp-section">
    <div class="lp-container">
        <div class="lp-lgpd">
            <div>
                <h2>Privacidade e LGPD desde o primeiro dia</h2>
                <p>Seus dados e os da sua empresa tratados com transparência, consentimento e direitos do titular.</p>
                <ul>
                    <li><i class="ph ph-check-circle"></i> Política de privacidade e termos claros</li>
                    <li><i class="ph ph-check-circle"></i> Exportação e exclusão de dados pessoais</li>
                    <li><i class="ph ph-check-circle"></i> Auditoria de ações e isolamento multi-tenant</li>
                </ul>
            </div>
            <a href="/privacidade" class="lp-btn lp-btn-ghost lp-lgpd-link">Saiba mais</a>
        </div>
    </div>
</section>

<section class="lp-section" id="planos">
    <div class="lp-container">
        <div class="lp-section-header">
            <h2>Planos que crescem com você</h2>
            <p>Comece sem custo. Escale quando precisar de mais empresas e usuários.</p>
        </div>
        <div class="lp-pricing">
            <div class="lp-plan">
                <h3>Starter</h3>
                <div class="lp-plan-price">R$ 0<small>/mês</small></div>
                <ul>
                    <li><i class="ph ph-check"></i> 1 empresa</li>
                    <li><i class="ph ph-check"></i> Lançamentos ilimitados</li>
                    <li><i class="ph ph-check"></i> Dashboard e relatórios</li>
                    <li><i class="ph ph-check"></i> 1 usuário</li>
                </ul>
                <a href="/cadastro" class="lp-btn lp-btn-ghost lp-btn-block">Começar grátis</a>
            </div>
            <div class="lp-plan featured">
                <span class="lp-plan-badge">Mais popular</span>
                <h3>Pro</h3>
                <div class="lp-plan-price">R$ 79<small>/mês</small></div>
                <ul>
                    <li><i class="ph ph-check"></i> Até 5 empresas</li>
                    <li><i class="ph ph-check"></i> Equipe e convites</li>
                    <li><i class="ph ph-check"></i> API e exportações</li>
                    <li><i class="ph ph-check"></i> Orçamento e centros de custo</li>
                </ul>
                <a href="/cadastro" class="lp-btn lp-btn-primary lp-btn-block">Testar 14 dias</a>
            </div>
            <div class="lp-plan">
                <h3>Business</h3>
                <div class="lp-plan-price lp-plan-price-text">Sob consulta</div>
                <ul>
                    <li><i class="ph ph-check"></i> Empresas ilimitadas</li>
                    <li><i class="ph ph-check"></i> SLA e suporte prioritário</li>
                    <li><i class="ph ph-check"></i> Onboarding dedicado</li>
                    <li><i class="ph ph-check"></i> Personalizações</li>
                </ul>
                <a href="mailto:contato@rezult.app" class="lp-btn lp-btn-ghost lp-btn-block">Falar com vendas</a>
            </div>
        </div>
    </div>
</section>

<section class="lp-section" id="faq">
    <div class="lp-container">
        <div class="lp-section-header">
            <h2>Perguntas frequentes</h2>
        </div>
        <div class="lp-faq">
            <details class="lp-faq-item" open>
                <summary>O Rezult substitui minha planilha? <i class="ph ph-caret-down"></i></summary>
                <p>Sim, para controle financeiro operacional. Você ganha categorias, múltiplas contas, relatórios automáticos e histórico seguro — sem fórmulas que quebram.</p>
            </details>
            <details class="lp-faq-item">
                <summary>Posso usar em mais de uma empresa? <i class="ph ph-caret-down"></i></summary>
                <p>Com um único login você alterna entre empresas e mantém os dados totalmente separados.</p>
            </details>
            <details class="lp-faq-item">
                <summary>Meus dados estão seguros? <i class="ph ph-caret-down"></i></summary>
                <p>Utilizamos criptografia de senha, proteção CSRF, isolamento por empresa e trilha de auditoria. Consulte nossa política de privacidade.</p>
            </details>
            <details class="lp-faq-item">
                <summary>Há aplicativo mobile? <i class="ph ph-caret-down"></i></summary>
                <p>O sistema é responsivo e funciona no navegador do celular. App nativo está no roadmap.</p>
            </details>
        </div>
    </div>
</section>

<section class="lp-section">
    <div class="lp-container">
        <div class="lp-cta">
            <h2>Pronto para ter clareza financeira?</h2>
            <p>Crie sua conta em menos de 2 minutos. Sem cartão de crédito no plano gratuito.</p>
            <a href="/cadastro" class="lp-btn lp-btn-primary lp-btn-lg">Começar agora — é grátis</a>
        </div>
    </div>
</section>

<footer class="lp-footer">
    <div class="lp-container">
        <div class="lp-footer-grid">
            <div>
                <a href="/" class="lp-logo lp-logo-footer">
                    <span class="lp-logo-mark">R</span> Rezult
                </a>
                <p class="lp-footer-tagline">
                    Gestão financeira empresarial clara, segura e em conformidade com a LGPD.
                </p>
            </div>
            <div>
                <h4>Produto</h4>
                <a href="#recursos">Recursos</a>
                <a href="#planos">Planos</a>
                <a href="/login">Entrar</a>
            </div>
            <div>
                <h4>Legal</h4>
                <a href="/privacidade">Privacidade</a>
                <a href="/termos">Termos de uso</a>
            </div>
            <div>
                <h4>Contato</h4>
                <a href="mailto:contato@rezult.app">contato@rezult.app</a>
                <a href="mailto:privacidade@rezult.app">DPO / Privacidade</a>
            </div>
        </div>
        <div class="lp-footer-bottom">
            <span>© <?= date('Y') ?> Rezult. Todos os direitos reservados.</span>
            <span>Gestão financeira moderna para PMEs.</span>
        </div>
    </div>
</footer>
