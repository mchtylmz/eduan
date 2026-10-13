<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Locked;

trait ServerTableActions
{
    #[Locked]
    public ?int $detailId = null;

    public function mount(): void
    {
        $this->requireTableAccess();
    }

    public function showDetail(int $id): void
    {
        $this->requireTableAccess();
        $this->builder()->findOrFail($id);
        $this->detailId = $id;
        $this->dispatch('open-table-detail', table: $this->tableName ?? $this->resource);
    }

    public function closeDetail(): void
    {
        $this->detailId = null;
    }

    protected function selectedRecord(): ?Model
    {
        $this->requireTableAccess();

        return $this->detailId ? $this->builder()->findOrFail($this->detailId) : null;
    }

    protected function requireTableAccess(string $operation = 'view'): void
    {
        $actor = auth()->user();
        abort_unless($actor?->can('dashboard:access') && $actor->can($this->resource.':view') && $actor->can($this->resource.':'.$operation), 403);
    }
}
