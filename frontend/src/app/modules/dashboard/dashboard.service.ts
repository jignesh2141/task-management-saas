import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { ApiService } from '../../core/services/api.service';

export interface DashboardWidget {
  id: number;
  role: string;
  widget_key: string;
  widget_name: string;
  description: string | null;
  is_active: boolean;
  order: number;
}

export interface DashboardStats {
  stats: {
    total_tasks?: number;
    pending_tasks?: number;
    completed_tasks?: number;
    in_progress_tasks?: number;
    cancelled_tasks?: number;
    team_tasks?: number;
    my_tasks?: number;
    my_pending_tasks?: number;
  };
}

@Injectable({
  providedIn: 'root'
})
export class DashboardService {
  constructor(private apiService: ApiService) { }

  getWidgets(): Observable<{ widgets: DashboardWidget[] }> {
    return this.apiService.get<{ widgets: DashboardWidget[] }>('/dashboard/widgets');
  }

  getStats(): Observable<DashboardStats> {
    return this.apiService.get<DashboardStats>('/dashboard/stats');
  }
}

