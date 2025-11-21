import { Injectable } from '@angular/core';
import { BehaviorSubject, Observable } from 'rxjs';
import { ApiService } from './api.service';
import { AuthService, Tenant } from './auth.service';

@Injectable({
  providedIn: 'root'
})
export class TenantService {
  private currentTenantSubject = new BehaviorSubject<Tenant | null>(null);
  public currentTenant$ = this.currentTenantSubject.asObservable();

  constructor(
    private apiService: ApiService,
    private authService: AuthService
  ) {
    this.authService.currentTenant$.subscribe(tenant => {
      this.currentTenantSubject.next(tenant);
    });
  }

  getCurrentTenant(): Tenant | null {
    return this.currentTenantSubject.value || this.authService.getCurrentTenantValue();
  }

  getCurrentTenantObservable(): Observable<Tenant | null> {
    return this.currentTenant$;
  }
}

