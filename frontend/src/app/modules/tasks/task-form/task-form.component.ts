import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { TasksService, Task } from '../tasks.service';

@Component({
  selector: 'app-task-form',
  templateUrl: './task-form.component.html',
  styleUrls: ['./task-form.component.css']
})
export class TaskFormComponent implements OnInit {
  taskForm: FormGroup;
  taskId: number | null = null;
  loading: boolean = false;
  errorMessage: string = '';

  constructor(
    private fb: FormBuilder,
    private tasksService: TasksService,
    private route: ActivatedRoute,
    private router: Router
  ) {
    this.taskForm = this.fb.group({
      title: ['', [Validators.required, Validators.minLength(3)]],
      description: [''],
      status: ['pending', Validators.required],
      assigned_to: [null]
    });
  }

  ngOnInit(): void {
    const id = this.route.snapshot.paramMap.get('id');
    if (id) {
      this.taskId = +id;
      this.loadTask();
    }
  }

  loadTask(): void {
    if (this.taskId) {
      this.tasksService.getTask(this.taskId).subscribe({
        next: (response) => {
          const task = response.task;
          this.taskForm.patchValue({
            title: task.title,
            description: task.description,
            status: task.status,
            assigned_to: task.assigned_to
          });
        },
        error: (error) => {
          console.error('Error loading task:', error);
          this.errorMessage = 'Failed to load task';
        }
      });
    }
  }

  onSubmit(): void {
    if (this.taskForm.valid) {
      this.loading = true;
      this.errorMessage = '';

      const taskData = this.taskForm.value;

      if (this.taskId) {
        this.tasksService.updateTask(this.taskId, taskData).subscribe({
          next: () => {
            this.router.navigate(['/tasks']);
          },
          error: (error) => {
            this.errorMessage = error.error?.message || 'Failed to update task';
            this.loading = false;
          }
        });
      } else {
        this.tasksService.createTask(taskData).subscribe({
          next: () => {
            this.router.navigate(['/tasks']);
          },
          error: (error) => {
            this.errorMessage = error.error?.message || 'Failed to create task';
            this.loading = false;
          }
        });
      }
    }
  }

  cancel(): void {
    this.router.navigate(['/tasks']);
  }
}
