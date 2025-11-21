# Development Plan: Multi-Tenant Task Management SaaS (Demo App)

## Project Overview
A lightweight multi-tenant SaaS application with role-based dashboards and subscription management. Single database with `tenant_id` columns for multi-tenancy using `stancl/tenancy` package.

**Tech Stack:**
- Backend: Laravel 9 (in `backend/` directory)
- Multi-tenancy: `stancl/tenancy` package
- Frontend: Angular 15
- UI Framework: Tailwind CSS + Fuse UI
- Database: MySQL

---

## Phase 1: Backend Foundation (Week 1)

### 1.1 Database Schema Setup

**Core Tables:**
- `tenants` - Tenant/organization information (managed by stancl/tenancy)
- `tenant_users` - User-tenant relationship (stancl/tenancy pivot table)
- `users` - User accounts (add `role` - tenant_id handled by package)
- `subscriptions` - Tenant subscription plans
- `tasks` - Task management (with tenant_id via BelongsToTenant trait)
- `dashboard_widgets` - Widget configuration per role
- `subscription_features` - Feature mapping per plan

**Key Migrations:**
```
- Run: php artisan tenancy:install (creates tenants table)
- Create users table (without tenant_id - package handles this)
- Create subscriptions table (with tenant_id)
- Create tasks table (with tenant_id - will use BelongsToTenant trait)
- Create dashboard_widgets table
- Create subscription_features table
```

**Note:** `stancl/tenancy` handles the tenant-user relationship through a pivot table, so users don't need a direct `tenant_id` column.

### 1.2 Multi-Tenancy Implementation

**Approach: Using `stancl/tenancy` Package**

We'll use the `stancl/tenancy` package for robust multi-tenancy support with single-database approach.

**Installation:**
```bash
cd backend
composer require stancl/tenancy
php artisan tenancy:install
php artisan migrate
```

**Configuration:**
- Configure for single-database approach (using `tenant_id` columns)
- Set up tenant identification (by domain, subdomain, or path)
- Configure tenant model and migrations

**Package Benefits:**
- Automatic tenant scoping
- Built-in tenant identification
- Tenant-aware database queries
- Easy tenant switching
- Well-tested and maintained

