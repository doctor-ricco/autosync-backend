# AutoSync Backend

Sistema de gestão de inventário de veículos para stands automóveis em Portugal.

## 🚀 Configuração Inicial

### Pré-requisitos

- PHP 8.2+
- Composer
- PostgreSQL (via Aiven)
- Node.js 18+ (para assets)

### 1. Clonar o Repositório

```bash
git clone <repository-url>
cd AutoSync-Backend
```

### 2. Configurar Ambiente

```bash
# Copiar arquivo de exemplo
cp .env.example .env

# Gerar chave da aplicação
php artisan key:generate
```

### 3. Configurar Banco de Dados

Edite o arquivo `.env` com suas credenciais do Aiven:

```env
DB_CONNECTION=pgsql
DB_HOST=seu-host-aiven.j.aivencloud.com
DB_PORT=17084
DB_DATABASE=defaultdb
DB_USERNAME=avnadmin
DB_PASSWORD=sua-senha-aiven
DB_SSLMODE=require
```

### 4. Instalar Dependências

```bash
# Instalar dependências PHP
composer install

# Instalar dependências Node.js
npm install
```

### 5. Executar Migrações

```bash
# Criar tabelas no banco de dados
php artisan migrate

# Executar seeders (dados de exemplo)
php artisan db:seed
```

### 6. Configurar Storage

```bash
# Criar link simbólico para storage
php artisan storage:link
```

### 7. Iniciar Servidor

```bash
# Desenvolvimento
php artisan serve

# Ou com Vite para assets
npm run dev
```

## 📁 Estrutura do Projeto

```
AutoSync-Backend/
├── app/
│   ├── Http/
│   │   ├── Controllers/     # Controladores da API
│   │   ├── Middleware/      # Middlewares personalizados
│   │   └── Requests/        # Validação de requests
│   ├── Models/              # Modelos Eloquent
│   ├── Services/            # Lógica de negócio
│   └── Resources/           # API Resources
├── database/
│   ├── migrations/          # Migrações do banco
│   ├── seeders/            # Dados iniciais
│   └── factories/          # Factories para testes
├── routes/
│   ├── api.php             # Rotas da API
│   └── web.php             # Rotas web (se necessário)
└── tests/                  # Testes automatizados
```

## 🔐 Segurança

### Variáveis de Ambiente

⚠️ **IMPORTANTE**: Nunca commite o arquivo `.env` no repositório!

- O `.env.example` serve como template
- Contém apenas valores de exemplo
- Credenciais reais ficam apenas no `.env` local

### Configurações de Segurança

- API Rate Limiting configurado
- CORS configurado para frontend
- Validação de requests implementada
- Sanitização de dados

## 🧪 Testes

```bash
# Executar todos os testes
php artisan test

# Executar testes específicos
php artisan test --filter=VehicleTest

# Coverage report
php artisan test --coverage
```

## 📊 API Documentation

A documentação completa da API está disponível em:
- [Documentação da API](./docs/API.md)
- [Postman Collection](./docs/postman_collection.json)

### Endpoints Principais

- `GET /api/vehicles` - Listar veículos
- `POST /api/vehicles` - Criar veículo
- `GET /api/vehicles/{id}` - Detalhes do veículo
- `PUT /api/vehicles/{id}` - Atualizar veículo
- `DELETE /api/vehicles/{id}` - Remover veículo

## 🚀 Deploy

### Produção

1. Configurar variáveis de ambiente de produção
2. Executar `composer install --optimize-autoloader --no-dev`
3. Executar `php artisan config:cache`
4. Executar `php artisan route:cache`
5. Configurar web server (Nginx/Apache)

### Docker (Opcional)

```bash
# Construir imagem
docker build -t autosync-backend .

# Executar container
docker run -p 8000:8000 autosync-backend
```

## 🤝 Contribuição

1. Fork o projeto
2. Crie uma branch para sua feature (`git checkout -b feature/AmazingFeature`)
3. Commit suas mudanças (`git commit -m 'Add some AmazingFeature'`)
4. Push para a branch (`git push origin feature/AmazingFeature`)
5. Abra um Pull Request

## 📝 Licença

Este projeto está sob a licença MIT. Veja o arquivo [LICENSE](LICENSE) para mais detalhes.

## 📞 Suporte

Para suporte, envie um email para suporte@autosync.pt ou abra uma issue no GitHub.
