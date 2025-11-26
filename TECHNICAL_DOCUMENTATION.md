# Technical Documentation

## Task Management SaaS - High-Level Architecture

This document provides a high-level overview of how each module is built in both the backend (Laravel) and frontend (Angular) of the Task Management SaaS application.

---

## Table of Contents

1. [Overview](#overview)
2. [Authentication Module](#authentication-module)
3. [Dashboard Module](#dashboard-module)
4. [Task Management Module](#task-management-module)
5. [Subscription Module](#subscription-module)
6. [Multi-Tenancy Architecture](#multi-tenancy-architecture)
7. [Role-Based Access Control](#role-based-access-control)

---

## Overview

### Tech Stack

**Backend:**
- Laravel 9 (PHP)
- Laravel Sanctum (API Authentication)
- Stancl Tenancy (Multi-tenancy package)
- MySQL Database

**Frontend:**
- Angular 15
- RxJS (Reactive Programming)
- Tailwind CSS + Fuse UI
- TypeScript

### Architecture Pattern

The application follows a **multi-tenant SaaS architecture** with:
- Single database with `tenant_id` columns for data isolation
- Role-based access control (Manager, Team Lead, Agent)
- Subscription-based feature gating
- RESTful API communication between frontend and backend

### Database Structure

- **tenants** - Organization/tenant information
- **users** - User accounts with roles
- **tenant_users** - Many-to-many relationship between users and tenants
- **tasks** - Task records with tenant_id
- **subscriptions** - Tenant subscription plans
- **dashboard_widgets** - Widget configuration per role
- **subscription_features** - Feature mapping per plan

---

## Authentication Module

### Backend Implementation

**Location:** `backend/app/Http/Controllers/AuthController.php`

#### Key Endpoints

1. **POST /api/auth/register**
   - Creates a new tenant and user
   - Validates tenant slug uniqueness
   - Links user to tenant via pivot table
   - Initializes tenancy context
   - Returns authentication token

2. **POST /api/auth/login**
   - Accepts `tenant_id` (slug or UUID), email, and password
   - Finds tenant by ID or slug
   - Validates user belongs to tenant
   - Initializes tenancy context
   - Returns authentication token

3. **POST /api/auth/logout**
   - Revokes current access token
   - Requires authentication

4. **GET /api/auth/me**
   - Returns current authenticated user with tenant info
   - Requires authentication and tenant context

#### Key Features

- **Tenant Identification:** Login accepts tenant slug or UUID in request body
- **Token Generation:** Uses Laravel Sanctum to generate API tokens
- **User-Tenant Relationship:** Many-to-many relationship via `tenant_users` pivot table
- **Tenancy Initialization:** Uses `tenancy()->initialize($tenant)` to set tenant context

### Frontend Implementation

**Location:** `frontend/src/app/core/services/auth.service.ts`

#### Key Components

1. **AuthService**
   - Manages authentication state using RxJS BehaviorSubject
   - Stores authentication token and tenant slug in localStorage
   - Provides observables for reactive updates
   - Handles login, register, logout operations

2. **Login Component**
   - Location: `frontend/src/app/modules/auth/login/`
   - Form with tenant_id, email, and password fields
   - Validates input and calls AuthService
   - Redirects to dashboard on success

3. **Register Component**
   - Location: `frontend/src/app/modules/auth/register/`
   - Form for tenant creation and user registration
   - Creates tenant and user in single request

#### Data Flow

1. User submits login form with tenant slug, email, password
2. AuthService sends POST request to `/api/auth/login`
3. Backend validates credentials and initializes tenancy
4. Backend returns token, user, and tenant data
5. Frontend stores token and tenant slug in localStorage
6. AuthService updates BehaviorSubject with user data
7. Components subscribe to auth state for reactive updates

#### Key Technologies

- **RxJS Observables:** For reactive state management
- **localStorage:** For persistent token and tenant storage
- **Angular Forms:** For form validation and submission

---

## Dashboard Module

### Backend Implementation

**Location:** `backend/app/Http/Controllers/DashboardController.php`

#### Key Endpoints

1. **GET /api/dashboard/widgets**
   - Returns widgets based on authenticated user's role
   - Filters by role using `DashboardWidget::forRole($user->role)`
   - Only returns active widgets, ordered by display order
   - Tenant context automatically applied

2. **GET /api/dashboard/stats**
   - Returns dashboard statistics based on user role
   - Common stats: total_tasks, pending_tasks, completed_tasks
   - Manager-specific: in_progress_tasks, cancelled_tasks
   - Team Lead-specific: team_tasks
   - Agent-specific: my_tasks, my_pending_tasks

#### Models

**DashboardWidget Model** (`backend/app/Models/DashboardWidget.php`)
- Stores widget configuration per role
- Scopes: `forRole()`, `active()`, `ordered()`
- Fields: role, widget_key, widget_name, description, is_active, order

#### Widget Configuration

Widgets are seeded in database with role-specific configurations:
- **Manager:** User Management, Reports, Analytics, Activity Logs, Subscription Overview
- **Team Lead:** Team Tasks, Performance Metrics, Team Activity
- **Agent:** My Tasks, Notifications, Personal Stats

### Frontend Implementation

**Location:** `frontend/src/app/modules/dashboard/`

#### Key Components

1. **DashboardComponent**
   - Location: `dashboard/dashboard.component.ts`
   - Loads widgets and stats on initialization
   - Dynamically renders widgets based on API response
   - Displays role-specific statistics

2. **DashboardService**
   - Location: `dashboard.service.ts`
   - Wraps API calls for widgets and stats
   - Returns RxJS Observables for reactive data binding

#### Data Flow

1. Component calls `dashboardService.getWidgets()`
2. Service makes GET request to `/api/dashboard/widgets`
3. Backend filters widgets by user role and returns JSON
4. Frontend receives widgets array
5. Component iterates and renders widgets dynamically
6. Stats loaded similarly and displayed in dashboard

#### Key Technologies

- **Dynamic Component Rendering:** Widgets rendered based on API response
- **RxJS Observables:** For reactive data loading
- **Async Pipe:** For automatic subscription management in templates

---

## Task Management Module

### Backend Implementation

**Location:** `backend/app/Http/Controllers/TaskController.php`

#### Key Endpoints

1. **GET /api/tasks**
   - Lists tasks with role-based filtering
   - **Manager:** Sees all tenant tasks
   - **Team Lead:** Sees tasks assigned to them or created by them
   - **Agent:** Sees only tasks assigned to them
   - Supports filtering by status and search query
   - Returns paginated results with user relationships

2. **POST /api/tasks**
   - Creates new task
   - Automatically sets tenant_id from tenancy context
   - Sets created_by to authenticated user
   - Validates title, description, status, assigned_to

3. **GET /api/tasks/{id}**
   - Returns single task with relationships
   - Role-based access check (agents can only view assigned tasks)

4. **PUT /api/tasks/{id}**
   - Updates task
   - Role-based permission check
   - Agents can only update their assigned tasks

5. **DELETE /api/tasks/{id}**
   - Deletes task
   - Only managers and task creators can delete

#### Models

**Task Model** (`backend/app/Models/Task.php`)
- Uses `BelongsToTenant` trait for automatic tenant scoping
- Relationships: `assignedUser()`, `creator()`
- Helper methods: `isCompleted()`, `isPending()`
- Fields: tenant_id, title, description, status, assigned_to, created_by

#### Tenant Scoping

All task queries are automatically scoped to current tenant via `BelongsToTenant` trait. No need to manually add `where('tenant_id', ...)` clauses.

### Frontend Implementation

**Location:** `frontend/src/app/modules/tasks/`

#### Key Components

1. **TaskListComponent**
   - Location: `task-list/task-list.component.ts`
   - Displays list of tasks
   - Supports filtering and search
   - Role-based UI (different actions per role)
   - Loads tasks on initialization

2. **TaskFormComponent**
   - Location: `task-form/task-form.component.ts`
   - Reactive form for creating/editing tasks
   - Validates input
   - Handles form submission

3. **TasksService**
   - Location: `tasks.service.ts`
   - Wraps all task API calls
   - Provides CRUD operations as Observables

#### Data Flow

1. Component calls `tasksService.getAll()`
2. Service makes GET request to `/api/tasks` with X-Tenant-ID header
3. Backend middleware identifies tenant and initializes tenancy
4. Backend filters tasks by role and tenant
5. Returns paginated task list with user relationships
6. Frontend displays tasks in list view
7. User actions (create/edit/delete) trigger corresponding API calls

#### Key Technologies

- **Reactive Forms:** For task creation/editing
- **RxJS Observables:** For API calls and state management
- **Role-Based UI:** Conditional rendering based on user role

---

## Subscription Module

### Backend Implementation

**Location:** `backend/app/Http/Controllers/SubscriptionController.php`

#### Key Endpoints

1. **GET /api/subscription/current**
   - Returns active subscription for current tenant
   - Tenant identified from tenancy context

2. **GET /api/subscription/plans**
   - Returns all available subscription plans
   - Includes plan features from SubscriptionFeature model
   - Plans: Basic (free), Pro ($29), Enterprise ($99)

3. **GET /api/subscription/features**
   - Returns enabled features for current tenant's plan
   - Filters features by subscription plan

4. **POST /api/subscription/upgrade**
   - Upgrades subscription to higher tier
   - Validates upgrade hierarchy (basic → pro → enterprise)
   - Updates subscription plan and started_at date

5. **POST /api/subscription/downgrade**
   - Downgrades subscription to lower tier
   - Validates downgrade hierarchy
   - Updates subscription plan

#### Models

**Subscription Model** (`backend/app/Models/Subscription.php`)
- Stores tenant subscription information
- Fields: tenant_id, plan, status, started_at, expires_at
- Relationship: belongs to Tenant

**SubscriptionFeature Model** (`backend/app/Models/SubscriptionFeature.php`)
- Maps features to subscription plans
- Scopes: `forPlan()`, `enabled()`
- Fields: plan, feature_key, feature_name, is_enabled

#### Plan Hierarchy

Plans follow a hierarchy: Basic (1) → Pro (2) → Enterprise (3)
- Upgrades must move to higher tier
- Downgrades must move to lower tier

### Frontend Implementation

**Location:** `frontend/src/app/modules/subscription/`

#### Key Components

1. **SubscriptionComponent**
   - Location: `subscription/subscription.component.ts`
   - Displays current subscription plan
   - Shows available features
   - Provides upgrade/downgrade interface
   - Plan comparison view

2. **SubscriptionService**
   - Location: `frontend/src/app/core/services/subscription.service.ts`
   - Wraps subscription API calls
   - Provides methods for plan management

#### Data Flow

1. Component loads current subscription on initialization
2. Service calls `/api/subscription/current`
3. Backend returns tenant's active subscription
4. Component displays plan details and features
5. User can view all plans via `/api/subscription/plans`
6. Upgrade/downgrade actions trigger POST requests
7. Backend validates and updates subscription

#### Key Technologies

- **Feature Gating:** UI elements shown/hidden based on subscription plan
- **Plan Comparison:** Side-by-side comparison of plan features
- **Reactive Updates:** Subscription changes reflected immediately

---

## Multi-Tenancy Architecture

### Overview

The application uses **single-database multi-tenancy** with `tenant_id` columns for data isolation. The `stancl/tenancy` package handles tenant context management.

### Tenant Identification

**Custom Middleware:** `backend/app/Http/Middleware/InitializeTenancyByRequestDataCustom.php`

#### How It Works

1. Middleware intercepts API requests
2. Extracts tenant identifier from:
   - `X-Tenant-ID` header (primary)
   - `X-Tenant` header (fallback)
   - `tenant` query parameter (fallback)
3. Finds tenant by UUID or slug
4. Initializes tenancy context using `tenancy()->initialize($tenant)`
5. All subsequent queries are automatically scoped to tenant

#### Route Protection

**Location:** `backend/routes/api.php`

Protected routes are wrapped in middleware:
```php
Route::middleware([InitializeTenancyByRequestDataCustom::class])->group(function () {
    // Protected routes here
});
```

#### Frontend Tenant Management

**Location:** `frontend/src/app/core/services/auth.service.ts`

- Tenant slug stored in localStorage as `tenant_id`
- ApiService automatically adds `X-Tenant-ID` header to all requests
- Tenant context maintained across page refreshes

### Tenant Scoping

**Models with Tenant Scoping:**
- Task model uses `BelongsToTenant` trait
- All queries automatically filtered by tenant_id
- No manual tenant filtering required in controllers

### Tenant-User Relationship

- Many-to-many relationship via `tenant_users` pivot table
- Users can belong to multiple tenants
- Login validates user belongs to specified tenant

---

## Role-Based Access Control

### Roles

The application has three roles:

1. **Manager**
   - Full access to all features
   - Can view all tasks
   - Can manage users
   - Can delete any task

2. **Team Lead**
   - Can view tasks assigned to them or created by them
   - Can create and assign tasks
   - Limited to team-related data

3. **Agent**
   - Can only view tasks assigned to them
   - Can update their assigned tasks
   - Cannot delete tasks
   - Cannot view other users' tasks

### Backend Implementation

#### User Model Methods

**Location:** `backend/app/Models/User.php`

Helper methods for role checking:
- `hasRole($role)` - Generic role check
- `isManager()` - Manager check
- `isTeamLead()` - Team Lead check
- `isAgent()` - Agent check

#### Controller-Level Checks

Controllers check user roles before processing requests:

**TaskController Example:**
```php
if ($user->isAgent() && $task->assigned_to !== $user->id) {
    return response()->json(['message' => 'Permission denied'], 403);
}
```

#### Data Filtering

Role-based query filtering:
- **Agents:** `Task::where('assigned_to', $user->id)`
- **Team Leads:** `Task::where('assigned_to', $user->id)->orWhere('created_by', $user->id)`
- **Managers:** No additional filtering (see all tasks)

### Frontend Implementation

#### Role Guards

**Location:** `frontend/src/app/core/guards/role.guard.ts`

Route guards protect routes based on user role:
- Prevents unauthorized access to role-specific routes
- Redirects to appropriate page if access denied

#### UI Conditional Rendering

Components conditionally render UI elements:

**Example:**
```typescript
if (authService.isManager()) {
    // Show manager-only features
}
```

#### AuthService Methods

**Location:** `frontend/src/app/core/services/auth.service.ts`

Provides role checking methods:
- `hasRole(role)` - Generic role check
- `isManager()` - Manager check
- `isTeamLead()` - Team Lead check
- `isAgent()` - Agent check

### Widget Configuration

Dashboard widgets are role-specific:
- Widgets stored in `dashboard_widgets` table with role field
- API returns only widgets matching user's role
- Frontend renders widgets dynamically based on API response

---

## Key Patterns and Technologies

### Backend Patterns

1. **Repository Pattern:** Controllers interact with models directly
2. **Middleware Pattern:** Tenant identification and authentication
3. **Scope Pattern:** Model scopes for query filtering
4. **Trait Pattern:** BelongsToTenant trait for automatic tenant scoping

### Frontend Patterns

1. **Service Pattern:** Services encapsulate API calls
2. **Observable Pattern:** RxJS Observables for reactive programming
3. **Component Pattern:** Angular components for UI
4. **Guard Pattern:** Route guards for access control

### Data Flow Pattern

1. User action in frontend component
2. Component calls service method
3. Service makes HTTP request with headers (token, tenant-id)
4. Backend middleware identifies tenant and authenticates user
5. Controller processes request with role-based logic
6. Model queries data (automatically tenant-scoped)
7. Response returned as JSON
8. Frontend service returns Observable
9. Component subscribes and updates UI

---

## File Structure Reference

### Backend

```
backend/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── AuthController.php
│   │   │   ├── DashboardController.php
│   │   │   ├── TaskController.php
│   │   │   └── SubscriptionController.php
│   │   └── Middleware/
│   │       └── InitializeTenancyByRequestDataCustom.php
│   └── Models/
│       ├── User.php
│       ├── Tenant.php
│       ├── Task.php
│       ├── Subscription.php
│       ├── DashboardWidget.php
│       └── SubscriptionFeature.php
├── routes/
│   └── api.php
└── database/
    ├── migrations/
    └── seeders/
```

### Frontend

```
frontend/src/app/
├── core/
│   ├── services/
│   │   ├── auth.service.ts
│   │   ├── api.service.ts
│   │   └── subscription.service.ts
│   └── guards/
│       └── role.guard.ts
└── modules/
    ├── auth/
    │   ├── login/
    │   └── register/
    ├── dashboard/
    │   ├── dashboard/
    │   └── dashboard.service.ts
    ├── tasks/
    │   ├── task-list/
    │   ├── task-form/
    │   └── tasks.service.ts
    └── subscription/
        ├── subscription/
        └── subscription.service.ts
```

---

## Summary

This Task Management SaaS application implements:

- **Multi-tenant architecture** with single database and tenant_id columns
- **Role-based access control** with three distinct roles
- **Subscription management** with plan-based feature gating
- **RESTful API** communication between Angular frontend and Laravel backend
- **Reactive programming** using RxJS Observables
- **Automatic tenant scoping** via BelongsToTenant trait
- **Custom tenant identification** via X-Tenant-ID header

Each module follows a consistent pattern: backend controllers handle business logic and data access, while frontend services and components handle UI and user interactions, with clear separation of concerns and role-based access control throughout.

