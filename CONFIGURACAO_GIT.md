# 📦 Configuração do Git - BccSistem

## ✅ Arquivos Criados

### 1. `.gitignore`
Arquivo principal que define o que **NÃO** será versionado no repositório.

### 2. `app/config/config.php.example`
Arquivo de exemplo da configuração (sem credenciais reais) que **SERÁ** versionado.

### 3. `.gitkeep` nas pastas vazias
Arquivos especiais que mantêm a estrutura de pastas no Git:
- `logs/.gitkeep`
- `public/uploads/.gitkeep`
- `public/uploads/contratos/.gitkeep`

### 4. `README_INSTALACAO.md`
Guia completo de instalação do sistema.

---

## 🚫 O Que NÃO Será Versionado

### 📁 **Arquivos Sensíveis (Segurança)**
- ✅ `app/config/config.php` - Contém credenciais do banco de dados
- ✅ `.env` e variações
- ✅ Certificados e chaves (*.pem, *.key, *.crt)

### 📝 **Logs**
- ✅ `logs/` - Todos os arquivos de log
- ✅ `*.log` - Qualquer arquivo .log no projeto

### 📤 **Uploads de Usuários**
- ✅ `public/uploads/**/*` - Todos os arquivos enviados pelos usuários
- ❌ Exceção: `.gitkeep` e `.htaccess` serão versionados

### 📦 **Dependências**
- ✅ `vendor/` - Dependências do Composer
- ✅ `node_modules/` - Dependências do Node.js
- ✅ `composer.lock` - Lock do Composer

### 💾 **Backups e Dumps**
- ✅ `*.sql`, `*.sql.gz` - Dumps do banco de dados
- ✅ `backup/`, `backups/` - Pastas de backup

### 🖥️ **Arquivos do Sistema Operacional**
- ✅ Windows: `Thumbs.db`, `Desktop.ini`, `*.lnk`
- ✅ macOS: `.DS_Store`, `._*`
- ✅ Linux: `*~`, `.directory`

### 🛠️ **IDEs e Editores**
- ✅ `.vscode/` - Visual Studio Code
- ✅ `.idea/` - PhpStorm / JetBrains
- ✅ `*.sublime-*` - Sublime Text
- ✅ `.settings/`, `nbproject/` - Eclipse, NetBeans

### 📦 **Arquivos Temporários e Cache**
- ✅ `cache/`, `tmp/`, `temp/`
- ✅ `*.cache`, `*.tmp`
- ✅ `.phpunit.result.cache`

### 📁 **Arquivos Compactados**
- ✅ `*.zip`, `*.rar`, `*.7z`, `*.tar.gz`

---

## ✔️ O Que SERÁ Versionado

### 📄 **Código Fonte**
- ✅ Todos os arquivos `.php`
- ✅ Todos os arquivos `.js`, `.css`
- ✅ HTML e templates

### ⚙️ **Configurações de Exemplo**
- ✅ `app/config/config.php.example` - Template de configuração
- ✅ `app/config/administradoras.php`
- ✅ `app/config/interesses.php`

### 📚 **Documentação**
- ✅ `README.md`
- ✅ `README_INSTALACAO.md`
- ✅ `LICENSE`
- ✅ Todos os arquivos `.md`

### 🏗️ **Estrutura de Pastas**
- ✅ `.gitkeep` - Mantém pastas vazias no Git
- ✅ `.htaccess` - Configurações do Apache

### 📦 **Gerenciamento de Dependências**
- ✅ `composer.json` - Definições de dependências

---

## 🔄 Comandos Git Importantes

### Verificar status (incluindo ignorados)
```bash
git status --ignored
```

### Adicionar novos arquivos
```bash
git add .
```

### Commitar alterações
```bash
git commit -m "Sua mensagem aqui"
```

### Ver o que está sendo ignorado
```bash
git check-ignore -v logs/ vendor/ app/config/config.php
```

### Forçar adicionar arquivo ignorado (cuidado!)
```bash
git add -f arquivo_ignorado.php
```

---

## 📋 Checklist de Primeira Configuração

Quando clonar o repositório pela primeira vez:

- [ ] Copiar `app/config/config.php.example` para `app/config/config.php`
- [ ] Editar `app/config/config.php` com suas credenciais
- [ ] Executar `composer install`
- [ ] Criar o banco de dados
- [ ] Importar a estrutura do banco
- [ ] Executar `php scripts/seed_admin.php`
- [ ] Verificar permissões das pastas `logs/` e `public/uploads/`

---

## ⚠️ IMPORTANTE

### Arquivos Sensíveis
**NUNCA** versione arquivos com credenciais ou dados sensíveis:
- ❌ Não commite `app/config/config.php`
- ❌ Não commite arquivos `.env`
- ❌ Não commite backups do banco de dados
- ❌ Não commite certificados ou chaves

### Antes de Fazer Push
Sempre revise o que será enviado:
```bash
git status
git diff
```

### Se Acidentalmente Commitar Arquivo Sensível
```bash
# Remover do histórico (cuidado!)
git rm --cached app/config/config.php
git commit -m "Remove arquivo sensível"

# IMPORTANTE: Trocar as credenciais expostas!
```

---

## 🎯 Boas Práticas

1. ✅ **Sempre** use `.gitignore` antes do primeiro commit
2. ✅ Mantenha arquivos de exemplo (*.example)
3. ✅ Use `.gitkeep` para pastas vazias importantes
4. ✅ Documente a instalação no README
5. ✅ Revise os arquivos antes de commitar
6. ✅ Use mensagens de commit descritivas
7. ✅ Nunca force push para main/master

---

## 📞 Dúvidas

Se tiver dúvidas sobre o que deve ou não ser versionado, consulte este documento ou a equipe de desenvolvimento.

