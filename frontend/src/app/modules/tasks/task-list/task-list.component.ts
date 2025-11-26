import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { TasksService, Task } from '../tasks.service';
import { AuthService } from '../../../core/services/auth.service';

@Component({
  selector: 'app-task-list',
  templateUrl: './task-list.component.html',
  styleUrls: ['./task-list.component.css']
})
export class TaskListComponent implements OnInit {
  tasks: Task[] = [];
  loading: boolean = true;
  currentPage: number = 1;
  totalPages: number = 1;
  statusFilter: string = '';
  searchQuery: string = '';
  currentUser: any = null;

  constructor(
    private tasksService: TasksService,
    private router: Router,
    private authService: AuthService
  ) {}

  ngOnInit(): void {
    this.currentUser = this.authService.getCurrentUserValue();
    this.loadTasks();
  }

  loadTasks(): void {
    this.loading = true;
    const params: any = { page: this.currentPage };
    
    if (this.statusFilter) {
      params.status = this.statusFilter;
    }
    
    if (this.searchQuery) {
      params.search = this.searchQuery;
    }

    this.tasksService.getTasks(params).subscribe({
      next: (response) => {
        this.tasks = response.data;
        this.currentPage = response.current_page;
        this.totalPages = response.last_page;
        this.loading = false;
      },
      error: (error) => {
        console.error('Error loading tasks:', error);
        this.loading = false;
      }
    });
  }

  onStatusFilterChange(): void {
    this.currentPage = 1;
    this.loadTasks();
  }

  onSearch(): void {
    this.currentPage = 1;
    this.loadTasks();
  }

  createTask(): void {
    this.router.navigate(['/tasks/create']);
  }

  editTask(task: Task): void {
    this.router.navigate(['/tasks/edit', task.id]);
  }

  deleteTask(task: Task): void {
    if (confirm(`Are you sure you want to delete "${task.title}"?`)) {
      this.tasksService.deleteTask(task.id).subscribe({
        next: () => {
          this.loadTasks();
        },
        error: (error) => {
          console.error('Error deleting task:', error);
          alert('Failed to delete task');
        }
      });
    }
  }

  canEdit(task: Task): boolean {
    if (this.authService.isManager()) return true;
    return task.created_by === this.currentUser?.id;
  }

  canDelete(task: Task): boolean {
    if (this.authService.isManager()) return true;
    return task.created_by === this.currentUser?.id;
  }

  getStatusColor(status: string): string {
    const colors: { [key: string]: string } = {
      'pending': 'bg-yellow-100 text-yellow-800',
      'in_progress': 'bg-blue-100 text-blue-800',
      'completed': 'bg-green-100 text-green-800',
      'cancelled': 'bg-red-100 text-red-800'
    };
    return colors[status] || 'bg-gray-100 text-gray-800';
  }

  goToFirstPage(): void {
    this.currentPage = 1;
    this.loadTasks();
  }

  goToPreviousPage(): void {
    if (this.currentPage > 1) {
      this.currentPage--;
      this.loadTasks();
    }
  }

  goToNextPage(): void {
    if (this.currentPage < this.totalPages) {
      this.currentPage++;
      this.loadTasks();
    }
  }

  goToLastPage(): void {
    this.currentPage = this.totalPages;
    this.loadTasks();
  }
}
