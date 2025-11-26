import { Injectable } from '@angular/core';
import { BehaviorSubject, Observable } from 'rxjs';
import { map, tap } from 'rxjs/operators';
import { ApiService } from './api.service';

export interface Subscription {
  id: number;
  tenant_id: string;
  plan: 'basic' | 'pro' | 'enterprise';
  status: 'active' | 'cancelled' | 'expired';
  started_at: string;
  expires_at: string | null;
}

export interface SubscriptionFeature {
  id: number;
  plan: string;
  feature_key: string;
  feature_name: string;
  description: string | null;
  is_enabled: boolean;
  limit_value: number | null;
}

export interface Plan {
  key: string;
  name: string;
  price: number;
  features: SubscriptionFeature[];
}

@Injectable({
  providedIn: 'root'
})
export class SubscriptionService {
  private currentSubscriptionSubject = new BehaviorSubject<Subscription | null>(null);
  public currentSubscription$ = this.currentSubscriptionSubject.asObservable();

  constructor(private apiService: ApiService) { }

  getCurrentSubscription(): Observable<Subscription> {
    return this.apiService.get<{ subscription: Subscription }>('/subscription/current').pipe(
      map(response => response.subscription),
      tap(subscription => {
        this.currentSubscriptionSubject.next(subscription);
      })
    );
  }

  getPlans(): Observable<{ plans: Plan[] }> {
    return this.apiService.get<{ plans: Plan[] }>('/subscription/plans');
  }

  getFeatures(): Observable<{ plan: string; features: SubscriptionFeature[] }> {
    return this.apiService.get<{ plan: string; features: SubscriptionFeature[] }>('/subscription/features');
  }

  upgrade(plan: 'pro' | 'enterprise'): Observable<any> {
    return this.apiService.post('/subscription/upgrade', { plan }).pipe(
      tap(() => {
        this.getCurrentSubscription().subscribe();
      })
    );
  }

  downgrade(plan: 'basic' | 'pro'): Observable<any> {
    return this.apiService.post('/subscription/downgrade', { plan }).pipe(
      tap(() => {
        this.getCurrentSubscription().subscribe();
      })
    );
  }

  hasFeature(featureKey: string): Observable<boolean> {
    return new Observable(observer => {
      this.getFeatures().subscribe(response => {
        const hasFeature = response.features.some(
          feature => feature.feature_key === featureKey && feature.is_enabled
        );
        observer.next(hasFeature);
        observer.complete();
      });
    });
  }

  getCurrentSubscriptionValue(): Subscription | null {
    return this.currentSubscriptionSubject.value;
  }
}

