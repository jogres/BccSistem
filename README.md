# BCC — Sistema de Cadastro e Dashboard (PHP + MySQL)

Aplicação web em **PHP (PDO)** com **login seguro**, **controle de acesso por perfis** (ADMIN / PADRAO / APRENDIZ), **cadastro de clientes**, **gestão de funcionários (ADMIN)** e **dashboard** com **gráficos de barra** (dia/semana/mês) e **comparação entre usuários**.

<p align="center">
  <img alt="PHP" src="https://img.shields.io/badge/PHP-7.4%2B-777BB4">
  <img alt="DB" src="https://img.shields.io/badge/MySQL%2FMariaDB-5.7%2B%2F10.3%2B-4479A1">
  <img alt="License" src="https://img.shields.io/badge/license-MIT-green">
</p>

---

## ✨ Funcionalidades

- **Autenticação** com `password_hash()` / `password_verify()` e regeneração de ID de sessão.
- **RBAC**:
  - **ADMIN**: acesso total; gerencia funcionários; compara qualquer usuário no dashboard.
  - **PADRAO / APRENDIZ**: cadastram clientes; veem apenas seus próprios clientes.
- **Clientes**: listar, criar, editar, **excluir lógico** (soft delete).
- **Funcionários (ADMIN)**: listar, criar e **editar** (nome, login, senha opcional, perfil e ativo/inativo).
- **Dashboard**:
  - Modos **Diário / Semanal / Mensal**, comparação multiusuário.
  - Gráficos **de barra** (Chart.js) responsivos, com rótulos e tooltips amigáveis.
  - API JSON: `public/api/dashboard_counts.php`.

---

## 📁 Estrutura

```
app/
  config/config.php
  lib/{Database.php, Auth.php, CSRF.php, Helpers.php}
  middleware/{require_login.php, require_admin.php}
  models/{Funcionario.php, Cliente.php, Dashboard.php}
  views/partials/{header.php, footer.php}
public/
  index.php, login.php, logout.php, dashboard.php
  clientes/{index.php, create.php, edit.php, delete.php}
  funcionarios/{index.php, create.php, edit.php}
  api/dashboard_counts.php
  assets/{css/style.css, js/dashboard.js}
scripts/
  seed_admin.php
docs/
  screenshots/{login.png, dashboard-week.png, dashboard-month.png, dashboard-compare.png}
```

---

## 🧩 Requisitos

- **PHP 7.4+** (recomendado PHP 8+)
- **MySQL 5.7+** ou **MariaDB 10.3+**
- **Servidor Web** (ex.: Apache via XAMPP)

---

## 🚀 Instalação

1. **Clonar**
   ```bash
   git clone https://github.com/seu-usuario/bcc-app.git
   cd bcc-app
   ```

2. **Banco de dados**  
   Execute o DDL do projeto (tabelas `roles`, `funcionarios`, `clientes`, `audit_logs`, etc.) no seu MySQL/MariaDB.

3. **Configuração**
   Edite `app/config/config.php`:
   ```php
   return [
     'db' => [
       'host'    => '127.0.0.1',
       'dbname'  => 'bcc',
       'user'    => 'root',
       'pass'    => '',
       'charset' => 'utf8mb4',
     ],
     'app' => [
       'base_url' => '/bcc-app/public' // ajuste conforme sua pasta/host
     ],
   ];
   ```

4. **Seed do ADMIN (CLI)**  
   - **XAMPP (Windows)**:
     ```bat
     cd F:\xampp\htdocs\bcc-app
     "F:\xampp\php\php.exe" scripts\seed_admin.php
     ```
     *(ou adicione `F:\xampp\php` ao PATH e rode `php scripts\seed_admin.php`)*

5. **Acessar**
   - Inicie o Apache no XAMPP
   - Navegue para `http://localhost/bcc-app/public/login.php`  
     Login inicial (seed): **admin / admin123** → altere a senha após o primeiro acesso.

