import { Injectable } from '@angular/core';
import { Router, CanActivate, ActivatedRouteSnapshot, RouterStateSnapshot } from '@angular/router';
import { AuthService } from '../services/auth.service';

@Injectable({
  providedIn: 'root'
})
export class RoleGuard implements CanActivate {
  constructor(
    private authService: AuthService,
    private router: Router
  ) {}

  canActivate(
    route: ActivatedRouteSnapshot,
    state: RouterStateSnapshot
  ): boolean {
    const requiredRoles = route.data['roles'] as Array<string>;
    
    if (!requiredRoles || requiredRoles.length === 0) {
      return true;
    }

    const user = this.authService.getCurrentUserValue();
    
    if (!user) {
      this.router.navigate(['/auth/login']);
      return false;
    }

    const hasRole = requiredRoles.includes(user.role);
    
    if (!hasRole) {
      this.router.navigate(['/dashboard']);
      return false;
    }

    return true;
  }
}

