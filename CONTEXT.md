# Smart Team - Contexto do Projeto

## Sobre o Projeto
Sistema web para controle e acompanhamento de chamados de acesso para onboarding de novos colaboradores. Substitui planilha Excel, oferecendo interface intuitiva e gerenciamento centralizado.

## Stack
- **Backend:** PHP 8.5.5 + Laravel 12 (em `backend/`)
- **Frontend:** Vue.js 3 (Composition API) + Vite (em `frontend/`)
- **Banco:** MySQL 8 (database: `smartteam`)
- **Auth:** Laravel Sanctum (token-based)
- **CSS:** Tailwind CSS 4
- **State:** Pinia
- **Pacotes extras:** laravel/boost (dev)

## Status da Implementação (2026-04-13)

### Backend - COMPLETO
- [x] Projeto Laravel criado e configurado
- [x] `.env` configurado (MySQL, Sanctum, CORS)
- [x] APP_KEY gerada
- [x] Enums: `UserRole`, `TicketStatus`
- [x] Migrations: users (com role), employees, applications, database_systems, access_types, tickets, ticket_logs, ticket_templates, personal_access_tokens
- [x] Models: User, Employee, Application, DatabaseSystem, AccessType, Ticket (com encrypt/decrypt de senha), TicketLog, TicketTemplate
- [x] Form Requests: LoginRequest, StoreEmployeeRequest, StoreTicketRequest, UpdateTicketRequest, BatchTicketRequest
- [x] Resources: EmployeeResource, TicketResource, TicketLogResource, DashboardResource
- [x] Controllers: AuthController (login/logout/me + rate limiting), DashboardController, EmployeeController, TicketController, ApplicationController, DatabaseSystemController, ReportController (CSV export)
- [x] Services: TicketService (create, batch, template, status update, duplicate structure, logs)
- [x] Middleware: CheckRole (admin/operator)
- [x] Routes: 35 rotas registradas (auth, dashboard, employees, tickets, applications, database-systems, reports)
- [x] Seeder: dados realistas (2 users, 7 apps, 10+ access types, 22+ DB systems, 3 employees, 25+ tickets, templates)

### Frontend - COMPLETO (refatorado com design system em 2026-04-14)
- [x] Vite + Vue 3 + Tailwind CSS 4 + Lucide Icons + Inter font
- [x] Design System: CSS variables para cores, sombras, bordas, transições
- [x] Componentes base UI: BaseCard, BaseModal, BaseBadge, BaseSkeleton, BaseToggle, BaseToast, ToastContainer, ConfirmDialog
- [x] Sidebar lateral escura com submenus expansíveis (TheSidebar), responsiva (drawer mobile, collapse tablet)
- [x] Topbar com breadcrumb, sino de notificações (badge pulsante), dropdown de usuário (TheTopbar)
- [x] Layouts: AppLayout (sidebar+topbar+content), AuthLayout (gradiente para login)
- [x] Router com guards (auth, admin) usando layouts
- [x] Pinia stores (auth, tickets), Composables (useAuth, useTickets, useEmployees, useToast)
- [x] LoginView: gradiente, ícones nos inputs, spinner no botão
- [x] DashboardView: summary cards com ícones/borda colorida/click-to-filter, tabs (Chamados/Por Colaborador), skeleton loading, empty states
- [x] EmployeeCreateView: formulário com labels, validação, template/duplicação
- [x] TicketCreateView: busca de colaborador, criação em lote com cards
- [x] TicketReportView: tabela estilizada, filtros, bulk toolbar flutuante, paginação com "Exibindo X-Y de Z"
- [x] EmployeeDetailView: avatar, progress bar, grid de dados, timeline vertical de logs
- [x] AdminCatalogView: tabs pill-style, modals de criação/edição, toggle switches, confirm dialog
- [x] Micro-interações: fade-in, scale buttons, pulse badges, shimmer skeletons, page transitions
- [x] Build limpo sem erros nem warnings

### Pendente
- [ ] Criar banco MySQL `smartteam` e rodar migrations + seeders
- [ ] Testar login e fluxo completo no browser
- [ ] Ajustar CORS se necessário para requests cross-origin
- [ ] Testes automatizados (opcional)

## Como Rodar

### Pré-requisitos
- PHP 8.5+ com extensões: openssl, mbstring, pdo_mysql, fileinfo, curl
- MySQL 8 rodando
- Node.js 18+
- Composer 2+

### Passos

1. **Criar banco MySQL:**
```sql
CREATE DATABASE smartteam CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. **Backend:**
```bash
cd backend
php artisan migrate
php artisan db:seed
php artisan serve
```

3. **Frontend:**
```bash
cd frontend
npm run dev
```

4. **Acessar:** http://localhost:5173

### Credenciais de Teste
- **Admin:** admin@smartteam.com / password
- **Operador:** operador@smartteam.com / password

## Estrutura de Diretórios

```
smartteam/
├── backend/                   # Laravel 12
│   ├── app/
│   │   ├── Enums/            # UserRole, TicketStatus
│   │   ├── Http/
│   │   │   ├── Controllers/  # 7 controllers
│   │   │   ├── Middleware/   # CheckRole
│   │   │   ├── Requests/    # 5 form requests
│   │   │   └── Resources/   # 4 API resources
│   │   ├── Models/           # 8 models
│   │   └── Services/        # TicketService
│   ├── database/
│   │   ├── migrations/       # 11 migrations
│   │   └── seeders/          # DatabaseSeeder
│   └── routes/
│       └── api.php           # 35 routes
├── frontend/                  # Vue.js 3
│   └── src/
│       ├── layouts/          # AppLayout (sidebar+topbar), AuthLayout
│       ├── views/            # 7 views
│       ├── components/
│       │   ├── navigation/   # TheSidebar, TheTopbar
│       │   ├── ui/           # BaseCard, BaseModal, BaseBadge, BaseSkeleton, BaseToggle, BaseToast, ConfirmDialog
│       │   ├── dashboard/    # SummaryCards, EmployeeTicketList
│       │   ├── tickets/      # TicketTable, TicketStatusBadge, TicketEditModal, TicketBatchForm
│       │   ├── shared/       # FilterBar, PasswordReveal
│       │   └── catalog/      # (future)
│       ├── composables/      # useAuth, useTickets, useEmployees, useToast
│       ├── stores/           # auth, tickets (Pinia)
│       ├── services/         # API axios instance
│       └── router/           # Vue Router config
└── CONTEXT.md                # Este arquivo
```