---

## 🖼️ Screenshots

Coloque suas imagens em `docs/screenshots/` e mantenha os nomes abaixo (ou ajuste os caminhos).

| Tela | Imagem |
|------|--------|
| Login | `docs/screenshots/login.png` |
| Dashboard (Semanal) | `docs/screenshots/dashboard-week.png` |
| Dashboard (Mensal) | `docs/screenshots/dashboard-month.png` |
| Dashboard (Comparação) | `docs/screenshots/dashboard-compare.png` |

Exemplo de inclusão no README:

```markdown
![Dashboard Semanal](docs/screenshots/dashboard-week.png)
```

---

## 🖥️ Uso

- **Clientes**: *Clientes → Novo* para cadastrar; edite/exclua na listagem.
- **Funcionários (ADMIN)**: *Funcionários → Novo/Editar* (senha opcional na edição; “Ativo = Não” oculta da lista padrão).
- **Dashboard**:
  - **Modo**: *Diário / Semanal / Mensal*;
  - (ADMIN) **Comparar usuários** com seleção múltipla;
  - Gráfico **de barras** com nomes legíveis no eixo inferior e valores sobre as barras.

---

## ⚙️ Configurações de Gráfico (escala do eixo Y)

Para começar sempre em **0** e mostrar uma escala **0→10** (ou 20/30), edite `public/assets/js/dashboard.js` (bloco do Chart.js):

```js
scales: {
  y: {
    beginAtZero: true,
    suggestedMax: 10,   // troque para 20 ou 30
    ticks: { stepSize: 1, precision: 0 }
  }
}
```

- `suggestedMax` define um **teto flexível** (se os dados passarem, o gráfico expande).  
- Para teto **fixo**, use `min: 0` e `max: 10/20/30`.

---

## 🔐 Segurança — boas práticas do projeto

- **Senhas** com `password_hash()` / `password_verify()`.
- **Sessões**: cookies `HttpOnly`, `SameSite=Lax` (e `Secure` em HTTPS), iniciar sessão só após definir parâmetros.
- **SQL**: **PDO** com prepared statements (placeholders nomeados) em todas as queries.

---

## 🧪 API

`GET public/api/dashboard_counts.php`  
**Parâmetros**
- `mode=day|week|month`
- `start=YYYY-MM-DD&end=YYYY-MM-DD` (modo *week*)
- `month=YYYY-MM` (modo *month*), `day=YYYY-MM-DD` (modo *day*)
- (ADMIN) `users[]=1&users[]=2...`

**Resposta**
```json
{
  "ok": true,
  "mode": "week",
  "start": "2025-09-15",
  "end": "2025-09-21",
  "labels": ["2025-W38", "..."],
  "series": {
    "12": { "name": "Fulano", "data": [2, ...], "total": 7 },
    "27": { "name": "Ciclano", "data": [3, ...], "total": 9 }
  }
}
```
> A UI converte rótulos técnicos (`YYYY-W##` / `YYYY-MM` / `YYYY-MM-DD`) em textos amigáveis.

---

## 📝 Changelog

```markdown
## [Unreleased]
### Added
- Filtro “Ativos/Inativos/Todos” em Funcionários.
- Dashboard com barras (dia/semana/mês) e comparação multiusuário.

### Changed
- Eixo/tooltip com rótulos amigáveis de período.

### Fixed
- Placeholders PDO nomeados (erro HY093).
- Sessão: `session_set_cookie_params()` antes do `session_start()`.

## [1.0.0] - 2025-09-01
### Added
- Login + RBAC (ADMIN/PADRAO/APRENDIZ).
- CRUD de Clientes com soft delete.
- CRUD de Funcionários (ADMIN).
- Dashboard básico com API.
```

---

## 🤝 Contribuindo

1. Faça um fork
2. Crie uma branch (`feat/minha-ideia`)
3. Commit e PR

---

## 📄 Licença

[MIT](LICENSE)
