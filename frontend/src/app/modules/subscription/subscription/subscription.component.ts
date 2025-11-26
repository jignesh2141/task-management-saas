import { Component, OnInit } from '@angular/core';
import { SubscriptionService, Subscription, Plan, SubscriptionFeature } from '../../../core/services/subscription.service';

@Component({
  selector: 'app-subscription',
  templateUrl: './subscription.component.html',
  styleUrls: ['./subscription.component.css']
})
export class SubscriptionComponent implements OnInit {
  currentSubscription: Subscription | null = null;
  plans: Plan[] = [];
  features: SubscriptionFeature[] = [];
  loading: boolean = true;
  upgrading: boolean = false;
  downgrading: boolean = false;

  constructor(private subscriptionService: SubscriptionService) {}

  ngOnInit(): void {
    this.loadSubscription();
    this.loadPlans();
  }

  loadSubscription(): void {
    this.subscriptionService.getCurrentSubscription().subscribe({
      next: (subscription: Subscription) => {
        this.currentSubscription = subscription;
        this.loadFeatures();
      },
      error: (error: any) => {
        console.error('Error loading subscription:', error);
        this.loading = false;
        // Handle 404 - no subscription found
        if (error.status === 404) {
          this.currentSubscription = null;
        }
      }
    });
  }

  loadPlans(): void {
    this.subscriptionService.getPlans().subscribe({
      next: (response: { plans: Plan[] }) => {
        this.plans = response.plans;
      },
      error: (error: any) => {
        console.error('Error loading plans:', error);
      }
    });
  }

  loadFeatures(): void {
    this.subscriptionService.getFeatures().subscribe({
      next: (response: { plan: string; features: SubscriptionFeature[] }) => {
        this.features = response.features;
        this.loading = false;
      },
      error: (error: any) => {
        console.error('Error loading features:', error);
        this.loading = false;
      }
    });
  }

  upgrade(plan: 'pro' | 'enterprise'): void {
    if (confirm(`Are you sure you want to upgrade to ${plan} plan?`)) {
      this.upgrading = true;
      this.subscriptionService.upgrade(plan).subscribe({
        next: () => {
          this.loadSubscription();
          this.upgrading = false;
        },
        error: (error: any) => {
          console.error('Error upgrading:', error);
          alert(error.error?.message || 'Failed to upgrade subscription');
          this.upgrading = false;
        }
      });
    }
  }

  downgrade(plan: 'basic' | 'pro'): void {
    if (confirm(`Are you sure you want to downgrade to ${plan} plan?`)) {
      this.downgrading = true;
      this.subscriptionService.downgrade(plan).subscribe({
        next: () => {
          this.loadSubscription();
          this.downgrading = false;
        },
        error: (error: any) => {
          console.error('Error downgrading:', error);
          alert(error.error?.message || 'Failed to downgrade subscription');
          this.downgrading = false;
        }
      });
    }
  }

  canUpgrade(planKey: string): boolean {
    if (!this.currentSubscription) return false;
    const planHierarchy = { 'basic': 1, 'pro': 2, 'enterprise': 3 };
    const currentLevel = planHierarchy[this.currentSubscription.plan as keyof typeof planHierarchy] || 0;
    const targetLevel = planHierarchy[planKey as keyof typeof planHierarchy] || 0;
    return targetLevel > currentLevel;
  }

  canDowngrade(planKey: string): boolean {
    if (!this.currentSubscription) return false;
    const planHierarchy = { 'basic': 1, 'pro': 2, 'enterprise': 3 };
    const currentLevel = planHierarchy[this.currentSubscription.plan as keyof typeof planHierarchy] || 0;
    const targetLevel = planHierarchy[planKey as keyof typeof planHierarchy] || 0;
    return targetLevel < currentLevel;
  }

  isCurrentPlan(planKey: string): boolean {
    return this.currentSubscription?.plan === planKey;
  }

  upgradePlan(planKey: string): void {
    if (planKey === 'pro' || planKey === 'enterprise') {
      this.upgrade(planKey as 'pro' | 'enterprise');
    }
  }

  downgradePlan(planKey: string): void {
    if (planKey === 'basic' || planKey === 'pro') {
      this.downgrade(planKey as 'basic' | 'pro');
    }
  }
}
