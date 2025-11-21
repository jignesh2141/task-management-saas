import { Injectable } from '@angular/core';
import {
  HttpRequest,
  HttpHandler,
  HttpEvent,
  HttpInterceptor
} from '@angular/common/http';
import { Observable } from 'rxjs';

@Injectable()
export class AuthInterceptor implements HttpInterceptor {

  intercept(request: HttpRequest<unknown>, next: HttpHandler): Observable<HttpEvent<unknown>> {
    const token = localStorage.getItem('auth_token');
    const tenantId = localStorage.getItem('tenant_id');

    let clonedRequest = request;

    if (token) {
      clonedRequest = request.clone({
        setHeaders: {
          Authorization: `Bearer ${token}`
        }
      });
    }

    if (tenantId && !request.headers.has('X-Tenant-ID')) {
      clonedRequest = clonedRequest.clone({
        setHeaders: {
          'X-Tenant-ID': tenantId
        }
      });
    }

    return next.handle(clonedRequest);
  }
}

