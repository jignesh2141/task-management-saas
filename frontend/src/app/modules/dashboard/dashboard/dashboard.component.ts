import { Component, OnInit } from '@angular/core';
import { DashboardService, DashboardWidget, DashboardStats } from '../dashboard.service';
import { AuthService } from '../../../core/services/auth.service';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css']
})
export class DashboardComponent implements OnInit {
  widgets: DashboardWidget[] = [];
  stats: DashboardStats['stats'] = {};
  loading: boolean = true;
  currentUser: any = null;

  constructor(
    private dashboardService: DashboardService,
    private authService: AuthService
  ) {}

  ngOnInit(): void {
    this.currentUser = this.authService.getCurrentUserValue();
    this.loadDashboard();
  }

  loadDashboard(): void {
    this.loading = true;
    
    this.dashboardService.getWidgets().subscribe({
      next: (response) => {
        this.widgets = response.widgets;
        this.loadStats();
      },
      error: (error) => {
        console.error('Error loading widgets:', error);
        this.loading = false;
      }
    });
  }

  loadStats(): void {
    this.dashboardService.getStats().subscribe({
      next: (response) => {
        this.stats = response.stats;
        this.loading = false;
      },
      error: (error) => {
        console.error('Error loading stats:', error);
        this.loading = false;
      }
    });
  }

  getWidgetComponent(widgetKey: string): string {
    // Map widget keys to component names or templates
    const widgetMap: { [key: string]: string } = {
      'user_management': 'User Management Widget',
      'reports': 'Reports Widget',
      'analytics': 'Analytics Widget',
      'activity_logs': 'Activity Logs Widget',
      'subscription_overview': 'Subscription Overview Widget',
      'team_tasks': 'Team Tasks Widget',
      'performance_metrics': 'Performance Metrics Widget',
      'team_activity': 'Team Activity Widget',
      'my_tasks': 'My Tasks Widget',
      'notifications': 'Notifications Widget',
      'personal_stats': 'Personal Stats Widget'
    };
    return widgetMap[widgetKey] || widgetKey;
  }
}
