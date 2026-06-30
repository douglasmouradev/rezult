<?php require __DIR__ . '/../partials/flash.php'; ?>
<div class="page-header"><h1>Integrações</h1><p class="text-muted">Conecte serviços externos à sua empresa.</p></div>
<div class="card mb-2" style="background:#ecfdf5;border-color:#10b981">
    <p style="margin:0"><strong>Gateway Asaas:</strong> cobranças reais com confirmação automática via webhook. Open Finance e NFS-e ainda em evolução.</p>
</div>
<div class="grid-3">
    <div class="card">
        <div class="card-header" style="padding:0;margin-bottom:16px">
            <h3 class="card-title"><i class="ph ph-bank"></i> Open Finance</h3>
            <p class="card-desc">Importação automática de extratos bancários.</p>
        </div>
        <form method="post" action="/integracoes">
            <input type="hidden" name="_csrf" value="<?= $csrf ?>">
            <input type="hidden" name="provedor" value="open_finance">
            <div class="form-group">
                <label>Client ID</label>
                <input class="input" name="client_id" value="<?= htmlspecialchars($openFinance['config']['client_id'] ?? '') ?>" placeholder="Client ID">
            </div>
            <div class="form-group">
                <label>Client Secret</label>
                <input class="input" name="client_secret" type="password" value="" placeholder="<?= !empty($openFinance['config']['client_secret_preenchido']) ? '•••••••• (salvo)' : 'Client Secret' ?>">
            </div>
            <div class="form-group">
                <label>Ambiente</label>
                <select name="ambiente" class="input">
                    <option value="sandbox" <?= ($openFinance['config']['ambiente'] ?? 'sandbox') === 'sandbox' ? 'selected' : '' ?>>Sandbox</option>
                    <option value="producao" <?= ($openFinance['config']['ambiente'] ?? '') === 'producao' ? 'selected' : '' ?>>Produção</option>
                </select>
            </div>
            <label class="filter-label" style="display:flex;align-items:center;gap:8px;margin-bottom:12px">
                <input type="checkbox" name="ativo" value="1" <?= !empty($openFinance['ativo']) ? 'checked' : '' ?>>
                <span>Ativar integração</span>
            </label>
            <button type="submit" class="btn-primary">Salvar</button>
        </form>
    </div>

    <div class="card">
        <div class="card-header" style="padding:0;margin-bottom:16px">
            <h3 class="card-title"><i class="ph ph-credit-card"></i> Gateway de pagamento</h3>
            <p class="card-desc">Cobranças e recebimentos online.</p>
        </div>
        <form method="post" action="/integracoes">
            <input type="hidden" name="_csrf" value="<?= $csrf ?>">
            <input type="hidden" name="provedor" value="gateway">
            <div class="form-group">
                <label>Provedor</label>
                <select name="gateway_provedor" class="input">
                    <option value="asaas" <?= ($gateway['config']['provedor'] ?? 'asaas') === 'asaas' ? 'selected' : '' ?>>Asaas</option>
                </select>
            </div>
            <div class="form-group">
                <label>Ambiente</label>
                <select name="ambiente" class="input">
                    <option value="sandbox" <?= ($gateway['config']['ambiente'] ?? 'sandbox') === 'sandbox' ? 'selected' : '' ?>>Sandbox</option>
                    <option value="producao" <?= ($gateway['config']['ambiente'] ?? '') === 'producao' ? 'selected' : '' ?>>Produção</option>
                </select>
            </div>
            <div class="form-group">
                <label>API Key</label>
                <input class="input" name="api_key" type="password" value="" placeholder="<?= !empty($gateway['config']['api_key_preenchido']) ? '•••••••• (salvo)' : 'Chave da API Asaas' ?>">
            </div>
            <div class="form-group">
                <label>Webhook URL (configure no painel Asaas)</label>
                <input class="input" name="webhook_url" value="<?= htmlspecialchars($gateway['config']['webhook_url'] ?? (rtrim((string)($appUrl ?? ''), '/') . '/webhooks/gateway/asaas')) ?>" readonly>
                <small class="text-muted">URL fixa do Rezult para receber confirmações de pagamento.</small>
            </div>
            <div class="form-group">
                <label>Token do webhook (asaas-access-token)</label>
                <input class="input" name="webhook_token" type="password" value="" placeholder="<?= !empty($gateway['config']['webhook_token_preenchido']) ? '•••••••• (salvo)' : 'Token configurado no Asaas' ?>">
            </div>
            <label class="filter-label" style="display:flex;align-items:center;gap:8px;margin-bottom:12px">
                <input type="checkbox" name="ativo" value="1" <?= !empty($gateway['ativo']) ? 'checked' : '' ?>>
                <span>Ativar integração</span>
            </label>
            <button type="submit" class="btn-primary">Salvar</button>
        </form>
    </div>

    <div class="card">
        <div class="card-header" style="padding:0;margin-bottom:16px">
            <h3 class="card-title"><i class="ph ph-receipt"></i> NFS-e</h3>
            <p class="card-desc">Emissão de notas fiscais de serviço.</p>
        </div>
        <form method="post" action="/integracoes">
            <input type="hidden" name="_csrf" value="<?= $csrf ?>">
            <input type="hidden" name="provedor" value="nfse">
            <div class="form-group">
                <label>CNPJ</label>
                <input class="input" name="cnpj" value="<?= htmlspecialchars($nfse['config']['cnpj'] ?? '') ?>" placeholder="00.000.000/0000-00">
            </div>
            <div class="form-group">
                <label>Inscrição municipal</label>
                <input class="input" name="inscricao_municipal" value="<?= htmlspecialchars($nfse['config']['inscricao_municipal'] ?? '') ?>" placeholder="Inscrição municipal">
            </div>
            <div class="form-group">
                <label>Token</label>
                <input class="input" name="token" type="password" value="" placeholder="<?= !empty($nfse['config']['token_preenchido']) ? '•••••••• (salvo)' : 'Token de acesso' ?>">
            </div>
            <label class="filter-label" style="display:flex;align-items:center;gap:8px;margin-bottom:12px">
                <input type="checkbox" name="ativo" value="1" <?= !empty($nfse['ativo']) ? 'checked' : '' ?>>
                <span>Ativar integração</span>
            </label>
            <button type="submit" class="btn-primary">Salvar</button>
        </form>
    </div>
</div>
