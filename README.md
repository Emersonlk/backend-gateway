<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## Sobre o projeto

API RESTful em Laravel para um sistema de pagamentos **multi-gateway**, com:

- Autenticação via Sanctum e roles (`ADMIN`, `MANAGER`, `FINANCE`, `USER`).
- Compra com múltiplos produtos calculada no back-end.
- Integração com dois gateways mockados via Docker (`matheusprotzen/gateways-mock`) com fallback por prioridade.
- Rotas protegidas por roles (CRUD de usuários/produtos, gateways, clientes, transações, reembolso).
- Testes automatizados (feature + unit) cobrindo os principais fluxos.

## Requisitos

- PHP 8.3+
- Composer
- Docker e Docker Compose

## Instalação (sem Docker)

```bash
git clone <repo>
cd backend-gateway

composer install
cp .env.example .env
php artisan key:generate

php artisan migrate --seed

php artisan serve
```

Aplicação disponível em `http://localhost:8000`.

## Rodando com Docker Compose

```bash
docker-compose up -d --build
```

Serviços:

- `app`: Laravel rodando em `http://localhost:8000`
- `mysql`: MySQL 8 em `localhost:3306` (database `payments`, usuário `root` / senha `rootroot`)
- `gateways-mock`: mocks dos gateways nas portas `3001` e `3002`

As URLs internas usadas pela aplicação são:

- Gateway 1: `http://gateways-mock:3001`
- Gateway 2: `http://gateways-mock:3002`

## Rotas principais

### Públicas

- `POST /api/login` – login (retorna token Sanctum).
- `POST /api/purchase` – realiza compra com múltiplos produtos.

### Protegidas (`Authorization: Bearer {token}`)

- `GET /api/user` – dados do usuário autenticado.
- `POST /api/logout`
- `GET /api/gateways` – listar gateways (ADMIN).
- `POST /api/gateways/{gateway}/toggle` – ativar/desativar (ADMIN).
- `PATCH /api/gateways/{gateway}/priority` – alterar prioridade (ADMIN).
- `apiResource /api/users` – CRUD de usuários (ADMIN, MANAGER).
- `apiResource /api/products` – CRUD de produtos (ADMIN, MANAGER, FINANCE).
- `GET /api/clients` – listar clientes.
- `GET /api/clients/{client}` – detalhe + compras.
- `GET /api/transactions` – listar transações.
- `GET /api/transactions/{transaction}` – detalhe.
- `POST /api/transactions/{transaction}/refund` – reembolso (ADMIN, FINANCE).

Para detalhes de payloads, consulte a collection Postman em `postman/Backend Gateway API.postman_collection.json`.

## Testes

```bash
php artisan test
```
