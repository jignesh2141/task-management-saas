import { Injectable } from '@angular/core';
import { BehaviorSubject, Observable } from 'rxjs';
import { tap } from 'rxjs/operators';
import { ApiService } from './api.service';

export interface User {
  id: number;
  name: string;
  email: string;
  role: 'manager' | 'team_lead' | 'agent';
  tenants?: any[];
}

export interface Tenant {
  id: string;
  name: string;
  slug: string;
}

export interface AuthResponse {
  message: string;
  user: User;
  tenant: Tenant;
  token: string;
}

@Injectable({
  providedIn: 'root'
})
export class AuthService {
  private currentUserSubject = new BehaviorSubject<User | null>(null);
  public currentUser$ = this.currentUserSubject.asObservable();

  private currentTenantSubject = new BehaviorSubject<Tenant | null>(null);
  public currentTenant$ = this.currentTenantSubject.asObservable();

  constructor(private apiService: ApiService) {
    this.loadUserFromStorage();
  }

  register(data: {
    tenant_name: string;
    tenant_slug: string;
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
    role?: string;
  }): Observable<AuthResponse> {
    return this.apiService.post<AuthResponse>('/auth/register', data).pipe(
      tap(response => {
        this.setAuthData(response);
      })
    );
  }

  login(email: string, password: string, tenantId: string): Observable<AuthResponse> {
    return this.apiService.post<AuthResponse>('/auth/login', {
      email,
      password,
      tenant_id: tenantId
    }).pipe(
      tap(response => {
        this.setAuthData(response);
      })
    );
  }

  logout(): void {
    this.apiService.post('/auth/logout', {}).subscribe();
    this.clearAuthData();
  }

  getCurrentUser(): Observable<any> {
    return this.apiService.get('/auth/me').pipe(
      tap(response => {
        if (response.user) {
          this.currentUserSubject.next(response.user);
        }
        if (response.tenant) {
          this.currentTenantSubject.next(response.tenant);
          // Store slug instead of UUID for tenant identification
          localStorage.setItem('tenant_id', response.tenant.slug);
        }
      })
    );
  }

  isAuthenticated(): boolean {
    return !!localStorage.getItem('auth_token');
  }

  getCurrentUserValue(): User | null {
    return this.currentUserSubject.value;
  }

  getCurrentTenantValue(): Tenant | null {
    return this.currentTenantSubject.value;
  }

  hasRole(role: string): boolean {
    const user = this.getCurrentUserValue();
    return user?.role === role;
  }

  isManager(): boolean {
    return this.hasRole('manager');
  }

  isTeamLead(): boolean {
    return this.hasRole('team_lead');
  }

  isAgent(): boolean {
    return this.hasRole('agent');
  }

  private setAuthData(response: AuthResponse): void {
    localStorage.setItem('auth_token', response.token);
    // Store slug instead of UUID for tenant identification
    localStorage.setItem('tenant_id', response.tenant.slug);
    this.currentUserSubject.next(response.user);
    this.currentTenantSubject.next(response.tenant);
  }

  private clearAuthData(): void {
    localStorage.removeItem('auth_token');
    localStorage.removeItem('tenant_id');
    this.currentUserSubject.next(null);
    this.currentTenantSubject.next(null);
  }

  private loadUserFromStorage(): void {
    const token = localStorage.getItem('auth_token');
    const tenantId = localStorage.getItem('tenant_id');
    
    if (token && tenantId) {
      this.getCurrentUser().subscribe();
    }
  }
}

