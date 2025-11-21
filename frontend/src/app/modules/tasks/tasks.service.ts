import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { ApiService } from '../../core/services/api.service';

export interface Task {
  id: number;
  tenant_id: string;
  title: string;
  description: string | null;
  status: 'pending' | 'in_progress' | 'completed' | 'cancelled';
  assigned_to: number | null;
  created_by: number;
  assigned_user?: {
    id: number;
    name: string;
    email: string;
  };
  creator?: {
    id: number;
    name: string;
    email: string;
  };
  created_at: string;
  updated_at: string;
}

export interface TaskListResponse {
  data: Task[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

@Injectable({
  providedIn: 'root'
})
export class TasksService {
  constructor(private apiService: ApiService) { }

  getTasks(params?: any): Observable<TaskListResponse> {
    const queryParams = new URLSearchParams(params).toString();
    const endpoint = queryParams ? `/tasks?${queryParams}` : '/tasks';
    return this.apiService.get<TaskListResponse>(endpoint);
  }

  getTask(id: number): Observable<{ task: Task }> {
    return this.apiService.get<{ task: Task }>(`/tasks/${id}`);
  }

  createTask(task: Partial<Task>): Observable<{ message: string; task: Task }> {
    return this.apiService.post<{ message: string; task: Task }>('/tasks', task);
  }

  updateTask(id: number, task: Partial<Task>): Observable<{ message: string; task: Task }> {
    return this.apiService.put<{ message: string; task: Task }>(`/tasks/${id}`, task);
  }

  deleteTask(id: number): Observable<{ message: string }> {
    return this.apiService.delete<{ message: string }>(`/tasks/${id}`);
  }
}