**Files to Create/Update:**
- `app/Models/Tenant.php` - Tenant model (extends stancl's Tenant)
- `app/Http/Middleware/InitializeTenancy.php` - Initialize tenant context
- Update `app/Models/User.php` - Add tenant relationship
- Update all tenant-scoped models to use `Stancl\Tenancy\Database\Concerns\BelongsToTenant`

**Implementation:**
- Configure tenant identification (recommend subdomain or path-based for demo)
- Use `BelongsToTenant` trait on models that need tenant scoping
- Package automatically filters queries by tenant
- Middleware initializes tenant context on each request
- Users automatically scoped to their tenant

**Tenant Identification Options:**
- **Subdomain**: `tenant1.app.com`, `tenant2.app.com` (recommended for demo)
- **Path**: `app.com/tenant1`, `app.com/tenant2`
- **Domain**: `tenant1.com`, `tenant2.com`

**Configuration Files:**
- `config/tenancy.php` - Main configuration
- `routes/tenancy.php` - Tenant-specific routes
- Update `app/Providers/RouteServiceProvider.php` to include tenant routes

**Important Notes:**
- Users are linked to tenants via `tenant_users` pivot table
- Use `tenancy()->tenant` to get current tenant
- Use `$user->tenants()` to get user's tenants
- Models with `BelongsToTenant` automatically filter by tenant

### 1.3 Authentication & Authorization

**Setup:**
- Configure Laravel Sanctum for API authentication
- Create role constants (Manager, Team Lead, Agent)
- Add role-based middleware

**Files:**
- `app/Http/Middleware/CheckRole.php` - Role-based access control
- Update `app/Models/User.php` - Add tenant relationship, role methods

---

## Phase 2: Backend APIs (Week 1-2)

### 2.1 Authentication APIs

**Routes (`routes/api.php`):**
```
POST /api/auth/register - Register new tenant + user
POST /api/auth/login - Login
POST /api/auth/logout - Logout
GET  /api/auth/me - Get current user with tenant info
```

**Controllers:**
- `app/Http/Controllers/AuthController.php`

**Implementation Notes:**
- Registration creates tenant first, then user, then links them
- Login identifies tenant from request (subdomain/path)
- Use `tenancy()->initialize($tenant)` to set tenant context
- User's tenant relationship checked during authentication

### 2.2 Dashboard APIs

**Routes:**
```
GET /api/dashboard/widgets - Get widgets based on user role
GET /api/dashboard/stats - Get dashboard statistics
```

**Controllers:**
- `app/Http/Controllers/DashboardController.php`

**Logic:**
- Return different widgets based on user role
- Filter stats by tenant and role permissions

### 2.3 Subscription APIs

**Routes:**
```
GET  /api/subscription/current - Get current subscription
GET  /api/subscription/plans - Get available plans
POST /api/subscription/upgrade - Upgrade plan
POST /api/subscription/downgrade - Downgrade plan
GET  /api/subscription/features - Get available features for current plan
```

**Controllers:**
- `app/Http/Controllers/SubscriptionController.php`

### 2.4 Task Management APIs

**Routes:**
```
GET    /api/tasks - List tasks (tenant-scoped)
POST   /api/tasks - Create task
GET    /api/tasks/{id} - Get task
PUT    /api/tasks/{id} - Update task
DELETE /api/tasks/{id} - Delete task
```

**Controllers:**
- `app/Http/Controllers/TaskController.php`

---

## Phase 3: Frontend Setup (Week 2)

### 3.1 Angular Project Structure

**Directory Structure:**
```
frontend/
├── src/
│   ├── app/
│   │   ├── core/
│   │   │   ├── services/
│   │   │   │   ├── auth.service.ts
│   │   │   │   ├── tenant.service.ts
│   │   │   │   ├── subscription.service.ts
│   │   │   │   └── api.service.ts
│   │   │   ├── guards/
│   │   │   │   ├── auth.guard.ts
│   │   │   │   └── role.guard.ts
│   │   │   └── interceptors/
│   │   │       └── auth.interceptor.ts
│   │   ├── modules/
│   │   │   ├── auth/
│   │   │   ├── dashboard/
│   │   │   ├── tasks/
│   │   │   └── subscription/
│   │   └── shared/
│   └── assets/
```

### 3.2 Install Dependencies

**Packages:**
- Angular 15 (includes RxJS by default)
- RxJS (already included with Angular)
- Tailwind CSS
- Fuse UI components
- Angular Material (if needed)
- HTTP Client for API calls (uses RxJS Observables)

### 3.3 Core Services (RxJS Usage)

**RxJS Patterns We'll Use:**
- **Observables** - All HTTP calls return Observables
- **BehaviorSubject** - For shared state (current user, tenant, subscription)
- **map, catchError, tap** - Common operators for data transformation and error handling
- **shareReplay** - For caching API responses
- **switchMap, mergeMap** - For chaining API calls (if needed)

**Auth Service:**
- Login/logout methods (return Observables)
- Token management
- User state management (using BehaviorSubject for current user)
- `currentUser$` - Observable stream of current user

**API Service:**
- Base HTTP client configuration
- API endpoint constants
- Error handling (using RxJS catchError operator)
- Request/response interceptors

**Tenant Service:**
- Current tenant info (BehaviorSubject for tenant state)
- Tenant switching (if needed)
- `currentTenant$` - Observable stream of current tenant

**Subscription Service:**
- Get current subscription (Observable)
- Check feature availability
- Upgrade/downgrade methods (Observables)
- `currentSubscription$` - Observable stream of subscription

---

## Phase 4: Frontend Features (Week 2-3)

### 4.1 Authentication Module

**Components:**
- Login page
- Register page (with tenant creation)

**Features:**
- Form validation
- Error handling (using RxJS catchError)
- Token storage
- Redirect after login
- Subscribe to auth service Observables for reactive updates

### 4.2 Dashboard Module

**Components:**
- Dashboard container
- Dynamic widget loader
- Role-specific widgets:
  - **Manager**: User management, reports, analytics
  - **Team Lead**: Team tasks, performance metrics
  - **Agent**: My tasks, notifications

**Implementation:**
- Load widgets based on user role from API (Observable)
- Use async pipe in templates for reactive data binding
- Render widgets dynamically
- Use Fuse UI components for cards/widgets
- Subscribe to dashboard data streams

### 4.3 Task Management Module

**Components:**
- Task list
- Task create/edit form
- Task detail view

**Features:**
- CRUD operations (all return Observables)
- Tenant-scoped data
- Role-based permissions (who can create/edit/delete)
- Reactive task list updates using Observables

### 4.4 Subscription Module

**Components:**
- Subscription overview page
- Plan comparison
- Upgrade/downgrade interface

**Features:**
- Display current plan
- Show available features
- Feature gating (hide/show based on plan)
- Plan upgrade/downgrade flow

---

## Phase 5: Role-Based Access Control (Week 3)

### 5.1 Backend Role Enforcement

**Middleware:**
- `CheckRole` middleware for routes
- Role-based query scoping

**Policies (Optional):**
- Task policies (who can edit/delete)
- Dashboard access policies

### 5.2 Frontend Role Guards

**Route Guards:**
- Protect routes by role
- Redirect unauthorized users

**UI Hiding:**
- Hide/show buttons/features based on role
- Disable actions for unauthorized roles

### 5.3 Widget Configuration

**Database Seed:**
- Pre-defined widgets for each role
- Widget permissions mapping

**Example Widgets:**
- Manager: User Management, Reports, Analytics, Activity Logs
- Team Lead: Team Tasks, Performance Metrics, Team Activity
- Agent: My Tasks, Notifications, Personal Stats

---

## Phase 6: Subscription Management (Week 3-4)

### 6.1 Subscription Plans Setup

**Plans:**
- **Basic**: 5 agents, basic tasks, no automation
- **Pro**: 20 agents, advanced tasks, basic automation, reports
- **Enterprise**: Unlimited agents, all features, automation, advanced reports, API access

**Database:**
- Seed subscription plans
- Map features to plans

### 6.2 Feature Gating

**Backend:**
- Middleware to check subscription features
- API endpoints return 403 if feature not available

**Frontend:**
- Service to check feature availability
- Hide/show UI elements based on subscription
- Show upgrade prompts for locked features

### 6.3 Subscription UI

**Components:**
- Current plan display
- Feature list (available/locked)
- Upgrade/downgrade buttons
- Plan comparison table

---

## Phase 7: Integration & Testing (Week 4)

### 7.1 API Integration

- Connect all frontend services to backend APIs
- Handle errors and loading states
- Implement proper error messages

### 7.2 Testing

**Backend:**
- Test multi-tenancy isolation
- Test role-based access
- Test subscription feature gating

**Frontend:**
- Test authentication flow
- Test role-based UI rendering
- Test subscription feature visibility

### 7.3 Demo Data

**Seeders:**
- Create demo tenants
- Create demo users (different roles)
- Create demo tasks
- Assign subscriptions

---

## Database Schema Summary

### Core Tables

**tenants** (managed by stancl/tenancy)
- id, data (JSON), created_at, updated_at
- Additional fields stored in `data` JSON column or custom columns

**tenant_users** (pivot table - stancl/tenancy)
- tenant_id, user_id

**users**
- id, name, email, password, role (enum: manager, team_lead, agent), created_at, updated_at
- Note: tenant_id relationship handled via pivot table

**subscriptions**
- id, tenant_id, plan (enum: basic, pro, enterprise), status, started_at, expires_at, created_at, updated_at

**tasks**
- id, tenant_id, title, description, status, assigned_to (user_id), created_by, created_at, updated_at

**dashboard_widgets**
- id, role, widget_key, widget_name, is_active, order, created_at, updated_at

**subscription_features**
- id, plan, feature_key, feature_name, is_enabled, created_at, updated_at

---

## Key Implementation Notes

### Multi-Tenancy (stancl/tenancy)
- Package automatically handles tenant scoping
- Models use `BelongsToTenant` trait for automatic tenant filtering
- Tenant identification via subdomain/path/domain
- Users linked to tenants through `tenant_users` pivot table
- Middleware initializes tenant context automatically
- All queries automatically scoped to current tenant

### Role System
- Three roles: Manager, Team Lead, Agent
- Roles stored in users table
- Middleware checks role for route access
- Frontend guards protect routes

### Subscription System
- Three plans: Basic, Pro, Enterprise
- Features mapped to plans in database
- Check subscription before allowing feature access
- UI shows/hides features based on plan

### Dashboard Widgets
- Widgets defined in database per role
- API returns only widgets for user's role
- Frontend renders widgets dynamically

---

## Development Checklist

### Backend
- [ ] Install and configure stancl/tenancy package
- [ ] Database migrations (tenants via package, users, subscriptions, tasks, widgets, features)
- [ ] Configure tenant identification method
- [ ] Add BelongsToTenant trait to tenant-scoped models
- [ ] Role-based middleware
- [ ] Authentication APIs
- [ ] Dashboard APIs (role-based widgets)
- [ ] Subscription APIs
- [ ] Task CRUD APIs
- [ ] Database seeders (demo data)

### Frontend
- [ ] Angular project setup with Tailwind + Fuse UI
- [ ] Authentication module (login/register)
- [ ] Core services (auth, API, tenant, subscription)
- [ ] Dashboard module with dynamic widgets
- [ ] Task management module
- [ ] Subscription management module
- [ ] Role guards and route protection
- [ ] Feature gating UI

### Integration
- [ ] Connect all APIs
- [ ] Error handling
- [ ] Loading states
- [ ] Demo data seeding
- [ ] Basic testing

---

## Quick Start Commands

### Backend
```bash
cd backend
composer install
composer require stancl/tenancy
php artisan tenancy:install
php artisan migrate
php artisan db:seed
php artisan serve
```

### Frontend
```bash
cd frontend
npm install
ng serve
```

---

## Estimated Timeline

- **Week 1**: Backend foundation + APIs
- **Week 2**: Frontend setup + core modules
- **Week 3**: Role-based access + subscription management
- **Week 4**: Integration, testing, demo data

**Total: ~4 weeks for a working demo**

---

## Notes for Demo

- Keep it simple - focus on demonstrating core concepts
- Use seeders for quick demo data
- Don't over-engineer - this is a demo app
- Focus on showing: multi-tenancy, role-based access, subscription gating
- Use Fuse UI components for quick, professional UI
- Use RxJS Observables for HTTP calls and state management (standard Angular pattern)
- Keep RxJS usage simple - basic operators only (map, catchError, BehaviorSubject)

---

## stancl/tenancy Quick Reference

### Common Usage Patterns

**Get Current Tenant:**
```php
$tenant = tenancy()->tenant;
```

**Create Tenant:**
```php
$tenant = Tenant::create(['id' => 'tenant1']);
$tenant->domains()->create(['domain' => 'tenant1.app.com']);
```

**Link User to Tenant:**
```php
$tenant->users()->attach($user);
```

**Initialize Tenant Context:**
```php
tenancy()->initialize($tenant);
```

**Check if Tenant is Initialized:**
```php
if (tenancy()->initialized) {
    // Tenant context is active
}
```

**In Models (Add Tenant Scoping):**
```php
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class Task extends Model
{
    use BelongsToTenant;
    // All queries automatically scoped to current tenant
}
```

**Middleware for Tenant Routes:**
- Package provides `InitializeTenancy` middleware
- Add to route groups that need tenant context
- Automatically identifies tenant from request

**Tenant Identification:**
- Configure in `config/tenancy.php`
- Set identification method (subdomain, path, domain)
- Package handles identification automatically

---

## RxJS Quick Reference for Angular

### Common Patterns We'll Use

**HTTP Service Method (Returns Observable):**
```typescript
getTasks(): Observable<Task[]> {
  return this.http.get<Task[]>('/api/tasks')
    .pipe(
      catchError(this.handleError)
    );
}
```

**BehaviorSubject for State Management:**
```typescript
private currentUser$ = new BehaviorSubject<User | null>(null);
public currentUser = this.currentUser$.asObservable();

setUser(user: User) {
  this.currentUser$.next(user);
}
```

**Using Async Pipe in Templates:**
```html
<div *ngIf="tasks$ | async as tasks">
  <div *ngFor="let task of tasks">{{ task.title }}</div>
</div>
```

**Error Handling:**
```typescript
this.taskService.getTasks().subscribe({
  next: (tasks) => console.log(tasks),
  error: (error) => console.error(error)
});
```

**Common Operators:**
- `map` - Transform data
- `catchError` - Handle errors
- `tap` - Side effects (logging, etc.)
- `shareReplay(1)` - Cache and share Observable
- `switchMap` - Switch to new Observable (for chaining)

