# 🚀 Instalação do BccSistem

## 📋 Pré-requisitos

- PHP 8.0 ou superior
- MySQL 5.7 ou superior
- Servidor Web (Apache/Nginx)
- Composer
- Extensões PHP necessárias:
  - PDO
  - pdo_mysql
  - mbstring
  - json
  - fileinfo

## 🔧 Passos para Instalação

### 1. Clone o Repositório
```bash
git clone [URL_DO_REPOSITORIO]
cd BccSistem
```

### 2. Instale as Dependências
```bash
composer install
```

### 3. Configure o Banco de Dados

#### 3.1. Crie o banco de dados
```sql
CREATE DATABASE bcc CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

#### 3.2. Importe a estrutura
```bash
mysql -u root -p bcc < database/schema.sql
```

### 4. Configure a Aplicação

#### 4.1. Copie o arquivo de configuração
```bash
cp app/config/config.php.example app/config/config.php
```

#### 4.2. Edite o arquivo `app/config/config.php` com suas credenciais:
```php
return [
    'db' => [
        'host'    => '127.0.0.1',
        'dbname'  => 'bcc',              // Nome do seu banco
        'user'    => 'seu_usuario',      // Seu usuário MySQL
        'pass'    => 'sua_senha',        // Sua senha MySQL
        'charset' => 'utf8mb4',
    ],
    'app' => [
        'base_url' => '/BccSistem/public', // Ajuste conforme sua instalação
        'timezone' => 'America/Sao_Paulo',
    ],
];
```

### 5. Configure as Permissões
```bash
# Linux/Mac
chmod -R 755 public/uploads
chmod -R 755 logs

# Windows - não é necessário
```

### 6. Crie o Usuário Administrador
```bash
php scripts/seed_admin.php
```

**Credenciais padrão:**
- Login: `admin`
- Senha: `admin123`

⚠️ **IMPORTANTE:** Altere a senha após o primeiro login!

### 7. Acesse o Sistema

Abra seu navegador e acesse:
```
http://localhost/BccSistem/public
```

Ou configure seu servidor web para apontar para a pasta `public/`.

## 📁 Estrutura de Pastas

```
BccSistem/
├── app/
│   ├── config/         # Arquivos de configuração
│   ├── lib/            # Bibliotecas e classes
│   ├── middleware/     # Middleware de autenticação
│   ├── models/         # Models do sistema
│   └── views/          # Views compartilhadas
├── logs/               # Logs do sistema (não versionado)
├── public/             # Ponto de entrada público
│   ├── assets/         # CSS, JS, imagens
│   ├── uploads/        # Arquivos enviados (não versionado)
│   ├── clientes/       # Páginas de clientes
│   ├── vendas/         # Páginas de vendas
│   └── funcionarios/   # Páginas de funcionários
├── scripts/            # Scripts auxiliares
└── vendor/             # Dependências (não versionado)
```

## 🔒 Segurança

- O arquivo `app/config/config.php` **NÃO** é versionado (contém credenciais)
- Logs e uploads **NÃO** são versionados
- Sempre use HTTPS em produção
- Altere as credenciais padrão do administrador
- Mantenha o PHP e dependências atualizados

## 🐛 Problemas Comuns

### Erro de conexão com o banco de dados
- Verifique as credenciais em `config.php`
- Confirme que o MySQL está rodando
- Verifique se o banco de dados foi criado

### Erro de permissões
- Certifique-se que as pastas `logs/` e `public/uploads/` têm permissões de escrita

### Páginas sem estilo (CSS não carrega)
- Verifique o `base_url` em `config.php`
- Ajuste conforme a estrutura do seu servidor

## 📞 Suporte

Para dúvidas ou problemas, consulte a documentação ou entre em contato com a equipe de desenvolvimento.

